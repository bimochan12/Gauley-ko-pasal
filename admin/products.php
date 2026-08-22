# `admin/products.php`

```php
<?php

session_start();

require_once "../config/database.php";

if (
    !isset($_SESSION["user_id"]) ||
    !isset($_SESSION["role"]) ||
    $_SESSION["role"] !== "admin"
) {
    header("Location: login.php");
    exit();
}

$message = "";


/* =========================
   DELETE PRODUCT
========================= */

if (isset($_GET["delete"])) {

    $product_id = (int) $_GET["delete"];

    /*
     * Check whether this product is already
     * connected to an existing order.
     */

    $check_stmt = $conn->prepare(
        "SELECT COUNT(*) AS total
         FROM order_items
         WHERE product_id = ?"
    );

    $check_stmt->bind_param(
        "i",
        $product_id
    );

    $check_stmt->execute();

    $check_result = $check_stmt->get_result();

    $order_count =
        $check_result->fetch_assoc()["total"];


    if ($order_count > 0) {

        $message =
            "Cannot delete this product because it has existing orders.";

    } else {

        /*
         * Get product image before deleting.
         */

        $stmt = $conn->prepare(
            "SELECT image
             FROM products
             WHERE product_id = ?"
        );

        $stmt->bind_param(
            "i",
            $product_id
        );

        $stmt->execute();

        $result = $stmt->get_result();


        if ($result->num_rows > 0) {

            $product =
                $result->fetch_assoc();


            /*
             * Delete image from uploads folder.
             */

            if (!empty($product["image"])) {

                $image_path =
                    "../uploads/"
                    . $product["image"];

                if (file_exists($image_path)) {

                    unlink($image_path);

                }
            }
        }


        /*
         * Delete product.
         */

        $stmt = $conn->prepare(
            "DELETE FROM products
             WHERE product_id = ?"
        );

        $stmt->bind_param(
            "i",
            $product_id
        );


        if ($stmt->execute()) {

            $message =
                "Product deleted successfully.";

        } else {

            $message =
                "Could not delete product.";

        }
    }
}


/* =========================
   UPDATE PRODUCT
========================= */

if (
    $_SERVER["REQUEST_METHOD"] === "POST"
    && isset($_POST["update_product"])
) {

    $product_id =
        (int) $_POST["product_id"];

    $product_name =
        trim($_POST["product_name"]);

    $description =
        trim($_POST["description"]);

    $price =
        (float) $_POST["price"];

    $quantity =
        (int) $_POST["quantity"];

    $category_id =
        (int) $_POST["category_id"];


    /*
     * Get current product image.
     */

    $stmt = $conn->prepare(
        "SELECT image
         FROM products
         WHERE product_id = ?"
    );

    $stmt->bind_param(
        "i",
        $product_id
    );

    $stmt->execute();

    $result =
        $stmt->get_result();


    if ($result->num_rows == 0) {

        $message =
            "Product not found.";

    } else {

        $product =
            $result->fetch_assoc();

        $image_name =
            $product["image"];


        /*
         * Validate product information.
         */

        if (
            $product_name === ""
            || $price < 0
            || $quantity < 0
            || $category_id <= 0
        ) {

            $message =
                "Please enter valid product information.";

        }


        /*
         * New image upload.
         */

        if (
            $message === ""
            && isset($_FILES["image"])
            && $_FILES["image"]["error"] === UPLOAD_ERR_OK
        ) {

            $allowed_types = [
                "image/jpeg",
                "image/png",
                "image/webp"
            ];

            $file_type =
                $_FILES["image"]["type"];


            if (
                !in_array(
                    $file_type,
                    $allowed_types
                )
            ) {

                $message =
                    "Only JPG, PNG and WEBP images are allowed.";

            } else {

                $extension =
                    strtolower(
                        pathinfo(
                            $_FILES["image"]["name"],
                            PATHINFO_EXTENSION
                        )
                    );


                $new_image_name =
                    uniqid(
                        "product_",
                        true
                    )
                    . "."
                    . $extension;


                $upload_path =
                    "../uploads/"
                    . $new_image_name;


                if (
                    move_uploaded_file(
                        $_FILES["image"]["tmp_name"],
                        $upload_path
                    )
                ) {

                    /*
                     * Delete old image.
                     */

                    if (!empty($image_name)) {

                        $old_path =
                            "../uploads/"
                            . $image_name;

                        if (
                            file_exists($old_path)
                        ) {

                            unlink($old_path);

                        }
                    }


                    $image_name =
                        $new_image_name;

                } else {

                    $message =
                        "New image upload failed.";

                }
            }
        }


        /*
         * Update database.
         */

        if ($message === "") {

            $stmt = $conn->prepare(
                "UPDATE products
                 SET product_name = ?,
                     description = ?,
                     image = ?,
                     price = ?,
                     quantity = ?,
                     category_id = ?
                 WHERE product_id = ?"
            );


            $stmt->bind_param(
                "sssdiii",
                $product_name,
                $description,
                $image_name,
                $price,
                $quantity,
                $category_id,
                $product_id
            );


            if ($stmt->execute()) {

                $message =
                    "Product updated successfully.";

            } else {

                $message =
                    "Could not update product.";

            }
        }
    }
}


/* =========================
   GET CATEGORIES
========================= */

$category_result = $conn->query(
    "SELECT category_id, category_name
     FROM categories
     ORDER BY category_name ASC"
);


/* =========================
   GET PRODUCTS
========================= */

$result = $conn->query(
    "SELECT
        p.product_id,
        p.product_name,
        p.description,
        p.price,
        p.quantity,
        p.image,
        p.category_id,
        c.category_name
     FROM products p
     LEFT JOIN categories c
        ON p.category_id = c.category_id
     ORDER BY p.product_id DESC"
);

?>

<!DOCTYPE html>
<html>

<head>

    <title>
        Products - Gauley Ko Pasal
    </title>

    <link
        rel="stylesheet"
        href="../style.css"
    >

</head>

<body>


<header>

    <h1>
        Gauley Ko Pasal
    </h1>

    <nav>

        <a href="dashboard.php">
            Dashboard
        </a>

        <a href="products.php">
            Products
        </a>

        <a href="categories.php">
            Categories
        </a>

        <a href="orders.php">
            Orders
        </a>

        <a href="users.php">
            Users
        </a>

        <a href="logout.php">
            Logout
        </a>

    </nav>

</header>


<div class="container">


    <div class="hero">

        <h2>
            Manage Products 📦
        </h2>

        <p>
            Add products and upload their photos
            directly from your device.
        </p>

    </div>


    <?php if ($message !== ""): ?>

        <div class="card">

            <p>
                <?php
                echo htmlspecialchars(
                    $message
                );
                ?>
            </p>

        </div>

    <?php endif; ?>


    <!-- =========================
         ADD PRODUCT
    ========================== -->

    <div class="card">

        <h2>
            Add New Product
        </h2>

        <form
            method="POST"
            action="add_product.php"
            enctype="multipart/form-data"
        >

            <label>
                Product Name
            </label>

            <input
                type="text"
                name="product_name"
                required
            >

            <br><br>


            <label>
                Description
            </label>

            <textarea
                name="description"
                rows="4"
            ></textarea>

            <br><br>


            <label>
                Price
            </label>

            <input
                type="number"
                name="price"
                step="0.01"
                min="0"
                required
            >

            <br><br>


            <label>
                Quantity
            </label>

            <input
                type="number"
                name="quantity"
                min="0"
                required
            >

            <br><br>


            <label>
                Category
            </label>

            <select
                name="category_id"
                required
            >

                <option value="">
                    -- Select Category --
                </option>

                <?php

                if ($category_result):

                    while (
                        $category =
                        $category_result->fetch_assoc()
                    ):

                ?>

                    <option
                        value="<?php
                        echo $category["category_id"];
                        ?>"
                    >

                        <?php
                        echo htmlspecialchars(
                            $category["category_name"]
                        );
                        ?>

                    </option>

                <?php

                    endwhile;

                endif;

                ?>

            </select>

            <br><br>


            <label>
                Product Image
            </label>

            <input
                type="file"
                name="image"
                accept=".jpg,.jpeg,.png,.webp"
            >

            <br><br>


            <button type="submit">
                Add Product
            </button>

        </form>

    </div>


    <br>


    <!-- =========================
         ALL PRODUCTS
    ========================== -->

    <div class="card">

        <h2>
            All Products
        </h2>

    </div>


    <?php if (
        $result
        && $result->num_rows > 0
    ): ?>


        <?php while (
            $product =
            $result->fetch_assoc()
        ): ?>


            <div class="card">


                <!-- PRODUCT IMAGE -->

                <?php if (
                    !empty($product["image"])
                ): ?>

                    <img
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
                        style="
                            max-width: 200px;
                            max-height: 200px;
                            object-fit: contain;
                        "
                    >

                <?php else: ?>

                    <p>
                        No Image
                    </p>

                <?php endif; ?>


                <h2>

                    <?php
                    echo htmlspecialchars(
                        $product["product_name"]
                    );
                    ?>

                </h2>


                <p>

                    <?php
                    echo htmlspecialchars(
                        $product["description"]
                    );
                    ?>

                </p>


                <p>

                    <strong>

                        Rs.

                        <?php
                        echo number_format(
                            $product["price"],
                            2
                        );
                        ?>

                    </strong>

                </p>


                <p>

                    Stock:

                    <strong>

                        <?php
                        echo $product["quantity"];
                        ?>

                    </strong>

                </p>


                <p>

                    Category:

                    <strong>

                        <?php

                        if (
                            !empty(
                                $product["category_name"]
                            )
                        ) {

                            echo htmlspecialchars(
                                $product["category_name"]
                            );

                        } else {

                            echo "No Category";

                        }

                        ?>

                    </strong>

                </p>


                <!-- =========================
                     EDIT PRODUCT
                ========================== -->

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
                            name="update_product"
                            value="1"
                        >

                        <input
                            type="hidden"
                            name="product_id"
                            value="<?php
                            echo $product["product_id"];
                            ?>"
                        >


                        <label>
                            Product Name
                        </label>

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


                        <label>
                            Description
                        </label>

                        <textarea
                            name="description"
                            rows="4"
                        ><?php
                        echo htmlspecialchars(
                            $product["description"]
                        );
                        ?></textarea>

                        <br><br>


                        <label>
                            Price
                        </label>

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


                        <label>
                            Quantity
                        </label>

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
                            Category
                        </label>

                        <select
                            name="category_id"
                            required
                        >

                            <option value="">
                                -- Select Category --
                            </option>


                            <?php

                            $edit_categories =
                                $conn->query(
                                    "SELECT
                                        category_id,
                                        category_name
                                     FROM categories
                                     ORDER BY category_name ASC"
                                );


                            while (
                                $category =
                                $edit_categories->fetch_assoc()
                            ):

                            ?>

                                <option
                                    value="<?php
                                    echo $category[
                                        "category_id"
                                    ];
                                    ?>"
                                    <?php

                                    if (
                                        $product[
                                            "category_id"
                                        ]
                                        ==
                                        $category[
                                            "category_id"
                                        ]
                                    ) {

                                        echo "selected";

                                    }

                                    ?>
                                >

                                    <?php
                                    echo htmlspecialchars(
                                        $category[
                                            "category_name"
                                        ]
                                    );
                                    ?>

                                </option>

                            <?php endwhile; ?>

                        </select>

                        <br><br>


                        <label>
                            Replace Product Image
                        </label>

                        <input
                            type="file"
                            name="image"
                            accept=".jpg,.jpeg,.png,.webp"
                        >

                        <br><br>


                        <button type="submit">
                            Save Changes
                        </button>

                    </form>

                </details>


                <br>


                <!-- =========================
                     DELETE PRODUCT
                ========================== -->

                <a
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


    <?php else: ?>


        <div class="card">

            <p>
                No products found.
            </p>

        </div>


    <?php endif; ?>


</div>


<footer>

    <p>

        © <?php echo date("Y"); ?>

        Gauley Ko Pasal

    </p>

</footer>


</body>

</html>
```
