<?php

require_once "../config/database.php";

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $name = $_POST["name"];
    $email = $_POST["email"];
    $password = $_POST["password"];
    $phone = $_POST["phone"];
    $address = $_POST["address"];

    $sql = "INSERT INTO users (name, email, password, phone, address)
            VALUES ('$name', '$email', '$password', '$phone', '$address')";

    if ($conn->query($sql) === TRUE) {

        $message = "Registration successful!";

    } else {

        $message = "Error: " . $conn->error;

    }

}

?>

<!DOCTYPE html>
<html>

<head>

    <title>Register - Gauley Ko Pasal</title>

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
            width: 400px;
            margin: 50px auto;
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
            margin: 8px 0;
            border: 1px solid #ccc;
            border-radius: 5px;
        }

        button {
            padding: 10px 25px;
            margin-top: 15px;
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
            margin: 15px;
            color: green;
        }

        .login-link {
            margin-top: 20px;
        }

        .login-link a {
            color: #222;
        }

    </style>

</head>

<body>


<nav>

    <a href="../index.php">Gauley Ko Pasal</a>

    <a href="login.php">Login</a>

</nav>


<div class="container">

    <h1>Create Account</h1>

    <p>Register for Gauley Ko Pasal</p>


    <?php

    if ($message != "") {

        echo '<p class="message">' . $message . '</p>';

    }

    ?>


    <form method="POST" action="register.php">


        <input
            type="text"
            name="name"
            placeholder="Enter your name"
            required
        >

        <br>


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


        <input
            type="text"
            name="phone"
            placeholder="Enter your phone number"
        >

        <br>


        <input
            type="text"
            name="address"
            placeholder="Enter your address"
        >

        <br>


        <button type="submit">
            Register
        </button>


    </form>


    <div class="login-link">

        <p>
            Already have an account?
            <a href="login.php">Login</a>
        </p>

    </div>

</div>


</body>

</html>