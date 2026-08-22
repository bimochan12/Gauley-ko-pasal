
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
   GET COMPLETED ORDERS
========================= */

$sql = "SELECT
            orders.order_id,
            orders.user_id,
            orders.total_amount,
            orders.status,
            orders.order_date,
            users.name,
            users.email
        FROM orders
        INNER JOIN users
            ON orders.user_id = users.user_id
        WHERE orders.status IN ('Delivered', 'Cancelled')
        ORDER BY orders.order_date DESC";

$result = $conn->query($sql);

?>

<!DOCTYPE html>
<html>

<head>

    <title>Gauley Ko Pasal - Order History</title>

    <link rel="stylesheet" href="../style.css">

</head>

<body>

<header>

    <h1>Gauley Ko Pasal</h1>

    <nav>

        <a href="dashboard.php">Dashboard</a>
        <a href="products.php">Products</a>
        <a href="categories.php">Categories</a>
        <a href="orders.php">Orders</a>
        <a href="order_history.php">Order History</a>
        <a href="users.php">Users</a>
        <a href="logout.php">Logout</a>

    </nav>

</header>


<div class="container">

    <div class="hero">

        <h2>Order History 📋</h2>

        <p>
            View delivered and cancelled customer orders.
        </p>

    </div>


    <?php if (!$result || $result->num_rows === 0): ?>

        <div class="card">

            <h3>
                No order history yet.
            </h3>

            <p>
                Delivered and cancelled orders will appear here.
            </p>

        </div>

    <?php else: ?>

        <?php while ($order = $result->fetch_assoc()): ?>

            <div
                class="card"
                style="margin-bottom: 20px;"
            >

                <h2>
                    Order #
                    <?php echo (int) $order["order_id"]; ?>
                </h2>

                <p>
                    <strong>Customer:</strong>
                    <?php
                    echo htmlspecialchars(
                        $order["name"]
                    );
                    ?>
                </p>

                <p>
                    <strong>Email:</strong>
                    <?php
                    echo htmlspecialchars(
                        $order["email"]
                    );
                    ?>
                </p>

                <p>
                    <strong>Total:</strong>
                    Rs.
                    <?php
                    echo number_format(
                        (float) $order["total_amount"],
                        2
                    );
                    ?>
                </p>

                <p>
                    <strong>Order Date:</strong>
                    <?php
                    echo htmlspecialchars(
                        $order["order_date"]
                    );
                    ?>
                </p>

                <p>
                    <strong>Status:</strong>
                    <?php
                    echo htmlspecialchars(
                        $order["status"]
                    );
                    ?>
                </p>

            </div>

        <?php endwhile; ?>

    <?php endif; ?>

</div>


<footer>

    <p>
        Gauley Ko Pasal — Admin Panel 🇳🇵
    </p>

</footer>

</body>

</html>
```
