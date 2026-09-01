<?php
session_start();
require_once "../config/database.php";
$message = "";
$message_type = "";
$name = "";
$email = "";
$phone = "";
$address = "";
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = trim($_POST["name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $phone = trim($_POST["phone"] ?? "");
    $address = trim($_POST["address"] ?? "");
    $password = $_POST["password"] ?? "";
    $confirm_password = $_POST["confirm_password"] ?? "";
    
    if ($name === "") {
        $message = "Please enter your full name.";
        $message_type = "error";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Please enter a valid email address.";
        $message_type = "error";
    } elseif (!preg_match("/^[0-9]{10}$/", $phone)) {
        $message = "Phone number must contain exactly 10 digits.";
        $message_type = "error";
    } elseif (strlen($password) < 8) {
        $message = "Password must be at least 8 characters long.";
        $message_type = "error";
    } elseif ($password !== $confirm_password) {
        $message = "Passwords do not match.";
        $message_type = "error";
    } elseif ($address === "") {
        $message = "Please enter your address.";
        $message_type = "error";
    } else {
        
        $check_sql = "
            SELECT user_id
            FROM users
            WHERE email = ?
            LIMIT 1
        ";
        $check_stmt = $conn->prepare($check_sql);
        if (!$check_stmt) {
            $message = "Something went wrong. Please try again.";
            $message_type = "error";
        } else {
            $check_stmt->bind_param("s", $email);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            if ($check_result->num_rows > 0) {
                $message = "An account with this email already exists.";
                $message_type = "error";
            } else {
                
                $hashed_password = password_hash(
                    $password,
                    PASSWORD_DEFAULT
                );
                
                $sql = "
                    INSERT INTO users
                    (
                        name,
                        email,
                        password,
                        phone,
                        address,
                        role
                    )
                    VALUES
                    (
                        ?,
                        ?,
                        ?,
                        ?,
                        ?,
                        'customer'
                    )
                ";
                $stmt = $conn->prepare($sql);
                if (!$stmt) {
                    $message = "Something went wrong. Please try again.";
                    $message_type = "error";
                } else {
                    $stmt->bind_param(
                        "sssss",
                        $name,
                        $email,
                        $hashed_password,
                        $phone,
                        $address
                    );
                    if ($stmt->execute()) {
                        header("Location: login.php?registered=1");
                        exit;
                    } else {
                        $message = "Registration failed. Please try again.";
                        $message_type = "error";
                    }
                    $stmt->close();
                }
            }
            $check_stmt->close();
        }
    }
}
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
        Create Account - Gauley Ko Pasal
    </title>
    <link
        rel="stylesheet"
        href="../css/customer.css"
    >
    <style>
        .auth-page {
            min-height: calc(100vh - 160px);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 60px 20px;
        }
        .auth-card {
            width: 100%;
            max-width: 520px;
            background: white;
            border: 1px solid #e4e9ef;
            border-radius: 20px;
            padding: 38px;
            box-shadow: 0 20px 50px rgba(20, 40, 70, 0.08);
        }
        .auth-header {
            margin-bottom: 28px;
        }
        .auth-eyebrow {
            color: #1976d2;
            font-size: 10px;
            font-weight: 900;
            letter-spacing: 2px;
            margin-bottom: 8px;
        }
        .auth-header h1 {
            font-size: 32px;
            color: #102a43;
            margin-bottom: 8px;
        }
        .auth-header p {
            color: #718096;
            font-size: 13px;
            line-height: 1.6;
        }
        .form-group {
            margin-bottom: 18px;
        }
        .form-group label {
            display: block;
            font-size: 12px;
            font-weight: 800;
            color: #172033;
            margin-bottom: 7px;
        }
        .form-group input {
            width: 100%;
            padding: 13px 14px;
            border: 1px solid #dce3ea;
            border-radius: 9px;
            outline: none;
            font-size: 14px;
            background: #fafbfd;
            transition: 0.2s;
        }
        .form-group input:focus {
            border-color: #1976d2;
            background: white;
            box-shadow: 0 0 0 3px rgba(25, 118, 210, 0.08);
        }
        .form-help {
            margin-top: 6px;
            font-size: 11px;
            color: #718096;
        }
        .auth-button {
            width: 100%;
            border: none;
            background: #102a43;
            color: white;
            padding: 14px;
            border-radius: 9px;
            font-size: 13px;
            font-weight: 800;
            cursor: pointer;
            transition: 0.2s;
            margin-top: 5px;
        }
        .auth-button:hover {
            background: #1976d2;
        }
        .auth-message {
            padding: 12px 14px;
            border-radius: 9px;
            margin-bottom: 20px;
            font-size: 13px;
            line-height: 1.5;
        }
        .auth-message.error {
            background: #fff1f1;
            color: #b42318;
            border: 1px solid #ffd5d2;
        }
        .auth-message.success {
            background: #effaf3;
            color: #18794e;
            border: 1px solid #c9efd8;
        }
        .auth-footer {
            text-align: center;
            margin-top: 24px;
            padding-top: 20px;
            border-top: 1px solid #edf0f3;
            color: #718096;
            font-size: 12px;
        }
        .auth-footer a {
            color: #1976d2;
            font-weight: 800;
        }
        .password-rules {
            margin-top: 7px;
            color: #718096;
            font-size: 11px;
        }
        @media (max-width: 600px) {
            .auth-page {
                padding: 35px 15px;
            }
            .auth-card {
                padding: 28px 20px;
            }
            .auth-header h1 {
                font-size: 27px;
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
            <a href="login.php">
                Login
            </a>
        </nav>
    </div>
</header>
<main class="auth-page">
    <div class="auth-card">
        <div class="auth-header">
            <div class="auth-eyebrow">
                GAULEY KO PASAL
            </div>
            <h1>
                Create your account
            </h1>
            <p>
                Join Gauley Ko Pasal and start shopping from local sellers.
            </p>
        </div>
        <?php if ($message !== ""): ?>
            <div class="auth-message <?= htmlspecialchars($message_type) ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>
        <form
            method="POST"
            action=""
        >
            
            <div class="form-group">
                <label for="name">
                    Full Name
                </label>
                <input
                    type="text"
                    id="name"
                    name="name"
                    value="<?= htmlspecialchars($name) ?>"
                    placeholder="Enter your full name"
                    autocomplete="name"
                    required
                >
            </div>
            
            <div class="form-group">
                <label for="email">
                    Email Address
                </label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    value="<?= htmlspecialchars($email) ?>"
                    placeholder="you@example.com"
                    autocomplete="email"
                    required
                >
            </div>
            
            <div class="form-group">
                <label for="phone">
                    Phone Number
                </label>
                <input
                    type="tel"
                    id="phone"
                    name="phone"
                    value="<?= htmlspecialchars($phone) ?>"
                    placeholder="98XXXXXXXX"
                    inputmode="numeric"
                    maxlength="10"
                    pattern="[0-9]{10}"
                    autocomplete="tel"
                    required
                >
                <div class="form-help">
                    Enter exactly 10 digits.
                </div>
            </div>
            
            <div class="form-group">
                <label for="address">
                    Address
                </label>
                <input
                    type="text"
                    id="address"
                    name="address"
                    value="<?= htmlspecialchars($address) ?>"
                    placeholder="Enter your address"
                    autocomplete="street-address"
                    required
                >
            </div>
            
            <div class="form-group">
                <label for="password">
                    Password
                </label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    placeholder="Create a password"
                    minlength="8"
                    autocomplete="new-password"
                    required
                >
                <div class="password-rules">
                    Password must be at least 8 characters.
                </div>
            </div>
            
            <div class="form-group">
                <label for="confirm_password">
                    Confirm Password
                </label>
                <input
                    type="password"
                    id="confirm_password"
                    name="confirm_password"
                    placeholder="Enter your password again"
                    minlength="8"
                    autocomplete="new-password"
                    required
                >
            </div>
            <button
                type="submit"
                class="auth-button"
            >
                Create Account
            </button>
        </form>
        <div class="auth-footer">
            Already have an account?
            <a href="login.php">
                Login here
            </a>
        </div>
    </div>
</main>
<footer>
    <div class="footer-inner">
        <div>
            <h3>
                Gauley Ko Pasal
            </h3>
            <p>
                Your local online marketplace.
            </p>
        </div>
        <div>
            <h4>
                Shop
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
            <a href="login.php">
                Login
            </a>
            <a href="register.php">
                Register
            </a>
        </div>
    </div>
    <div class="footer-bottom">
        © <?= date("Y") ?> Gauley Ko Pasal
    </div>
</footer>
</body>
</html> 

