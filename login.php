<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background-color: #f0f7fc; /* Very light blue background */
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }

        .login-card {
            background: #ffffff;
            width: 100%;
            max-width: 360px;
            padding: 40px 30px;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            text-align: center;
        }

        /* Header / Logo Styling */
        .logo-container {
            margin-bottom: 25px;
        }
        
        /* Styled the logo image to fit perfectly inside the card layout */
        .logo-img {
            width: 100px;
            height: auto;
            margin-bottom: 15px;
            display: block;
            margin-left: auto;
            margin-right: auto;
        }

        .system-title {
            color: #1e4473; /* Deep blue text */
            font-size: 19px;
            font-weight: 700;
            text-transform: uppercase;
            line-height: 1.2;
            letter-spacing: 0.5px;
        }

        .subtitle {
            color: #5c6b73;
            font-size: 12px;
            font-weight: 500;
            margin-top: 5px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Form Controls */
        .form-group {
            position: relative;
            margin-bottom: 16px;
        }

        .form-group i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #8fa0a6;
            font-size: 16px;
        }

        .form-control {
            width: 100%;
            padding: 12px 15px 12px 45px;
            border: 1.5px solid #d0dfeb;
            border-radius: 10px;
            font-size: 14px;
            color: #333;
            outline: none;
            transition: all 0.3s ease;
        }

        /* Focus blue border like the screenshot */
        .form-control:focus {
            border-color: #5294e2;
            box-shadow: 0 0 5px rgba(82, 148, 226, 0.3);
        }

        .form-control::placeholder {
            color: #a0b0b5;
        }

        /* Password Eye Icon */
.password-group .password-toggle {
    left: auto;
    right: 15px;
    cursor: pointer;
    color: #8fa0a6;
    font-size: 15px;
}

.password-group .password-toggle:hover {
    color: #1e4473;
}

.password-group .form-control {
    padding-right: 45px;
}

        /* Utilities Row (Remember me & Forgot Password) */
        .form-utilities {
    display: flex;
    justify-content: flex-end;
    align-items: center;
    font-size: 12px;
    margin-bottom: 25px;
    padding: 0 2px;
}

        .remember-me {
            display: flex;
            align-items: center;
            gap: 5px;
            cursor: pointer;
        }

        .remember-me input {
            cursor: pointer;
            accent-color: #1e4473;
        }

        .forgot-link {
            color: #4a5568;
            text-decoration: none;
        }

        .forgot-link:hover {
            text-decoration: underline;
        }

        /* Login Button */
        .btn-submit {
            width: 100%;
            background-color: #1e4473; /* Match the dark blue button */
            color: white;
            border: none;
            padding: 12px;
            border-radius: 25px; /* Fully rounded capsule button */
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            box-shadow: 0 4px 10px rgba(30, 68, 115, 0.2);
            transition: background-color 0.2s ease;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .btn-submit:hover {
            background-color: #153256;
        }

        /* Footer Links */
        .form-footer {
            margin-top: 20px;
            font-size: 12px;
            color: #4a5568;
        }

        .form-footer a {
            color: #1e4473;
            text-decoration: none;
            font-weight: 600;
        }

        .form-footer a:hover {
            text-decoration: underline;
        }

        .admin-contact {
            margin-top: 15px;
            font-size: 11px;
            color: #718096;
        }
    </style>
</head>
<body>

<div class="login-card">
    
    <div class="logo-container">
        <img src="/EggFarm/vdvc.png" alt="VDVC Logo" class="logo-img">
        <h1 class="system-title">Egg Farm<br>Management System</h1>
        
    </div>

    <form action="login_process.php" method="POST">
        
        <div class="form-group">
            <i class="fa-regular fa-user"></i>
            <input type="text" 
                   name="username" 
                   class="form-control" 
                   placeholder="Username" 
                   required>
        </div>
<div class="form-group password-group"> 
    <i class="fa-solid fa-lock"></i> 
    <input type="password"  
           name="password"  
           id="password"
           class="form-control"  
           placeholder="Password"  
           required>

    <i class="fa-solid fa-eye password-toggle"
       id="passwordToggle"
       onclick="togglePassword()"></i>
</div>

        <div class="form-utilities">
           
            <a href="#" class="forgot-link">Forgot Password?</a>
        </div>

        <button type="submit" class="btn-submit">Log In</button>

    </form>

    <div class="form-footer">
        Don't have an account? <a href="register.php">Sign Up</a>
       
    </div>

</div>
<script>
function togglePassword() {
    var password = document.getElementById("password");
    var toggle = document.getElementById("passwordToggle");

    if (password.type === "password") {
        password.type = "text";
        toggle.className = "fa-solid fa-eye-slash password-toggle";
    } else {
        password.type = "password";
        toggle.className = "fa-solid fa-eye password-toggle";
    }
}
</script>
</body>
</html>