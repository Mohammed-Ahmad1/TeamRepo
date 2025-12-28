<?php
session_start();
require_once "./connect.php";
$db = new Database();
$conn = $db->conn;

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);
    $phone = trim($_POST['phone']);
    $role = "user"; 

    if (empty($first_name) || empty($last_name) || empty($email) || empty($password) || empty($confirm_password) || empty($phone)) {
        $error = "All fields are required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email address";
    } elseif (!preg_match('/^[A-Z].{7,}$/', $password)) {
        $error = "Password must start with a capital letter and be at least 8 characters long";
    } elseif ($password !== $confirm_password) {
        $error = "Password and confirm password do not match";
    } else {
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? OR phone = ?");
        $stmt->bind_param("ss", $email, $phone);
        $stmt->execute();
        $result = $stmt->get_result(); 
        
        if ($result->num_rows > 0) {
            $error = "This phone OR Email is already registered";
        } else {
            $stmt = $conn->prepare("INSERT INTO users (first_name, last_name, email, password, role, phone) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssss", $first_name, $last_name, $email, $password, $role, $phone);

            if ($stmt->execute()) {
                $success = "Account created successfully. You can login now";
            } else {
                $error = "Something went wrong. Please try again";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register | Anon Store</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
        body { background-color: #f4f7f6; display: flex; justify-content: center; align-items: center; min-height: 100vh; padding: 20px; }
        .register-card { background: #fff; width: 100%; max-width: 500px; padding: 40px; border-radius: 15px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); }
        .register-header { text-align: center; margin-bottom: 25px; }
        .register-header h2 { font-weight: 700; color: #333; margin-bottom: 5px; }
        .register-header p { color: #777; font-size: 14px; }
        .form-row { display: flex; gap: 15px; }
        .input-group { margin-bottom: 15px; flex: 1; }
        .input-group label { display: block; margin-bottom: 5px; font-size: 13px; color: #555; font-weight: 600; }
        .input-field { position: relative; display: flex; align-items: center; }
        .input-field i { position: absolute; left: 15px; color: #aaa; font-size: 14px; }
        .input-field input { width: 100%; padding: 10px 15px 10px 40px; border: 1px solid #ddd; border-radius: 8px; outline: none; transition: 0.3s; font-size: 14px; }
        .register-btn { width: 100%; padding: 12px; background-color: #333; color: white; border: none; border-radius: 8px; font-size: 16px; font-weight: 600; cursor: pointer; transition: 0.3s; margin-top: 10px; }
        .register-btn:hover { background-color: #000; }
        .error-msg { background-color: #f8d7da; color: #721c24; padding: 10px; border-radius: 8px; margin-bottom: 20px; font-size: 13px; text-align: center; border: 1px solid #f5c6cb; }
        .success-msg { background-color: #d4edda; color: #155724; padding: 10px; border-radius: 8px; margin-bottom: 20px; font-size: 13px; text-align: center; border: 1px solid #c3e6cb; }
        .login-text { text-align: center; font-size: 14px; color: #777; margin-top: 20px; }
        .login-text a { color: #ff4b2b; font-weight: 600; text-decoration: none; }
    </style>
</head>
<body>

<div class="register-card">
    <div class="register-header">
        <h2>Create Account</h2>
        <p>Join Anon Store today</p>
    </div>

    <?php if (!empty($error)): ?>
        <div class="error-msg"><?php echo $error; ?></div>
    <?php endif; ?>

    <?php if (!empty($success)): ?>
        <div class="success-msg"><?php echo $success; ?></div>

        <!-- ONLY ADDITION: delayed redirect -->
        <script>
            setTimeout(function () {
                window.location.href = "Login.php";
            }, 3000);
        </script>
    <?php endif; ?>

    <form action="" method="POST">
        <div class="form-row">
            <div class="input-group">
                <label>First Name</label>
                <div class="input-field">
                    <i class="fas fa-user"></i>
                    <input type="text" name="first_name" placeholder="John" required>
                </div>
            </div>
            <div class="input-group">
                <label>Last Name</label>
                <div class="input-field">
                    <i class="fas fa-user"></i>
                    <input type="text" name="last_name" placeholder="Doe" required>
                </div>
            </div>
        </div>

        <div class="input-group">
            <label>Email Address</label>
            <div class="input-field">
                <i class="fas fa-envelope"></i>
                <input type="email" name="email" placeholder="john@example.com" required>
            </div>
        </div>

        <div class="input-group">
            <label>Phone Number</label>
            <div class="input-field">
                <i class="fas fa-phone"></i>
                <input type="text" name="phone" placeholder="07XXXXXXXX" maxlength="10" required>
            </div>
        </div>

        <div class="form-row">
            <div class="input-group">
                <label>Password</label>
                <div class="input-field">
                    <i class="fas fa-lock"></i>
                    <input type="password" name="password" placeholder="Min 8 chars" required>
                </div>
            </div>
            <div class="input-group">
                <label>Confirm Password</label>
                <div class="input-field">
                    <i class="fas fa-lock"></i>
                    <input type="password" name="confirm_password" placeholder="Repeat it" required>
                </div>
            </div>
        </div>

        <button type="submit" class="register-btn">Sign Up</button>

        <p class="login-text">Already have an account? <a href="Login.php">Login</a></p>
    </form>
</div>

</body>
</html>
