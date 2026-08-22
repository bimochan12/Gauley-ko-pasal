<?php

session_start();

require_once "../config/database.php";

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $email = trim($_POST["email"]);
    $password = $_POST["password"];

    $sql = "SELECT * FROM users WHERE email = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();

    $result = $stmt->get_result();

    if ($result->num_rows == 1) {

        $user = $result->fetch_assoc();

        if (password_verify($password, $user["password"])) {

            $_SESSION["user_id"] = $user["user_id"];
            $_SESSION["name"] = $user["name"];
            $_SESSION["role"] = $user["role"];

            header("Location: index.php");
            exit();

        } else {

            $message = "Incorrect password.";

        }

    } else {

        $message = "No account found with that email.";

    }
}

?>

<!DOCTYPE html>
<html>

<head>

    <title>Gauley Ko Pasal - Login</title>

    <link rel="stylesheet" href="../style.css">

</head>

<body>

<header>

    <h1>Gauley Ko Pasal</h1>

    <nav>

        <a href="index.php">Home</a>

        <a href="cart.php">Cart</a>

        <a href="register.php">Register</a>

    </nav>

</header>


<div class="container">

    <div class="hero">

        <h2>Welcome Back 🙏</h2>

        <p>Login to your Gauley Ko Pasal account.</p>

    </div>


    <div class="card">

        <?php if ($message != ""): ?>

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
            Don't have an account?
            <a href="register.php">Create one</a>
        </p>

    </div>

</div>


<footer>

    <p>
        © <?php echo date("Y"); ?> Gauley Ko Pasal
    </p>

    <p>
        Your friendly local online shop 🇳🇵
    </p>

</footer>

</body>

</html>