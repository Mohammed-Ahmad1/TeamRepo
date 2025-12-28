<?php
session_start();
require_once "./connect.php";

$db = new Database();
$conn = $db->conn;

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    // 🔹 Added role to the query (ONLY CHANGE)
    $stmt = $conn->prepare("SELECT user_id, password, role FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 0) {
        $error = "This email is not registered.";
    } else {
        $user = $result->fetch_assoc();

        if ($password === $user['password']) {

            // 🔹 Session setup
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['role']    = $user['role'];

            // 🔹 Role-based redirect
            if ($user['role'] === 'admin') {
                header("Location: ../operations/dashboard.php");
            } else {
                header("Location: index.php");
            }
            exit;

        } else {
            $error = "Incorrect email or password.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Anon Store</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
        body { background-color: #f4f7f6; display: flex; justify-content: center; align-items: center; min-height: 100vh; }
        
        .login-card {
            background: #fff;
            width: 100%;
            max-width: 400px;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.05);
        }

        .login-header { text-align: center; margin-bottom: 30px; }
        .login-header h2 { font-weight: 700; color: #333; margin-bottom: 10px; }
        .login-header p { color: #777; font-size: 14px; }

        .input-group { margin-bottom: 20px; }
        .input-group label { display: block; margin-bottom: 8px; font-size: 14px; color: #555; font-weight: 600; }
        .input-field { position: relative; display: flex; align-items: center; }
        .input-field i { position: absolute; left: 15px; color: #aaa; }
        
        .input-field input {
            width: 100%;
            padding: 12px 15px 12px 45px;
            border: 1px solid #ddd;
            border-radius: 8px;
            outline: none;
            transition: 0.3s;
        }

        .input-field input:focus { border-color: #ff4b2b; box-shadow: 0 0 5px rgba(255, 75, 43, 0.2); }

        .login-btn {
            width: 100%;
            padding: 12px;
            background-color: #333;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.3s;
            margin-top: 10px;
        }

        .login-btn:hover { background-color: #000; }

        .error-msg {
            background-color: #f8d7da;
            color: #721c24;
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            text-align: center;
            border: 1px solid #f5c6cb;
        }

        .signup-text { text-align: center; font-size: 14px; color: #777; margin-top: 20px; }
        .signup-text a { color: #ff4b2b; font-weight: 600; text-decoration: none; }
    </style>
</head>
<body>

<div class="login-card">
    <div class="login-header">
        <h2>Login</h2>
        <p>Welcome back to Anon Store</p>
    </div>

    <?php if (!empty($error)): ?>
        <div class="error-msg"><?php echo $error; ?></div>
    <?php endif; ?>

    <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST" class="login-form">
        <div class="input-group">
            <label>Email Address</label>
            <div class="input-field">
                <i class="fas fa-envelope"></i>
                <input type="email" name="email" placeholder="example@mail.com"
                       value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>" required>
            </div>
        </div>

        <div class="input-group">
            <label>Password</label>
            <div class="input-field">
                <i class="fas fa-lock"></i>
                <input type="password" name="password" placeholder="********" required>
            </div>
        </div>

        <button type="submit" class="login-btn">Login</button>

        <p class="signup-text">Don't have an account? <a href="SignUp.php">Sign Up</a></p>
    </form>
</div>

</body>
</html>