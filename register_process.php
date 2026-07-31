<?php

include 'db.php';

$fullname = $_POST['fullname'];
$username = $_POST['username'];
$email = $_POST['email'];
$password = md5($_POST['password']);

$sql = "INSERT INTO users
(fullname,username,email,password,role)

VALUES

('$fullname',
 '$username',
 '$email',
 '$password',
 'customer')";

if(mysqli_query($conn,$sql)){

    echo "Registration Successful";

}else{

    echo "Error";

}

?>