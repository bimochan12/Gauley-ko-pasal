<?php

session_start();

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] != "admin") {
    header("Location: login.php");
    exit();
}

require_once "../config/database.php";

$message = "";

/* =========================
   DELETE PRODUCT
========================= */

if (isset($_GET["delete"])) {

    $product_id = (int) $_GET["delete"];

    // Get image filename first
    $stmt = $conn->prepare(
        "SELECT image FROM products WHERE product_id = ?"
    );

    $stmt->bind_param("i", $product_id);
    $stmt->execute();

    $result = $stmt->get_result();
    $product = $result->fetch_assoc();

    if ($product) {

        // Delete product from database
        $stmt = $conn->prepare(
            "DELETE FROM products WHERE product_id = ?"
        );

        $stmt->bind_param("i", $product_id);

        if ($stmt->execute()) {

            // Delete uploaded image
            if (!empty($product["image"])) {

                $image_path = "../uploads/" . $product["image"];

                if (file_exists($image_path)) {
                    unlink($image_path);
                }
            }

            $message = "Product deleted successfully.";

        } else {

            $message = "Could not delete product.";
        }
    }
}


/* =========================
   ADD PRODUCT
========================= */

if (
    $_SERVER["REQUEST_METHOD"] == "POST"
    && isset($_POST["action"])
    && $_POST["action"] == "add"
) {

    $product_name = trim($_POST["product_name"]);
    $description = trim($_POST["description"]);
    $price = (float) $_POST["price"];
    $quantity = (int) $_POST["quantity"];

    $image_name = "";


    if (
        $product_name == ""
        || $price < 0
        || $quantity < 0
    ) {

        $message = "Please enter valid product information.";

    } else {

        /* IMAGE UPLOAD */

        if (
            isset($_FILES["image"])
            && $_FILES["image"]["error"] == 0
        ) {

            $allowed_types = [
                "image/jpeg",
                "image/png",
                "image/webp"
            ];

            $file_type = $_FILES["image"]["type"];

            if (!in_array($file_type, $allowed_types)) {

                $message = "Only JPG, PNG and WEBP images are allowed.";

            } else {

                $extension = pathinfo(
                    $_FILES["image"]["name"],
                    PATHINFO_EXTENSION
                );

                $image_name =
                    uniqid("product_", true)
                    . "."
                    . strtolower($extension);

                $upload_path =
                    "../uploads/"
                    . $image_name;

                if (
                    !move_uploaded_file(
                        $_FILES["image"]["tmp_name"],
                        $upload_path
                    )
                ) {

                    $message = "Image upload failed.";
                    $image_name = "";
                }
            }
        }


        /* INSERT PRODUCT */

        if ($message == "") {

            $stmt = $conn->prepare(
                "INSERT INTO products
                (product_name, description, image, price, quantity)
                VALUES (?, ?, ?, ?, ?)"
            );

            $stmt->bind_param(
                "sssdi",
                $product_name,
                $description,
                $image_name,
                $price,
                $quantity
            );

            if ($stmt->execute()) {

                $message = "Product added successfully.";

            } else {

                $message = "Could not add product.";

            }
        }
    }
}


/* =========================
   UPDATE PRODUCT
========================= */

if (
    $_SERVER["REQUEST_METHOD"] == "POST"
    && isset($_POST["action"])
    && $_POST["action"] == "update"
) {

    $product_id = (int) $_POST["product_id"];

    $product_name = trim($_POST["product_name"]);
    $description = trim($_POST["description"]);
    $price = (float) $_POST["price"];
    $quantity = (int) $_POST["quantity"];


    if (
        $product_name == ""
        || $price < 0
        || $quantity < 0
    ) {

        $message = "Please enter valid product information.";

    } else {

        /* GET OLD IMAGE */

        $stmt = $conn->prepare(
            "SELECT image FROM products WHERE product_id = ?"
        );

        $stmt->bind_param("i", $product_id);
        $stmt->execute();

        $result = $stmt->get_result();
        $old_product = $result->fetch_assoc();

        $image_name = $old_product["image"] ?? "";


        /* NEW IMAGE */

        if (
            isset($_FILES["image"])
            && $_FILES["image"]["error"] == 0
        ) {

            $allowed_types = [
                "image/jpeg",
                "image/png",
                "image/webp"
            ];

            $file_type = $_FILES["image"]["type"];

            if (!in_array($file_type, $allowed_types)) {

                $message =
                    "Only JPG, PNG and WEBP images are allowed.";

            } else {

                $extension = pathinfo(
                    $_FILES["image"]["name"],
                    PATHINFO_EXTENSION
                );

                $new_image_name =
                    uniqid("product_", true)
                    . "."
                    . strtolower($extension);

                $upload_path =
                    "../uploads/"
                    . $new_image_name;

                if (
                    move_uploaded_file(
                        $_FILES["image"]["tmp_name"],
                        $upload_path
                    )
                ) {

                    // Delete old image
                    if (!empty($image_name)) {

                        $old_path =
                            "../uploads/"
                            . $image_name;

                        if (file_exists($old_path)) {
                            unlink($old_path);
                        }
                    }

                    $image_name = $new_image_name;

                } else {

                    $message = "New image upload failed.";
                }
            }
        }


        /* UPDATE DATABASE */

        if ($message == "") {

            $stmt = $conn->prepare(
                "UPDATE products
                 SET product_name = ?,
                     description = ?,
                     image = ?,
                     price = ?,
                     quantity = ?
                 WHERE product_id = ?"
            );

            $stmt->bind_param(
                "sss dii",
                $product_name,
                $description,
                $image_name,
                $price,
                $quantity,
                $product_id
            );

            /*
             * Fix spacing in bind types manually:
             * s = string
             * s = string
             * s = string
             * d = decimal
             * i = integer
             * i = integer
             */

            $stmt = $conn->prepare(
                "UPDATE products
                 SET product_name = ?,
                     description = ?,
                     image = ?,
                     price = ?,
                     quantity = ?
                 WHERE product_id = ?"
            );

            $stmt->bind_param(
                "sssdii",
                $product_name,
                $description,
                $image_name,
                $price,
                $quantity,
                $product_id
            );

            if ($stmt->execute()) {

                $message = "Product updated successfully.";

            } else {

                $message = "Could not update product.";
            }
        }
    }
}


/* =========================
   GET PRODUCTS
========================= */

$result = $conn->query(
    "SELECT *
     FROM products
     ORDER BY product_id DESC"
);

?>

<!DOCTYPE html>

<html>

<head>

    <title>Gauley Ko Pasal - Products</title>

    <link rel="stylesheet" href="../style.css">

    <style>

        .product-image {
            width: 100%;
            height: 180px;
            object-fit: contain;
            background: #f5f1e8;
            border-radius: 8px;
            margin-bottom: 15px;
        }

        .no-image {
            width: 100%;
            height: 180px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f5f1e8;
            border-radius: 8px;
            margin-bottom: 15px;
            color: #777;
        }

    </style>

</head>

<body>

<header>

    <h1>Gauley Ko Pasal</h1>

    <nav>

        <a href="dashboard.php">Dashboard</a>

        <a href="products.php">Products</a>

        <a href="orders.php">Orders</a>

        <a href="logout.php">Logout</a>

    </nav>

</header>


<div class="container">

    <div class="hero">

        <h2>Manage Products 📦</h2>

        <p>
            Add products and upload their photos directly from your device.
        </p>

    </div>


    <?php if ($message != ""): ?>

        <div class="card">

            <p>
                <strong>
                    <?php echo htmlspecialchars($message); ?>
                </strong>
            </p>

        </div>

        <br>

    <?php endif; ?>


    <!-- ADD PRODUCT -->

    <div class="card">

        <h2>Add New Product</h2>

        <form
            method="POST"
            enctype="multipart/form-data"
        >

            <input
                type="hidden"
                name="action"
                value="add"
            >


            <label>Product Name</label>

            <input
                type="text"
                name="product_name"
                placeholder="Example: Coca Cola"
                required
            >

            <br><br>


            <label>Description</label>

            <textarea
                name="description"
                placeholder="Product description"
            ></textarea>

            <br><br>


            <label>Price</label>

            <input
                type="number"
                name="price"
                step="0.01"
                min="0"
                required
            >

            <br><br>


            <label>Quantity</label>

            <input
                type="number"
                name="quantity"
                min="0"
                required
            >

            <br><br>


            <label>Product Image</label>

            <input
                type="file"
                name="image"
                accept="image/jpeg,image/png,image/webp"
            >

            <br><br>


            <button type="submit">
                Add Product
            </button>

        </form>

    </div>


    <br><br>


    <h2>All Products</h2>


    <?php if ($result->num_rows == 0): ?>

        <div class="card">

            <p>
                No products found.
            </p>

        </div>

    <?php else: ?>

        <div class="products">

            <?php while ($product = $result->fetch_assoc()): ?>

                <div class="product-card">


                    <?php if (!empty($product["image"])): ?>

                        <img
                            class="product-image"
                            src="../uploads/<?php
                            echo htmlspecialchars(
                                $product["image"]
                            );
                            ?>"
                            alt="<?php
                            echo htmlspecialchars(
                                $product["product_name"]
                            );
                            ?>"
                        >

                    <?php else: ?>

                        <div class="no-image">
                            No Image
                        </div>

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


                    <p class="price">

                        Rs.
                        <?php
                        echo number_format(
                            $product["price"],
                            2
                        );
                        ?>

                    </p>


                    <p class="stock">

                        Stock:
                        <strong>
                            <?php
                            echo $product["quantity"];
                            ?>
                        </strong>

                    </p>


                    <details>

                        <summary>
                            Edit Product
                        </summary>

                        <br>


                        <form
                            method="POST"
                            enctype="multipart/form-data"
                        >

                            <input
                                type="hidden"
                                name="action"
                                value="update"
                            >

                            <input
                                type="hidden"
                                name="product_id"
                                value="<?php
                                echo $product["product_id"];
                                ?>"
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
                                echo $product["price"];
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
                                echo $product["quantity"];
                                ?>"
                                required
                            >

                            <br><br>


                            <label>
                                Replace Product Image
                            </label>

                            <input
                                type="file"
                                name="image"
                                accept="image/jpeg,image/png,image/webp"
                            >

                            <br><br>


                            <button type="submit">
                                Save Changes
                            </button>

                        </form>

                    </details>


                    <br>


                    <a
                        class="button"
                        href="products.php?delete=<?php
                        echo $product["product_id"];
                        ?>"
                        onclick="return confirm(
                            'Are you sure you want to delete this product?'
                        );"
                    >
                        Delete Product
                    </a>

                </div>

            <?php endwhile; ?>

        </div>

    <?php endif; ?>

</div>


<footer>

    <p>
        Gauley Ko Pasal — Admin Panel 🇳🇵
    </p>

</footer>

</body>

</html>