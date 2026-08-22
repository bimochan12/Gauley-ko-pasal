
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
   COUNT PENDING SELLERS
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

    $pending_sellers =
        (int) $row["total"];

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

    $total_users =
        (int) $row["total"];

}


/* =========================
   COUNT SELLERS
========================= */

$total_sellers = 0;

$sql = "
    SELECT COUNT(*) AS total
    FROM users
    WHERE role = 'seller'
";

$result = $conn->query($sql);

if ($result) {

    $row = $result->fetch_assoc();

    $total_sellers =
        (int) $row["total"];

}

?>

<!DOCTYPE html>

<html>

<head>

    <title>
        Admin Dashboard - Gauley Ko Pasal
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

        <a href="sellers.php">
            Sellers
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

</header>


<div class="container">


    <!-- =========================
         WELCOME
    ========================== -->

    <div class="hero">

        <h2>
            Admin Dashboard
        </h2>

        <p>

            Welcome,

            <strong>

                <?php
                echo htmlspecialchars(
                    $_SESSION["name"]
                );
                ?>

            </strong>!

        </p>

        <p>
            Manage users, seller registrations,
            and monitor important system activity.
        </p>

    </div>


    <!-- =========================
         ADMIN OPTIONS
    ========================== -->

    <div class="products">


        <!-- SELLERS -->

        <div class="product-card">

            <h3>
                🏪 Seller Applications

                <?php if (
                    $pending_sellers > 0
                ): ?>

                    <span>

                        🔴

                        <?php
                        echo $pending_sellers;
                        ?>

                    </span>

                <?php endif; ?>

            </h3>


            <p>

                <?php

                if (
                    $pending_sellers > 0
                ) {

                    echo
                        $pending_sellers .
                        " seller application(s) waiting for approval.";

                } else {

                    echo
                        "No seller applications waiting for approval.";

                }

                ?>

            </p>


            <a
                class="button"
                href="sellers.php"
            >
                Manage Sellers
            </a>

        </div>


        <!-- USERS -->

        <div class="product-card">

            <h3>
                👥 Users
            </h3>

            <p>

                Total registered users:

                <strong>

                    <?php
                    echo $total_users;
                    ?>

                </strong>

            </p>


            <a
                class="button"
                href="users.php"
            >
                Manage Users
            </a>

        </div>


        <!-- TOTAL SELLERS -->

        <div class="product-card">

            <h3>
                🏪 Total Sellers
            </h3>

            <p>

                Registered seller accounts:

                <strong>

                    <?php
                    echo $total_sellers;
                    ?>

                </strong>

            </p>


            <a
                class="button"
                href="sellers.php"
            >
                View Sellers
            </a>

        </div>


        <!-- ACTIVITY LOGS -->

        <div class="product-card">

            <h3>
                📋 Activity Logs
            </h3>

            <p>
                View important actions performed
                by administrators.
            </p>


            <a
                class="button"
                href="activity_logs.php"
            >
                View Activity Logs
            </a>

        </div>


    </div>


</div>


<footer>

    <p>
        Gauley Ko Pasal —
        Admin Panel 🇳🇵
    </p>

</footer>


</body>

</html>
```
