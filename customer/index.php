<?php

session_start();

require_once "../config/database.php";

$sql = "SELECT *
        FROM products
        WHERE quantity > 0
        ORDER BY product_id DESC";

$result = $conn->query($sql);

?>

<!DOCTYPE html>
<html>

<head>

    <title>Gauley Ko Pasal</title>

    <link rel="stylesheet" href="../style.css">

    <style>

        .product-image {
            width: 100%;
            height: 200px;
            object-fit: contain;
            background: #f5f1e8;
            border-radius: 8px;
            margin-bottom: 15px;
        }

        .no-image {
            width: 100%;
            height: 200px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f5f1e8;
            border-radius: 8px;
            margin-bottom: 15px;
            color: #777;
        }

        .product-card-link {
            text-decoration: none;
            color: inherit;
            display: block;
        }

        .product-card-link:hover {
            opacity: 0.9;
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

    <div class="hero">

        <h2>Namaste! 🙏</h2>

        <p>
            Welcome to Gauley Ko Pasal —
            your friendly local online shop.
        </p>

        <?php if (isset($_SESSION["user_id"])): ?>

            <p>
                Welcome,
                <strong>
                    <?php
                    echo htmlspecialchars(
                        $_SESSION["name"]
                    );
                    ?>
                </strong>!
            </p>

        <?php else: ?>

            <p>
                Login or create an account to start shopping.
            </p>

        <?php endif; ?>

    </div>


    <h2>Our Products</h2>

    <br>


    <?php if ($result->num_rows == 0): ?>

        <div class="card">

            <h3>No products available.</h3>

            <p>
                Please check back later.
            </p>

        </div>

    <?php else: ?>

        <div class="products">

            <?php while ($product = $result->fetch_assoc()): ?>

                <div class="product-card">


                    <a
                        class="product-card-link"
                        href="product.php?id=<?php
                        echo $product["product_id"];
                        ?>"
                    >


                        <?php if (!empty($product["image"])): ?>

                            <img
                                class="product-image"
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

                            <div class="no-image">
                                No Image
                            </div>

                        <?php endif; ?>


                        <h3>

                            <?php
                            echo htmlspecialchars(
                                $product["product_name"]
                            );
                            ?>

                        </h3>


                        <p>

                            <?php
                            echo htmlspecialchars(
                                $product["description"]
                            );
                            ?>

                        </p>


                        <p class="price">

                            Rs.
                            <?php
                            echo number_format(
                                $product["price"],
                                2
                            );
                            ?>

                        </p>


                        <p class="stock">

                            Available:
                            <strong>
                                <?php
                                echo $product["quantity"];
                                ?>
                            </strong>

                        </p>


                    </a>


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


                </div>

            <?php endwhile; ?>

        </div>

    <?php endif; ?>

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