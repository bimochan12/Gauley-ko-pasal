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

$message = "";
$message_type = "";

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["seller_id"]) &&
    isset($_POST["seller_action"])
) {

    $seller_id = (int) $_POST["seller_id"];
    $seller_action = $_POST["seller_action"];

    if (
        $seller_action === "approve" ||
        $seller_action === "reject"
    ) {

        $new_status =
            ($seller_action === "approve")
            ? "Approved"
            : "Rejected";

        

        $seller_stmt = $conn->prepare(
            "SELECT name
             FROM users
             WHERE user_id = ?
             AND role = 'seller'"
        );

        if ($seller_stmt) {

            $seller_stmt->bind_param(
                "i",
                $seller_id
            );

            $seller_stmt->execute();

            $seller_result =
                $seller_stmt->get_result();

            if ($seller_result->num_rows === 1) {

                $seller_data =
                    $seller_result->fetch_assoc();

                $seller_name =
                    $seller_data["name"];

                

                $update_stmt = $conn->prepare(
                    "UPDATE users
                     SET seller_status = ?
                     WHERE user_id = ?
                     AND role = 'seller'
                     AND seller_status = 'Pending'"
                );

                if ($update_stmt) {

                    $update_stmt->bind_param(
                        "si",
                        $new_status,
                        $seller_id
                    );

                    if ($update_stmt->execute()) {

                        if (
                            $update_stmt->affected_rows > 0
                        ) {

                            

                            $admin_id =
                                (int) $_SESSION["user_id"];

                            if ($new_status === "Approved") {

                                $action =
                                    "Seller approved";

                                $details =
                                    "Admin " .
                                    ($_SESSION["name"] ?? "Unknown Admin") .
                                    " approved seller " .
                                    $seller_name .
                                    " (Seller ID: " .
                                    $seller_id .
                                    ").";

                            } else {

                                $action =
                                    "Seller rejected";

                                $details =
                                    "Admin " .
                                    ($_SESSION["name"] ?? "Unknown Admin") .
                                    " rejected seller " .
                                    $seller_name .
                                    " (Seller ID: " .
                                    $seller_id .
                                    ").";
                            }

                            $log_stmt = $conn->prepare(
                                "INSERT INTO activity_logs
                                (
                                    user_id,
                                    action,
                                    details
                                )
                                VALUES (?, ?, ?)"
                            );

                            if ($log_stmt) {

                                $log_stmt->bind_param(
                                    "iss",
                                    $admin_id,
                                    $action,
                                    $details
                                );

                                $log_stmt->execute();

                                $log_stmt->close();
                            }

                            if (
                                $new_status === "Approved"
                            ) {

                                $message =
                                    "Seller approved successfully.";

                                $message_type =
                                    "success";

                            } else {

                                $message =
                                    "Seller registration rejected.";

                                $message_type =
                                    "success";
                            }

                        } else {

                            $message =
                                "Seller has already been processed.";

                            $message_type =
                                "error";
                        }

                    } else {

                        $message =
                            "Could not update seller status.";

                        $message_type =
                            "error";
                    }

                    $update_stmt->close();

                } else {

                    $message =
                        "Could not prepare seller update.";

                    $message_type =
                        "error";
                }

            } else {

                $message =
                    "Seller account was not found.";

                $message_type =
                    "error";
            }

            $seller_stmt->close();

        } else {

            $message =
                "Could not find seller.";

            $message_type =
                "error";
        }
    }
}

$search = "";

if (isset($_GET["search"])) {

    $search =
        trim($_GET["search"]);
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
            address,
            national_id,
            national_id_image,
            seller_status,
            has_shop,
            shop_name,
            store_location,
            created_at
         FROM users
         WHERE role = 'seller'
         AND (
             name LIKE ?
             OR email LIKE ?
             OR phone LIKE ?
             OR address LIKE ?
             OR shop_name LIKE ?
             OR store_location LIKE ?
             OR seller_status LIKE ?
         )
         ORDER BY
            CASE seller_status
                WHEN 'Pending' THEN 1
                WHEN 'Approved' THEN 2
                WHEN 'Rejected' THEN 3
                ELSE 4
            END,
            user_id DESC"
    );

    $stmt->bind_param(
        "sssssss",
        $search_term,
        $search_term,
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

    $sql = "
        SELECT
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
        WHERE role = 'seller'
        ORDER BY
            CASE seller_status
                WHEN 'Pending' THEN 1
                WHEN 'Approved' THEN 2
                WHEN 'Rejected' THEN 3
                ELSE 4
            END,
            user_id DESC
    ";

    $result =
        $conn->query($sql);
}

$pending_count = 0;

$pending_result = $conn->query(
    "SELECT COUNT(*) AS total
     FROM users
     WHERE role = 'seller'
     AND seller_status = 'Pending'"
);

if ($pending_result) {

    $pending_count =
        (int) $pending_result
        ->fetch_assoc()["total"];
}

$approved_count = 0;

$approved_result = $conn->query(
    "SELECT COUNT(*) AS total
     FROM users
     WHERE role = 'seller'
     AND seller_status = 'Approved'"
);

if ($approved_result) {

    $approved_count =
        (int) $approved_result
        ->fetch_assoc()["total"];
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
        Seller Applications - Gauley Ko Pasal
    </title>

    <link
        rel="stylesheet"
        href="../style.css"
    >

    <style>

        

        .seller-page {
            max-width: 1250px;
            margin: 0 auto;
        }

        

        .page-heading {
            margin-bottom: 25px;
        }

        .page-heading h2 {
            margin-bottom: 8px;
        }

        .page-heading p {
            color: #666;
            margin: 0;
        }

        

        .seller-summary {
            display: grid;
            grid-template-columns:
                repeat(
                    2,
                    minmax(0, 1fr)
                );

            gap: 18px;

            margin-bottom: 25px;
        }

        .summary-card {
            background: white;

            border-radius: 14px;

            padding: 22px;

            border: 1px solid #e5e5e5;

            box-shadow:
                0 4px 15px
                rgba(0,0,0,0.06);
        }

        .summary-card span {
            display: block;

            color: #777;

            font-size: 14px;

            margin-bottom: 8px;
        }

        .summary-number {
            font-size: 30px;

            font-weight: 700;

            color: #222;
        }

        .pending-summary {
            border-left:
                5px solid #d89b18;
        }

        .approved-summary {
            border-left:
                5px solid #2e8b57;
        }

        

        .search-panel {
            background: white;

            border-radius: 14px;

            padding: 20px;

            margin-bottom: 25px;

            border: 1px solid #e5e5e5;

            box-shadow:
                0 4px 15px
                rgba(0,0,0,0.05);
        }

        .search-title {
            font-weight: 700;

            margin-bottom: 12px;
        }

        .search-form {
            display: flex;

            gap: 10px;

            align-items: center;
        }

        .search-form input {
            flex: 1;

            padding: 12px 14px;

            border: 1px solid #ccc;

            border-radius: 8px;

            font-size: 15px;

            box-sizing: border-box;
        }

        .search-form button {
            padding: 12px 20px;

            border: none;

            border-radius: 8px;

            background: #222;

            color: white;

            cursor: pointer;

            font-weight: 600;
        }

        .search-form button:hover {
            opacity: 0.88;
        }

        .clear-button {
            padding: 12px 18px;

            border-radius: 8px;

            background: #eeeeee;

            color: #222;

            text-decoration: none;

            font-weight: 600;
        }

        

        .message-box {
            padding: 15px 18px;

            border-radius: 10px;

            margin-bottom: 20px;

            background: white;

            border: 1px solid #ddd;
        }

        .message-success {
            border-left:
                5px solid #2e8b57;
        }

        .message-error {
            border-left:
                5px solid #c0392b;
        }

        

        .applications-panel {
            background: white;

            border-radius: 14px;

            padding: 25px;

            border: 1px solid #e5e5e5;

            box-shadow:
                0 4px 15px
                rgba(0,0,0,0.06);
        }

        .applications-header {
            display: flex;

            justify-content: space-between;

            align-items: center;

            margin-bottom: 20px;

            gap: 15px;
        }

        .applications-header h2 {
            margin: 0;
        }

        .applications-count {
            background: #f1f1f1;

            padding: 7px 12px;

            border-radius: 20px;

            font-size: 13px;

            font-weight: 600;
        }

        

        .seller-table-wrapper {
            overflow-x: auto;
        }

        .seller-table {
            width: 100%;

            border-collapse: collapse;

            min-width: 950px;
        }

        .seller-table th {
            background: #222;

            color: white;

            padding: 14px;

            text-align: left;

            font-size: 13px;

            letter-spacing: 0.2px;
        }

        .seller-table td {
            padding: 16px 14px;

            border-bottom:
                1px solid #e8e8e8;

            vertical-align: top;

            font-size: 14px;
        }

        .seller-table tbody tr:hover {
            background: #fafafa;
        }

        .seller-name {
            font-weight: 700;

            color: #222;

            margin-bottom: 5px;
        }

        .seller-email {
            color: #777;

            font-size: 13px;
        }

        .shop-name {
            font-weight: 700;

            color: #222;

            margin-bottom: 5px;
        }

        .secondary-text {
            color: #777;

            font-size: 13px;
        }

        

        .status-badge {
            display: inline-block;

            padding: 6px 10px;

            border-radius: 20px;

            font-size: 12px;

            font-weight: 700;
        }

        .status-pending {
            background: #fff3cd;

            color: #856404;
        }

        .status-approved {
            background: #d9f2e3;

            color: #216b3b;
        }

        .status-rejected {
            background: #f8d7da;

            color: #842029;
        }

        

        .action-button {
            display: inline-block;

            padding: 9px 13px;

            border-radius: 7px;

            border: none;

            text-decoration: none;

            cursor: pointer;

            font-size: 13px;

            font-weight: 600;

            margin-bottom: 7px;

            box-sizing: border-box;
        }

        .approve-button {
            background: #222;

            color: white;

            width: 100%;
        }

        .approve-button:hover {
            opacity: 0.85;
        }

        .reject-button {
            background: #eeeeee;

            color: #222;

            width: 100%;
        }

        .reject-button:hover {
            background: #dddddd;
        }

        .details-button {
            background: #841d2d;

            color: white;

            width: 100%;

            text-align: center;
        }

        .details-button:hover {
            opacity: 0.88;
        }

        .no-action {
            color: #999;

            font-size: 13px;
        }

        

        .empty-state {
            text-align: center;

            padding: 50px 20px;

            color: #777;
        }

        .empty-state-icon {
            font-size: 40px;

            margin-bottom: 10px;
        }

        .empty-state h3 {
            color: #333;

            margin-bottom: 8px;
        }

        

        .back-link {
            display: inline-block;

            margin-top: 22px;

            color: #222;

            text-decoration: none;

            font-weight: 600;
        }

        .back-link:hover {
            text-decoration: underline;
        }

        

        @media (max-width: 700px) {

            .seller-summary {
                grid-template-columns: 1fr;
            }

            .search-form {
                flex-direction: column;

                align-items: stretch;
            }

            .search-form button,
            .clear-button {
                text-align: center;
            }

            .applications-panel {
                padding: 15px;
            }

        }

    </style>

</head>

<body>

<header>

    <h1>

        <a
            href="dashboard.php"
            style="
                color: inherit;
                text-decoration: none;
            "
        >
            Gauley Ko Pasal
        </a>

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

<div class="container seller-page">

    

    <div class="page-heading">

        <h2>
            Seller Applications 🏪
        </h2>

        <p>
            Review seller registrations and approve or reject applications.
        </p>

    </div>

    

    <?php if ($message !== ""): ?>

        <div
            class="message-box
            <?php
            echo $message_type === "success"
                ? "message-success"
                : "message-error";
            ?>"
        >

            <?php
            echo htmlspecialchars($message);
            ?>

        </div>

    <?php endif; ?>

    

    <div class="seller-summary">

        <div class="summary-card pending-summary">

            <span>
                Pending Applications
            </span>

            <div class="summary-number">
                <?php
                echo $pending_count;
                ?>
            </div>

        </div>

        <div class="summary-card approved-summary">

            <span>
                Approved Sellers
            </span>

            <div class="summary-number">
                <?php
                echo $approved_count;
                ?>
            </div>

        </div>

    </div>

    

    <div class="search-panel">

        <div class="search-title">
            Search Sellers
        </div>

        <form
            method="GET"
            class="search-form"
        >

            <input
                type="text"
                name="search"
                placeholder="Search by seller name, shop, email, phone or location..."
                value="<?php
                    echo htmlspecialchars($search);
                ?>"
            >

            <button type="submit">
                🔎 Search
            </button>

            <?php if ($search !== ""): ?>

                <a
                    href="sellers.php"
                    class="clear-button"
                >
                    Clear
                </a>

            <?php endif; ?>

        </form>

    </div>

    

    <div class="applications-panel">

        <div class="applications-header">

            <h2>
                Applications
            </h2>

            <span class="applications-count">

                <?php

                if ($result) {

                    echo $result->num_rows;

                } else {

                    echo "0";

                }

                ?>

                result(s)

            </span>

        </div>

        <?php if (
            $result &&
            $result->num_rows > 0
        ): ?>

            <div class="seller-table-wrapper">

                <table class="seller-table">

                    <thead>

                        <tr>

                            <th>
                                #
                            </th>

                            <th>
                                Seller
                            </th>

                            <th>
                                Contact
                            </th>

                            <th>
                                Shop
                            </th>

                            <th>
                                Joined
                            </th>

                            <th>
                                Status
                            </th>

                            <th>
                                Action
                            </th>

                        </tr>

                    </thead>

                    <tbody>

                    <?php

                    $display_number = 1;

                    while (
                        $seller =
                        $result->fetch_assoc()
                    ):

                        $status =
                            $seller["seller_status"];

                        if (
                            empty($status)
                        ) {

                            $status =
                                "Pending";
                        }

                    ?>

                        <tr>

                            

                            <td>

                                <?php
                                echo $display_number;
                                ?>

                            </td>

                            

                            <td>

                                <div class="seller-name">

                                    <?php
                                    echo htmlspecialchars(
                                        $seller["name"]
                                    );
                                    ?>

                                </div>

                                <div class="seller-email">

                                    <?php
                                    echo htmlspecialchars(
                                        $seller["email"]
                                    );
                                    ?>

                                </div>

                            </td>

                            

                            <td>

                                <div>

                                    <strong>
                                        Phone
                                    </strong>

                                    <br>

                                    <span class="secondary-text">

                                        <?php
                                        echo htmlspecialchars(
                                            $seller["phone"] ?? "-"
                                        );
                                        ?>

                                    </span>

                                </div>

                                <br>

                                <div>

                                    <strong>
                                        Address
                                    </strong>

                                    <br>

                                    <span class="secondary-text">

                                        <?php
                                        echo htmlspecialchars(
                                            $seller["address"] ?? "-"
                                        );
                                        ?>

                                    </span>

                                </div>

                            </td>

                            

                            <td>

                                <div class="shop-name">

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

                                        echo "No shop name";

                                    }

                                    ?>

                                </div>

                                <div class="secondary-text">

                                    <?php

                                    if (
                                        !empty(
                                            $seller["store_location"]
                                        )
                                    ) {

                                        echo htmlspecialchars(
                                            $seller["store_location"]
                                        );

                                    } else {

                                        echo "Location not provided";

                                    }

                                    ?>

                                </div>

                            </td>

                            

                            <td>

                                <span class="secondary-text">

                                    <?php
                                    echo htmlspecialchars(
                                        $seller["created_at"] ?? "-"
                                    );
                                    ?>

                                </span>

                            </td>

                            

                            <td>

                                <?php if (
                                    $status === "Approved"
                                ): ?>

                                    <span
                                        class="status-badge status-approved"
                                    >
                                        Approved
                                    </span>

                                <?php elseif (
                                    $status === "Rejected"
                                ): ?>

                                    <span
                                        class="status-badge status-rejected"
                                    >
                                        Rejected
                                    </span>

                                <?php else: ?>

                                    <span
                                        class="status-badge status-pending"
                                    >
                                        Pending
                                    </span>

                                <?php endif; ?>

                            </td>

                            

                            <td>

                                <?php if (
                                    $status === "Pending"
                                ): ?>

                                    <form
                                        method="POST"
                                    >

                                        <input
                                            type="hidden"
                                            name="seller_id"
                                            value="<?php
                                                echo (int)
                                                    $seller["user_id"];
                                            ?>"
                                        >

                                        <button
                                            type="submit"
                                            name="seller_action"
                                            value="approve"
                                            class="action-button approve-button"
                                            onclick="return confirm('Approve this seller?');"
                                        >
                                            ✓ Approve
                                        </button>

                                    </form>

                                    <form
                                        method="POST"
                                    >

                                        <input
                                            type="hidden"
                                            name="seller_id"
                                            value="<?php
                                                echo (int)
                                                    $seller["user_id"];
                                            ?>"
                                        >

                                        <button
                                            type="submit"
                                            name="seller_action"
                                            value="reject"
                                            class="action-button reject-button"
                                            onclick="return confirm('Reject this seller registration?');"
                                        >
                                            ✕ Reject
                                        </button>

                                    </form>

                                <?php elseif (
                                    $status === "Approved"
                                ): ?>

                                    <a
                                        href="seller_details.php?id=<?php
                                            echo (int)
                                                $seller["user_id"];
                                        ?>"
                                        class="action-button details-button"
                                    >
                                        View Details
                                    </a>

                                <?php else: ?>

                                    <span class="no-action">
                                        Application rejected
                                    </span>

                                <?php endif; ?>

                            </td>

                        </tr>

                    <?php

                        $display_number++;

                    endwhile;

                    ?>

                    </tbody>

                </table>

            </div>

        <?php else: ?>

            <div class="empty-state">

                <div class="empty-state-icon">
                    🏪
                </div>

                <h3>
                    No Sellers Found
                </h3>

                <p>

                    <?php if (
                        $search !== ""
                    ): ?>

                        No sellers matched
                        "<strong><?php
                            echo htmlspecialchars($search);
                        ?></strong>".

                    <?php else: ?>

                        There are currently no seller applications.

                    <?php endif; ?>

                </p>

            </div>

        <?php endif; ?>

    </div>

    <a
        href="dashboard.php"
        class="back-link"
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
