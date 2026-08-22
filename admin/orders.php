
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

$message = "";

if (isset($_GET["success"]) && $_GET["success"] === "status_updated") {
    $message = "Order status updated successfully.";
}

if (isset($_GET["error"])) {
    if ($_GET["error"] === "invalid_order") {
        $message = "Invalid order.";
    } elseif ($_GET["error"] === "invalid_status") {
        $message = "Invalid order status.";
    } elseif ($_GET["error"] === "order_not_found") {
        $message = "Order not found.";
    } elseif ($_GET["error"] === "update_failed") {
        $message = "Could not update the order.";
    } elseif ($_GET["error"] === "database") {
        $message = "A database error occurred.";
    }
}


/* =========================
   ACTIVE ORDERS
   ONLY PENDING / PROCESSING
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
        WHERE orders.status IN ('Pending', 'Processing')
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

        <a href="categories.php">Categories</a>

        <a href="orders.php">Orders</a>

        <a href="order_history.php">Order History</a>

        <a href="users.php">Users</a>

        <a href="logout.php">Logout</a>

    </nav>

</header>


<div class="container">

    <div class="hero">

        <h2>Active Orders 🛒</h2>

        <p>
            Manage pending and processing customer orders.
        </p>

    </div>


    <?php if ($message !== ""): ?>

        <div class="card">

            <p>
                <?php echo htmlspecialchars($message); ?>
            </p>

        </div>

        <br>

    <?php endif; ?>


    <?php if (!$result || $result->num_rows === 0): ?>

        <div class="card">

            <h3>No active orders.</h3>

            <p>
                All current orders have been completed or cancelled.
            </p>

        </div>

    <?php else: ?>

        <?php while ($order = $result->fetch_assoc()): ?>

            <div class="card" style="margin-bottom: 20px;">

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

                    <strong>Current Status:</strong>

                    <?php
                    echo htmlspecialchars(
                        $order["status"]
                    );
                    ?>

                </p>


                <form
                    method="POST"
                    action="update_order.php"
                >

                    <input
                        type="hidden"
                        name="order_id"
                        value="<?php
                        echo (int) $order["order_id"];
                        ?>"
                    >

                    <label>
                        Change Status
                    </label>

                    <select
                        name="status"
                        required
                    >

                        <option
                            value="Pending"
                            <?php
                            if (
                                $order["status"] === "Pending"
                            ) {
                                echo "selected";
                            }
                            ?>
                        >
                            Pending
                        </option>

                        <option
                            value="Processing"
                            <?php
                            if (
                                $order["status"] === "Processing"
                            ) {
                                echo "selected";
                            }
                            ?>
                        >
                            Processing
                        </option>

                        <option value="Delivered">
                            Delivered
                        </option>

                        <option value="Cancelled">
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
```
