<?php

session_start();

require_once "../config/database.php";

$cart = $_SESSION["cart"] ?? [];

$total = 0;

?>

<!DOCTYPE html>
<html>

<head>

    <title>Gauley Ko Pasal - Cart</title>

    <link rel="stylesheet" href="../style.css">

</head>

<body>

<header>

    <h1>Gauley Ko Pasal</h1>

    <nav>

        <a href="index.php">Home</a>

        <a href="cart.php">Cart</a>

        <?php if (isset($_SESSION["user_id"])): ?>

            <a href="logout.php">Logout</a>

        <?php else: ?>

            <a href="login.php">Login</a>

        <?php endif; ?>

    </nav>

</header>


<div class="container">

    <div class="hero">

        <h2>Your Shopping Cart 🛒</h2>

        <p>Review your items before checkout.</p>

    </div>


    <?php if (empty($cart)): ?>

        <div class="card">

            <h3>Your cart is empty.</h3>

            <p>Add some products from our shop.</p>

            <a class="button" href="index.php">
                Continue Shopping
            </a>

        </div>

    <?php else: ?>

        <?php foreach ($cart as $product_id => $quantity): ?>

            <?php

            $sql = "SELECT * FROM products WHERE product_id = ?";

            $stmt = $conn->prepare($sql);

            $stmt->bind_param("i", $product_id);

            $stmt->execute();

            $result = $stmt->get_result();

            $product = $result->fetch_assoc();

            if (!$product) {
                continue;
            }

            $subtotal = $product["price"] * $quantity;

            $total += $subtotal;

            ?>

            <div class="card" style="margin-bottom: 20px;">

                <h3>
                    <?php echo htmlspecialchars($product["product_name"]); ?>
                </h3>

                <p>
                    <?php echo htmlspecialchars($product["description"]); ?>
                </p>

                <p class="price">
                    Rs. <?php echo number_format($product["price"], 2); ?>
                </p>

                <p>
                    Quantity:
                    <strong><?php echo $quantity; ?></strong>
                </p>

                <p>
                    Subtotal:
                    <strong>
                        Rs. <?php echo number_format($subtotal, 2); ?>
                    </strong>
                </p>

            </div>

        <?php endforeach; ?>


        <div class="card">

            <h2>
                Total:
                Rs. <?php echo number_format($total, 2); ?>
            </h2>

            <a class="button" href="checkout.php">
                Proceed to Checkout
            </a>

            <a class="button" href="index.php">
                Continue Shopping
            </a>

        </div>

    <?php endif; ?>

</div>


<footer>

    <p>
        © <?php echo date("Y"); ?> Gauley Ko Pasal
    </p>

</footer>

</body>

</html>