  <?php
  require_once(__DIR__ . '/../includes/database/communityDA.php');
  session_start();

  $communityDA = new CommunityDA();

  // Get any error passed in the URL
  $error = isset($_GET['error']) ? $_GET['error'] : '';

  // Check if success parameter is set
  $success = (isset($_GET['success_message']) && $_GET['success_message'] == '1')
    ? "Forum updated successfully!" : '';

  $forumId = isset($_GET['id']) ? $_GET['id'] : null;
  if (!$forumId) {
    die("Forum ID is required.");
  }

  $forum = $communityDA->getCommunityById($forumId);
  if (!$forum) {
    die("Forum not found.");
  }
  ?>
  <!DOCTYPE html>
  <html lang="en">

  <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Forum</title>
    <link rel="stylesheet" href="../assets/css/createForum.css">
    <!-- SweetAlert CDN -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
      /* General styling */
      * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      }

      body {
        background-color: #f5f7fa;
        color: #333;
        line-height: 1.6;
      }

      .container {
        max-width: 800px;
        margin: 50px auto;
        padding: 30px;
        background-color: #fff;
        border-radius: 10px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
      }

      h2 {
        color: #2c3e50;
        margin-bottom: 25px;
        text-align: center;
        font-size: 28px;
        font-weight: 600;
        border-bottom: 2px solid #eaeaea;
        padding-bottom: 15px;
      }

      /* Form styling */
      form {
        display: flex;
        flex-direction: column;
      }

      label {
        margin: 12px 0 5px;
        font-weight: 500;
        color: #2c3e50;
      }

      input[type="text"],
      textarea,
      select {
        padding: 12px;
        border: 1px solid #ddd;
        border-radius: 5px;
        font-size: 16px;
        transition: border 0.3s;
      }

      input[type="text"]:focus,
      textarea:focus,
      select:focus {
        border-color: #3498db;
        outline: none;
        box-shadow: 0 0 5px rgba(52, 152, 219, 0.3);
      }

      textarea {
        min-height: 120px;
        resize: vertical;
      }

      /* Image upload styling */
      .image-upload {
        margin: 15px 0;
      }

      #drop-area {
        border: 2px dashed #ccc;
        border-radius: 8px;
        padding: 30px;
        text-align: center;
        cursor: pointer;
        transition: all 0.3s;
        background-color: #f9f9f9;
      }

      #drop-area:hover {
        border-color: #3498db;
        background-color: #f0f7fc;
      }

      #select-file {
        color: #3498db;
        cursor: pointer;
        text-decoration: underline;
        font-weight: 500;
      }

      .community-img {
        max-width: 200px;
        max-height: 200px;
        margin-top: 15px;
        border-radius: 5px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
      }

      /* Button styling */
      .button-group {
        margin-top: 30px;
        display: flex;
        gap: 15px;
        justify-content: center;
      }

      .update-btn,
      .cancel-btn {
        padding: 12px 25px;
        border: none;
        border-radius: 5px;
        font-size: 16px;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.3s;
        text-align: center;
        text-decoration: none;
        display: inline-block;
      }

      .update-btn {
        background-color: #3498db;
        color: white;
      }

      .update-btn:hover {
        background-color: #2980b9;
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
      }

      .cancel-btn {
        background-color: #e74c3c;
        color: white;
      }

      .cancel-btn:hover {
        background-color: #c0392b;
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
      }

      /* Responsive styles */
      @media (max-width: 768px) {
        .container {
          width: 95%;
          padding: 20px;
          margin: 20px auto;
        }

        .button-group {
          flex-direction: column;
        }

        .update-btn,
        .cancel-btn {
          width: 100%;
        }
      }
    </style>
  </head>

  <body>
    <div class="container">
      <h2>Forum Details</h2>
      <?php if (!empty($error)): ?>
        <script>
          Swal.fire({
            icon: 'error',
            title: 'Error',
            text: '<?= htmlspecialchars($error) ?>'
          });
        </script>
      <?php endif; ?>

      <form action="../controllers/updateForumController.php" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="forum_id" value="<?= htmlspecialchars($forum['community_id']); ?>">

        <label for="name">Community Name:</label>
        <input type="text" name="name" value="<?= htmlspecialchars($forum['name']); ?>" required>

        <label for="description">Description:</label>
        <textarea name="description" rows="4"><?= htmlspecialchars($forum['description']); ?></textarea>

        <label for="visibility">Visibility:</label>
        <select name="visibility" required>
          <option value="Public" <?= ($forum['visibility'] == 'Public') ? 'selected' : ''; ?>>Public</option>
          <option value="Private" <?= ($forum['visibility'] == 'Private') ? 'selected' : ''; ?>>Private</option>
          <option value="Anonymous" <?= ($forum['visibility'] == 'Anonymous') ? 'selected' : ''; ?>>Anonymous</option>
        </select>
        <!-- Category field -->
        <label for="category">Category:</label>
        <select name="category" id="category-select" required>
          <option value="">Select Category</option>
          <option value="anxiety" <?= $forum['category'] === 'anxiety' ? 'selected' : ''; ?>>Anxiety</option>
          <option value="stress" <?= $forum['category'] === 'stress' ? 'selected' : ''; ?>>Stress</option>
          <option value="depression" <?= $forum['category'] === 'depression' ? 'selected' : ''; ?>>Depression</option>
          <option value="trauma support" <?= $forum['category'] === 'trauma support' ? 'selected' : ''; ?>>Trauma Support</option>
          <option value="mindfulness and meditation" <?= $forum['category'] === 'mindfulness and meditation' ? 'selected' : ''; ?>>
            Mindfulness & Meditation
          </option>
          <option value="other" <?= !in_array($forum['category'], ['anxiety', 'stress', 'depression', 'trauma support', 'mindfulness and meditation']) ? 'selected' : ''; ?>>
            Other
          </option>
        </select>

        <!-- Custom category input, shown only if "Other" -->
        <div id="other-category-container" style="margin-top:0.5em; <?= !in_array($forum['category'], ['anxiety', 'stress', 'depression', 'trauma support', 'mindfulness and meditation']) ? 'display:block;' : 'display:none;'; ?>">
          <label for="other_category">Please specify:</label>
          <input
            type="text"
            name="other_category"
            id="other_category"
            placeholder="Custom category"
            value="<?= !in_array($forum['category'], ['anxiety', 'stress', 'depression', 'trauma support', 'mindfulness and meditation'])
                      ? htmlspecialchars($forum['category'])
                      : ''; ?>"
            <?= !in_array($forum['category'], ['anxiety', 'stress', 'depression', 'trauma support', 'mindfulness and meditation']) ? 'required' : ''; ?>>
        </div>
        <!-- end category -->

        <label for="picture">Community Picture (optional):</label>
        <div class="image-upload">
          <input type="file" name="picture" id="picture" accept="image/*" hidden>
          <div id="drop-area">
            <p>Drag & Drop an image or <span id="select-file">Browse</span></p>
            <img id="preview-image"
              src="/<?= !empty($forum['picture_url']) ? htmlspecialchars($forum['picture_url']) : 'assets/image/default_image.jpg'; ?>"
              alt="Community Image" class="community-img">
          </div>
        </div>

        <div class="button-group">
          <button type="submit" class="update-btn">Update Forum</button>
          <a href="forumDetail.php?id=<?= htmlspecialchars($forum['community_id']); ?>" class="cancel-btn">Cancel</a>
        </div>
      </form>
    </div>

    <!-- SweetAlert Success Message -->
    <?php if (!empty($success)): ?>
      <script>
        document.addEventListener("DOMContentLoaded", function() {
          Swal.fire({
            title: "Success!",
            text: "<?= htmlspecialchars($success); ?>",
            icon: "success",
            confirmButtonText: "Return to Forum"
          }).then(() => {
            window.location.href = "forumDetail.php?id=<?= htmlspecialchars($forum['community_id']); ?>";
          });
        });
      </script>
    <?php endif; ?>

    <!-- Image upload preview script -->
    <script>
      const dropArea = document.getElementById('drop-area');
      const fileInput = document.getElementById('picture');
      const previewImage = document.getElementById('preview-image');
      const selectFile = document.getElementById('select-file');

      // Click to open file selector
      selectFile.addEventListener('click', () => fileInput.click());

      // Drag & Drop Events
      dropArea.addEventListener('dragover', (event) => {
        event.preventDefault();
        dropArea.style.borderColor = "blue";
      });

      dropArea.addEventListener('dragleave', () => {
        dropArea.style.borderColor = "#ccc";
      });

      dropArea.addEventListener('drop', (event) => {
        event.preventDefault();
        dropArea.style.borderColor = "#ccc";
        const file = event.dataTransfer.files[0];
        handleFile(file);
      });

      // File selection via input
      fileInput.addEventListener('change', (event) => {
        const file = event.target.files[0];
        handleFile(file);
      });

      function handleFile(file) {
        if (file && file.type.startsWith('image/')) {
          const reader = new FileReader();
          reader.onload = function(e) {
            previewImage.src = e.target.result;
          };
          reader.readAsDataURL(file);
        }
      }

       // Category "Other" toggle
    const categorySelect = document.getElementById('category-select');
    const otherContainer = document.getElementById('other-category-container');
    const otherInput     = document.getElementById('other_category');

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