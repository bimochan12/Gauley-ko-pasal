<?php

session_start();

if (
    !isset($_SESSION["user_id"]) ||
    !isset($_SESSION["role"]) ||
    $_SESSION["role"] !== "seller"
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
   GET SELLER ID
========================= */

$seller_id = (int) $_SESSION["user_id"];


/* =========================
   GET FORM DATA
========================= */

$order_item_id =
    isset($_POST["order_item_id"])
    ? (int) $_POST["order_item_id"]
    : 0;

$status =
    isset($_POST["status"])
    ? trim($_POST["status"])
    : "";


/* =========================
   VALIDATE ORDER ITEM ID
========================= */

if ($order_item_id <= 0) {

    header(
        "Location: orders.php?error=invalid_item"
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
   GET ORDER ITEM
   AND CHECK SELLER OWNERSHIP
========================= */

$check_stmt = $conn->prepare(
    "SELECT
        order_items.order_item_id,
        order_items.status
     FROM order_items

     INNER JOIN products
        ON order_items.product_id =
           products.product_id

     WHERE order_items.order_item_id = ?
     AND products.seller_id = ?"
);


if (!$check_stmt) {

    header(
        "Location: orders.php?error=database"
    );

    exit();

}


$check_stmt->bind_param(
    "ii",
    $order_item_id,
    $seller_id
);

$check_stmt->execute();

$check_result =
    $check_stmt->get_result();


/* =========================
   CHECK PERMISSION
========================= */

if ($check_result->num_rows === 0) {

    header(
        "Location: orders.php?error=not_your_product"
    );

    exit();

}


$order_item =
    $check_result->fetch_assoc();

$current_status =
    $order_item["status"];


/* =========================
   PREVENT CHANGING
   COMPLETED ITEMS
========================= */

if (
    $current_status === "Delivered" ||
    $current_status === "Cancelled"
) {

    header(
        "Location: orders.php?error=order_completed"
    );

    exit();

}


/* =========================
   VALID STATUS FLOW
========================= */

$valid_change = false;


/* Pending -> Processing / Cancelled */

if (
    $current_status === "Pending"
    &&
    (
        $status === "Processing"
        ||
        $status === "Cancelled"
    )
) {

    $valid_change = true;

}


/* Processing -> Delivered / Cancelled */

elseif (
    $current_status === "Processing"
    &&
    (
        $status === "Delivered"
        ||
        $status === "Cancelled"
    )
) {

    $valid_change = true;

}


/* Same status */

elseif (
    $current_status === $status
) {

    header(
        "Location: orders.php?success=status_updated"
    );

    exit();

}


/* Invalid status change */

if (!$valid_change) {

    header(
        "Location: orders.php?error=invalid_status"
    );

    exit();

}


/* =========================
   UPDATE ONLY THIS
   ORDER ITEM
========================= */

$update_stmt = $conn->prepare(
    "UPDATE order_items
     SET status = ?
     WHERE order_item_id = ?"
);


if (!$update_stmt) {

    header(
        "Location: orders.php?error=database"
    );

    exit();

}


$update_stmt->bind_param(
    "si",
    $status,
    $order_item_id
);


/* =========================
   EXECUTE UPDATE
========================= */

if ($update_stmt->execute()) {

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