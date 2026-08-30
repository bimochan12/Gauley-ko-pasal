<?php

require_once "../config/database.php";

$product_id = $_GET["id"];

$sql = "SELECT * FROM products WHERE product_id = $product_id";

$result = $conn->query($sql);

if ($result->num_rows == 1) {

    $product = $result->fetch_assoc();

} else {

    echo "Product not found.";
    exit();

}

?>

<!DOCTYPE html>
<html>

<head>

    <title><?php echo $product["product_name"]; ?> - Gauley Ko Pasal</title>

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

        .price {
            font-size: 22px;
            font-weight: bold;
        }

        .available {
            color: #555;
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

    <h1>
        <?php echo $product["product_name"]; ?>
    </h1>


    <p>
        <?php echo $product["description"]; ?>
    </p>


    <p class="price">
        Rs. <?php echo $product["price"]; ?>
    </p>


    <p class="available">
        Available: <?php echo $product["quantity"]; ?>
    </p>


    <form method="POST" action="cart.php">

        <input
            type="hidden"
            name="product_id"
            value="<?php echo $product["product_id"]; ?>"
        >

        <button type="submit">
            Add to Cart
        </button>

    </form>


    <a class="back" href="products.php">
        ← Back to Products
    </a>

</div>


</body>

</html>