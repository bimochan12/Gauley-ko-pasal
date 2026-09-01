<?php
session_start();
require_once "../config/database.php";
if (
    !isset($_SESSION["user_id"]) ||
    !isset($_SESSION["role"]) ||
    $_SESSION["role"] !== "admin"
) {
    header("Location: login.php");
    exit();
}
if (
    !isset($_GET["id"]) ||
    !is_numeric($_GET["id"])
) {
    header("Location: approved_sellers.php");
    exit();
}
$seller_id = (int) $_GET["id"];
if ($seller_id <= 0) {
    header("Location: approved_sellers.php");
    exit();
}
$stmt = $conn->prepare(
    "SELECT
        user_id,
        name,
        email,
        shop_name,
        seller_status,
        account_status
     FROM users
     WHERE user_id = ?
     AND role = 'seller'
     AND seller_status = 'Approved'
     AND account_status = 'Active'"
);
if (!$stmt) {
    die("Could not prepare seller query.");
}
$stmt->bind_param(
    "i",
    $seller_id
);
$stmt->execute();
$result = $stmt->get_result();
$seller = $result->fetch_assoc();
$stmt->close();
if (!$seller) {
    $_SESSION["admin_error"] =
        "Seller not found or the seller is already inactive.";
    header("Location: approved_sellers.php");
    exit();
}
if (
    !isset($_GET["confirm"]) ||
    $_GET["confirm"] !== "yes"
) {
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
        Delete Seller Account - Gauley Ko Pasal
    </title>
    <link
        rel="stylesheet"
        href="../style.css"
    >
    <style>
        .delete-container {
            width: 90%;
            max-width: 700px;
            margin: 55px auto 70px;
        }
        .delete-card {
            background: white;
            padding: 35px;
            border-radius: 16px;
            border: 1px solid #eee4d5;
            box-shadow:
                0 6px 20px rgba(
                    45,
                    36,
                    31,
                    0.08
                );
        }
        .warning-icon {
            font-size: 48px;
            margin-bottom: 8px;
        }
        .delete-card h2 {
            margin: 0 0 10px;
            color: #7b1e2b;
        }
        .seller-info {
            background: #faf6ef;
            border: 1px solid #eee4d5;
            border-radius: 10px;
            padding: 18px;
            margin: 22px 0;
        }
        .seller-info p {
            margin: 8px 0;
        }
        .warning-box {
            background: #fff4f4;
            border: 1px solid #f0cccc;
            border-radius: 10px;
            padding: 18px;
            color: #6d2424;
            line-height: 1.6;
            margin-bottom: 25px;
        }
        .warning-box strong {
            display: block;
            margin-bottom: 8px;
        }
        .warning-box ul {
            margin-bottom: 0;
        }
        .delete-actions {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }
        .delete-button {
            display: inline-block;
            padding: 12px 18px;
            background: #b42318;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: bold;
        }
        .delete-button:hover {
            background: #8f1c13;
            text-decoration: none;
        }
        .cancel-button {
            display: inline-block;
            padding: 12px 18px;
            background: #eee;
            color: #333;
            text-decoration: none;
            border-radius: 8px;
            font-weight: bold;
        }
        .cancel-button:hover {
            background: #ddd;
            text-decoration: none;
        }
        @media (max-width: 600px) {
            .delete-container {
                width: 92%;
            }
            .delete-card {
                padding: 24px;
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
        <a href="sellers.php">
            Seller Applications
        </a>
        <a href="approved_sellers.php">
            Approved Sellers
        </a>
        <a href="users.php">
            Users
        </a>
        <a href="activity_logs.php">
            Activity Logs
        </a>
        <a href="logout.php">
            Logout
        </a>
    </nav>
</header>
<div class="delete-container">
    <div class="delete-card">
        <div class="warning-icon">
            ⚠️
        </div>
        <h2>
            Delete Seller Account
        </h2>
        <p>
            You are about to deactivate this seller account.
        </p>
        <div class="seller-info">
            <p>
                <strong>
                    Seller:
                </strong>
                <?php
                echo htmlspecialchars(
                    $seller["name"]
                );
                ?>
            </p>
            <p>
                <strong>
                    Email:
                </strong>
                <?php
                echo htmlspecialchars(
                    $seller["email"]
                );
                ?>
            </p>
            <p>
                <strong>
                    Shop:
                </strong>
                <?php
                echo htmlspecialchars(
                    !empty($seller["shop_name"])
                    ? $seller["shop_name"]
                    : "Shop Name Not Provided"
                );
                ?>
            </p>
        </div>
        <div class="warning-box">
            <strong>
                ⚠️ What will happen?
            </strong>
            <ul>
                <li>
                    The seller account will become inactive.
                </li>
                <li>
                    The seller will no longer be able to log in.
                </li>
                <li>
                    The seller will disappear from the
                    approved sellers list.
                </li>
                <li>
                    The seller's existing products will
                    remain in the database for order history.
                </li>
                <li>
                    Orders containing products from this
                    seller will be cancelled.
                </li>
                <li>
                    Customers will see
                    <strong>
                        Unauthorized seller
                    </strong>
                    as the cancellation reason.
                </li>
            </ul>
        </div>
        <div class="delete-actions">
            <a
                href="delete_seller.php?id=<?php echo $seller_id; ?>&confirm=yes"
                class="delete-button"
                onclick="
                    return confirm(
                        'Are you absolutely sure you want to deactivate this seller account?'
                    );
                "
            >
                🗑 Delete Seller Account
            </a>
            <a
                href="seller_details.php?id=<?php echo $seller_id; ?>"
                class="cancel-button"
            >
                ← Cancel
            </a>
        </div>
    </div>
</div>
<footer>
    <p>
        Gauley Ko Pasal — Admin Panel 🇳🇵
    </p>
</footer>
</body>
</html>
<?php
    exit();
}
$conn->begin_transaction();
try {
    
    $product_ids = [];
    $product_stmt = $conn->prepare(
        "SELECT product_id
         FROM products
         WHERE seller_id = ?"
    );
    if (!$product_stmt) {
        throw new Exception(
            "Could not prepare product query."
        );
    }
    $product_stmt->bind_param(
        "i",
        $seller_id
    );
    $product_stmt->execute();
    $product_result =
        $product_stmt->get_result();
    while (
        $product =
        $product_result->fetch_assoc()
    ) {
        $product_ids[] =
            (int) $product["product_id"];
    }
    $product_stmt->close();
    
    $affected_orders = [];
    if (!empty($product_ids)) {
        $placeholders = implode(
            ",",
            array_fill(
                0,
                count($product_ids),
                "?"
            )
        );
        $order_sql =
            "SELECT DISTINCT order_id
             FROM order_items
             WHERE product_id IN ($placeholders)";
        $order_stmt =
            $conn->prepare(
                $order_sql
            );
        if (!$order_stmt) {
            throw new Exception(
                "Could not prepare order query."
            );
        }
        $types =
            str_repeat(
                "i",
                count($product_ids)
            );
        $order_stmt->bind_param(
            $types,
            ...$product_ids
        );
        $order_stmt->execute();
        $order_result =
            $order_stmt->get_result();
        while (
            $order =
            $order_result->fetch_assoc()
        ) {
            $affected_orders[] =
                (int) $order["order_id"];
        }
        $order_stmt->close();
    }
    
    if (!empty($affected_orders)) {
        $order_placeholders =
            implode(
                ",",
                array_fill(
                    0,
                    count($affected_orders),
                    "?"
                )
            );
        $cancellation_note =
            "This order was cancelled because a seller responsible for one or more products in this order is no longer authorized to sell on Gauley Ko Pasal.";
        $update_order_sql =
            "UPDATE orders
             SET
                status = 'Cancelled',
                cancellation_reason = 'Unauthorized seller',
                cancellation_note = ?,
                cancelled_at = NOW()
             WHERE order_id IN ($order_placeholders)
             AND status <> 'Delivered'";
        $update_order_stmt =
            $conn->prepare(
                $update_order_sql
            );
        if (!$update_order_stmt) {
            throw new Exception(
                "Could not prepare order cancellation query."
            );
        }
        $types =
            "s" .
            str_repeat(
                "i",
                count($affected_orders)
            );
        $params =
            array_merge(
                [$cancellation_note],
                $affected_orders
            );
        $update_order_stmt->bind_param(
            $types,
            ...$params
        );
        $update_order_stmt->execute();
        $update_order_stmt->close();
        
        $placeholders = implode(
            ",",
            array_fill(
                0,
                count($product_ids),
                "?"
            )
        );
        $update_items_stmt =
            $conn->prepare(
                "UPDATE order_items
                 SET status = 'Cancelled'
                 WHERE product_id IN ($placeholders)"
            );
        if (!$update_items_stmt) {
            throw new Exception(
                "Could not prepare order item cancellation query."
            );
        }
        $types =
            str_repeat(
                "i",
                count($product_ids)
            );
        $update_items_stmt->bind_param(
            $types,
            ...$product_ids
        );
        $update_items_stmt->execute();
        $update_items_stmt->close();
    }
    
    $deactivate_stmt =
        $conn->prepare(
            "UPDATE users
             SET account_status = 'Inactive'
             WHERE user_id = ?
             AND role = 'seller'
             AND seller_status = 'Approved'
             AND account_status = 'Active'"
        );
    if (!$deactivate_stmt) {
        throw new Exception(
            "Could not prepare account deactivation query."
        );
    }
    $deactivate_stmt->bind_param(
        "i",
        $seller_id
    );
    $deactivate_stmt->execute();
    if (
        $deactivate_stmt->affected_rows !== 1
    ) {
        throw new Exception(
            "Seller account could not be deactivated."
        );
    }
    $deactivate_stmt->close();
    
    $conn->commit();
    
    $_SESSION["admin_message"] =
        "Seller account for " .
        $seller["name"] .
        " has been deactivated successfully. " .
        count($affected_orders) .
        " affected order(s) were cancelled.";
    header(
        "Location: approved_sellers.php"
    );
    exit();
} catch (Throwable $e) {
    
    $conn->rollback();
    $_SESSION["admin_error"] =
        "Unable to deactivate seller: " .
        $e->getMessage();
    header(
        "Location: seller_details.php?id=" .
        $seller_id
    );
    exit();
}
?>

