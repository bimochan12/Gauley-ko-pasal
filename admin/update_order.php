<?php

session_start();

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] != "admin") {
    header("Location: login.php");
    exit();
}

require_once "../config/database.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $order_id = $_POST["order_id"];
    $status = $_POST["status"];

    $allowed_statuses = [
        "Pending",
        "Processing",
        "Delivered",
        "Cancelled"
    ];

    if (in_array($status, $allowed_statuses)) {

        $sql = "UPDATE orders
                SET status = ?
                WHERE order_id = ?";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $status, $order_id);
        $stmt->execute();
    }
}

header("Location: orders.php");
exit();

?>