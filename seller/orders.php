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


/*
|--------------------------------------------------------------------------
| SUCCESS / ERROR MESSAGES
|--------------------------------------------------------------------------
*/

if (
    isset($_GET["success"]) &&
    $_GET["success"] === "status_updated"
) {
    $message = "Product order status updated successfully.";
}

if (isset($_GET["error"])) {

    if ($_GET["error"] === "invalid_item") {

        $message = "Invalid order item.";

    } elseif ($_GET["error"] === "invalid_status") {

        $message = "Invalid order status.";

    } elseif ($_GET["error"] === "not_your_product") {

        $message = "You do not have permission to manage this product.";

    } elseif ($_GET["error"] === "update_failed") {

        $message = "Could not update the product order status.";

    } elseif ($_GET["error"] === "database") {

        $message = "A database error occurred.";

    }

}


/*
|--------------------------------------------------------------------------
| GET ACTIVE ORDERS
|--------------------------------------------------------------------------
|
| We check BOTH:
|
| 1. order_items.status
| 2. orders.status
|
| This allows a customer's cancelled order to appear here.
|
*/

$sql = "
    SELECT
        orders.order_id,
        orders.order_date,
        orders.status AS order_status,
        orders.cancellation_reason,
        orders.cancellation_note,
        orders.cancelled_at,
        orders.delivery_latitude,
        orders.delivery_longitude,

        users.name AS customer_name,
        users.email AS customer_email,
        users.phone AS customer_phone,
        users.address AS customer_address,

        order_items.order_item_id,
        order_items.quantity,
        order_items.price,
        order_items.status AS item_status,

        products.product_name,
        products.image

    FROM order_items

    INNER JOIN orders
        ON order_items.order_id = orders.order_id

    INNER JOIN users
        ON orders.user_id = users.user_id

    INNER JOIN products
        ON order_items.product_id = products.product_id

    WHERE products.seller_id = ?

    AND (
        order_items.status IN ('Pending', 'Processing')
        OR orders.status = 'Cancelled'
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

$result = $stmt->get_result();

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

        .order-card {
            margin-bottom: 25px;
        }

        .order-status {
            display: inline-block;
            padding: 7px 13px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: bold;
        }

        .status-pending {
            background: #fff3cd;
            color: #856404;
        }

        .status-processing {
            background: #dbeafe;
            color: #1d4ed8;
        }

        .status-cancelled {
            background: #fee2e2;
            color: #b91c1c;
        }

        .cancellation-box {
            margin-top: 20px;
            padding: 18px;

            background: #fff5f5;

            border: 1px solid #fecaca;

            border-radius: 10px;
        }

        .cancellation-box h3 {
            margin-top: 0;
            margin-bottom: 12px;
            color: #b91c1c;
        }

        .cancellation-box p {
            margin: 8px 0;
        }

        .customer-box {
            margin-top: 18px;
            padding: 15px;

            background: #f7f8fa;

            border-radius: 10px;
        }

        .location-box {
            margin-top: 18px;
            padding: 15px;

            background: #eef6fd;

            border-radius: 10px;
        }

        .status-update-box {
            margin-top: 20px;
            padding-top: 20px;

            border-top: 1px solid #e5e7eb;
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


<div class="container">


    <div class="hero">

        <h2>
            Active Orders 🛒
        </h2>

        <p>
            Manage orders for your own products.
        </p>

    </div>


    <?php if ($message !== ""): ?>

        <div class="card">

            <p>

                <?php
                echo htmlspecialchars($message);
                ?>

            </p>

        </div>

        <br>

    <?php endif; ?>


    <?php if (
        !$result ||
        $result->num_rows === 0
    ): ?>

        <div class="card">

            <h3>
                No active orders.
            </h3>

            <p>
                New orders containing your products
                will appear here.
            </p>

        </div>


    <?php else: ?>


        <?php while (
            $item = $result->fetch_assoc()
        ): ?>


            <?php

            $order_status =
                $item["order_status"] ?? "Pending";

            $item_status =
                $item["item_status"] ?? "Pending";

            $is_cancelled =
                $order_status === "Cancelled";

            ?>


            <div class="card order-card">


                <!-- ORDER NUMBER -->

                <h2>

                    Order #

                    <?php
                    echo (int) $item["order_id"];
                    ?>

                </h2>


                <!-- ORDER STATUS -->

                <p>

                    <strong>
                        Order Status:
                    </strong>

                    <span
                        class="order-status
                        <?php

                        if (
                            $order_status === "Cancelled"
                        ) {

                            echo "status-cancelled";

                        } elseif (
                            $item_status === "Processing"
                        ) {

                            echo "status-processing";

                        } else {

                            echo "status-pending";

                        }

                        ?>"
                    >

                        <?php
                        echo htmlspecialchars(
                            $order_status
                        );
                        ?>

                    </span>

                </p>


                <br>


                <!-- PRODUCT -->

                <p>

                    <strong>
                        Product:
                    </strong>

                    <?php
                    echo htmlspecialchars(
                        $item["product_name"]
                    );
                    ?>

                </p>


                <?php if (
                    !empty($item["image"])
                ): ?>

                    <img
                        src="../uploads/<?php
                        echo htmlspecialchars(
                            $item["image"]
                        );
                        ?>"
                        alt="<?php
                        echo htmlspecialchars(
                            $item["product_name"]
                        );
                        ?>"
                        style="
                            width:150px;
                            max-height:150px;
                            object-fit:cover;
                            margin-top:10px;
                        "
                    >

                    <br><br>

                <?php endif; ?>


                <!-- QUANTITY -->

                <p>

                    <strong>
                        Quantity:
                    </strong>

                    <?php
                    echo (int) $item["quantity"];
                    ?>

                </p>


                <!-- PRICE -->

                <p>

                    <strong>
                        Price:
                    </strong>

                    Rs.

                    <?php
                    echo number_format(
                        (float) $item["price"],
                        2
                    );
                    ?>

                </p>


                <!-- CUSTOMER -->

                <div class="customer-box">

                    <h3>
                        Customer Information
                    </h3>

                    <p>

                        <strong>
                            Name:
                        </strong>

                        <?php
                        echo htmlspecialchars(
                            $item["customer_name"]
                        );
                        ?>

                    </p>


                    <p>

                        <strong>
                            Email:
                        </strong>

                        <?php
                        echo htmlspecialchars(
                            $item["customer_email"]
                        );
                        ?>

                    </p>


                    <p>

                        <strong>
                            Phone:
                        </strong>

                        <?php
                        echo htmlspecialchars(
                            $item["customer_phone"]
                        );
                        ?>

                    </p>


                    <p>

                        <strong>
                            Address:
                        </strong>

                        <?php
                        echo htmlspecialchars(
                            $item["customer_address"]
                        );
                        ?>

                    </p>

                </div>


                <!-- ORDER DATE -->

                <p style="margin-top:18px;">

                    <strong>
                        Order Date:
                    </strong>

                    <?php
                    echo htmlspecialchars(
                        $item["order_date"]
                    );
                    ?>

                </p>


                <!-- DELIVERY LOCATION -->

                <div class="location-box">

                    <strong>
                        📍 Delivery Location
                    </strong>

                    <?php if (
                        $item["delivery_latitude"] !== null &&
                        $item["delivery_longitude"] !== null
                    ): ?>

                        <p>

                            Latitude:

                            <?php
                            echo htmlspecialchars(
                                $item["delivery_latitude"]
                            );
                            ?>

                            <br>

                            Longitude:

                            <?php
                            echo htmlspecialchars(
                                $item["delivery_longitude"]
                            );
                            ?>

                        </p>


                        <p>

                            <a
                                href="https://www.google.com/maps?q=<?php
                                    echo urlencode(
                                        $item["delivery_latitude"]
                                        . ","
                                        . $item["delivery_longitude"]
                                    );
                                ?>"
                                target="_blank"
                            >
                                📍 Open Delivery Location
                            </a>

                        </p>


                    <?php else: ?>

                        <p>
                            Delivery location not available.
                        </p>

                    <?php endif; ?>

                </div>


                <!-- CANCELLATION INFORMATION -->

                <?php if ($is_cancelled): ?>

                    <div class="cancellation-box">

                        <h3>
                            ❌ Order Cancelled
                        </h3>


                        <p>

                            <strong>
                                Cancellation Reason:
                            </strong>

                            <?php

                            if (
                                !empty(
                                    $item[
                                        "cancellation_reason"
                                    ]
                                )
                            ) {

                                echo htmlspecialchars(
                                    $item[
                                        "cancellation_reason"
                                    ]
                                );

                            } else {

                                echo "Not provided.";

                            }

                            ?>

                        </p>


                        <p>

                            <strong>
                                Customer's Explanation:
                            </strong>

                            <br>

                            <?php

                            if (
                                !empty(
                                    $item[
                                        "cancellation_note"
                                    ]
                                )
                            ) {

                                echo nl2br(
                                    htmlspecialchars(
                                        $item[
                                            "cancellation_note"
                                        ]
                                    )
                                );

                            } else {

                                echo "No additional explanation provided.";

                            }

                            ?>

                        </p>


                        <?php if (
                            !empty(
                                $item["cancelled_at"]
                            )
                        ): ?>

                            <p>

                                <strong>
                                    Cancelled At:
                                </strong>

                                <?php
                                echo htmlspecialchars(
                                    $item["cancelled_at"]
                                );
                                ?>

                            </p>

                        <?php endif; ?>


                    </div>


                <?php endif; ?>


                <!-- STATUS UPDATE -->

                <?php if (!$is_cancelled): ?>

                    <div class="status-update-box">

                        <p>

                            <strong>
                                Product Status:
                            </strong>

                            <?php
                            echo htmlspecialchars(
                                $item_status
                            );
                            ?>

                        </p>


                        <form
                            method="POST"
                            action="update_order.php"
                        >

                            <input
                                type="hidden"
                                name="order_item_id"
                                value="<?php
                                echo (int)
                                    $item[
                                        "order_item_id"
                                    ];
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
                                        $item_status
                                        === "Pending"
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
                                        $item_status
                                        === "Processing"
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


                            </select>


                            <br><br>


                            <button
                                type="submit"
                            >
                                Update Product Status
                            </button>


                        </form>

                    </div>

                <?php endif; ?>


            </div>


        <?php endwhile; ?>


    <?php endif; ?>


</div>


<footer>

    <p>
        Gauley Ko Pasal — Seller Panel 🇳🇵
    </p>

</footer>


</body>

</html>