
<?php

session_start();

require_once "../config/database.php";


/* =========================
   ADMIN AUTHENTICATION
========================= */

if (
    !isset($_SESSION["user_id"]) ||
    !isset($_SESSION["role"]) ||
    $_SESSION["role"] !== "admin"
) {
    header("Location: login.php");
    exit();
}


$message = "";


/* =========================
   GET CURRENT ADMIN
========================= */

$admin_id = (int) $_SESSION["user_id"];

$stmt = $conn->prepare(
    "SELECT name, email
     FROM users
     WHERE user_id = ?
     AND role = 'admin'"
);

$stmt->bind_param(
    "i",
    $admin_id
);

$stmt->execute();

$result = $stmt->get_result();

$admin = $result->fetch_assoc();


/* =========================
   UPDATE PROFILE
========================= */

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["update_profile"])
) {

    $name = trim($_POST["name"]);


    if ($name === "") {

        $message =
            "Name cannot be empty.";

    } else {

        $update_stmt = $conn->prepare(
            "UPDATE users
             SET name = ?
             WHERE user_id = ?
             AND role = 'admin'"
        );

        $update_stmt->bind_param(
            "si",
            $name,
            $admin_id
        );


        if ($update_stmt->execute()) {

            $_SESSION["name"] = $name;

            $message =
                "Profile updated successfully.";

            $admin["name"] = $name;

        } else {

            $message =
                "Could not update profile.";

        }

    }

}


/* =========================
   CHANGE PASSWORD
========================= */

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["change_password"])
) {

    $current_password =
        $_POST["current_password"];

    $new_password =
        $_POST["new_password"];

    $confirm_password =
        $_POST["confirm_password"];


    if (
        $current_password === "" ||
        $new_password === "" ||
        $confirm_password === ""
    ) {

        $message =
            "Please fill in all password fields.";

    }

    elseif (
        strlen($new_password) < 6
    ) {

        $message =
            "New password must be at least 6 characters.";

    }

    elseif (
        $new_password !== $confirm_password
    ) {

        $message =
            "New passwords do not match.";

    }

    else {

        $password_stmt = $conn->prepare(
            "SELECT password
             FROM users
             WHERE user_id = ?
             AND role = 'admin'"
        );

        $password_stmt->bind_param(
            "i",
            $admin_id
        );

        $password_stmt->execute();

        $password_result =
            $password_stmt->get_result();

        $password_data =
            $password_result->fetch_assoc();


        if (
            password_verify(
                $current_password,
                $password_data["password"]
            )
        ) {

            $hashed_password =
                password_hash(
                    $new_password,
                    PASSWORD_DEFAULT
                );


            $update_password_stmt =
                $conn->prepare(
                    "UPDATE users
                     SET password = ?
                     WHERE user_id = ?
                     AND role = 'admin'"
                );

            $update_password_stmt->bind_param(
                "si",
                $hashed_password,
                $admin_id
            );


            if (
                $update_password_stmt->execute()
            ) {

                $message =
                    "Password changed successfully.";

            } else {

                $message =
                    "Could not change password.";

            }

        } else {

            $message =
                "Current password is incorrect.";

        }

    }

}

?>

<!DOCTYPE html>

<html>

<head>

    <title>
        My Profile - Gauley Ko Pasal
    </title>

    <link
        rel="stylesheet"
        href="../style.css"
    >

</head>

<body>


<header>

    <h1>
        Gauley Ko Pasal
    </h1>

    <nav>

        <a href="dashboard.php">
            Dashboard
        </a>

        <a href="sellers.php">
            Sellers
        </a>

        <a href="users.php">
            Users
        </a>

        <a href="activity_logs.php">
            Activity Logs
        </a>

        <a href="profile.php">
            My Profile
        </a>

        <a href="logout.php">
            Logout
        </a>

    </nav>

</header>


<div class="container">


    <div class="hero">

        <h2>
            My Profile 👤
        </h2>

        <p>
            Manage your administrator account.
        </p>

    </div>


    <?php if ($message !== ""): ?>

        <div class="card">

            <p>

                <?php
                echo htmlspecialchars($message);
                ?>

            </p>

        </div>

        <br>

    <?php endif; ?>


    <!-- PROFILE DETAILS -->

    <div class="card">

        <h2>
            Profile Information
        </h2>


        <form method="POST">

            <input
                type="hidden"
                name="update_profile"
                value="1"
            >


            <label>
                Name
            </label>

            <input
                type="text"
                name="name"
                value="<?php
                echo htmlspecialchars(
                    $admin["name"] ?? ""
                );
                ?>"
                required
            >

            <br><br>


            <label>
                Email
            </label>

            <input
                type="email"
                value="<?php
                echo htmlspecialchars(
                    $admin["email"] ?? ""
                );
                ?>"
                disabled
            >

            <br><br>


            <button type="submit">
                Update Profile
            </button>

        </form>

    </div>


    <br>


    <!-- CHANGE PASSWORD -->

    <div class="card">

        <h2>
            Change Password
        </h2>


        <form method="POST">

            <input
                type="hidden"
                name="change_password"
                value="1"
            >


            <label>
                Current Password
            </label>

            <input
                type="password"
                name="current_password"
                required
            >

            <br><br>


            <label>
                New Password
            </label>

            <input
                type="password"
                name="new_password"
                minlength="6"
                required
            >

            <br><br>


            <label>
                Confirm New Password
            </label>

            <input
                type="password"
                name="confirm_password"
                minlength="6"
                required
            >

            <br><br>


            <button type="submit">
                Change Password
            </button>

        </form>

    </div>


    <br>


    <a href="dashboard.php">
        ← Back to Dashboard
    </a>


</div>


<footer>

    <p>
        Gauley Ko Pasal —
        Admin Panel 🇳🇵
    </p>

</footer>


</body>

</html>
```
