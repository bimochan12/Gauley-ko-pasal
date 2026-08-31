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

if (!isset($_GET["id"])) {

    header("Location: products.php");
    exit();
}

$product_id = (int) $_GET["id"];
$seller_id = $_SESSION["user_id"];

$sql = "DELETE FROM products
        WHERE product_id = ?
        AND seller_id = ?";

$stmt = $conn->prepare($sql);

$stmt->bind_param(
    "ii",
    $product_id,
    $seller_id
);

$stmt->execute();

header("Location: products.php");
exit();

?>
