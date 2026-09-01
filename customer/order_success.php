<?php
session_start();
require_once "../config/database.php";
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}
$user_id = (int) $_SESSION["user_id"];
$order_id = (int) ($_GET["order_id"] ?? 0);
if ($order_id <= 0) {
    header("Location: my_orders.php");
    exit;
}
$sql = "
    SELECT
        o.order_id,
        o.user_id,
        o.total_amount,
        o.status,
        o.order_date,
        o.delivery_latitude,
        o.delivery_longitude,
        o.cancellation_reason,
        o.cancellation_note,
        o.cancelled_at
    FROM orders o
    WHERE o.order_id = ?
    AND o.user_id = ?
    LIMIT 1
";
$stmt = $conn->prepare($sql);
$stmt->bind_param(
    "ii",
    $order_id,
    $user_id
);
$stmt->execute();
$result = $stmt->get_result();
$order = $result->fetch_assoc();
if (!$order) {
    header("Location: my_orders.php");
    exit;
}
$item_sql = "
    SELECT
        oi.product_id,
        oi.quantity,
        oi.price,
        p.product_name,
        p.image
    FROM order_items oi
    LEFT JOIN products p
        ON oi.product_id = p.product_id
    WHERE oi.order_id = ?
    ORDER BY oi.order_item_id ASC
";
$item_stmt = $conn->prepare($item_sql);
$item_stmt->bind_param(
    "i",
    $order_id
);
$item_stmt->execute();
$item_result = $item_stmt->get_result();
$items = [];
while ($item = $item_result->fetch_assoc()) {
    $items[] = $item;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >
    <title>
        Order Placed - Gauley Ko Pasal
    </title>
    <link
        rel="stylesheet"
        href="../css/customer.css"
    >
    <style>
        .success-page {
            width: 92%;
            max-width: 950px;
            margin: auto;
            padding: 60px 0 80px;
        }
        .success-box {
            background: white;
            border: 1px solid #e4e9ef;
            border-radius: 20px;
            padding: 45px;
            text-align: center;
            box-shadow:
                0 10px 35px
                rgba(20, 40, 70, 0.06);
        }
        .success-icon {
            width: 75px;
            height: 75px;
            margin: 0 auto 20px;
            border-radius: 50%;
            background: #e9f8ef;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 38px;
        }
        .success-box h1 {
            color: #102a43;
            font-size: 34px;
            margin-bottom: 10px;
        }
        .success-box > p {
            color: #718096;
            font-size: 14px;
            line-height: 1.6;
        }
        .order-number {
            margin-top: 20px;
            font-size: 14px;
            font-weight: 800;
            color: #1976d2;
        }
        .success-actions {
            display: flex;
            justify-content: center;
            gap: 12px;
            margin-top: 28px;
            flex-wrap: wrap;
        }
        .success-button {
            display: inline-block;
            padding: 13px 20px;
            border-radius: 9px;
            font-size: 12px;
            font-weight: 800;
            background: #102a43;
            color: white;
        }
        .success-button:hover {
            background: #1976d2;
        }
        .success-button.secondary {
            background: #eef2f6;
            color: #102a43;
        }
        .success-button.secondary:hover {
            background: #dfe7ef;
        }
        .order-details {
            margin-top: 30px;
            text-align: left;
            background: #fafbfd;
            border: 1px solid #edf1f5;
            border-radius: 15px;
            padding: 25px;
        }
        .order-details h2 {
            color: #102a43;
            font-size: 19px;
            margin-bottom: 20px;
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            gap: 20px;
            padding: 10px 0;
            border-bottom: 1px solid #edf1f5;
            font-size: 13px;
        }
        .detail-row:last-child {
            border-bottom: none;
        }
        .detail-label {
            color: #718096;
        }
        .detail-value {
            color: #102a43;
            font-weight: 700;
            text-align: right;
        }
        .items-section {
            margin-top: 30px;
            text-align: left;
        }
        .items-section h2 {
            color: #102a43;
            font-size: 19px;
            margin-bottom: 15px;
        }
        .order-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px 0;
            border-bottom: 1px solid #edf1f5;
        }
        .order-item:last-child {
            border-bottom: none;
        }
        .item-image {
            width: 60px;
            height: 60px;
            background: #f4f7fa;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            flex-shrink: 0;
        }
        .item-image img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            padding: 6px;
        }
        .item-placeholder {
            font-size: 25px;
        }
        .item-info {
            flex: 1;
        }
        .item-info h3 {
            font-size: 14px;
            color: #172033;
            margin-bottom: 5px;
        }
        .item-info p {
            color: #718096;
            font-size: 12px;
        }
        .item-total {
            font-size: 13px;
            font-weight: 800;
            color: #102a43;
        }
        .total-box {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 2px solid #e4e9ef;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .total-box span {
            color: #718096;
            font-size: 13px;
            font-weight: 700;
        }
        .total-box strong {
            color: #102a43;
            font-size: 23px;
        }
        @media (max-width: 600px) {
            .success-page {
                padding-top: 35px;
            }
            .success-box {
                padding: 30px 20px;
            }
            .success-box h1 {
                font-size: 27px;
            }
            .detail-row {
                flex-direction: column;
                gap: 4px;
            }
            .detail-value {
                text-align: left;
            }
            .order-item {
                align-items: flex-start;
            }
            .item-total {
                font-size: 12px;
            }
        }
    </style>
</head>
<body>
<div class="topbar">
    🇳🇵 Local shopping made simple
</div>
<header class="navbar">
    <div class="nav-inner">
        <a
            href="index.php"
            class="logo"
        >
            Gauley Ko <span>Pasal</span>
        </a>
        <form
            class="search"
            method="GET"
            action="products.php"
        >
            <input
                type="text"
                name="search"
                placeholder="Search products..."
            >
            <button type="submit">
                🔍
            </button>
        </form>
        <nav class="nav-links">
            <a href="index.php">
                Home
            </a>
            <a href="products.php">
                Products
            </a>
            <a href="cart.php">
                🛒 Cart
            </a>
            <a href="my_orders.php">
                My Orders
            </a>
            <a href="logout.php">
                Logout
            </a>
        </nav>
    </div>
</header>
<main class="success-page">
    <div class="success-box">
        <div class="success-icon">
            ✓
        </div>
        <h1>
            Order Placed Successfully!
        </h1>
        <p>
            Thank you for shopping with
            <strong>Gauley Ko Pasal</strong>.
            Your order has been received and
            is now being processed.
        </p>
        <div class="order-number">
            Order #
            <?php
            echo $order_id;
            ?>
        </div>
        
        <div class="success-actions">
            <a
                href="my_orders.php"
                class="success-button"
            >
                View My Orders →
            </a>
            <a
                href="products.php"
                class="success-button secondary"
            >
                Continue Shopping
            </a>
        </div>
        
        <div class="order-details">
            <h2>
                Order Details
            </h2>
            <div class="detail-row">
                <span class="detail-label">
                    Order Number
                </span>
                <span class="detail-value">
                    #<?php
                    echo $order_id;
                    ?>
                </span>
            </div>
            <div class="detail-row">
                <span class="detail-label">
                    Order Date
                </span>
                <span class="detail-value">
                    <?php
                    echo date(
                        "M d, Y • h:i A",
                        strtotime(
                            $order["order_date"]
                        )
                    );
                    ?>
                </span>
            </div>
            <div class="detail-row">
                <span class="detail-label">
                    Status
                </span>
                <span class="detail-value">
                    <?php
                    echo htmlspecialchars(
                        $order["status"]
                    );
                    ?>
                </span>
            </div>
            <div class="detail-row">
                <span class="detail-label">
                    Delivery Location
                </span>
                <span class="detail-value">
                    <?php if (
                        $order["delivery_latitude"] !== null &&
                        $order["delivery_longitude"] !== null
                    ): ?>
                        📍 Location saved
                    <?php else: ?>
                        Not available
                    <?php endif; ?>
                </span>
            </div>
        </div>
        
        <div class="items-section">
            <h2>
                Items Ordered
            </h2>
            <?php foreach ($items as $item): ?>
                <?php
                $item_name =
                    $item["product_name"]
                    ?? "Product";
                $item_price =
                    (float)
                    ($item["price"] ?? 0);
                $item_quantity =
                    (int)
                    ($item["quantity"] ?? 0);
                $item_image =
                    trim(
                        $item["image"] ?? ""
                    );
                ?>
                <div class="order-item">
                    <div class="item-image">
                        <?php if (
                            $item_image !== "" &&
                            file_exists(
                                "../" . $item_image
                            )
                        ): ?>
                            <img
                                src="<?php
                                    echo htmlspecialchars(
                                        "../" .
                                        $item_image
                                    );
                                ?>"
                                alt="<?php
                                    echo htmlspecialchars(
                                        $item_name
                                    );
                                ?>"
                            >
                        <?php else: ?>
                            <div class="item-placeholder">
                                🛍️
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="item-info">
                        <h3>
                            <?php
                            echo htmlspecialchars(
                                $item_name
                            );
                            ?>
                        </h3>
                        <p>
                            Rs.
                            <?php
                            echo number_format(
                                $item_price,
                                2
                            );
                            ?>
                            ×
                            <?php
                            echo $item_quantity;
                            ?>
                        </p>
                    </div>
                    <div class="item-total">
                        Rs.
                        <?php
                        echo number_format(
                            $item_price *
                            $item_quantity,
                            2
                        );
                        ?>
                    </div>
                </div>
            <?php endforeach; ?>
            
            <div class="total-box">
                <span>
                    ORDER TOTAL
                </span>
                <strong>
                    Rs.
                    <?php
                    echo number_format(
                        (float)
                        $order["total_amount"],
                        2
                    );
                    ?>
                </strong>
            </div>
        </div>
    </div>
</main>
<footer>
    <div class="footer-inner">
        <div>
            <h3>
                Gauley Ko Pasal
            </h3>
            <p>
                Your local online marketplace
                for everyday products.
            </p>
        </div>
        <div>
            <h4>
                Quick Links
            </h4>
            <a href="index.php">
                Home
            </a>
            <a href="products.php">
                Products
            </a>
            <a href="cart.php">
                Cart
            </a>
        </div>
        <div>
            <h4>
                Account
            </h4>
            <a href="my_orders.php">
                My Orders
            </a>
            <a href="logout.php">
                Logout
            </a>
        </div>
    </div>
    <div class="footer-bottom">
        © 2026 Gauley Ko Pasal.
        All rights reserved.
    </div>
</footer>
</body>
</html>

