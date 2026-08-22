
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


$message = "";


/* =========================
   APPROVE / REJECT SELLER
========================= */

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["seller_action"])
) {

    $seller_id = (int) $_POST["seller_id"];

    $seller_action =
        $_POST["seller_action"];


    if (
        $seller_action === "approve" ||
        $seller_action === "reject"
    ) {

        /* =========================
           GET SELLER INFORMATION
        ========================= */

        $seller_stmt = $conn->prepare(
            "SELECT name
             FROM users
             WHERE user_id = ?
             AND role = 'seller'"
        );

        $seller_stmt->bind_param(
            "i",
            $seller_id
        );

        $seller_stmt->execute();

        $seller_result =
            $seller_stmt->get_result();


        if (
            $seller_result->num_rows === 0
        ) {

            $message =
                "Seller account was not found.";

        } else {

            $seller_data =
                $seller_result->fetch_assoc();

            $seller_name =
                $seller_data["name"];


            /* =========================
               SET NEW STATUS
            ========================= */

            $new_status =
                $seller_action === "approve"
                ? "Approved"
                : "Rejected";


            /* =========================
               UPDATE SELLER
            ========================= */

            $stmt = $conn->prepare(
                "UPDATE users
                 SET seller_status = ?
                 WHERE user_id = ?
                 AND role = 'seller'
                 AND seller_status = 'Pending'"
            );

            $stmt->bind_param(
                "si",
                $new_status,
                $seller_id
            );


            if ($stmt->execute()) {

                if (
                    $stmt->affected_rows > 0
                ) {

                    /* =========================
                       RECORD ACTIVITY
                    ========================= */

                    $admin_id =
                        (int) $_SESSION["user_id"];

                    $action =
                        $new_status === "Approved"
                        ? "Seller approved"
                        : "Seller rejected";

                    $details =
                        "Admin " .
                        ($_SESSION["name"] ?? "Unknown Admin") .
                        " " .
                        strtolower($new_status) .
                        " seller " .
                        $seller_name .
                        " (Seller ID: " .
                        $seller_id .
                        ").";


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

                    }


                    if (
                        $new_status === "Approved"
                    ) {

                        $message =
                            "Seller approved successfully.";

                    } else {

                        $message =
                            "Seller registration rejected.";

                    }

                } else {

                    $message =
                        "Seller has already been processed.";

                }

            } else {

                $message =
                    "Could not update seller status.";

            }

        }

    }

}


/* =========================
   GET SELLERS
========================= */

$sql = "SELECT
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
            user_id DESC";

$result = $conn->query($sql);

?>

<!DOCTYPE html>

<html>

<head>

    <title>
        Seller Management - Gauley Ko Pasal
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


    <div class="hero">

        <h2>
            Seller Management 🏪
        </h2>

        <p>
            Review seller registrations and approve
            or reject applications.
        </p>

    </div>


    <?php if ($message !== ""): ?>

        <div class="card">

            <p>
                <?php
                echo htmlspecialchars($message);
                ?>
            </p>

        </div>

        <br>

    <?php endif; ?>


    <div class="card">

        <h2>
            Seller Applications
        </h2>


        <?php if (
            $result &&
            $result->num_rows > 0
        ): ?>


            <table>

                <thead>

                    <tr>

                        <th>ID</th>

                        <th>Seller</th>

                        <th>Contact Information</th>

                        <th>National ID</th>

                        <th>Business Information</th>

                        <th>Status</th>

                        <th>Action</th>

                    </tr>

                </thead>


                <tbody>


                <?php

                $display_number = 1;

                while (
                    $seller =
                    $result->fetch_assoc()
                ):

                ?>


                    <tr>

                        <td>

                            <?php
                            echo $display_number;
                            ?>

                        </td>


                        <td>

                            <strong>

                                <?php
                                echo htmlspecialchars(
                                    $seller["name"]
                                );
                                ?>

                            </strong>

                            <br><br>

                            <strong>Email:</strong>

                            <?php
                            echo htmlspecialchars(
                                $seller["email"]
                            );
                            ?>

                            <br><br>

                            <strong>Registered:</strong>

                            <?php
                            echo htmlspecialchars(
                                $seller["created_at"]
                            );
                            ?>

                        </td>


                        <td>

                            <strong>
                                Phone:
                            </strong>

                            <?php
                            echo htmlspecialchars(
                                $seller["phone"] ?? "-"
                            );
                            ?>

                            <br><br>

                            <strong>
                                Address:
                            </strong>

                            <?php
                            echo htmlspecialchars(
                                $seller["address"] ?? "-"
                            );
                            ?>

                        </td>


                        <td>

                            <strong>
                                ID Number:
                            </strong>

                            <?php
                            echo htmlspecialchars(
                                $seller["national_id"] ?? "-"
                            );
                            ?>

                            <br><br>


                            <?php if (
                                !empty(
                                    $seller["national_id_image"]
                                )
                            ): ?>


                                <a
                                    href="../uploads/national_ids/<?php
                                    echo urlencode(
                                        $seller[
                                            "national_id_image"
                                        ]
                                    );
                                    ?>"
                                    target="_blank"
                                >
                                    View National ID Card
                                </a>


                            <?php else: ?>


                                No ID image uploaded.


                            <?php endif; ?>


                        </td>


                        <td>

                            <strong>
                                Has Shop:
                            </strong>

                            <?php
                            echo htmlspecialchars(
                                $seller["has_shop"] ?? "-"
                            );
                            ?>

                            <br><br>


                            <strong>
                                Shop Name:
                            </strong>

                            <?php
                            echo htmlspecialchars(
                                !empty($seller["shop_name"])
                                ? $seller["shop_name"]
                                : "-"
                            );
                            ?>

                            <br><br>


                            <strong>
                                Operating Location:
                            </strong>

                            <?php
                            echo htmlspecialchars(
                                $seller["store_location"] ?? "-"
                            );
                            ?>

                        </td>


                        <td>

                            <strong>

                                <?php
                                echo htmlspecialchars(
                                    $seller["seller_status"]
                                    ?? "Pending"
                                );
                                ?>

                            </strong>

                        </td>


                        <td>


                            <?php if (
                                ($seller["seller_status"] ?? "Pending")
                                === "Pending"
                            ): ?>


                                <form
                                    method="POST"
                                    style="margin-bottom: 10px;"
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
                                        onclick="return confirm('Approve this seller?');"
                                    >
                                        Approve
                                    </button>

                                </form>


                                <form method="POST">

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
                                        onclick="return confirm('Reject this seller registration?');"
                                    >
                                        Reject
                                    </button>

                                </form>


                            <?php else: ?>


                                No pending action.


                            <?php endif; ?>


                        </td>

                    </tr>


                    <?php

                    $display_number++;

                    endwhile;

                    ?>


                </tbody>

            </table>


        <?php else: ?>


            <p>
                No seller applications found.
            </p>


        <?php endif; ?>


    </div>


    <br>


    <a href="dashboard.php">
        ← Back to Dashboard
    </a>


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
