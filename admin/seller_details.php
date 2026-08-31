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

if (
    !isset($_GET["id"]) ||
    !is_numeric($_GET["id"])
) {
    header("Location: approved_sellers.php");
    exit();
}

$seller_id = (int) $_GET["id"];

$stmt = $conn->prepare(
    "SELECT
        user_id,
        name,
        email,
        phone,
        address,
        national_id,
        national_id_image,
        seller_status,
        has_shop,
        shop_name,
        store_location,
        created_at
     FROM users
     WHERE user_id = ?
     AND role = 'seller'
     AND seller_status = 'Approved'"
);

if (!$stmt) {
    die("Could not prepare seller query.");
}

$stmt->bind_param(
    "i",
    $seller_id
);

$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows !== 1) {

    $stmt->close();

    ?>

    <!DOCTYPE html>

    <html>

    <head>

        <meta charset="UTF-8">

        <meta
            name="viewport"
            content="width=device-width, initial-scale=1.0"
        >

        <title>
            Seller Not Found - Gauley Ko Pasal
        </title>

        <link
            rel="stylesheet"
            href="../style.css"
        >

    </head>

    <body>

    <header>

        <div class="admin-header">

            <a
                href="dashboard.php"
                class="site-logo"
            >
                Gauley Ko Pasal
            </a>

            <nav>

                <a href="dashboard.php">
                    Dashboard
                </a>

                <a href="sellers.php">
                    Seller Applications
                </a>

                <a href="approved_sellers.php">
                    Approved Sellers
                </a>

                <a href="users.php">
                    Users
                </a>

                <a href="activity_logs.php">
                    Activity Logs
                </a>

                <a href="logout.php">
                    Logout
                </a>

            </nav>

        </div>

    </header>

    <div class="admin-container">

        <div class="empty-state">

            <div class="empty-icon">
                🔍
            </div>

            <h2>
                Approved Seller Not Found
            </h2>

            <p>
                The seller you are looking for does not exist
                or is no longer approved.
            </p>

            <a
                href="approved_sellers.php"
                class="primary-button"
            >
                ← Back to Approved Sellers
            </a>

        </div>

    </div>

    <footer>

        <p>
            Gauley Ko Pasal — Admin Panel 🇳🇵
        </p>

    </footer>

    </body>

    </html>

    <?php

    exit();
}

$seller = $result->fetch_assoc();

$stmt->close();

?>

<!DOCTYPE html>

<html>

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        <?php
        echo htmlspecialchars(
            !empty($seller["shop_name"])
            ? $seller["shop_name"]
            : "Seller Details"
        );
        ?>
        - Gauley Ko Pasal
    </title>

    <link
        rel="stylesheet"
        href="../style.css"
    >

    <style>

        header {
            background: #7b1e2b;
            padding: 18px 6%;
        }

        .admin-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 25px;
        }

        .site-logo {
            color: white;
            text-decoration: none;
            font-size: 27px;
            font-weight: bold;
        }

        .site-logo:hover {
            text-decoration: none;
        }

        nav {
            margin-top: 0;
        }

        nav a {
            color: white;
            margin-right: 18px;
            font-size: 14px;
        }

        nav a:last-child {
            margin-right: 0;
        }

        .admin-container {
            width: 88%;
            max-width: 1000px;
            margin: 35px auto 60px;
        }

        

        .details-hero {

            background: linear-gradient(
                135deg,
                #efe1c3,
                #f7ead0
            );

            padding: 35px;

            border-radius: 16px;

            margin-bottom: 25px;

            border: 1px solid #e2d2b5;
        }

        .details-hero h2 {

            margin: 0 0 8px;

            color: #7b1e2b;

            font-size: 30px;
        }

        .details-hero p {

            margin: 0;

            color: #62564e;
        }

        

        .shop-profile {

            background: white;

            padding: 30px;

            border-radius: 16px;

            border: 1px solid #eee4d5;

            box-shadow:
                0 5px 18px rgba(
                    45,
                    36,
                    31,
                    0.08
                );

            margin-bottom: 20px;

            display: flex;

            align-items: center;

            gap: 20px;
        }

        .shop-icon {

            width: 70px;

            height: 70px;

            display: flex;

            align-items: center;

            justify-content: center;

            background: #f3e7d2;

            border-radius: 15px;

            font-size: 36px;

            flex-shrink: 0;
        }

        .shop-profile h1 {

            margin: 0 0 6px;

            color: #7b1e2b;

            font-size: 27px;
        }

        .seller-subtitle {

            color: #6d6158;

            margin: 0;
        }

        .approved-badge {

            display: inline-block;

            margin-top: 10px;

            padding: 6px 12px;

            background: #e5f2e7;

            color: #35613c;

            border-radius: 20px;

            font-size: 12px;

            font-weight: bold;
        }

        

        .information-card {

            background: white;

            border-radius: 16px;

            border: 1px solid #eee4d5;

            box-shadow:
                0 5px 18px rgba(
                    45,
                    36,
                    31,
                    0.07
                );

            margin-bottom: 20px;

            overflow: hidden;
        }

        .information-header {

            padding: 20px 25px;

            background: #faf6ef;

            border-bottom: 1px solid #eee4d5;
        }

        .information-header h2 {

            margin: 0;

            color: #7b1e2b;

            font-size: 19px;
        }

        .information-body {

            padding: 5px 25px 15px;
        }

        .detail-row {

            display: grid;

            grid-template-columns: 180px 1fr;

            gap: 20px;

            padding: 16px 0;

            border-bottom: 1px solid #eee7dc;
        }

        .detail-row:last-child {

            border-bottom: none;
        }

        .detail-label {

            font-weight: bold;

            color: #6d6158;
        }

        .detail-value {

            color: #302823;

            word-break: break-word;
        }

        

        .status-approved {

            display: inline-block;

            padding: 5px 11px;

            border-radius: 20px;

            background: #e5f2e7;

            color: #35613c;

            font-weight: bold;

            font-size: 13px;
        }

        

        .id-link {

            display: inline-block;

            background: #7b1e2b;

            color: white;

            padding: 9px 14px;

            border-radius: 6px;

            text-decoration: none;

            font-size: 14px;
        }

        .id-link:hover {

            background: #5d1721;

            text-decoration: none;
        }

        

        .back-button {

            display: inline-block;

            background: #7b1e2b;

            color: white;

            padding: 11px 18px;

            border-radius: 7px;

            text-decoration: none;

            margin-top: 5px;
        }

        .back-button:hover {

            background: #5d1721;

            text-decoration: none;
        }

        

        @media (max-width: 850px) {

            .admin-header {

                flex-direction: column;

                align-items: flex-start;

            }

            nav {

                line-height: 2;
            }

        }

        @media (max-width: 600px) {

            .admin-container {

                width: 92%;
            }

            .shop-profile {

                align-items: flex-start;

                flex-direction: column;
            }

            .detail-row {

                grid-template-columns: 1fr;

                gap: 5px;
            }

            .details-hero {

                padding: 25px;
            }

            .details-hero h2 {

                font-size: 27px;
            }

        }

    </style>

</head>

<body>

<header>

    <div class="admin-header">

        <a
            href="dashboard.php"
            class="site-logo"
        >
            Gauley Ko Pasal
        </a>

        <nav>

            <a href="dashboard.php">
                Dashboard
            </a>

            <a href="sellers.php">
                Seller Applications
            </a>

            <a href="approved_sellers.php">
                Approved Sellers
            </a>

            <a href="users.php">
                Users
            </a>

            <a href="activity_logs.php">
                Activity Logs
            </a>

            <a href="logout.php">
                Logout
            </a>

        </nav>

    </div>

</header>

<div class="admin-container">

    

    <div class="details-hero">

        <h2>
            Seller Details
        </h2>

        <p>
            Complete information about this approved seller.
        </p>

    </div>

    

    <div class="shop-profile">

        <div class="shop-icon">
            🏪
        </div>

        <div>

            <h1>

                <?php

                echo htmlspecialchars(
                    !empty($seller["shop_name"])
                    ? $seller["shop_name"]
                    : "Shop Name Not Provided"
                );

                ?>

            </h1>

            <p class="seller-subtitle">

                Seller:

                <strong>

                    <?php
                    echo htmlspecialchars(
                        $seller["name"]
                    );
                    ?>

                </strong>

            </p>

            <span class="approved-badge">
                ✓ Approved Seller
            </span>

        </div>

    </div>

    

    <div class="information-card">

        <div class="information-header">

            <h2>
                👤 Seller Information
            </h2>

        </div>

        <div class="information-body">

            <div class="detail-row">

                <div class="detail-label">
                    Seller Name
                </div>

                <div class="detail-value">

                    <?php
                    echo htmlspecialchars(
                        $seller["name"]
                    );
                    ?>

                </div>

            </div>

            <div class="detail-row">

                <div class="detail-label">
                    Email
                </div>

                <div class="detail-value">

                    <?php
                    echo htmlspecialchars(
                        $seller["email"]
                    );
                    ?>

                </div>

            </div>

            <div class="detail-row">

                <div class="detail-label">
                    Phone
                </div>

                <div class="detail-value">

                    <?php
                    echo htmlspecialchars(
                        $seller["phone"] ?? "-"
                    );
                    ?>

                </div>

            </div>

            <div class="detail-row">

                <div class="detail-label">
                    Address
                </div>

                <div class="detail-value">

                    <?php
                    echo htmlspecialchars(
                        $seller["address"] ?? "-"
                    );
                    ?>

                </div>

            </div>

        </div>

    </div>

    

    <div class="information-card">

        <div class="information-header">

            <h2>
                🏪 Shop Information
            </h2>

        </div>

        <div class="information-body">

            <div class="detail-row">

                <div class="detail-label">
                    Shop Name
                </div>

                <div class="detail-value">

                    <?php

                    echo htmlspecialchars(
                        !empty($seller["shop_name"])
                        ? $seller["shop_name"]
                        : "-"
                    );

                    ?>

                </div>

            </div>

            <div class="detail-row">

                <div class="detail-label">
                    Has Shop
                </div>

                <div class="detail-value">

                    <?php
                    echo htmlspecialchars(
                        $seller["has_shop"] ?? "-"
                    );
                    ?>

                </div>

            </div>

            <div class="detail-row">

                <div class="detail-label">
                    Store Location
                </div>

                <div class="detail-value">

                    <?php
                    echo htmlspecialchars(
                        $seller["store_location"] ?? "-"
                    );
                    ?>

                </div>

            </div>

        </div>

    </div>

    

    <div class="information-card">

        <div class="information-header">

            <h2>
                📋 Account Information
            </h2>

        </div>

        <div class="information-body">

            <div class="detail-row">

                <div class="detail-label">
                    Seller ID
                </div>

                <div class="detail-value">

                    #<?php
                    echo (int) $seller["user_id"];
                    ?>

                </div>

            </div>

            <div class="detail-row">

                <div class="detail-label">
                    Joined
                </div>

                <div class="detail-value">

                    <?php
                    echo htmlspecialchars(
                        $seller["created_at"] ?? "-"
                    );
                    ?>

                </div>

            </div>

            <div class="detail-row">

                <div class="detail-label">
                    Seller Status
                </div>

                <div class="detail-value">

                    <span class="status-approved">
                        ✓ Approved
                    </span>

                </div>

            </div>

        </div>

    </div>

    

    <div class="information-card">

        <div class="information-header">

            <h2>
                🪪 Identification
            </h2>

        </div>

        <div class="information-body">

            <div class="detail-row">

                <div class="detail-label">
                    National ID
                </div>

                <div class="detail-value">

                    <?php

                    if (
                        !empty(
                            $seller["national_id"]
                        )
                    ) {

                        echo htmlspecialchars(
                            $seller["national_id"]
                        );

                    } else {

                        echo "-";

                    }

                    ?>

                </div>

            </div>

            <?php if (
                !empty(
                    $seller["national_id_image"]
                )
            ): ?>

                <div class="detail-row">

                    <div class="detail-label">
                        National ID Card
                    </div>

                    <div class="detail-value">

                        <a
                            href="../uploads/national_ids/<?php
                            echo rawurlencode(
                                $seller["national_id_image"]
                            );
                            ?>"
                            target="_blank"
                            class="id-link"
                        >
                            View ID Card
                        </a>

                    </div>

                </div>

            <?php endif; ?>

        </div>

    </div>

    

    <a
        href="approved_sellers.php"
        class="back-button"
    >
        ← Back to Approved Sellers
    </a>

</div>

<footer>

    <p>
        Gauley Ko Pasal — Admin Panel 🇳🇵
    </p>

</footer>

</body>

</html>
