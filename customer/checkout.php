<?php
session_start();
require_once "../config/database.php";
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}
$user_id = (int) $_SESSION["user_id"];
if (!isset($_SESSION["cart"]) || empty($_SESSION["cart"])) {
    header("Location: cart.php");
    exit;
}
$user_sql = "SELECT
                name,
                email,
                phone,
                address
             FROM users
             WHERE user_id = ?";
$user_stmt = $conn->prepare($user_sql);
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_result = $user_stmt->get_result();
$user = $user_result->fetch_assoc();
if (!$user) {
    session_destroy();
    header("Location: login.php");
    exit;
}
$name = $user["name"] ?? "";
$email = $user["email"] ?? "";
$phone = $user["phone"] ?? "";
$address = $user["address"] ?? "";
$message = "";
$saved_latitude =
    $_SESSION["checkout_latitude"] ?? 27.7172;
$saved_longitude =
    $_SESSION["checkout_longitude"] ?? 85.3240;
function getProductImage($image)
{
    $image = trim($image ?? "");
    if ($image === "") {
        return "";
    }
    
    if (
        filter_var(
            $image,
            FILTER_VALIDATE_URL
        )
    ) {
        return $image;
    }
    
    $image = ltrim($image, "./");
    
    $possible_paths = [
        "../" . $image,
        "../uploads/" . basename($image),
        "../images/" . basename($image),
        "../product_images/" . basename($image),
        $image
    ];
    foreach ($possible_paths as $path) {
        if (
            file_exists($path) &&
            is_file($path)
        ) {
            return $path;
        }
    }
    
    if (
        strpos($image, "uploads/") === 0
    ) {
        return "../" . $image;
    }
    if (
        strpos($image, "images/") === 0
    ) {
        return "../" . $image;
    }
    if (
        strpos($image, "product_images/") === 0
    ) {
        return "../" . $image;
    }
    return "";
}
$cart_items = [];
$total_amount = 0;
$product_ids = array_keys($_SESSION["cart"]);
if (!empty($product_ids)) {
    $product_ids = array_map(
        "intval",
        $product_ids
    );
    $placeholders = implode(
        ",",
        array_fill(
            0,
            count($product_ids),
            "?"
        )
    );
    $sql = "SELECT
                product_id,
                product_name,
                description,
                price,
                quantity,
                image
            FROM products
            WHERE product_id IN ($placeholders)";
    $stmt = $conn->prepare($sql);
    $types = str_repeat(
        "i",
        count($product_ids)
    );
    $stmt->bind_param(
        $types,
        ...$product_ids
    );
    $stmt->execute();
    $result = $stmt->get_result();
    while ($product = $result->fetch_assoc()) {
        $product_id =
            (int) $product["product_id"];
        $cart_quantity =
            (int) (
                $_SESSION["cart"][$product_id]
                ?? 0
            );
        $stock =
            (int) $product["quantity"];
        
        if ($cart_quantity > $stock) {
            $cart_quantity = $stock;
            $_SESSION["cart"][$product_id] =
                $cart_quantity;
        }
        
        if ($cart_quantity <= 0) {
            unset(
                $_SESSION["cart"][$product_id]
            );
            continue;
        }
        $price =
            (float) $product["price"];
        $subtotal =
            $price * $cart_quantity;
        $product["cart_quantity"] =
            $cart_quantity;
        $product["subtotal"] =
            $subtotal;
        
        $product["image_path"] =
            getProductImage(
                $product["image"] ?? ""
            );
        $cart_items[] =
            $product;
        $total_amount +=
            $subtotal;
    }
}
if (empty($cart_items)) {
    $_SESSION["cart"] = [];
    header("Location: cart.php");
    exit;
}
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name =
        trim($_POST["name"] ?? "");
    $email =
        trim($_POST["email"] ?? "");
    $phone =
        trim($_POST["phone"] ?? "");
    $address =
        trim($_POST["address"] ?? "");
    $latitude =
        trim(
            $_POST["delivery_latitude"]
            ?? ""
        );
    $longitude =
        trim(
            $_POST["delivery_longitude"]
            ?? ""
        );
    
    if (
        $latitude !== "" &&
        $longitude !== ""
    ) {
        $_SESSION["checkout_latitude"] =
            $latitude;
        $_SESSION["checkout_longitude"] =
            $longitude;
    }
    
    if ($name === "") {
        $message =
            "Please enter your full name.";
    } elseif ($email === "") {
        $message =
            "Please enter your email.";
    } elseif (
        !filter_var(
            $email,
            FILTER_VALIDATE_EMAIL
        )
    ) {
        $message =
            "Please enter a valid email address.";
    } elseif ($phone === "") {
        $message =
            "Please enter your phone number.";
    } elseif ($address === "") {
        $message =
            "Please enter your delivery address.";
    } elseif (
        $latitude === "" ||
        $longitude === ""
    ) {
        $message =
            "Please select your delivery location on the map.";
    } elseif (
        !is_numeric($latitude) ||
        !is_numeric($longitude)
    ) {
        $message =
            "Invalid delivery location.";
    } else {
        $latitude =
            (float) $latitude;
        $longitude =
            (float) $longitude;
        
        $conn->begin_transaction();
        try {
            
            $update_user_sql =
                "UPDATE users
                 SET name = ?,
                     email = ?,
                     phone = ?,
                     address = ?
                 WHERE user_id = ?";
            $update_user_stmt =
                $conn->prepare(
                    $update_user_sql
                );
            $update_user_stmt->bind_param(
                "ssssi",
                $name,
                $email,
                $phone,
                $address,
                $user_id
            );
            $update_user_stmt->execute();
            
            $order_sql =
                "INSERT INTO orders
                (
                    user_id,
                    total_amount,
                    status,
                    delivery_latitude,
                    delivery_longitude
                )
                VALUES
                (
                    ?,
                    ?,
                    'Pending',
                    ?,
                    ?
                )";
            $order_stmt =
                $conn->prepare(
                    $order_sql
                );
            $order_stmt->bind_param(
                "iddd",
                $user_id,
                $total_amount,
                $latitude,
                $longitude
            );
            $order_stmt->execute();
            $order_id =
                $conn->insert_id;
            
            $item_sql =
                "INSERT INTO order_items
                (
                    order_id,
                    product_id,
                    quantity,
                    price
                )
                VALUES
                (
                    ?,
                    ?,
                    ?,
                    ?
                )";
            $item_stmt =
                $conn->prepare(
                    $item_sql
                );
            
            $stock_sql =
                "UPDATE products
                 SET quantity = quantity - ?
                 WHERE product_id = ?
                 AND quantity >= ?";
            $stock_stmt =
                $conn->prepare(
                    $stock_sql
                );
            foreach ($cart_items as $item) {
                $product_id =
                    (int) $item["product_id"];
                $quantity =
                    (int) $item["cart_quantity"];
                $price =
                    (float) $item["price"];
                
                $item_stmt->bind_param(
                    "iiid",
                    $order_id,
                    $product_id,
                    $quantity,
                    $price
                );
                $item_stmt->execute();
                
                $stock_stmt->bind_param(
                    "iii",
                    $quantity,
                    $product_id,
                    $quantity
                );
                $stock_stmt->execute();
                if (
                    $stock_stmt->affected_rows !== 1
                ) {
                    throw new Exception(
                        "A product does not have enough stock."
                    );
                }
            }
            
            $conn->commit();
            $_SESSION["cart"] = [];
            unset(
                $_SESSION["checkout_latitude"]
            );
            unset(
                $_SESSION["checkout_longitude"]
            );
            $_SESSION["last_order_id"] =
                $order_id;
            header(
                "Location: order_success.php"
            );
            exit;
        } catch (Throwable $e) {
            $conn->rollback();
            $message =
                "Unable to place your order: "
                . $e->getMessage();
        }
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
    Checkout - Gauley Ko Pasal
</title>
<link
    rel="stylesheet"
    href="../css/customer.css"
>
<link
    rel="stylesheet"
    href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
/>
<style>
.checkout-page {
    width: 92%;
    max-width: 1200px;
    margin: 45px auto 80px;
}
.checkout-heading {
    margin-bottom: 30px;
}
.checkout-heading .eyebrow {
    color: #1976d2;
    font-size: 11px;
    font-weight: 900;
    letter-spacing: 2px;
}
.checkout-heading h1 {
    font-size: 40px;
    margin: 8px 0;
}
.checkout-heading p {
    color: #718096;
}
.checkout-layout {
    display: grid;
    grid-template-columns:
        1.5fr .8fr;
    gap: 25px;
    align-items: start;
}
.checkout-card,
.order-summary {
    background: white;
    border: 1px solid #e4e9ef;
    border-radius: 18px;
    padding: 28px;
}
.checkout-card h2,
.order-summary h2 {
    margin-bottom: 22px;
}
.form-row {
    display: grid;
    grid-template-columns:
        1fr 1fr;
    gap: 15px;
}
.form-group {
    margin-bottom: 18px;
}
.form-group label {
    display: block;
    font-size: 12px;
    font-weight: 900;
    margin-bottom: 7px;
}
.form-group input,
.form-group textarea {
    width: 100%;
    padding: 13px 14px;
    border: 1px solid #dce3ea;
    border-radius: 9px;
    outline: none;
    font-family: inherit;
    font-size: 14px;
    box-sizing: border-box;
}
.form-group textarea {
    min-height: 90px;
    resize: vertical;
}
.form-group input:focus,
.form-group textarea:focus {
    border-color: #1976d2;
}
.delivery-note {
    background: #eef6fd;
    padding: 13px;
    border-radius: 9px;
    color: #315b7c;
    font-size: 12px;
    line-height: 1.6;
    margin-bottom: 22px;
}
.error-message {
    background: #fff1f1;
    border: 1px solid #ffd0d0;
    color: #b42318;
    padding: 14px;
    border-radius: 9px;
    margin-bottom: 20px;
    font-size: 13px;
}
.section-title {
    margin: 30px 0 7px;
    font-size: 18px;
}
.section-description {
    color: #718096;
    font-size: 12px;
    margin-bottom: 12px;
}


    width: 100%;
    height: 420px;
    border-radius: 14px;
    overflow: hidden;
}
.location-button {
    margin-top: 12px;
    border: none;
    background: #102a43;
    color: white;
    padding: 11px 16px;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 800;
}
.location-button:hover {
    background: #1976d2;
}
.coordinates {
    display: grid;
    grid-template-columns:
        1fr 1fr;
    gap: 10px;
    margin-top: 12px;
}
.coordinate {
    background: #f5f7fa;
    padding: 11px;
    border-radius: 8px;
    font-size: 11px;
}
.coordinate strong {
    display: block;
    margin-bottom: 4px;
}
.edit-order {
    margin-top: 28px;
    padding-top: 25px;
    border-top: 1px solid #edf0f3;
}
.edit-order h3 {
    font-size: 17px;
    margin-bottom: 6px;
}
.edit-order p {
    color: #718096;
    font-size: 12px;
    line-height: 1.6;
    margin-bottom: 15px;
}
.edit-buttons {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}
.edit-button {
    display: inline-block;
    padding: 11px 15px;
    border-radius: 8px;
    font-size: 12px;
    font-weight: 800;
    border: 1px solid #dce3ea;
    background: white;
    color: #172033;
}
.edit-button:hover {
    border-color: #1976d2;
    color: #1976d2;
}
.add-products-button {
    display: inline-block;
    padding: 11px 15px;
    border-radius: 8px;
    font-size: 12px;
    font-weight: 800;
    background: #102a43;
    color: white;
}
.add-products-button:hover {
    background: #1976d2;
}
.summary-item {
    display: grid;
    grid-template-columns:
        64px 1fr auto;
    gap: 12px;
    align-items: center;
    padding: 14px 0;
    border-bottom: 1px solid #edf0f3;
}
.summary-item:last-child {
    border-bottom: none;
}
.summary-image {
    width: 64px;
    height: 64px;
    background: #f5f7fa;
    border: 1px solid #e4e9ef;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    flex-shrink: 0;
}
.summary-image img {
    width: 100%;
    height: 100%;
    object-fit: contain;
    padding: 6px;
    box-sizing: border-box;
}
.summary-image-placeholder {
    font-size: 28px;
    line-height: 1;
}
.summary-product-info {
    min-width: 0;
}
.summary-name {
    font-weight: 800;
    color: #102a43;
    font-size: 13px;
    line-height: 1.4;
    margin-bottom: 5px;
    word-break: break-word;
}
.summary-quantity {
    color: #718096;
    font-size: 11px;
    line-height: 1.4;
}
.summary-unit-price {
    color: #718096;
    font-size: 10px;
    margin-top: 3px;
}
.summary-item-price {
    color: #102a43;
    font-size: 13px;
    font-weight: 900;
    white-space: nowrap;
    text-align: right;
}
.summary-total {
    display: flex;
    justify-content: space-between;
    border-top: 2px solid #edf0f3;
    margin-top: 18px;
    padding-top: 20px;
    font-size: 20px;
    font-weight: 900;
}
.place-order {
    width: 100%;
    border: none;
    background: #1976d2;
    color: white;
    padding: 15px;
    border-radius: 10px;
    font-weight: 900;
    font-size: 14px;
    cursor: pointer;
    margin-top: 20px;
}
.place-order:hover {
    background: #102a43;
}
.summary-edit {
    display: block;
    text-align: center;
    margin-top: 15px;
    padding: 11px;
    border: 1px solid #dce3ea;
    border-radius: 8px;
    font-size: 12px;
    font-weight: 800;
}
.summary-edit:hover {
    border-color: #1976d2;
    color: #1976d2;
}
@media (max-width: 850px) {
    .checkout-layout {
        grid-template-columns: 1fr;
    }
    .form-row {
        grid-template-columns: 1fr;
    }
    .checkout-heading h1 {
        font-size: 30px;
    }


        height: 350px;
    }
}
@media (max-width: 500px) {
    .checkout-card,
    .order-summary {
        padding: 18px;
    }
    .coordinates {
        grid-template-columns: 1fr;
    }
    .summary-item {
        grid-template-columns:
            58px 1fr auto;
        gap: 9px;
    }
    .summary-image {
        width: 58px;
        height: 58px;
    }
    .summary-name {
        font-size: 12px;
    }
    .summary-item-price {
        font-size: 12px;
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
        </nav>
    </div>
</header>
<main class="checkout-page">
    <div class="checkout-heading">
        <p class="eyebrow">
            CHECKOUT
        </p>
        <h1>
            Complete Your Order
        </h1>
        <p>
            Review your order and confirm your delivery details.
        </p>
    </div>
    
    <?php if ($message !== ""): ?>
        <div class="error-message">
            <?php
            echo htmlspecialchars($message);
            ?>
        </div>
    <?php endif; ?>
    <div class="checkout-layout">
        
        <section class="checkout-card">
            <h2>
                Customer & Delivery Details
            </h2>
            <div class="delivery-note">
                Your account information is already filled in.
                You can edit your name, phone number and delivery
                address before placing your order.
            </div>
            <form
                method="POST"
                id="checkout-form"
            >
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="name">
                            Full Name
                        </label>
                        <input
                            type="text"
                            id="name"
                            name="name"
                            value="<?php
                                echo htmlspecialchars($name);
                            ?>"
                            required
                        >
                    </div>
                    <div class="form-group">
                        <label for="email">
                            Email Address
                        </label>
                        <input
                            type="email"
                            id="email"
                            name="email"
                            value="<?php
                                echo htmlspecialchars($email);
                            ?>"
                            required
                        >
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="phone">
                        Phone Number
                    </label>
                    <input
                        type="text"
                        id="phone"
                        name="phone"
                        value="<?php
                            echo htmlspecialchars($phone);
                        ?>"
                        required
                    >
                </div>
                
                <div class="form-group">
                    <label for="address">
                        Delivery Address
                    </label>
                    <textarea
                        id="address"
                        name="address"
                        placeholder="Enter your complete delivery address"
                        required
                    ><?php
                        echo htmlspecialchars($address);
                    ?></textarea>
                </div>
                
                <h3 class="section-title">
                    📍 Choose Delivery Location
                </h3>
                <p class="section-description">
                    Click anywhere on the map or drag the marker
                    to your exact delivery location.
                </p>
                <div id="map"></div>
                <button
                    type="button"
                    class="location-button"
                    id="current-location"
                >
                    📍 Use My Current Location
                </button>
                
                <div class="coordinates">
                    <div class="coordinate">
                        <strong>
                            Latitude
                        </strong>
                        <span id="latitude-display">
                            Not selected
                        </span>
                    </div>
                    <div class="coordinate">
                        <strong>
                            Longitude
                        </strong>
                        <span id="longitude-display">
                            Not selected
                        </span>
                    </div>
                </div>
                <input
                    type="hidden"
                    name="delivery_latitude"
                    id="delivery_latitude"
                    value="<?php
                        echo htmlspecialchars(
                            $saved_latitude
                        );
                    ?>"
                >
                <input
                    type="hidden"
                    name="delivery_longitude"
                    id="delivery_longitude"
                    value="<?php
                        echo htmlspecialchars(
                            $saved_longitude
                        );
                    ?>"
                >
                
                <div class="edit-order">
                    <h3>
                        🛒 Edit Your Order
                    </h3>
                    <p>
                        Want to change something before placing
                        your order? You can modify your cart or
                        add more products.
                    </p>
                    <div class="edit-buttons">
                        <a
                            href="cart.php"
                            class="edit-button"
                        >
                            ← Edit Cart
                        </a>
                        <a
                            href="products.php"
                            class="add-products-button"
                        >
                            ＋ Add More Products
                        </a>
                    </div>
                </div>
                
                <button
                    type="submit"
                    class="place-order"
                >
                    Place Order — Rs.
                    <?php
                    echo number_format(
                        $total_amount,
                        2
                    );
                    ?>
                </button>
            </form>
        </section>
        
        <aside class="order-summary">
            <h2>
                Your Order
            </h2>
            <?php foreach ($cart_items as $item): ?>
                <?php
                $item_image =
                    $item["image_path"] ?? "";
                $item_name =
                    $item["product_name"];
                $item_quantity =
                    (int) $item["cart_quantity"];
                $item_price =
                    (float) $item["price"];
                $item_subtotal =
                    (float) $item["subtotal"];
                ?>
                <div class="summary-item">
                    
                    <div class="summary-image">
                        <?php if ($item_image !== ""): ?>
                            <img
                                src="<?php
                                    echo htmlspecialchars(
                                        $item_image
                                    );
                                ?>"
                                alt="<?php
                                    echo htmlspecialchars(
                                        $item_name
                                    );
                                ?>"
                            >
                        <?php else: ?>
                            <div
                                class="summary-image-placeholder"
                            >
                                🛍️
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="summary-product-info">
                        <div class="summary-name">
                            <?php
                            echo htmlspecialchars(
                                $item_name
                            );
                            ?>
                        </div>
                        <div class="summary-quantity">
                            Quantity:
                            <?php
                            echo $item_quantity;
                            ?>
                        </div>
                        <div class="summary-unit-price">
                            Rs.
                            <?php
                            echo number_format(
                                $item_price,
                                2
                            );
                            ?>
                            each
                        </div>
                    </div>
                    
                    <div class="summary-item-price">
                        Rs.
                        <?php
                        echo number_format(
                            $item_subtotal,
                            2
                        );
                        ?>
                    </div>
                </div>
            <?php endforeach; ?>
            
            <div class="summary-total">
                <span>
                    Total
                </span>
                <span>
                    Rs.
                    <?php
                    echo number_format(
                        $total_amount,
                        2
                    );
                    ?>
                </span>
            </div>
            
            <a
                href="cart.php"
                class="summary-edit"
            >
                ✏️ Change Quantity / Remove Items
            </a>
            
            <a
                href="products.php"
                class="summary-edit"
            >
                ＋ Add More Products
            </a>
        </aside>
    </div>
</main>
<footer>
    <div class="footer-bottom">
        © <?php echo date("Y"); ?>
        Gauley Ko Pasal
    </div>
</footer>
<script
    src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
></script>
<script>
const defaultLatitude =
    <?php
    echo (float) $saved_latitude;
    ?>;
const defaultLongitude =
    <?php
    echo (float) $saved_longitude;
    ?>;
const map =
    L.map("map").setView(
        [
            defaultLatitude,
            defaultLongitude
        ],
        14
    );
L.tileLayer(
    "https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png",
    {
        maxZoom: 19,
        attribution:
            "&copy; OpenStreetMap contributors"
    }
).addTo(map);
let marker =
    L.marker(
        [
            defaultLatitude,
            defaultLongitude
        ],
        {
            draggable: true
        }
    ).addTo(map);
function updateLocation(
    latitude,
    longitude
) {
    latitude =
        Number(latitude).toFixed(8);
    longitude =
        Number(longitude).toFixed(8);
    document.getElementById(
        "delivery_latitude"
    ).value =
        latitude;
    document.getElementById(
        "delivery_longitude"
    ).value =
        longitude;
    document.getElementById(
        "latitude-display"
    ).textContent =
        latitude;
    document.getElementById(
        "longitude-display"
    ).textContent =
        longitude;
}
updateLocation(
    defaultLatitude,
    defaultLongitude
);
marker.on(
    "dragend",
    function () {
        const position =
            marker.getLatLng();
        updateLocation(
            position.lat,
            position.lng
        );
    }
);
map.on(
    "click",
    function (event) {
        marker.setLatLng(
            [
                event.latlng.lat,
                event.latlng.lng
            ]
        );
        updateLocation(
            event.latlng.lat,
            event.latlng.lng
        );
    }
);
document
    .getElementById(
        "current-location"
    )
    .addEventListener(
        "click",
        function () {
            if (!navigator.geolocation) {
                alert(
                    "Your browser does not support location services."
                );
                return;
            }
            navigator.geolocation.getCurrentPosition(
                function (position) {
                    const latitude =
                        position.coords.latitude;
                    const longitude =
                        position.coords.longitude;
                    marker.setLatLng(
                        [
                            latitude,
                            longitude
                        ]
                    );
                    map.setView(
                        [
                            latitude,
                            longitude
                        ],
                        17
                    );
                    updateLocation(
                        latitude,
                        longitude
                    );
                },
                function () {
                    alert(
                        "Location access was denied or unavailable. You can choose your location manually on the map."
                    );
                },
                {
                    enableHighAccuracy: true,
                    timeout: 10000,
                    maximumAge: 0
                }
            );
        }
    );
setTimeout(
    function () {
        map.invalidateSize();
    },
    300
);
</script>
</body>
</html>

