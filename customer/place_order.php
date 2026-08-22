<?php

session_start();

require_once "../config/database.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: ../admin/login.php");
    exit();
}

if (empty($_SESSION["cart"])) {
    header("Location: cart.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] != "POST") {
    header("Location: checkout.php");
    exit();
}

$user_id = $_SESSION["user_id"];
$cart = $_SESSION["cart"];

$total = 0;

/*
    Calculate total
*/

foreach ($cart as $product_id => $quantity) {

    $sql = "SELECT * FROM products WHERE product_id = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $product_id);
    $stmt->execute();

    $result = $stmt->get_result();

    if ($result->num_rows == 1) {

        $product = $result->fetch_assoc();

        $total += $product["price"] * $quantity;
    }
}

/*
    Create order
*/

$sql = "INSERT INTO orders (user_id, total_amount)
        VALUES (?, ?)";

$stmt = $conn->prepare($sql);

$stmt->bind_param(
    "id",
    $user_id,
    $total
);

$stmt->execute();

$order_id = $conn->insert_id;

/*
    Add products to order_items
*/

foreach ($cart as $product_id => $quantity) {

    $sql = "SELECT * FROM products WHERE product_id = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $product_id);
    $stmt->execute();

    $result = $stmt->get_result();

    if ($result->num_rows == 1) {

        $product = $result->fetch_assoc();

        $price = $product["price"];

        $sql = "INSERT INTO order_items
                (order_id, product_id, quantity, price)
                VALUES (?, ?, ?, ?)";

        $item_stmt = $conn->prepare($sql);

        $item_stmt->bind_param(
            "iiid",
            $order_id,
            $product_id,
            $quantity,
            $price
        );

        $item_stmt->execute();

        /*
            Reduce product stock
        */

        $new_quantity = $product["quantity"] - $quantity;

        $update_sql = "UPDATE products
                       SET quantity = ?
                       WHERE product_id = ?";

        $update_stmt = $conn->prepare($update_sql);

        $update_stmt->bind_param(
            "ii",
            $new_quantity,
            $product_id
        );

        $update_stmt->execute();
    }
}

/*
    Empty cart
*/

unset($_SESSION["cart"]);

?>

<!DOCTYPE html>
<html>

<head>
    <title>Order Successful - Gauley Ko Pasal</title>
</head>

<body>

    <h1>Order Placed Successfully!</h1>

    <p>Thank you for your order.</p>

    <p>
        Your Order ID:
        <strong>#<?php echo $order_id; ?></strong>
    </p>

    <p>
        Total Amount:
        <strong>Rs. <?php echo $total; ?></strong>
    </p>

    <br>

    <a href="index.php">Continue Shopping</a>

</body>

</html>