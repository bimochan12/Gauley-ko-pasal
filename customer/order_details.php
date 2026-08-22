<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

require_once "../config/database.php";

$user_id = $_SESSION["user_id"];

if (!isset($_GET["id"])) {
    header("Location: orders.php");
    exit();
}

$order_id = (int) $_GET["id"];


/* GET ORDER */

$sql = "SELECT
            orders.order_id,
            orders.total_amount,
            orders.status,
            orders.order_date,
            users.name,
            users.email
        FROM orders
        INNER JOIN users
            ON orders.user_id = users.user_id
        WHERE orders.order_id = ?
        AND orders.user_id = ?";

$stmt = $conn->prepare($sql);

$stmt->bind_param(
    "ii",
    $order_id,
    $user_id
);

$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows != 1) {
    header("Location: orders.php");
    exit();
}

$order = $result->fetch_assoc();


/* GET ORDER ITEMS */

$sql = "SELECT
            order_items.quantity,
            order_items.price,
            products.product_name,
            products.description
        FROM order_items
        INNER JOIN products
            ON order_items.product_id = products.product_id
        WHERE order_items.order_id = ?";

$stmt = $conn->prepare($sql);

$stmt->bind_param(
    "i",
    $order_id
);

$stmt->execute();

$items = $stmt->get_result();

?>

<!DOCTYPE html>
<html>

<head>

    <title>
        Gauley Ko Pasal - Order #<?php echo $order_id; ?>
    </title>

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

        <h2>
            Order #<?php echo $order_id; ?> 📦
        </h2>

        <p>
            Here are the details of your order.
        </p>

    </div>


    <!-- ORDER INFORMATION -->

    <div class="card">

        <h2>Order Information</h2>

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

    </div>


    <br>


    <!-- ORDER ITEMS -->

    <h2>Items Ordered</h2>


    <?php if ($items->num_rows == 0): ?>

        <div class="card">

            <p>
                No items found for this order.
            </p>

        </div>

    <?php else: ?>

        <?php while ($item = $items->fetch_assoc()): ?>

            <?php

            $subtotal =
                $item["price"] *
                $item["quantity"];

            ?>

            <div
                class="card"
                style="margin-bottom: 20px;"
            >

                <h3>

                    <?php
                    echo htmlspecialchars(
                        $item["product_name"]
                    );
                    ?>

                </h3>


                <p>

                    <?php
                    echo htmlspecialchars(
                        $item["description"]
                    );
                    ?>

                </p>


                <p>

                    <strong>Price:</strong>

                    Rs.
                    <?php
                    echo number_format(
                        $item["price"],
                        2
                    );
                    ?>

                </p>


                <p>

                    <strong>Quantity:</strong>

                    <?php
                    echo $item["quantity"];
                    ?>

                </p>


                <p>

                    <strong>Subtotal:</strong>

                    Rs.
                    <?php
                    echo number_format(
                        $subtotal,
                        2
                    );
                    ?>

                </p>

            </div>

        <?php endwhile; ?>

    <?php endif; ?>


    <!-- TOTAL -->

    <div class="card">

        <h2>

            Total:

            Rs.
            <?php
            echo number_format(
                $order["total_amount"],
                2
            );
            ?>

        </h2>

    </div>


    <br>

    <a
        class="button"
        href="orders.php"
    >
        ← Back to My Orders
    </a>

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