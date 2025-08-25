<?php
require_once(__DIR__ . '/../includes/database/postDA.php');
require_once(__DIR__ . '/../includes/database/communityDA.php');
require_once(__DIR__ . '/../includes/database/communityMemberDA.php');
require_once(__DIR__ . '/../includes/database/userDA.php');

$communityDA = new CommunityDA();
$memberDA = new CommunityMemberDA();
$postDA = new PostDA();
$userDA = new UserDA();

// Get summary statistics
$totalCommunities = count($communityDA->getAllCommunities());
$totalMembers = count($memberDA->getAllCommunityMembers());
$totalPosts = count($postDA->getAllPosts());
$totalUsers = count($userDA->getAllUsers());

// Get community growth data
$communityGrowth = $communityDA->getFilteredCommunities(null, null, null, null);
$growthData = [];
$dates = [];
foreach ($communityGrowth as $community) {
    $date = date('Y-m', strtotime($community['created_at']));
    $growthData[$date] = ($growthData[$date] ?? 0) + 1;
    $dates[] = $date;
}
$dates = array_unique($dates);
sort($dates);

// Get member growth data
$members = $memberDA->getAllCommunityMembers();
$memberGrowthData = [];
foreach ($members as $member) {
    $date = date('Y-m', strtotime($member['joined_at']));
    $memberGrowthData[$date] = ($memberGrowthData[$date] ?? 0) + 1;
    if (!in_array($date, $dates)) {
        $dates[] = $date;
    }
}
sort($dates);

// Get member role distribution
$roleDistribution = ['Admin' => 0, 'Moderator' => 0, 'Member' => 0];
foreach ($members as $member) {
    $role = ucfirst(strtolower($member['role']));
    if (isset($roleDistribution[$role])) {
        $roleDistribution[$role]++;
    }
}

// Get post activity by community
$posts = $postDA->getAllPosts();
$communityPosts = [];
$communityNames = [];
foreach ($posts as $post) {
    $communityId = $post['community_id'];
    if (!isset($communityPosts[$communityId])) {
        $community = $communityDA->getCommunityById($communityId);
        $communityPosts[$communityId] = 0;
        $communityNames[$communityId] = $community['name'] ?? 'Unknown';
    }
    $communityPosts[$communityId]++;
}

// Get post activity by month
$postActivityByMonth = [];
foreach ($posts as $post) {
    $date = date('Y-m', strtotime($post['created_at']));
    $postActivityByMonth[$date] = ($postActivityByMonth[$date] ?? 0) + 1;
}

// Get top 5 most active communities
arsort($communityPosts);
$topCommunities = array_slice($communityPosts, 0, 5, true);
$topCommunityNames = [];
foreach (array_keys($topCommunities) as $communityId) {
    $topCommunityNames[] = $communityNames[$communityId];
}


// Format dates for charts
$formattedDates = [];
foreach ($dates as $date) {
    $formattedDates[] = date('M Y', strtotime($date));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Community Forum Admin Report</title>
    <link rel="stylesheet" href="../assets/css/adminForumReport.css">
    <link rel="stylesheet" href="../../css/main.css">
    <link rel="stylesheet" href="../../css/animations.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment.min.js"></script>
    <style>
        /* Hamburger menu for mobile */
        .hamburger {
            display: none;
            font-size: 24px;
            background: none;
            border: none;
            color: var(--primary-color);
            cursor: pointer;
            position: fixed;
            top: 15px;
            left: 15px;
            z-index: 101;
        }
        @media (max-width: 576px) {
            .hamburger {
                display: block;
            }
            .menu {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }
            .menu.open {
                transform: translateX(0);
            }
            .page-wrapper, footer {
                margin-left: 0;
                width: 100%;
            }
        }
        /* Apply animations to menu rows */
        .menu-row {
            animation: transitionIn-X 0.5s ease forwards;
        }
    </style>
</head>
<body>
    <!-- Hamburger Menu Toggle for Mobile -->
    <button class="hamburger">☰</button>

    <!-- Include Admin Menu -->
    <?php include('../../admin/adminMenu.php'); ?>
    
    <div class="page-wrapper">
        <div class="container">
            <header>
                <h1>Community Forum Admin Report</h1>
                <p class="report-date">Generated on <?php echo date('F j, Y'); ?></p>
            </header>
            
            <!-- Summary Cards -->
            <div class="summary-cards">
                <div class="card">
                    <h3>Total Communities</h3>
                    <p><?php echo $totalCommunities; ?></p>
                </div>
                <div class="card">
                    <h3>Total Members</h3>
                    <p><?php echo $totalMembers; ?></p>
                </div>
                <div class="card">
                    <h3>Total Posts</h3>
                    <p><?php echo $totalPosts; ?></p>
                </div>
                <div class="card">
                    <h3>Total Users</h3>
                    <p><?php echo $totalUsers; ?></p>
                </div>
            </div>

            <!-- Overview Section -->
            <section class="report-section">
                <h2>Platform Growth Overview</h2>
                <div class="charts growth-charts">
                    <!-- Community Growth Chart -->
                    <div class="chart-container">
                        <h3>Community Growth</h3>
                        <canvas id="growthChart"></canvas>
                    </div>
                    
                    <!-- Member Growth Chart -->
                    <div class="chart-container">
                        <h3>Member Growth</h3>
                        <canvas id="memberGrowthChart"></canvas>
                    </div>
                    
                    <!-- Post Activity Chart -->
                    <div class="chart-container">
                        <h3>Post Activity</h3>
                        <canvas id="postActivityChart"></canvas>
                    </div>
                </div>
            </section>

            <!-- Community Insights Section -->
            <section class="report-section">
                <h2>Community Insights</h2>
                <div class="charts">
                    <!-- Post Activity by Community Chart -->
                    <div class="chart-container">
                        <h3>Top 5 Active Communities</h3>
                        <canvas id="topCommunitiesChart"></canvas>
                    </div>

                    <!-- Role Distribution Chart -->
                    <div class="chart-container">
                        <h3>Member Role Distribution</h3>
                        <canvas id="roleChart"></canvas>
                    </div>
                </div>
            </section>

        
        </div>
    </div>

    <script>
        // Hamburger menu toggle
        const hamburger = document.querySelector('.hamburger');
        const menu = document.querySelector('.menu');
        hamburger.addEventListener('click', () => {
            menu.classList.toggle('open');
        });

        // Fix active state for Forum menu item
        document.addEventListener('DOMContentLoaded', () => {
            const forumMenuBtn = document.querySelector('.menu-icon-forums');
            const forumLink = document.querySelector('.menu-icon-forums + a');
            if (forumMenuBtn && forumLink) {
                forumMenuBtn.classList.add('menu-active', 'menu-icon-forums-active');
                forumLink.classList.add('non-style-link-menu-active');
            }
        });

        // Utility function to create gradient backgrounds
        function createGradient(ctx, color) {
            const gradient = ctx.createLinearGradient(0, 0, 0, 200);
            gradient.addColorStop(0, color);
            gradient.addColorStop(1, 'rgba(255, 255, 255, 0.3)');
            return gradient;
        }

        // Common chart options
        const commonChartOptions = {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                    labels: {
                        boxWidth: 12,
                        font: { size: 11 }
                    }
                },
                tooltip: {
                    mode: 'index',
                    intersect: false,
                }
            }
        };

        // Community Growth Chart
        const growthCtx = document.getElementById('growthChart').getContext('2d');
        const growthGradient = createGradient(growthCtx, 'rgba(0, 123, 255, 0.7)');
        new Chart(growthCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($formattedDates); ?>,
                datasets: [{
                    label: 'New Communities',
                    data: <?php echo json_encode(array_map(function($date) use ($growthData) {
                        return $growthData[$date] ?? 0;
                    }, $dates)); ?>,
                    borderColor: '#007bff',
                    backgroundColor: growthGradient,
                    fill: true,
                    tension: 0.4,
                    borderWidth: 2,
                    pointRadius: 3
                }]
            },
            options: {
                ...commonChartOptions,
                scales: {
                    y: { beginAtZero: true, title: { display: true, text: 'Count', font: { size: 10 } }, ticks: { font: { size: 10 } } },
                    x: { title: { display: true, text: 'Month', font: { size: 10 } }, ticks: { font: { size: 9 }, maxRotation: 45, minRotation: 45 } }
                }
            }
        });

        // Member Growth Chart
        const memberGrowthCtx = document.getElementById('memberGrowthChart').getContext('2d');
        const memberGradient = createGradient(memberGrowthCtx, 'rgba(40, 167, 69, 0.7)');
        new Chart(memberGrowthCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($formattedDates); ?>,
                datasets: [{
                    label: 'New Members',
                    data: <?php echo json_encode(array_map(function($date) use ($memberGrowthData) {
                        return $memberGrowthData[$date] ?? 0;
                    }, $dates)); ?>,
                    borderColor: '#28a745',
                    backgroundColor: memberGradient,
                    fill: true,
                    tension: 0.4,
                    borderWidth: 2,
                    pointRadius: 3
                }]
            },
            options: {
                ...commonChartOptions,
                scales: {
                    y: { beginAtZero: true, title: { display: true, text: 'Count', font: { size: 10 } }, ticks: { font: { size: 10 } } },
                    x: { title: { display: true, text: 'Month', font: { size: 10 } }, ticks: { font: { size: 9 }, maxRotation: 45, minRotation: 45 } }
                }
            }
        });

        // Post Activity Over Time Chart
        const postActivityCtx = document.getElementById('postActivityChart').getContext('2d');
        const postActivityGradient = createGradient(postActivityCtx, 'rgba(255, 193, 7, 0.7)');
        new Chart(postActivityCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($formattedDates); ?>,
                datasets: [{
                    label: 'Posts',
                    data: <?php echo json_encode(array_map(function($date) use ($postActivityByMonth) {
                        return $postActivityByMonth[$date] ?? 0;
                    }, $dates)); ?>,
                    borderColor: '#ffc107',
                    backgroundColor: postActivityGradient,
                    fill: true,
                    tension: 0.4,
                    borderWidth: 2,
                    pointRadius: 3
                }]
            },
            options: {
                ...commonChartOptions,
                scales: {
                    y: { beginAtZero: true, title: { display: true, text: 'Count', font: { size: 10 } }, ticks: { font: { size: 10 } } },
                    x: { title: { display: true, text: 'Month', font: { size: 10 } }, ticks: { font: { size: 9 }, maxRotation: 45, minRotation: 45 } }
                }
            }
        });

        // Role Distribution Chart
        const roleCtx = document.getElementById('roleChart').getContext('2d');
        new Chart(roleCtx, {
            type: 'doughnut',
            data: {
                labels: ['Admin', 'Moderator', 'Member'],
                datasets: [{
                    data: <?php echo json_encode(array_values($roleDistribution)); ?>,
                    backgroundColor: ['#007bff', '#28a745', '#ffc107'],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'right', labels: { boxWidth: 12, font: { size: 11 } } },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.raw || 0;
                                const total = context.dataset.data.reduce((acc, val) => acc + val, 0);
                                const percentage = Math.round((value / total) * 100);
                                return `${label}: ${value} (${percentage}%)`;
                            }
                        }
                    }
                },
                cutout: '65%'
            }
        });

        // Top Communities Chart
        const topCommunitiesCtx = document.getElementById('topCommunitiesChart').getContext('2d');
        new Chart(topCommunitiesCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_values($topCommunityNames)); ?>,
                datasets: [{
                    label: 'Posts',
                    data: <?php echo json_encode(array_values($topCommunities)); ?>,
                    backgroundColor: [
                        'rgba(0, 123, 255, 0.7)',
                        'rgba(40, 167, 69, 0.7)',
                        'rgba(255, 193, 7, 0.7)',
                        'rgba(220, 53, 69, 0.7)',
                        'rgba(111, 66, 193, 0.7)'
                    ],
                    borderColor: ['#007bff', '#28a745', '#ffc107', '#dc3545', '#6f42c1'],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: { beginAtZero: true, title: { display: true, text: 'Posts', font: { size: 10 } }, ticks: { font: { size: 10 } } },
                    x: { title: { display: true, text: 'Community', font: { size: 10 } }, ticks: { font: { size: 9 } } }
                },
                indexAxis: 'y',
                plugins: { legend: { display: false } }
            }
        });

    </script>
</body>
</html>