<?php

session_start();

require_once "../config/database.php";

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST["email"]);
    $password = $_POST["password"];

    $sql = "SELECT * FROM users
            WHERE email = ?
            AND role = 'seller'";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();

    $result = $stmt->get_result();

    if ($result->num_rows === 1) {

        $user = $result->fetch_assoc();

        if (password_verify($password, $user["password"])) {

            if ($user["seller_status"] === "Approved") {

                $_SESSION["user_id"] = $user["user_id"];
                $_SESSION["name"] = $user["name"];
                $_SESSION["role"] = $user["role"];

                header("Location: dashboard.php");
                exit();

            } elseif ($user["seller_status"] === "Pending") {

                $message = "Your seller registration is waiting for admin approval.";

            } elseif ($user["seller_status"] === "Rejected") {

                $message = "Your seller registration was not approved.";

            } else {

                $message = "Your seller account has not been approved yet.";

            }

        } else {

            $message = "Incorrect password.";

        }

    } else {

        $message = "Seller account not found.";

    }
}

?>

<!DOCTYPE html>
<html>

<head>

    <title>Seller Login - Gauley Ko Pasal</title>

    <link rel="stylesheet" href="../style.css">

</head>

<body>

<header>

    <h1>Gauley Ko Pasal</h1>

</header>


<div class="container">

    <div class="hero">

        <h2>Seller Login 🏪</h2>

        <p>
            Login to manage your products and orders.
        </p>

    </div>


    <div class="card">

        <?php if ($message !== ""): ?>

            <p>
                <?php echo htmlspecialchars($message); ?>
            </p>

        <?php endif; ?>


        <form method="POST">

            <label>Email</label>

            <input
                type="email"
                name="email"
                required
            >

            <br><br>


            <label>Password</label>

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


        <br>

        <p>
            Want to become a seller?

            <a href="register.php">
                Register as Seller
            </a>
        </p>

    </div>

</div>


<footer>

    <p>
        Gauley Ko Pasal — Seller Panel 🇳🇵
    </p>

</footer>

</body>

</html>