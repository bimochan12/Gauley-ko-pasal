
<?php

session_start();

if (
    !isset($_SESSION["user_id"]) ||
    !isset($_SESSION["role"]) ||
    $_SESSION["role"] !== "seller"
) {
    header("Location: login.php");
    exit();
}

require_once "../config/database.php";

$seller_id = (int) $_SESSION["user_id"];


/* =========================
   TOTAL PRODUCTS
========================= */

$product_stmt = $conn->prepare(
    "SELECT COUNT(*) AS total
     FROM products
     WHERE seller_id = ?"
);

$product_stmt->bind_param(
    "i",
    $seller_id
);

$product_stmt->execute();

$product_result =
    $product_stmt->get_result();

$total_products =
    (int) $product_result
    ->fetch_assoc()["total"];


/* =========================
   ACTIVE ORDER ITEMS
========================= */

$active_stmt = $conn->prepare(
    "SELECT COUNT(*) AS total
     FROM order_items

     INNER JOIN products
        ON order_items.product_id =
           products.product_id

     WHERE products.seller_id = ?
     AND order_items.status IN
        ('Pending', 'Processing')"
);

$active_stmt->bind_param(
    "i",
    $seller_id
);

$active_stmt->execute();

$active_result =
    $active_stmt->get_result();

$active_orders =
    (int) $active_result
    ->fetch_assoc()["total"];


/* =========================
   DELIVERED ORDER ITEMS
========================= */

$delivered_stmt = $conn->prepare(
    "SELECT COUNT(*) AS total
     FROM order_items

     INNER JOIN products
        ON order_items.product_id =
           products.product_id

     WHERE products.seller_id = ?
     AND order_items.status = 'Delivered'"
);

$delivered_stmt->bind_param(
    "i",
    $seller_id
);

$delivered_stmt->execute();

$delivered_result =
    $delivered_stmt->get_result();

$delivered_orders =
    (int) $delivered_result
    ->fetch_assoc()["total"];


/* =========================
   TOTAL EARNINGS
   FROM DELIVERED PRODUCTS
========================= */

$earnings_stmt = $conn->prepare(
    "SELECT
        COALESCE(
            SUM(
                order_items.price *
                order_items.quantity
            ),
            0
        ) AS total_earnings

     FROM order_items

     INNER JOIN products
        ON order_items.product_id =
           products.product_id

     WHERE products.seller_id = ?
     AND order_items.status = 'Delivered'"
);

$earnings_stmt->bind_param(
    "i",
    $seller_id
);

$earnings_stmt->execute();

$earnings_result =
    $earnings_stmt->get_result();

$total_earnings =
    (float) $earnings_result
    ->fetch_assoc()["total_earnings"];

?>

<!DOCTYPE html>

<html>

<head>

    <title>
        Seller Dashboard - Gauley Ko Pasal
    </title>

    <link
        rel="stylesheet"
        href="../style.css"
    >

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
            My Products
        </a>

        <a href="orders.php">
            Active Orders
        </a>

        <a href="order_history.php">
            Order History
        </a>

        <a href="logout.php">
            Logout
        </a>

    </nav>

</header>


<div class="container">


    <!-- WELCOME -->

    <div class="hero">

        <h2>
            Seller Dashboard 🏪
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
            Manage your products and customer
            orders from here.
        </p>

    </div>


    <!-- STATISTICS -->

    <div class="products">


        <!-- TOTAL PRODUCTS -->

        <div class="product-card">

            <h3>
                📦 Total Products
            </h3>

            <h2>

                <?php
                echo $total_products;
                ?>

            </h2>

            <p>
                Products currently listed
                in your store.
            </p>

        </div>


        <!-- ACTIVE ORDERS -->

        <div class="product-card">

            <h3>
                🛒 Active Orders
            </h3>

            <h2>

                <?php
                echo $active_orders;
                ?>

            </h2>

            <p>
                Product orders waiting to
                be processed or delivered.
            </p>

        </div>


        <!-- DELIVERED -->

        <div class="product-card">

            <h3>
                ✅ Delivered Items
            </h3>

            <h2>

                <?php
                echo $delivered_orders;
                ?>

            </h2>

            <p>
                Product orders successfully
                delivered.
            </p>

        </div>


        <!-- EARNINGS -->

        <div class="product-card">

            <h3>
                💰 Total Earnings
            </h3>

            <h2>

                Rs.

                <?php
                echo number_format(
                    $total_earnings,
                    2
                );
                ?>

            </h2>

            <p>
                Earnings from delivered
                product orders.
            </p>

        </div>


    </div>


    <br>


    <!-- QUICK ACTIONS -->

    <div class="products">


        <!-- PRODUCTS -->

        <div class="product-card">

            <h3>
                📦 My Products
            </h3>

            <p>
                Add, edit and manage
                your products.
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
            </h3>

            <p>
                Process and manage
                customer product orders.
            </p>

            <a
                class="button"
                href="orders.php"
            >
                View Active Orders
            </a>

        </div>


        <!-- HISTORY -->

        <div class="product-card">

            <h3>
                📋 Order History
            </h3>

            <p>
                View delivered and
                cancelled product orders.
            </p>

            <a
                class="button"
                href="order_history.php"
            >
                View Order History
            </a>

        </div>


    </div>


</div>


<footer>

    <p>
        Gauley Ko Pasal —
        Seller Panel 🇳🇵
    </p>

</footer>


</body>

</html>
```
