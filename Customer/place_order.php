<?php

session_start();

require_once "../config/database.php";


// Check if user is logged in

if (!isset($_SESSION["user_id"])) {

    echo "Please login first.";
    exit();

}


// Check if cart is empty

if (!isset($_SESSION["cart"]) || empty($_SESSION["cart"])) {

    echo "Your cart is empty.";
    exit();

}


$user_id = $_SESSION["user_id"];

$total = 0;


// Calculate total

foreach ($_SESSION["cart"] as $product_id => $quantity) {

    $sql = "SELECT * FROM products WHERE product_id = $product_id";

    $result = $conn->query($sql);

    if ($result && $result->num_rows == 1) {

        $product = $result->fetch_assoc();

        $total = $total + ($product["price"] * $quantity);

    }

}


// Create order

$sql = "INSERT INTO orders (user_id, total_amount)
        VALUES ($user_id, $total)";


if ($conn->query($sql) === TRUE) {

    $order_id = $conn->insert_id;

} else {

    echo "Error creating order: " . $conn->error;
    exit();

}


// Add products to order_items

foreach ($_SESSION["cart"] as $product_id => $quantity) {

    $sql = "SELECT * FROM products WHERE product_id = $product_id";

    $result = $conn->query($sql);

    if ($result && $result->num_rows == 1) {

        $product = $result->fetch_assoc();

        $price = $product["price"];


        $sql = "INSERT INTO order_items
                (order_id, product_id, quantity, price)
                VALUES ($order_id, $product_id, $quantity, $price)";

        $conn->query($sql);

    }

}


// Empty cart after successful order

$_SESSION["cart"] = [];

?>

<!DOCTYPE html>
<html>

<head>

    <title>Order Successful - Gauley Ko Pasal</title>

    <style>

        body {
            font-family: Arial, sans-serif;
            margin: 0;
            background-color: #f5f5f5;
        }

        nav {
            background-color: #222;
            padding: 20px;
        }

        nav a {
            color: white;
            text-decoration: none;
            margin-right: 20px;
        }

        .container {
            width: 500px;
            margin: 70px auto;
            background-color: white;
            padding: 40px;
            border-radius: 8px;
            text-align: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .success {
            font-size: 28px;
            margin-bottom: 25px;
        }

        .details {
            text-align: left;
            background-color: #f5f5f5;
            padding: 20px;
            border-radius: 5px;
            margin: 25px 0;
        }

        .button {
            display: inline-block;
            padding: 12px 18px;
            margin: 5px;
            background-color: #222;
            color: white;
            text-decoration: none;
            border-radius: 5px;
        }

        .button:hover {
            background-color: #444;
        }

    </style>

</head>

<body>


<nav>

    <a href="../index.php">Gauley Ko Pasal</a>

    <a href="products.php">Products</a>

    <a href="cart.php">Cart</a>

    <a href="my_orders.php">My Orders</a>

</nav>


<div class="container">

    <div class="success">
        Order Placed Successfully!
    </div>


    <p>
        Thank you for shopping at Gauley Ko Pasal.
    </p>


    <div class="details">

        <p>
            <strong>Order ID:</strong>
            <?php echo $order_id; ?>
        </p>

        <p>
            <strong>Total Amount:</strong>
            Rs. <?php echo number_format($total, 2); ?>
        </p>

        <p>
            <strong>Order Status:</strong>
            Pending
        </p>

    </div>


    <a class="button" href="my_orders.php">
        View My Orders
    </a>


    <a class="button" href="products.php">
        Continue Shopping
    </a>


    <br><br>


    <a href="index.php">
        Back to Home
    </a>

</div>


</body>

</html>