<?php
session_start();
require_once "../config/database.php";
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}
$user_id = (int) $_SESSION["user_id"];
$message = "";
$message_type = "";
if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["cancel_order"])
) {
    $order_id = (int) ($_POST["order_id"] ?? 0);
    $cancellation_reason =
        trim($_POST["cancellation_reason"] ?? "");
    if ($order_id <= 0) {
        $message = "Invalid order.";
        $message_type = "error";
    } elseif ($cancellation_reason === "") {
        $message =
            "Please provide a reason for cancelling your order.";
        $message_type = "error";
    } elseif (strlen($cancellation_reason) < 5) {
        $message =
            "Please provide a little more detail about why you want to cancel.";
        $message_type = "error";
    } else {
        
        $check_sql = "
            SELECT order_id, status
            FROM orders
            WHERE order_id = ?
            AND user_id = ?
            LIMIT 1
        ";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param(
            "ii",
            $order_id,
            $user_id
        );
        $check_stmt->execute();
        $check_result =
            $check_stmt->get_result();
        if ($check_result->num_rows === 0) {
            $message =
                "Order not found.";
            $message_type = "error";
        } else {
            $order = $check_result->fetch_assoc();
            $current_status =
                $order["status"];
            
            if (
                $current_status !== "Pending" &&
                $current_status !== "Processing"
            ) {
                $message =
                    "This order can no longer be cancelled.";
                $message_type = "error";
            } else {
                
                $cancel_sql = "
                    UPDATE orders
                    SET
                        status = 'Cancelled',
                        cancellation_reason = ?,
                        cancelled_at = CURRENT_TIMESTAMP
                    WHERE order_id = ?
                    AND user_id = ?
                ";
                $cancel_stmt =
                    $conn->prepare($cancel_sql);
                $cancel_stmt->bind_param(
                    "sii",
                    $cancellation_reason,
                    $order_id,
                    $user_id
                );
                if ($cancel_stmt->execute()) {
                    $message =
                        "Order #"
                        . $order_id
                        . " has been cancelled successfully.";
                    $message_type = "success";
                } else {
                    $message =
                        "Could not cancel the order. Please try again.";
                    $message_type = "error";
                }
            }
        }
    }
}
$orders = [];
$sql = "
    SELECT
        order_id,
        total_amount,
        status,
        order_date,
        delivery_latitude,
        delivery_longitude,
        cancellation_reason,
        cancelled_at
    FROM orders
    WHERE user_id = ?
    ORDER BY order_id DESC
";
$stmt = $conn->prepare($sql);
$stmt->bind_param(
    "i",
    $user_id
);
$stmt->execute();
$result =
    $stmt->get_result();
while ($order = $result->fetch_assoc()) {
    $order_id =
        (int) $order["order_id"];
    
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
    ";
    $item_stmt =
        $conn->prepare($item_sql);
    $item_stmt->bind_param(
        "i",
        $order_id
    );
    $item_stmt->execute();
    $item_result =
        $item_stmt->get_result();
    $items = [];
    while (
        $item =
        $item_result->fetch_assoc()
    ) {
        $items[] = $item;
    }
    $order["items"] = $items;
    $orders[] = $order;
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
        My Orders - Gauley Ko Pasal
    </title>
    <link
        rel="stylesheet"
        href="../css/customer.css"
    >
    <style>
        .orders-page {
            width: 92%;
            max-width: 1100px;
            margin: 0 auto;
            padding: 55px 0 80px;
        }
        .orders-header {
            margin-bottom: 30px;
        }
        .orders-eyebrow {
            color: #1976d2;
            font-size: 10px;
            font-weight: 900;
            letter-spacing: 2px;
        }
        .orders-header h1 {
            font-size: 38px;
            color: #102a43;
            margin: 8px 0;
        }
        .orders-header p {
            color: #718096;
            font-size: 14px;
        }
        
        .order-message {
            padding: 15px 18px;
            border-radius: 10px;
            margin-bottom: 25px;
            font-size: 13px;
            font-weight: 700;
        }
        .order-message.success {
            background: #e9f8ef;
            color: #16834b;
            border: 1px solid #c7ead6;
        }
        .order-message.error {
            background: #fff0f0;
            color: #d64545;
            border: 1px solid #f3cccc;
        }
        
        .empty-orders {
            background: white;
            border: 1px solid #e4e9ef;
            border-radius: 18px;
            padding: 70px 30px;
            text-align: center;
        }
        .empty-orders-icon {
            font-size: 55px;
            margin-bottom: 15px;
        }
        .empty-orders h2 {
            color: #102a43;
            margin-bottom: 10px;
        }
        .empty-orders p {
            color: #718096;
            margin-bottom: 25px;
        }
        
        .order-card {
            background: white;
            border: 1px solid #e4e9ef;
            border-radius: 18px;
            margin-bottom: 25px;
            overflow: hidden;
            box-shadow:
                0 8px 25px
                rgba(20,40,70,0.04);
        }
        .order-top {
            padding: 22px 25px;
            border-bottom:
                1px solid #edf1f5;
            display: flex;
            justify-content:
                space-between;
            align-items: center;
            gap: 20px;
        }
        .order-number {
            font-size: 14px;
            font-weight: 900;
            color: #102a43;
        }
        .order-date {
            color: #718096;
            font-size: 12px;
            margin-top: 5px;
        }
        
        .order-status {
            padding: 7px 12px;
            border-radius: 30px;
            font-size: 11px;
            font-weight: 900;
            white-space: nowrap;
        }
        .status-pending {
            background: #fff7df;
            color: #9a6700;
        }
        .status-processing {
            background: #eaf4ff;
            color: #1976d2;
        }
        .status-delivered {
            background: #e9f8ef;
            color: #16834b;
        }
        .status-cancelled {
            background: #fff0f0;
            color: #d64545;
        }
        
        .order-items {
            padding: 10px 25px;
        }
        .order-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px 0;
            border-bottom:
                1px solid #edf1f5;
        }
        .order-item:last-child {
            border-bottom: none;
        }
        .order-item-image {
            width: 65px;
            height: 65px;
            border-radius: 12px;
            background: #f4f7fa;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            flex-shrink: 0;
        }
        .order-item-image img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            padding: 7px;
        }
        .order-item-placeholder {
            font-size: 28px;
        }
        .order-item-info {
            flex: 1;
        }
        .order-item-info h3 {
            font-size: 14px;
            color: #172033;
            margin-bottom: 5px;
        }
        .order-item-info p {
            color: #718096;
            font-size: 12px;
        }
        .order-item-price {
            text-align: right;
            font-size: 13px;
            font-weight: 800;
            color: #102a43;
        }
        
        .order-bottom {
            padding: 20px 25px;
            background: #fafbfd;
            border-top:
                1px solid #edf1f5;
            display: flex;
            justify-content:
                space-between;
            align-items: center;
            gap: 20px;
        }
        .delivery-info {
            color: #718096;
            font-size: 12px;
        }
        .order-total {
            text-align: right;
        }
        .order-total-label {
            color: #718096;
            font-size: 11px;
        }
        .order-total-price {
            display: block;
            color: #102a43;
            font-size: 21px;
            font-weight: 900;
            margin-top: 3px;
        }
        
        .cancel-order-btn {
            background: #fff0f0;
            color: #d64545;
            border: 1px solid #f3cccc;
            padding: 10px 15px;
            border-radius: 9px;
            font-size: 12px;
            font-weight: 800;
            cursor: pointer;
        }
        .cancel-order-btn:hover {
            background: #d64545;
            color: white;
        }
        
        .cancel-box {
            display: none;
            padding: 20px 25px;
            background: #fffafa;
            border-top:
                1px solid #f1dddd;
        }
        .cancel-box.active {
            display: block;
        }
        .cancel-box h3 {
            color: #102a43;
            font-size: 15px;
            margin-bottom: 8px;
        }
        .cancel-box p {
            color: #718096;
            font-size: 12px;
            margin-bottom: 15px;
        }
        .cancel-box textarea {
            width: 100%;
            min-height: 90px;
            resize: vertical;
            border: 1px solid #dfe5ec;
            border-radius: 9px;
            padding: 12px;
            font-family: inherit;
            font-size: 13px;
            outline: none;
        }
        .cancel-box textarea:focus {
            border-color: #1976d2;
        }
        .cancel-actions {
            display: flex;
            gap: 10px;
            margin-top: 12px;
        }
        .confirm-cancel {
            background: #d64545;
            color: white;
            border: none;
            padding: 10px 16px;
            border-radius: 9px;
            font-size: 12px;
            font-weight: 800;
            cursor: pointer;
        }
        .cancel-close {
            background: #eef1f5;
            color: #102a43;
            border: none;
            padding: 10px 16px;
            border-radius: 9px;
            font-size: 12px;
            font-weight: 800;
            cursor: pointer;
        }
        
        .cancellation-display {
            margin-top: 15px;
            padding: 14px 16px;
            background: #fff5f5;
            border-radius: 10px;
            border-left: 3px solid #d64545;
        }
        .cancellation-display strong {
            display: block;
            color: #d64545;
            font-size: 11px;
            margin-bottom: 5px;
        }
        .cancellation-display p {
            color: #5d6672;
            font-size: 12px;
            margin: 0;
        }
        
        @media (max-width: 650px) {
            .orders-page {
                padding-top: 35px;
            }
            .orders-header h1 {
                font-size: 30px;
            }
            .order-top {
                align-items: flex-start;
                flex-direction: column;
            }
            .order-bottom {
                align-items: flex-start;
                flex-direction: column;
            }
            .order-total {
                text-align: left;
            }
            .order-item-price {
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
<main class="orders-page">
    <div class="orders-header">
        <span class="orders-eyebrow">
            YOUR ACCOUNT
        </span>
        <h1>
            My Orders
        </h1>
        <p>
            Track your purchases and manage your orders.
        </p>
    </div>
    <?php if ($message !== ""): ?>
        <div class="order-message <?php
            echo htmlspecialchars($message_type);
        ?>">
            <?php
            echo htmlspecialchars($message);
            ?>
        </div>
    <?php endif; ?>
    <?php if (empty($orders)): ?>
        <div class="empty-orders">
            <div class="empty-orders-icon">
                📦
            </div>
            <h2>
                No orders yet
            </h2>
            <p>
                You haven't placed an order yet.
                Start shopping and your orders will appear here.
            </p>
            <a
                href="products.php"
                class="shop-button"
            >
                Start Shopping →
            </a>
        </div>
    <?php else: ?>
        <?php foreach ($orders as $order): ?>
            <?php
            $order_id =
                (int) $order["order_id"];
            $status =
                $order["status"]
                ?? "Pending";
            $status_class =
                "status-" .
                strtolower($status);
            ?>
            <article class="order-card">
                
                <div class="order-top">
                    <div>
                        <div class="order-number">
                            Order #
                            <?php
                            echo $order_id;
                            ?>
                        </div>
                        <div class="order-date">
                            Placed on
                            <?php
                            echo date(
                                "M d, Y • h:i A",
                                strtotime(
                                    $order["order_date"]
                                )
                            );
                            ?>
                        </div>
                    </div>
                    <span
                        class="order-status <?php
                            echo htmlspecialchars(
                                $status_class
                            );
                        ?>"
                    >
                        <?php
                        echo htmlspecialchars(
                            $status
                        );
                        ?>
                    </span>
                </div>
                
                <div class="order-items">
                    <?php foreach (
                        $order["items"]
                        as $item
                    ): ?>
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
                                $item["image"]
                                ?? ""
                            );
                        ?>
                        <div class="order-item">
                            <div class="order-item-image">
                                <?php if (
                                    $item_image !== "" &&
                                    file_exists(
                                        "../" .
                                        $item_image
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
                                    <div
                                        class="order-item-placeholder"
                                    >
                                        🛍️
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="order-item-info">
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
                            <div class="order-item-price">
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
                </div>
                
                <?php if (
                    $status === "Cancelled" &&
                    !empty(
                        $order["cancellation_reason"]
                    )
                ): ?>
                    <div class="cancellation-display">
                        <strong>
                            CANCELLATION REASON
                        </strong>
                        <p>
                            <?php
                            echo nl2br(
                                htmlspecialchars(
                                    $order[
                                        "cancellation_reason"
                                    ]
                                )
                            );
                            ?>
                        </p>
                    </div>
                <?php endif; ?>
                
                <?php if (
                    $status === "Pending" ||
                    $status === "Processing"
                ): ?>
                    <div
                        class="cancel-box"
                        id="cancel-box-<?php
                            echo $order_id;
                        ?>"
                    >
                        <h3>
                            Cancel Order #<?php
                            echo $order_id;
                            ?>?
                        </h3>
                        <p>
                            Please tell us why you want to cancel
                            this order. This reason will be visible
                            to the seller.
                        </p>
                        <form
                            method="POST"
                            action="my_orders.php"
                        >
                            <input
                                type="hidden"
                                name="order_id"
                                value="<?php
                                    echo $order_id;
                                ?>"
                            >
                            <textarea
                                name="cancellation_reason"
                                placeholder="Example: I ordered the wrong item..."
                                minlength="5"
                                required
                            ></textarea>
                            <div class="cancel-actions">
                                <button
                                    type="submit"
                                    name="cancel_order"
                                    class="confirm-cancel"
                                    onclick="
                                        return confirm(
                                            'Are you sure you want to cancel this order?'
                                        );
                                    "
                                >
                                    Confirm Cancellation
                                </button>
                                <button
                                    type="button"
                                    class="cancel-close"
                                    onclick="
                                        closeCancel(
                                            <?php
                                            echo $order_id;
                                            ?>
                                        );
                                    "
                                >
                                    Keep Order
                                </button>
                            </div>
                        </form>
                    </div>
                <?php endif; ?>
                
                <div class="order-bottom">
                    <div class="delivery-info">
                        <?php if (
                            $order[
                                "delivery_latitude"
                            ] !== null &&
                            $order[
                                "delivery_longitude"
                            ] !== null
                        ): ?>
                            📍 Delivery location saved
                        <?php else: ?>
                            📍 Delivery location unavailable
                        <?php endif; ?>
                        <?php if (
                            $status === "Pending" ||
                            $status === "Processing"
                        ): ?>
                            <br><br>
                            <button
                                type="button"
                                class="cancel-order-btn"
                                onclick="
                                    openCancel(
                                        <?php
                                        echo $order_id;
                                        ?>
                                    );
                                "
                            >
                                ✕ Cancel Order
                            </button>
                        <?php endif; ?>
                    </div>
                    <div class="order-total">
                        <span class="order-total-label">
                            ORDER TOTAL
                        </span>
                        <strong
                            class="order-total-price"
                        >
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
            </article>
        <?php endforeach; ?>
    <?php endif; ?>
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
<script>
function openCancel(orderId) {
    const box =
        document.getElementById(
            "cancel-box-" + orderId
        );
    if (box) {
        box.classList.add("active");
        box.scrollIntoView({
            behavior: "smooth",
            block: "center"
        });
    }
}
function closeCancel(orderId) {
    const box =
        document.getElementById(
            "cancel-box-" + orderId
        );
    if (box) {
        box.classList.remove("active");
    }
}
</script>
</body>
</html>

