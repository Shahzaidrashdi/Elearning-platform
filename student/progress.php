<?php
require_once 'config.php';

// Update progress when student watches a lesson
function updateProgress($userId, $lessonId, $courseId, $percentage) {
    global $pdo;
    
    // Check if progress record exists
    $stmt = $pdo->prepare("SELECT * FROM progress 
                          WHERE user_id = ? AND lesson_id = ? AND course_id = ?");
    $stmt->execute([$userId, $lessonId, $courseId]);
    $progress = $stmt->fetch();
    
    if ($progress) {
        // Update existing record
        $stmt = $pdo->prepare("UPDATE progress 
                              SET progress_percentage = ?, last_watched = NOW() 
                              WHERE id = ?");
        $stmt->execute([$percentage, $progress['id']]);
    } else {
        // Create new record
        $stmt = $pdo->prepare("INSERT INTO progress 
                              (user_id, lesson_id, course_id, progress_percentage, last_watched) 
                              VALUES (?, ?, ?, ?, NOW())");
        $stmt->execute([$userId, $lessonId, $courseId, $percentage]);
    }
    
    // Mark as completed if >= 95% watched
    if ($percentage >= 95) {
        $stmt = $pdo->prepare("UPDATE progress SET completed = TRUE 
                              WHERE user_id = ? AND lesson_id = ? AND course_id = ?");
        $stmt->execute([$userId, $lessonId, $courseId]);
    }
    
    // Check if all lessons are completed
    checkCourseCompletion($userId, $courseId);
}

// Check if all lessons in a course are completed
function checkCourseCompletion($userId, $courseId) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as total_lessons FROM lessons WHERE course_id = ?");
    $stmt->execute([$courseId]);
    $totalLessons = $stmt->fetch()['total_lessons'];
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as completed_lessons FROM progress 
                          WHERE user_id = ? AND course_id = ? AND completed = TRUE");
    $stmt->execute([$userId, $courseId]);
    $completedLessons = $stmt->fetch()['completed_lessons'];
    
    if ($completedLessons >= $totalLessons) {
        $stmt = $pdo->prepare("UPDATE enrollments SET completed = TRUE 
                              WHERE student_id = ? AND course_id = ?");
        $stmt->execute([$userId, $courseId]);
        
        // Generate certificate
        generateCertificate($userId, $courseId);
    }
}

// Generate PDF certificate
function generateCertificate($userId, $courseId) {
    // Implementation using TCPDF or Dompdf
    // This would create a PDF file and store it in the database or filesystem
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Course Lesson - E-Learning Platform</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.plyr.io/3.7.8/plyr.css" />
    <link rel="stylesheet" href="progress.css">
</head>
<body>
    <div class="lesson-container">
        <!-- Navigation Header -->
            <i class="fas fa-arrow-left"></i> Back to Course
            </a>
           
            <h2 id="lessonTitle">Loading Lesson...</h2>
            <div class="progress-indicator">
                <span id="completionStatus">Not Started</span>
                <div class="progress">
                    <div id="progressBar" class="progress-bar" role="progressbar" style="width: 0%"></div>
                </div>
            </div>
        </header>

        <!-- Main Content Area -->
        <div class="lesson-content">
            <!-- Video Player Section -->
            <div class="video-container">
                <video id="player" playsinline controls>
                    <source src="" type="video/mp4" id="videoSource">
                </video>
            </div>

            <!-- Lesson Description -->
            <div class="lesson-description card">
                <div class="card-body">
                    <h4>About This Lesson</h4>
                    <p id="lessonDescription">Loading description...</p>
                </div>
            </div>

            <!-- Completion Badge -->
            <div id="completionBadge" class="completion-badge" style="display: none;">
                <i class="fas fa-check-circle"></i>
                <span>Lesson Completed!</span>
            </div>

            <!-- Navigation Buttons -->
            <div class="lesson-navigation">
                <button id="prevLesson" class="btn btn-outline-primary">
                    <i class="fas fa-chevron-left"></i> Previous
                </button>
                <button id="nextLesson" class="btn btn-primary">
                    Next <i class="fas fa-chevron-right"></i>
                </button>
            </div>
        </div>

        <!-- Course Progress Sidebar -->
        <aside class="course-progress">
            <div class="progress-sidebar card">
                <div class="card-header">
                    <h5>Course Progress</h5>
                </div>
                <div class="card-body">
                    <div class="course-progress-summary">
                        <div class="progress-circle">
                            <svg width="100" height="100" viewBox="0 0 36 36" class="circular-chart">
                                <path class="circle-bg" d="M18 2.0845
                                    a 15.9155 15.9155 0 0 1 0 31.831
                                    a 15.9155 15.9155 0 0 1 0 -31.831"/>
                                <path id="courseProgressCircle" class="circle" stroke-dasharray="0, 100" d="M18 2.0845
                                    a 15.9155 15.9155 0 0 1 0 31.831
                                    a 15.9155 15.9155 0 0 1 0 -31.831"/>
                                <text x="18" y="20" class="percentage">0%</text>
                            </svg>
                        </div>
                        <div class="progress-stats">
                            <p><span id="completedLessons">0</span> of <span id="totalLessons">0</span> lessons completed</p>
                            <button id="viewCertificate" class="btn btn-sm btn-success" style="display: none;">
                                <i class="fas fa-certificate"></i> View Certificate
                            </button>
                        </div>
                    </div>
                    <hr>
                    <h6>Lessons</h6>
                    <ul class="lesson-list" id="lessonList">
                        <!-- Lessons will be populated by JavaScript -->
                    </ul>
                </div>
            </div>
        </aside>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
    <script src="https://cdn.plyr.io/3.7.8/plyr.js"></script>
    <script>
        // Initialize video player
        const player = new Plyr('#player');
        
        // Current lesson and course data
        let currentLessonId = <?= $_GET['lesson_id'] ?? 0 ?>;
        const currentCourseId = <?= $_GET['course_id'] ?? 0 ?>;
        const userId = <?= $_SESSION['user_id'] ?? 0 ?>;
        
        // Track video progress
        player.on('timeupdate', event => {
            const percentage = (player.currentTime / player.duration) * 100;
            updateProgress(percentage);
            
            // Update progress bar
            document.getElementById('progressBar').style.width = `${percentage}%`;
            
            // Update completion status text
            const statusElement = document.getElementById('completionStatus');
            if (percentage >= 95) {
                statusElement.textContent = "Completed";
                document.getElementById('completionBadge').style.display = 'flex';
            } else if (percentage > 0) {
                statusElement.textContent = `${Math.round(percentage)}% Watched`;
            }
        });
        
        // Function to update progress on server
        function updateProgress(percentage) {
            fetch('update_progress.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    user_id: userId,
                    lesson_id: currentLessonId,
                    course_id: currentCourseId,
                    progress: percentage
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.course_completed) {
                    document.getElementById('viewCertificate').style.display = 'block';
                }
                refreshProgressData();
            });
        }
        
        // Function to load lesson data
        function loadLessonData(lessonId) {
            fetch(`get_lesson.php?lesson_id=${lessonId}`)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('lessonTitle').textContent = data.title;
                    document.getElementById('lessonDescription').textContent = data.description;
                    document.getElementById('videoSource').src = data.video_path;
                    player.source = {
                        type: 'video',
                        sources: [{
                            src: data.video_path,
                            type: 'video/mp4'
                        }]
                    };
                    
                    // Load progress for this lesson
                    fetch(`get_progress.php?user_id=${userId}&lesson_id=${lessonId}`)
                        .then(response => response.json())
                        .then(progress => {
                            if (progress) {
                                const percentage = progress.progress_percentage;
                                document.getElementById('progressBar').style.width = `${percentage}%`;
                                if (percentage >= 95) {
                                    document.getElementById('completionStatus').textContent = "Completed";
                                    document.getElementById('completionBadge').style.display = 'flex';
                                } else if (percentage > 0) {
                                    document.getElementById('completionStatus').textContent = `${Math.round(percentage)}% Watched`;
                                }
                            }
                        });
                });
        }
        
        // Function to refresh course progress data
        function refreshProgressData() {
            fetch(`get_course_progress.php?user_id=${userId}&course_id=${currentCourseId}`)
                .then(response => response.json())
                .then(data => {
                    // Update circular progress
                    const circle = document.getElementById('courseProgressCircle');
                    const percentageElement = document.querySelector('.percentage');
                    const percentage = Math.round(data.completion_percentage);
                    
                    circle.style.strokeDasharray = `${percentage}, 100`;
                    percentageElement.textContent = `${percentage}%`;
                    
                    // Update lesson counts
                    document.getElementById('completedLessons').textContent = data.completed_lessons;
                    document.getElementById('totalLessons').textContent = data.total_lessons;
                    
                    // Show certificate button if course completed
                    if (data.course_completed) {
                        document.getElementById('viewCertificate').style.display = 'block';
                    }
                    
                    // Update lesson list with completion status
                    const lessonList = document.getElementById('lessonList');
                    lessonList.innerHTML = '';
                    
                    data.lessons.forEach(lesson => {
                        const li = document.createElement('li');
                        li.className = `lesson-item ${lesson.id === currentLessonId ? 'active' : ''}`;
                        
                        if (lesson.completed) {
                            li.innerHTML = `
                                <i class="fas fa-check-circle text-success"></i>
                                <span>${lesson.title}</span>
                                <span class="duration">${formatDuration(lesson.duration)}</span>
                            `;
                        } else if (lesson.started) {
                            li.innerHTML = `
                                <i class="fas fa-play-circle text-primary"></i>
                                <span>${lesson.title}</span>
                                <span class="duration">${formatDuration(lesson.duration)}</span>
                            `;
                        } else {
                            li.innerHTML = `
                                <i class="far fa-circle"></i>
                                <span>${lesson.title}</span>
                                <span class="duration">${formatDuration(lesson.duration)}</span>
                            `;
                        }
                        
                        li.addEventListener('click', () => {
                            window.location.href = `lesson.php?course_id=${currentCourseId}&lesson_id=${lesson.id}`;
                        });
                        
                        lessonList.appendChild(li);
                    });
                });
        }
        
        // Helper function to format duration
        function formatDuration(seconds) {
            const mins = Math.floor(seconds / 60);
            const secs = seconds % 60;
            return `${mins}:${secs < 10 ? '0' : ''}${secs}`;
        }
        
        // Load initial data
        document.addEventListener('DOMContentLoaded', () => {
            if (currentLessonId) {
                loadLessonData(currentLessonId);
                refreshProgressData();
            }
            
            // Navigation button event listeners
            document.getElementById('prevLesson').addEventListener('click', () => {
                // Implement navigation to previous lesson
            });
            
            document.getElementById('nextLesson').addEventListener('click', () => {
                // Implement navigation to next lesson
            });
            
            // Certificate button
            document.getElementById('viewCertificate').addEventListener('click', () => {
                window.open(`certificate.php?user_id=${userId}&course_id=${currentCourseId}`, '_blank');
            });
        });
    </script>
</body>
</html>
