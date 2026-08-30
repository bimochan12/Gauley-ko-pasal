<?php

session_start();

require_once "../config/database.php";

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $email = $_POST["email"];
    $password = $_POST["password"];

    $sql = "SELECT * FROM users WHERE email = '$email'";

    $result = $conn->query($sql);

    if ($result && $result->num_rows == 1) {

        $user = $result->fetch_assoc();

        /*
         * Your original registration system may be storing
         * the password differently, so first check the
         * stored password.
         */

        if ($password == $user["password"]) {

            $_SESSION["user_id"] = $user["user_id"];
            $_SESSION["name"] = $user["name"];

            header("Location: index.php");
            exit();

        } else {

            $message = "Invalid email or password.";

        }

    } else {

        $message = "Invalid email or password.";

    }

}

?>

<!DOCTYPE html>
<html>

<head>

    <title>Login - Gauley Ko Pasal</title>

    <style>

        body {
            font-family: Arial, sans-serif;
            margin: 0;
            background-color: #f5f5f5;
        }

        nav {
            background-color: #222;
            padding: 20px;
        }

        nav a {
            color: white;
            text-decoration: none;
            margin-right: 20px;
        }

        .container {
            width: 350px;
            margin: 70px auto;
            background-color: white;
            padding: 30px;
            border-radius: 8px;
            text-align: center;
        }

        h1 {
            margin-bottom: 10px;
        }

        input {
            width: 90%;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #ccc;
            border-radius: 5px;
        }

        button {
            padding: 10px 25px;
            margin-top: 10px;
            background-color: #222;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        button:hover {
            background-color: #444;
        }

        .message {
            color: red;
            margin-bottom: 15px;
        }

        .register-link {
            margin-top: 20px;
        }

        .register-link a {
            color: #222;
        }

    </style>

</head>

<body>

<nav>

    <a href="index.php">Gauley Ko Pasal</a>

    <a href="register.php">Register</a>

</nav>


<div class="container">

    <h1>Login</h1>

    <p>Login to Gauley Ko Pasal</p>


    <?php

    if ($message != "") {

        echo '<p class="message">' . $message . '</p>';

    }

    ?>


    <form method="POST" action="login.php">

        <input
            type="email"
            name="email"
            placeholder="Enter your email"
            required
        >

        <br>

        <input
            type="password"
            name="password"
            placeholder="Enter your password"
            required
        >

        <br>

        <button type="submit">
            Login
        </button>

    </form>


    <div class="register-link">

        <p>
            Don't have an account?
            <a href="register.php">Register</a>
        </p>

    </div>

</div>

</body>

</html>