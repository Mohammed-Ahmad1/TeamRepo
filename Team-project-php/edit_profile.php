<?php
session_start();
require_once "./connect.php";
$db = new Database();
$conn = $db->conn;


if(!isset($_SESSION['user_id'])) {
    header("Location: ./Login.php");
    exit();
}

$user_id = $_SESSION['user_id'];


$sql = "SELECT first_name, last_name, email, phone, password FROM users WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user_data = $result->fetch_assoc(); 


if(isset($_POST['update-btn'])) {
    $first_name = $_POST['first_name'];
    $last_name  = $_POST['last_name'];
    $email      = $_POST['email'];
    $phone      = $_POST['phone'];
    $password   = $_POST['password'];

    $sql = "UPDATE users SET first_name=?, last_name=?, email=?, phone=?, password=? WHERE user_id=?";
    $stmt = $conn->prepare($sql);
    
    $stmt->bind_param("sssssi", $first_name, $last_name, $email, $phone, $password, $user_id);
    
    if ($stmt->execute()) {
        header("Location: edit_profile.php?status=success");
        exit();
    } else {
        echo "Error: " . $conn->error;
    }
}




?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile - Anon</title>
    <link rel="stylesheet" href="./assets/css/style-prefix.css"> 
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --salmon-pink: hsl(353, 100%, 78%);
            --onyx: hsl(0, 0%, 25%);
            --cultured: hsl(0, 0%, 93%);
            --white: hsl(0, 0%, 100%);
        }

        body {
            background-color: var(--cultured);
            font-family: 'Poppins', sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
        }

        .edit-profile-container {
            max-width: 500px;
            width: 90%;
            background: var(--white);
            padding: 40px 30px;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.05);
        }

        .profile-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .profile-header h2 {
            color: var(--onyx);
            font-weight: 700;
            font-size: 1.5rem;
            margin-bottom: 10px;
        }

        /* جملة المستخدم المستخرجة من قاعدة البيانات */
        .user-phrase-box {
            background: #fff5f6;
            border-left: 4px solid var(--salmon-pink);
            padding: 15px;
            margin-bottom: 30px;
            font-style: italic;
            color: var(--onyx);
            font-size: 0.9rem;
            border-radius: 0 8px 8px 0;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            color: var(--onyx);
            font-weight: 600;
            margin-bottom: 8px;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 1.5px solid #eee;
            border-radius: 10px;
            outline: none;
            transition: 0.3s ease;
            font-size: 0.95rem;
            color: var(--onyx);
        }

        .form-control:focus {
            border-color: var(--salmon-pink);
            box-shadow: 0 0 0 3px hsla(353, 100%, 78%, 0.1);
        }

        .update-btn,.delete-btn {
            background-color: var(--salmon-pink);
            color: white;
            border: none;
            width: 100%;
            padding: 14px;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-top: 10px;
        }

        .update-btn:hover,.delete-btn:hover {
            background-color: var(--onyx);
            transform: translateY(-2px);
        }

        .back-link {
            display: block;
            text-align: center;
            margin-top: 25px;
            color: #888;
            text-decoration: none;
            font-size: 0.85rem;
            transition: 0.3s;
        }

        .back-link:hover {
            color: var(--salmon-pink);
        }
    </style>
</head>
<body>

    <div class="edit-profile-container">
        <div class="profile-header">
            <h2>Settings</h2>
            <p style="font-size: 0.8rem; color: #777;">Update your account details</p>
        </div>

        <div class="user-phrase-box">
            "Your custom status message from the database appears here."
        </div>

<?php if (isset($_GET['status']) && $_GET['status'] == 'success'): ?>
    <div style="background-color: #d4edda; color: #155724; padding: 10px; border-radius: 10px; text-align: center; margin-bottom: 20px; font-size: 0.9rem;">
        ✅ Profile updated successfully!
    </div>
<?php endif; ?>


<form method="post">
    <div class="form-group">
        <label>First Name</label>
        <input type="text" name="first_name" class="form-control" value="<?php echo htmlspecialchars($user_data['first_name']); ?>">
    </div>

    <div class="form-group">
        <label>Last Name</label>
        <input type="text" name="last_name" class="form-control" value="<?php echo htmlspecialchars($user_data['last_name']); ?>">
    </div>

    <div class="form-group">
        <label>Email Address</label>
        <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user_data['email']); ?>">
    </div>

    <div class="form-group">
        <label>Phone Number</label>
        <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($user_data['phone']); ?>">
    </div>

    <div class="form-group">
        <label>Password</label>
        <input type="text" name="password" class="form-control" value="<?php echo htmlspecialchars($user_data['password']); ?>">
    </div>

    <button type="submit" name="update-btn" class="update-btn">Save Changes</button>

</form>
        <a href="index.php" class="back-link">← Return to Profile</a>
    </div>

</body>
</html>