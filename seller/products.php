<?php

session_start();

if (
    !isset($_SESSION["user_id"]) ||
    !isset($_SESSION["role"]) ||
    $_SESSION["role"] !== "seller"
) {
    header("Location: login.php");
    exit();
}

require_once "../config/database.php";

$seller_id = $_SESSION["user_id"];

$sql = "SELECT
            products.product_id,
            products.product_name,
            products.description,
            products.price,
            products.quantity,
            products.image,
            categories.category_name
        FROM products
        LEFT JOIN categories
            ON products.category_id = categories.category_id
        WHERE products.seller_id = ?
        ORDER BY products.product_id DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $seller_id);
$stmt->execute();

$result = $stmt->get_result();

?>

<!DOCTYPE html>
<html>

<head>

    <title>My Products - Gauley Ko Pasal</title>

    <link rel="stylesheet" href="../style.css">

</head>

<body>

<header>

    <h1>Gauley Ko Pasal</h1>

    <nav>

        <a href="dashboard.php">Dashboard</a>

        <a href="products.php">My Products</a>

        <a href="orders.php">Orders</a>

        <a href="logout.php">Logout</a>

    </nav>

</header>


<div class="container">

    <div class="hero">

        <h2>My Products 📦</h2>

        <p>
            Manage the products you have added to the store.
        </p>

    </div>


    <div style="margin-bottom: 20px;">

        <a
            class="button"
            href="add_product.php"
        >
            + Add Product
        </a>

    </div>


    <?php if ($result->num_rows > 0): ?>

        <div class="products">

            <?php while ($product = $result->fetch_assoc()): ?>

                <div class="product-card">

                    <?php if (!empty($product["image"])): ?>

                        <img
                            src="../uploads/<?php echo htmlspecialchars($product["image"]); ?>"
                            alt="<?php echo htmlspecialchars($product["product_name"]); ?>"
                            style="
                                width: 100%;
                                max-height: 200px;
                                object-fit: cover;
                            "
                        >

                    <?php endif; ?>


                    <h3>

                        <?php
                        echo htmlspecialchars(
                            $product["product_name"]
                        );
                        ?>

                    </h3>


                    <p>

                        <?php
                        echo htmlspecialchars(
                            $product["description"]
                        );
                        ?>

                    </p>


                    <p>

                        <strong>Price:</strong>

                        Rs.
                        <?php
                        echo number_format(
                            $product["price"],
                            2
                        );
                        ?>

                    </p>


                    <p>

                        <strong>Quantity:</strong>

                        <?php
                        echo htmlspecialchars(
                            $product["quantity"]
                        );
                        ?>

                    </p>


                    <p>

                        <strong>Category:</strong>

                        <?php
                        echo htmlspecialchars(
                            $product["category_name"] ?? "Uncategorized"
                        );
                        ?>

                    </p>


                    <br>


                    <a
                        class="button"
                        href="edit_product.php?id=<?php echo $product["product_id"]; ?>"
                    >
                        Edit
                    </a>


                    <a
                        class="button"
                        href="delete_product.php?id=<?php echo $product["product_id"]; ?>"
                        onclick="return confirm('Are you sure you want to delete this product?');"
                    >
                        Delete
                    </a>

                </div>

            <?php endwhile; ?>

        </div>

    <?php else: ?>

        <div class="card">

            <h3>No products yet.</h3>

            <p>
                Start by adding your first product.
            </p>

        </div>

    <?php endif; ?>


</div>


<footer>

    <p>
        Gauley Ko Pasal — Seller Panel 🇳🇵
    </p>

</footer>

</body>

</html>