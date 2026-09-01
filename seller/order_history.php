<?php
session_start();
require_once "../config/database.php";
if (
    !isset($_SESSION["user_id"]) ||
    !isset($_SESSION["role"]) ||
    $_SESSION["role"] !== "seller"
) {
    header("Location: login.php");
    exit();
}
$seller_id = (int) $_SESSION["user_id"];
$orders = [];
$sql = "
    SELECT
        o.order_id,
        o.order_date,
        o.total_amount,
        o.status,
        o.cancellation_reason,
        o.cancellation_note,
        o.cancelled_at,
        u.name AS customer_name,
        u.email AS customer_email,
        COUNT(oi.order_item_id) AS item_count
    FROM orders o
    INNER JOIN users u
        ON o.user_id = u.user_id
    INNER JOIN order_items oi
        ON o.order_id = oi.order_id
    INNER JOIN products p
        ON oi.product_id = p.product_id
    WHERE p.seller_id = ?
    AND (
        oi.status = 'Delivered'
        OR oi.status = 'Cancelled'
        OR o.status = 'Delivered'
        OR o.status = 'Cancelled'
    )
    GROUP BY
        o.order_id,
        o.order_date,
        o.total_amount,
        o.status,
        o.cancellation_reason,
        o.cancellation_note,
        o.cancelled_at,
        u.name,
        u.email
    ORDER BY o.order_id DESC
";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("Database error.");
}
$stmt->bind_param(
    "i",
    $seller_id
);
$stmt->execute();
$result = $stmt->get_result();
while ($order = $result->fetch_assoc()) {
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
        Order History - Gauley Ko Pasal
    </title>
    <link
        rel="stylesheet"
        href="../style.css"
    >
    <style>
        
        .history-page {
            width: 92%;
            max-width: 1100px;
            margin: 0 auto;
            padding: 45px 0 80px;
        }
        .history-header {
            margin-bottom: 30px;
        }
        .history-header h2 {
            margin-bottom: 8px;
        }
        .history-header p {
            color: #718096;
        }
        
        .history-card {
            background: white;
            border: 1px solid #e4e9ef;
            border-radius: 18px;
            margin-bottom: 22px;
            overflow: hidden;
            box-shadow:
                0 8px 25px rgba(20, 40, 70, 0.04);
        }
        .history-top {
            padding: 22px 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            border-bottom: 1px solid #edf1f5;
        }
        .order-number {
            color: #102a43;
            font-size: 17px;
            font-weight: 900;
        }
        .order-date {
            color: #718096;
            font-size: 12px;
            margin-top: 5px;
        }
        
        .status {
            display: inline-block;
            padding: 7px 13px;
            border-radius: 30px;
            font-size: 11px;
            font-weight: 900;
            white-space: nowrap;
        }
        .status-delivered {
            background: #e9f8ef;
            color: #16834b;
        }
        .status-cancelled {
            background: #fff0f0;
            color: #d64545;
        }
        .status-pending {
            background: #fff7df;
            color: #9a6700;
        }
        .status-processing {
            background: #eaf4ff;
            color: #1976d2;
        }
        
        .history-body {
            padding: 22px 25px;
        }
        .info-grid {
            display: grid;
            grid-template-columns:
                repeat(3, 1fr);
            gap: 18px;
        }
        .info-box {
            background: #f7f9fb;
            border-radius: 10px;
            padding: 14px;
        }
        .info-label {
            display: block;
            color: #718096;
            font-size: 10px;
            font-weight: 900;
            letter-spacing: 0.5px;
            margin-bottom: 5px;
        }
        .info-value {
            color: #172033;
            font-size: 13px;
            font-weight: 700;
            word-break: break-word;
        }
        
        .cancellation-box {
            margin-top: 20px;
            padding: 18px;
            background: #fff8f8;
            border: 1px solid #f1cccc;
            border-radius: 12px;
        }
        .cancellation-box h3 {
            margin: 0 0 15px;
            color: #b42323;
            font-size: 15px;
        }
        .cancel-row {
            padding: 10px 0;
            border-bottom:
                1px solid #f1dede;
        }
        .cancel-row:last-child {
            border-bottom: none;
        }
        .cancel-label {
            display: block;
            color: #8b5a5a;
            font-size: 10px;
            font-weight: 900;
            margin-bottom: 5px;
        }
        .cancel-value {
            color: #572525;
            font-size: 13px;
            line-height: 1.6;
        }
        
        .history-footer {
            padding: 18px 25px;
            background: #fafbfd;
            border-top: 1px solid #edf1f5;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
        }
        .history-total {
            color: #102a43;
            font-size: 18px;
            font-weight: 900;
        }
        .details-button {
            display: inline-block;
            padding: 10px 16px;
            background: #102a43;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-size: 11px;
            font-weight: 800;
        }
        .details-button:hover {
            background: #1976d2;
        }
        
        .empty-history {
            background: white;
            border: 1px solid #e4e9ef;
            border-radius: 18px;
            padding: 65px 30px;
            text-align: center;
        }
        .empty-history-icon {
            font-size: 55px;
            margin-bottom: 15px;
        }
        .empty-history h3 {
            color: #102a43;
            margin-bottom: 8px;
        }
        .empty-history p {
            color: #718096;
            font-size: 13px;
        }
        
        @media (max-width: 800px) {
            .info-grid {
                grid-template-columns: 1fr 1fr;
            }
        }
        @media (max-width: 600px) {
            .history-top {
                align-items: flex-start;
                flex-direction: column;
            }
            .info-grid {
                grid-template-columns: 1fr;
            }
            .history-footer {
                align-items: flex-start;
                flex-direction: column;
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
<main class="history-page">
    <div class="history-header">
        <h2>
            Order History 📦
        </h2>
        <p>
            View completed and cancelled orders
            containing your products.
        </p>
    </div>
    <?php if (empty($orders)): ?>
        
        <div class="empty-history">
            <div class="empty-history-icon">
                📦
            </div>
            <h3>
                No order history yet
            </h3>
            <p>
                Delivered and cancelled orders
                will appear here.
            </p>
        </div>
    <?php else: ?>
        <?php foreach ($orders as $order): ?>
            <?php
            $status =
                $order["status"] ?? "Pending";
            $status_class =
                "status-" .
                strtolower($status);
            ?>
            
            <article class="history-card">
                
                <div class="history-top">
                    <div>
                        <div class="order-number">
                            Order #
                            <?php
                            echo (int)
                                $order["order_id"];
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
                        class="status <?php
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
                
                <div class="history-body">
                    <div class="info-grid">
                        
                        <div class="info-box">
                            <span class="info-label">
                                CUSTOMER
                            </span>
                            <span class="info-value">
                                <?php
                                echo htmlspecialchars(
                                    $order[
                                        "customer_name"
                                    ]
                                );
                                ?>
                            </span>
                        </div>
                        
                        <div class="info-box">
                            <span class="info-label">
                                EMAIL
                            </span>
                            <span class="info-value">
                                <?php
                                echo htmlspecialchars(
                                    $order[
                                        "customer_email"
                                    ]
                                );
                                ?>
                            </span>
                        </div>
                        
                        <div class="info-box">
                            <span class="info-label">
                                YOUR ORDER ITEMS
                            </span>
                            <span class="info-value">
                                <?php
                                echo (int)
                                    $order[
                                        "item_count"
                                    ];
                                ?>
                                item(s)
                            </span>
                        </div>
                    </div>
                    
                    <?php if (
                        strtolower($status)
                        === "cancelled"
                    ): ?>
                        <div class="cancellation-box">
                            <h3>
                                ⚠ Customer Cancellation
                            </h3>
                            <?php if (
                                !empty(
                                    $order[
                                        "cancellation_reason"
                                    ]
                                )
                            ): ?>
                                <div class="cancel-row">
                                    <span class="cancel-label">
                                        REASON
                                    </span>
                                    <div class="cancel-value">
                                        <?php
                                        echo htmlspecialchars(
                                            $order[
                                                "cancellation_reason"
                                            ]
                                        );
                                        ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                            <?php if (
                                !empty(
                                    $order[
                                        "cancellation_note"
                                    ]
                                )
                            ): ?>
                                <div class="cancel-row">
                                    <span class="cancel-label">
                                        CUSTOMER NOTE
                                    </span>
                                    <div class="cancel-value">
                                        <?php
                                        echo nl2br(
                                            htmlspecialchars(
                                                $order[
                                                    "cancellation_note"
                                                ]
                                            )
                                        );
                                        ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                            <?php if (
                                !empty(
                                    $order[
                                        "cancelled_at"
                                    ]
                                )
                            ): ?>
                                <div class="cancel-row">
                                    <span class="cancel-label">
                                        CANCELLED AT
                                    </span>
                                    <div class="cancel-value">
                                        <?php
                                        echo date(
                                            "M d, Y • h:i A",
                                            strtotime(
                                                $order[
                                                    "cancelled_at"
                                                ]
                                            )
                                        );
                                        ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                            <?php if (
                                empty(
                                    $order[
                                        "cancellation_reason"
                                    ]
                                ) &&
                                empty(
                                    $order[
                                        "cancellation_note"
                                    ]
                                )
                            ): ?>
                                <div class="cancel-value">
                                    No cancellation explanation
                                    was provided.
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="history-footer">
                    <strong class="history-total">
                        Rs.
                        <?php
                        echo number_format(
                            (float)
                            $order[
                                "total_amount"
                            ],
                            2
                        );
                        ?>
                    </strong>
                    <a
                        href="order_details.php?order_id=<?php
                            echo (int)
                                $order["order_id"];
                        ?>"
                        class="details-button"
                    >
                        View Order Details →
                    </a>
                </div>
            </article>
        <?php endforeach; ?>
    <?php endif; ?>
</main>
<footer>
    <p>
        Gauley Ko Pasal — Seller Panel 🇳🇵
    </p>
</footer>
</body>
</html>

