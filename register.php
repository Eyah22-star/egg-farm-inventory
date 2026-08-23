<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
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
            background-color: #f0f7fc; /* Light blue background */
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px 0;
        }

        .register-card {
            background: #ffffff;
            width: 100%;
            max-width: 360px;
            padding: 40px 30px;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            text-align: center;
            position: relative;
        }

        /* Header / Logo Styling */
        .logo-container {
            margin-bottom: 25px;
        }
        
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

        .form-group i.input-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #8fa0a6;
            font-size: 16px;
        }

        /* Show/Hide Password Eye Icon Styling */
        .toggle-password {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #8fa0a6;
            cursor: pointer;
            font-size: 16px;
            transition: color 0.2s ease;
        }

        .toggle-password:hover {
            color: #1e4473;
        }

        .form-control {
            width: 100%;
            padding: 12px 40px 12px 45px; /* Added right padding for the eye icon */
            border: 1.5px solid #d0dfeb;
            border-radius: 10px;
            font-size: 14px;
            color: #333;
            outline: none;
            transition: all 0.3s ease;
        }

        /* Focus border dynamic response */
        .form-control:focus {
            border-color: #5294e2;
            box-shadow: 0 0 5px rgba(82, 148, 226, 0.3);
        }

        .form-control::placeholder {
            color: #a0b0b5;
        }

        /* Register Button */
        .btn-submit {
            width: 100%;
            background-color: #1e4473; /* Dark blue button */
            color: white;
            border: none;
            padding: 12px;
            border-radius: 25px; /* Capsule layout button */
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            box-shadow: 0 4px 10px rgba(30, 68, 115, 0.2);
            transition: background-color 0.2s ease;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-top: 10px;
        }

        .btn-submit:hover {
            background-color: #153256;
        }

        /* Toast/Notification Popup Style */
        .notification {
            visibility: hidden;
            min-width: 280px;
            background-color: #2ec4b6; /* Clean teal/green success color */
            color: #fff;
            text-align: center;
            border-radius: 8px;
            padding: 12px;
            position: fixed;
            z-index: 1000;
            left: 50%;
            top: 30px;
            transform: translateX(-50%);
            font-size: 14px;
            font-weight: 500;
            box-shadow: 0 4px 15px rgba(0,0,0,0.15);
            opacity: 0;
            transition: opacity 0.5s, top 0.5s, visibility 0.5s;
        }

        .notification.show {
            visibility: visible;
            opacity: 1;
            top: 50px;
        }

        /* Footer Links */
        .form-footer {
            margin-top: 25px;
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
    </style>
</head>
<body>

<div id="toastNotification" class="notification">Register Successfully!</div>

<div class="register-card">
    
    <div class="logo-container">
        <img src="/EggFarm/vdvc.png" alt="VDVC Logo" class="logo-img">
        <h1 class="system-title">Customer Registration</h1>
        <div class="subtitle">Poultry Farm Management</div>
    </div>

    <form id="registrationForm" action="register_process.php" method="POST">
        
        <div class="form-group">
            <i class="fa-regular fa-address-card input-icon"></i>
            <input type="text" 
                   name="fullname" 
                   class="form-control" 
                   placeholder="Full Name" 
                   required>
        </div>

        <div class="form-group">
            <i class="fa-regular fa-user input-icon"></i>
            <input type="text" 
                   name="username" 
                   class="form-control" 
                   placeholder="Username" 
                   required>
        </div>

        <div class="form-group">
            <i class="fa-regular fa-envelope input-icon"></i>
            <input type="email" 
                   name="email" 
                   class="form-control" 
                   placeholder="Email Address" 
                   required>
        </div>

       <div class="form-group password-group"> 
    <i class="fa-solid fa-lock input-icon"></i> 
    <input type="password"  
           name="password"  
           id="passwordField" 
           class="form-control"  
           placeholder="Password"  
           required> 

    <i class="fa-solid fa-eye toggle-password" 
       id="togglePasswordIcon"></i>
</div>

        <button type="submit" class="btn-submit">Register</button>

    </form>

    <div class="form-footer">
        Already have an account? <a href="login.php">Log In</a>
    </div>

</div>

<script>
    // 1. SHOW/HIDE PASSWORD FUNCTIONALITY
    const passwordField = document.getElementById('passwordField');
    const togglePasswordIcon = document.getElementById('togglePasswordIcon');

    togglePasswordIcon.addEventListener('click', function () {
        // I-toggle ang type attribute
        const type = passwordField.getAttribute('type') === 'password' ? 'text' : 'password';
        passwordField.setAttribute('type', type);
        
        // I-toggle ang icon class (mula eye papuntang eye-slash)
        this.classList.toggle('fa-eye');
        this.classList.toggle('fa-eye-slash');
    });

   // 2. AJAX SUBMISSION
const registrationForm = document.getElementById('registrationForm');
const toastNotification = document.getElementById('toastNotification');

registrationForm.addEventListener('submit', function (e) {

    e.preventDefault();

    const formData = new FormData(this);

    fetch('register_process.php', {
        method: 'POST',
        body: formData
    })
    .then(function(response) {
        return response.json();
    })
    .then(function(data) {

        // Show the message from register_process.php
        toastNotification.textContent = data.message;

        // SUCCESS
        if(data.status === 'success') {

            toastNotification.style.backgroundColor = '#2ec4b6';

            toastNotification.classList.add('show');

            // Clear all inputs ONLY after successful registration
            registrationForm.reset();

            // Reset password field to hidden
            passwordField.setAttribute('type', 'password');

            // Reset eye icon
            togglePasswordIcon.classList.add('fa-eye');
            togglePasswordIcon.classList.remove('fa-eye-slash');

        }

        // ERROR
        else {

            toastNotification.style.backgroundColor = '#e74c3c';

            toastNotification.classList.add('show');

            // DO NOT clear the form
            // User can correct the username/email
        }

        // Hide notification after 3 seconds
        setTimeout(function() {
            toastNotification.classList.remove('show');
        }, 3000);

    })
    .catch(function(error) {

        console.error('Error:', error);

        toastNotification.textContent =
            'Something went wrong. Please try again.';

        toastNotification.style.backgroundColor = '#e74c3c';

        toastNotification.classList.add('show');

        setTimeout(function() {
            toastNotification.classList.remove('show');
        }, 3000);
    });

});
</script>

</body>
</html>