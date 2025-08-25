<?php
session_start();
$error   = $_GET['error']   ?? '';
$success = $_SESSION['success_message'] ?? '';
unset($_SESSION['success_message']); // Clear after display
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title>Create a New Forum</title>
  <link rel="stylesheet" href="../assets/css/createForum.css">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
  <div class="container">
    <h1 class="page-title">Forum Creation</h1>
    <h2>Create a New Forum</h2>

    <?php if ($error): ?>
      <script>
        document.addEventListener("DOMContentLoaded", function() {
          Swal.fire({
            icon: 'error',
            title: 'Oops...',
            text: "<?= htmlspecialchars($error) ?>",
            confirmButtonColor: '#d33'
          });
        });
      </script>
    <?php endif; ?>

    <form action="../controllers/createForumControlller.php"
          method="POST"
          enctype="multipart/form-data"
          class="filter-form">

      <label for="name">Community Name:</label>
      <input type="text" name="name" required>

      <label for="description">Description:</label>
      <textarea name="description" rows="4"></textarea>

      <label for="visibility">Visibility:</label>
      <select name="visibility" required>
        <option value="">Select Visibility</option>
        <option value="Public">Public</option>
        <option value="Private">Private</option>
        <option value="Anonymous">Anonymous</option>
      </select>

      <!-- New Category Field -->
      <label for="category">Category:</label>
      <select name="category" id="category-select" required>
        <option value="">Select Category</option>
        <option value="anxiety">Anxiety</option>
        <option value="stress">Stress</option>
        <option value="depression">Depression</option>
        <option value="trauma support">Trauma Support</option>
        <option value="mindfulness and meditation">Mindfulness & Meditation</option>
        <option value="other">Other</option>
      </select>

      <!-- Hidden text input for "Other" -->
      <div id="other-category-container" style="display:none; margin-top:0.5em;">
        <label for="other_category">Please specify:</label>
        <input type="text" name="other_category" id="other_category" placeholder="Custom category">
      </div>
      <!-- End Category -->

      <label for="picture">Community Picture (optional):</label>
      <div class="image-upload">
        <input type="file" name="picture" id="picture" accept="image/*" hidden>
        <div id="drop-area">
          <p>Drag & Drop an image or <span id="select-file">Browse</span></p>
          <img id="preview-image"
               src="/<?= !empty($forum['picture_url'])
                      ? htmlspecialchars($forum['picture_url'])
                      : '../assets/image/default_image.jpeg'; ?>"
               alt="Community Image"
               class="community-img">
        </div>
      </div>

      <div class="button-group">
        <button type="submit">Create Forum</button>
        <button type="button" onclick="window.location.href='../views/communityMain.php';">
          Back
        </button>
      </div>
    </form>
  </div>

  <?php if (isset($_GET['success'])): ?>
    <script>
      document.addEventListener("DOMContentLoaded", function() {
        Swal.fire({
          icon: 'success',
          title: 'Forum Created!',
          text: 'Your community forum has been created successfully.',
          confirmButtonColor: '#3085d6'
        }).then(() => {
          window.location.href = "../views/communityMain.php";
        });
      });
    </script>
  <?php endif; ?>

  <script>
    // Image upload handlers
    const dropArea = document.getElementById('drop-area');
    const fileInput = document.getElementById('picture');
    const previewImage = document.getElementById('preview-image');
    const selectFile = document.getElementById('select-file');

    // Ensure elements exist (for debugging)
    if (!dropArea || !fileInput || !previewImage || !selectFile) {
      console.error('One or more elements not found:', {
        dropArea: !!dropArea,
        fileInput: !!fileInput,
        previewImage: !!previewImage,
        selectFile: !!selectFile
      });
    }

    // Handle click on "Browse" to trigger file input
    selectFile.addEventListener('click', () => {
      fileInput.click();
      console.log('Browse clicked');
    });

    // Prevent default behavior for drag events to enable dropping
    dropArea.addEventListener('dragenter', (e) => {
      e.preventDefault();
      e.stopPropagation();
      dropArea.style.borderColor = 'blue';
      console.log('Drag enter');
    });

    dropArea.addEventListener('dragover', (e) => {
      e.preventDefault();
      e.stopPropagation();
      dropArea.style.borderColor = 'blue';
      console.log('Drag over');
    });

    dropArea.addEventListener('dragleave', (e) => {
      e.preventDefault();
      e.stopPropagation();
      dropArea.style.borderColor = '#ccc';
      console.log('Drag leave');
    });

    dropArea.addEventListener('drop', (e) => {
      e.preventDefault();
      e.stopPropagation();
      dropArea.style.borderColor = '#ccc';
      console.log('Drop event triggered');
      const files = e.dataTransfer.files;
      if (files.length > 0) {
        const file = files[0];
        console.log('File dropped:', file.name, file.type);
        handleFile(file);
        // Set the file to the input for form submission
        try {
          const dataTransfer = new DataTransfer();
          dataTransfer.items.add(file);
          fileInput.files = dataTransfer.files;
          console.log('File set to input:', fileInput.files.length, 'files');
        } catch (error) {
          console.error('Error setting file to input:', error);
          Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Failed to set the file for upload. Please try again.',
            confirmButtonColor: '#d33'
          });
        }
      } else {
        console.log('No files dropped');
      }
    });

    // Handle file selection via the input
    fileInput.addEventListener('change', (e) => {
      if (e.target.files.length > 0) {
        const file = e.target.files[0];
        console.log('File selected via input:', file.name, file.type);
        handleFile(file);
      } else {
        console.log('No file selected via input');
      }
    });

    // Function to handle the file and display preview
    function handleFile(file) {
      if (!file) {
        console.error('No file provided to handleFile');
        return;
      }
      if (!file.type.startsWith('image/')) {
        console.log('Invalid file type:', file.type);
        Swal.fire({
          icon: 'error',
          title: 'Invalid File',
          text: 'Please upload an image file.',
          confirmButtonColor: '#d33'
        });
        return;
      }
      const reader = new FileReader();
      reader.onload = (e) => {
        console.log('FileReader onload triggered');
        previewImage.src = e.target.result;
        console.log('Preview image updated:', previewImage.src);
      };
      reader.onerror = (e) => {
        console.error('FileReader error:', e);
        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: 'Failed to read the image file. Please try another file.',
          confirmButtonColor: '#d33'
        });
      };
      reader.readAsDataURL(file);
      console.log('File handled:', file.name);
    }

    // Category "Other" logic
    const categorySelect = document.getElementById('category-select');
    const otherContainer = document.getElementById('other-category-container');
    const otherInput = document.getElementById('other_category');

    categorySelect.addEventListener('change', () => {
      if (categorySelect.value === 'other') {
        otherContainer.style.display = 'block';
        otherInput.required = true;
      } else {
        otherContainer.style.display = 'none';
        otherInput.required = false;
        otherInput.value = '';
      }
    });
  </script>
</body>
</html>