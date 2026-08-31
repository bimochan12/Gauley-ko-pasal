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

$seller_id = $_SESSION["user_id"];

if (!isset($_GET["id"])) {
    header("Location: orders.php");
    exit();
}

$order_id = (int) $_GET["id"];

$check_sql = "SELECT DISTINCT orders.order_id
              FROM orders
              INNER JOIN order_items
                  ON orders.order_id = order_items.order_id
              INNER JOIN products
                  ON order_items.product_id = products.product_id
              WHERE orders.order_id = ?
              AND products.seller_id = ?";

$check_stmt = $conn->prepare($check_sql);
$check_stmt->bind_param(
    "ii",
    $order_id,
    $seller_id
);
$check_stmt->execute();

$check_result = $check_stmt->get_result();

if ($check_result->num_rows !== 1) {
    header("Location: orders.php");
    exit();
}

$sql = "SELECT
            order_items.quantity,
            order_items.price,
            products.product_name,
            products.image
        FROM order_items
        INNER JOIN products
            ON order_items.product_id = products.product_id
        WHERE order_items.order_id = ?
        AND products.seller_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param(
    "ii",
    $order_id,
    $seller_id
);
$stmt->execute();

$result = $stmt->get_result();

?>

<!DOCTYPE html>
<html>

<head>

    <title>Order Details - Gauley Ko Pasal</title>

    <link rel="stylesheet" href="../style.css">

</head>

<body>

<header>

    <h1>Gauley Ko Pasal</h1>

    <nav>

        <a href="dashboard.php">Dashboard</a>
        <a href="products.php">My Products</a>
        <a href="orders.php">Orders</a>
        <a href="logout.php">Logout</a>

    </nav>

</header>

<div class="container">

    <div class="hero">

        <h2>
            Order #<?php echo $order_id; ?> Items
        </h2>

        <p>
            These are the products from this order that belong to you.
        </p>

    </div>

    <div class="products">

        <?php while ($item = $result->fetch_assoc()): ?>

            <div class="product-card">

                <?php if (!empty($item["image"])): ?>

                    <img
                        src="../uploads/<?php echo htmlspecialchars($item["image"]); ?>"
                        alt="<?php echo htmlspecialchars($item["product_name"]); ?>"
                        style="
                            width: 100%;
                            max-height: 200px;
                            object-fit: cover;
                        "
                    >

                <?php endif; ?>

                <h3>
                    <?php
                    echo htmlspecialchars(
                        $item["product_name"]
                    );
                    ?>
                </h3>

                <p>

                    <strong>Quantity:</strong>

                    <?php
                    echo htmlspecialchars(
                        $item["quantity"]
                    );
                    ?>

                </p>

                <p>

                    <strong>Price per item:</strong>

                    Rs.
                    <?php
                    echo number_format(
                        $item["price"],
                        2
                    );
                    ?>

                </p>

                <p>

                    <strong>Subtotal:</strong>

                    Rs.
                    <?php
                    echo number_format(
                        $item["price"] * $item["quantity"],
                        2
                    );
                    ?>

                </p>

            </div>

        <?php endwhile; ?>

    </div>

    <br>

    <a
        class="button"
        href="orders.php"
    >
        ← Back to Orders
    </a>

</div>

<footer>

    <p>
        Gauley Ko Pasal — Seller Panel 🇳🇵
    </p>

</footer>

</body>

</html>
