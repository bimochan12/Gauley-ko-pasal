<?php
session_start();
require_once "../config/database.php";
if (!isset($_SESSION["cart"])) {
    $_SESSION["cart"] = [];
}
if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["add_to_cart"])
) {
    $product_id =
        (int) ($_POST["product_id"] ?? 0);
    $quantity =
        (int) ($_POST["quantity"] ?? 1);
    if ($product_id > 0 && $quantity > 0) {
        $sql = "
            SELECT
                product_id,
                product_name,
                price,
                quantity
            FROM products
            WHERE product_id = ?
        ";
        $stmt =
            $conn->prepare($sql);
        $stmt->bind_param(
            "i",
            $product_id
        );
        $stmt->execute();
        $result =
            $stmt->get_result();
        if ($result->num_rows > 0) {
            $product =
                $result->fetch_assoc();
            $stock =
                (int) $product["quantity"];
            if ($stock > 0) {
                if (
                    isset(
                        $_SESSION["cart"][$product_id]
                    )
                ) {
                    $new_quantity =
                        $_SESSION["cart"][$product_id]
                        + $quantity;
                } else {
                    $new_quantity =
                        $quantity;
                }
                if ($new_quantity > $stock) {
                    $new_quantity =
                        $stock;
                }
                $_SESSION["cart"][$product_id] =
                    $new_quantity;
            }
        }
    }
    header("Location: cart.php");
    exit;
}
if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["update_cart"])
) {
    if (isset($_POST["quantities"])) {
        foreach (
            $_POST["quantities"]
            as $product_id => $quantity
        ) {
            $product_id =
                (int) $product_id;
            $quantity =
                (int) $quantity;
            if ($product_id <= 0) {
                continue;
            }
            if ($quantity <= 0) {
                unset(
                    $_SESSION["cart"][$product_id]
                );
                continue;
            }
            $sql = "
                SELECT quantity
                FROM products
                WHERE product_id = ?
            ";
            $stmt =
                $conn->prepare($sql);
            $stmt->bind_param(
                "i",
                $product_id
            );
            $stmt->execute();
            $result =
                $stmt->get_result();
            if ($result->num_rows > 0) {
                $product =
                    $result->fetch_assoc();
                $stock =
                    (int) $product["quantity"];
                if ($quantity > $stock) {
                    $quantity =
                        $stock;
                }
                if ($quantity > 0) {
                    $_SESSION["cart"][$product_id] =
                        $quantity;
                } else {
                    unset(
                        $_SESSION["cart"][$product_id]
                    );
                }
            } else {
                unset(
                    $_SESSION["cart"][$product_id]
                );
            }
        }
    }
    header("Location: cart.php");
    exit;
}
if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["remove_from_cart"])
) {
    $product_id =
        (int) ($_POST["product_id"] ?? 0);
    if ($product_id > 0) {
        unset(
            $_SESSION["cart"][$product_id]
        );
    }
    header("Location: cart.php");
    exit;
}
if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["clear_cart"])
) {
    $_SESSION["cart"] = [];
    header("Location: cart.php");
    exit;
}
$cart_items = [];
$subtotal = 0;
if (!empty($_SESSION["cart"])) {
    $product_ids =
        array_keys($_SESSION["cart"]);
    $product_ids =
        array_map(
            "intval",
            $product_ids
        );
    $placeholders =
        implode(
            ",",
            array_fill(
                0,
                count($product_ids),
                "?"
            )
        );
    $types =
        str_repeat(
            "i",
            count($product_ids)
        );
    $sql = "
        SELECT
            *
        FROM products
        WHERE product_id IN ($placeholders)
    ";
    $stmt =
        $conn->prepare($sql);
    $stmt->bind_param(
        $types,
        ...$product_ids
    );
    $stmt->execute();
    $result =
        $stmt->get_result();
    while (
        $product =
        $result->fetch_assoc()
    ) {
        $product_id =
            (int) $product["product_id"];
        if (
            !isset(
                $_SESSION["cart"][$product_id]
            )
        ) {
            continue;
        }
        $cart_quantity =
            (int)
            $_SESSION["cart"][$product_id];
        $stock =
            (int) $product["quantity"];
        if ($cart_quantity > $stock) {
            $cart_quantity =
                $stock;
            $_SESSION["cart"][$product_id] =
                $stock;
        }
        if ($cart_quantity <= 0) {
            unset(
                $_SESSION["cart"][$product_id]
            );
            continue;
        }
        $price =
            (float) $product["price"];
        $item_total =
            $price * $cart_quantity;
        $subtotal +=
            $item_total;
        $product["cart_quantity"] =
            $cart_quantity;
        $product["item_total"] =
            $item_total;
        $cart_items[] =
            $product;
    }
}
$total_items =
    count($cart_items);
function getProductImage($image)
{
    $image =
        trim($image ?? "");
    if ($image === "") {
        return "";
    }
    
    $image =
        str_replace(
            "\\",
            "/",
            $image
        );
    
    if (
        substr(
            $image,
            0,
            2
        ) === "./"
    ) {
        $image =
            substr(
                $image,
                2
            );
    }
    
    $path_one =
        "../" . ltrim(
            $image,
            "/"
        );
    if (
        file_exists($path_one)
    ) {
        return $path_one;
    }
    
    if (
        file_exists($image)
    ) {
        return $image;
    }
    
    $path_three =
        "../uploads/" .
        basename($image);
    if (
        file_exists($path_three)
    ) {
        return $path_three;
    }
    
    $absolute_upload =
        __DIR__ .
        "/../uploads/" .
        basename($image);
    if (
        file_exists($absolute_upload)
    ) {
        return "../uploads/" .
            basename($image);
    }
    return "";
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
        Shopping Cart - Gauley Ko Pasal
    </title>
    <link
        rel="stylesheet"
        href="../css/customer.css"
    >
    <style>
        
        .cart-page {
            width: 92%;
            max-width: 1200px;
            margin: 50px auto 80px;
        }
        .cart-header {
            margin-bottom: 30px;
        }
        .cart-header .section-label {
            display: inline-block;
            margin-bottom: 8px;
        }
        .cart-header h1 {
            font-size: 40px;
            color: #102a43;
            margin-bottom: 8px;
        }
        .cart-header p {
            color: #718096;
            font-size: 14px;
        }
        .cart-layout {
            display: grid;
            grid-template-columns:
                1fr 350px;
            gap: 25px;
            align-items: start;
        }
        
        .cart-items {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        .cart-item {
            background: white;
            border: 1px solid #e4e9ef;
            border-radius: 16px;
            padding: 18px;
            display: grid;
            grid-template-columns:
                110px 1fr auto;
            gap: 20px;
            align-items: center;
        }
        
        .cart-item-image {
            width: 110px;
            height: 110px;
            background: #f4f7fa;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }
        .cart-item-image img {
            display: block;
            width: 100%;
            height: 100%;
            object-fit: contain;
            padding: 8px;
        }
        .cart-item-placeholder {
            font-size: 38px;
        }
        
        .cart-item-info h3 {
            font-size: 16px;
            color: #102a43;
            margin-bottom: 7px;
        }
        .cart-item-info h3 a {
            color: inherit;
            text-decoration: none;
        }
        .cart-item-info h3 a:hover {
            color: #1976d2;
        }
        .cart-item-info p {
            color: #718096;
            font-size: 12px;
            margin-bottom: 8px;
        }
        .cart-item-price {
            font-size: 14px;
            font-weight: 800;
            color: #1976d2;
        }
        
        .cart-item-actions {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .quantity-control {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .quantity-control label {
            font-size: 11px;
            color: #718096;
            font-weight: 700;
        }
        .quantity-control input {
            width: 65px;
            padding: 9px;
            border: 1px solid #dce3ea;
            border-radius: 8px;
            text-align: center;
            font-size: 13px;
            outline: none;
        }
        .quantity-control input:focus {
            border-color: #1976d2;
        }
        .item-total {
            font-size: 16px;
            font-weight: 900;
            color: #102a43;
            white-space: nowrap;
        }
        .remove-button {
            border: none;
            background: #fff1f1;
            color: #d64545;
            padding: 8px 11px;
            border-radius: 8px;
            font-size: 11px;
            font-weight: 800;
            cursor: pointer;
        }
        .remove-button:hover {
            background: #ffe0e0;
        }
        
        .cart-summary {
            background: white;
            border: 1px solid #e4e9ef;
            border-radius: 16px;
            padding: 25px;
            position: sticky;
            top: 100px;
        }
        .cart-summary h2 {
            font-size: 20px;
            color: #102a43;
            margin-bottom: 25px;
        }
        .summary-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            color: #718096;
            font-size: 13px;
            gap: 15px;
        }
        .summary-row strong {
            color: #102a43;
        }
        .summary-total {
            border-top: 1px solid #e4e9ef;
            margin-top: 10px;
            padding-top: 18px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .summary-total span {
            font-size: 13px;
            font-weight: 800;
            color: #102a43;
        }
        .summary-total strong {
            font-size: 24px;
            color: #102a43;
        }
        .checkout-button {
            display: block;
            width: 100%;
            border: none;
            background: #102a43;
            color: white;
            padding: 14px;
            border-radius: 9px;
            text-align: center;
            font-size: 13px;
            font-weight: 800;
            margin-top: 22px;
            cursor: pointer;
            text-decoration: none;
            box-sizing: border-box;
        }
        .checkout-button:hover {
            background: #1976d2;
        }
        .continue-button {
            display: block;
            width: 100%;
            text-align: center;
            margin-top: 10px;
            padding: 12px;
            border: 1px solid #dce3ea;
            border-radius: 9px;
            font-size: 12px;
            font-weight: 800;
            color: #102a43;
            box-sizing: border-box;
            text-decoration: none;
        }
        .continue-button:hover {
            border-color: #1976d2;
            color: #1976d2;
        }
        .update-button {
            border: none;
            background: #eef6fd;
            color: #1976d2;
            padding: 9px 13px;
            border-radius: 8px;
            font-size: 11px;
            font-weight: 800;
            cursor: pointer;
        }
        .update-button:hover {
            background: #dceefd;
        }
        .clear-button {
            border: none;
            background: transparent;
            color: #d64545;
            font-size: 11px;
            font-weight: 700;
            cursor: pointer;
            margin-top: 15px;
        }
        .clear-button:hover {
            text-decoration: underline;
        }
        
        .empty-cart {
            background: white;
            border: 1px solid #e4e9ef;
            border-radius: 18px;
            padding: 70px 30px;
            text-align: center;
        }
        .empty-cart-icon {
            font-size: 70px;
            margin-bottom: 20px;
        }
        .empty-cart h2 {
            color: #102a43;
            font-size: 25px;
            margin-bottom: 10px;
        }
        .empty-cart p {
            color: #718096;
            font-size: 13px;
            margin-bottom: 25px;
        }
        .shop-button {
            display: inline-block;
            background: #102a43;
            color: white;
            padding: 13px 20px;
            border-radius: 9px;
            font-size: 12px;
            font-weight: 800;
            text-decoration: none;
        }
        .shop-button:hover {
            background: #1976d2;
        }
        
        @media (max-width: 850px) {
            .cart-layout {
                grid-template-columns: 1fr;
            }
            .cart-summary {
                position: static;
            }
        }
        @media (max-width: 600px) {
            .cart-header h1 {
                font-size: 32px;
            }
            .cart-item {
                grid-template-columns:
                    80px 1fr;
                gap: 15px;
            }
            .cart-item-image {
                width: 80px;
                height: 80px;
            }
            .cart-item-actions {
                grid-column: 1 / -1;
                justify-content: space-between;
                flex-wrap: wrap;
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
            <?php if (isset($_SESSION["user_id"])): ?>
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
<main class="cart-page">
    <div class="cart-header">
        <span class="section-label">
            YOUR CART
        </span>
        <h1>
            Shopping Cart
        </h1>
        <p>
            Review your items before checkout.
        </p>
    </div>
    <?php if (empty($cart_items)): ?>
        
        <div class="empty-cart">
            <div class="empty-cart-icon">
                🛒
            </div>
            <h2>
                Your cart is empty
            </h2>
            <p>
                Looks like you haven't added
                anything yet.
            </p>
            <a
                href="products.php"
                class="shop-button"
            >
                Start Shopping →
            </a>
        </div>
    <?php else: ?>
        
        <div class="cart-layout">
            
            <div class="cart-items">
                <form
                    method="POST"
                    action="cart.php"
                >
                    <?php foreach (
                        $cart_items
                        as $item
                    ): ?>
                        <?php
                        $item_id =
                            (int)
                            $item["product_id"];
                        $item_name =
                            $item["product_name"];
                        $item_price =
                            (float)
                            $item["price"];
                        $item_quantity =
                            (int)
                            $item["cart_quantity"];
                        $item_total =
                            (float)
                            $item["item_total"];
                        
                        $item_image =
                            getProductImage(
                                $item["image"] ?? ""
                            );
                        ?>
                        <article
                            class="cart-item"
                        >
                            
                            <div
                                class="cart-item-image"
                            >
                                <?php if (
                                    $item_image !== ""
                                ): ?>
                                    <img
                                        src="<?php
                                            echo htmlspecialchars(
                                                $item_image,
                                                ENT_QUOTES,
                                                "UTF-8"
                                            );
                                        ?>"
                                        alt="<?php
                                            echo htmlspecialchars(
                                                $item_name,
                                                ENT_QUOTES,
                                                "UTF-8"
                                            );
                                        ?>"
                                        onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
                                    >
                                    <div
                                        class="cart-item-placeholder"
                                        style="display:none;"
                                    >
                                        🛍️
                                    </div>
                                <?php else: ?>
                                    <div
                                        class="cart-item-placeholder"
                                    >
                                        🛍️
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div
                                class="cart-item-info"
                            >
                                <h3>
                                    <a
                                        href="product.php?id=<?php
                                            echo $item_id;
                                        ?>"
                                    >
                                        <?php
                                        echo htmlspecialchars(
                                            $item_name
                                        );
                                        ?>
                                    </a>
                                </h3>
                                <p>
                                    Rs.
                                    <?php
                                    echo number_format(
                                        $item_price,
                                        2
                                    );
                                    ?>
                                    each
                                </p>
                                <span
                                    class="cart-item-price"
                                >
                                    Item total:
                                    Rs.
                                    <?php
                                    echo number_format(
                                        $item_total,
                                        2
                                    );
                                    ?>
                                </span>
                            </div>
                            
                            <div
                                class="cart-item-actions"
                            >
                                <div
                                    class="quantity-control"
                                >
                                    <label
                                        for="quantity_<?php
                                            echo $item_id;
                                        ?>"
                                    >
                                        Qty
                                    </label>
                                    <input
                                        type="number"
                                        id="quantity_<?php
                                            echo $item_id;
                                        ?>"
                                        name="quantities[<?php
                                            echo $item_id;
                                        ?>]"
                                        value="<?php
                                            echo $item_quantity;
                                        ?>"
                                        min="1"
                                        max="<?php
                                            echo (int)
                                                $item["quantity"];
                                        ?>"
                                    >
                                </div>
                                <strong
                                    class="item-total"
                                >
                                    Rs.
                                    <?php
                                    echo number_format(
                                        $item_total,
                                        2
                                    );
                                    ?>
                                </strong>
                                <button
                                    type="submit"
                                    name="remove_from_cart"
                                    value="1"
                                    class="remove-button"
                                    formaction="cart.php"
                                    formmethod="POST"
                                    onclick="
                                        this.form.insertAdjacentHTML(
                                            'beforeend',
                                            '<input type=\'hidden\' name=\'product_id\' value=\'<?php echo $item_id; ?>\'>'
                                        );
                                    "
                                >
                                    Remove
                                </button>
                            </div>
                        </article>
                    <?php endforeach; ?>
                    <div
                        style="
                            display:flex;
                            justify-content:flex-end;
                            margin-top:15px;
                        "
                    >
                        <button
                            type="submit"
                            name="update_cart"
                            class="update-button"
                        >
                            Update Cart
                        </button>
                    </div>
                </form>
                
                <form
                    method="POST"
                    action="cart.php"
                    style="text-align:right;"
                >
                    <button
                        type="submit"
                        name="clear_cart"
                        class="clear-button"
                        onclick="
                            return confirm(
                                'Remove all items from your cart?'
                            );
                        "
                    >
                        Clear Cart
                    </button>
                </form>
            </div>
            
            <aside
                class="cart-summary"
            >
                <h2>
                    Order Summary
                </h2>
                <div class="summary-row">
                    <span>
                        Items
                    </span>
                    <strong>
                        <?php
                        echo $total_items;
                        ?>
                    </strong>
                </div>
                <div class="summary-row">
                    <span>
                        Subtotal
                    </span>
                    <strong>
                        Rs.
                        <?php
                        echo number_format(
                            $subtotal,
                            2
                        );
                        ?>
                    </strong>
                </div>
                <div class="summary-row">
                    <span>
                        Delivery
                    </span>
                    <strong>
                        Calculated at checkout
                    </strong>
                </div>
                <div class="summary-total">
                    <span>
                        Total
                    </span>
                    <strong>
                        Rs.
                        <?php
                        echo number_format(
                            $subtotal,
                            2
                        );
                        ?>
                    </strong>
                </div>
                <a
                    href="checkout.php"
                    class="checkout-button"
                >
                    Proceed to Checkout →
                </a>
                <a
                    href="products.php"
                    class="continue-button"
                >
                    ← Continue Shopping
                </a>
            </aside>
        </div>
    <?php endif; ?>
</main>
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

