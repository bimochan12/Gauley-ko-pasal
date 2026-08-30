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
   SEARCH
========================= */

$search = "";

if (isset($_GET["search"])) {
    $search = trim($_GET["search"]);
}


/* =========================
   GET APPROVED SELLERS
========================= */

if ($search !== "") {

    $search_term = "%" . $search . "%";

    $stmt = $conn->prepare(
        "SELECT
            user_id,
            name,
            email,
            phone,
            shop_name,
            store_location,
            created_at
         FROM users
         WHERE role = 'seller'
         AND seller_status = 'Approved'
         AND (
             name LIKE ?
             OR email LIKE ?
             OR phone LIKE ?
             OR shop_name LIKE ?
             OR store_location LIKE ?
         )
         ORDER BY created_at DESC"
    );

    $stmt->bind_param(
        "sssss",
        $search_term,
        $search_term,
        $search_term,
        $search_term,
        $search_term
    );

    $stmt->execute();

    $result = $stmt->get_result();

} else {

    $sql = "
        SELECT
            user_id,
            name,
            email,
            phone,
            shop_name,
            store_location,
            created_at
        FROM users
        WHERE role = 'seller'
        AND seller_status = 'Approved'
        ORDER BY created_at DESC
    ";

    $result = $conn->query($sql);
}

?>

<!DOCTYPE html>

<html>

<head>

    <title>
        Approved Sellers - Gauley Ko Pasal
    </title>

    <link
        rel="stylesheet"
        href="../style.css"
    >

    <style>

        .seller-grid {
            display: grid;
            grid-template-columns: repeat(
                auto-fit,
                minmax(280px, 1fr)
            );
            gap: 20px;
            margin-top: 20px;
        }


        .seller-card {
            background: white;
            padding: 22px;
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.08);
        }


        .seller-card h3 {
            margin-top: 0;
        }


        .seller-card p {
            margin: 8px 0;
        }


        .details-button {
            display: inline-block;
            margin-top: 15px;
            padding: 10px 16px;
            background-color: #841d2d;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-weight: bold;
        }


        .details-button:hover {
            opacity: 0.9;
        }


        .back-button {
            display: inline-block;
            margin-top: 20px;
        }


        /* =========================
           SEARCH BOX
        ========================= */

        .search-box {
            display: flex;
            gap: 10px;
            margin-top: 20px;
            margin-bottom: 20px;
        }


        .search-box input {
            flex: 1;
            padding: 11px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 15px;
        }


        .search-box button {
            padding: 11px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            background: #222;
            color: white;
        }


        .clear-search {
            display: inline-block;
            padding: 11px 18px;
            background: #ddd;
            color: #222;
            text-decoration: none;
            border-radius: 5px;
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

</header>


<div class="container">


    <div class="hero">

        <h2>
            Approved Sellers 🏪
        </h2>

        <p>
            View all sellers whose registrations have been approved.
        </p>

    </div>


    <div class="card">


        <h2>
            Approved Sellers
        </h2>


        <p>
            These are sellers who have been approved by the administrator.
        </p>


        <!-- =========================
             SEARCH
        ========================== -->

        <form
            method="GET"
            class="search-box"
        >

            <input
                type="text"
                name="search"
                placeholder="Search by seller, shop, email, phone or location..."
                value="<?php echo htmlspecialchars($search); ?>"
            >


            <button type="submit">
                🔎 Search
            </button>


            <?php if ($search !== ""): ?>

                <a
                    href="approved_sellers.php"
                    class="clear-search"
                >
                    Clear
                </a>

            <?php endif; ?>

        </form>


        <?php if (
            $result &&
            $result->num_rows > 0
        ): ?>


            <div class="seller-grid">


                <?php while (
                    $seller =
                    $result->fetch_assoc()
                ): ?>


                    <div class="seller-card">


                        <h3>

                            <?php

                            if (
                                !empty(
                                    $seller["shop_name"]
                                )
                            ) {

                                echo htmlspecialchars(
                                    $seller["shop_name"]
                                );

                            } else {

                                echo "Shop Name Not Provided";

                            }

                            ?>

                        </h3>


                        <p>

                            <strong>
                                Seller:
                            </strong>

                            <?php
                            echo htmlspecialchars(
                                $seller["name"]
                            );
                            ?>

                        </p>


                        <p>

                            <strong>
                                Email:
                            </strong>

                            <?php
                            echo htmlspecialchars(
                                $seller["email"]
                            );
                            ?>

                        </p>


                        <p>

                            <strong>
                                Phone:
                            </strong>

                            <?php
                            echo htmlspecialchars(
                                $seller["phone"] ?? "-"
                            );
                            ?>

                        </p>


                        <p>

                            <strong>
                                Location:
                            </strong>

                            <?php
                            echo htmlspecialchars(
                                $seller["store_location"] ?? "-"
                            );
                            ?>

                        </p>


                        <p>

                            <strong>
                                Joined:
                            </strong>

                            <?php
                            echo htmlspecialchars(
                                $seller["created_at"] ?? "-"
                            );
                            ?>

                        </p>


                        <p>

                            <strong>
                                Status:
                            </strong>

                            <span
                                style="
                                    color: green;
                                    font-weight: bold;
                                "
                            >
                                Approved
                            </span>

                        </p>


                        <a
                            class="details-button"
                            href="seller_details.php?id=<?php echo (int) $seller["user_id"]; ?>"
                        >
                            View Seller Details
                        </a>


                    </div>


                <?php endwhile; ?>


            </div>


        <?php else: ?>


            <div class="card">

                <h3>
                    No Approved Sellers Found
                </h3>


                <p>

                    <?php if ($search !== ""): ?>

                        No approved sellers matched:

                        <strong>
                            <?php
                            echo htmlspecialchars($search);
                            ?>
                        </strong>

                    <?php else: ?>

                        There are currently no approved sellers.

                    <?php endif; ?>

                </p>

            </div>


        <?php endif; ?>


    </div>


    <a
        class="back-button"
        href="dashboard.php"
    >
        ← Back to Dashboard
    </a>


</div>


<footer>

    <p>
        Gauley Ko Pasal — Admin Panel 🇳🇵
    </p>

</footer>


</body>

</html>