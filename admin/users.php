
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
$message_type = "";


/* =========================
   DELETE USER
========================= */

if (
    $_SERVER["REQUEST_METHOD"] === "GET" &&
    isset($_GET["delete"])
) {

    $delete_user_id = (int) $_GET["delete"];


    /* Cannot delete yourself */

    if ($delete_user_id === (int) $_SESSION["user_id"]) {

        $message =
            "You cannot delete your own account.";

        $message_type = "error";

    } else {


        /* =========================
           CHECK USER EXISTS
           SELLERS CANNOT BE DELETED
        ========================= */

        $check_stmt = $conn->prepare(
            "SELECT user_id, name, role
             FROM users
             WHERE user_id = ?
             AND role != 'seller'"
        );

        $check_stmt->bind_param(
            "i",
            $delete_user_id
        );

        $check_stmt->execute();

        $user_result =
            $check_stmt->get_result();


        if ($user_result->num_rows === 0) {

            $message =
                "User account not found.";

            $message_type = "error";

        } else {

            $user_to_delete =
                $user_result->fetch_assoc();


            /* =========================
               PROTECT LAST ADMIN
            ========================= */

            if (
                $user_to_delete["role"] === "admin"
            ) {

                $admin_result = $conn->query(
                    "SELECT COUNT(*) AS total
                     FROM users
                     WHERE role = 'admin'"
                );

                $admin_count =
                    (int) $admin_result
                    ->fetch_assoc()["total"];


                if ($admin_count <= 1) {

                    $message =
                        "You cannot delete the last admin account.";

                    $message_type = "error";

                }

            }


            /* =========================
               CHECK EXISTING ORDERS
            ========================= */

            if ($message === "") {

                $order_stmt = $conn->prepare(
                    "SELECT COUNT(*) AS total
                     FROM orders
                     WHERE user_id = ?"
                );

                $order_stmt->bind_param(
                    "i",
                    $delete_user_id
                );

                $order_stmt->execute();

                $order_result =
                    $order_stmt->get_result();

                $order_count =
                    (int) $order_result
                    ->fetch_assoc()["total"];


                if ($order_count > 0) {

                    $message =
                        "This user cannot be deleted because they have existing orders.";

                    $message_type = "error";

                }

            }


            /* =========================
               DELETE USER
            ========================= */

            if ($message === "") {

                $delete_stmt = $conn->prepare(
                    "DELETE FROM users
                     WHERE user_id = ?
                     AND role != 'seller'"
                );

                $delete_stmt->bind_param(
                    "i",
                    $delete_user_id
                );


                if ($delete_stmt->execute()) {

                    /* RECORD ACTIVITY */

                    $admin_id =
                        (int) $_SESSION["user_id"];

                    $action =
                        "User deleted";

                    $details =
                        "Admin deleted " .
                        $user_to_delete["name"] .
                        " (User ID: " .
                        $delete_user_id .
                        ", Role: " .
                        $user_to_delete["role"] .
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

                    $log_stmt->bind_param(
                        "iss",
                        $admin_id,
                        $action,
                        $details
                    );

                    $log_stmt->execute();


                    $message =
                        "User account deleted successfully.";

                    $message_type = "success";

                } else {

                    $message =
                        "Could not delete the user account.";

                    $message_type = "error";

                }

            }

        }

    }

}


/* =========================
   CREATE ADMIN ACCOUNT
========================= */

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["create_user"])
) {

    $name =
        trim($_POST["name"]);

    $email =
        trim($_POST["email"]);

    $password =
        $_POST["password"];


    /* Validate fields */

    if (
        $name === "" ||
        $email === "" ||
        $password === ""
    ) {

        $message =
            "Please fill in all fields.";

        $message_type = "error";

    }

    elseif (
        !filter_var(
            $email,
            FILTER_VALIDATE_EMAIL
        )
    ) {

        $message =
            "Please enter a valid email address.";

        $message_type = "error";

    }

    elseif (
        strlen($password) < 6
    ) {

        $message =
            "Password must be at least 6 characters.";

        $message_type = "error";

    }

    else {

        /* Check duplicate email */

        $check_stmt = $conn->prepare(
            "SELECT user_id
             FROM users
             WHERE email = ?"
        );

        $check_stmt->bind_param(
            "s",
            $email
        );

        $check_stmt->execute();

        $check_result =
            $check_stmt->get_result();


        if (
            $check_result->num_rows > 0
        ) {

            $message =
                "An account with this email already exists.";

            $message_type = "error";

        } else {

            /* Hash password */

            $hashed_password =
                password_hash(
                    $password,
                    PASSWORD_DEFAULT
                );


            /* Create admin */

            $stmt = $conn->prepare(
                "INSERT INTO users
                (
                    name,
                    email,
                    password,
                    role
                )
                VALUES
                (
                    ?,
                    ?,
                    ?,
                    'admin'
                )"
            );

            $stmt->bind_param(
                "sss",
                $name,
                $email,
                $hashed_password
            );


            if ($stmt->execute()) {

                /* RECORD ACTIVITY */

                $admin_id =
                    (int) $_SESSION["user_id"];

                $new_admin_id =
                    $conn->insert_id;

                $action =
                    "Admin account created";

                $details =
                    "Admin created a new administrator account: " .
                    $name .
                    " (User ID: " .
                    $new_admin_id .
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

                $log_stmt->bind_param(
                    "iss",
                    $admin_id,
                    $action,
                    $details
                );

                $log_stmt->execute();


                $message =
                    "Admin account created successfully.";

                $message_type = "success";

            } else {

                $message =
                    "Could not create admin account.";

                $message_type = "error";

            }

        }

    }

}


/* =========================
   GET USERS
   EXCLUDE SELLERS
========================= */

$sql = "
    SELECT
        user_id,
        name,
        email,
        role
    FROM users
    WHERE role != 'seller'
    ORDER BY user_id DESC
";

$result =
    $conn->query($sql);

?>

<!DOCTYPE html>
<html>

<head>

    <title>
        Manage Users - Gauley Ko Pasal
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
            User Management 👥
        </h2>

        <p>
            Manage registered users and administrator accounts.
        </p>

    </div>


    <?php if ($message !== ""): ?>

        <div class="card">

            <p>

                <?php
                echo htmlspecialchars(
                    $message
                );
                ?>

            </p>

        </div>

        <br>

    <?php endif; ?>


    <!-- CREATE ADMIN -->

    <div class="card">

        <h2>
            Create Admin Account
        </h2>

        <p>
            Create another administrator account.
        </p>


        <form method="POST">

            <input
                type="hidden"
                name="create_user"
                value="1"
            >


            <label>
                Name
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


            <button type="submit">
                Create Admin
            </button>

        </form>

    </div>


    <br>


    <!-- REGISTERED USERS -->

    <div class="card">

        <h2>
            Registered Users
        </h2>

        <p>
            Seller registrations are managed separately
            from the Sellers page.
        </p>


        <?php if (
            $result &&
            $result->num_rows > 0
        ): ?>


            <table>

                <thead>

                    <tr>

                        <th>ID</th>

                        <th>Name</th>

                        <th>Email</th>

                        <th>Role</th>

                        <th>Action</th>

                    </tr>

                </thead>


                <tbody>


                <?php

                $display_number = 1;

                while (
                    $user =
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

                            <?php
                            echo htmlspecialchars(
                                $user["name"]
                            );
                            ?>

                        </td>


                        <td>

                            <?php
                            echo htmlspecialchars(
                                $user["email"]
                            );
                            ?>

                        </td>


                        <td>

                            <?php
                            echo htmlspecialchars(
                                $user["role"]
                            );
                            ?>

                        </td>


                        <td>


                            <?php if (
                                (int) $user["user_id"]
                                !==
                                (int) $_SESSION["user_id"]
                            ): ?>


                                <a
                                    href="users.php?delete=<?php
                                    echo (int)
                                        $user["user_id"];
                                    ?>"
                                    onclick="return confirm('Are you sure you want to delete this account?');"
                                >
                                    Delete
                                </a>


                            <?php else: ?>


                                <span>
                                    Current Account
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


        <?php else: ?>


            <p>
                No registered users found.
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
