
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
        ON order_items.order_id = orders.order_id

    INNER JOIN users
        ON orders.user_id = users.user_id

    INNER JOIN products
        ON order_items.product_id = products.product_id

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

$result = $stmt->get_result();

?>

<!DOCTYPE html>
<html>

<head>

    <title>
        Active Orders - Gauley Ko Pasal
    </title>

    <link
        rel="stylesheet"
        href="../style.css"
    >

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

            <div
                class="card"
                style="margin-bottom: 20px;"
            >

                <h2>

                    Order #

                    <?php
                    echo (int) $item["order_id"];
                    ?>

                </h2>

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
                            width: 150px;
                            max-height: 150px;
                            object-fit: cover;
                        "
                    >

                    <br><br>

                <?php endif; ?>

                <p>

                    <strong>
                        Quantity:
                    </strong>

                    <?php
                    echo (int) $item["quantity"];
                    ?>

                </p>

                <p>

                    <strong>
                        Price:
                    </strong>

                    Rs.

                    <?php
                    echo number_format(
                        $item["price"],
                        2
                    );
                    ?>

                </p>

                <p>

                    <strong>
                        Customer:
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
                        Order Date:
                    </strong>

                    <?php
                    echo htmlspecialchars(
                        $item["order_date"]
                    );
                    ?>

                </p>

                <p>

                    <strong>
                        Current Status:
                    </strong>

                    <?php
                    echo htmlspecialchars(
                        $item["item_status"]
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
                            $item["order_item_id"];
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
                                $item["item_status"]
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
                                $item["item_status"]
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

                        <option value="Cancelled">
                            Cancelled
                        </option>

                    </select>

                    <br><br>

                    <button type="submit">
                        Update Product Status
                    </button>

                </form>

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
```

