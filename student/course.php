<?php
require_once 'config.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Handle enrollment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['enroll_course'])) {
    $courseId = $_POST['course_id'];
    $studentId = $_SESSION['user_id'];
    
    // Check if already enrolled
    $stmt = $pdo->prepare("SELECT * FROM enrollments WHERE student_id = ? AND course_id = ?");
    $stmt->execute([$studentId, $courseId]);
    
    if ($stmt->rowCount() === 0) {
        // Enroll the student
        $stmt = $pdo->prepare("INSERT INTO enrollments (student_id, course_id) VALUES (?, ?)");
        $stmt->execute([$studentId, $courseId]);
        
        $_SESSION['success'] = "You have successfully enrolled in the course!";
        header("Location: student_dashboard.php");
        exit;
    } else {
        $_SESSION['error'] = "You are already enrolled in this course!";
    }
}

// Get all available courses
$stmt = $pdo->query("SELECT * FROM courses");
$courses = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Available Courses - E-Learning Platform</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #6e8efb;
            --secondary-color: #a777e3;
            --light-bg: #f8f9fa;
        }
        
        body {
            background-color: var(--light-bg);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        /* Navigation Bar */
        nav {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 1rem 2rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        .logo {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .logo i {
            font-size: 1.8rem;
        }
        
        .logo h1 {
            font-size: 1.5rem;
            font-weight: 600;
            margin: 0;
        }
        
        /* Courses Content */
        .courses-container {
            padding: 2rem;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .courses-header {
            background-color: white;
            border-radius: 10px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.05);
        }
        
        .courses-header h2 {
            color: var(--secondary-color);
            margin-bottom: 1rem;
        }
        
        .course-card {
            border: none;
            border-radius: 10px;
            overflow: hidden;
            transition: transform 0.3s, box-shadow 0.3s;
            margin-bottom: 1.5rem;
            height: 100%;
        }
        
        .course-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }
        
        .course-card img {
            height: 180px;
            object-fit: cover;
        }
        
        .card-body {
            padding: 1.5rem;
            display: flex;
            flex-direction: column;
            height: calc(100% - 180px);
        }
        
        .card-title {
            font-weight: 600;
            color: var(--primary-color);
        }
        
        .card-text {
            flex-grow: 1;
        }
        
        .btn-enroll {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border: none;
            padding: 0.6rem 1.5rem;
            font-weight: 500;
            width: 100%;
        }
        
        .btn-enroll:hover {
            opacity: 0.9;
        }
        
        .no-courses {
            text-align: center;
            padding: 3rem;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.05);
        }
        
        .no-courses i {
            font-size: 3rem;
            color: var(--primary-color);
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg">
        <div class="container-fluid">
            <div class="logo">
                <i class="fas fa-graduation-cap"></i>
                <h1>E-Learning Platform</h1>
            </div>
            <div class="d-flex">
                <a href="dashboard.php" class="btn btn-outline-light me-2">
                    <i class="fas fa-home"></i> Dashboard
                </a>
            </div>
        </div>
    </nav>

    <!-- Courses Content -->
    <div class="courses-container">
        <!-- Header Section -->
        <div class="courses-header">
            <h2><i class="fas fa-book-open"></i> Available Courses</h2>
            <p class="mb-0">Browse and enroll in courses to start learning</p>
        </div>

        <!-- Display success/error messages -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger">
                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <!-- Courses List -->
        <?php if (count($courses) > 0): ?>
            <div class="row">
                <?php foreach ($courses as $course): ?>
                    <div class="col-md-4">
                        <div class="course-card card">
                            <img src="<?php echo htmlspecialchars($course['thumbnail'] ?? 'assets/images/default-course.jpg'); ?>" 
                                 class="card-img-top" alt="<?php echo htmlspecialchars($course['title']); ?>">
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($course['title']); ?></h5>
                                <p class="card-text"><?php 
                                    echo substr(htmlspecialchars($course['description']), 0, 100);
                                    if (strlen($course['description']) > 100) echo '...';
                                ?></p>
                                <form method="POST" action="course.php">
                                    <input type="hidden" name="course_id" value="<?php echo $course['id']; ?>">
                                    <button type="submit" name="enroll_course" class="btn btn-enroll text-white">
                                        <i class="fas fa-user-plus"></i> Enroll Now
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="no-courses">
                <i class="fas fa-book"></i>
                <h4>No courses available yet</h4>
                <p class="text-muted">Check back later for new courses</p>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
