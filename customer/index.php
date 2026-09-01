<?php
session_start();
require_once "../config/database.php";
$search = trim($_GET["search"] ?? "");
$user_name = "";
if (isset($_SESSION["user_id"])) {
    $user_id = (int) $_SESSION["user_id"];
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
        if ($user_result->num_rows === 1) {
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
    if (count($name_parts) >= 2) {
        $avatar_initials =
            strtoupper(
                substr($name_parts[0], 0, 1) .
                substr(
                    $name_parts[count($name_parts) - 1],
                    0,
                    1
                )
            );
    } else {
        $avatar_initials =
            strtoupper(
                substr($user_name, 0, 2)
            );
    }
}
if ($search !== "") {
    $sql = "
        SELECT *
        FROM products
        WHERE quantity > 0
        AND (
            product_name LIKE ?
            OR description LIKE ?
        )
        ORDER BY product_id DESC
    ";
    $stmt =
        $conn->prepare($sql);
    $search_term =
        "%" . $search . "%";
    $stmt->bind_param(
        "ss",
        $search_term,
        $search_term
    );
    $stmt->execute();
    $result =
        $stmt->get_result();
} else {
    $sql = "
        SELECT *
        FROM products
        WHERE quantity > 0
        ORDER BY product_id DESC
    ";
    $result =
        $conn->query($sql);
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
        Gauley Ko Pasal
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
        .profile-avatar-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #102a43;
            color: white;
            text-decoration: none;
            font-size: 20px;
            border: 2px solid #e4e9ef;
            transition: .2s ease;
        }
        .profile-avatar-icon:hover {
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
            action="index.php"
        >
            <input
                type="text"
                name="search"
                placeholder="Search products..."
                value="<?php
                    echo htmlspecialchars(
                        $search
                    );
                ?>"
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
            <?php if (isset($_SESSION["user_id"])): ?>
                <a href="my_orders.php">
                    My Orders
                </a>
                
                <div class="profile-menu">
                    <?php if ($user_name !== ""): ?>
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
<section class="hero">
    <div class="hero-content">
        <span class="hero-label">
            GAULEY KO PASAL
        </span>
        <h1>
            Your local mart,
            <span>online.</span>
        </h1>
        <p>
            Shop everyday products from
            Gauley Ko Pasal and get what
            you need without the hassle.
        </p>
        <div class="hero-buttons">
            <a
                href="products.php"
                class="cta-button"
            >
                Shop Products →
            </a>
            <?php if (!isset($_SESSION["user_id"])): ?>
                <a
                    href="register.php"
                    class="shop-button"
                >
                    Create Account
                </a>
            <?php endif; ?>
        </div>
    </div>
    <div class="hero-visual">
        <div class="hero-circle">
            🛒
        </div>
    </div>
</section>
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
    <section class="section">
        <div class="section-header">
            <div>
                <span class="section-label">
                    OUR STORE
                </span>
                <h2>
                    <?php if ($search !== ""): ?>
                        Search results for
                        "<?php
                        echo htmlspecialchars(
                            $search
                        );
                        ?>"
                    <?php else: ?>
                        Explore products
                    <?php endif; ?>
                </h2>
                <p>
                    Find everyday products
                    from Gauley Ko Pasal.
                </p>
            </div>
            <?php if ($search !== ""): ?>
                <a href="index.php">
                    View all products →
                </a>
            <?php endif; ?>
        </div>
        <div class="product-grid">
            <?php if (
                $result &&
                $result->num_rows > 0
            ): ?>
                <?php while (
                    $product =
                    $result->fetch_assoc()
                ): ?>
                    <?php
                    $product_id =
                        (int)
                        $product["product_id"];
                    $product_name =
                        $product["product_name"];
                    $description =
                        $product["description"] ?? "";
                    $price =
                        (float)
                        $product["price"];
                    $image =
                        trim(
                            $product["image"] ?? ""
                        );
                    
                    $image_path = "";
                    if ($image !== "") {
                        
                        $image_filename =
                            basename($image);
                        $possible_image_path =
                            "../uploads/" .
                            $image_filename;
                        if (
                            file_exists(
                                $possible_image_path
                            )
                        ) {
                            $image_path =
                                $possible_image_path;
                        }
                    }
                    ?>
                    <article class="product-card">
                        
                        <div class="product-image">
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
                                <div class="product-placeholder">
                                    🛍️
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="product-info">
                            <h3>
                                <?php
                                echo htmlspecialchars(
                                    $product_name
                                );
                                ?>
                            </h3>
                            <p class="product-description">
                                <?php
                                echo htmlspecialchars(
                                    $description
                                );
                                ?>
                            </p>
                            <div class="product-bottom">
                                <strong class="price">
                                    Rs.
                                    <?php
                                    echo number_format(
                                        $price,
                                        2
                                    );
                                    ?>
                                </strong>
                                <a
                                    href="product.php?id=<?php
                                        echo $product_id;
                                    ?>"
                                    class="cart-btn"
                                >
                                    View →
                                </a>
                            </div>
                        </div>
                    </article>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="empty-products">
                    <div class="product-placeholder">
                        🛒
                    </div>
                    <h3>
                        No products found
                    </h3>
                    <p>
                        Try another search or
                        browse all available products.
                    </p>
                    <br>
                    <a
                        href="index.php"
                        class="cart-btn"
                    >
                        View All Products
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
            Find what you need today.
        </h2>
        <p>
            Simple local shopping for everyday needs.
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

