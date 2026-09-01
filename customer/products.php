<?php
session_start();
require_once "../config/database.php";
$search = trim(
    $_GET["search"] ?? ""
);
$main_category_id =
    isset($_GET["category"]) &&
    is_numeric($_GET["category"])
        ? (int) $_GET["category"]
        : 0;
$subcategory_id =
    isset($_GET["subcategory"]) &&
    is_numeric($_GET["subcategory"])
        ? (int) $_GET["subcategory"]
        : 0;
$main_categories = [];
$main_category_sql = "
    SELECT
        main_category_id,
        category_name
    FROM main_categories
    ORDER BY
        CASE
            WHEN LOWER(category_name) = 'other'
            THEN 1
            ELSE 0
        END,
        category_name ASC
";
$main_category_result =
    $conn->query(
        $main_category_sql
    );
if ($main_category_result) {
    while (
        $main_category =
        $main_category_result->fetch_assoc()
    ) {
        $main_categories[] =
            $main_category;
    }
}
$current_category_name = "";
if ($main_category_id > 0) {
    $category_name_stmt =
        $conn->prepare(
            "SELECT category_name
             FROM main_categories
             WHERE main_category_id = ?"
        );
    if ($category_name_stmt) {
        $category_name_stmt->bind_param(
            "i",
            $main_category_id
        );
        $category_name_stmt->execute();
        $category_name_result =
            $category_name_stmt->get_result();
        if (
            $category_name_result->num_rows === 1
        ) {
            $current_category =
                $category_name_result
                    ->fetch_assoc();
            $current_category_name =
                $current_category[
                    "category_name"
                ];
        }
        $category_name_stmt->close();
    }
}
$current_subcategory_name = "";
if ($subcategory_id > 0) {
    $subcategory_name_stmt =
        $conn->prepare(
            "SELECT subcategory_name
             FROM subcategories
             WHERE subcategory_id = ?"
        );
    if ($subcategory_name_stmt) {
        $subcategory_name_stmt->bind_param(
            "i",
            $subcategory_id
        );
        $subcategory_name_stmt->execute();
        $subcategory_name_result =
            $subcategory_name_stmt->get_result();
        if (
            $subcategory_name_result->num_rows === 1
        ) {
            $current_subcategory =
                $subcategory_name_result
                    ->fetch_assoc();
            $current_subcategory_name =
                $current_subcategory[
                    "subcategory_name"
                ];
        }
        $subcategory_name_stmt->close();
    }
}
$products = [];
$stmt = null;
if (
    $search !== "" &&
    $subcategory_id > 0
) {
    $sql = "
        SELECT
            p.product_id,
            p.product_name,
            p.description,
            p.price,
            p.quantity,
            p.image,
            p.category_id,
            p.main_category_id,
            mc.category_name
                AS main_category_name,
            sc.subcategory_name
        FROM products p
        LEFT JOIN main_categories mc
            ON p.main_category_id =
               mc.main_category_id
        LEFT JOIN subcategories sc
            ON p.category_id =
               sc.subcategory_id
        WHERE p.quantity > 0
        AND (
            p.product_name LIKE ?
            OR p.description LIKE ?
        )
        AND p.category_id = ?
        ORDER BY
            p.product_id DESC
    ";
    $stmt =
        $conn->prepare($sql);
    if ($stmt) {
        $search_term =
            "%" .
            $search .
            "%";
        $stmt->bind_param(
            "ssi",
            $search_term,
            $search_term,
            $subcategory_id
        );
    }
}
elseif (
    $search !== "" &&
    $main_category_id > 0
) {
    $sql = "
        SELECT
            p.product_id,
            p.product_name,
            p.description,
            p.price,
            p.quantity,
            p.image,
            p.category_id,
            p.main_category_id,
            mc.category_name
                AS main_category_name,
            sc.subcategory_name
        FROM products p
        LEFT JOIN main_categories mc
            ON p.main_category_id =
               mc.main_category_id
        LEFT JOIN subcategories sc
            ON p.category_id =
               sc.subcategory_id
        WHERE p.quantity > 0
        AND (
            p.product_name LIKE ?
            OR p.description LIKE ?
        )
        AND p.main_category_id = ?
        ORDER BY
            p.product_id DESC
    ";
    $stmt =
        $conn->prepare($sql);
    if ($stmt) {
        $search_term =
            "%" .
            $search .
            "%";
        $stmt->bind_param(
            "ssi",
            $search_term,
            $search_term,
            $main_category_id
        );
    }
}
elseif (
    $search !== ""
) {
    $sql = "
        SELECT
            p.product_id,
            p.product_name,
            p.description,
            p.price,
            p.quantity,
            p.image,
            p.category_id,
            p.main_category_id,
            mc.category_name
                AS main_category_name,
            sc.subcategory_name
        FROM products p
        LEFT JOIN main_categories mc
            ON p.main_category_id =
               mc.main_category_id
        LEFT JOIN subcategories sc
            ON p.category_id =
               sc.subcategory_id
        WHERE p.quantity > 0
        AND (
            p.product_name LIKE ?
            OR p.description LIKE ?
        )
        ORDER BY
            p.product_id DESC
    ";
    $stmt =
        $conn->prepare($sql);
    if ($stmt) {
        $search_term =
            "%" .
            $search .
            "%";
        $stmt->bind_param(
            "ss",
            $search_term,
            $search_term
        );
    }
}
elseif (
    $subcategory_id > 0
) {
    $sql = "
        SELECT
            p.product_id,
            p.product_name,
            p.description,
            p.price,
            p.quantity,
            p.image,
            p.category_id,
            p.main_category_id,
            mc.category_name
                AS main_category_name,
            sc.subcategory_name
        FROM products p
        LEFT JOIN main_categories mc
            ON p.main_category_id =
               mc.main_category_id
        LEFT JOIN subcategories sc
            ON p.category_id =
               sc.subcategory_id
        WHERE p.quantity > 0
        AND p.category_id = ?
        ORDER BY
            p.product_id DESC
    ";
    $stmt =
        $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param(
            "i",
            $subcategory_id
        );
    }
}
elseif (
    $main_category_id > 0
) {
    $sql = "
        SELECT
            p.product_id,
            p.product_name,
            p.description,
            p.price,
            p.quantity,
            p.image,
            p.category_id,
            p.main_category_id,
            mc.category_name
                AS main_category_name,
            sc.subcategory_name
        FROM products p
        LEFT JOIN main_categories mc
            ON p.main_category_id =
               mc.main_category_id
        LEFT JOIN subcategories sc
            ON p.category_id =
               sc.subcategory_id
        WHERE p.quantity > 0
        AND p.main_category_id = ?
        ORDER BY
            p.product_id DESC
    ";
    $stmt =
        $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param(
            "i",
            $main_category_id
        );
    }
}
else {
    $sql = "
        SELECT
            p.product_id,
            p.product_name,
            p.description,
            p.price,
            p.quantity,
            p.image,
            p.category_id,
            p.main_category_id,
            mc.category_name
                AS main_category_name,
            sc.subcategory_name
        FROM products p
        LEFT JOIN main_categories mc
            ON p.main_category_id =
               mc.main_category_id
        LEFT JOIN subcategories sc
            ON p.category_id =
               sc.subcategory_id
        WHERE p.quantity > 0
        ORDER BY
            p.product_id DESC
    ";
    $result =
        $conn->query($sql);
}
if ($stmt) {
    $stmt->execute();
    $result =
        $stmt->get_result();
    $stmt->close();
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
        Products - Gauley Ko Pasal
    </title>
    <link
        rel="stylesheet"
        href="../css/customer.css"
    >
    <style>
        
        .other-category-button {
            border: none;
            background: transparent;
            padding: 9px 15px;
            border-radius: 20px;
            color: #4a5568;
            font-size: 12px;
            font-weight: 800;
            cursor: pointer;
            white-space: nowrap;
            transition: 0.2s ease;
        }
        .other-category-button:hover,
        .other-category-button.active {
            background: #102a43;
            color: white;
        }
        
        .other-category-panel {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            z-index: 9999;
            background:
                rgba(
                    16,
                    42,
                    67,
                    0.35
                );
            padding:
                100px
                20px
                30px;
            overflow-y: auto;
        }
        .other-category-panel.show {
            display: block;
        }
        
        .other-category-box {
            width: 92%;
            max-width: 700px;
            margin: 0 auto;
            background: white;
            border-radius: 18px;
            border:
                1px solid


            box-shadow:
                0 25px 70px
                rgba(
                    20,
                    40,
                    70,
                    0.22
                );
            padding: 22px;
        }
        
        .other-category-header {
            display: flex;
            justify-content:
                space-between;
            align-items: center;
            margin-bottom: 18px;
        }
        .other-category-header span {
            color: #1976d2;
            font-size: 9px;
            font-weight: 900;
            letter-spacing: 1.8px;
        }
        .other-category-header h3 {
            margin: 5px 0 0;
            color: #102a43;
            font-size: 22px;
        }
        
        .other-close {
            width: 36px;
            height: 36px;
            border: none;
            border-radius: 50%;
            background: #f1f4f7;
            color: #102a43;
            font-size: 23px;
            cursor: pointer;
        }
        .other-close:hover {
            background: #e4e9ef;
        }
        
        .category-search {
            display: flex;
            align-items: center;
            gap: 10px;
            border:
                1px solid


            background: #f8fafc;
            border-radius: 11px;
            padding:
                11px
                14px;
            margin-bottom: 15px;
        }
        .category-search span {
            font-size: 15px;
        }
        .category-search input {
            border: none;
            outline: none;
            background: transparent;
            width: 100%;
            font-size: 13px;
            color: #172033;
        }
        
        .category-results {
            max-height: 450px;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        
        .category-result {
            display: flex;
            align-items: center;
            gap: 12px;
            padding:
                12px
                13px;
            border-radius: 10px;
            text-decoration: none;
            color: #172033;
            font-size: 13px;
            font-weight: 700;
            transition: 0.15s ease;
        }
        .category-result:hover {
            background: #eef5fb;
            color: #1976d2;
        }
        
        .subcategory-result {
            padding-left: 28px;
            font-weight: 600;
        }
        .subcategory-result small {
            margin-left: auto;
            color: #8a97a6;
            font-size: 10px;
            font-weight: 500;
        }
        
        .category-result-icon {
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f0f5fa;
            border-radius: 8px;
            flex-shrink: 0;
        }
        
        .category-arrow {
            margin-left: auto;
            color: #9aa7b5;
        }
        
        .no-category-results {
            text-align: center;
            padding: 35px 20px;
            color: #718096;
        }
        .no-category-results div {
            font-size: 30px;
            margin-bottom: 8px;
        }
        .no-category-results strong {
            color: #102a43;
            display: block;
            margin-bottom: 5px;
        }
        .no-category-results p {
            font-size: 12px;
        }
        
        .product-category {
            display: inline-block;
            margin-top: 8px;
            padding:
                5px
                9px;
            background: #eef5fb;
            color: #1976d2;
            border-radius: 7px;
            font-size: 10px;
            font-weight: 800;
        }
        .product-subcategory {
            display: block;
            color: #718096;
            font-size: 11px;
            margin-top: 5px;
        }
        
        .product-image {
            overflow: hidden;
        }
        .product-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }
        
        @media (max-width: 650px) {
            .other-category-panel {
                padding-top: 70px;
            }
            .other-category-box {
                width: 94%;
                padding: 16px;
            }
            .subcategory-result small {
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
            Gauley Ko
            <span>Pasal</span>
        </a>
        
        <form
            class="search"
            method="GET"
            action="products.php"
        >
            <?php if (
                $main_category_id > 0
            ): ?>
                <input
                    type="hidden"
                    name="category"
                    value="<?php
                        echo $main_category_id;
                    ?>"
                >
            <?php endif; ?>
            <?php if (
                $subcategory_id > 0
            ): ?>
                <input
                    type="hidden"
                    name="subcategory"
                    value="<?php
                        echo $subcategory_id;
                    ?>"
                >
            <?php endif; ?>
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
            <a href="my_orders.php">
                My Orders
            </a>
            <a href="profile.php">
                Profile
            </a>
        </nav>
    </div>
</header>
<div class="category-nav">
    <div class="category-inner">
        
        <a
            href="products.php"
            class="<?php
                echo (
                    $main_category_id === 0 &&
                    $subcategory_id === 0 &&
                    $search === ""
                )
                    ? "active"
                    : "";
            ?>"
        >
            All
        </a>
        
        <?php foreach (
            $main_categories
            as $main_category
        ): ?>
            <?php
            $mc_id =
                (int)
                $main_category[
                    "main_category_id"
                ];
            $mc_name =
                $main_category[
                    "category_name"
                ];
            if (
                strtolower(
                    trim($mc_name)
                ) === "other"
            ) {
                continue;
            }
            ?>
            <a
                href="products.php?category=<?php
                    echo $mc_id;
                ?>"
                class="<?php
                    echo (
                        $main_category_id ===
                        $mc_id &&
                        $subcategory_id === 0
                    )
                        ? "active"
                        : "";
                ?>"
            >
                <?php
                echo htmlspecialchars(
                    $mc_name
                );
                ?>
            </a>
        <?php endforeach; ?>
        
        <button
            type="button"
            class="other-category-button"
            id="otherCategoryButton"
        >
            Other ▾
        </button>
    </div>
</div>
<div
    class="other-category-panel"
    id="otherCategoryPanel"
>
    <div class="other-category-box">
        
        <div class="other-category-header">
            <div>
                <span>
                    BROWSE CATEGORIES
                </span>
                <h3>
                    Find a category
                </h3>
            </div>
            <button
                type="button"
                id="closeOtherCategory"
                class="other-close"
            >
                ×
            </button>
        </div>
        
        <div class="category-search">
            <span>
                🔍
            </span>
            <input
                type="text"
                id="categorySearch"
                placeholder="Search categories..."
                autocomplete="off"
            >
        </div>
        
        <div
            class="category-results"
            id="categoryResults"
        >
            <?php foreach (
                $main_categories
                as $main_category
            ): ?>
                <?php
                $mc_id =
                    (int)
                    $main_category[
                        "main_category_id"
                    ];
                $mc_name =
                    $main_category[
                        "category_name"
                    ];
                ?>
                
                <a
                    href="products.php?category=<?php
                        echo $mc_id;
                    ?>"
                    class="category-result"
                    data-category-name="<?php
                        echo htmlspecialchars(
                            strtolower(
                                $mc_name
                            )
                        );
                    ?>"
                >
                    <span
                        class="category-result-icon"
                    >
                        🛒
                    </span>
                    <span>
                        <?php
                        echo htmlspecialchars(
                            $mc_name
                        );
                        ?>
                    </span>
                    <span
                        class="category-arrow"
                    >
                        →
                    </span>
                </a>
                <?php
                
                $sub_sql = "
                    SELECT
                        subcategory_id,
                        subcategory_name
                    FROM subcategories
                    WHERE main_category_id = ?
                    ORDER BY
                        subcategory_name ASC
                ";
                $sub_stmt =
                    $conn->prepare(
                        $sub_sql
                    );
                ?>
                <?php if ($sub_stmt): ?>
                    <?php
                    $sub_stmt->bind_param(
                        "i",
                        $mc_id
                    );
                    $sub_stmt->execute();
                    $sub_result =
                        $sub_stmt->get_result();
                    ?>
                    <?php while (
                        $sub =
                        $sub_result->fetch_assoc()
                    ): ?>
                        <?php
                        $sub_id =
                            (int)
                            $sub[
                                "subcategory_id"
                            ];
                        $sub_name =
                            $sub[
                                "subcategory_name"
                            ];
                        ?>
                        <a
                            href="products.php?category=<?php
                                echo $mc_id;
                            ?>&subcategory=<?php
                                echo $sub_id;
                            ?>"
                            class="
                                category-result
                                subcategory-result
                            "
                            data-category-name="<?php
                                echo htmlspecialchars(
                                    strtolower(
                                        $sub_name
                                    )
                                );
                            ?>"
                        >
                            <span
                                class="
                                    category-result-icon
                                "
                            >
                                🏷️
                            </span>
                            <span>
                                <?php
                                echo htmlspecialchars(
                                    $sub_name
                                );
                                ?>
                            </span>
                            <small>
                                <?php
                                echo htmlspecialchars(
                                    $mc_name
                                );
                                ?>
                            </small>
                        </a>
                    <?php endwhile; ?>
                    <?php
                    $sub_stmt->close();
                    ?>
                <?php endif; ?>
            <?php endforeach; ?>
            
            <div
                id="noCategoryResults"
                class="no-category-results"
                style="display:none;"
            >
                <div>
                    🔎
                </div>
                <strong>
                    No category found
                </strong>
                <p>
                    Try another category name.
                </p>
            </div>
        </div>
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
                    <?php if (
                        $subcategory_id > 0 &&
                        $current_subcategory_name !== ""
                    ): ?>
                        <?php
                        echo htmlspecialchars(
                            $current_subcategory_name
                        );
                        ?>
                    <?php elseif (
                        $main_category_id > 0 &&
                        $current_category_name !== ""
                    ): ?>
                        <?php
                        echo htmlspecialchars(
                            $current_category_name
                        );
                        ?>
                    <?php elseif (
                        $search !== ""
                    ): ?>
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
                    <?php if (
                        $subcategory_id > 0
                    ): ?>
                        Products in
                        <?php
                        echo htmlspecialchars(
                            $current_subcategory_name
                        );
                        ?>.
                    <?php elseif (
                        $main_category_id > 0
                    ): ?>
                        Products in
                        <?php
                        echo htmlspecialchars(
                            $current_category_name
                        );
                        ?>.
                    <?php elseif (
                        $search !== ""
                    ): ?>
                        Search results for your query.
                    <?php else: ?>
                        Find everyday products
                        from Gauley Ko Pasal.
                    <?php endif; ?>
                </p>
            </div>
            <?php if (
                $main_category_id > 0 ||
                $subcategory_id > 0 ||
                $search !== ""
            ): ?>
                <a href="products.php">
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
                        $product[
                            "product_id"
                        ];
                    $product_name =
                        $product[
                            "product_name"
                        ];
                    $description =
                        $product[
                            "description"
                        ] ?? "";
                    $price =
                        (float)
                        $product[
                            "price"
                        ];
                    $image =
                        trim(
                            $product[
                                "image"
                            ] ?? ""
                        );
                    $main_name =
                        $product[
                            "main_category_name"
                        ] ?? "";
                    $sub_name =
                        $product[
                            "subcategory_name"
                        ] ?? "";
                    
                    $image_path = "";
                    if (
                        $image !== ""
                    ) {
                        $image_filename =
                            basename(
                                $image
                            );
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
                    
                    <article
                        class="product-card"
                    >
                        
                        <div
                            class="product-image"
                        >
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
                                <div
                                    class="
                                        product-placeholder
                                    "
                                >
                                    🛍️
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div
                            class="product-info"
                        >
                            <h3>
                                <?php
                                echo htmlspecialchars(
                                    $product_name
                                );
                                ?>
                            </h3>
                            <?php if (
                                $main_name !== ""
                            ): ?>
                                <span
                                    class="
                                        product-category
                                    "
                                >
                                    <?php
                                    echo htmlspecialchars(
                                        $main_name
                                    );
                                    ?>
                                </span>
                            <?php endif; ?>
                            <?php if (
                                $sub_name !== ""
                            ): ?>
                                <span
                                    class="
                                        product-subcategory
                                    "
                                >
                                    <?php
                                    echo htmlspecialchars(
                                        $sub_name
                                    );
                                    ?>
                                </span>
                            <?php endif; ?>
                            <?php if (
                                $description !== ""
                            ): ?>
                                <p
                                    class="
                                        product-description
                                    "
                                >
                                    <?php
                                    echo htmlspecialchars(
                                        $description
                                    );
                                    ?>
                                </p>
                            <?php endif; ?>
                            
                            <div
                                class="product-bottom"
                            >
                                <strong
                                    class="price"
                                >
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
                
                <div
                    class="empty-products"
                >
                    <div
                        class="
                            product-placeholder
                        "
                    >
                        🛒
                    </div>
                    <h3>
                        No products found
                    </h3>
                    <p>
                        <?php if (
                            $subcategory_id > 0
                        ): ?>
                            There are currently
                            no products in this
                            subcategory.
                        <?php elseif (
                            $main_category_id > 0
                        ): ?>
                            There are currently
                            no products in this
                            category.
                        <?php elseif (
                            $search !== ""
                        ): ?>
                            Try another search.
                        <?php else: ?>
                            There are currently
                            no products available.
                        <?php endif; ?>
                    </p>
                    <br>
                    <a
                        href="products.php"
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
            Simple local shopping for
            everyday needs.
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
            <a href="my_orders.php">
                My Orders
            </a>
            <a href="profile.php">
                My Profile
            </a>
        </div>
    </div>
    <div class="footer-bottom">
        © 2026 Gauley Ko Pasal.
        All rights reserved.
    </div>
</footer>
<script>
document.addEventListener(
    "DOMContentLoaded",
    function () {
        
        const button =
            document.getElementById(
                "otherCategoryButton"
            );
        const panel =
            document.getElementById(
                "otherCategoryPanel"
            );
        const closeButton =
            document.getElementById(
                "closeOtherCategory"
            );
        const search =
            document.getElementById(
                "categorySearch"
            );
        const resultsContainer =
            document.getElementById(
                "categoryResults"
            );
        const noResults =
            document.getElementById(
                "noCategoryResults"
            );
        
        function getCategoryItems() {
            return Array.from(
                resultsContainer.querySelectorAll(
                    ".category-result"
                )
            );
        }
        
        button.addEventListener(
            "click",
            function () {
                panel.classList.toggle(
                    "show"
                );
                button.classList.toggle(
                    "active"
                );
                if (
                    panel.classList.contains(
                        "show"
                    )
                ) {
                    setTimeout(
                        function () {
                            search.focus();
                        },
                        100
                    );
                }
            }
        );
        
        function closePanel() {
            panel.classList.remove(
                "show"
            );
            button.classList.remove(
                "active"
            );
            search.value = "";
            getCategoryItems()
                .forEach(
                    function (item) {
                        item.style.display =
                            "flex";
                        item.dataset.score =
                            "0";
                    }
                );
            noResults.style.display =
                "none";
        }
        closeButton.addEventListener(
            "click",
            closePanel
        );
        
        panel.addEventListener(
            "click",
            function (event) {
                if (
                    event.target === panel
                ) {
                    closePanel();
                }
            }
        );
        
        document.addEventListener(
            "keydown",
            function (event) {
                if (
                    event.key === "Escape" &&
                    panel.classList.contains(
                        "show"
                    )
                ) {
                    closePanel();
                }
            }
        );
        
        search.addEventListener(
            "input",
            function () {
                const query =
                    search.value
                        .trim()
                        .toLowerCase();
                const items =
                    getCategoryItems();
                let visibleCount = 0;
                
                if (
                    query === ""
                ) {
                    items.forEach(
                        function (item) {
                            item.style.display =
                                "flex";
                            item.dataset.score =
                                "0";
                        }
                    );
                    noResults.style.display =
                        "none";
                    return;
                }
                
                items.forEach(
                    function (item) {
                        const name =
                            (
                                item.dataset
                                    .categoryName
                                || ""
                            ).toLowerCase();
                        let score = 0;
                        
                        if (
                            name === query
                        ) {
                            score = 1000;
                        }
                        
                        else if (
                            name.startsWith(
                                query
                            )
                        ) {
                            score = 700;
                        }
                        
                        else if (
                            name
                                .split(" ")
                                .some(
                                    function (
                                        word
                                    ) {
                                        return word
                                            .startsWith(
                                                query
                                            );
                                    }
                                )
                        ) {
                            score = 500;
                        }
                        
                        else if (
                            name.includes(
                                query
                            )
                        ) {
                            score = 200;
                        }
                        
                        if (
                            score > 0
                        ) {
                            item.style.display =
                                "flex";
                            visibleCount++;
                        }
                        else {
                            item.style.display =
                                "none";
                        }
                        item.dataset.score =
                            String(score);
                    }
                );
                
                items.sort(
                    function (
                        a,
                        b
                    ) {
                        const scoreA =
                            parseInt(
                                a.dataset.score
                            ) || 0;
                        const scoreB =
                            parseInt(
                                b.dataset.score
                            ) || 0;
                        if (
                            scoreA !==
                            scoreB
                        ) {
                            return (
                                scoreB -
                                scoreA
                            );
                        }
                        const nameA =
                            (
                                a.dataset
                                    .categoryName
                                || ""
                            ).toLowerCase();
                        const nameB =
                            (
                                b.dataset
                                    .categoryName
                                || ""
                            ).toLowerCase();
                        return nameA.localeCompare(
                            nameB
                        );
                    }
                );
                
                items.forEach(
                    function (item) {
                        resultsContainer.appendChild(
                            item
                        );
                    }
                );
                
                if (
                    visibleCount === 0
                ) {
                    noResults.style.display =
                        "block";
                }
                else {
                    noResults.style.display =
                        "none";
                }
            }
        );
    }
);
</script>
</body>
</html>

