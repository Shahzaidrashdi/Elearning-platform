<?php
require_once 'config.php';

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and validate inputs
    $username = htmlspecialchars(trim($_POST['username']));
    $email = htmlspecialchars(trim($_POST['email']));
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $role = $_POST['role']; // instructor or student
    
    // Validation
    if (empty($username)) $errors[] = "Username is required";
    if (empty($email)) $errors[] = "Email is required";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Invalid email format";
    if (empty($password)) $errors[] = "Password is required";
    if (strlen($password) < 8) $errors[] = "Password must be at least 8 characters";
    if ($password !== $confirm_password) $errors[] = "Passwords don't match";
    if (!in_array($role, ['instructor', 'student'])) $errors[] = "Invalid role selected";
    
    if (empty($errors)) {
        try {
            // Check if username or email already exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);
            
            if ($stmt->rowCount() > 0) {
                $errors[] = "Username or email already exists";
            } else {
                // Hash password
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                
                // Insert user with selected role
                $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
                $stmt->execute([$username, $email, $hashedPassword, $role]);
                
                $_SESSION['success'] = "Registration successful! Please login.";
                header("Location: login.php");
                exit;
            }
        } catch(PDOException $e) {
            $errors[] = "Database error occurred. Please try again later.";
            error_log("Registration error: " . $e->getMessage());
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - E-Learning Platform</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .registration-container {
            max-width: 500px;
            margin: 50px auto;
            padding: 30px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .role-selection {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
        }
        .role-option {
            flex: 1;
            text-align: center;
            padding: 15px;
            border: 2px solid #dee2e6;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
        }
        .role-option:hover {
            border-color: #0d6efd;
        }
        .role-option input[type="radio"] {
            display: none;
        }
        .role-option input[type="radio"]:checked + .role-label {
            border-color: #0d6efd;
            background-color: #f0f7ff;
        }
        .role-label {
            display: block;
            width: 100%;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="registration-container">
            <h2 class="text-center mb-4">Create Your Account</h2>
            
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <?php foreach ($errors as $error): ?>
                        <p class="mb-0"><?php echo htmlspecialchars($error); ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="register.php">
                <div class="mb-3">
                    <label for="username" class="form-label">Username</label>
                    <input type="text" class="form-control" id="username" name="username" 
                           value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" required>
                </div>
                
                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="email" name="email" 
                           value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                </div>
                
                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                    <small class="text-muted">Minimum 8 characters</small>
                </div>
                
                <div class="mb-3">
                    <label for="confirm_password" class="form-label">Confirm Password</label>
                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                </div>
                
                <div class="mb-4">
                    <label class="form-label">Register As</label>
                    <div class="role-selection">
                        <div class="role-option">
                            <input type="radio" id="student" name="role" value="student" 
                                   <?php echo (isset($_POST['role']) && $_POST['role'] === 'student') ? 'checked' : 'checked'; ?>>
                            <label for="student" class="role-label">
                                <i class="fas fa-user-graduate" style="font-size: 2rem; margin-bottom: 10px;"></i><br>
                                <strong>Student</strong><br>
                                <small>Learn from courses</small>
                            </label>
                        </div>
                        <div class="role-option">
                            <input type="radio" id="instructor" name="role" value="instructor" 
                                   <?php echo (isset($_POST['role']) && $_POST['role'] === 'instructor') ? 'checked' : ''; ?>>
                            <label for="instructor" class="role-label">
                                <i class="fas fa-chalkboard-teacher" style="font-size: 2rem; margin-bottom: 10px;"></i><br>
                                <strong>Instructor</strong><br>
                                <small>Create and sell courses</small>
                            </label>
                        </div>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary w-100">Register</button>
                
                <div class="mt-3 text-center">
                    <p>Already have an account? <a href="login.php">Login here</a></p>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
</body>
</html>
