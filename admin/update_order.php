```php
<?php

session_start();

if (
    !isset($_SESSION["user_id"]) ||
    !isset($_SESSION["role"]) ||
    $_SESSION["role"] !== "admin"
) {
    header("Location: login.php");
    exit();
}

require_once "../config/database.php";


/* =========================
   ONLY ACCEPT POST
========================= */

if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    header("Location: orders.php");
    exit();

}


/* =========================
   GET FORM DATA
========================= */

$order_id = isset($_POST["order_id"])
    ? (int) $_POST["order_id"]
    : 0;

$status = isset($_POST["status"])
    ? trim($_POST["status"])
    : "";


/* =========================
   VALIDATE ORDER ID
========================= */

if ($order_id <= 0) {

    header(
        "Location: orders.php?error=invalid_order"
    );

    exit();

}


/* =========================
   VALIDATE STATUS
========================= */

$allowed_statuses = [
    "Pending",
    "Processing",
    "Delivered",
    "Cancelled"
];

if (
    !in_array(
        $status,
        $allowed_statuses,
        true
    )
) {

    header(
        "Location: orders.php?error=invalid_status"
    );

    exit();

}


/* =========================
   CHECK ORDER EXISTS
========================= */

$check_stmt = $conn->prepare(
    "SELECT order_id
     FROM orders
     WHERE order_id = ?"
);

if (!$check_stmt) {

    header(
        "Location: orders.php?error=database"
    );

    exit();

}


$check_stmt->bind_param(
    "i",
    $order_id
);

$check_stmt->execute();

$check_result =
    $check_stmt->get_result();


if ($check_result->num_rows === 0) {

    header(
        "Location: orders.php?error=order_not_found"
    );

    exit();

}


/* =========================
   UPDATE STATUS
========================= */

$stmt = $conn->prepare(
    "UPDATE orders
     SET status = ?
     WHERE order_id = ?"
);


if (!$stmt) {

    header(
        "Location: orders.php?error=database"
    );

    exit();

}


$stmt->bind_param(
    "si",
    $status,
    $order_id
);


if ($stmt->execute()) {

    header(
        "Location: orders.php?success=status_updated"
    );

    exit();

}


header(
    "Location: orders.php?error=update_failed"
);

exit();

?>
```
