<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/animations.css">
    <link rel="stylesheet" href="../css/main.css">
    <link rel="stylesheet" href="../css/admin.css">
    <link rel="stylesheet" href="../css/adminIngest.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <title>Ingest</title>
    <style>
        .popup {
            animation: transitionIn-Y-bottom 0.5s;
        }

        .sub-table {
            animation: transitionIn-Y-bottom 0.5s;
        }

        .upload-box.drag-over {
            border: 2px dashed #009688;
            background-color: #f0fdfd;
        }
    </style>
</head>

<body>
    <?php

    session_start();

    if (isset($_SESSION["user"])) {
        if (($_SESSION["user"]) == "" or $_SESSION['usertype'] != 'a') {
            header("location: ../login.php");
        }
    } else {
        header("location: ../login.php");
    }

    include("../connection.php");
    ?>
    <div class="container">
        <?php include(__DIR__ . '/adminMenu.php'); ?>
        <div class="dash-body">
            <table border="0" width="100%" style="border-spacing: 0;margin:0;padding:0;margin-top:25px;padding-bottom:50px">
                <tr>
                    <td width="13%">
                        <a href="adminChatbot.php"><button class="login-btn btn-primary-soft btn btn-icon-back" style="padding-top:11px;padding-bottom:11px;margin-left:20px;width:125px">
                                <font class="tn-in-text">Back</font>
                            </button></a>
                    </td>
                    <td width="67%">
                    </td>
                    <td width="15%">
                        <p style="font-size: 14px;color: rgb(119, 119, 119);padding: 0;margin: 0;text-align: right;">Today's Date</p>
                        <p class="heading-sub12" style="padding: 0;margin: 0;">
                            <?php
                            date_default_timezone_set('Asia/Kuala_Lumpur');
                            echo date('Y-m-d');
                            ?>
                        </p>
                    </td>
                    <td width="10%">
                        <button class="btn-label" style="display: flex;justify-content: center;align-items: center;"><img src="../img/calendar.svg" width="100%"></button>
                    </td>
                </tr>
            </table>

            <div class="bottom-container">


                <div class="upload-box">
                    <h2 class="upload-heading">Upload Medical PDF</h2>
                    <form class="upload-form" id="upload-form" enctype="multipart/form-data">
                        <input class="upload-input" type="file" name="pdf_file" id="pdf_file" accept="application/pdf" required>
                        <button class="upload-button" type="submit">Upload</button>
                    </form>
                    <div class="pdf-progress-wrapper">
                        <div class="pdf-progress-label" id="pdf-progress-message"></div>
                        <div class="pdf-progress-bar-outer">
                            <div class="pdf-progress-bar-inner" id="pdf-progress-bar"></div>
                        </div>
                    </div>
                    <div class="pdf-section" id="uploaded-pdfs">
                        <h3 class="pdf-title">📚 Uploaded PDFs:</h3>
                        <ul class="pdf-list" id="pdf-list"></ul>
                    </div>
                </div>


                <div class="upload-box">
                    <h2>Run Python Ingestion Script</h2>
                    <form id="run-script-form">
                        <button type="submit" id="run-button">Run Python Script</button>
                        <button type="button" id="cancel-button" style="display: none;">Cancel Script</button>
                    </form>

                    <div id="progress-bar-container">
                        <div id="progress-bar">0%</div>
                    </div>
                    <div id="progress-message">Waiting to start...</div>
                    <div id="output"></div>
                </div>


                <script>
                    const form = document.getElementById('run-script-form');
                    const runButton = document.getElementById('run-button');
                    const cancelButton = document.getElementById('cancel-button');
                    const progressBar = document.getElementById('progress-bar');
                    const progressMessage = document.getElementById('progress-message');
                    const outputDiv = document.getElementById('output');
                    let progressPollingInterval = null;
                    let outputPollingInterval = null;

                    form.addEventListener('submit', function(event) {
                        event.preventDefault();
                        runButton.disabled = true;
                        cancelButton.style.display = 'inline-block';
                        cancelButton.disabled = false;
                        progressMessage.textContent = 'Starting ingestion process...';
                        progressBar.style.width = '0%';
                        progressBar.textContent = '0%';
                        outputDiv.textContent = '';

                        fetch('run_ingest.php', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/x-www-form-urlencoded',
                                },
                                body: 'run_script=1'
                            })
                            .then(response => response.json())
                            .then(data => {
                                console.log('Run script response:', data);
                                if (data.status === 'started') {
                                    progressPollingInterval = setInterval(fetchProgress, 100);
                                    outputPollingInterval = setInterval(fetchOutput, 100);
                                } else {
                                    progressMessage.textContent = 'Error starting script: ' + data.message;
                                    console.error('Error details:', data);
                                    resetUI();
                                }
                            })
                            .catch(error => {
                                console.error('Error starting script:', error);
                                progressMessage.textContent = 'Error starting script.';
                                resetUI();
                            });
                    });

                    cancelButton.addEventListener('click', function() {
                        cancelButton.disabled = true;
                        progressMessage.textContent = 'Cancelling script...';

                        fetch('cancel_ingest.php', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/x-www-form-urlencoded',
                                },
                                body: 'cancel_script=1'
                            })
                            .then(response => response.json())
                            .then(data => {
                                console.log('Cancel script response:', data);
                                if (data.status === 'cancelled') {
                                    progressMessage.textContent = 'Script cancelled successfully.';
                                } else {
                                    progressMessage.textContent = 'Error cancelling script: ' + data.message;
                                    console.error('Cancel error details:', data);
                                }
                                resetUI();
                            })
                            .catch(error => {
                                console.error('Error cancelling script:', error);
                                progressMessage.textContent = 'Error cancelling script.';
                                resetUI();
                            });
                    });



                    function fetchProgress() {
                        // Add cache buster to prevent caching
                        fetch('get_progress.php')
                            .then(response => {
                                if (!response.ok) throw new Error('Network response was not ok');
                                return response.json();
                            })
                            .then(data => {
                                console.log('Progress update:', data);


                                progressMessage.textContent = data.message;
                                progressBar.style.width = `${data.percent}%`;
                                progressBar.textContent = `${data.percent}%`;


                                // If complete, stop polling
                                if (data.percent >= 100) {
                                    progressMessage.textContent = 'Process completed!';
                                    fetchOutput();
                                    resetUI(); // This already clears the intervals
                                }

                            })
                            .catch(error => {
                                console.error('Progress fetch error:', error);
                                // Don't reset UI on temporary errors
                            });
                    }

                    // Modify your existing fetchOutput function
                    function fetchOutput() {
                        fetch('get_output.php')
                            .then(response => response.json())
                            .then(data => {
                                if (data.output) {
                                    outputDiv.textContent = data.output;
                                    outputDiv.scrollTop = outputDiv.scrollHeight;
                                }
                            })
                            .catch(error => {
                                console.error('Output fetch error:', error);
                            });
                    }

                    // Update your polling intervals to be more frequent
                    // progressPollingInterval = setInterval(fetchProgress, 1000); // Every 500ms
                    // outputPollingInterval = setInterval(fetchOutput, 3000); // Every second

                    function resetUI() {
                        clearInterval(progressPollingInterval);
                        clearInterval(outputPollingInterval);
                        runButton.disabled = false;
                        cancelButton.style.display = 'none';
                        cancelButton.disabled = true;
                    }
                </script>
            </div>
        </div>
    </div>
</body>

</html>

<script>
    function fetchUploadedPDFs() {
        $.ajax({
            url: 'http://localhost:8000/admin/retrieve_pdf.php',
            type: 'GET',
            success: function(data) {
                $('#pdf-list').html('');
                // Check if data is empty (no files)
                if (data.length === 0) {
                    $('#run-button').prop('disabled', true); // Disable run-button
                } else {
                    $('#run-button').prop('disabled', false); // Enable run-button
                    data.forEach(function(file) {
                        $('#pdf-list').append(
                            `<li class="pdf-item">
                            <span>${file}</span>
                            <button class="delete-pdf-btn" data-filename="${file}">Delete</button>
                        </li>`
                        );
                    });
                }
            },
            error: function() {
                $('#pdf-list').html('<li class="pdf-item">Unable to load uploaded PDFs.</li>');
            }
        });
    }


    $(document).ready(function() {
        fetchUploadedPDFs();

        // Delete PDF handler
        $(document).on('click', '.delete-pdf-btn', function() {
            const filename = $(this).data('filename');
            if (confirm(`Are you sure you want to delete ${filename}?`)) {
                $.ajax({
                    url: 'http://localhost:8000/admin/delete_pdf.php',
                    type: 'POST',
                    data: {
                        filename: filename
                    },
                    success: function(response) {
                        fetchUploadedPDFs(); // Refresh the PDF list
                        $('#pdf-progress-message').text(`Successfully deleted ${filename}`);
                    },
                    error: function(err) {
                        $('#pdf-progress-message').text(`Error deleting ${filename}`);
                        console.error(err);
                    }
                });
            }
        });
    });

    // Drag-and-drop upload validation for PDFs only
    const uploadBox = document.querySelector('.upload-box');

    uploadBox.addEventListener('dragover', (e) => {
        e.preventDefault();
        e.dataTransfer.dropEffect = 'copy';
        uploadBox.classList.add('drag-over');
    });

    uploadBox.addEventListener('dragleave', () => {
        uploadBox.classList.remove('drag-over');
    });

    uploadBox.addEventListener('drop', (e) => {
        e.preventDefault();
        uploadBox.classList.remove('drag-over');

        const files = e.dataTransfer.files;
        if (files.length === 0) return;

        const file = files[0];

        if (file.type !== 'application/pdf') {
            alert("Only PDF files are allowed.");
            return;
        }

        const formData = new FormData();
        formData.append("pdf_file", file);

        $('#pdf-progress-message').text("Uploading...");

        $.ajax({
            xhr: function() {
                const xhr = new XMLHttpRequest();
                xhr.upload.addEventListener("progress", function(evt) {
                    if (evt.lengthComputable) {
                        const percentComplete = evt.loaded / evt.total * 100;
                        $('#pdf-progress-bar').css('width', percentComplete + '%');
                        $('#pdf-progress-message').text("Uploading: " + Math.round(percentComplete) + "%");
                    }
                }, false);
                return xhr;
            },
            url: 'http://localhost:8000/admin/upload_pdf.php',
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            success: function(response) {
                const res = JSON.parse(response);
                $('#pdf-progress-message').text(res.message);
                if (res.message.startsWith("✅")) {
                    fetchUploadedPDFs();
                }
            },
            error: function(err) {
                $('#pdf-progress-message').text("❌ Upload failed.");
                console.error(err);
            }
        });
    });




    $('#upload-form').on('submit', function(e) {
        e.preventDefault();

        const fileInput = $('#pdf_file')[0];
        if (!fileInput.files.length) {
            alert("Please select a PDF file.");
            return;
        }

        const formData = new FormData();
        formData.append("pdf_file", fileInput.files[0]);

        $('#pdf-progress-container').show();
        $('#pdf-progress-bar').val(0);
        $('#pdf-progress-message').text("Uploading...");

        $.ajax({
            xhr: function() {
                const xhr = new window.XMLHttpRequest();
                xhr.upload.addEventListener("progress", function(evt) {
                    if (evt.lengthComputable) {
                        const percentComplete = evt.loaded / evt.total * 100;
                        $('#pdf-progress-bar').css('width', percentComplete + '%');
                        $('#pdf-progress-message').text("Uploading: " + Math.round(percentComplete) + "%");
                    }
                }, false);
                return xhr;
            },
            url: 'http://localhost:8000/admin/upload_pdf.php',
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            success: function(response) {
                const res = JSON.parse(response);
                $('#pdf-progress-message').text(res.message);
                if (res.message.startsWith("✅")) {
                    $('#pdf_file').val('');
                    fetchUploadedPDFs();
                }
            },
            error: function(err) {
                $('#pdf-progress-message').text("❌ Upload failed.");
                console.error(err);
            }
        });
    });
</script>