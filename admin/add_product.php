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


/*
|--------------------------------------------------------------------------
| Load Categories
|--------------------------------------------------------------------------
*/

$categories = $conn->query(
    "SELECT category_id, category_name
     FROM categories
     ORDER BY category_name ASC"
);


/*
|--------------------------------------------------------------------------
| Add Product
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $product_name = trim($_POST["product_name"]);
    $description = trim($_POST["description"]);
    $price = (float) $_POST["price"];
    $quantity = (int) $_POST["quantity"];
    $category_id = (int) $_POST["category_id"];

    $image_name = "";


    /*
    |--------------------------------------------------------------------------
    | Basic Validation
    |--------------------------------------------------------------------------
    */

    if (
        $product_name === "" ||
        $price < 0 ||
        $quantity < 0 ||
        $category_id <= 0
    ) {

        $message = "Please enter valid product information.";

    } else {


        /*
        |--------------------------------------------------------------------------
        | Image Upload
        |--------------------------------------------------------------------------
        */

        if (
            isset($_FILES["image"]) &&
            $_FILES["image"]["error"] === UPLOAD_ERR_OK
        ) {

            $upload_dir = "../uploads/";

            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }


            $original_name = $_FILES["image"]["name"];

            $tmp_name = $_FILES["image"]["tmp_name"];

            $file_extension = strtolower(
                pathinfo(
                    $original_name,
                    PATHINFO_EXTENSION
                )
            );


            $allowed_extensions = [
                "jpg",
                "jpeg",
                "png",
                "webp"
            ];


            if (
                !in_array(
                    $file_extension,
                    $allowed_extensions
                )
            ) {

                $message =
                    "Only JPG, JPEG, PNG and WEBP images are allowed.";

            } else {

                $image_name =
                    uniqid("product_", true)
                    . "."
                    . $file_extension;


                $destination =
                    $upload_dir . $image_name;


                if (
                    !move_uploaded_file(
                        $tmp_name,
                        $destination
                    )
                ) {

                    $message =
                        "Failed to upload the image.";

                    $image_name = "";

                }

            }

        }


        /*
        |--------------------------------------------------------------------------
        | Insert Product
        |--------------------------------------------------------------------------
        */

        if ($message === "") {

            $sql = "INSERT INTO products
                    (
                        product_name,
                        description,
                        price,
                        quantity,
                        image,
                        category_id,
                        seller_id
                    )
                    VALUES (?, ?, ?, ?, ?, ?, ?)";


            $stmt = $conn->prepare($sql);


            $seller_id = $_SESSION["user_id"];


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

                header("Location: products.php");

                exit();

            } else {

                $message =
                    "Failed to add product: "
                    . $stmt->error;

            }

        }

    }

}

?>

<!DOCTYPE html>
<html>

<head>

    <title>Add Product - Gauley Ko Pasal</title>

    <link
        rel="stylesheet"
        href="../style.css"
    >

</head>

<body>

<header>

    <h1>Gauley Ko Pasal</h1>

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

        <h2>Add New Product</h2>

        <p>
            Add a product to your Gauley Ko Pasal store.
        </p>

    </div>


    <?php if ($message !== ""): ?>

        <div class="card">

            <p>
                <?php
                echo htmlspecialchars($message);
                ?>
            </p>

        </div>

    <?php endif; ?>


    <div class="card">

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
                    -- Select Category --
                </option>

                <?php if ($categories): ?>

                    <?php while (
                        $category =
                        $categories->fetch_assoc()
                    ): ?>

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

                    <?php endwhile; ?>

                <?php endif; ?>

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