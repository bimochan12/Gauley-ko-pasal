
<?php

session_start();

if (
    !isset($_SESSION["user_id"]) ||
    !isset($_SESSION["role"]) ||
    $_SESSION["role"] !== "admin"
) {
    header("Location: login.php");
    exit();
}

require_once "../config/database.php";


/* =========================
   COUNT ACTIVE ORDERS
========================= */

$active_orders = 0;

$sql = "SELECT COUNT(*) AS total
        FROM orders
        WHERE status IN ('Pending', 'Processing')";

$result = $conn->query($sql);

if ($result) {

    $row = $result->fetch_assoc();

    $active_orders = (int) $row["total"];
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

    <h1>
        Gauley Ko Pasal
    </h1>


    <nav>

        <a href="dashboard.php">
            Dashboard
        </a>

        <a href="products.php">
            Products
        </a>

        <a href="categories.php">
            Categories
        </a>

        <a href="orders.php">
            Orders
        </a>

        <a href="order_history.php">
            Order History
        </a>

        <a href="users.php">
            Users
        </a>

        <a href="logout.php">
            Logout
        </a>

    </nav>

</header>


<div class="container">


    <!-- =========================
         WELCOME
    ========================== -->

    <div class="hero">

        <h2>
            Admin Dashboard
        </h2>

        <p>

            Welcome,

            <strong>
                <?php
                echo htmlspecialchars(
                    $_SESSION["name"]
                );
                ?>
            </strong>!

        </p>

        <p>

            You are logged in as:

            <strong>
                <?php
                echo htmlspecialchars(
                    $_SESSION["role"]
                );
                ?>
            </strong>

        </p>

    </div>


    <!-- =========================
         ADMIN OPTIONS
    ========================== -->

    <div class="products">


        <!-- PRODUCTS -->

        <div class="product-card">

            <h3>
                📦 Products
            </h3>

            <p>
                Add, edit and delete products.
            </p>

            <a
                class="button"
                href="products.php"
            >
                Manage Products
            </a>

        </div>


        <!-- ACTIVE ORDERS -->

        <div class="product-card">

            <h3>
                🛒 Active Orders

                <?php if ($active_orders > 0): ?>

                    <span>
                        🔴
                        <?php
                        echo $active_orders;
                        ?>
                    </span>

                <?php endif; ?>

            </h3>


            <p>

                <?php

                if ($active_orders > 0) {

                    echo
                        $active_orders .
                        " order(s) need your attention.";

                } else {

                    echo
                        "No pending or processing orders.";

                }

                ?>

            </p>


            <a
                class="button"
                href="orders.php"
            >
                Manage Orders
            </a>

        </div>


        <!-- ORDER HISTORY -->

        <div class="product-card">

            <h3>
                📋 Order History
            </h3>

            <p>
                View delivered and cancelled orders.
            </p>

            <a
                class="button"
                href="order_history.php"
            >
                View History
            </a>

        </div>


        <!-- USERS -->

        <div class="product-card">

            <h3>
                👥 Users
            </h3>

            <p>
                View registered customers and sellers.
            </p>

            <a
                class="button"
                href="users.php"
            >
                Manage Users
            </a>

        </div>


        <!-- CATEGORIES -->

        <div class="product-card">

            <h3>
                🗂️ Categories
            </h3>

            <p>
                Add, edit and manage product categories.
            </p>

            <a
                class="button"
                href="categories.php"
            >
                Manage Categories
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
```
