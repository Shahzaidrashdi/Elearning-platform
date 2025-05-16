<?php
require_once 'config.php';

// Redirect if not logged in or not a student
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit;
}

// Get student information
$studentId = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
$stmt->execute([$studentId]);
$student = $stmt->fetch();

// Get enrolled courses
$stmt = $pdo->prepare("SELECT c.id, c.title, c.description, c.thumbnail 
                      FROM enrollments e
                      JOIN courses c ON e.course_id = c.id
                      WHERE e.student_id = ?");
$stmt->execute([$studentId]);
$enrolledCourses = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - E-Learning Platform</title>
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
        
        /* Dashboard Content */
        .dashboard-container {
            padding: 2rem;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .welcome-section {
            background-color: white;
            border-radius: 10px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.05);
        }
        
        .welcome-section h2 {
            color: var(--secondary-color);
            margin-bottom: 1rem;
        }
        
        .welcome-section p {
            font-size: 1.1rem;
            color: #555;
        }
        
        .courses-section {
            background-color: white;
            border-radius: 10px;
            padding: 2rem;
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.05);
        }
        
        .course-card {
            border: none;
            border-radius: 10px;
            overflow: hidden;
            transition: transform 0.3s, box-shadow 0.3s;
            margin-bottom: 1.5rem;
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
        }
        
        .card-title {
            font-weight: 600;
            color: var(--primary-color);
        }
        
        .no-courses {
            text-align: center;
            padding: 3rem;
        }
        
        .no-courses i {
            font-size: 3rem;
            color: var(--primary-color);
            margin-bottom: 1rem;
        }
        
        .btn-enroll {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border: none;
            padding: 0.6rem 1.5rem;
            font-weight: 500;
        }
        
        .btn-enroll:hover {
            opacity: 0.9;
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
                  <a href="progress.php"><i class="fas fa-home"></i> Progress</a>
                  <a href="course.php"><i class="fas fa-book"></i> Courses</a>
                  <a href="logout.php" class="btn btn-light">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
    </nav>

    <!-- Dashboard Content -->
    <div class="dashboard-container">
        <!-- Welcome Section -->
        <div class="welcome-section">
            <h2>Welcome back, <?php echo htmlspecialchars($student['username']); ?>!</h2>
            <p>Continue your learning journey or explore new courses.</p>
        </div>

        <!-- Enrolled Courses Section -->
        <div class="courses-section">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h3>Your Enrolled Courses</h3>
                <a href="course.php" class="btn btn-enroll text-white">
                    <i class="fas fa-search"></i> Browse Courses
                </a>
            </div>

            <?php if (count($enrolledCourses) > 0): ?>
                <div class="row">
                    <?php foreach ($enrolledCourses as $course): ?>
                        <div class="col-md-4">
                            <div class="course-card card">
                                <img src="<?php echo htmlspecialchars($course['thumbnail']); ?>" 
                                     class="card-img-top" alt="<?php echo htmlspecialchars($course['title']); ?>">
                                <div class="card-body">
                                    <h5 class="card-title"><?php echo htmlspecialchars($course['title']); ?></h5>
                                    <p class="card-text"><?php 
                                        echo substr(htmlspecialchars($course['description']), 0, 100);
                                        if (strlen($course['description']) > 100) echo '...';
                                    ?></p>
                                    <a href="course.php?id=<?php echo $course['id']; ?>" 
                                       class="btn btn-sm btn-outline-primary">
                                        Continue Learning
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="no-courses">
                    <i class="fas fa-book-open"></i>
                    <h4>You're not enrolled in any courses yet</h4>
                    <p class="text-muted mb-4">Explore our courses and start your learning journey today</p>
                    <a href="course.php" class="btn btn-enroll text-white">
                        <i class="fas fa-search"></i> Browse Available Courses
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
