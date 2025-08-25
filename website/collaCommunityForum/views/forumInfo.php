<?php
session_start();
// Include necessary files
require_once(__DIR__ . '/../includes/database/communityDA.php');
require_once(__DIR__ . '/../includes/database/communityMemberDA.php');
require_once(__DIR__ . '/../includes/database/joinCommunityRequestDA.php');
require_once(__DIR__ . '/../includes/utilities/generateCommunityMemberID.php');
require_once(__DIR__ . '/../includes/database/userDA.php');

// Initialize data access objects
$joinRequestDA = new JoinCommunityRequestDA();
$communityDA = new CommunityDA();
$communityMemberDA = new CommunityMemberDA();

if (isset($_SESSION["user"])) {
    if ($_SESSION["user"] == "" && $_SESSION['usertype'] != 'p'  && $_SESSION['usertype'] != 'a'  && $_SESSION['usertype'] != 'd') {
        header("location: ../../login.php");
        exit;
    } else {
        $useremail = $_SESSION["user"];
    }
} else {
    header("location: ../../login.php");
    exit;
}
$userDA = new UserDA();
$userData = $userDA->getUserByEmail($useremail);
$userId = $userData['user_id'];

// Check if community_id is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    // Redirect to forums list or show error
    header("Location: forums.php");
    exit;
}

$communityId = $_GET['id'];

// Get community details
$community = $communityDA->getCommunityById($communityId);

// Check if community exists
if (!$community) {
    // Community not found
    header("Location: forums.php");
    exit;
}
$getCommunityMembers = $communityMemberDA->getCommunityMembersById($communityId);
// Get all members of this community

// Count members by role
$roleCount = [
    'admin' => 0,
    'moderator' => 0,
    'member' => 0
];

foreach ($getCommunityMembers as $member) {
    $role = strtolower($member['role']);
    if (isset($roleCount[$role])) {
        $roleCount[$role]++;
    } else {
        $roleCount['member']++; // Default to member if role is not recognized
    }
}

$isMemAdmin = false;
foreach ($getCommunityMembers as $member) {
    if(strtolower($member['role']) == "admin"){
        $isMemAdmin = true;
    }
   
}

// Calculate join date
$createdDate = new DateTime($community['created_at']);
$formattedCreatedDate = $createdDate->format('F j, Y');

// Get page title
$pageTitle = "Forum Details: " . htmlspecialchars($community['name']);
$isAdmin = ($userId == $community['creator_id']);
// Debugging: Log user and creator IDs to verify
error_log("Logged-in user ID: " . $userId . ", Creator ID: " . $community['creator_id']);

//user request to join forum code
// Get all pending join requests for this community if the user is admin
$pendingRequests = [];
if ($isAdmin) {
    // Get all requests and filter for this community and pending status
    $allRequests = $joinRequestDA->getAllJoinRequests();
    if ($allRequests) {
        foreach ($allRequests as $request) {
            if ($request['community_id'] == $communityId && $request['request_status'] == 'Pending') {
                $pendingRequests[] = $request;
            }
        }
    }
}

// Fetch usernames for creator, members, and pending requests
$authorUsernames = [];
// Add the creator's user_id
$uniqueUserIds = [$community['creator_id']];
// Add each member's user_id
foreach ($getCommunityMembers as $member) {
    $uniqueUserIds[] = $member['user_id'];
}
// Add each pending request's user_id (if any)
foreach ($pendingRequests as $request) {
    $uniqueUserIds[] = $request['user_id'];
}
// Remove duplicates
$uniqueUserIds = array_unique($uniqueUserIds);

// Fetch usernames for all unique user_ids
foreach ($uniqueUserIds as $userId) {
    if (!isset($authorUsernames[$userId])) {
        $user = $userDA->getUserById($userId);
        if (!$user) {
            error_log("User not found for user_id: " . $userId);
        }
        $authorUsernames[$userId] = $user ? ($community['visibility'] === 'Anonymous' && $isAdmin ? 'Anonymous User' : $user['username']) : 'Unknown User';
    }
}
?>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1.0">
    <title><?= htmlspecialchars($community['name']); ?> - Forum</title>
    <link href="../assets/css/forumInfo.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="../assets/js/forumInfo.js"></script>
</head>
<div class="main-content">
    <!-- Breadcrumb navigation -->
    <div class="navbar">
        <a href="communityMain.php">Forums</a>
        <span> > </span>
        <a href="forumDetail.php?id=<?php echo $communityId; ?>"><?php echo htmlspecialchars($community['name']); ?></a>
        <span> > </span>
        <span>Forum Details</span>
    </div>

    <!-- Forum Details Header -->
    <header>
        <h1>Forum Details: <?php echo htmlspecialchars($community['name']); ?></h1>
        <p>Viewing forum information and member list</p>
    </header>

    <!-- Forum General Information -->
    <div class="forum-details">
        <h2>General Information</h2>
        <div class="details-grid">
            <div class="details-row">
                <div class="detail-label">Forum ID:</div>
                <div class="detail-value"><?php echo htmlspecialchars($community['community_id']); ?></div>
            </div>
            <div class="details-row">
                <div class="detail-label">Name:</div>
                <div class="detail-value"><?php echo htmlspecialchars($community['name']); ?></div>
            </div>
            <div class="details-row">
                <div class="detail-label">Description:</div>
                <div class="detail-value"><?php echo htmlspecialchars($community['description']); ?></div>
            </div>
            <div class="details-row">
                <div class="detail-label">Category:</div>
                <div class="detail-value"><?php echo htmlspecialchars($community['category']); ?></div>
            </div>
            <div class="details-row">
                <div class="detail-label">Visibility:</div>
                <div class="detail-value"><?php echo htmlspecialchars($community['visibility']); ?></div>
            </div>
            <div class="details-row">
                <div class="detail-label">Created On:</div>
                <div class="detail-value"><?php echo $formattedCreatedDate; ?></div>
            </div>
            <div class="details-row">
                <div class="detail-label">Created By:</div>
                <div class="detail-value"><?php echo htmlspecialchars($authorUsernames[$community['creator_id']]); ?></div>
            </div>
            <div class="details-row">
                <div class="detail-label">Total Members:</div>
                <div class="detail-value"><?php echo array_sum($roleCount); ?></div>
            </div>
        </div>
    </div>
    <!-- Forum request section -->
    <div class="forum-request">
        <?php if (($isAdmin || $isMemAdmin) && !empty($pendingRequests)): ?>
            <h2>Pending Join Requests</h2>
            <div class="request-table-container">
                <table class="request-table">
                    <thead>
                        <tr>
                            <th>Request ID</th>
                            <th>Username</th>
                            <th>Request Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pendingRequests as $request): ?>
                            <tr data-id="<?php echo $request['request_id']; ?>">
                                <td><?php echo htmlspecialchars($request['request_id']); ?></td>
                                <td><?php echo htmlspecialchars($authorUsernames[$request['user_id']]); ?></td>
                                <td>
                                    <?php
                                    $requestDate = new DateTime($request['request_date']);
                                    echo $requestDate->format('M j, Y H:i');
                                    ?>
                                </td>
                                <td class="request-actions">
                                    <button class="view-request-btn" data-id="<?php echo $request['request_id']; ?>"
                                        data-reason="<?php echo htmlspecialchars($request['reason']); ?>"
                                        data-user="<?php echo htmlspecialchars($authorUsernames[$request['user_id']]); ?>">
                                        <i class="fas fa-eye"></i> View
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        
        <?php else: ?>
            <h2>Join Requests</h2>
            <p>There are currently no pending join requests for this forum.</p>
        <?php endif; ?>
    </div>

    <!-- View request dialog -->
    <div id="view-request-dialog" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Join Request Details</h2>
                <span class="close-btn">×</span>
            </div>
            <div class="modal-body">
                <div class="request-details">
                    <div class="detail-row">
                        <div class="detail-label">Request ID:</div>
                        <div class="detail-value" id="request-id-display"></div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Username:</div>
                        <div class="detail-value" id="request-user-display"></div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Reason for joining:</div>
                        <div class="detail-value" id="request-reason-display"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="cancel-btn">Close</button>
                    <button type="button" class="approve-btn" data-action="approve">Approve</button>
                    <button type="button" class="reject-btn" data-action="reject">Reject</button>
                </div>
            </div>
        </div>
    </div>
    <div class="forum-stat-members">
        <!-- Forum Statistics -->
        <div class="forum-statistics">
            <h2>Member Statistics</h2>
            <div class="stat-cards">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $roleCount['admin']; ?></div>
                    <div class="stat-label">Administrators</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $roleCount['moderator']; ?></div>
                    <div class="stat-label">Moderators</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $roleCount['member']; ?></div>
                    <div class="stat-label">Regular Members</div>
                </div>
            </div>
        </div>

        <!-- Member List Section with improved styling and AJAX functionality -->
        <div class="forum-members">
            <h2>Member List</h2>

            <!-- Search and filter options -->
            <div class="member-filters">
                <div class="filter-group">
                    <input type="text" id="member-search" placeholder="Search members...">
                    <select id="role-filter">
                        <option value="">All Roles</option>
                        <option value="admin">Administrators</option>
                        <option value="moderator">Moderators</option>
                        <option value="member">Regular Members</option>
                    </select>
                </div>
                <div class="filter-actions">
                    <button id="apply-filters" class="filter-btn primary-btn">
                        <i class="fas fa-filter"></i> Apply Filters
                    </button>
                    <button id="reset-filters" class="filter-btn secondary-btn">
                        <i class="fas fa-undo"></i> Reset
                    </button>
                </div>
            </div>

            <!-- Members table -->
            <div class="member-table-container">
                <table class="member-table" id="member-table">
                    <thead>
                        <tr>
                            <th>Member</th>
                            <th>Role</th>
                            <th>Joined Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($getCommunityMembers as $member): ?>
                            <tr data-role="<?php echo strtolower($member['role']); ?>" data-id="<?php echo $member['membership_id']; ?>" data-name="<?php echo htmlspecialchars($authorUsernames[$member['user_id']]); ?>">
                                <td class="member-info">
                                    <div class="member-details">
                                        <div class="member-name"><?php echo htmlspecialchars($authorUsernames[$member['user_id']]); ?></div>
                                    </div>
                                </td>
                                <td class="member-role">
                                    <span class="role-badge role-<?php echo strtolower($member['role']); ?>">
                                        <?php echo ucfirst($member['role']); ?>
                                    </span>
                                </td>
                                <td class="joined-date">
                                    <?php
                                    $joinedDate = new DateTime($member['joined_at']);
                                    echo $joinedDate->format('M j, Y');
                                    ?>
                                </td>
                                <td class="member-actions">
                                    <?php
                                    // Debugging: Log member and user comparison
                                    error_log("Comparing user_id: " . $member['user_id'] . " with creator_id: " . $community['creator_id']);
                                    if ($isAdmin && $member['user_id'] != $community['creator_id']): ?>
                                        <button class="action-btn edit-role-btn" data-id="<?php echo $member['membership_id']; ?>" data-role="<?php echo strtolower($member['role']); ?>" title="Adjust Role" onclick="console.log('Edit button clicked for ID: <?php echo $member['membership_id']; ?>')">
                                            <i class="fas fa-user-edit"></i>
                                        </button>
                                        <button class="action-btn remove-btn" data-id="<?php echo $member['membership_id']; ?>" title="Remove Member" onclick="console.log('Remove button clicked for ID: <?php echo $member['membership_id']; ?>')">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Edit Member Role Dialog with improved styling -->
        <div id="edit-role-dialog" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h2><i class="fas fa-user-edit"></i> Adjust Member Role</h2>
                    <span class="close-btn">×</span>
                </div>
                <div class="modal-body">
                    <div class="member-info-display">
                        <p>Member ID: <span id="edit-member-id" class="highlight-text"></span></p>
                        <p>Current Role: <span id="current-role" class="highlight-text"></span></p>
                    </div>
                    <div class="role-selection">
                        <label for="role-select">Select new role:</label>
                        <select id="role-select" class="styled-select">
                            <option value="admin">Administrator</option>
                            <option value="moderator">Moderator</option>
                            <option value="member">Regular Member</option>
                        </select>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="cancel-btn secondary-btn">
                            <i class="fas fa-times"></i> Cancel
                        </button>
                        <button type="button" class="confirm-edit-btn primary-btn">
                            <i class="fas fa-check"></i> Save Changes
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Confirmation Dialog for Member Removal with improved styling -->
        <div id="remove-confirm-dialog" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h2><i class="fas fa-exclamation-triangle"></i> Confirm Removal</h2>
                    <span class="close-btn">×</span>
                </div>
                <div class="modal-body">
                    <div class="warning-message">
                        <p>Are you sure you want to remove this member from the forum?</p>
                        <p class="member-to-remove">Member ID: <span id="remove-member-id" class="highlight-text"></span></p>
                        <p class="warning-text">This action cannot be undone.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="cancel-btn secondary-btn">
                            <i class="fas fa-times"></i> Cancel
                        </button>
                        <button type="button" class="confirm-remove-btn danger-btn">
                            <i class="fas fa-trash-alt"></i> Remove
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Custom Toast Notification -->
    <div id="toast-notification" class="toast-notification">
        <div class="toast-icon"></div>
        <div class="toast-message"></div>
        <button class="toast-close">×</button>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM fully loaded'); // Debug log

    // Check URL parameters on page load
    const urlParams = new URLSearchParams(window.location.search);
    console.log('URL parameters:', urlParams.toString());
    if (urlParams.get('status') === 'approved') {
        showToast('Join request approved successfully! The user has been added to the forum.', 'success');
    } else if (urlParams.get('status') === 'rejected') {
        showToast('Join request rejected successfully.', 'success');
    }

    // Function to show toast notification
    function showToast(message, type = 'success') {
        console.log('Showing toast:', message, type);
        const toast = document.getElementById('toast-notification');
        // Clear existing timeout
        clearTimeout(toast.timeoutId);
        toast.classList.remove('show', 'success', 'error');
        const toastMessage = toast.querySelector('.toast-message');
        const toastIcon = toast.querySelector('.toast-icon');
        toastIcon.classList.remove('fa-check-circle', 'fa-exclamation-circle');
        toastMessage.textContent = '';

        // Set new message and type
        toastMessage.textContent = message;
        if (type === 'success') {
            toast.classList.add('success');
            toastIcon.classList.add('fa-check-circle');
        } else {
            toast.classList.add('error');
            toastIcon.classList.add('fa-exclamation-circle');
        }

        // Show toast
        toast.classList.add('show');

        // Auto-hide after 3 seconds
        toast.timeoutId = setTimeout(() => {
            toast.classList.remove('show');
        }, 3000);
    }

    // Close toast on click
    document.querySelector('.toast-close').addEventListener('click', () => {
        document.getElementById('toast-notification').classList.remove('show');
    });

    function filterMembers() {
        try {
            console.log('Filtering members');
            const searchInput = document.getElementById('member-search');
            const roleFilter = document.getElementById('role-filter');
            const table = document.getElementById('member-table');
            const tbody = table.querySelector('tbody');
            const rows = tbody.getElementsByTagName('tr');

            if (!searchInput || !roleFilter || !table || !tbody) {
                throw new Error('Filter elements not found');
            }

            const input = searchInput.value.toLowerCase().trim();
            const roleValue = roleFilter.value.toLowerCase();
            console.log('Search input:', input, 'Role filter:', roleValue);

            for (let i = 0; i < rows.length; i++) {
                const row = rows[i];
                const name = row.getAttribute('data-name')?.toLowerCase() || '';
                const role = row.getAttribute('data-role')?.toLowerCase() || '';

                if (!name || !role) {
                    console.warn('Row missing data attributes:', { name, role, rowIndex: i });
                    row.style.display = 'none';
                    continue;
                }

                const nameMatch = input === '' || name.includes(input);
                const roleMatch = roleValue === '' || role === roleValue;
                const isVisible = nameMatch && roleMatch;

                console.log('Row:', i, 'Name:', name, 'Role:', role, 'Visible:', isVisible);
                row.style.display = isVisible ? '' : 'none';
            }
        } catch (error) {
            console.error('Error in filterMembers:', error);
            showToast('Error filtering members: ' + error.message, 'error');
        }
    }

    function resetFilters() {
        try {
            console.log('Resetting filters');
            const searchInput = document.getElementById('member-search');
            const roleFilter = document.getElementById('role-filter');

            if (!searchInput || !roleFilter) {
                throw new Error('Filter elements not found');
            }

            searchInput.value = '';
            roleFilter.value = '';
            filterMembers();
        } catch (error) {
            console.error('Error in resetFilters:', error);
            showToast('Error resetting filters: ' + error.message, 'error');
        }
    }

    // Set up event listeners for filter controls
    try {
        const searchInput = document.getElementById('member-search');
        const roleFilter = document.getElementById('role-filter');
        const applyButton = document.getElementById('apply-filters');
        const resetButton = document.getElementById('reset-filters');

        if (!searchInput || !roleFilter || !applyButton || !resetButton) {
            throw new Error('Filter controls not found');
        }

        searchInput.addEventListener('input', () => {
            console.log('Search input changed:', searchInput.value);
            filterMembers();
        });

        roleFilter.addEventListener('change', () => {
            console.log('Role filter changed:', roleFilter.value);
            filterMembers();
        });

        applyButton.addEventListener('click', () => {
            console.log('Apply filters clicked');
            filterMembers();
        });

        resetButton.addEventListener('click', () => {
            console.log('Reset filters clicked');
            resetFilters();
        });
    } catch (error) {
        console.error('Error setting up filter event listeners:', error);
        showToast('Error initializing filters: ' + error.message, 'error');
    }

    // View join request functionality
    document.querySelectorAll('.view-request-btn').forEach(button => {
        button.addEventListener('click', function() {
            try {
                console.log('View request button clicked');
                const requestId = this.getAttribute('data-id');
                const reason = this.getAttribute('data-reason');
                const user = this.getAttribute('data-user');

                document.getElementById('request-id-display').textContent = requestId;
                document.getElementById('request-user-display').textContent = user;
                document.getElementById('request-reason-display').textContent = reason;
                document.getElementById('view-request-dialog').classList.add('show');
            } catch (error) {
                console.error('Error in view-request-btn click handler:', error);
                showToast('Error viewing request: ' + error.message, 'error');
            }
        });
    });

    // EDIT HERE: Updated join request approval/rejection handler
document.querySelectorAll('.approve-btn, .reject-btn').forEach(button => {
    button.addEventListener('click', function() {
        try {
            console.log(`${this.getAttribute('data-action')} join request button clicked`);
            const requestId = document.getElementById('request-id-display').textContent;
            const communityId = '<?php echo $communityId; ?>';
            const action = this.getAttribute('data-action');

            fetch('../controllers/joinForumRequestController.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    request_id: requestId,
                    community_id: communityId,
                    action: action
                })
            })
            .then(response => {
                console.log('Join request response status:', response.status);
                // EDIT HERE: Log raw response text for debugging
                response.text().then(text => {
                    console.log('Raw response text:', text);
                    try {
                        const data = JSON.parse(text);
                        console.log('Parsed response data:', data);
                        if (data.success) {
                            showToast(
                                action === 'approve'
                                    ? 'Join request approved successfully! The user has been added to the forum.'
                                    : 'Join request rejected successfully.',
                                'success'
                            );
                            setTimeout(() => location.replace(`forumInfo.php?id=${communityId}`), 1500);
                        } else {
                            console.error('Server error:', data.message);
                            showToast(`Failed to ${action} join request: ${data.message}`, 'error');
                        }
                    } catch (e) {
                        // EDIT HERE: Show raw response in toast for debugging
                        console.error('Failed to parse JSON:', e, 'Raw text:', text);
                        showToast(`Invalid server response: ${text.substring(0, 100)}`, 'error');
                    }
                });
                if (!response.ok) {
                    throw new Error(`Failed to ${action} join request: HTTP error ${response.status}`);
                }
                return response;
            })
            .catch(error => {
                console.error(`Error ${action}ing join request:`, error);
                showToast(`Error ${action}ing join request: ${error.message}`, 'error');
            });
            document.getElementById('view-request-dialog').classList.remove('show');
        } catch (error) {
            console.error(`Error in ${this.getAttribute('data-action')}-btn click handler:`, error);
            showToast(`Error ${this.getAttribute('data-action')}ing join request: ${error.message}`, 'error');
        }
    });
});

    // Edit role functionality
    document.querySelectorAll('.edit-role-btn').forEach(button => {
        console.log('Found edit button:', button); // Debug log
        button.addEventListener('click', function(e) {
            try {
                e.preventDefault();
                console.log('Edit role button clicked');
                let membershipId = this.getAttribute('data-id');
                let currentRole = this.getAttribute('data-role');
                
                // Update modal content
                let editMemberIdElement = document.getElementById('edit-member-id');
                let currentRoleElement = document.getElementById('current-role');
                let roleSelectElement = document.getElementById('role-select');
                let editDialog = document.getElementById('edit-role-dialog');

                if (!editMemberIdElement || !currentRoleElement || !roleSelectElement || !editDialog) {
                    throw new Error('Modal elements not found');
                }

                editMemberIdElement.textContent = membershipId;
                currentRoleElement.textContent = currentRole;
                roleSelectElement.value = currentRole;

                // Show the modal using the .show class
                console.log('Showing edit-role-dialog');
                editDialog.classList.add('show');
            } catch (error) {
                console.error('Error in edit-role-btn click handler:', error);
                showToast('Error editing role: ' + error.message, 'error');
            }
        });
    });

    // Confirm edit role
    document.querySelector('#edit-role-dialog .confirm-edit-btn').addEventListener('click', function() {
        try {
            console.log('Confirm edit role button clicked');
            let membershipId = document.getElementById('edit-member-id').textContent;
            let newRole = document.getElementById('role-select').value;
            let communityId = '<?php echo $communityId; ?>';

            fetch('../controllers/updateMemberRoleController.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    membership_id: membershipId,
                    role: newRole,
                    community_id: communityId
                })
            })
            .then(response => {
                console.log('Update role response status:', response.status);
                if (!response.ok) {
                    throw new Error(`Failed to update role: HTTP error ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                console.log('Update role response data:', data);
                if (data.success) {
                    showToast('Member role updated successfully', 'success');
                    setTimeout(() => location.replace(`forumInfo.php?id=${communityId}`), 1500);
                } else {
                    console.error('Server error:', data.message);
                    showToast(`Failed to update role: ${data.message}`, 'error');
                }
            })
            .catch(error => {
                console.error('Error updating role:', error);
                showToast(`Error updating role: ${error.message}`, 'error');
            });
            document.getElementById('edit-role-dialog').classList.remove('show');
        } catch (error) {
            console.error('Error in confirm-edit-btn click handler:', error);
            showToast('Error updating role: ' + error.message, 'error');
        }
    });

    // Remove functionality
    document.querySelectorAll('.remove-btn').forEach(button => {
        console.log('Found remove button:', button); // Debug log
        button.addEventListener('click', function(e) {
            try {
                e.preventDefault();
                console.log('Remove button clicked');
                let membershipId = this.getAttribute('data-id');
                
                // Validate membershipId
                if (!membershipId || membershipId.trim() === '') {
                    throw new Error('Invalid membership ID');
                }
                
                console.log('Opening remove dialog for membership_id:', membershipId);
                
                // Update modal content
                let removeMemberIdElement = document.getElementById('remove-member-id');
                let removeDialog = document.getElementById('remove-confirm-dialog');

                if (!removeMemberIdElement || !removeDialog) {
                    throw new Error('Remove modal elements not found');
                }

                removeMemberIdElement.textContent = membershipId;

                // Show the modal using the .show class
                console.log('Showing remove-confirm-dialog');
                removeDialog.classList.add('show');
            } catch (error) {
                console.error('Error in remove-btn click handler:', error);
                showToast('Error removing member: ' + error.message, 'error');
            }
        });
    });

    document.querySelector('.confirm-remove-btn').addEventListener('click', function() {
        try {
            console.log('Confirm remove button clicked');
            let membershipId = document.getElementById('remove-member-id').textContent;
            let communityId = '<?php echo $communityId; ?>';
            
            // Validate membershipId
            if (!membershipId || membershipId.trim() === '') {
                throw new Error('Invalid membership ID');
            }
            
            console.log('Removing member with membership_id:', membershipId, 'from community:', communityId);

            fetch('../controllers/removeMemberForumController.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    membership_id: membershipId,
                    community_id: communityId
                })
            })
            .then(response => {
                console.log('Remove member response status:', response.status);
                if (!response.ok) {
                    throw new Error(`Failed to remove member: HTTP error ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                console.log('Remove member response data:', data);
                if (data.success) {
                    showToast('Member removed successfully', 'success');
                    setTimeout(() => location.replace(`forumInfo.php?id=${communityId}`), 1500);
                } else {
                    console.error('Server error:', data.message);
                    showToast(`Failed to remove member: ${data.message}`, 'error');
                }
            })
            .catch(error => {
                console.error('Error removing member:', error);
                showToast(`Error removing member: ${error.message}`, 'error');
            });
            document.getElementById('remove-confirm-dialog').classList.remove('show');
        } catch (error) {
            console.error('Error in confirm-remove-btn click handler:', error);
            showToast('Error removing member: ' + error.message, 'error');
        }
    });

    // Close dialogs
    document.querySelectorAll('.close-btn').forEach(button => {
        button.addEventListener('click', function() {
            try {
                console.log('Close button clicked');
                this.closest('.modal').classList.remove('show');
            } catch (error) {
                console.error('Error in close-btn click handler:', error);
            }
        });
    });

    document.querySelectorAll('.cancel-btn').forEach(button => {
        button.addEventListener('click', function() {
            try {
                console.log('Cancel button clicked');
                this.closest('.modal').classList.remove('show');
            } catch (error) {
                console.error('Error in cancel-btn click handler:', error);
            }
        });
    });
});
</script>