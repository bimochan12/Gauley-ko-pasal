<?php
session_start();
require_once "../config/database.php";
$product_id =
    isset($_GET["id"])
    ? (int) $_GET["id"]
    : 0;
if ($product_id <= 0) {
    header("Location: products.php");
    exit();
}
$sql = "
    SELECT *
    FROM products
    WHERE product_id = ?
";
$stmt = $conn->prepare($sql);
$stmt->bind_param(
    "i",
    $product_id
);
$stmt->execute();
$result =
    $stmt->get_result();
if (
    !$result ||
    $result->num_rows === 0
) {
    header("Location: products.php");
    exit();
}
$product =
    $result->fetch_assoc();
$product_name =
    $product["product_name"] ?? "";
$description =
    $product["description"] ?? "";
$price =
    (float) ($product["price"] ?? 0);
$quantity =
    (int) ($product["quantity"] ?? 0);
$image =
    trim(
        $product["image"] ?? ""
    );
$image_path = "";
if ($image !== "") {
    
    if (
        file_exists(
            "../" . $image
        )
    ) {
        $image_path =
            "../" . $image;
    }
    
    elseif (
        file_exists($image)
    ) {
        $image_path =
            $image;
    }
    
    elseif (
        file_exists(
            "../uploads/" . $image
        )
    ) {
        $image_path =
            "../uploads/" . $image;
    }
}
$user_name = "";
if (
    isset($_SESSION["user_id"])
) {
    $user_id =
        (int) $_SESSION["user_id"];
    $user_stmt = $conn->prepare(
        "SELECT name
         FROM users
         WHERE user_id = ?"
    );
    if ($user_stmt) {
        $user_stmt->bind_param(
            "i",
            $user_id
        );
        $user_stmt->execute();
        $user_result =
            $user_stmt->get_result();
        if (
            $user_result &&
            $user_result->num_rows === 1
        ) {
            $user_data =
                $user_result->fetch_assoc();
            $user_name =
                $user_data["name"] ?? "";
        }
        $user_stmt->close();
    }
}
$avatar_initials = "U";
if ($user_name !== "") {
    $name_parts =
        preg_split(
            "/\s+/",
            trim($user_name)
        );
    if (
        count($name_parts) >= 2
    ) {
        $avatar_initials =
            strtoupper(
                substr(
                    $name_parts[0],
                    0,
                    1
                )
                .
                substr(
                    $name_parts[
                        count($name_parts) - 1
                    ],
                    0,
                    1
                )
            );
    }
    else {
        $avatar_initials =
            strtoupper(
                substr(
                    $user_name,
                    0,
                    2
                )
            );
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >
    <title>
        <?php
        echo htmlspecialchars(
            $product_name
        );
        ?>
        - Gauley Ko Pasal
    </title>
    <link
        rel="stylesheet"
        href="../css/customer.css"
    >
    <style>
        
        .profile-menu {
            position: relative;
            display: flex;
            align-items: center;
        }
        .profile-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #102a43;
            color: white;
            text-decoration: none;
            font-size: 12px;
            font-weight: 900;
            letter-spacing: .5px;
            border: 2px solid #e4e9ef;
            transition: .2s ease;
        }
        .profile-avatar:hover {
            background: #1976d2;
            transform: translateY(-1px);
        }
        .profile-welcome {
            font-size: 11px;
            color: #718096;
            margin-right: 8px;
            white-space: nowrap;
        }
        @media (max-width: 750px) {
            .profile-welcome {
                display: none;
            }
        }
        
        .product-detail {
            width: 92%;
            max-width: 1200px;
            margin: 50px auto 80px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 60px;
            align-items: start;
        }
        .product-detail-image {
            background: white;
            border: 1px solid #e4e9ef;
            border-radius: 20px;
            min-height: 500px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }
        .product-detail-image img {
            width: 100%;
            height: 500px;
            object-fit: contain;
            padding: 40px;
            display: block;
        }
        .product-detail-placeholder {
            font-size: 120px;
        }
        .product-detail-info {
            padding-top: 15px;
        }
        .product-detail-label {
            display: inline-block;
            color: #1976d2;
            font-size: 10px;
            font-weight: 900;
            letter-spacing: 2px;
            margin-bottom: 15px;
        }
        .product-detail-info h1 {
            font-size: clamp(
                34px,
                5vw,
                52px
            );
            line-height: 1.1;
            letter-spacing: -1.5px;
            color: #102a43;
            margin-bottom: 18px;
        }
        .product-detail-description {
            color: #718096;
            font-size: 15px;
            line-height: 1.8;
            margin-bottom: 25px;
        }
        .product-detail-price {
            font-size: 32px;
            font-weight: 900;
            color: #102a43;
            margin-bottom: 12px;
        }
        .product-stock {
            display: inline-block;
            padding: 7px 12px;
            border-radius: 30px;
            background: #eef6fd;
            color: #1976d2;
            font-size: 11px;
            font-weight: 800;
            margin-bottom: 30px;
        }
        .product-stock.out {
            background: #fff1f1;
            color: #d64545;
        }
        .quantity-label {
            display: block;
            font-size: 12px;
            font-weight: 800;
            color: #172033;
            margin-bottom: 8px;
        }
        .quantity-input {
            width: 100px;
            padding: 12px;
            border: 1px solid #dce3ea;
            border-radius: 9px;
            font-size: 15px;
            outline: none;
            margin-bottom: 18px;
        }
        .quantity-input:focus {
            border-color: #1976d2;
        }
        .product-actions {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }
        .add-cart-button {
            border: none;
            background: #102a43;
            color: white;
            padding: 14px 24px;
            border-radius: 9px;
            font-size: 13px;
            font-weight: 800;
            cursor: pointer;
        }
        .add-cart-button:hover {
            background: #1976d2;
        }
        .back-button {
            display: inline-block;
            padding: 13px 20px;
            border: 1px solid #dce3ea;
            border-radius: 9px;
            font-size: 13px;
            font-weight: 800;
            color: #102a43;
            background: white;
        }
        .back-button:hover {
            border-color: #1976d2;
            color: #1976d2;
        }
        @media (max-width: 800px) {
            .product-detail {
                grid-template-columns: 1fr;
                gap: 30px;
                margin-top: 30px;
            }
            .product-detail-image {
                min-height: 350px;
            }
            .product-detail-image img {
                height: 350px;
            }
        }
    </style>
</head>
<body>
<div class="topbar">
    🇳🇵 Local shopping made simple
</div>
<header class="navbar">
    <div class="nav-inner">
        
        <a
            href="index.php"
            class="logo"
        >
            Gauley Ko <span>Pasal</span>
        </a>
        
        <form
            class="search"
            method="GET"
            action="products.php"
        >
            <input
                type="text"
                name="search"
                placeholder="Search products..."
            >
            <button type="submit">
                🔍
            </button>
        </form>
        
        <nav class="nav-links">
            <a href="index.php">
                Home
            </a>
            <a href="products.php">
                Products
            </a>
            <a href="cart.php">
                🛒 Cart
            </a>
            <?php if (
                isset($_SESSION["user_id"])
            ): ?>
                <a href="my_orders.php">
                    My Orders
                </a>
                
                <div class="profile-menu">
                    <?php if (
                        $user_name !== ""
                    ): ?>
                        <span class="profile-welcome">
                            <?php
                            echo htmlspecialchars(
                                $user_name
                            );
                            ?>
                        </span>
                    <?php endif; ?>
                    <a
                        href="profile.php"
                        class="profile-avatar"
                        title="My Profile"
                        aria-label="My Profile"
                    >
                        <?php
                        echo htmlspecialchars(
                            $avatar_initials
                        );
                        ?>
                    </a>
                </div>
            <?php else: ?>
                <a href="login.php">
                    Login
                </a>
                <a href="register.php">
                    Register
                </a>
            <?php endif; ?>
        </nav>
    </div>
</header>
<div class="category-nav">
    <div class="category-inner">
        <a href="products.php">
            All
        </a>
        <a href="products.php?search=food">
            Food
        </a>
        <a href="products.php?search=groceries">
            Groceries
        </a>
        <a href="products.php?search=household">
            Household
        </a>
        <a href="products.php?search=personal">
            Personal Care
        </a>
        <a href="products.php?search=drinks">
            Drinks
        </a>
        <a href="products.php?search=snacks">
            Snacks
        </a>
    </div>
</div>
<main>
    <section class="product-detail">
        
        <div class="product-detail-image">
            <?php if (
                $image_path !== ""
            ): ?>
                <img
                    src="<?php
                        echo htmlspecialchars(
                            $image_path
                        );
                    ?>"
                    alt="<?php
                        echo htmlspecialchars(
                            $product_name
                        );
                    ?>"
                >
            <?php else: ?>
                <div class="product-detail-placeholder">
                    🛍️
                </div>
            <?php endif; ?>
        </div>
        
        <div class="product-detail-info">
            <span class="product-detail-label">
                GAULEY KO PASAL
            </span>
            <h1>
                <?php
                echo htmlspecialchars(
                    $product_name
                );
                ?>
            </h1>
            <p class="product-detail-description">
                <?php
                if (
                    $description !== ""
                ) {
                    echo nl2br(
                        htmlspecialchars(
                            $description
                        )
                    );
                }
                else {
                    echo
                        "No description available for this product.";
                }
                ?>
            </p>
            <div class="product-detail-price">
                Rs.
                <?php
                echo number_format(
                    $price,
                    2
                );
                ?>
            </div>
            <?php if (
                $quantity > 0
            ): ?>
                <span class="product-stock">
                    ✓ In Stock
                </span>
                <form
                    method="POST"
                    action="cart.php"
                >
                    <input
                        type="hidden"
                        name="product_id"
                        value="<?php
                            echo $product_id;
                        ?>"
                    >
                    <label
                        class="quantity-label"
                        for="quantity"
                    >
                        Quantity
                    </label>
                    <input
                        class="quantity-input"
                        type="number"
                        name="quantity"
                        id="quantity"
                        value="1"
                        min="1"
                        max="<?php
                            echo $quantity;
                        ?>"
                        required
                    >
                    <div class="product-actions">
                        <button
                            type="submit"
                            name="add_to_cart"
                            class="add-cart-button"
                        >
                            🛒 Add to Cart
                        </button>
                        <a
                            href="products.php"
                            class="back-button"
                        >
                            ← Continue Shopping
                        </a>
                    </div>
                </form>
            <?php else: ?>
                <span class="product-stock out">
                    Out of Stock
                </span>
                <div class="product-actions">
                    <a
                        href="products.php"
                        class="back-button"
                    >
                        ← Continue Shopping
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </section>
</main>
<section class="cta">
    <div>
        <span>
            GAULEY KO PASAL
        </span>
        <h2>
            More products to explore.
        </h2>
        <p>
            Find more everyday products
            from local sellers.
        </p>
    </div>
    <a
        href="products.php"
        class="cta-button"
    >
        Browse Products →
    </a>
</section>
<footer>
    <div class="footer-inner">
        <div>
            <h3>
                Gauley Ko Pasal
            </h3>
            <p>
                Your local online marketplace
                for everyday products.
            </p>
        </div>
        <div>
            <h4>
                Quick Links
            </h4>
            <a href="index.php">
                Home
            </a>
            <a href="products.php">
                Products
            </a>
            <a href="cart.php">
                Cart
            </a>
        </div>
        <div>
            <h4>
                Account
            </h4>
            <?php if (
                isset($_SESSION["user_id"])
            ): ?>
                <a href="my_orders.php">
                    My Orders
                </a>
                <a href="profile.php">
                    My Profile
                </a>
            <?php else: ?>
                <a href="login.php">
                    Login
                </a>
                <a href="register.php">
                    Register
                </a>
            <?php endif; ?>
        </div>
    </div>
    <div class="footer-bottom">
        © 2026 Gauley Ko Pasal.
        All rights reserved.
    </div>
</footer>
</body>
</html>

