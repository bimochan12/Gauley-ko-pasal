<?php

require_once "../config/database.php";

$sql = "SELECT * FROM products";

$result = $conn->query($sql);

?>

<!DOCTYPE html>
<html>

<head>

    <title>Products - Gauley Ko Pasal</title>

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
            width: 90%;
            margin: 40px auto;
        }

        h1 {
            text-align: center;
        }

        .products {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            justify-content: center;
        }

        .product {
            background-color: white;
            width: 220px;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .product h3 {
            margin-top: 0;
        }

        .price {
            font-weight: bold;
            font-size: 18px;
        }

        .available {
            color: #555;
        }

        .button {
            display: inline-block;
            padding: 10px 15px;
            background-color: #222;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 10px;
        }

        .button:hover {
            background-color: #444;
        }

        .bottom {
            text-align: center;
            margin-top: 30px;
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

    <h1>Our Products</h1>

    <div class="products">


<?php

if ($result->num_rows > 0) {

    while ($product = $result->fetch_assoc()) {

?>

        <div class="product">

            <h3>
                <?php echo $product["product_name"]; ?>
            </h3>

            <p>
                <?php echo $product["description"]; ?>
            </p>

            <p class="price">
                Rs. <?php echo $product["price"]; ?>
            </p>

            <p class="available">
                Available: <?php echo $product["quantity"]; ?>
            </p>

            <a
                class="button"
                href="product.php?id=<?php echo $product["product_id"]; ?>"
            >
                View Product
            </a>

        </div>


<?php

    }

} else {

    echo "<p>No products available.</p>";

}

?>

    </div>


    <div class="bottom">

        <a href="index.php">Back to Home</a>

    </div>

</div>


</body>

</html>