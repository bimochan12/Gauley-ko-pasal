<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

require_once "../config/database.php";

$user_id = $_SESSION["user_id"];

$sql = "SELECT
            order_id,
            total_amount,
            status,
            order_date
        FROM orders
        WHERE user_id = ?
        ORDER BY order_date DESC";

$stmt = $conn->prepare($sql);

$stmt->bind_param(
    "i",
    $user_id
);

$stmt->execute();

$result = $stmt->get_result();

?>

<!DOCTYPE html>
<html>

<head>

    <title>Gauley Ko Pasal - My Orders</title>

    <link rel="stylesheet" href="../style.css">

</head>

<body>

<header>

    <h1>Gauley Ko Pasal</h1>

    <nav>

        <a href="index.php">Home</a>

        <a href="cart.php">Cart</a>

        <a href="orders.php">My Orders</a>

        <a href="logout.php">Logout</a>

    </nav>

</header>


<div class="container">

    <div class="hero">

        <h2>My Orders 📦</h2>

        <p>
            View and track your orders.
        </p>

    </div>


    <?php if ($result->num_rows == 0): ?>

        <div class="card">

            <h3>You haven't placed any orders yet.</h3>

            <p>
                Visit our shop and place your first order.
            </p>

            <a
                class="button"
                href="index.php"
            >
                Start Shopping
            </a>

        </div>

    <?php else: ?>

        <?php while ($order = $result->fetch_assoc()): ?>

            <div
                class="card"
                style="margin-bottom: 20px;"
            >

                <h2>

                    Order #<?php
                    echo $order["order_id"];
                    ?>

                </h2>


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

                    <strong>Status:</strong>

                    <?php
                    echo htmlspecialchars(
                        $order["status"]
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


                <a
                    class="button"
                    href="order_details.php?id=<?php
                    echo $order["order_id"];
                    ?>"
                >
                    View Order Details
                </a>

            </div>

        <?php endwhile; ?>

    <?php endif; ?>

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