<?php

session_start();

require_once "../config/database.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

if (!isset($_GET["id"])) {
    header("Location: index.php");
    exit();
}

$product_id = (int) $_GET["id"];

$sql = "SELECT * FROM products WHERE product_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $product_id);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows != 1) {
    header("Location: index.php");
    exit();
}

$product = $result->fetch_assoc();

if ($product["quantity"] <= 0) {
    header("Location: index.php");
    exit();
}

if (!isset($_SESSION["cart"])) {
    $_SESSION["cart"] = [];
}

$current_quantity = $_SESSION["cart"][$product_id] ?? 0;

if ($current_quantity < $product["quantity"]) {
    $_SESSION["cart"][$product_id] = $current_quantity + 1;
}

header("Location: cart.php");
exit();

?>