<?php

session_start();

require_once "../config/database.php";

if (!isset($_GET["id"])) {
    header("Location: index.php");
    exit();
}

$product_id = (int) $_GET["id"];

$stmt = $conn->prepare(
    "SELECT *
     FROM products
     WHERE product_id = ?"
);

$stmt->bind_param("i", $product_id);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows != 1) {
    header("Location: index.php");
    exit();
}

$product = $result->fetch_assoc();

?>

<!DOCTYPE html>
<html>

<head>

    <title>
        <?php echo htmlspecialchars($product["product_name"]); ?>
        - Gauley Ko Pasal
    </title>

    <link rel="stylesheet" href="../style.css">

    <style>

        .detail-image {
            width: 100%;
            max-width: 450px;
            height: 400px;
            object-fit: contain;
            background: #f5f1e8;
            border-radius: 12px;
        }

        .product-detail {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            align-items: center;
        }

        @media (max-width: 700px) {

            .product-detail {
                grid-template-columns: 1fr;
            }

        }

    </style>

</head>

<body>

<header>

    <h1>Gauley Ko Pasal</h1>

    <nav>

        <a href="index.php">Home</a>

        <a href="cart.php">Cart</a>

        <?php if (isset($_SESSION["user_id"])): ?>

            <a href="orders.php">My Orders</a>

            <a href="logout.php">Logout</a>

        <?php else: ?>

            <a href="login.php">Login</a>

            <a href="register.php">Register</a>

        <?php endif; ?>

    </nav>

</header>


<div class="container">

    <div class="card product-detail">


        <div>

            <?php if (!empty($product["image"])): ?>

                <img
                    class="detail-image"
                    src="../uploads/<?php
                    echo htmlspecialchars(
                        $product["image"]
                    );
                    ?>"
                    alt="<?php
                    echo htmlspecialchars(
                        $product["product_name"]
                    );
                    ?>"
                >

            <?php else: ?>

                <div class="detail-image">

                    No Image

                </div>

            <?php endif; ?>

        </div>


        <div>

            <h2>

                <?php
                echo htmlspecialchars(
                    $product["product_name"]
                );
                ?>

            </h2>


            <p>

                <?php
                echo htmlspecialchars(
                    $product["description"]
                );
                ?>

            </p>


            <h2>

                Rs.
                <?php
                echo number_format(
                    $product["price"],
                    2
                );
                ?>

            </h2>


            <p>

                <strong>Available:</strong>

                <?php
                echo $product["quantity"];
                ?>

            </p>


            <?php if ($product["quantity"] > 0): ?>

                <?php if (isset($_SESSION["user_id"])): ?>

                    <a
                        class="button"
                        href="add_to_cart.php?id=<?php
                        echo $product["product_id"];
                        ?>"
                    >
                        Add to Cart 🛒
                    </a>

                <?php else: ?>

                    <a
                        class="button"
                        href="login.php"
                    >
                        Login to Buy
                    </a>

                <?php endif; ?>

            <?php else: ?>

                <p>

                    <strong>
                        Out of Stock
                    </strong>

                </p>

            <?php endif; ?>


            <br><br>

            <a href="index.php">
                ← Continue Shopping
            </a>

        </div>

    </div>

</div>


<footer>

    <p>
        © <?php echo date("Y"); ?> Gauley Ko Pasal
    </p>

    <p>
        Your friendly local online shop 🇳🇵
    </p>

</footer>

</body>

</html>