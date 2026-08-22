<?php

session_start();

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] != "admin") {
    header("Location: login.php");
    exit();
}

require_once "../config/database.php";

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
        ORDER BY orders.order_date DESC";

$result = $conn->query($sql);

?>

<!DOCTYPE html>
<html>

<head>

    <title>Gauley Ko Pasal - Orders</title>

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

        <h2>Manage Orders 🛒</h2>

        <p>
            View customer orders and update their status.
        </p>

    </div>


    <?php if ($result->num_rows == 0): ?>

        <div class="card">

            <h3>No orders yet.</h3>

            <p>
                Customer orders will appear here.
            </p>

        </div>

    <?php else: ?>

        <?php while ($order = $result->fetch_assoc()): ?>

            <div class="card" style="margin-bottom: 20px;">

                <h2>
                    Order #<?php echo $order["order_id"]; ?>
                </h2>

                <p>
                    <strong>Customer:</strong>
                    <?php echo htmlspecialchars($order["name"]); ?>
                </p>

                <p>
                    <strong>Email:</strong>
                    <?php echo htmlspecialchars($order["email"]); ?>
                </p>

                <p>
                    <strong>Total:</strong>
                    Rs.
                    <?php
                    echo number_format(
                        $order["total_amount"],
                        2
                    );
                    ?>
                </p>

                <p>
                    <strong>Order Date:</strong>
                    <?php echo $order["order_date"]; ?>
                </p>


                <form
                    method="POST"
                    action="update_order.php"
                >

                    <input
                        type="hidden"
                        name="order_id"
                        value="<?php echo $order["order_id"]; ?>"
                    >


                    <label>
                        Order Status
                    </label>

                    <select name="status">

                        <option
                            value="Pending"
                            <?php
                            if ($order["status"] == "Pending") {
                                echo "selected";
                            }
                            ?>
                        >
                            Pending
                        </option>

                        <option
                            value="Processing"
                            <?php
                            if ($order["status"] == "Processing") {
                                echo "selected";
                            }
                            ?>
                        >
                            Processing
                        </option>

                        <option
                            value="Delivered"
                            <?php
                            if ($order["status"] == "Delivered") {
                                echo "selected";
                            }
                            ?>
                        >
                            Delivered
                        </option>

                        <option
                            value="Cancelled"
                            <?php
                            if ($order["status"] == "Cancelled") {
                                echo "selected";
                            }
                            ?>
                        >
                            Cancelled
                        </option>

                    </select>

                    <br><br>

                    <button type="submit">
                        Update Order
                    </button>

                </form>

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