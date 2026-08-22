<?php

session_start();

require_once "../config/database.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

if (empty($_SESSION["cart"])) {
    header("Location: cart.php");
    exit();
}

$cart = $_SESSION["cart"];

$total = 0;

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

?>

<!DOCTYPE html>
<html>

<head>

    <title>Gauley Ko Pasal - Checkout</title>

    <link rel="stylesheet" href="../style.css">

</head>

<body>

<header>

    <h1>Gauley Ko Pasal</h1>

    <nav>

        <a href="index.php">Home</a>

        <a href="cart.php">Cart</a>

        <a href="logout.php">Logout</a>

    </nav>

</header>


<div class="container">

    <div class="hero">

        <h2>Checkout 🛍️</h2>

        <p>Almost there! Enter your delivery information.</p>

    </div>


    <div class="card">

        <h2>Delivery Information</h2>

        <br>

        <form method="POST" action="place_order.php">

            <label>Name</label>

            <input
                type="text"
                name="name"
                value="<?php echo htmlspecialchars($_SESSION["name"]); ?>"
                required
            >

            <br><br>


            <label>Phone</label>

            <input
                type="text"
                name="phone"
                required
            >

            <br><br>


            <label>Delivery Address</label>

            <textarea
                name="address"
                placeholder="Enter your delivery address"
                required
            ></textarea>

            <br><br>


            <h2>
                Total:
                Rs. <?php echo number_format($total, 2); ?>
            </h2>

            <br>

            <button type="submit">
                Place Order
            </button>

        </form>

        <br>

        <a href="cart.php">
            ← Back to Cart
        </a>

    </div>

</div>


<footer>

    <p>
        © <?php echo date("Y"); ?> Gauley Ko Pasal
    </p>

    <p>
        Your friendly local online shop 🇳🇵
    </p>

</footer>

</body>

</html>