<?php

session_start();

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] != "admin") {
    header("Location: login.php");
    exit();
}

?>

<!DOCTYPE html>
<html>

<head>

    <title>Gauley Ko Pasal - Admin Dashboard</title>

    <link rel="stylesheet" href="../style.css">

</head>

<body>

<header>

    <h1>Gauley Ko Pasal</h1>

    <nav>

        <a href="dashboard.php">Dashboard</a>
        <a href="products.php">Products</a>
        <a href="orders.php">Orders</a>
        <a href="logout.php">Logout</a>

    </nav>

</header>

<div class="container">

    <div class="hero">

        <h2>Admin Dashboard</h2>

        <p>
            Welcome,
            <strong>
                <?php echo htmlspecialchars($_SESSION["name"]); ?>
            </strong>!
        </p>

        <p>
            You are logged in as:
            <strong>
                <?php echo htmlspecialchars($_SESSION["role"]); ?>
            </strong>
        </p>

    </div>

    <div class="products">

        <div class="product-card">

            <h3>📦 Products</h3>

            <p>
                Add, edit and delete products.
            </p>

            <a class="button" href="products.php">
                Manage Products
            </a>

        </div>

        <div class="product-card">

            <h3>🛒 Orders</h3>

            <p>
                View customer orders and update their status.
            </p>

            <a class="button" href="orders.php">
                Manage Orders
            </a>

        </div>

    </div>

</div>

<footer>

    <p>
        Gauley Ko Pasal — Admin Panel 🇳🇵
    </p>

</footer>

</body>

</html>