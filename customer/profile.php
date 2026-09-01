<?php
session_start();
require_once "../config/database.php";
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}
$user_id = (int) $_SESSION["user_id"];
$message = "";
$message_type = "";
if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["update_profile"])
) {
    $name =
        trim($_POST["name"] ?? "");
    $phone =
        trim($_POST["phone"] ?? "");
    $address =
        trim($_POST["address"] ?? "");
    if ($name === "") {
        $message =
            "Name is required.";
        $message_type = "error";
    } elseif (strlen($name) > 100) {
        $message =
            "Name cannot be longer than 100 characters.";
        $message_type = "error";
    } elseif (strlen($phone) > 20) {
        $message =
            "Phone number cannot be longer than 20 characters.";
        $message_type = "error";
    } elseif (strlen($address) > 255) {
        $message =
            "Address cannot be longer than 255 characters.";
        $message_type = "error";
    } else {
        $sql = "
            UPDATE users
            SET
                name = ?,
                phone = ?,
                address = ?
            WHERE user_id = ?
        ";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            $message =
                "Database error.";
            $message_type = "error";
        } else {
            $stmt->bind_param(
                "sssi",
                $name,
                $phone,
                $address,
                $user_id
            );
            if ($stmt->execute()) {
                
                $_SESSION["name"] = $name;
                $message =
                    "Profile updated successfully.";
                $message_type = "success";
            } else {
                $message =
                    "Could not update your profile.";
                $message_type = "error";
            }
            $stmt->close();
        }
    }
}
if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["change_password"])
) {
    $current_password =
        $_POST["current_password"] ?? "";
    $new_password =
        $_POST["new_password"] ?? "";
    $confirm_password =
        $_POST["confirm_password"] ?? "";
    
    $password_stmt = $conn->prepare(
        "SELECT password
         FROM users
         WHERE user_id = ?"
    );
    if (!$password_stmt) {
        $message =
            "Database error.";
        $message_type = "error";
    } else {
        $password_stmt->bind_param(
            "i",
            $user_id
        );
        $password_stmt->execute();
        $password_result =
            $password_stmt->get_result();
        if (
            $password_result->num_rows !== 1
        ) {
            $message =
                "User account could not be found.";
            $message_type = "error";
        } else {
            $user = $password_result->fetch_assoc();
            $stored_password =
                $user["password"];
            
            if (
                !password_verify(
                    $current_password,
                    $stored_password
                )
            ) {
                $message =
                    "Current password is incorrect.";
                $message_type = "error";
            } elseif (
                strlen($new_password) < 6
            ) {
                $message =
                    "New password must be at least 6 characters.";
                $message_type = "error";
            } elseif (
                $new_password !== $confirm_password
            ) {
                $message =
                    "New passwords do not match.";
                $message_type = "error";
            } else {
                
                $hashed_password =
                    password_hash(
                        $new_password,
                        PASSWORD_DEFAULT
                    );
                $update_password =
                    $conn->prepare(
                        "UPDATE users
                         SET password = ?
                         WHERE user_id = ?"
                    );
                if (!$update_password) {
                    $message =
                        "Database error.";
                    $message_type = "error";
                } else {
                    $update_password->bind_param(
                        "si",
                        $hashed_password,
                        $user_id
                    );
                    if (
                        $update_password->execute()
                    ) {
                        $message =
                            "Password changed successfully.";
                        $message_type = "success";
                    } else {
                        $message =
                            "Could not change your password.";
                        $message_type = "error";
                    }
                    $update_password->close();
                }
            }
        }
        $password_stmt->close();
    }
}
$sql = "
    SELECT
        user_id,
        name,
        email,
        phone,
        address,
        role,
        created_at
    FROM users
    WHERE user_id = ?
";
$stmt = $conn->prepare($sql);
$stmt->bind_param(
    "i",
    $user_id
);
$stmt->execute();
$result =
    $stmt->get_result();
if ($result->num_rows !== 1) {
    session_destroy();
    header("Location: login.php");
    exit;
}
$user =
    $result->fetch_assoc();
$name =
    $user["name"] ?? "";
$email =
    $user["email"] ?? "";
$phone =
    $user["phone"] ?? "";
$address =
    $user["address"] ?? "";
$created_at =
    $user["created_at"] ?? "";
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
        My Profile - Gauley Ko Pasal
    </title>
    <link
        rel="stylesheet"
        href="../css/customer.css"
    >
    <style>
        
        .profile-page {
            width: 92%;
            max-width: 1000px;
            margin: 0 auto;
            padding: 55px 0 80px;
        }
        .profile-header {
            margin-bottom: 35px;
        }
        .profile-eyebrow {
            color: #1976d2;
            font-size: 10px;
            font-weight: 900;
            letter-spacing: 2px;
        }
        .profile-header h1 {
            font-size: 38px;
            color: #102a43;
            margin: 8px 0;
        }
        .profile-header p {
            color: #718096;
            font-size: 14px;
        }
        
        .profile-grid {
            display: grid;
            grid-template-columns:
                1fr 1fr;
            gap: 25px;
        }
        .profile-card {
            background: white;
            border: 1px solid #e4e9ef;
            border-radius: 18px;
            padding: 28px;
            box-shadow:
                0 8px 25px
                rgba(20, 40, 70, 0.04);
        }
        .profile-card.full {
            grid-column: 1 / -1;
        }
        .profile-card h2 {
            color: #102a43;
            font-size: 20px;
            margin-bottom: 7px;
        }
        .profile-card > p {
            color: #718096;
            font-size: 13px;
            margin-bottom: 25px;
        }
        
        .profile-form-group {
            margin-bottom: 18px;
        }
        .profile-form-group label {
            display: block;
            color: #102a43;
            font-size: 12px;
            font-weight: 800;
            margin-bottom: 7px;
        }
        .profile-form-group input,
        .profile-form-group textarea {
            width: 100%;
            box-sizing: border-box;
            padding: 12px 13px;
            border: 1px solid #dce3ea;
            border-radius: 9px;
            font-family: inherit;
            font-size: 13px;
            outline: none;
        }
        .profile-form-group textarea {
            resize: vertical;
            min-height: 100px;
        }
        .profile-form-group input:focus,
        .profile-form-group textarea:focus {
            border-color: #1976d2;
        }
        .profile-form-group input[readonly] {
            background: #f5f7fa;
            color: #718096;
            cursor: not-allowed;
        }
        
        .profile-button {
            border: none;
            background: #102a43;
            color: white;
            padding: 12px 18px;
            border-radius: 9px;
            font-size: 12px;
            font-weight: 800;
            cursor: pointer;
        }
        .profile-button:hover {
            background: #1976d2;
        }
        
        .account-info {
            display: grid;
            grid-template-columns:
                1fr 1fr;
            gap: 15px;
        }
        .account-info-box {
            background: #f7f9fb;
            border-radius: 10px;
            padding: 15px;
        }
        .account-info-box span {
            display: block;
            color: #718096;
            font-size: 10px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 5px;
        }
        .account-info-box strong {
            color: #102a43;
            font-size: 13px;
        }
        
        .profile-message {
            padding: 13px 16px;
            border-radius: 10px;
            margin-bottom: 25px;
            font-size: 13px;
            font-weight: 700;
        }
        .profile-message.success {
            background: #e9f8ef;
            color: #16834b;
            border: 1px solid #c8ecd8;
        }
        .profile-message.error {
            background: #fff0f0;
            color: #d64545;
            border: 1px solid #f2cccc;
        }
        
        @media (max-width: 700px) {
            .profile-page {
                padding-top: 35px;
            }
            .profile-header h1 {
                font-size: 30px;
            }
            .profile-grid {
                grid-template-columns: 1fr;
            }
            .profile-card.full {
                grid-column: auto;
            }
            .account-info {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
<div class="topbar">
    🇳🇵 Local shopping made simple
</div>
<header class="navbar">
    <div class="nav-inner">
        <a
            href="index.php"
            class="logo"
        >
            Gauley Ko <span>Pasal</span>
        </a>
        <form
            class="search"
            method="GET"
            action="products.php"
        >
            <input
                type="text"
                name="search"
                placeholder="Search products..."
            >
            <button type="submit">
                🔍
            </button>
        </form>
        <nav class="nav-links">
            <a href="index.php">
                Home
            </a>
            <a href="products.php">
                Products
            </a>
            <a href="cart.php">
                🛒 Cart
            </a>
            <a href="my_orders.php">
                My Orders
            </a>
            <a href="profile.php">
                Profile
            </a>
            <a href="logout.php">
                Logout
            </a>
        </nav>
    </div>
</header>
<main class="profile-page">
    <div class="profile-header">
        <span class="profile-eyebrow">
            YOUR ACCOUNT
        </span>
        <h1>
            My Profile
        </h1>
        <p>
            Manage your personal information and account settings.
        </p>
    </div>
    <?php if ($message !== ""): ?>
        <div
            class="profile-message <?php
                echo htmlspecialchars(
                    $message_type
                );
            ?>"
        >
            <?php
            echo htmlspecialchars(
                $message
            );
            ?>
        </div>
    <?php endif; ?>
    <div class="profile-grid">
        
        <section class="profile-card">
            <h2>
                Personal Information
            </h2>
            <p>
                Update the information used for your account and deliveries.
            </p>
            <form
                method="POST"
                action="profile.php"
            >
                <div class="profile-form-group">
                    <label>
                        Full Name
                    </label>
                    <input
                        type="text"
                        name="name"
                        value="<?php
                            echo htmlspecialchars(
                                $name
                            );
                        ?>"
                        maxlength="100"
                        required
                    >
                </div>
                <div class="profile-form-group">
                    <label>
                        Email Address
                    </label>
                    <input
                        type="email"
                        value="<?php
                            echo htmlspecialchars(
                                $email
                            );
                        ?>"
                        readonly
                    >
                    <small style="
                        display:block;
                        margin-top:6px;
                        color:#718096;
                        font-size:11px;
                    ">
                        Email cannot be changed here.
                    </small>
                </div>
                <div class="profile-form-group">
                    <label>
                        Phone Number
                    </label>
                    <input
                        type="text"
                        name="phone"
                        value="<?php
                            echo htmlspecialchars(
                                $phone
                            );
                        ?>"
                        maxlength="20"
                        placeholder="Your phone number"
                    >
                </div>
                <div class="profile-form-group">
                    <label>
                        Address
                    </label>
                    <textarea
                        name="address"
                        maxlength="255"
                        placeholder="Your delivery address"
                    ><?php
                        echo htmlspecialchars(
                            $address
                        );
                    ?></textarea>
                </div>
                <button
                    type="submit"
                    name="update_profile"
                    class="profile-button"
                >
                    Save Changes
                </button>
            </form>
        </section>
        
        <section class="profile-card">
            <h2>
                Account Information
            </h2>
            <p>
                Information about your Gauley Ko Pasal account.
            </p>
            <div class="account-info">
                <div class="account-info-box">
                    <span>
                        Account ID
                    </span>
                    <strong>
                        #<?php
                        echo (int) $user["user_id"];
                        ?>
                    </strong>
                </div>
                <div class="account-info-box">
                    <span>
                        Account Type
                    </span>
                    <strong>
                        Customer
                    </strong>
                </div>
                <div class="account-info-box">
                    <span>
                        Email
                    </span>
                    <strong>
                        <?php
                        echo htmlspecialchars(
                            $email
                        );
                        ?>
                    </strong>
                </div>
                <div class="account-info-box">
                    <span>
                        Joined
                    </span>
                    <strong>
                        <?php
                        if ($created_at !== "") {
                            echo htmlspecialchars(
                                date(
                                    "M d, Y",
                                    strtotime(
                                        $created_at
                                    )
                                )
                            );
                        } else {
                            echo "Unknown";
                        }
                        ?>
                    </strong>
                </div>
            </div>
        </section>
        
        <section class="profile-card full">
            <h2>
                Change Password
            </h2>
            <p>
                Keep your account secure by using a strong password.
            </p>
            <form
                method="POST"
                action="profile.php"
            >
                <div class="profile-grid">
                    <div class="profile-form-group">
                        <label>
                            Current Password
                        </label>
                        <input
                            type="password"
                            name="current_password"
                            required
                        >
                    </div>
                    <div class="profile-form-group">
                        <label>
                            New Password
                        </label>
                        <input
                            type="password"
                            name="new_password"
                            minlength="6"
                            required
                        >
                    </div>
                </div>
                <div class="profile-form-group">
                    <label>
                        Confirm New Password
                    </label>
                    <input
                        type="password"
                        name="confirm_password"
                        minlength="6"
                        required
                    >
                </div>
                <button
                    type="submit"
                    name="change_password"
                    class="profile-button"
                >
                    Change Password
                </button>
            </form>
        </section>
    </div>
</main>
<footer>
    <div class="footer-inner">
        <div>
            <h3>
                Gauley Ko Pasal
            </h3>
            <p>
                Your local online marketplace
                for everyday products.
            </p>
        </div>
        <div>
            <h4>
                Quick Links
            </h4>
            <a href="index.php">
                Home
            </a>
            <a href="products.php">
                Products
            </a>
            <a href="cart.php">
                Cart
            </a>
        </div>
        <div>
            <h4>
                Account
            </h4>
            <a href="my_orders.php">
                My Orders
            </a>
            <a href="profile.php">
                Profile
            </a>
            <a href="logout.php">
                Logout
            </a>
        </div>
    </div>
    <div class="footer-bottom">
        © 2026 Gauley Ko Pasal.
        All rights reserved.
    </div>
</footer>
</body>
</html>

