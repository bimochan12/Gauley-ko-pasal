# seller/add_product.php

```php
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


$message = "";

$seller_id = (int) $_SESSION["user_id"];


/* =========================
   GET CATEGORIES
========================= */

$category_sql = "
    SELECT
        category_id,
        category_name
    FROM categories
    ORDER BY category_name ASC
";

$category_result = $conn->query(
    $category_sql
);


/* =========================
   ADD PRODUCT
========================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $product_name =
        trim($_POST["product_name"] ?? "");

    $description =
        trim($_POST["description"] ?? "");

    $price =
        $_POST["price"] ?? "";

    $quantity =
        $_POST["quantity"] ?? "";

    $category_id =
        (int) ($_POST["category_id"] ?? 0);

    $image_name = null;


    /* =========================
       VALIDATE PRODUCT NAME
    ========================== */

    if ($product_name === "") {

        $message =
            "Product name is required.";

    }


    /* =========================
       VALIDATE PRICE
    ========================== */

    elseif (
        !is_numeric($price) ||
        (float) $price < 0
    ) {

        $message =
            "Please enter a valid price.";

    }


    /* =========================
       VALIDATE QUANTITY
    ========================== */

    elseif (
        filter_var(
            $quantity,
            FILTER_VALIDATE_INT
        ) === false ||
        (int) $quantity < 0
    ) {

        $message =
            "Please enter a valid quantity.";

    }


    /* =========================
       VALIDATE CATEGORY
    ========================== */

    elseif ($category_id <= 0) {

        $message =
            "Please select a category.";

    }

    else {

        $category_check = $conn->prepare(
            "SELECT category_id
             FROM categories
             WHERE category_id = ?"
        );

        $category_check->bind_param(
            "i",
            $category_id
        );

        $category_check->execute();

        $category_check_result =
            $category_check->get_result();


        if (
            $category_check_result->num_rows !== 1
        ) {

            $message =
                "Selected category does not exist.";

        }

    }


    /* =========================
       HANDLE IMAGE UPLOAD
    ========================== */

    if (
        $message === "" &&
        isset($_FILES["image"]) &&
        $_FILES["image"]["error"] !== UPLOAD_ERR_NO_FILE
    ) {

        if (
            $_FILES["image"]["error"]
            !== UPLOAD_ERR_OK
        ) {

            $message =
                "There was a problem uploading the image.";

        } else {

            $allowed_types = [
                "image/jpeg" => "jpg",
                "image/png"  => "png",
                "image/webp" => "webp"
            ];


            $finfo = new finfo(
                FILEINFO_MIME_TYPE
            );

            $file_type = $finfo->file(
                $_FILES["image"]["tmp_name"]
            );


            if (
                !array_key_exists(
                    $file_type,
                    $allowed_types
                )
            ) {

                $message =
                    "Please upload a valid JPG, PNG, or WEBP image.";

            } else {

                $extension =
                    $allowed_types[$file_type];


                $image_name =
                    uniqid(
                        "product_",
                        true
                    )
                    . "."
                    . $extension;


                $upload_directory =
                    "../uploads/";


                if (
                    !is_dir(
                        $upload_directory
                    )
                ) {

                    mkdir(
                        $upload_directory,
                        0777,
                        true
                    );

                }


                $upload_path =
                    $upload_directory .
                    $image_name;


                if (
                    !move_uploaded_file(
                        $_FILES["image"]["tmp_name"],
                        $upload_path
                    )
                ) {

                    $message =
                        "Could not save the product image.";

                    $image_name = null;

                }

            }

        }

    }


    /* =========================
       INSERT PRODUCT
    ========================== */

    if ($message === "") {

        $price =
            (float) $price;

        $quantity =
            (int) $quantity;


        $sql = "
            INSERT INTO products
            (
                product_name,
                description,
                price,
                quantity,
                image,
                category_id,
                seller_id
            )
            VALUES
            (
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                ?
            )
        ";


        $stmt = $conn->prepare(
            $sql
        );


        $stmt->bind_param(
            "ssdisii",
            $product_name,
            $description,
            $price,
            $quantity,
            $image_name,
            $category_id,
            $seller_id
        );


        if ($stmt->execute()) {

            header(
                "Location: products.php"
            );

            exit();

        } else {

            /* Remove uploaded image if
               database insert failed */

            if (
                $image_name !== null
            ) {

                $uploaded_file =
                    "../uploads/" .
                    $image_name;

                if (
                    file_exists(
                        $uploaded_file
                    )
                ) {

                    unlink(
                        $uploaded_file
                    );

                }

            }


            $message =
                "Something went wrong while adding the product.";

        }

    }

}

?>

<!DOCTYPE html>
<html>

<head>

    <title>
        Add Product - Gauley Ko Pasal
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
            My Products
        </a>

        <a href="orders.php">
            Active Orders
        </a>

        <a href="order_history.php">
            Order History
        </a>

        <a href="logout.php">
            Logout
        </a>

    </nav>

</header>


<div class="container">


    <div class="hero">

        <h2>
            Add Product 📦
        </h2>

        <p>
            Add a new product to your store.
        </p>

    </div>


    <div class="card">


        <?php if ($message !== ""): ?>

            <p>

                <?php
                echo htmlspecialchars(
                    $message
                );
                ?>

            </p>

        <?php endif; ?>


        <form
            method="POST"
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
                rows="5"
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
                    Select Category
                </option>


                <?php while (
                    $category =
                    $category_result->fetch_assoc()
                ): ?>

                    <option
                        value="<?php
                        echo (int)
                            $category["category_id"];
                        ?>"
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


</div>


<footer>

    <p>
        Gauley Ko Pasal —
        Seller Panel 🇳🇵
    </p>

</footer>


</body>

</html>
```
