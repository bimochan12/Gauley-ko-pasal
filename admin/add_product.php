<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

require_once "../config/database.php";

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $product_name = $_POST["product_name"];
    $description = $_POST["description"];
    $price = $_POST["price"];
    $quantity = $_POST["quantity"];

    $seller_id = $_SESSION["user_id"];

    $sql = "INSERT INTO products 
            (product_name, description, price, quantity, seller_id)
            VALUES (?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);

    $stmt->bind_param(
        "ssdii",
        $product_name,
        $description,
        $price,
        $quantity,
        $seller_id
    );

    if ($stmt->execute()) {
        $message = "Product added successfully!";
    } else {
        $message = "Error adding product.";
    }

    $stmt->close();
}

?>

<!DOCTYPE html>
<html>

<head>
    <title>Gauley Ko Pasal - Add Product</title>
</head>

<body>

    <h1>Add Product</h1>

    <?php if ($message != ""): ?>
        <p><?php echo $message; ?></p>
    <?php endif; ?>

    <form method="POST">

        <label>Product Name:</label>
        <br>
        <input type="text" name="product_name" required>

        <br><br>

        <label>Description:</label>
        <br>
        <textarea name="description"></textarea>

        <br><br>

        <label>Price:</label>
        <br>
        <input type="number" name="price" step="0.01" required>

        <br><br>

        <label>Quantity:</label>
        <br>
        <input type="number" name="quantity" min="0" required>

        <br><br>

        <button type="submit">Add Product</button>

    </form>

    <br>

    <a href="products.php">Back to Products</a>

</body>

</html>