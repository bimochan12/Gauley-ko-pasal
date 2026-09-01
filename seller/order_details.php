<?php

session_start();

require_once "../config/database.php";

/*
|--------------------------------------------------------------------------
| REQUIRE SELLER LOGIN
|--------------------------------------------------------------------------
*/

if (
    !isset($_SESSION["user_id"]) ||
    !isset($_SESSION["role"]) ||
    $_SESSION["role"] !== "seller"
) {
    header("Location: login.php");
    exit();
}

$seller_id = (int) $_SESSION["user_id"];


/*
|--------------------------------------------------------------------------
| GET ORDER ID
|--------------------------------------------------------------------------
*/

$order_id = (int) ($_GET["order_id"] ?? 0);

if ($order_id <= 0) {
    header("Location: orders.php?error=invalid_order");
    exit();
}


/*
|--------------------------------------------------------------------------
| GET ORDER INFORMATION
|--------------------------------------------------------------------------
|
| We only allow the seller to see the order if that order
| contains at least one product belonging to this seller.
|
*/

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
        o.cancelled_at,

        u.name AS customer_name,
        u.email AS customer_email

    FROM orders o

    INNER JOIN users u
        ON o.user_id = u.user_id

    INNER JOIN order_items oi
        ON o.order_id = oi.order_id

    INNER JOIN products p
        ON oi.product_id = p.product_id

    WHERE o.order_id = ?
    AND p.seller_id = ?

    LIMIT 1
";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Database error.");
}

$stmt->bind_param(
    "ii",
    $order_id,
    $seller_id
);

$stmt->execute();

$result = $stmt->get_result();

if (!$result || $result->num_rows === 0) {
    header("Location: orders.php?error=not_your_order");
    exit();
}

$order = $result->fetch_assoc();


/*
|--------------------------------------------------------------------------
| GET SELLER'S PRODUCTS FROM THIS ORDER
|--------------------------------------------------------------------------
*/

$item_sql = "
    SELECT
        oi.order_item_id,
        oi.product_id,
        oi.quantity,
        oi.price,
        oi.status AS item_status,

        p.product_name,
        p.image

    FROM order_items oi

    INNER JOIN products p
        ON oi.product_id = p.product_id

    WHERE oi.order_id = ?
    AND p.seller_id = ?

    ORDER BY oi.order_item_id ASC
";

$item_stmt = $conn->prepare($item_sql);

if (!$item_stmt) {
    die("Database error.");
}

$item_stmt->bind_param(
    "ii",
    $order_id,
    $seller_id
);

$item_stmt->execute();

$item_result = $item_stmt->get_result();

$items = [];

while ($item = $item_result->fetch_assoc()) {
    $items[] = $item;
}


/*
|--------------------------------------------------------------------------
| CALCULATE SELLER ORDER TOTAL
|--------------------------------------------------------------------------
*/

$seller_total = 0;

foreach ($items as $item) {

    $seller_total +=
        (float) $item["price"] *
        (int) $item["quantity"];
}


/*
|--------------------------------------------------------------------------
| STATUS CLASS
|--------------------------------------------------------------------------
*/

$status = $order["status"] ?? "Pending";

$status_class =
    "status-" .
    strtolower($status);


/*
|--------------------------------------------------------------------------
| GOOGLE MAP LINK
|--------------------------------------------------------------------------
*/

$latitude =
    $order["delivery_latitude"];

$longitude =
    $order["delivery_longitude"];

$has_location =
    $latitude !== null &&
    $longitude !== null &&
    $latitude !== "" &&
    $longitude !== "";

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
        Order #<?php echo $order_id; ?> -
        Seller Panel
    </title>

    <link
        rel="stylesheet"
        href="../style.css"
    >

    <style>

        body {
            background: #f7f8fa;
        }

        .order-details-page {
            width: 92%;
            max-width: 1100px;
            margin: 0 auto;
            padding: 45px 0 80px;
        }

        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: #1976d2;
            font-weight: 700;
            font-size: 13px;
        }

        .page-header {
            background: white;
            border: 1px solid #e4e9ef;
            border-radius: 18px;
            padding: 25px;
            margin-bottom: 22px;

            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
        }

        .page-header h1 {
            margin: 0 0 7px;
            color: #102a43;
        }

        .page-header p {
            margin: 0;
            color: #718096;
            font-size: 13px;
        }

        .status {
            padding: 8px 14px;
            border-radius: 30px;
            font-size: 12px;
            font-weight: 800;
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

        .details-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 22px;
            margin-bottom: 22px;
        }

        .details-card {
            background: white;
            border: 1px solid #e4e9ef;
            border-radius: 18px;
            padding: 25px;
        }

        .details-card h2 {
            margin: 0 0 20px;
            color: #102a43;
            font-size: 19px;
        }

        .info-row {
            padding: 12px 0;
            border-bottom: 1px solid #edf1f5;
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .info-label {
            display: block;
            color: #718096;
            font-size: 11px;
            font-weight: 700;
            margin-bottom: 4px;
        }

        .info-value {
            color: #172033;
            font-size: 14px;
            font-weight: 600;
        }

        .items-card {
            background: white;
            border: 1px solid #e4e9ef;
            border-radius: 18px;
            overflow: hidden;
            margin-bottom: 22px;
        }

        .items-header {
            padding: 22px 25px;
            border-bottom: 1px solid #edf1f5;
        }

        .items-header h2 {
            margin: 0;
            color: #102a43;
            font-size: 19px;
        }

        .order-item {
            display: flex;
            align-items: center;
            gap: 18px;
            padding: 20px 25px;
            border-bottom: 1px solid #edf1f5;
        }

        .order-item:last-child {
            border-bottom: none;
        }

        .item-image {
            width: 75px;
            height: 75px;
            border-radius: 12px;
            background: #f4f7fa;
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
            padding: 7px;
        }

        .item-placeholder {
            font-size: 30px;
        }

        .item-info {
            flex: 1;
        }

        .item-info h3 {
            margin: 0 0 7px;
            color: #172033;
            font-size: 15px;
        }

        .item-info p {
            margin: 3px 0;
            color: #718096;
            font-size: 12px;
        }

        .item-status {
            display: inline-block;
            margin-top: 7px;
            padding: 5px 9px;
            background: #f1f4f8;
            border-radius: 20px;
            color: #526174;
            font-size: 10px;
            font-weight: 800;
        }

        .item-total {
            text-align: right;
            color: #102a43;
            font-size: 15px;
            font-weight: 900;
        }

        .total-section {
            background: #fafbfd;
            border-top: 1px solid #edf1f5;
            padding: 22px 25px;

            display: flex;
            justify-content: flex-end;
            align-items: center;
            gap: 25px;
        }

        .total-label {
            color: #718096;
            font-size: 11px;
            font-weight: 800;
        }

        .total-price {
            color: #102a43;
            font-size: 23px;
            font-weight: 900;
        }

        /*
        |--------------------------------------------------------------------------
        | CANCELLATION
        |--------------------------------------------------------------------------
        */

        .cancellation-card {
            background: #fff8f8;
            border: 1px solid #f1cccc;
            border-radius: 18px;
            padding: 25px;
            margin-bottom: 22px;
        }

        .cancellation-card h2 {
            color: #b42323;
            margin: 0 0 18px;
            font-size: 19px;
        }

        .cancel-row {
            padding: 12px 0;
            border-bottom: 1px solid #f1dede;
        }

        .cancel-row:last-child {
            border-bottom: none;
        }

        .cancel-label {
            display: block;
            color: #8b5a5a;
            font-size: 11px;
            font-weight: 800;
            margin-bottom: 5px;
        }

        .cancel-value {
            color: #572525;
            font-size: 14px;
        }

        /*
        |--------------------------------------------------------------------------
        | LOCATION
        |--------------------------------------------------------------------------
        */

        .location-card {
            background: white;
            border: 1px solid #e4e9ef;
            border-radius: 18px;
            padding: 25px;
            margin-bottom: 22px;
        }

        .location-card h2 {
            margin: 0 0 15px;
            color: #102a43;
            font-size: 19px;
        }

        .coordinates {
            background: #f4f7fa;
            padding: 13px;
            border-radius: 9px;
            margin-bottom: 15px;
            font-size: 12px;
            color: #526174;
        }

        .map-button {
            display: inline-block;
            background: #102a43;
            color: white;
            padding: 11px 17px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 800;
            text-decoration: none;
        }

        .map-button:hover {
            background: #1976d2;
        }

        .no-location {
            color: #718096;
            font-size: 13px;
        }

        @media (max-width: 750px) {

            .details-grid {
                grid-template-columns: 1fr;
            }

            .page-header {
                align-items: flex-start;
                flex-direction: column;
            }

            .order-item {
                align-items: flex-start;
            }

            .item-total {
                font-size: 13px;
            }

            .total-section {
                justify-content: space-between;
            }

        }

        @media (max-width: 550px) {

            .order-item {
                flex-wrap: wrap;
            }

            .item-total {
                width: 100%;
                text-align: left;
            }

            .total-section {
                align-items: flex-start;
                flex-direction: column;
                gap: 5px;
            }

        }

    </style>

</head>

<body>


<!-- HEADER -->

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


<!-- PAGE -->

<main class="order-details-page">


    <a
        href="orders.php"
        class="back-link"
    >
        ← Back to Active Orders
    </a>


    <!-- HEADER -->

    <section class="page-header">

        <div>

            <h1>

                Order #<?php
                echo $order_id;
                ?>

            </h1>

            <p>

                Placed on

                <?php
                echo date(
                    "M d, Y • h:i A",
                    strtotime(
                        $order["order_date"]
                    )
                );
                ?>

            </p>

        </div>


        <span
            class="status <?php
                echo htmlspecialchars(
                    $status_class
                );
            ?>"
        >

            <?php
            echo htmlspecialchars($status);
            ?>

        </span>

    </section>


    <!-- CUSTOMER + ORDER INFORMATION -->

    <div class="details-grid">


        <section class="details-card">

            <h2>
                Customer Information
            </h2>


            <div class="info-row">

                <span class="info-label">
                    NAME
                </span>

                <span class="info-value">

                    <?php
                    echo htmlspecialchars(
                        $order["customer_name"]
                    );
                    ?>

                </span>

            </div>


            <div class="info-row">

                <span class="info-label">
                    EMAIL
                </span>

                <span class="info-value">

                    <?php
                    echo htmlspecialchars(
                        $order["customer_email"]
                    );
                    ?>

                </span>

            </div>


            <div class="info-row">

                <span class="info-label">
                    ORDER ID
                </span>

                <span class="info-value">

                    #<?php
                    echo $order_id;
                    ?>

                </span>

            </div>


        </section>


        <section class="details-card">

            <h2>
                Order Information
            </h2>


            <div class="info-row">

                <span class="info-label">
                    ORDER STATUS
                </span>

                <span class="info-value">

                    <?php
                    echo htmlspecialchars(
                        $status
                    );
                    ?>

                </span>

            </div>


            <div class="info-row">

                <span class="info-label">
                    ORDER DATE
                </span>

                <span class="info-value">

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


            <div class="info-row">

                <span class="info-label">
                    FULL ORDER TOTAL
                </span>

                <span class="info-value">

                    Rs.
                    <?php
                    echo number_format(
                        (float)
                        $order["total_amount"],
                        2
                    );
                    ?>

                </span>

            </div>


        </section>


    </div>


    <!-- PRODUCTS -->

    <section class="items-card">


        <div class="items-header">

            <h2>
                Products From Your Store
            </h2>

        </div>


        <?php if (empty($items)): ?>

            <div style="
                padding:25px;
                color:#718096;
            ">

                No products from your store
                were found in this order.

            </div>

        <?php else: ?>


            <?php foreach ($items as $item): ?>

                <?php

                $item_name =
                    $item["product_name"];

                $quantity =
                    (int) $item["quantity"];

                $price =
                    (float) $item["price"];

                $item_total =
                    $price * $quantity;

                $image =
                    trim(
                        $item["image"] ?? ""
                    );

                ?>


                <div class="order-item">


                    <div class="item-image">

                        <?php if (
                            $image !== "" &&
                            file_exists(
                                "../" . $image
                            )
                        ): ?>

                            <img
                                src="<?php
                                    echo htmlspecialchars(
                                        "../" . $image
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

                            Price:

                            Rs.
                            <?php
                            echo number_format(
                                $price,
                                2
                            );
                            ?>

                        </p>


                        <p>

                            Quantity:

                            <?php
                            echo $quantity;
                            ?>

                        </p>


                        <span class="item-status">

                            <?php
                            echo htmlspecialchars(
                                $item["item_status"]
                                ?? $status
                            );
                            ?>

                        </span>

                    </div>


                    <div class="item-total">

                        Rs.
                        <?php
                        echo number_format(
                            $item_total,
                            2
                        );
                        ?>

                    </div>


                </div>


            <?php endforeach; ?>


            <div class="total-section">

                <span class="total-label">
                    YOUR STORE TOTAL
                </span>

                <strong class="total-price">

                    Rs.
                    <?php
                    echo number_format(
                        $seller_total,
                        2
                    );
                    ?>

                </strong>

            </div>


        <?php endif; ?>


    </section>


    <!-- CANCELLATION INFORMATION -->

    <?php if (
        strtolower($status) === "cancelled"
    ): ?>


        <section class="cancellation-card">

            <h2>
                ⚠ Cancellation Information
            </h2>


            <?php if (
                !empty(
                    $order["cancellation_reason"]
                )
            ): ?>

                <div class="cancel-row">

                    <span class="cancel-label">
                        CANCELLATION REASON
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
                    $order["cancellation_note"]
                )
            ): ?>

                <div class="cancel-row">

                    <span class="cancel-label">
                        CUSTOMER'S NOTE
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
                    $order["cancelled_at"]
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


        </section>


    <?php endif; ?>


    <!-- DELIVERY LOCATION -->

    <section class="location-card">

        <h2>
            📍 Delivery Location
        </h2>


        <?php if ($has_location): ?>


            <div class="coordinates">

                <strong>
                    Latitude:
                </strong>

                <?php
                echo htmlspecialchars(
                    $latitude
                );
                ?>

                &nbsp;&nbsp;

                <strong>
                    Longitude:
                </strong>

                <?php
                echo htmlspecialchars(
                    $longitude
                );
                ?>

            </div>


            <a
                href="https://www.google.com/maps?q=<?php
                    echo urlencode(
                        $latitude .
                        "," .
                        $longitude
                    );
                ?>"
                target="_blank"
                rel="noopener noreferrer"
                class="map-button"
            >
                Open Delivery Location in Google Maps →
            </a>


        <?php else: ?>


            <p class="no-location">

                The customer did not provide
                a delivery location.

            </p>


        <?php endif; ?>


    </section>


</main>


<!-- FOOTER -->

<footer>

    <p>
        Gauley Ko Pasal — Seller Panel 🇳🇵
    </p>

</footer>


</body>

</html>