
<?php

session_start();


/* =========================
   RECORD LOGOUT ACTIVITY
========================= */

if (
    isset($_SESSION["user_id"]) &&
    isset($_SESSION["role"]) &&
    $_SESSION["role"] === "admin"
) {

    require_once "../config/database.php";

    $admin_id = (int) $_SESSION["user_id"];

    $admin_name = $_SESSION["name"] ?? "Unknown Admin";

    $action = "Admin logged out";

    $details =
        "Admin " .
        $admin_name .
        " logged out of the admin panel.";


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

}


/* =========================
   DESTROY SESSION
========================= */

session_unset();

session_destroy();


/* =========================
   REDIRECT TO LOGIN
========================= */

header("Location: login.php");

exit();

?>
```
