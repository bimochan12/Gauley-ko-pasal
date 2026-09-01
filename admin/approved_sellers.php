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
$search = "";
if (isset($_GET["search"])) {
    $search = trim($_GET["search"]);
}
if ($search !== "") {
    $search_term =
        "%" . $search . "%";
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
         AND account_status = 'Active'
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
    $result =
        $stmt->get_result();
} else {
    $sql =
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
         AND account_status = 'Active'
         ORDER BY created_at DESC";
    $result =
        $conn->query($sql);
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
        Approved Sellers - Gauley Ko Pasal
    </title>
    <link
        rel="stylesheet"
        href="../style.css"
    >
    <style>
        .seller-grid {
            display: grid;
            grid-template-columns:
                repeat(
                    auto-fit,
                    minmax(280px, 1fr)
                );
            gap: 20px;
            margin-top: 20px;
        }
        .seller-card {
            background: white;
            padding: 22px;
            border-radius: 12px;
            box-shadow:
                0 3px 10px rgba(
                    0,
                    0,
                    0,
                    0.08
                );
        }
        .seller-card h3 {
            margin-top: 0;
        }
        .seller-card p {
            margin: 8px 0;
        }
        .seller-actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin-top: 18px;
        }
        .details-button {
            display: inline-block;
            padding: 10px 15px;
            background: #841d2d;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-weight: bold;
        }
        .details-button:hover {
            opacity: 0.9;
        }
        .delete-button {
            display: inline-block;
            padding: 10px 15px;
            background: #b42318;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-weight: bold;
        }
        .delete-button:hover {
            background: #8f1c13;
        }
        .back-button {
            display: inline-block;
            margin-top: 20px;
        }
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
        .success-message {
            background: #e8f6ec;
            border: 1px solid #b9dfc1;
            color: #286638;
            padding: 14px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .error-message {
            background: #fff1f1;
            border: 1px solid #f0cccc;
            color: #a32020;
            padding: 14px;
            border-radius: 8px;
            margin-bottom: 20px;
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
            View and manage sellers currently authorized
            to sell on Gauley Ko Pasal.
        </p>
    </div>
    <?php if (
        !empty($_SESSION["admin_message"])
    ): ?>
        <div class="success-message">
            <?php
            echo htmlspecialchars(
                $_SESSION["admin_message"]
            );
            unset(
                $_SESSION["admin_message"]
            );
            ?>
        </div>
    <?php endif; ?>
    <?php if (
        !empty($_SESSION["admin_error"])
    ): ?>
        <div class="error-message">
            <?php
            echo htmlspecialchars(
                $_SESSION["admin_error"]
            );
            unset(
                $_SESSION["admin_error"]
            );
            ?>
        </div>
    <?php endif; ?>
    <div class="card">
        <h2>
            Approved Sellers
        </h2>
        <p>
            These sellers are currently active and
            authorized to sell.
        </p>
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
                            echo htmlspecialchars(
                                !empty(
                                    $seller["shop_name"]
                                )
                                ? $seller["shop_name"]
                                : "Shop Name Not Provided"
                            );
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
                                ✓ Active
                            </span>
                        </p>
                        <div class="seller-actions">
                            <a
                                class="details-button"
                                href="seller_details.php?id=<?php echo (int) $seller["user_id"]; ?>"
                            >
                                View Details
                            </a>
                            <a
                                class="delete-button"
                                href="delete_seller.php?id=<?php echo (int) $seller["user_id"]; ?>"
                            >
                                🗑 Delete Account
                            </a>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="card">
                <h3>
                    No Active Approved Sellers Found
                </h3>
                <p>
                    <?php if ($search !== ""): ?>
                        No active approved sellers matched:
                        <strong>
                            <?php
                            echo htmlspecialchars(
                                $search
                            );
                            ?>
                        </strong>
                    <?php else: ?>
                        There are currently no active approved sellers.
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

