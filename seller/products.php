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
$seller_id = $_SESSION["user_id"];
$sql = "SELECT
            products.product_id,
            products.product_name,
            products.description,
            products.price,
            products.quantity,
            products.image,
            categories.category_name
        FROM products
        LEFT JOIN categories
            ON products.category_id = categories.category_id
        WHERE products.seller_id = ?
        ORDER BY products.product_id DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param(
    "i",
    $seller_id
);
$stmt->execute();
$result = $stmt->get_result();
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
        My Products - Gauley Ko Pasal
    </title>
    <link
        rel="stylesheet"
        href="../style.css"
    >
    <style>
        
        .products-page {
            width: 92%;
            max-width: 1200px;
            margin: 45px auto 80px;
        }
        
        .products-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            gap: 20px;
            margin-bottom: 30px;
        }
        .products-header-text h2 {
            margin: 0 0 8px;
            font-size: 30px;
            color: #102a43;
        }
        .products-header-text p {
            margin: 0;
            color: #718096;
            font-size: 13px;
        }
        .add-product-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 12px 18px;
            background: #102a43;
            color: white;
            border-radius: 9px;
            text-decoration: none;
            font-size: 12px;
            font-weight: 800;
            white-space: nowrap;
            transition: 0.2s ease;
        }
        .add-product-button:hover {
            background: #1976d2;
        }
        
        .seller-products-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 18px;
        }
        
        .seller-product-card {
            background: white;
            border: 1px solid #e4e9ef;
            border-radius: 15px;
            padding: 15px;
            display: flex;
            align-items: center;
            gap: 15px;
            min-width: 0;
            transition:
                transform 0.2s ease,
                box-shadow 0.2s ease,
                border-color 0.2s ease;
        }
        .seller-product-card:hover {
            transform: translateY(-2px);
            border-color: #d5dee8;
            box-shadow:
                0 8px 22px rgba(
                    16,
                    42,
                    67,
                    0.08
                );
        }
        
        .seller-product-image {
            width: 105px;
            height: 105px;
            flex: 0 0 105px;
            background: #f5f7fa;
            border-radius: 11px;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1px solid #edf0f3;
        }
        .seller-product-image img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            padding: 8px;
        }
        .seller-product-placeholder {
            font-size: 35px;
            opacity: 0.7;
        }
        
        .seller-product-info {
            flex: 1;
            min-width: 0;
        }
        .seller-product-name {
            font-size: 16px;
            font-weight: 900;
            color: #102a43;
            margin-bottom: 5px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .seller-product-price {
            font-size: 14px;
            font-weight: 900;
            color: #1976d2;
            margin-bottom: 7px;
        }
        .seller-product-meta {
            display: flex;
            align-items: center;
            gap: 9px;
            flex-wrap: wrap;
            margin-bottom: 6px;
        }
        .stock-text {
            font-size: 11px;
            font-weight: 800;
            color: #4a5568;
        }
        .category-text {
            font-size: 10px;
            color: #718096;
            background: #f4f6f8;
            padding: 4px 7px;
            border-radius: 5px;
        }
        .seller-product-description {
            color: #718096;
            font-size: 11px;
            line-height: 1.4;
            margin: 0 0 9px;
            display: -webkit-box;
            -webkit-line-clamp: 1;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .seller-product-actions {
            display: flex;
            align-items: center;
            gap: 7px;
        }
        .product-action {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 7px 10px;
            border-radius: 7px;
            text-decoration: none;
            font-size: 10px;
            font-weight: 800;
            transition: 0.2s ease;
        }
        .details-button {
            background: #eef6fd;
            color: #1976d2;
        }
        .details-button:hover {
            background: #dceefd;
        }
        .edit-button {
            background: #102a43;
            color: white;
        }
        .edit-button:hover {
            background: #1976d2;
        }
        .delete-button {
            background: #fff1f1;
            color: #d64545;
        }
        .delete-button:hover {
            background: #ffe0e0;
        }
        
        .empty-products {
            background: white;
            border: 1px solid #e4e9ef;
            border-radius: 16px;
            padding: 60px 25px;
            text-align: center;
        }
        .empty-products-icon {
            font-size: 50px;
            margin-bottom: 15px;
        }
        .empty-products h3 {
            color: #102a43;
            margin-bottom: 8px;
        }
        .empty-products p {
            color: #718096;
            font-size: 13px;
            margin-bottom: 20px;
        }
        
        @media (max-width: 850px) {
            .seller-products-grid {
                grid-template-columns: 1fr;
            }
        }
        @media (max-width: 600px) {
            .products-page {
                width: 94%;
                margin-top: 30px;
            }
            .products-header {
                flex-direction: column;
                align-items: stretch;
            }
            .add-product-button {
                width: 100%;
            }
            .seller-product-card {
                padding: 13px;
                gap: 12px;
            }
            .seller-product-image {
                width: 85px;
                height: 85px;
                flex-basis: 85px;
            }
            .seller-product-name {
                font-size: 14px;
            }
            .seller-product-description {
                display: none;
            }
        }
        @media (max-width: 400px) {
            .seller-product-image {
                width: 75px;
                height: 75px;
                flex-basis: 75px;
            }
            .seller-product-actions {
                flex-wrap: wrap;
            }
        }
    </style>
</head>
<body>
<header>
    <h1>
        Gauley Ko Pasal
    </h1>
    <nav>
        <a href="dashboard.php">
            Dashboard
        </a>
        <a href="products.php">
            My Products
        </a>
        <a href="orders.php">
            Orders
        </a>
        <a href="logout.php">
            Logout
        </a>
    </nav>
</header>
<main class="products-page">
    
    <div class="products-header">
        <div class="products-header-text">
            <h2>
                My Products 📦
            </h2>
            <p>
                Manage the products you have added
                to the store.
            </p>
        </div>
        <a
            class="add-product-button"
            href="add_product.php"
        >
            + Add Product
        </a>
    </div>
    
    <?php if ($result->num_rows > 0): ?>
        <div class="seller-products-grid">
            <?php while (
                $product =
                $result->fetch_assoc()
            ): ?>
                <?php
                $product_id =
                    (int) $product["product_id"];
                $product_name =
                    $product["product_name"];
                $description =
                    $product["description"] ?? "";
                $price =
                    (float) $product["price"];
                $quantity =
                    (int) $product["quantity"];
                $category =
                    $product["category_name"]
                    ?? "Uncategorized";
                $image =
                    trim(
                        $product["image"] ?? ""
                    );
                ?>
                
                <article
                    class="seller-product-card"
                >
                    
                    <div
                        class="seller-product-image"
                    >
                        <?php if ($image !== ""): ?>
                            <img
                                src="../uploads/<?php
                                    echo htmlspecialchars(
                                        $image
                                    );
                                ?>"
                                alt="<?php
                                    echo htmlspecialchars(
                                        $product_name
                                    );
                                ?>"
                            >
                        <?php else: ?>
                            <div
                                class="seller-product-placeholder"
                            >
                                📦
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div
                        class="seller-product-info"
                    >
                        <div
                            class="seller-product-name"
                            title="<?php
                                echo htmlspecialchars(
                                    $product_name
                                );
                            ?>"
                        >
                            <?php
                            echo htmlspecialchars(
                                $product_name
                            );
                            ?>
                        </div>
                        <div
                            class="seller-product-price"
                        >
                            Rs.
                            <?php
                            echo number_format(
                                $price,
                                2
                            );
                            ?>
                        </div>
                        <div
                            class="seller-product-meta"
                        >
                            <span
                                class="stock-text"
                            >
                                Stock:
                                <?php
                                echo $quantity;
                                ?>
                            </span>
                            <span
                                class="category-text"
                            >
                                <?php
                                echo htmlspecialchars(
                                    $category
                                );
                                ?>
                            </span>
                        </div>
                        <?php if (
                            $description !== ""
                        ): ?>
                            <p
                                class="seller-product-description"
                                title="<?php
                                    echo htmlspecialchars(
                                        $description
                                    );
                                ?>"
                            >
                                <?php
                                echo htmlspecialchars(
                                    $description
                                );
                                ?>
                            </p>
                        <?php endif; ?>
                        
                        <div
                            class="seller-product-actions"
                        >
                            
                            <a
                                class="product-action details-button"
                                href="product.php?id=<?php
                                    echo $product_id;
                                ?>"
                            >
                                Details
                            </a>
                            
                            <a
                                class="product-action edit-button"
                                href="edit_product.php?id=<?php
                                    echo $product_id;
                                ?>"
                            >
                                Edit
                            </a>
                            
                            <a
                                class="product-action delete-button"
                                href="delete_product.php?id=<?php
                                    echo $product_id;
                                ?>"
                                onclick="
                                    return confirm(
                                        'Are you sure you want to delete this product?'
                                    );
                                "
                            >
                                Delete
                            </a>
                        </div>
                    </div>
                </article>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        
        <div class="empty-products">
            <div class="empty-products-icon">
                📦
            </div>
            <h3>
                No products yet.
            </h3>
            <p>
                Start by adding your first product.
            </p>
            <a
                class="add-product-button"
                href="add_product.php"
            >
                + Add Your First Product
            </a>
        </div>
    <?php endif; ?>
</main>
<footer>
    <p>
        Gauley Ko Pasal — Seller Panel 🇳🇵
    </p>
</footer>
</body>
</html>

