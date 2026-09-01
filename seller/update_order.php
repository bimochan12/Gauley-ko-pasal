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

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: orders.php");
    exit();
}

$seller_id = (int) $_SESSION["user_id"];

$order_item_id =
    isset($_POST["order_item_id"])
    ? (int) $_POST["order_item_id"]
    : 0;

$status =
    isset($_POST["status"])
    ? trim($_POST["status"])
    : "";


/*
|--------------------------------------------------------------------------
| VALIDATE ITEM
|--------------------------------------------------------------------------
*/

if ($order_item_id <= 0) {

    header(
        "Location: orders.php?error=invalid_item"
    );

    exit();
}


/*
|--------------------------------------------------------------------------
| VALID STATUSES
|--------------------------------------------------------------------------
*/

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


/*
|--------------------------------------------------------------------------
| GET ORDER ITEM + VERIFY SELLER
|--------------------------------------------------------------------------
*/

$check_stmt = $conn->prepare(
    "SELECT
        order_items.order_item_id,
        order_items.order_id,
        order_items.status,
        products.seller_id

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


if ($check_result->num_rows === 0) {

    header(
        "Location: orders.php?error=not_your_product"
    );

    exit();
}


$order_item =
    $check_result->fetch_assoc();


$order_id =
    (int) $order_item["order_id"];

$current_status =
    $order_item["status"];


/*
|--------------------------------------------------------------------------
| PREVENT CHANGING COMPLETED ITEM
|--------------------------------------------------------------------------
*/

if (
    $current_status === "Delivered" ||
    $current_status === "Cancelled"
) {

    header(
        "Location: orders.php?error=order_completed"
    );

    exit();
}


/*
|--------------------------------------------------------------------------
| CHECK VALID STATUS CHANGE
|--------------------------------------------------------------------------
*/

$valid_change = false;


if (
    $current_status === "Pending"
    &&
    (
        $status === "Processing" ||
        $status === "Cancelled"
    )
) {

    $valid_change = true;

}


elseif (
    $current_status === "Processing"
    &&
    (
        $status === "Delivered" ||
        $status === "Cancelled"
    )
) {

    $valid_change = true;

}


elseif (
    $current_status === $status
) {

    header(
        "Location: orders.php?success=status_updated"
    );

    exit();
}


if (!$valid_change) {

    header(
        "Location: orders.php?error=invalid_status"
    );

    exit();
}


/*
|--------------------------------------------------------------------------
| UPDATE ORDER ITEM
|--------------------------------------------------------------------------
*/

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


if (!$update_stmt->execute()) {

    header(
        "Location: orders.php?error=update_failed"
    );

    exit();
}


/*
|--------------------------------------------------------------------------
| UPDATE PARENT ORDER STATUS
|--------------------------------------------------------------------------
|
| IMPORTANT:
|
| The customer My Orders page reads orders.status.
|
| We only mark the whole order Delivered when
| ALL order items are Delivered.
|
|--------------------------------------------------------------------------
*/

if ($status === "Delivered") {

    $pending_stmt = $conn->prepare(
        "SELECT COUNT(*) AS remaining
         FROM order_items
         WHERE order_id = ?
         AND status NOT IN ('Delivered', 'Cancelled')"
    );


    if (!$pending_stmt) {

        header(
            "Location: orders.php?error=database"
        );

        exit();
    }


    $pending_stmt->bind_param(
        "i",
        $order_id
    );

    $pending_stmt->execute();

    $pending_result =
        $pending_stmt->get_result();

    $pending_data =
        $pending_result->fetch_assoc();

    $remaining =
        (int) $pending_data["remaining"];


    /*
    |--------------------------------------------------------------------------
    | ALL ITEMS DELIVERED
    |--------------------------------------------------------------------------
    */

    if ($remaining === 0) {

        /*
        |--------------------------------------------------------------------------
        | CHECK IF ANY ITEM WAS CANCELLED
        |--------------------------------------------------------------------------
        */

        $cancelled_stmt = $conn->prepare(
            "SELECT COUNT(*) AS cancelled
             FROM order_items
             WHERE order_id = ?
             AND status = 'Cancelled'"
        );


        $cancelled_stmt->bind_param(
            "i",
            $order_id
        );

        $cancelled_stmt->execute();

        $cancelled_result =
            $cancelled_stmt->get_result();

        $cancelled_data =
            $cancelled_result->fetch_assoc();

        $cancelled =
            (int) $cancelled_data["cancelled"];


        /*
        |--------------------------------------------------------------------------
        | SET ORDER STATUS
        |--------------------------------------------------------------------------
        */

        if ($cancelled === 0) {

            $order_update = $conn->prepare(
                "UPDATE orders
                 SET status = 'Delivered'
                 WHERE order_id = ?"
            );

            $order_update->bind_param(
                "i",
                $order_id
            );

            $order_update->execute();

        }

    }

}


/*
|--------------------------------------------------------------------------
| HANDLE CANCELLATION
|--------------------------------------------------------------------------
*/

if ($status === "Cancelled") {

    $remaining_stmt = $conn->prepare(
        "SELECT COUNT(*) AS remaining
         FROM order_items
         WHERE order_id = ?
         AND status NOT IN ('Delivered', 'Cancelled')"
    );

    $remaining_stmt->bind_param(
        "i",
        $order_id
    );

    $remaining_stmt->execute();

    $remaining_result =
        $remaining_stmt->get_result();

    $remaining_data =
        $remaining_result->fetch_assoc();

    $remaining =
        (int) $remaining_data["remaining"];


    /*
    |--------------------------------------------------------------------------
    | IF EVERYTHING IS CANCELLED
    |--------------------------------------------------------------------------
    */

    if ($remaining === 0) {

        $cancelled_update = $conn->prepare(
            "UPDATE orders
             SET status = 'Cancelled',
                 cancelled_at = CURRENT_TIMESTAMP
             WHERE order_id = ?"
        );

        $cancelled_update->bind_param(
            "i",
            $order_id
        );

        $cancelled_update->execute();

    }

}


/*
|--------------------------------------------------------------------------
| SUCCESS
|--------------------------------------------------------------------------
*/

header(
    "Location: orders.php?success=status_updated"
);

exit();

?>