<?php

session_start();
include 'db.php';

$username = $_POST['username'];
$password = md5($_POST['password']); // Paalala: Sa susunod mas mainam gumamit ng password_hash() para mas secured!

$sql = "SELECT * FROM users
        WHERE username='$username'
        AND password='$password'
        AND status='active'";

$result = mysqli_query($conn, $sql);

if(mysqli_num_rows($result) == 1){

    $row = mysqli_fetch_assoc($result);

    $_SESSION['user_id'] = $row['id'];
    $_SESSION['fullname'] = $row['fullname'];
    $_SESSION['username'] = $row['username']; // Idinagdag para sa dynamic welcome message sa panel
    $_SESSION['role'] = $row['role'];

    if($row['role'] == "owner"){
        header("Location: owner_dashboard.php");
        exit();
    }
    elseif($row['role'] == "manager"){
        // Pinalitan mula manager_dashboard.php patungong manager_panel.php para tumugma sa file natin
        header("Location: manager_panel.php");
        exit();
    }
    else{
        header("Location: customer_dashboard.php");
        exit();
    }

}else{
    echo "Invalid Username or Password";
}

?>