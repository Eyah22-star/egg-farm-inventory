
<?php

include('manager_panel.php');

include('db.php');


// =========================================================
// GET CURRENT LOGGED-IN MANAGER
// =========================================================

$user_id = $_SESSION['user_id'];


// =========================================================
// MESSAGE VARIABLES
// =========================================================

$success_message = "";

$error_message = "";


// =========================================================
// HANDLE PROFILE UPDATE
// =========================================================

if (isset($_POST['update_profile'])) {


    $fullname = trim($_POST['fullname']);

    $username = trim($_POST['username']);


    // CHECK REQUIRED FIELDS

    if ($fullname == "" || $username == "") {

        $error_message =
            "Please fill in all required fields.";

    } else {


        // =================================================
        // CHECK IF USERNAME ALREADY EXISTS
        // =================================================

        $check_sql = "

            SELECT id

            FROM users

            WHERE username = ?

            AND id != ?

        ";


        $check_stmt = mysqli_prepare(
            $conn,
            $check_sql
        );


        mysqli_stmt_bind_param(
            $check_stmt,
            "si",
            $username,
            $user_id
        );


        mysqli_stmt_execute(
            $check_stmt
        );


        mysqli_stmt_store_result(
            $check_stmt
        );


        if (
            mysqli_stmt_num_rows(
                $check_stmt
            ) > 0
        ) {

            $error_message =
                "This username is already being used.";

        } else {


            // =============================================
            // UPDATE PROFILE
            // =============================================

            $update_sql = "

                UPDATE users

                SET

                    fullname = ?,

                    username = ?

                WHERE id = ?

            ";


            $update_stmt = mysqli_prepare(
                $conn,
                $update_sql
            );


            mysqli_stmt_bind_param(
                $update_stmt,
                "ssi",
                $fullname,
                $username,
                $user_id
            );


            if (
                mysqli_stmt_execute(
                    $update_stmt
                )
            ) {


                // UPDATE SESSION DATA

                $_SESSION['fullname'] =
                    $fullname;


                $_SESSION['username'] =
                    $username;


                $success_message =
                    "Profile information updated successfully.";

            } else {

                $error_message =
                    "Unable to update your profile.";

            }


            mysqli_stmt_close(
                $update_stmt
            );

        }


        mysqli_stmt_close(
            $check_stmt
        );

    }

}


// =========================================================
// HANDLE PASSWORD CHANGE
// =========================================================

if (isset($_POST['change_password'])) {


    $current_password =
        $_POST['current_password'];


    $new_password =
        $_POST['new_password'];


    $confirm_password =
        $_POST['confirm_password'];


    // CHECK EMPTY FIELDS

    if (

        $current_password == ""

        ||

        $new_password == ""

        ||

        $confirm_password == ""

    ) {

        $error_message =
            "Please complete all password fields.";

    }


    // CHECK PASSWORD LENGTH

    elseif (
        strlen($new_password) < 6
    ) {

        $error_message =
            "New password must contain at least 6 characters.";

    }


    // CHECK PASSWORD MATCH

    elseif (
        $new_password != $confirm_password
    ) {

        $error_message =
            "New passwords do not match.";

    }


    else {


        // =================================================
        // GET CURRENT PASSWORD FROM DATABASE
        // =================================================

        $password_sql = "

            SELECT password

            FROM users

            WHERE id = ?

        ";


        $password_stmt = mysqli_prepare(
            $conn,
            $password_sql
        );


        mysqli_stmt_bind_param(
            $password_stmt,
            "i",
            $user_id
        );


        mysqli_stmt_execute(
            $password_stmt
        );


        mysqli_stmt_bind_result(
            $password_stmt,
            $database_password
        );


        mysqli_stmt_fetch(
            $password_stmt
        );


        mysqli_stmt_close(
            $password_stmt
        );


        // =================================================
        // CHECK CURRENT PASSWORD
        // =================================================

        $current_password_md5 =
            md5(
                $current_password
            );


        if (
            $current_password_md5
            !=
            $database_password
        ) {

            $error_message =
                "Your current password is incorrect.";

        }


        else {


            // =============================================
            // HASH NEW PASSWORD
            // =============================================

            $new_password_md5 =
                md5(
                    $new_password
                );


            // =============================================
            // UPDATE PASSWORD
            // =============================================

            $password_update_sql = "

                UPDATE users

                SET password = ?

                WHERE id = ?

            ";


            $password_update_stmt =
                mysqli_prepare(
                    $conn,
                    $password_update_sql
                );


            mysqli_stmt_bind_param(
                $password_update_stmt,
                "si",
                $new_password_md5,
                $user_id
            );


            if (

                mysqli_stmt_execute(
                    $password_update_stmt
                )

            ) {

                $success_message =
                    "Password changed successfully.";

            }


            else {

                $error_message =
                    "Unable to change password.";

            }


            mysqli_stmt_close(
                $password_update_stmt
            );

        }

    }

}


// =========================================================
// GET CURRENT MANAGER INFORMATION
// =========================================================

$user_sql = "

    SELECT *

    FROM users

    WHERE id = ?

    LIMIT 1

";


$user_stmt = mysqli_prepare(
    $conn,
    $user_sql
);


mysqli_stmt_bind_param(
    $user_stmt,
    "i",
    $user_id
);


mysqli_stmt_execute(
    $user_stmt
);


$user_result =
    mysqli_stmt_get_result(
        $user_stmt
    );


$user =
    mysqli_fetch_assoc(
        $user_result
    );


mysqli_stmt_close(
    $user_stmt
);

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Profile Management
    </title>


    <script src="https://cdn.tailwindcss.com"></script>


    <style>


        /* =============================================
           BODY
        ============================================= */

        body {

            margin:
                0;

            background:
                #f1f5f9;

        }


        /* =============================================
           MAIN CONTENT
        ============================================= */
.main-content {

    margin-left:
        260px;

    min-height:
        100vh;

    padding:
        18px;

    background:

        linear-gradient(

            135deg,

            #eeece6 0%,

            #f5f3ed 100%

        );

}

        /* =============================================
           OUTER CARD
        ============================================= */

        .profile-outer-card {

            width:
                100%;

            box-sizing:
                border-box;

            padding:
                22px;

            background:
                #ffffff;

            border:
                1px solid #dbe3ea;

            border-radius:
                16px;

            box-shadow:
                0 4px 16px rgba(
                    15,
                    23,
                    42,
                    0.05
                );

        }


        /* =============================================
           OUTER CARD HEADER
        ============================================= */

        .outer-card-header {

            display:
                flex;

            align-items:
                center;

            gap:
                14px;

            padding:
                2px 2px 20px;

        }


        .profile-header-icon {

            width:
                46px;

            height:
                46px;

            flex-shrink:
                0;

            display:
                flex;

            align-items:
                center;

            justify-content:
                center;

            border-radius:
                50%;

            background:
                #e6f4ea;

            color:
                #2f6b45;

            font-size:
                20px;

        }


        .outer-card-header h1 {

            margin:
                0;

            color:
                #334155;

            font-size:
                23px;

            font-weight:
                700;

        }


        .outer-card-header p {

            margin:
                4px 0 0;

            color:
                #64748b;

            font-size:
                12px;

        }


        /* =============================================
           LINE BEFORE PROFILE CARDS
        ============================================= */

        .outer-card-divider {

            width:
                100%;

            height:
                1px;

            margin:
                0 0 20px;

            background:
                #e2e8f0;

        }


        /* =============================================
           PROFILE LAYOUT
        ============================================= */

        .profile-grid {

            display:
                grid;

            grid-template-columns:
                300px
                minmax(0, 1fr);

            gap:
                18px;

            align-items:
                stretch;

        }


        /* =============================================
           CARD
        ============================================= */

        .profile-card {

            box-sizing:
                border-box;

            background:
                #ffffff;

            border:
                1px solid #dbe3ea;

            border-radius:
                12px;

            box-shadow:
                0 2px 8px rgba(
                    15,
                    23,
                    42,
                    0.04
                );

        }


        /* =============================================
           LEFT PROFILE CARD
        ============================================= */

        .profile-summary {

            min-height:
                100%;

            padding:
                28px 22px;

            text-align:
                center;

        }


        .profile-avatar {

            width:
                105px;

            height:
                105px;

            margin:
                0 auto 16px;

            display:
                flex;

            align-items:
                center;

            justify-content:
                center;

            border-radius:
                50%;

            background:
                linear-gradient(
                    135deg,
                    #d9eee2,
                    #bdd9c8
                );

            color:
                #245c3a;

            font-size:
                42px;

            border:
                7px solid #f4f8f5;

        }


        .profile-summary h2 {

            margin:
                0;

            color:
                #334155;

            font-size:
                18px;

            font-weight:
                700;

        }


        .role-badge {

            display:
                inline-block;

            margin-top:
                9px;

            padding:
                6px 14px;

            border-radius:
                20px;

            background:
                #e6f4ea;

            color:
                #2f6b45;

            font-size:
                12px;

            font-weight:
                600;

        }


        .profile-divider {

            border:
                none;

            border-top:
                1px solid #e2e8f0;

            margin:
                25px 0;

        }


        /* =============================================
           PROFILE DETAILS
        ============================================= */

        .profile-contact {

            text-align:
                left;

        }


        .profile-contact-item {

            display:
                flex;

            align-items:
                center;

            gap:
                10px;

            margin-bottom:
                15px;

            color:
                #64748b;

            font-size:
                12px;

        }


        .profile-contact-item i {

            width:
                18px;

            color:
                #527d59;

        }


        /* =============================================
           ACCOUNT STATUS
        ============================================= */

        .account-status {

            margin-top:
                25px;

            padding:
                17px;

            text-align:
                left;

            border-radius:
                9px;

            background:
                linear-gradient(
                    135deg,
                    #f5faf7,
                    #e8f1eb
                );

        }


        .account-status-title {

            margin-bottom:
                10px;

            color:
                #64748b;

            font-size:
                12px;

        }


        .status-active {

            display:
                flex;

            align-items:
                center;

            gap:
                8px;

            color:
                #2f8f57;

            font-size:
                13px;

            font-weight:
                600;

        }


        .status-dot {

            width:
                8px;

            height:
                8px;

            border-radius:
                50%;

            background:
                #2f8f57;

        }


        /* =============================================
           RIGHT PROFILE CARD
        ============================================= */

        .profile-content {

            min-height:
                100%;

            overflow:
                hidden;

        }


        /* =============================================
           TABS
        ============================================= */

        .profile-tabs {

            display:
                flex;

            gap:
                28px;

            padding:
                18px 25px 0;

            border-bottom:
                1px solid #dbe3ea;

        }


        .profile-tab {

            position:
                relative;

            padding:
                0 4px 15px;

            border:
                none;

            background:
                transparent;

            color:
                #64748b;

            font-size:
                13px;

            font-weight:
                600;

            cursor:
                pointer;

        }


        .profile-tab.active {

            color:
                #2f6b45;

        }


        .profile-tab.active:after {

            content:
                "";

            position:
                absolute;

            bottom:
                -1px;

            left:
                0;

            width:
                100%;

            height:
                3px;

            background:
                #3e7556;

        }


        /* =============================================
           TAB CONTENT
        ============================================= */

        .tab-content {

            display:
                none;

            padding:
                25px;

        }


        .tab-content.active {

            display:
                block;

        }


        /* =============================================
           SECTION
        ============================================= */

        .section-title {

            margin:
                0 0 5px;

            color:
                #334155;

            font-size:
                16px;

        }


        .section-description {

            margin:
                0 0 25px;

            color:
                #64748b;

            font-size:
                12px;

        }


        /* =============================================
           FORM
        ============================================= */

        .form-grid {

            display:
                grid;

            grid-template-columns:
                1fr
                1fr;

            gap:
                20px;

        }


        .form-group {

            display:
                flex;

            flex-direction:
                column;

            min-width:
                0;

        }


        .form-group.full-width {

            grid-column:
                1 / -1;

        }


        .form-group label {

            margin-bottom:
                8px;

            color:
                #475569;

            font-size:
                12px;

            font-weight:
                600;

        }


        .required {

            color:
                #dc2626;

        }


        .form-control {

            width:
                100%;

            box-sizing:
                border-box;

            padding:
                11px 13px;

            border:
                1px solid #cbd5e1;

            border-radius:
                7px;

            font-size:
                13px;

            outline:
                none;

        }


        .form-control:focus {

            border-color:
                #527d59;

        }


        .readonly-control {

            background:
                #f1f5f9;

            color:
                #64748b;

        }


        /* =============================================
           BUTTON
        ============================================= */

        .form-action {

            display:
                flex;

            justify-content:
                flex-end;

            margin-top:
                26px;

        }


        .save-button {

            padding:
                12px 20px;

            border:
                none;

            border-radius:
                7px;

            background:
                #3e7556;

            color:
                #ffffff;

            font-size:
                13px;

            font-weight:
                600;

            cursor:
                pointer;

            transition:
                0.2s ease;

        }


        .save-button:hover {

            background:
                #2f6b45;

        }


        /* =============================================
           INFO BOX
        ============================================= */

        .security-box {

            display:
                flex;

            align-items:
                center;

            gap:
                12px;

            margin-top:
                25px;

            padding:
                17px;

            border-radius:
                8px;

            background:
                #eef7f1;

            color:
                #527d59;

            font-size:
                11px;

        }


        /* =============================================
           ALERT
        ============================================= */

        .alert {

            margin-bottom:
                20px;

            padding:
                13px 16px;

            border-radius:
                8px;

            font-size:
                13px;

        }


        .alert-success {

            background:
                #ecfdf5;

            color:
                #166534;

        }


        .alert-error {

            background:
                #fef2f2;

            color:
                #b91c1c;

        }


        /* =============================================
           RESPONSIVE
        ============================================= */

        @media screen and (max-width: 1000px) {

            .profile-grid {

                grid-template-columns:
                    1fr;

            }


            .profile-summary {

                min-height:
                    auto;

            }

        }


        @media screen and (max-width: 768px) {

            .main-content {

                margin-left:
                    0;

                padding:
                    18px;

            }


            .profile-outer-card {

                padding:
                    14px;

            }


            .outer-card-header {

                align-items:
                    flex-start;

            }


            .form-grid {

                grid-template-columns:
                    1fr;

            }


            .form-group.full-width {

                grid-column:
                    auto;

            }


            .profile-tabs {

                gap:
                    15px;

                padding-left:
                    18px;

                padding-right:
                    18px;

            }


            .tab-content {

                padding:
                    20px;

            }

        }

    </style>

</head>


<body class="bg-slate-100 font-sans text-gray-700 antialiased min-h-screen">


<div class="main-content">


    <!-- =============================================
         OUTER CARD
    ============================================= -->

    <div class="profile-outer-card">


        <!-- =============================================
             OUTER CARD HEADER
        ============================================= -->

        <div class="outer-card-header">


            <div class="profile-header-icon">

                <i class="fa-solid fa-user"></i>

            </div>


            <div>

                <h1>
                    Profile Management
                </h1>

                <p>
                    Manage your account information and security settings.
                </p>

            </div>


        </div>


        <!-- =============================================
             LINE BEFORE PROFILE CARDS
        ============================================= -->

        <div class="outer-card-divider"></div>


        <!-- =============================================
             PROFILE GRID
        ============================================= -->

        <div class="profile-grid">


            <!-- =============================================
                 LEFT PROFILE SECTION
            ============================================= -->

            <div class="profile-card profile-summary">


                <div class="profile-avatar">

                    <i class="fa-solid fa-user"></i>

                </div>


                <h2>

                    <?php
                    echo htmlspecialchars(
                        $user['fullname']
                    );
                    ?>

                </h2>


                <div class="role-badge">

                    Manager

                </div>


                <hr class="profile-divider">


                <div class="profile-contact">


                    <div class="profile-contact-item">

                        <i class="fa-solid fa-user"></i>

                        <span>

                            <?php
                            echo htmlspecialchars(
                                $user['username']
                            );
                            ?>

                        </span>

                    </div>


                </div>


                <div class="account-status">


                    <div class="account-status-title">

                        Account Status

                    </div>


                    <div class="status-active">


                        <span class="status-dot"></span>


                        <?php

                        if (isset($user['status'])) {

                            echo ucfirst(
                                htmlspecialchars(
                                    $user['status']
                                )
                            );

                        } else {

                            echo "Active";

                        }

                        ?>


                    </div>


                </div>


            </div>


            <!-- =============================================
                 RIGHT PROFILE SECTION
            ============================================= -->

            <div class="profile-card profile-content">


                <!-- TABS -->

                <div class="profile-tabs">


                    <button
                        type="button"
                        class="profile-tab active"
                        data-tab="personal"
                    >

                        <i class="fa-solid fa-user"></i>

                        Personal Information

                    </button>


                    <button
                        type="button"
                        class="profile-tab"
                        data-tab="security"
                    >

                        <i class="fa-solid fa-shield-halved"></i>

                        Security Settings

                    </button>


                </div>


                <!-- =========================================
                     PERSONAL TAB
                ========================================== -->

                <div
                    class="tab-content active"
                    id="personal"
                >


                    <?php if ($success_message != "") { ?>

                        <div class="alert alert-success">

                            <?php
                            echo htmlspecialchars(
                                $success_message
                            );
                            ?>

                        </div>

                    <?php } ?>


                    <?php if ($error_message != "") { ?>

                        <div class="alert alert-error">

                            <?php
                            echo htmlspecialchars(
                                $error_message
                            );
                            ?>

                        </div>

                    <?php } ?>


                    <h2 class="section-title">

                        Personal Information

                    </h2>


                    <p class="section-description">

                        Update your account information.

                    </p>


                    <form
                        method="POST"
                        action=""
                    >


                        <div class="form-grid">


                            <!-- FULL NAME -->

                            <div class="form-group">


                                <label>

                                    Full Name

                                    <span class="required">
                                        *
                                    </span>

                                </label>


                                <input
                                    type="text"
                                    name="fullname"
                                    class="form-control"
                                    required
                                    value="<?php echo htmlspecialchars($user['fullname']); ?>"
                                >


                            </div>


                            <!-- USERNAME -->

                            <div class="form-group">


                                <label>

                                    Username

                                    <span class="required">
                                        *
                                    </span>

                                </label>


                                <input
                                    type="text"
                                    name="username"
                                    class="form-control"
                                    required
                                    value="<?php echo htmlspecialchars($user['username']); ?>"
                                >


                            </div>


                            <!-- ROLE -->

                            <div class="form-group full-width">


                                <label>
                                    Role
                                </label>


                                <input
                                    type="text"
                                    class="form-control readonly-control"
                                    readonly
                                    value="Manager"
                                >


                            </div>


                        </div>


                        <div class="form-action">


                            <button
                                type="submit"
                                name="update_profile"
                                class="save-button"
                            >

                                <i class="fa-solid fa-floppy-disk"></i>

                                Save Changes

                            </button>


                        </div>


                    </form>


                    <div class="security-box">

                        <i class="fa-solid fa-circle-info"></i>

                        Your profile information is secure and only visible to authorized personnel.

                    </div>


                </div>


                <!-- =========================================
                     SECURITY TAB
                ========================================== -->

                <div
                    class="tab-content"
                    id="security"
                >


                    <?php if ($success_message != "") { ?>

                        <div class="alert alert-success">

                            <?php
                            echo htmlspecialchars(
                                $success_message
                            );
                            ?>

                        </div>

                    <?php } ?>


                    <?php if ($error_message != "") { ?>

                        <div class="alert alert-error">

                            <?php
                            echo htmlspecialchars(
                                $error_message
                            );
                            ?>

                        </div>

                    <?php } ?>


                    <h2 class="section-title">

                        Security Settings

                    </h2>


                    <p class="section-description">

                        Change your account password to keep your account secure.

                    </p>


                    <form
                        method="POST"
                        action=""
                    >


                        <div class="form-grid">


                            <!-- CURRENT PASSWORD -->

                            <div class="form-group full-width">


                                <label>

                                    Current Password

                                    <span class="required">
                                        *
                                    </span>

                                </label>


                                <input
                                    type="password"
                                    name="current_password"
                                    class="form-control"
                                    required
                                >


                            </div>


                            <!-- NEW PASSWORD -->

                            <div class="form-group">


                                <label>

                                    New Password

                                    <span class="required">
                                        *
                                    </span>

                                </label>


                                <input
                                    type="password"
                                    name="new_password"
                                    class="form-control"
                                    required
                                >


                            </div>


                            <!-- CONFIRM PASSWORD -->

                            <div class="form-group">


                                <label>

                                    Confirm New Password

                                    <span class="required">
                                        *
                                    </span>

                                </label>


                                <input
                                    type="password"
                                    name="confirm_password"
                                    class="form-control"
                                    required
                                >


                            </div>


                        </div>


                        <div class="form-action">


                            <button
                                type="submit"
                                name="change_password"
                                class="save-button"
                            >

                                <i class="fa-solid fa-lock"></i>

                                Change Password

                            </button>


                        </div>


                    </form>


                    <div class="security-box">

                        <i class="fa-solid fa-shield-halved"></i>

                        Use a strong password and do not share it with anyone.

                    </div>


                </div>


            </div>


        </div>


    </div>


</div>


<script>


// =========================================================
// TAB FUNCTION
// =========================================================

var tabs =
    document.querySelectorAll(
        ".profile-tab"
    );


var tabContents =
    document.querySelectorAll(
        ".tab-content"
    );


for (
    var i = 0;
    i < tabs.length;
    i++
) {


    tabs[i].onclick =
        function()
        {


            var selectedTab =
                this.getAttribute(
                    "data-tab"
                );


            // REMOVE ACTIVE TAB

            for (
                var j = 0;
                j < tabs.length;
                j++
            ) {

                tabs[j].classList.remove(
                    "active"
                );

            }


            // REMOVE ACTIVE CONTENT

            for (
                var k = 0;
                k < tabContents.length;
                k++
            ) {

                tabContents[k].classList.remove(
                    "active"
                );

            }


            // ACTIVATE TAB

            this.classList.add(
                "active"
            );


            document
                .getElementById(
                    selectedTab
                )
                .classList
                .add(
                    "active"
                );


        };


}


// =========================================================
// PASSWORD VALIDATION
// =========================================================

var passwordForm =
    document.querySelector(
        "#security form"
    );


if (passwordForm) {


    passwordForm.onsubmit =
        function(event)
        {


            var newPassword =
                document.querySelector(
                    '[name="new_password"]'
                );


            var confirmPassword =
                document.querySelector(
                    '[name="confirm_password"]'
                );


            if (
                newPassword.value
                !=
                confirmPassword.value
            ) {

                event.preventDefault();

                alert(
                    "New passwords do not match."
                );

            }


        };


}


</script>


</body>

</html>

