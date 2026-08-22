<?php

session_start();

require_once "../config/database.php";

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $name = trim($_POST["name"]);
    $email = trim($_POST["email"]);
    $password = $_POST["password"];
    $phone = trim($_POST["phone"]);
    $address = trim($_POST["address"]);

    if ($name == "" || $email == "" || $password == "") {

        $message = "Please fill in all required fields.";

    } else {

        $check_sql = "SELECT user_id FROM users WHERE email = ?";

        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("s", $email);
        $check_stmt->execute();

        $check_result = $check_stmt->get_result();

        if ($check_result->num_rows > 0) {

            $message = "An account with this email already exists.";

        } else {

            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            $sql = "INSERT INTO users
                    (name, email, password, phone, address, role)
                    VALUES (?, ?, ?, ?, ?, 'customer')";

            $stmt = $conn->prepare($sql);

            $stmt->bind_param(
                "sssss",
                $name,
                $email,
                $hashed_password,
                $phone,
                $address
            );

            if ($stmt->execute()) {

                header("Location: login.php");
                exit();

            } else {

                $message = "Registration failed. Please try again.";
            }
        }
    }
}

?>

<!DOCTYPE html>
<html>

<head>

    <title>Gauley Ko Pasal - Register</title>

    <link rel="stylesheet" href="../style.css">

</head>

<body>

<header>

    <h1>Gauley Ko Pasal</h1>

    <nav>

        <a href="index.php">Home</a>

        <a href="cart.php">Cart</a>

        <a href="login.php">Login</a>

    </nav>

</header>


<div class="container">

    <div class="hero">

        <h2>Create Your Account 🙏</h2>

        <p>Join Gauley Ko Pasal and start shopping.</p>

    </div>


    <div class="card">

        <?php if ($message != ""): ?>

            <p>
                <?php echo htmlspecialchars($message); ?>
            </p>

        <?php endif; ?>


        <form method="POST">

            <label>Name</label>

            <input
                type="text"
                name="name"
                required
            >

            <br><br>


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


            <label>Phone</label>

            <input
                type="text"
                name="phone"
            >

            <br><br>


            <label>Address</label>

            <textarea
                name="address"
                placeholder="Enter your address"
            ></textarea>

            <br><br>


            <button type="submit">
                Create Account
            </button>

        </form>


        <br>

        <p>
            Already have an account?
            <a href="login.php">Login here</a>
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