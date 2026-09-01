<?php
session_start();
require_once "../config/database.php";
if (isset($_SESSION["user_id"])) {
    if (
        isset($_SESSION["role"]) &&
        $_SESSION["role"] === "customer"
    ) {
        header("Location: index.php");
        exit();
    } elseif (
        isset($_SESSION["role"]) &&
        $_SESSION["role"] === "seller"
    ) {
        header("Location: ../seller/dashboard.php");
        exit();
    } elseif (
        isset($_SESSION["role"]) &&
        $_SESSION["role"] === "admin"
    ) {
        header("Location: ../admin/dashboard.php");
        exit();
    }
}
$email = "";
$message = "";
$message_type = "";
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";
    
    if ($email === "") {
        $message = "Please enter your email address.";
        $message_type = "error";
    } elseif (
        !filter_var(
            $email,
            FILTER_VALIDATE_EMAIL
        )
    ) {
        $message = "Please enter a valid email address.";
        $message_type = "error";
    } elseif ($password === "") {
        $message = "Please enter your password.";
        $message_type = "error";
    } else {
        
        $stmt = $conn->prepare(
            "SELECT
                user_id,
                name,
                email,
                password,
                role,
                seller_status
             FROM users
             WHERE email = ?
             LIMIT 1"
        );
        if (!$stmt) {
            $message =
                "Something went wrong. Please try again.";
            $message_type = "error";
        } else {
            $stmt->bind_param(
                "s",
                $email
            );
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();
                
                if (
                    password_verify(
                        $password,
                        $user["password"]
                    )
                ) {
                    
                    if (
                        $user["role"] === "seller" &&
                        $user["seller_status"] !== "Approved"
                    ) {
                        $message =
                            "Your seller account is not approved.";
                        $message_type = "error";
                    } else {
                        
                        $_SESSION["user_id"] =
                            (int) $user["user_id"];
                        $_SESSION["name"] =
                            $user["name"];
                        $_SESSION["email"] =
                            $user["email"];
                        $_SESSION["role"] =
                            $user["role"];
                        
                        if (
                            $user["role"] === "customer"
                        ) {
                            header(
                                "Location: index.php"
                            );
                            exit();
                        } elseif (
                            $user["role"] === "seller"
                        ) {
                            header(
                                "Location: ../seller/dashboard.php"
                            );
                            exit();
                        } elseif (
                            $user["role"] === "admin"
                        ) {
                            header(
                                "Location: ../admin/dashboard.php"
                            );
                            exit();
                        } else {
                            $message =
                                "Invalid account role.";
                            $message_type = "error";
                        }
                    }
                } else {
                    $message =
                        "Incorrect email or password.";
                    $message_type = "error";
                }
            } else {
                $message =
                    "Incorrect email or password.";
                $message_type = "error";
            }
            $stmt->close();
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
        Customer Login - Gauley Ko Pasal
    </title>
    <link
        rel="stylesheet"
        href="../css/customer.css"
    >
    <style>
        
        body {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .login-page {
            width: 92%;
            max-width: 480px;
            margin: 55px auto 70px;
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-card {
            width: 100%;
            background: white;
            border-radius: 18px;
            padding: 35px;
            border: 1px solid #e5e7eb;
            box-shadow:
                0 10px 30px
                rgba(0, 0, 0, 0.08);
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 28px;
        }
        .login-icon {
            width: 65px;
            height: 65px;
            margin: 0 auto 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #eef6fd;
            border-radius: 16px;
            font-size: 30px;
        }
        .login-header h1 {
            margin: 0 0 8px;
            font-size: 29px;
            color: #102a43;
        }
        .login-header p {
            margin: 0;
            color: #718096;
            font-size: 13px;
            line-height: 1.6;
        }
        
        .message {
            padding: 13px 15px;
            border-radius: 9px;
            margin-bottom: 20px;
            font-size: 13px;
            line-height: 1.5;
        }
        .message.error {
            background: #fff1f1;
            border: 1px solid #ffd0d0;
            color: #b42318;
        }
        
        .form-group {
            margin-bottom: 18px;
        }
        .form-group label {
            display: block;
            margin-bottom: 7px;
            font-size: 12px;
            font-weight: 900;
            color: #172033;
        }
        .form-group input {
            width: 100%;
            box-sizing: border-box;
            padding: 13px 14px;
            border: 1px solid #dce3ea;
            border-radius: 9px;
            outline: none;
            font-family: inherit;
            font-size: 14px;
            transition: 0.2s;
        }
        .form-group input:focus {
            border-color: #1976d2;
            box-shadow:
                0 0 0 3px
                rgba(25, 118, 210, 0.08);
        }
        
        .login-button {
            width: 100%;
            border: none;
            background: #1976d2;
            color: white;
            padding: 14px;
            border-radius: 9px;
            font-size: 14px;
            font-weight: 900;
            cursor: pointer;
            margin-top: 5px;
            transition: 0.2s;
        }
        .login-button:hover {
            background: #102a43;
        }
        
        .login-links {
            text-align: center;
            margin-top: 22px;
            padding-top: 20px;
            border-top: 1px solid #edf0f3;
        }
        .login-links p {
            margin: 8px 0;
            color: #718096;
            font-size: 12px;
        }
        .login-links a {
            color: #1976d2;
            font-weight: 800;
            text-decoration: none;
        }
        .login-links a:hover {
            text-decoration: underline;
        }
        
        .main-login-button {
            display: block;
            width: 100%;
            box-sizing: border-box;
            text-align: center;
            margin-top: 15px;
            padding: 12px;
            border: 1px solid #dce3ea;
            border-radius: 9px;
            background: #f8fafc;
            color: #172033;
            text-decoration: none;
            font-size: 12px;
            font-weight: 800;
            transition: 0.2s;
        }
        .main-login-button:hover {
            border-color: #1976d2;
            color: #1976d2;
            background: #eef6fd;
            text-decoration: none;
        }
        
        @media (max-width: 500px) {
            .login-page {
                width: 94%;
                margin-top: 30px;
            }
            .login-card {
                padding: 24px 20px;
            }
            .login-header h1 {
                font-size: 25px;
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
            href="../index.php"
            class="logo"
        >
            Gauley Ko
            <span>Pasal</span>
        </a>
        <nav class="nav-links">
            <a href="../index.php">
                Home
            </a>
            <a href="register.php">
                Register
            </a>
        </nav>
    </div>
</header>
<main class="login-page">
    <div class="login-card">
        <div class="login-header">
            <div class="login-icon">
                🛒
            </div>
            <h1>
                Customer Login
            </h1>
            <p>
                Login to your Gauley Ko Pasal
                customer account.
            </p>
        </div>
        
        <?php if ($message !== ""): ?>
            <div
                class="message <?php echo htmlspecialchars($message_type); ?>"
            >
                <?php
                echo htmlspecialchars($message);
                ?>
            </div>
        <?php endif; ?>
        
        <form
            method="POST"
            action=""
        >
            <div class="form-group">
                <label for="email">
                    Email Address
                </label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    value="<?php
                        echo htmlspecialchars($email);
                    ?>"
                    placeholder="Enter your email"
                    autocomplete="email"
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
                    placeholder="Enter your password"
                    autocomplete="current-password"
                    required
                >
            </div>
            <button
                type="submit"
                class="login-button"
            >
                Login to Customer Account
            </button>
        </form>
        
        <div class="login-links">
            <p>
                Don't have a customer account?
                <a href="register.php">
                    Create Account
                </a>
            </p>
            <p>
                Are you a seller?
                <a href="../seller/login.php">
                    Seller Login
                </a>
            </p>
            
            <a
                href="../index.php"
                class="main-login-button"
            >
                ← Back to Main Login
            </a>
        </div>
    </div>
</main>
<footer>
    <div class="footer-bottom">
        © <?php echo date("Y"); ?>
        Gauley Ko Pasal 🇳🇵
    </div>
</footer>
</body>
</html>

