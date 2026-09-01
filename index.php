<?php

session_start();

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
        Gauley Ko Pasal 🇳🇵
    </title>


    <style>

        /*
        ============================================================
        GLOBAL
        ============================================================
        */

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }


        body {

            min-height: 100vh;

            font-family:
                Arial,
                Helvetica,
                sans-serif;

            color: #172033;

            background:
                linear-gradient(
                    135deg,
                    #f7fbff 0%,
                    #eef5fb 50%,
                    #f8fafc 100%
                );

            display: flex;

            flex-direction: column;
        }


        a {
            text-decoration: none;
        }


        /*
        ============================================================
        HEADER
        ============================================================
        */

        header {

            background: rgba(255, 255, 255, 0.94);

            border-bottom:
                1px solid #e3eaf1;

            padding: 18px 6%;

            display: flex;

            justify-content: space-between;

            align-items: center;

            position: sticky;

            top: 0;

            z-index: 100;
        }


        .brand {

            display: flex;

            align-items: center;

            gap: 11px;
        }


        .brand-icon {

            width: 42px;

            height: 42px;

            border-radius: 12px;

            background: #102a43;

            color: white;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 21px;

            box-shadow:
                0 7px 18px
                rgba(16, 42, 67, 0.15);
        }


        .brand-text h1 {

            font-size: 19px;

            line-height: 1;

            color: #102a43;

            letter-spacing: -0.5px;
        }


        .brand-text span {

            display: block;

            margin-top: 4px;

            font-size: 10px;

            color: #718096;

            font-weight: 700;

            letter-spacing: 1px;

            text-transform: uppercase;
        }


        .header-badge {

            padding: 8px 13px;

            border-radius: 30px;

            background: #eef6fd;

            color: #1976d2;

            font-size: 11px;

            font-weight: 800;
        }


        /*
        ============================================================
        MAIN CONTAINER
        ============================================================
        */

        .container {

            width: 90%;

            max-width: 1180px;

            margin:
                55px auto 70px;

            flex: 1;
        }


        /*
        ============================================================
        HERO
        ============================================================
        */

        .hero {

            text-align: center;

            max-width: 760px;

            margin:
                0 auto 48px;
        }


        .hero-label {

            display: inline-flex;

            align-items: center;

            gap: 7px;

            padding: 7px 13px;

            border-radius: 30px;

            background: white;

            border:
                1px solid #dfe8f0;

            color: #1976d2;

            font-size: 10px;

            font-weight: 900;

            letter-spacing: 1.5px;

            text-transform: uppercase;

            margin-bottom: 18px;

            box-shadow:
                0 5px 18px
                rgba(30, 60, 90, 0.04);
        }


        .hero h2 {

            font-size:
                clamp(34px, 5vw, 58px);

            line-height: 1.08;

            letter-spacing: -2px;

            color: #102a43;

            margin-bottom: 18px;
        }


        .hero h2 span {

            color: #1976d2;
        }


        .hero p {

            max-width: 620px;

            margin: auto;

            color: #718096;

            font-size: 15px;

            line-height: 1.8;
        }


        /*
        ============================================================
        ROLE CARDS
        ============================================================
        */

        .products {

            display: grid;

            grid-template-columns:
                repeat(3, 1fr);

            gap: 20px;
        }


        .product-card {

            background: white;

            border:
                1px solid #e2e9f0;

            border-radius: 20px;

            padding: 30px;

            min-height: 300px;

            display: flex;

            flex-direction: column;

            position: relative;

            overflow: hidden;

            transition:
                transform .22s ease,
                box-shadow .22s ease,
                border-color .22s ease;
        }


        .product-card::before {

            content: "";

            position: absolute;

            top: 0;

            left: 0;

            width: 100%;

            height: 4px;

            background: #1976d2;
        }


        .product-card:hover {

            transform:
                translateY(-6px);

            border-color:
                #cdddea;

            box-shadow:
                0 18px 45px
                rgba(31, 59, 88, 0.11);
        }


        /*
        ============================================================
        CARD ICONS
        ============================================================
        */

        .card-icon {

            width: 52px;

            height: 52px;

            border-radius: 15px;

            background: #eef6fd;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 25px;

            margin-bottom: 20px;
        }


        .product-card h3 {

            color: #102a43;

            font-size: 22px;

            margin-bottom: 10px;
        }


        .product-card p {

            color: #718096;

            font-size: 13px;

            line-height: 1.7;

            margin-bottom: 24px;
        }


        /*
        ============================================================
        PRIMARY BUTTON
        ============================================================
        */

        .button {

            display: inline-flex;

            align-items: center;

            justify-content: center;

            gap: 8px;

            width: 100%;

            padding: 13px 18px;

            border-radius: 10px;

            background: #102a43;

            color: white;

            font-size: 12px;

            font-weight: 900;

            transition: .2s ease;
        }


        .button:hover {

            background: #1976d2;

            transform:
                translateY(-1px);
        }


        /*
        ============================================================
        SECONDARY LINKS
        ============================================================
        */

        .card-links {

            margin-top: auto;

            padding-top: 18px;

            border-top:
                1px solid #edf1f5;

            display: flex;

            flex-direction: column;

            gap: 9px;
        }


        .card-links a {

            color: #53677d;

            font-size: 11px;

            font-weight: 800;

            transition: .2s ease;
        }


        .card-links a:hover {

            color: #1976d2;

            padding-left: 3px;
        }


        /*
        ============================================================
        LOGGED-IN CARD
        ============================================================
        */

        .logged-in-card {

            margin-top: 22px;

            background: #102a43;

            border-radius: 20px;

            padding: 24px 27px;

            color: white;

            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 20px;

            box-shadow:
                0 15px 35px
                rgba(16, 42, 67, 0.16);
        }


        .logged-content {

            display: flex;

            align-items: center;

            gap: 15px;
        }


        .logged-avatar {

            width: 50px;

            height: 50px;

            border-radius: 50%;

            background: #1976d2;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 18px;

            font-weight: 900;
        }


        .logged-text small {

            display: block;

            color: #b7c8d8;

            font-size: 10px;

            font-weight: 800;

            text-transform: uppercase;

            letter-spacing: 1px;

            margin-bottom: 4px;
        }


        .logged-text h3 {

            font-size: 16px;
        }


        .logged-button {

            background: white;

            color: #102a43;

            padding: 12px 18px;

            border-radius: 9px;

            font-size: 11px;

            font-weight: 900;

            white-space: nowrap;

            transition: .2s ease;
        }


        .logged-button:hover {

            background: #eef6fd;

            color: #1976d2;
        }


        /*
        ============================================================
        FOOTER
        ============================================================
        */

        footer {

            background: #102a43;

            color: #aebfd0;

            text-align: center;

            padding: 22px 15px;

            margin-top: auto;
        }


        footer p {

            font-size: 11px;

            font-weight: 700;

            letter-spacing: .3px;
        }


        footer span {

            color: #6fa8dc;
        }


        /*
        ============================================================
        TABLET
        ============================================================
        */

        @media (max-width: 850px) {

            .products {

                grid-template-columns:
                    1fr;
            }


            .product-card {

                min-height: auto;
            }


            .logged-in-card {

                align-items: flex-start;

                flex-direction: column;
            }


            .logged-button {

                width: 100%;

                text-align: center;
            }

        }


        /*
        ============================================================
        MOBILE
        ============================================================
        */

        @media (max-width: 550px) {

            header {

                padding:
                    14px 5%;
            }


            .header-badge {

                display: none;
            }


            .container {

                width: 92%;

                margin-top: 38px;
            }


            .hero {

                margin-bottom: 32px;
            }


            .hero h2 {

                font-size: 35px;

                letter-spacing: -1.2px;
            }


            .hero p {

                font-size: 13px;
            }


            .product-card {

                padding: 24px;
            }


            .logged-in-card {

                padding: 20px;
            }

        }

    </style>

</head>


<body>


<!-- ============================================================
     HEADER
============================================================ -->

<header>


    <div class="brand">


        <div class="brand-icon">

            🛍️

        </div>


        <div class="brand-text">

            <h1>
                Gauley Ko Pasal 🇳🇵
            </h1>

            <span>
                Local Online Marketplace
            </span>

        </div>


    </div>


    <div class="header-badge">

        🇳🇵 Made for Nepal

    </div>


</header>


<!-- ============================================================
     MAIN
============================================================ -->

<div class="container">


    <!-- HERO -->

    <section class="hero">


        <div class="hero-label">

            ✦ Welcome to Gauley Ko Pasal

        </div>


        <h2>

            Everything you need,
            <span>all in one place.</span>

        </h2>


        <p>

            Shop from local sellers, start selling
            your own products, or manage the
            marketplace from one simple platform.

        </p>


    </section>


    <!-- ========================================================
         ROLE CARDS
    ========================================================= -->

    <div class="products">


        <!-- CUSTOMER -->

        <div class="product-card">


            <div class="card-icon">

                🛒

            </div>


            <h3>
                Customer
            </h3>


            <p>

                Browse everyday products,
                discover local sellers and
                shop conveniently online.

            </p>


            <a
                class="button"
                href="customer/index.php"
            >

                Visit Store →

            </a>


            <div class="card-links">


                <a
                    href="customer/login.php"
                >

                    Already have an account?
                    Customer Login

                </a>


                <a
                    href="customer/register.php"
                >

                    Create Customer Account

                </a>


            </div>


        </div>


        <!-- SELLER -->

        <div class="product-card">


            <div class="card-icon">

                📦

            </div>


            <h3>
                Seller
            </h3>


            <p>

                Join Gauley Ko Pasal,
                add your products and
                manage your online shop.

            </p>


            <a
                class="button"
                href="seller/register.php"
            >

                Register as Seller →

            </a>


            <div class="card-links">


                <a
                    href="seller/login.php"
                >

                    Already a seller?
                    Seller Login

                </a>


            </div>


        </div>


        <!-- ADMIN -->

        <div class="product-card">


            <div class="card-icon">

                ⚙️

            </div>


            <h3>
                Admin
            </h3>


            <p>

                Manage users, sellers,
                products and the marketplace
                from the administration panel.

            </p>


            <a
                class="button"
                href="admin/login.php"
            >

                Admin Login →

            </a>


            <div class="card-links">


                <a href="admin/login.php">

                    Open Administration Panel

                </a>


            </div>


        </div>


    </div>


    <!-- ========================================================
         LOGGED-IN USER
    ========================================================= -->

    <?php if (isset($_SESSION["user_id"])): ?>


        <div class="logged-in-card">


            <div class="logged-content">


                <div class="logged-avatar">

                    👤

                </div>


                <div class="logged-text">


                    <small>
                        Already Logged In
                    </small>


                    <h3>

                        Welcome,

                        <?php

                        echo htmlspecialchars(
                            $_SESSION["name"] ?? "User"
                        );

                        ?>

                        👋

                    </h3>


                </div>


            </div>


            <?php if (
                isset($_SESSION["role"]) &&
                $_SESSION["role"] === "customer"
            ): ?>


                <a
                    class="logged-button"
                    href="customer/index.php"
                >

                    Go to Store →

                </a>


            <?php elseif (
                isset($_SESSION["role"]) &&
                $_SESSION["role"] === "seller"
            ): ?>


                <a
                    class="logged-button"
                    href="seller/dashboard.php"
                >

                    Seller Dashboard →

                </a>


            <?php elseif (
                isset($_SESSION["role"]) &&
                $_SESSION["role"] === "admin"
            ): ?>


                <a
                    class="logged-button"
                    href="admin/dashboard.php"
                >

                    Admin Dashboard →

                </a>


            <?php endif; ?>


        </div>


    <?php endif; ?>


</div>


<!-- ============================================================
     FOOTER
============================================================ -->

<footer>


    <p>

        Gauley Ko Pasal 🇳🇵
        <span>•</span>
        Your local online marketplace

    </p>


</footer>


</body>

</html>