<?php
session_start();
require_once "../config/database.php";
$message = "";
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST["email"]);
    $password = $_POST["password"];
    $sql = "
        SELECT
            user_id,
            name,
            email,
            password,
            role
        FROM users
        WHERE email = ?
        AND role = 'admin'
    ";
    $stmt = $conn->prepare($sql);
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
            $_SESSION["user_id"] =
                $user["user_id"];
            $_SESSION["name"] =
                $user["name"];
            $_SESSION["role"] =
                $user["role"];
            $admin_id =
                (int) $user["user_id"];
            $action =
                "Admin logged in";
            $details =
                "Admin " .
                $user["name"] .
                " logged into the admin panel.";
            $log_stmt = $conn->prepare(
                "INSERT INTO activity_logs
                (
                    user_id,
                    action,
                    details
                )
                VALUES (?, ?, ?)"
            );
            if ($log_stmt) {
                $log_stmt->bind_param(
                    "iss",
                    $admin_id,
                    $action,
                    $details
                );
                $log_stmt->execute();
            }
            header(
                "Location: dashboard.php"
            );
            exit();
        } else {
            $message =
                "Incorrect password.";
        }
    } else {
        $message =
            "Admin account not found.";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>
        Gauley Ko Pasal - Admin Login
    </title>
    <link
        rel="stylesheet"
        href="../style.css"
    >
    <style>
        .site-logo {
            color: inherit;
            text-decoration: none;
            cursor: pointer;
        }
        .site-logo:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
<header>
    <h1>
        <a
            href="../index.php"
            class="site-logo"
        >
            Gauley Ko Pasal
        </a>
    </h1>
    <nav>
        <a href="login.php">
            Admin Login
        </a>
    </nav>
</header>
<div class="container">
    <div class="hero">
        <h2>
            Admin Login
        </h2>
        <p>
            Login to manage the Gauley Ko Pasal
            administration panel.
        </p>
    </div>
    <div class="card">
        <?php if ($message !== ""): ?>
            <p>
                <?php
                echo htmlspecialchars(
                    $message
                );
                ?>
            </p>
        <?php endif; ?>
        <form method="POST">
            <label>
                Email
            </label>
            <input
                type="email"
                name="email"
                required
            >
            <br><br>
            <label>
                Password
            </label>
            <input
                type="password"
                name="password"
                required
            >
            <br><br>
            <button type="submit">
                Login
            </button>
        </form>
    </div>
</div>
<footer>
    <p>
        Gauley Ko Pasal —
        Admin Panel 🇳🇵
    </p>
</footer>
</body>
</html>

