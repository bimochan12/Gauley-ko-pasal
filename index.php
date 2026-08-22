<?php

session_start();

?>

<!DOCTYPE html>
<html>

<head>

    <title>
        Gauley Ko Pasal
    </title>

    <link
        rel="stylesheet"
        href="style.css"
    >

</head>


<body>


<header>

    <h1>
        Gauley Ko Pasal 🇳🇵
    </h1>

</header>


<div class="container">


    <!-- =========================
         WELCOME
    ========================== -->

    <div class="hero">

        <h2>
            Welcome to Gauley Ko Pasal
        </h2>

        <p>
            Buy products, sell your products,
            or manage the marketplace.
        </p>

    </div>


    <!-- =========================
         USER OPTIONS
    ========================== -->

    <div class="products">


        <!-- =====================
             CUSTOMER
        ====================== -->

        <div class="product-card">

            <h3>
                🛍️ Customer
            </h3>

            <p>
                Browse products and shop from
                sellers on Gauley Ko Pasal.
            </p>

            <br>

            <a
                class="button"
                href="customer/index.php"
            >
                Visit Store
            </a>

            <br><br>

            <a
                href="customer/login.php"
            >
                Customer Login
            </a>

            <br><br>

            <a
                href="customer/register.php"
            >
                Create Customer Account
            </a>

        </div>


        <!-- =====================
             SELLER
        ====================== -->

        <div class="product-card">

            <h3>
                🏪 Seller
            </h3>

            <p>
                Register as a seller and start
                managing your products.
            </p>

            <br>

            <a
                class="button"
                href="seller/register.php"
            >
                Register as Seller
            </a>

            <br><br>

            <a
                href="seller/login.php"
            >
                Seller Login
            </a>

        </div>


        <!-- =====================
             ADMIN
        ====================== -->

        <div class="product-card">

            <h3>
                🔐 Administrator
            </h3>

            <p>
                Manage products, orders,
                users and seller applications.
            </p>

            <br>

            <a
                class="button"
                href="admin/login.php"
            >
                Admin Login
            </a>

        </div>


    </div>


    <!-- =========================
         LOGGED-IN USER SHORTCUT
    ========================== -->

    <?php if (isset($_SESSION["user_id"])): ?>

        <br>

        <div class="card">

            <h3>
                Already Logged In
            </h3>

            <p>

                Welcome,

                <strong>

                    <?php
                    echo htmlspecialchars(
                        $_SESSION["name"]
                    );
                    ?>

                </strong>

            </p>


            <?php if (
                $_SESSION["role"] === "customer"
            ): ?>

                <a
                    class="button"
                    href="customer/index.php"
                >
                    Go to Store
                </a>


            <?php elseif (
                $_SESSION["role"] === "seller"
            ): ?>

                <a
                    class="button"
                    href="seller/dashboard.php"
                >
                    Go to Seller Dashboard
                </a>


            <?php elseif (
                $_SESSION["role"] === "admin"
            ): ?>

                <a
                    class="button"
                    href="admin/dashboard.php"
                >
                    Go to Admin Dashboard
                </a>

            <?php endif; ?>


        </div>

    <?php endif; ?>


</div>


<footer>

    <p>
        Gauley Ko Pasal 🇳🇵
    </p>

</footer>


</body>

</html>