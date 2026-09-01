<?php
session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}
require_once "../config/database.php";
if (isset($_GET["id"])) {
    $product_id = $_GET["id"];
    $sql = "DELETE FROM products WHERE product_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $stmt->close();
}
header("Location: products.php");
exit();
?>

