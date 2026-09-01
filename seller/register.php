<?php

session_start();

require_once "../config/database.php";

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $name = trim($_POST["name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";
    $phone = trim($_POST["phone"] ?? "");
    $address = trim($_POST["address"] ?? "");
    $national_id = trim($_POST["national_id"] ?? "");

    $has_shop = $_POST["has_shop"] ?? "";
    $shop_name = trim($_POST["shop_name"] ?? "");
    $store_location = trim($_POST["store_location"] ?? "");


    /* =========================
       BASIC VALIDATION
    ========================= */

    if ($name === "") {

        $message = "Please enter your full name.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $message = "Please enter a valid email address.";

    } elseif (strlen($password) < 8) {

        $message = "Password must be at least 8 characters long.";

    } elseif (!preg_match("/^[0-9]{10}$/", $phone)) {

        $message = "Phone number must contain exactly 10 digits.";

    } elseif ($address === "") {

        $message = "Please enter your home address.";

    } elseif ($national_id === "") {

        $message = "Please enter your National ID / Citizenship Number.";

    } elseif ($has_shop !== "Yes" && $has_shop !== "No") {

        $message = "Please select whether you have a physical shop.";

    } elseif ($has_shop === "Yes" && $shop_name === "") {

        $message = "Please enter your shop name.";

    } elseif ($store_location === "") {

        $message = "Please enter your operating location.";

    }


    /* =========================
       CHECK EMAIL
    ========================= */

    if ($message === "") {

        $check_sql = "
            SELECT user_id
            FROM users
            WHERE email = ?
        ";

        $check_stmt = $conn->prepare($check_sql);

        $check_stmt->bind_param(
            "s",
            $email
        );

        $check_stmt->execute();

        $check_result = $check_stmt->get_result();

        if ($check_result->num_rows > 0) {

            $message =
                "An account with this email already exists.";

        }

        $check_stmt->close();
    }


    /* =========================
       NATIONAL ID IMAGE
    ========================= */

    if ($message === "") {

        if (
            !isset($_FILES["national_id_image"]) ||
            $_FILES["national_id_image"]["error"] !== UPLOAD_ERR_OK
        ) {

            $message =
                "Please upload a photo of your National ID card.";

        } else {

            $allowed_types = [
                "image/jpeg",
                "image/png",
                "image/webp"
            ];

            $file_type = mime_content_type(
                $_FILES["national_id_image"]["tmp_name"]
            );

            if (!in_array($file_type, $allowed_types, true)) {

                $message =
                    "National ID image must be JPG, PNG or WEBP.";

            }

        }

    }


    /* =========================
       UPLOAD + DATABASE INSERT
    ========================= */

    if ($message === "") {

        $file_extension = strtolower(
            pathinfo(
                $_FILES["national_id_image"]["name"],
                PATHINFO_EXTENSION
            )
        );

        $new_filename =
            uniqid("national_id_", true)
            . "."
            . $file_extension;

        $upload_directory =
            "../uploads/national_ids/";

        $upload_path =
            $upload_directory
            . $new_filename;


        /* Create upload folder if needed */

        if (!is_dir($upload_directory)) {

            mkdir(
                $upload_directory,
                0755,
                true
            );

        }


        if (
            move_uploaded_file(
                $_FILES["national_id_image"]["tmp_name"],
                $upload_path
            )
        ) {


            /* =========================
               PASSWORD HASHING
            ========================= */

            $hashed_password = password_hash(
                $password,
                PASSWORD_DEFAULT
            );


            /* =========================
               INSERT SELLER
            ========================= */

            $sql = "
                INSERT INTO users
                (
                    name,
                    email,
                    password,
                    phone,
                    address,
                    role,
                    national_id,
                    national_id_image,
                    seller_status,
                    has_shop,
                    shop_name,
                    store_location
                )
                VALUES
                (
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    'seller',
                    ?,
                    ?,
                    'Pending',
                    ?,
                    ?,
                    ?
                )
            ";

            $stmt = $conn->prepare($sql);

            $stmt->bind_param(
                "ssssssssss",
                $name,
                $email,
                $hashed_password,
                $phone,
                $address,
                $national_id,
                $new_filename,
                $has_shop,
                $shop_name,
                $store_location
            );


            if ($stmt->execute()) {

                $message =
                    "Registration submitted successfully! Please wait for admin approval.";

            } else {

                if (file_exists($upload_path)) {

                    unlink($upload_path);

                }

                $message =
                    "Something went wrong. Please try again.";

            }

            $stmt->close();

        } else {

            $message =
                "Could not upload your National ID card image.";

        }

    }

}

?>

<!DOCTYPE html>

<html>

<head>

    <title>
        Seller Registration - Gauley Ko Pasal
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

        <a href="login.php">
            Seller Login
        </a>

    </nav>

</header>


<div class="container">

    <div class="hero">

        <h2>
            Become a Seller 🏪
        </h2>

        <p>
            Submit your information for admin verification.
        </p>

    </div>


    <div class="card">


        <?php if ($message !== ""): ?>

            <p>

                <?php
                echo htmlspecialchars($message);
                ?>

            </p>

        <?php endif; ?>


        <form
            method="POST"
            enctype="multipart/form-data"
        >


            <!-- NAME -->

            <label>
                Full Name
            </label>

            <input
                type="text"
                name="name"
                value="<?php echo htmlspecialchars($name ?? ""); ?>"
                required
            >


            <br><br>


            <!-- EMAIL -->

            <label>
                Email
            </label>

            <input
                type="email"
                name="email"
                value="<?php echo htmlspecialchars($email ?? ""); ?>"
                placeholder="example@gmail.com"
                required
            >


            <br><br>


            <!-- PASSWORD -->

            <label>
                Password
            </label>

            <input
                type="password"
                name="password"
                minlength="8"
                required
            >

            <small>
                Password must be at least 8 characters.
            </small>


            <br><br>


            <!-- PHONE -->

            <label>
                Phone Number
            </label>

            <input
                type="tel"
                name="phone"
                value="<?php echo htmlspecialchars($phone ?? ""); ?>"
                pattern="[0-9]{10}"
                minlength="10"
                maxlength="10"
                inputmode="numeric"
                placeholder="98XXXXXXXX"
                required
            >

            <small>
                Enter exactly 10 digits.
            </small>


            <br><br>


            <!-- ADDRESS -->

            <label>
                Home Address
            </label>

            <input
                type="text"
                name="address"
                value="<?php echo htmlspecialchars($address ?? ""); ?>"
                required
            >


            <br><br>


            <!-- NATIONAL ID -->

            <label>
                National ID / Citizenship Number
            </label>

            <input
                type="text"
                name="national_id"
                value="<?php echo htmlspecialchars($national_id ?? ""); ?>"
                required
            >


            <br><br>


            <!-- NATIONAL ID IMAGE -->

            <label>
                Upload National ID Card Photo
            </label>

            <input
                type="file"
                name="national_id_image"
                accept=".jpg,.jpeg,.png,.webp"
                required
            >


            <br><br>


            <!-- HAS SHOP -->

            <label>
                Do you have a physical shop?
            </label>

            <select
                name="has_shop"
                id="has_shop"
                required
            >

                <option value="">
                    Select an option
                </option>

                <option
                    value="Yes"
                    <?php
                    echo (
                        ($has_shop ?? "") === "Yes"
                        ? "selected"
                        : ""
                    );
                    ?>
                >
                    Yes
                </option>

                <option
                    value="No"
                    <?php
                    echo (
                        ($has_shop ?? "") === "No"
                        ? "selected"
                        : ""
                    );
                    ?>
                >
                    No
                </option>

            </select>


            <br><br>


            <!-- SHOP NAME -->

            <div
                id="shop_fields"
                style="display:none;"
            >

                <label>
                    Shop Name
                </label>

                <input
                    type="text"
                    name="shop_name"
                    id="shop_name"
                    value="<?php echo htmlspecialchars($shop_name ?? ""); ?>"
                >

                <br><br>

            </div>


            <!-- LOCATION -->

            <label>
                Exact Operating / Store Location
            </label>

            <input
                type="text"
                name="store_location"
                value="<?php echo htmlspecialchars($store_location ?? ""); ?>"
                placeholder="Where you operate your business from"
                required
            >


            <br><br>


            <button type="submit">
                Submit Registration
            </button>


        </form>


        <br>


        <p>

            Already have an approved seller account?

            <a href="login.php">
                Login as Seller
            </a>

        </p>


    </div>

</div>


<footer>

    <p>
        Gauley Ko Pasal — Seller Panel 🇳🇵
    </p>

</footer>


<script>

const hasShop =
    document.getElementById("has_shop");

const shopFields =
    document.getElementById("shop_fields");

const shopName =
    document.getElementById("shop_name");


function updateShopFields() {

    if (hasShop.value === "Yes") {

        shopFields.style.display = "block";

        shopName.required = true;

    } else {

        shopFields.style.display = "none";

        shopName.required = false;

        if (hasShop.value === "No") {

            shopName.value = "";

        }

    }

}


hasShop.addEventListener(
    "change",
    updateShopFields
);


/* Keep shop fields visible after validation error */

updateShopFields();

</script>


</body>

</html>