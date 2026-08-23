<?php

include 'db.php';

header('Content-Type: application/json');

$fullname = trim($_POST['fullname']);
$username = trim($_POST['username']);
$email = trim($_POST['email']);
$password = md5($_POST['password']);

/* =========================================
   CHECK IF USERNAME ALREADY EXISTS
   ========================================= */

$check_username = mysqli_prepare($conn, "SELECT id FROM users WHERE username = ?");
mysqli_stmt_bind_param($check_username, "s", $username);
mysqli_stmt_execute($check_username);
mysqli_stmt_store_result($check_username);

if(mysqli_stmt_num_rows($check_username) > 0){

    echo json_encode(array(
        "status" => "error",
        "message" => "Username is already taken."
    ));

    mysqli_stmt_close($check_username);
    exit();
}

mysqli_stmt_close($check_username);


/* =========================================
   CHECK IF EMAIL ALREADY EXISTS
   ========================================= */

$check_email = mysqli_prepare($conn, "SELECT id FROM users WHERE email = ?");
mysqli_stmt_bind_param($check_email, "s", $email);
mysqli_stmt_execute($check_email);
mysqli_stmt_store_result($check_email);

if(mysqli_stmt_num_rows($check_email) > 0){

    echo json_encode(array(
        "status" => "error",
        "message" => "Email address is already registered."
    ));

    mysqli_stmt_close($check_email);
    exit();
}

mysqli_stmt_close($check_email);


/* =========================================
   INSERT NEW CUSTOMER
   ========================================= */

$sql = "INSERT INTO users
        (fullname, username, email, password, role)
        VALUES (?, ?, ?, ?, 'customer')";

$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param(
    $stmt,
    "ssss",
    $fullname,
    $username,
    $email,
    $password
);

if(mysqli_stmt_execute($stmt)){

    echo json_encode(array(
        "status" => "success",
        "message" => "Registration Successful!"
    ));

}else{

    echo json_encode(array(
        "status" => "error",
        "message" => "Registration failed. Please try again."
    ));
}

mysqli_stmt_close($stmt);

?>