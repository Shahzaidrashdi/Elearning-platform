<?php
require_once 'config.php';

// Verify instructor role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'instructor') {
    header("Location: login.php");
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['video'])) {
    $lessonTitle = trim($_POST['title']);
    $lessonDescription = trim($_POST['description']);
    
    // Validate inputs
    if (empty($courseId) || empty($lessonTitle) || empty($lessonDescription)) {
        $error = "All fields are required";
    } else {
        $uploadDir = 'assets/uploads/videos/';
        $thumbnailDir = 'assets/uploads/thumbnails/';
        
        // Create directories if they don't exist
        if (!file_exists($uploadDir)) mkdir($uploadDir, 0777, true);
        if (!file_exists($thumbnailDir)) mkdir($thumbnailDir, 0777, true);
        
        $videoFile = $_FILES['video'];
        $originalName = basename($videoFile['name']);
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        
        // Validate file type
        $allowedExtensions = ['mp4', 'webm', 'mov'];
        if (!in_array($extension, $allowedExtensions)) {
            $error = "Only MP4, WebM, and MOV files are allowed";
        } elseif ($videoFile['size'] > 500000000) { // 500MB limit
            $error = "File size must be less than 500MB";
        } else {
            // Generate unique filename
            $newName = 'vid_' . uniqid() . '.' . $extension;
            $targetPath = $uploadDir . $newName;
            
            // Move uploaded file
            if (move_uploaded_file($videoFile['tmp_name'], $targetPath)) {
                try {
                    // Generate thumbnail
                    $thumbnailName = 'thumb_' . uniqid() . '.jpg';
                    $thumbnailPath = $thumbnailDir . $thumbnailName;
                    
                    // Use FFmpeg to get duration and generate thumbnail
                    $ffmpegCmd = FFMPEG_PATH . " -i " . escapeshellarg($targetPath) . " 2>&1";
                    $output = shell_exec($ffmpegCmd);
                    
                    // Extract duration
                    preg_match('/Duration: (\d+):(\d+):(\d+)/', $output, $matches);
                    $duration = ($matches[1] * 3600) + ($matches[2] * 60) + $matches[3];
                    
                    // Generate thumbnail at 10% of video
                    $thumbnailCmd = FFMPEG_PATH . " -i " . escapeshellarg($targetPath) . 
                                   " -ss 00:00:10 -vframes 1 " . escapeshellarg($thumbnailPath);
                    shell_exec($thumbnailCmd);
                    
                    // Insert into database
                    $stmt = $pdo->prepare("INSERT INTO lessons 
                                        (course_id, title, description, video_path, thumbnail_path, duration) 
                                        VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt->execute([
                        $courseId, 
                        $lessonTitle, 
                        $lessonDescription, 
                        $targetPath, 
                        $thumbnailPath, 
                        $duration
                    ]);
                    
                    $success = "Video uploaded and lesson created successfully!";
                } catch(PDOException $e) {
                    // Delete uploaded file if DB insert fails
                    unlink($targetPath);
                    if (file_exists($thumbnailPath)) unlink($thumbnailPath);
                    $error = "Database error: " . $e->getMessage();
                    error_log("Video upload error: " . $e->getMessage());
                }
            } else {
                $error = "Error uploading video file";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Video Lesson</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .upload-container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 2rem;
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        .upload-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        .upload-header i {
            font-size: 3rem;
            color: #6e8efb;
            margin-bottom: 1rem;
        }
        .file-upload {
            margin: 2rem 0;
        }
        .upload-area {
            border: 2px dashed #ced4da;
            border-radius: 8px;
            padding: 3rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
        }
        .upload-area:hover {
            border-color: #6e8efb;
            background-color: #f8faff;
        }
        .upload-area i {
            font-size: 3rem;
            color: #6e8efb;
            margin-bottom: 1rem;
        }
        .file-info {
            margin-top: 1rem;
            padding: 1rem;
            background-color: #f8f9fa;
            border-radius: 5px;
            display: none;
        }
        .btn-upload {
            background: linear-gradient(135deg, #6e8efb, #a777e3);
            border: none;
            padding: 0.75rem 1.5rem;
            font-weight: 500;
        }
        .btn-upload:hover {
            opacity: 0.9;
        }
    </style>
</head>
<body>
    
    <div class="upload-container">
        <div class="upload-header">
            <i class="fas fa-video"></i>
            <h2>Upload New Video Lesson</h2>
            <p class="text-muted">Share your knowledge with students</p>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        
        <form method="POST" enctype="multipart/form-data">
            
            <div class="mb-3">
                <label for="title" class="form-label">Lesson Title</label>
                <input type="text" class="form-control" id="title" name="title" 
                       value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : ''; ?>" required>
            </div>
            
            <div class="mb-3">
                <label for="description" class="form-label">Lesson Description</label>
                <textarea class="form-control" id="description" name="description" rows="3" required><?php 
                    echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; 
                ?></textarea>
            </div>
            
            <div class="file-upload">
                <label class="form-label">Video File</label>
                <div class="upload-area" id="uploadArea">
                    <i class="fas fa-cloud-upload-alt"></i>
                    <p>Drag & drop your video file here or click to browse</p>
                    <p class="small text-muted">Max file size: 500MB | Supported formats: MP4, WebM, MOV</p>
                    <input type="file" id="video" name="video" accept="video/*" required>
                    <div class="file-info" id="fileInfo"></div>
                </div>
            </div>
            
            <div class="d-grid">
                <button type="submit" class="btn btn-upload text-white">
                    <i class="fas fa-upload"></i> Upload Lesson
                </button>
            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // File upload drag and drop functionality
        const uploadArea = document.getElementById('uploadArea');
        const fileInput = document.querySelector('input[type="file"]');
        const fileInfo = document.getElementById('fileInfo');
        
        uploadArea.addEventListener('click', () => fileInput.click());
        
        fileInput.addEventListener('change', function(e) {
            if (this.files.length) {
                showFileInfo(this.files[0]);
            }
        });
        
        uploadArea.addEventListener('dragover', (e) => {
            e.preventDefault();
            uploadArea.classList.add('dragover');
        });
        
        uploadArea.addEventListener('dragleave', () => {
            uploadArea.classList.remove('dragover');
        });
        
        uploadArea.addEventListener('drop', (e) => {
            e.preventDefault();
            uploadArea.classList.remove('dragover');
            
            if (e.dataTransfer.files.length) {
                fileInput.files = e.dataTransfer.files;
                showFileInfo(e.dataTransfer.files[0]);
            }
        });
        
        function showFileInfo(file) {
            fileInfo.style.display = 'block';
            fileInfo.innerHTML = `
                <p><strong>Selected file:</strong> ${file.name}</p>
                <p><strong>Size:</strong> ${(file.size / (1024 * 1024)).toFixed(2)} MB</p>
                <p><strong>Type:</strong> ${file.type || 'Unknown'}</p>
            `;
        }
    </script>
</body>
</html>
