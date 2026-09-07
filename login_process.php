<?php

session_start();

include 'db.php';


/* =========================================
   CHECK IF FORM WAS SUBMITTED
========================================= */

if ($_SERVER['REQUEST_METHOD'] == 'POST') {


    /* =========================================
       GET USER INPUT
    ========================================= */

    $username = mysqli_real_escape_string(
        $conn,
        $_POST['username']
    );


    $password = md5(
        $_POST['password']
    );


    /* =========================================
       CHECK USER ACCOUNT
    ========================================= */

    $sql = "

        SELECT * FROM users

        WHERE username = '$username'

        AND password = '$password'

        AND status = 'active'

    ";


    $result = mysqli_query(
        $conn,
        $sql
    );


    /* =========================================
       SUCCESSFUL LOGIN
    ========================================= */

    if (mysqli_num_rows($result) == 1) {


        $row = mysqli_fetch_assoc(
            $result
        );


        /* =========================================
           CREATE SESSION
        ========================================= */

        $_SESSION['user_id'] =
            $row['id'];


        $_SESSION['fullname'] =
            $row['fullname'];


        $_SESSION['username'] =
            $row['username'];


        $_SESSION['role'] =
            $row['role'];


        /* =========================================
           ROLE-BASED REDIRECTION
        ========================================= */


        /* OWNER / ADMIN */

        if ($row['role'] == "owner") {

            header(
                "Location: owner_dashboard.php"
            );

            exit();

        }


        /* MANAGER */

        elseif ($row['role'] == "manager") {

            header(
                "Location: manager_dashboard.php"
            );

            exit();

        }


        /* CUSTOMER */

        else {

            header(
                "Location: customer_dashboard.php"
            );

            exit();

        }


    }


    /* =========================================
       INVALID LOGIN
    ========================================= */

    else {


        echo "

        <script>

            alert('Invalid username or password.');

            window.location.href = 'login.php';

        </script>

        ";

        exit();

    }


}


else {


    header(
        "Location: login.php"
    );

    exit();

}

?>