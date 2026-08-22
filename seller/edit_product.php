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
$message = "";


if (!isset($_GET["id"])) {

    header("Location: products.php");
    exit();
}


$product_id = (int) $_GET["id"];


/* GET PRODUCT — ONLY IF IT BELONGS TO THIS SELLER */

$sql = "SELECT *
        FROM products
        WHERE product_id = ?
        AND seller_id = ?";

$stmt = $conn->prepare($sql);

$stmt->bind_param(
    "ii",
    $product_id,
    $seller_id
);

$stmt->execute();

$result = $stmt->get_result();


if ($result->num_rows !== 1) {

    header("Location: products.php");
    exit();
}


$product = $result->fetch_assoc();


/* GET CATEGORIES */

$category_sql = "SELECT category_id, category_name
                 FROM categories
                 ORDER BY category_name ASC";

$category_result = $conn->query($category_sql);


/* UPDATE PRODUCT */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $product_name = trim($_POST["product_name"]);
    $description = trim($_POST["description"]);
    $price = $_POST["price"];
    $quantity = $_POST["quantity"];
    $category_id = $_POST["category_id"];

    $image_name = $product["image"];


    /* NEW IMAGE */

    if (
        isset($_FILES["image"]) &&
        $_FILES["image"]["error"] === UPLOAD_ERR_OK
    ) {

        $allowed_types = [
            "image/jpeg",
            "image/png",
            "image/webp"
        ];

        $file_type = $_FILES["image"]["type"];


        if (in_array($file_type, $allowed_types)) {

            $extension = pathinfo(
                $_FILES["image"]["name"],
                PATHINFO_EXTENSION
            );

            $new_image_name =
                uniqid("product_", true)
                . "."
                . $extension;

            $upload_path =
                "../uploads/" . $new_image_name;


            if (
                move_uploaded_file(
                    $_FILES["image"]["tmp_name"],
                    $upload_path
                )
            ) {

                $image_name = $new_image_name;

            }

        } else {

            $message =
                "Please upload a valid image (JPG, PNG, or WEBP).";

        }
    }


    if ($message === "") {

        $update_sql = "UPDATE products
                       SET
                           product_name = ?,
                           description = ?,
                           price = ?,
                           quantity = ?,
                           image = ?,
                           category_id = ?
                       WHERE product_id = ?
                       AND seller_id = ?";

        $update_stmt =
            $conn->prepare($update_sql);

        $update_stmt->bind_param(
            "ssdisiii",
            $product_name,
            $description,
            $price,
            $quantity,
            $image_name,
            $category_id,
            $product_id,
            $seller_id
        );


        if ($update_stmt->execute()) {

            header("Location: products.php");
            exit();

        } else {

            $message =
                "Something went wrong while updating the product.";

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
        <a href="products.php">My Products</a>
        <a href="orders.php">Orders</a>
        <a href="logout.php">Logout</a>

    </nav>

</header>


<div class="container">

    <div class="hero">

        <h2>Edit Product ✏️</h2>

        <p>
            Update your product information.
        </p>

    </div>


    <div class="card">

        <?php if ($message !== ""): ?>

            <p>
                <?php echo htmlspecialchars($message); ?>
            </p>

        <?php endif; ?>


        <form
            method="POST"
            enctype="multipart/form-data"
        >

            <label>Product Name</label>

            <input
                type="text"
                name="product_name"
                value="<?php
                    echo htmlspecialchars(
                        $product["product_name"]
                    );
                ?>"
                required
            >

            <br><br>


            <label>Description</label>

            <textarea
                name="description"
                rows="5"
            ><?php
                echo htmlspecialchars(
                    $product["description"]
                );
            ?></textarea>

            <br><br>


            <label>Price</label>

            <input
                type="number"
                name="price"
                step="0.01"
                min="0"
                value="<?php
                    echo htmlspecialchars(
                        $product["price"]
                    );
                ?>"
                required
            >

            <br><br>


            <label>Quantity</label>

            <input
                type="number"
                name="quantity"
                min="0"
                value="<?php
                    echo htmlspecialchars(
                        $product["quantity"]
                    );
                ?>"
                required
            >

            <br><br>


            <label>Category</label>

            <select
                name="category_id"
                required
            >

                <?php while (
                    $category =
                    $category_result->fetch_assoc()
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

            </select>

            <br><br>


            <?php if (!empty($product["image"])): ?>

                <p>
                    <strong>Current Image:</strong>
                </p>

                <img
                    src="../uploads/<?php
                        echo htmlspecialchars(
                            $product["image"]
                        );
                    ?>"
                    alt="Product Image"
                    style="
                        max-width: 200px;
                        max-height: 200px;
                        object-fit: cover;
                    "
                >

                <br><br>

            <?php endif; ?>


            <label>Change Image (optional)</label>

            <input
                type="file"
                name="image"
                accept=".jpg,.jpeg,.png,.webp"
            >

            <br><br>


            <button type="submit">
                Update Product
            </button>


            <a
                class="button"
                href="products.php"
            >
                Cancel
            </a>

        </form>

    </div>

</div>


<footer>

    <p>
        Gauley Ko Pasal — Seller Panel 🇳🇵
    </p>

</footer>

</body>

</html>