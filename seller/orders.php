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
$message = "";
$message_type = "info";
if (
    isset($_GET["success"]) &&
    $_GET["success"] === "status_updated"
) {
    $message =
        "Product order status updated successfully.";
    $message_type = "success";
}
if (isset($_GET["error"])) {
    if ($_GET["error"] === "invalid_item") {
        $message =
            "Invalid order item.";
    } elseif (
        $_GET["error"] === "invalid_status"
    ) {
        $message =
            "Invalid order status.";
    } elseif (
        $_GET["error"] === "not_your_product"
    ) {
        $message =
            "You do not have permission to manage this order.";
    } elseif (
        $_GET["error"] === "update_failed"
    ) {
        $message =
            "Could not update the product order status.";
    } elseif (
        $_GET["error"] === "database"
    ) {
        $message =
            "A database error occurred.";
    } elseif (
        $_GET["error"] === "delete_failed"
    ) {
        $message =
            "Could not delete the cancelled order.";
    }
    $message_type = "error";
}
$sql = "
    SELECT
        orders.order_id,
        orders.order_date,
        users.name AS customer_name,
        users.email AS customer_email,
        order_items.order_item_id,
        order_items.quantity,
        order_items.price,
        order_items.status AS item_status,
        products.product_name,
        products.image
    FROM order_items
    INNER JOIN orders
        ON order_items.order_id =
           orders.order_id
    INNER JOIN users
        ON orders.user_id =
           users.user_id
    INNER JOIN products
        ON order_items.product_id =
           products.product_id
    WHERE products.seller_id = ?
    AND (
        order_items.status = 'Pending'
        OR order_items.status = 'Processing'
    )
    ORDER BY
        orders.order_date DESC,
        order_items.order_item_id DESC
";
$stmt = $conn->prepare($sql);
$stmt->bind_param(
    "i",
    $seller_id
);
$stmt->execute();
$result =
    $stmt->get_result();
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
        Active Orders - Gauley Ko Pasal
    </title>
    <link
        rel="stylesheet"
        href="../style.css"
    >
    <style>
        
        .orders-page {
            width: 92%;
            max-width: 1200px;
            margin: 45px auto 80px;
        }
        
        .orders-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            gap: 20px;
            margin-bottom: 30px;
        }
        .orders-header-text h2 {
            margin: 0 0 8px;
            font-size: 30px;
            color: #102a43;
        }
        .orders-header-text p {
            margin: 0;
            color: #718096;
            font-size: 13px;
        }
        .history-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 11px 16px;
            border: 1px solid #dce3ea;
            border-radius: 9px;
            background: white;
            color: #102a43;
            text-decoration: none;
            font-size: 12px;
            font-weight: 800;
            white-space: nowrap;
            transition: 0.2s ease;
        }
        .history-button:hover {
            border-color: #1976d2;
            color: #1976d2;
        }
        
        .order-message {
            padding: 13px 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 12px;
            font-weight: 700;
        }
        .order-message.success {
            background: #eaf8ef;
            border: 1px solid #c9ecd7;
            color: #16834b;
        }
        .order-message.error {
            background: #fff1f1;
            border: 1px solid #ffd0d0;
            color: #b42318;
        }
        
        .orders-grid {
            display: grid;
            grid-template-columns:
                repeat(2, minmax(0, 1fr));
            gap: 18px;
        }
        
        .order-card {
            background: white;
            border: 1px solid #e4e9ef;
            border-radius: 15px;
            padding: 15px;
            display: flex;
            gap: 15px;
            min-width: 0;
            transition:
                transform 0.2s ease,
                box-shadow 0.2s ease,
                border-color 0.2s ease;
        }
        .order-card:hover {
            transform: translateY(-2px);
            border-color: #d5dee8;
            box-shadow:
                0 8px 22px rgba(
                    16,
                    42,
                    67,
                    0.08
                );
        }
        
        .order-image {
            width: 105px;
            height: 105px;
            flex: 0 0 105px;
            border-radius: 11px;
            background: #f5f7fa;
            border: 1px solid #edf0f3;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .order-image img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            padding: 8px;
        }
        .order-image-placeholder {
            font-size: 34px;
            opacity: 0.7;
        }
        
        .order-info {
            flex: 1;
            min-width: 0;
        }
        .order-number {
            font-size: 10px;
            color: #718096;
            font-weight: 800;
            margin-bottom: 4px;
        }
        .order-product-name {
            font-size: 16px;
            font-weight: 900;
            color: #102a43;
            margin-bottom: 5px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .order-price {
            font-size: 14px;
            font-weight: 900;
            color: #1976d2;
            margin-bottom: 7px;
        }
        .order-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 7px;
            margin-bottom: 7px;
        }
        .order-meta-item {
            background: #f5f7fa;
            color: #4a5568;
            border-radius: 5px;
            padding: 4px 7px;
            font-size: 10px;
            font-weight: 700;
        }
        .customer-info {
            font-size: 11px;
            color: #718096;
            line-height: 1.5;
            margin-bottom: 8px;
        }
        .customer-info strong {
            color: #4a5568;
        }
        
        .status-row {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 8px;
        }
        .status-label {
            font-size: 10px;
            font-weight: 800;
            color: #718096;
        }
        .status-badge {
            display: inline-flex;
            align-items: center;
            padding: 4px 8px;
            border-radius: 20px;
            font-size: 9px;
            font-weight: 900;
        }
        .status-pending {
            background: #fff7df;
            color: #9a6700;
        }
        .status-processing {
            background: #eaf4ff;
            color: #1976d2;
        }
        
        .order-update-form {
            display: flex;
            align-items: center;
            gap: 7px;
            flex-wrap: wrap;
        }
        .order-update-form select {
            padding: 7px 8px;
            border: 1px solid #dce3ea;
            border-radius: 7px;
            background: white;
            color: #102a43;
            font-size: 10px;
            font-weight: 700;
            outline: none;
        }
        .order-update-form select:focus {
            border-color: #1976d2;
        }
        .update-status-button {
            border: none;
            background: #102a43;
            color: white;
            padding: 7px 10px;
            border-radius: 7px;
            font-size: 10px;
            font-weight: 800;
            cursor: pointer;
        }
        .update-status-button:hover {
            background: #1976d2;
        }
        
        .empty-orders {
            background: white;
            border: 1px solid #e4e9ef;
            border-radius: 16px;
            padding: 65px 25px;
            text-align: center;
        }
        .empty-orders-icon {
            font-size: 50px;
            margin-bottom: 15px;
        }
        .empty-orders h3 {
            color: #102a43;
            margin-bottom: 8px;
        }
        .empty-orders p {
            color: #718096;
            font-size: 13px;
            margin-bottom: 20px;
        }
        
        @media (max-width: 850px) {
            .orders-grid {
                grid-template-columns: 1fr;
            }
        }
        @media (max-width: 600px) {
            .orders-page {
                width: 94%;
                margin-top: 30px;
            }
            .orders-header {
                flex-direction: column;
                align-items: stretch;
            }
            .history-button {
                width: 100%;
            }
            .order-card {
                padding: 13px;
                gap: 12px;
            }
            .order-image {
                width: 85px;
                height: 85px;
                flex-basis: 85px;
            }
            .order-product-name {
                font-size: 14px;
            }
            .customer-info {
                display: none;
            }
        }
        @media (max-width: 400px) {
            .order-image {
                width: 75px;
                height: 75px;
                flex-basis: 75px;
            }
        }
    </style>
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
<main class="orders-page">
    
    <div class="orders-header">
        <div class="orders-header-text">
            <h2>
                Active Orders 🛒
            </h2>
            <p>
                Manage orders for your products.
            </p>
        </div>
        <a
            href="order_history.php"
            class="history-button"
        >
            View Order History →
        </a>
    </div>
    
    <?php if ($message !== ""): ?>
        <div
            class="order-message <?php
                echo $message_type;
            ?>"
        >
            <?php
            echo htmlspecialchars(
                $message
            );
            ?>
        </div>
    <?php endif; ?>
    
    <?php if (
        !$result ||
        $result->num_rows === 0
    ): ?>
        <div class="empty-orders">
            <div class="empty-orders-icon">
                📦
            </div>
            <h3>
                No active orders.
            </h3>
            <p>
                New orders containing your products
                will appear here.
            </p>
            <a
                href="order_history.php"
                class="history-button"
            >
                View Order History
            </a>
        </div>
    <?php else: ?>
        <div class="orders-grid">
            <?php while (
                $item =
                $result->fetch_assoc()
            ): ?>
                <?php
                $order_id =
                    (int) $item["order_id"];
                $order_item_id =
                    (int)
                    $item["order_item_id"];
                $product_name =
                    $item["product_name"];
                $quantity =
                    (int)
                    $item["quantity"];
                $price =
                    (float)
                    $item["price"];
                $status =
                    $item["item_status"];
                $customer_name =
                    $item["customer_name"];
                $customer_email =
                    $item["customer_email"];
                $order_date =
                    $item["order_date"];
                $image =
                    trim(
                        $item["image"] ?? ""
                    );
                ?>
                <article class="order-card">
                    
                    <div class="order-image">
                        <?php if ($image !== ""): ?>
                            <img
                                src="../uploads/<?php
                                    echo htmlspecialchars(
                                        $image
                                    );
                                ?>"
                                alt="<?php
                                    echo htmlspecialchars(
                                        $product_name
                                    );
                                ?>"
                            >
                        <?php else: ?>
                            <div
                                class="order-image-placeholder"
                            >
                                📦
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="order-info">
                        <div class="order-number">
                            ORDER #
                            <?php
                            echo $order_id;
                            ?>
                        </div>
                        <div
                            class="order-product-name"
                            title="<?php
                                echo htmlspecialchars(
                                    $product_name
                                );
                            ?>"
                        >
                            <?php
                            echo htmlspecialchars(
                                $product_name
                            );
                            ?>
                        </div>
                        <div class="order-price">
                            Rs.
                            <?php
                            echo number_format(
                                $price,
                                2
                            );
                            ?>
                        </div>
                        <div class="order-meta">
                            <span
                                class="order-meta-item"
                            >
                                Qty:
                                <?php
                                echo $quantity;
                                ?>
                            </span>
                            <span
                                class="order-meta-item"
                            >
                                <?php
                                echo htmlspecialchars(
                                    date(
                                        "M d, Y",
                                        strtotime(
                                            $order_date
                                        )
                                    )
                                );
                                ?>
                            </span>
                        </div>
                        <div class="customer-info">
                            <strong>
                                Customer:
                            </strong>
                            <?php
                            echo htmlspecialchars(
                                $customer_name
                            );
                            ?>
                            <br>
                            <?php
                            echo htmlspecialchars(
                                $customer_email
                            );
                            ?>
                        </div>
                        
                        <div class="status-row">
                            <span class="status-label">
                                Status:
                            </span>
                            <span
                                class="status-badge <?php
                                    echo $status === "Pending"
                                        ? "status-pending"
                                        : "status-processing";
                                ?>"
                            >
                                <?php
                                echo htmlspecialchars(
                                    $status
                                );
                                ?>
                            </span>
                        </div>
                        
                        <form
                            method="POST"
                            action="update_order.php"
                            class="order-update-form"
                        >
                            <input
                                type="hidden"
                                name="order_item_id"
                                value="<?php
                                    echo $order_item_id;
                                ?>"
                            >
                            <select
                                name="status"
                                required
                            >
                                <option
                                    value="Pending"
                                    <?php
                                    if (
                                        $status === "Pending"
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
                                        $status === "Processing"
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
                            <button
                                type="submit"
                                class="update-status-button"
                            >
                                Update
                            </button>
                        </form>
                    </div>
                </article>
            <?php endwhile; ?>
        </div>
    <?php endif; ?>
</main>
<footer>
    <p>
        Gauley Ko Pasal — Seller Panel 🇳🇵
    </p>
</footer>
</body>
</html>

