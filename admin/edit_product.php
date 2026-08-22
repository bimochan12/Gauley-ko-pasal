<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

require_once "../config/database.php";

if (!isset($_GET["id"])) {
    header("Location: products.php");
    exit();
}

$product_id = $_GET["id"];

$sql = "SELECT * FROM products WHERE product_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $product_id);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows == 0) {
    header("Location: products.php");
    exit();
}

$product = $result->fetch_assoc();

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $product_name = $_POST["product_name"];
    $description = $_POST["description"];
    $price = $_POST["price"];
    $quantity = $_POST["quantity"];

    $sql = "UPDATE products
            SET product_name = ?,
                description = ?,
                price = ?,
                quantity = ?
            WHERE product_id = ?";

    $stmt = $conn->prepare($sql);

    $stmt->bind_param(
        "ssdii",
        $product_name,
        $description,
        $price,
        $quantity,
        $product_id
    );

    if ($stmt->execute()) {
        header("Location: products.php");
        exit();
    }
}

?>

<!DOCTYPE html>
<html>

<head>
    <title>Edit Product</title>
</head>

<body>

    <h1>Edit Product</h1>

    <form method="POST">

        <label>Product Name:</label>
        <br>
        <input
            type="text"
            name="product_name"
            value="<?php echo $product["product_name"]; ?>"
            required
        >

        <br><br>

        <label>Description:</label>
        <br>
        <textarea name="description"><?php echo $product["description"]; ?></textarea>

        <br><br>

        <label>Price:</label>
        <br>
        <input
            type="number"
            name="price"
            step="0.01"
            value="<?php echo $product["price"]; ?>"
            required
        >

        <br><br>

        <label>Quantity:</label>
        <br>
        <input
            type="number"
            name="quantity"
            value="<?php echo $product["quantity"]; ?>"
            required
        >

        <br><br>

        <button type="submit">Update Product</button>

    </form>

    <br>

    <a href="products.php">Back to Products</a>

</body>

</html>