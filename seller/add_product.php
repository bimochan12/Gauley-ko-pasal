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

$seller_id = (int) $_SESSION["user_id"];

$message = "";
$message_type = "error";

/*
|--------------------------------------------------------------------------
| LOAD MAIN CATEGORIES
|--------------------------------------------------------------------------
*/

$main_categories = [];

$category_sql = "
    SELECT
        main_category_id,
        category_name
    FROM main_categories
    ORDER BY category_name ASC
";

$category_result = $conn->query($category_sql);

if ($category_result) {

    while ($category = $category_result->fetch_assoc()) {

        $main_categories[] = $category;

    }

}


/*
|--------------------------------------------------------------------------
| FORM VALUES
|--------------------------------------------------------------------------
*/

$product_name = "";
$description = "";
$price = "";
$quantity = "";
$main_category_id = "";
$subcategory_id = "";
$custom_subcategory = "";


/*
|--------------------------------------------------------------------------
| ADD PRODUCT
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $product_name =
        trim($_POST["product_name"] ?? "");

    $description =
        trim($_POST["description"] ?? "");

    $price =
        trim($_POST["price"] ?? "");

    $quantity =
        trim($_POST["quantity"] ?? "");

    $main_category_id =
        (int) ($_POST["main_category_id"] ?? 0);

    $subcategory_id =
        (int) ($_POST["subcategory_id"] ?? 0);

    $custom_subcategory =
        trim($_POST["custom_subcategory"] ?? "");

    $image_name = null;


    /*
    |--------------------------------------------------------------------------
    | VALIDATE PRODUCT NAME
    |--------------------------------------------------------------------------
    */

    if ($product_name === "") {

        $message =
            "Product name is required.";

    }


    /*
    |--------------------------------------------------------------------------
    | VALIDATE PRICE
    |--------------------------------------------------------------------------
    */

    elseif (
        !is_numeric($price) ||
        (float) $price < 0
    ) {

        $message =
            "Please enter a valid price.";

    }


    /*
    |--------------------------------------------------------------------------
    | VALIDATE QUANTITY
    |--------------------------------------------------------------------------
    */

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


    /*
    |--------------------------------------------------------------------------
    | VALIDATE MAIN CATEGORY
    |--------------------------------------------------------------------------
    */

    elseif ($main_category_id <= 0) {

        $message =
            "Please select a main category.";

    }


    /*
    |--------------------------------------------------------------------------
    | CHECK MAIN CATEGORY EXISTS
    |--------------------------------------------------------------------------
    */

    else {

        $category_check = $conn->prepare(
            "SELECT
                main_category_id,
                category_name
             FROM main_categories
             WHERE main_category_id = ?"
        );

        if (!$category_check) {

            $message =
                "Could not verify the selected category.";

        } else {

            $category_check->bind_param(
                "i",
                $main_category_id
            );

            $category_check->execute();

            $category_check_result =
                $category_check->get_result();

            if (
                $category_check_result->num_rows !== 1
            ) {

                $message =
                    "Selected main category does not exist.";

            }

        }

    }


    /*
    |--------------------------------------------------------------------------
    | HANDLE SUBCATEGORY
    |--------------------------------------------------------------------------
    */

    $final_subcategory_id = null;

    if ($message === "") {

        /*
        |----------------------------------------------------------------------
        | CUSTOM SUBCATEGORY
        |----------------------------------------------------------------------
        */

        if ($custom_subcategory !== "") {

            if (
                strlen($custom_subcategory) < 2
            ) {

                $message =
                    "Custom subcategory must contain at least 2 characters.";

            } elseif (
                strlen($custom_subcategory) > 100
            ) {

                $message =
                    "Custom subcategory is too long.";

            } else {

                /*
                |--------------------------------------------------------------------------
                | CHECK IF THIS SELLER ALREADY HAS THE SAME SUBCATEGORY
                |--------------------------------------------------------------------------
                */

                $custom_check = $conn->prepare(
                    "SELECT
                        subcategory_id
                     FROM subcategories
                     WHERE subcategory_name = ?
                     AND main_category_id = ?
                     AND (
                         seller_id = ?
                         OR seller_id IS NULL
                     )
                     LIMIT 1"
                );

                if ($custom_check) {

                    $custom_check->bind_param(
                        "sii",
                        $custom_subcategory,
                        $main_category_id,
                        $seller_id
                    );

                    $custom_check->execute();

                    $custom_result =
                        $custom_check->get_result();

                    if (
                        $custom_result->num_rows > 0
                    ) {

                        $existing =
                            $custom_result->fetch_assoc();

                        $final_subcategory_id =
                            (int)
                            $existing["subcategory_id"];

                    } else {

                        /*
                        |--------------------------------------------------------------------------
                        | CREATE CUSTOM SUBCATEGORY
                        |--------------------------------------------------------------------------
                        */

                        $create_subcategory =
                            $conn->prepare(
                                "INSERT INTO subcategories
                                (
                                    subcategory_name,
                                    main_category_id,
                                    seller_id
                                )
                                VALUES (?, ?, ?)"
                            );

                        if (!$create_subcategory) {

                            $message =
                                "Could not create the custom subcategory.";

                        } else {

                            $create_subcategory->bind_param(
                                "sii",
                                $custom_subcategory,
                                $main_category_id,
                                $seller_id
                            );

                            if (
                                $create_subcategory->execute()
                            ) {

                                $final_subcategory_id =
                                    (int)
                                    $conn->insert_id;

                            } else {

                                $message =
                                    "Could not create the custom subcategory.";

                            }

                        }

                    }

                } else {

                    $message =
                        "Could not check the custom subcategory.";

                }

            }

        }


        /*
        |----------------------------------------------------------------------
        | EXISTING SUBCATEGORY
        |----------------------------------------------------------------------
        */

        elseif (
            $subcategory_id > 0
        ) {

            $subcategory_check =
                $conn->prepare(
                    "SELECT
                        subcategory_id
                     FROM subcategories
                     WHERE subcategory_id = ?
                     AND main_category_id = ?
                     AND (
                         seller_id IS NULL
                         OR seller_id = ?
                     )"
                );

            if (!$subcategory_check) {

                $message =
                    "Could not verify the subcategory.";

            } else {

                $subcategory_check->bind_param(
                    "iii",
                    $subcategory_id,
                    $main_category_id,
                    $seller_id
                );

                $subcategory_check->execute();

                $subcategory_result =
                    $subcategory_check->get_result();

                if (
                    $subcategory_result->num_rows !== 1
                ) {

                    $message =
                        "Invalid subcategory selected.";

                } else {

                    $final_subcategory_id =
                        $subcategory_id;

                }

            }

        }

    }


    /*
    |--------------------------------------------------------------------------
    | IMAGE UPLOAD
    |--------------------------------------------------------------------------
    */

    if (
        $message === "" &&
        isset($_FILES["image"]) &&
        $_FILES["image"]["error"]
        !== UPLOAD_ERR_NO_FILE
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

                "image/png" => "png",

                "image/webp" => "webp"

            ];


            $finfo =
                new finfo(FILEINFO_MIME_TYPE);


            $file_type =
                $finfo->file(
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


    /*
    |--------------------------------------------------------------------------
    | INSERT PRODUCT
    |--------------------------------------------------------------------------
    */

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
                seller_id,
                main_category_id
            )
            VALUES
            (
                ?,
                ?,
                ?,
                ?,
                ?,
                NULL,
                ?,
                ?
            )
        ";


        $stmt =
            $conn->prepare($sql);


        if (!$stmt) {

            $message =
                "Could not prepare the product.";

        } else {

            $stmt->bind_param(
                "ssdisii",
                $product_name,
                $description,
                $price,
                $quantity,
                $image_name,
                $seller_id,
                $main_category_id
            );


            if ($stmt->execute()) {

                header(
                    "Location: products.php?success=product_added"
                );

                exit();

            } else {

                /*
                |--------------------------------------------------------------------------
                | DELETE UPLOADED IMAGE IF INSERT FAILED
                |--------------------------------------------------------------------------
                */

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

}

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Add Product - Gauley Ko Pasal
    </title>

    <link
        rel="stylesheet"
        href="../style.css"
    >

    <style>

        .category-help {
            display: block;
            margin-top: 6px;
            color: #718096;
            font-size: 12px;
        }

        .custom-category-box {
            display: none;
            margin-top: 12px;
        }

        .custom-category-box.show {
            display: block;
        }

        .category-preview {
            margin-top: 10px;
            padding: 12px;
            border-radius: 8px;
            background: #f4f7fa;
            color: #4a5568;
            font-size: 13px;
            display: none;
        }

        .category-preview.show {
            display: block;
        }

        .required {
            color: #d64545;
        }

    </style>

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

            <div
                style="
                    background:#fff0f0;
                    color:#c53030;
                    padding:12px 15px;
                    border-radius:8px;
                    margin-bottom:20px;
                "
            >

                <?php
                echo htmlspecialchars(
                    $message
                );
                ?>

            </div>

        <?php endif; ?>


        <form
            method="POST"
            enctype="multipart/form-data"
        >


            <!-- PRODUCT NAME -->

            <label>

                Product Name
                <span class="required">*</span>

            </label>


            <input
                type="text"
                name="product_name"
                value="<?php
                    echo htmlspecialchars(
                        $product_name
                    );
                ?>"
                required
            >


            <br><br>


            <!-- DESCRIPTION -->

            <label>
                Description
            </label>


            <textarea
                name="description"
                rows="5"
                placeholder="Describe the product..."
            ><?php
                echo htmlspecialchars(
                    $description
                );
            ?></textarea>


            <br><br>


            <!-- PRICE -->

            <label>

                Price
                <span class="required">*</span>

            </label>


            <input
                type="number"
                name="price"
                step="0.01"
                min="0"
                value="<?php
                    echo htmlspecialchars(
                        $price
                    );
                ?>"
                placeholder="0.00"
                required
            >


            <br><br>


            <!-- QUANTITY -->

            <label>

                Quantity
                <span class="required">*</span>

            </label>


            <input
                type="number"
                name="quantity"
                min="0"
                value="<?php
                    echo htmlspecialchars(
                        $quantity
                    );
                ?>"
                placeholder="Available stock"
                required
            >


            <br><br>


            <!-- MAIN CATEGORY -->

            <label>

                Main Category
                <span class="required">*</span>

            </label>


            <select
                name="main_category_id"
                id="main_category_id"
                required
            >

                <option value="">
                    Select a category
                </option>


                <?php foreach (
                    $main_categories
                    as $category
                ): ?>

                    <option
                        value="<?php
                            echo (int)
                                $category[
                                    "main_category_id"
                                ];
                        ?>"
                        <?php
                        if (
                            (int)
                            $main_category_id
                            ===
                            (int)
                            $category[
                                "main_category_id"
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

                <?php endforeach; ?>


            </select>


            <span class="category-help">

                Choose the general type of product,
                such as Food, Groceries, Drinks,
                Household, or Personal Care.

            </span>


            <br>


            <!-- SUBCATEGORY -->

            <label>

                Subcategory

            </label>


            <select
                name="subcategory_id"
                id="subcategory_id"
            >

                <option value="">
                    Select a subcategory
                </option>

            </select>


            <span class="category-help">

                You can choose an existing subcategory
                or create your own below.

            </span>


            <!-- CUSTOM SUBCATEGORY -->

            <div
                class="custom-category-box"
                id="custom-category-box"
            >

                <label>

                    Your Own Subcategory

                </label>


                <input
                    type="text"
                    name="custom_subcategory"
                    id="custom_subcategory"
                    value="<?php
                        echo htmlspecialchars(
                            $custom_subcategory
                        );
                    ?>"
                    maxlength="100"
                    placeholder="Example: Soft Drinks"
                >


                <span class="category-help">

                    If you enter a custom subcategory,
                    it will be created under the selected
                    main category.

                </span>

            </div>


            <br>


            <button
                type="button"
                id="custom-category-button"
                style="
                    margin-bottom:20px;
                "
            >
                + Create My Own Subcategory
            </button>


            <div
                class="category-preview"
                id="category-preview"
            ></div>


            <br>


            <!-- IMAGE -->

            <label>

                Product Image

            </label>


            <input
                type="file"
                name="image"
                accept=".jpg,.jpeg,.png,.webp"
            >


            <span class="category-help">

                JPG, PNG, or WEBP only.

            </span>


            <br><br>


            <!-- SUBMIT -->

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


<script>

/*
|--------------------------------------------------------------------------
| CATEGORY DATA
|--------------------------------------------------------------------------
|
| We load subcategories from the database using a small AJAX request.
|
*/

const mainCategory =
    document.getElementById(
        "main_category_id"
    );

const subcategory =
    document.getElementById(
        "subcategory_id"
    );

const customButton =
    document.getElementById(
        "custom-category-button"
    );

const customBox =
    document.getElementById(
        "custom-category-box"
    );

const customInput =
    document.getElementById(
        "custom_subcategory"
    );

const preview =
    document.getElementById(
        "category-preview"
    );


/*
|--------------------------------------------------------------------------
| LOAD SUBCATEGORIES
|--------------------------------------------------------------------------
*/

function loadSubcategories() {

    const categoryId =
        mainCategory.value;


    subcategory.innerHTML =
        '<option value="">Select a subcategory</option>';


    preview.classList.remove(
        "show"
    );


    if (!categoryId) {

        return;

    }


    fetch(
        "get_subcategories.php?main_category_id="
        +
        encodeURIComponent(
            categoryId
        )
    )

    .then(
        response =>
            response.json()
    )

    .then(
        data => {

            if (
                !Array.isArray(data)
            ) {

                return;

            }


            data.forEach(
                item => {

                    const option =
                        document.createElement(
                            "option"
                        );


                    option.value =
                        item.subcategory_id;


                    option.textContent =
                        item.subcategory_name;


                    subcategory.appendChild(
                        option
                    );

                }
            );

        }
    )

    .catch(
        error => {

            console.error(
                "Could not load subcategories:",
                error
            );

        }
    );

}


/*
|--------------------------------------------------------------------------
| MAIN CATEGORY CHANGED
|--------------------------------------------------------------------------
*/

mainCategory.addEventListener(
    "change",
    function () {

        loadSubcategories();

        updatePreview();

    }
);


/*
|--------------------------------------------------------------------------
| EXISTING SUBCATEGORY CHANGED
|--------------------------------------------------------------------------
*/

subcategory.addEventListener(
    "change",
    function () {

        if (
            this.value !== ""
        ) {

            customInput.value = "";

        }

        updatePreview();

    }
);


/*
|--------------------------------------------------------------------------
| CUSTOM SUBCATEGORY BUTTON
|--------------------------------------------------------------------------
*/

customButton.addEventListener(
    "click",
    function () {

        customBox.classList.toggle(
            "show"
        );


        if (
            customBox.classList.contains(
                "show"
            )
        ) {

            customInput.focus();

            subcategory.value = "";

        }

        updatePreview();

    }
);


/*
|--------------------------------------------------------------------------
| CUSTOM INPUT
|--------------------------------------------------------------------------
*/

customInput.addEventListener(
    "input",
    function () {

        if (
            this.value.trim() !== ""
        ) {

            subcategory.value = "";

        }

        updatePreview();

    }
);


/*
|--------------------------------------------------------------------------
| PREVIEW
|--------------------------------------------------------------------------
*/

function updatePreview() {

    const mainText =
        mainCategory.options[
            mainCategory.selectedIndex
        ]?.text || "";


    const subText =
        subcategory.options[
            subcategory.selectedIndex
        ]?.text || "";


    const customText =
        customInput.value.trim();


    let finalSubcategory =
        subText;


    if (
        customText !== ""
    ) {

        finalSubcategory =
            customText;

    }


    if (
        mainCategory.value &&
        finalSubcategory &&
        finalSubcategory !==
        "Select a subcategory"
    ) {

        preview.textContent =
            "Category: "
            +
            mainText
            +
            " → "
            +
            finalSubcategory;


        preview.classList.add(
            "show"
        );

    } else if (
        mainCategory.value
    ) {

        preview.textContent =
            "Main Category: "
            +
            mainText;


        preview.classList.add(
            "show"
        );

    } else {

        preview.classList.remove(
            "show"
        );

    }

}


/*
|--------------------------------------------------------------------------
| LOAD EXISTING SUBCATEGORIES IF FORM HAD AN ERROR
|--------------------------------------------------------------------------
*/

if (
    mainCategory.value
) {

    loadSubcategories();

}

</script>


</body>

</html>