<?php

session_start();

require_once "../config/database.php";

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $name = trim($_POST["name"]);
    $email = trim($_POST["email"]);
    $password = $_POST["password"];
    $phone = trim($_POST["phone"]);
    $address = trim($_POST["address"]);

    $national_id = trim($_POST["national_id"]);

    $has_shop = $_POST["has_shop"];

    $shop_name = trim($_POST["shop_name"]);

    $store_location = trim($_POST["store_location"]);


    /* =========================
       CHECK IF EMAIL EXISTS
    ========================= */

    $check_sql = "SELECT user_id
                  FROM users
                  WHERE email = ?";

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

    } else {


        /* =========================
           VALIDATE SHOP INFO
        ========================= */

        if (
            $has_shop === "Yes" &&
            $shop_name === ""
        ) {

            $message =
                "Please enter your shop name.";

        } elseif (
            $store_location === ""
        ) {

            $message =
                "Please enter your operating location.";

        }


        /* =========================
           NATIONAL ID IMAGE
        ========================= */

        elseif (
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

            $file_type =
                mime_content_type(
                    $_FILES["national_id_image"]["tmp_name"]
                );


            if (
                !in_array(
                    $file_type,
                    $allowed_types
                )
            ) {

                $message =
                    "National ID image must be JPG, PNG or WEBP.";

            } else {

                $file_extension =
                    pathinfo(
                        $_FILES["national_id_image"]["name"],
                        PATHINFO_EXTENSION
                    );


                $new_filename =
                    uniqid(
                        "national_id_",
                        true
                    )
                    . "."
                    . $file_extension;


                $upload_path =
                    "../uploads/national_ids/"
                    . $new_filename;


                if (
                    move_uploaded_file(
                        $_FILES["national_id_image"]["tmp_name"],
                        $upload_path
                    )
                ) {


                    /* =========================
                       CREATE SELLER
                    ========================= */

                    $hashed_password =
                        password_hash(
                            $password,
                            PASSWORD_DEFAULT
                        );


                    $sql = "INSERT INTO users
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
                            )";


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

                        /*
                         Remove uploaded image if
                         database insertion fails.
                        */

                        if (
                            file_exists(
                                $upload_path
                            )
                        ) {

                            unlink(
                                $upload_path
                            );

                        }


                        $message =
                            "Something went wrong. Please try again.";

                    }

                } else {

                    $message =
                        "Could not upload your National ID card image.";

                }

            }

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


            <label>
                Full Name
            </label>

            <input
                type="text"
                name="name"
                required
            >

            <br><br>


            <label>
                Email
            </label>

            <input
                type="email"
                name="email"
                required
            >

            <br><br>


            <label>
                Password
            </label>

            <input
                type="password"
                name="password"
                minlength="6"
                required
            >

            <br><br>


            <label>
                Phone Number
            </label>

            <input
                type="text"
                name="phone"
                required
            >

            <br><br>


            <label>
                Home Address
            </label>

            <input
                type="text"
                name="address"
                required
            >

            <br><br>


            <label>
                National ID / Citizenship Number
            </label>

            <input
                type="text"
                name="national_id"
                required
            >

            <br><br>


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

                <option value="Yes">
                    Yes
                </option>

                <option value="No">
                    No
                </option>

            </select>

            <br><br>


            <div id="shop_fields">

                <label>
                    Shop Name
                </label>

                <input
                    type="text"
                    name="shop_name"
                    id="shop_name"
                >

                <br><br>

            </div>


            <label>
                Exact Operating / Store Location
            </label>

            <input
                type="text"
                name="store_location"
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


shopFields.style.display = "none";


hasShop.addEventListener(
    "change",
    function () {

        if (this.value === "Yes") {

            shopFields.style.display = "block";

            shopName.required = true;

        } else {

            shopFields.style.display = "none";

            shopName.required = false;

            shopName.value = "";

        }

    }
);

</script>

</body>

</html>