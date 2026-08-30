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


// Calculate total

$total = 0;

foreach ($_SESSION["cart"] as $product_id => $quantity) {

    $sql = "SELECT * FROM products WHERE product_id = $product_id";

    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {

        $product = $result->fetch_assoc();

        $total = $total + ($product["price"] * $quantity);

    }

}

?>

<!DOCTYPE html>
<html>

<head>

    <title>Checkout - Gauley Ko Pasal</title>

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
            margin: 60px auto;
            background-color: white;
            padding: 35px;
            border-radius: 8px;
            text-align: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .total {
            font-size: 22px;
            font-weight: bold;
            margin: 25px 0;
        }

        .confirm {
            margin: 20px 0;
        }

        button {
            padding: 12px 25px;
            background-color: #222;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
        }

        button:hover {
            background-color: #444;
        }

        .back {
            display: inline-block;
            margin-top: 20px;
            color: #222;
            text-decoration: none;
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

    <h1>Checkout</h1>

    <p>Please review your order before placing it.</p>


    <div class="total">

        Total Amount:
        Rs. <?php echo number_format($total, 2); ?>

    </div>


    <div class="confirm">

        <p>
            Are you sure you want to place this order?
        </p>


        <form method="POST" action="place_order.php">

            <button type="submit">
                Place Order
            </button>

        </form>

    </div>


    <a class="back" href="cart.php">
        ← Back to Cart
    </a>

</div>


</body>

</html>