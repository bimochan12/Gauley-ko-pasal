
<?php

session_start();

if (
    !isset($_SESSION["user_id"]) ||
    !isset($_SESSION["role"]) ||
    $_SESSION["role"] !== "admin"
) {
    header("Location: login.php");
    exit();
}

require_once "../config/database.php";

if (!isset($_GET["id"])) {
    header("Location: products.php");
    exit();
}

$product_id = (int) $_GET["id"];

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

$categories = $conn->query(
    "SELECT category_id, category_name
     FROM categories
     ORDER BY category_name ASC"
);

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $product_name = trim($_POST["product_name"]);
    $description = trim($_POST["description"]);
    $price = (float) $_POST["price"];
    $quantity = (int) $_POST["quantity"];
    $category_id = (int) $_POST["category_id"];

    if (
        $product_name === "" ||
        $price < 0 ||
        $quantity < 0 ||
        $category_id <= 0
    ) {

        $message = "Please enter valid product information.";

    } else {

        $sql = "UPDATE products
                SET product_name = ?,
                    description = ?,
                    price = ?,
                    quantity = ?,
                    category_id = ?
                WHERE product_id = ?";

        $stmt = $conn->prepare($sql);

        $stmt->bind_param(
            "ssdiii",
            $product_name,
            $description,
            $price,
            $quantity,
            $category_id,
            $product_id
        );

        if ($stmt->execute()) {

            header("Location: products.php");
            exit();

        } else {

            $message = "Failed to update product.";

        }
    }
}

?>

<!DOCTYPE html>
<html>

<head>

    <title>Edit Product - Gauley Ko Pasal</title>

    <link rel="stylesheet" href="../style.css">

</head>

<body>

<header>

    <h1>Gauley Ko Pasal</h1>

    <nav>

        <a href="dashboard.php">Dashboard</a>
        <a href="products.php">Products</a>
        <a href="categories.php">Categories</a>
        <a href="orders.php">Orders</a>
        <a href="users.php">Users</a>
        <a href="logout.php">Logout</a>

    </nav>

</header>

<div class="container">

    <div class="hero">

        <h2>Edit Product</h2>

        <p>Update product information and category.</p>

    </div>

    <?php if ($message !== ""): ?>

        <div class="card">

            <p>
                <?php echo htmlspecialchars($message); ?>
            </p>

        </div>

    <?php endif; ?>

    <div class="card">

        <form method="POST">

            <label>Product Name:</label>

            <br>

            <input
                type="text"
                name="product_name"
                value="<?php
                    echo htmlspecialchars($product["product_name"]);
                ?>"
                required
            >

            <br><br>

            <label>Description:</label>

            <br>

            <textarea
                name="description"
                rows="5"
            ><?php
                echo htmlspecialchars($product["description"]);
            ?></textarea>

            <br><br>

            <label>Price:</label>

            <br>

            <input
                type="number"
                name="price"
                step="0.01"
                min="0"
                value="<?php
                    echo htmlspecialchars($product["price"]);
                ?>"
                required
            >

            <br><br>

            <label>Quantity:</label>

            <br>

            <input
                type="number"
                name="quantity"
                min="0"
                value="<?php
                    echo htmlspecialchars($product["quantity"]);
                ?>"
                required
            >

            <br><br>

            <label>Category:</label>

            <br>

            <select name="category_id" required>

                <option value="">
                    -- Select Category --
                </option>

                <?php if ($categories): ?>

                    <?php while (
                        $category = $categories->fetch_assoc()
                    ): ?>

                        <option
                            value="<?php
                                echo $category["category_id"];
                            ?>"
                            <?php
                                if (
                                    $category["category_id"]
                                    == $product["category_id"]
                                ) {
                                    echo "selected";
                                }
                            ?>
                        >

                            <?php
                                echo htmlspecialchars(
                                    $category["category_name"]
                                );
                            ?>

                        </option>

                    <?php endwhile; ?>

                <?php endif; ?>

            </select>

            <br><br>

            <button type="submit">
                Update Product
            </button>

        </form>

    </div>

    <br>

    <a href="products.php">
        ← Back to Products
    </a>

</div>

<footer>

    <p>
        © <?php echo date("Y"); ?> Gauley Ko Pasal
    </p>

</footer>

</body>

</html>
```

