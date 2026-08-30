<?php

session_start();

require_once "../config/database.php";


/* Create cart if it does not exist */

if (!isset($_SESSION["cart"])) {
    $_SESSION["cart"] = [];
}


/* Add product to cart */

if (isset($_POST["product_id"])) {

    $product_id = $_POST["product_id"];

    if (isset($_SESSION["cart"][$product_id])) {

        $_SESSION["cart"][$product_id] =
            $_SESSION["cart"][$product_id] + 1;

    } else {

        $_SESSION["cart"][$product_id] = 1;

    }

}


/* Increase / Decrease / Remove */

if (isset($_GET["action"]) && isset($_GET["id"])) {

    $action = $_GET["action"];
    $product_id = $_GET["id"];


    /* Increase */

    if ($action == "increase") {

        if (isset($_SESSION["cart"][$product_id])) {

            $_SESSION["cart"][$product_id] =
                $_SESSION["cart"][$product_id] + 1;

        }

    }


    /* Decrease */

    if ($action == "decrease") {

        if (isset($_SESSION["cart"][$product_id])) {

            $_SESSION["cart"][$product_id] =
                $_SESSION["cart"][$product_id] - 1;


            if ($_SESSION["cart"][$product_id] <= 0) {

                unset($_SESSION["cart"][$product_id]);

            }

        }

    }


    /* Remove */

    if ($action == "remove") {

        if (isset($_SESSION["cart"][$product_id])) {

            unset($_SESSION["cart"][$product_id]);

        }

    }

}

?>

<!DOCTYPE html>
<html>

<head>

    <title>Cart - Gauley Ko Pasal</title>


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
            width: 80%;
            max-width: 700px;
            margin: 40px auto;
        }


        h1 {
            text-align: center;
        }


        .cart-item {
            background-color: white;
            padding: 20px;
            margin-bottom: 15px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }


        .cart-item h3 {
            margin-top: 0;
        }


        .quantity {
            margin: 15px 0;
        }


        .quantity a {
            display: inline-block;
            padding: 5px 10px;
            background-color: #222;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            margin: 0 5px;
        }


        .remove {
            color: #c00;
            text-decoration: none;
        }


        .total {
            background-color: white;
            padding: 20px;
            margin-top: 20px;
            border-radius: 8px;
            text-align: right;
        }


        .buttons {
            text-align: center;
            margin-top: 25px;
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


        .empty {
            background-color: white;
            padding: 30px;
            text-align: center;
            border-radius: 8px;
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


    <h1>Your Cart</h1>


<?php

/* Check if cart is empty */

if (empty($_SESSION["cart"])) {

?>

    <div class="empty">

        <h2>Your cart is empty.</h2>

        <a class="button" href="products.php">
            Start Shopping
        </a>

    </div>

<?php

} else {

    $total = 0;


    /* Display products */

    foreach ($_SESSION["cart"] as $product_id => $quantity) {

        $sql = "SELECT * FROM products WHERE product_id = $product_id";

        $result = $conn->query($sql);


        if ($result && $result->num_rows > 0) {

            $product = $result->fetch_assoc();

            $price = $product["price"];

            $subtotal = $price * $quantity;

            $total = $total + $subtotal;

?>


    <div class="cart-item">

        <h3>
            <?php echo $product["product_name"]; ?>
        </h3>


        <p>
            Price:
            Rs. <?php echo number_format($price, 2); ?>
        </p>


        <div class="quantity">

            <strong>Quantity:</strong>

            <a href="cart.php?action=decrease&id=<?php echo $product_id; ?>">
                -
            </a>

            <?php echo $quantity; ?>

            <a href="cart.php?action=increase&id=<?php echo $product_id; ?>">
                +
            </a>

        </div>


        <p>

            Subtotal:
            <strong>
                Rs. <?php echo number_format($subtotal, 2); ?>
            </strong>

        </p>


        <a
            class="remove"
            href="cart.php?action=remove&id=<?php echo $product_id; ?>"
        >
            Remove
        </a>

    </div>


<?php

        }

    }

?>


    <div class="total">

        <h2>
            Total: Rs. <?php echo number_format($total, 2); ?>
        </h2>

    </div>


    <div class="buttons">

        <a class="button" href="products.php">
            Continue Shopping
        </a>

        <a class="button" href="checkout.php">
            Proceed to Checkout
        </a>

    </div>


<?php

}

?>


    <div class="buttons">

        <a href="index.php">
            Home
        </a>

    </div>


</div>


</body>

</html>