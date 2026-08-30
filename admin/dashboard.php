<?php

session_start();

require_once "../config/database.php";


/* =========================
   ADMIN AUTHENTICATION
========================= */

if (
    !isset($_SESSION["user_id"]) ||
    !isset($_SESSION["role"]) ||
    $_SESSION["role"] !== "admin"
) {
    header("Location: login.php");
    exit();
}


/* =========================
   COUNT PENDING SELLER APPLICATIONS
========================= */

$pending_sellers = 0;

$sql = "
    SELECT COUNT(*) AS total
    FROM users
    WHERE role = 'seller'
    AND seller_status = 'Pending'
";

$result = $conn->query($sql);

if ($result) {

    $row = $result->fetch_assoc();

    $pending_sellers = (int) $row["total"];

}


/* =========================
   COUNT USERS
========================= */

$total_users = 0;

$sql = "
    SELECT COUNT(*) AS total
    FROM users
    WHERE role != 'seller'
";

$result = $conn->query($sql);

if ($result) {

    $row = $result->fetch_assoc();

    $total_users = (int) $row["total"];

}


/* =========================
   COUNT APPROVED SELLERS
========================= */

$total_sellers = 0;

$sql = "
    SELECT COUNT(*) AS total
    FROM users
    WHERE role = 'seller'
    AND seller_status = 'Approved'
";

$result = $conn->query($sql);

if ($result) {

    $row = $result->fetch_assoc();

    $total_sellers = (int) $row["total"];

}

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
        Admin Dashboard - Gauley Ko Pasal
    </title>

    <link
        rel="stylesheet"
        href="../style.css"
    >

    <style>

        /* =========================
           ADMIN DASHBOARD
        ========================= */

        body {
            background: #f6f1e7;
        }


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


        /* =========================
           MAIN CONTAINER
        ========================= */

        .admin-container {
            width: 88%;
            max-width: 1200px;
            margin: 35px auto 60px;
        }


        /* =========================
           WELCOME SECTION
        ========================= */

        .dashboard-hero {
            background: linear-gradient(
                135deg,
                #efe1c3,
                #f7ead0
            );

            padding: 38px;

            border-radius: 16px;

            margin-bottom: 28px;

            border: 1px solid #e2d2b5;
        }


        .dashboard-hero h2 {
            margin: 0 0 10px;

            font-size: 34px;

            color: #7b1e2b;
        }


        .dashboard-hero p {
            margin: 7px 0;

            color: #5d5048;

            font-size: 16px;
        }


        .welcome-name {
            color: #7b1e2b;
        }


        /* =========================
           STAT CARDS
        ========================= */

        .stats-grid {

            display: grid;

            grid-template-columns:
                repeat(3, 1fr);

            gap: 20px;

            margin-bottom: 28px;
        }


        .stat-card {

            background: white;

            padding: 24px;

            border-radius: 14px;

            box-shadow:
                0 5px 18px rgba(45, 36, 31, 0.08);

            border: 1px solid #eee4d5;

            position: relative;

            overflow: hidden;
        }


        .stat-card::before {

            content: "";

            position: absolute;

            left: 0;

            top: 0;

            width: 5px;

            height: 100%;

            background: #7b1e2b;
        }


        .stat-icon {

            font-size: 30px;

            margin-bottom: 12px;
        }


        .stat-label {

            color: #766960;

            font-size: 14px;

            margin-bottom: 6px;
        }


        .stat-number {

            font-size: 34px;

            font-weight: bold;

            color: #2d241f;
        }


        .stat-link {

            display: inline-block;

            margin-top: 14px;

            color: #7b1e2b;

            font-size: 14px;
        }


        /* =========================
           PENDING ALERT
        ========================= */

        .pending-alert {

            background: #fff8e8;

            border: 1px solid #ead7a8;

            border-radius: 12px;

            padding: 18px 20px;

            margin-bottom: 28px;

            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 20px;
        }


        .pending-alert-text strong {

            color: #7b1e2b;

        }


        .pending-alert-button {

            background: #7b1e2b;

            color: white;

            padding: 10px 16px;

            border-radius: 6px;

            text-decoration: none;

            white-space: nowrap;

        }


        .pending-alert-button:hover {

            background: #5d1721;

            text-decoration: none;
        }


        /* =========================
           MANAGEMENT SECTION
        ========================= */

        .section-title {

            color: #2d241f;

            margin: 0 0 17px;

            font-size: 22px;
        }


        .management-grid {

            display: grid;

            grid-template-columns:
                repeat(2, 1fr);

            gap: 20px;
        }


        .management-card {

            background: white;

            padding: 25px;

            border-radius: 14px;

            box-shadow:
                0 5px 18px rgba(45, 36, 31, 0.08);

            border: 1px solid #eee4d5;

            transition:
                transform 0.15s ease,
                box-shadow 0.15s ease;
        }


        .management-card:hover {

            transform: translateY(-2px);

            box-shadow:
                0 8px 22px rgba(45, 36, 31, 0.12);
        }


        .management-icon {

            font-size: 30px;

            margin-bottom: 10px;
        }


        .management-card h3 {

            margin: 0 0 8px;

            color: #7b1e2b;

            font-size: 20px;
        }


        .management-card p {

            color: #6b5e55;

            line-height: 1.5;

            min-height: 45px;
        }


        .management-button {

            display: inline-block;

            background: #7b1e2b;

            color: white;

            padding: 10px 17px;

            border-radius: 6px;

            margin-top: 10px;

            text-decoration: none;
        }


        .management-button:hover {

            background: #5d1721;

            text-decoration: none;
        }


        /* =========================
           NO PENDING MESSAGE
        ========================= */

        .no-pending {

            background: white;

            border: 1px solid #e3daca;

            border-radius: 10px;

            padding: 14px 18px;

            margin-bottom: 28px;

            color: #5f554e;
        }


        /* =========================
           RESPONSIVE
        ========================= */

        @media (max-width: 850px) {

            .admin-header {

                flex-direction: column;

                align-items: flex-start;

            }

            nav {

                line-height: 2;

            }

            .stats-grid {

                grid-template-columns: 1fr;

            }

            .management-grid {

                grid-template-columns: 1fr;

            }

        }


        @media (max-width: 600px) {

            .admin-container {

                width: 92%;

            }

            .dashboard-hero {

                padding: 25px;

            }

            .dashboard-hero h2 {

                font-size: 28px;

            }

            .pending-alert {

                flex-direction: column;

                align-items: flex-start;

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


    <!-- =========================
         WELCOME
    ========================== -->

    <div class="dashboard-hero">

        <h2>
            Admin Dashboard
        </h2>


        <p>

            Welcome,

            <strong class="welcome-name">

                <?php
                echo htmlspecialchars(
                    $_SESSION["name"]
                );
                ?>

            </strong>!

        </p>


        <p>
            Manage Gauley Ko Pasal from one place.
            Review sellers, manage users, and monitor
            important system activity.
        </p>

    </div>


    <!-- =========================
         PENDING SELLER ALERT
    ========================== -->

    <?php if ($pending_sellers > 0): ?>

        <div class="pending-alert">

            <div class="pending-alert-text">

                🔔

                <strong>
                    <?php
                    echo $pending_sellers;
                    ?>
                    seller application<?php
                    echo ($pending_sellers === 1)
                        ? ""
                        : "s";
                    ?>
                </strong>

                waiting for your review.

            </div>


            <a
                href="sellers.php"
                class="pending-alert-button"
            >
                Review Applications
            </a>

        </div>

    <?php else: ?>

        <div class="no-pending">

            ✓

            <strong>
                No pending seller applications.
            </strong>

            Everything is up to date.

        </div>

    <?php endif; ?>


    <!-- =========================
         STATISTICS
    ========================== -->

    <div class="stats-grid">


        <!-- USERS -->

        <div class="stat-card">

            <div class="stat-icon">
                👥
            </div>

            <div class="stat-label">
                Registered Users
            </div>

            <div class="stat-number">
                <?php
                echo $total_users;
                ?>
            </div>

            <a
                href="users.php"
                class="stat-link"
            >
                Manage users →
            </a>

        </div>


        <!-- SELLERS -->

        <div class="stat-card">

            <div class="stat-icon">
                🏪
            </div>

            <div class="stat-label">
                Approved Sellers
            </div>

            <div class="stat-number">
                <?php
                echo $total_sellers;
                ?>
            </div>

            <a
                href="approved_sellers.php"
                class="stat-link"
            >
                View sellers →
            </a>

        </div>


        <!-- APPLICATIONS -->

        <div class="stat-card">

            <div class="stat-icon">
                📋
            </div>

            <div class="stat-label">
                Pending Applications
            </div>

            <div class="stat-number">
                <?php
                echo $pending_sellers;
                ?>
            </div>

            <a
                href="sellers.php"
                class="stat-link"
            >
                Review applications →
            </a>

        </div>


    </div>


    <!-- =========================
         MANAGEMENT
    ========================== -->

    <h2 class="section-title">
        Management
    </h2>


    <div class="management-grid">


        <!-- SELLER APPLICATIONS -->

        <div class="management-card">

            <div class="management-icon">
                🏪
            </div>

            <h3>
                Seller Applications
            </h3>

            <p>

                Review seller registrations,
                approve applications, or reject
                applications that don't meet the requirements.

            </p>


            <a
                href="sellers.php"
                class="management-button"
            >
                Manage Applications
            </a>

        </div>


        <!-- APPROVED SELLERS -->

        <div class="management-card">

            <div class="management-icon">
                🛍️
            </div>

            <h3>
                Approved Sellers
            </h3>

            <p>

                Browse all approved sellers
                and view their shop and account
                information.

            </p>


            <a
                href="approved_sellers.php"
                class="management-button"
            >
                View Approved Sellers
            </a>

        </div>


        <!-- USERS -->

        <div class="management-card">

            <div class="management-icon">
                👥
            </div>

            <h3>
                User Management
            </h3>

            <p>

                View registered users and create
                additional administrator accounts.

            </p>


            <a
                href="users.php"
                class="management-button"
            >
                Manage Users
            </a>

        </div>


        <!-- ACTIVITY LOGS -->

        <div class="management-card">

            <div class="management-icon">
                📊
            </div>

            <h3>
                Activity Logs
            </h3>

            <p>

                Monitor important administrator
                actions and system activity.

            </p>


            <a
                href="activity_logs.php"
                class="management-button"
            >
                View Activity Logs
            </a>

        </div>


    </div>


</div>


<footer>

    <p>
        Gauley Ko Pasal — Admin Panel 🇳🇵
    </p>

</footer>


</body>

</html>