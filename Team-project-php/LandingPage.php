<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to Anon</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --onyx: hsl(0, 0%, 25%);
            --salmon-pink: hsl(353, 100%, 78%);
            --white: hsl(0, 0%, 100%);
            --cultured: hsl(0, 0%, 93%);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            /* خلفية فاتحة مثل خلفية الموقع */
            background-color: var(--cultured); 
            text-align: center;
        }

        .container {
            background-color: var(--white);
            padding: 60px 40px;
            border-radius: 20px;
            /* ظل ناعم مثل الـ showcase في الموقع */
            box-shadow: 0 10px 30px hsla(0, 0%, 0%, 0.1);
            max-width: 500px;
            width: 90%;
        }

        .logo {
            margin-bottom: 20px;
            width: 150px;
        }

        h1 {
            color: var(--onyx);
            margin-bottom: 10px;
            font-size: 2rem;
            font-weight: 700;
        }

        p {
            color: hsl(0, 0%, 45%);
            margin-bottom: 30px;
            font-size: 0.9rem;
        }

        .btn-group {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .btn {
            display: block;
            padding: 15px 30px;
            font-size: 1rem;
            font-weight: 600;
            text-decoration: none;
            border-radius: 10px;
            transition: 0.3s ease;
            text-transform: uppercase;
        }

        /* زر الدخول بلون Salmon Pink */
        .btn-login {
            background-color: var(--salmon-pink);
            color: var(--white);
        }

        .btn-login:hover {
            background-color: var(--onyx);
        }

        /* زر التسجيل بلون أسود/رمادي غامق */
        .btn-signup {
            background-color: var(--onyx);
            color: var(--white);
        }

        .btn-signup:hover {
            background-color: var(--salmon-pink);
        }

    </style>
</head>
<body>


    <div class="container">
        <img src="../Team-project-php\assets\images\logo\logo.svg" alt="Anon Logo" class="logo">
        
        <h1>Welcome to Anon</h1>
        <p>Your one-stop destination for the latest fashion trends.</p>
        
        <div class="btn-group">
            <a href="Login.php" class="btn btn-login">Login</a>
            <a href="SignUp.php" class="btn btn-signup">Create Account</a>
        </div>
    </div>

</body>
</html>