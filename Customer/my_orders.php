<?php

session_start();

require_once "../config/database.php";


// Check if user is logged in

if (!isset($_SESSION["user_id"])) {

    echo "Please login first.";
    exit();

}

$user_id = $_SESSION["user_id"];


// Get orders belonging to this user

$sql = "SELECT * FROM orders
        WHERE user_id = $user_id
        ORDER BY order_date DESC";

$result = $conn->query($sql);

?>

<!DOCTYPE html>
<html>

<head>

    <title>My Orders - Gauley Ko Pasal</title>

    <style>

        body {
            font-family: Arial, sans-serif;
            margin: 0;
            background-color: #f5f5f5;
        }

        nav {
            background-color: #222;
            padding: 20px;
        }

        nav a {
            color: white;
            text-decoration: none;
            margin-right: 20px;
        }

        .container {
            width: 80%;
            max-width: 700px;
            margin: 40px auto;
        }

        h1 {
            text-align: center;
        }

        .order {
            background-color: white;
            padding: 20px;
            margin-bottom: 15px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .order h3 {
            margin-top: 0;
        }

        .status {
            font-weight: bold;
        }

        .buttons {
            text-align: center;
            margin-top: 25px;
        }

        .button {
            display: inline-block;
            padding: 12px 18px;
            margin: 5px;
            background-color: #222;
            color: white;
            text-decoration: none;
            border-radius: 5px;
        }

        .button:hover {
            background-color: #444;
        }

        .empty {
            background-color: white;
            padding: 30px;
            text-align: center;
            border-radius: 8px;
        }

    </style>

</head>

<body>


<nav>

    <a href="../index.php">Gauley Ko Pasal</a>

    <a href="products.php">Products</a>

    <a href="cart.php">Cart</a>

    <a href="my_orders.php">My Orders</a>

</nav>


<div class="container">

    <h1>My Orders</h1>


<?php

if ($result->num_rows == 0) {

?>

    <div class="empty">

        <h2>No Orders Yet</h2>

        <p>You have not placed any orders yet.</p>

        <a class="button" href="products.php">
            Start Shopping
        </a>

    </div>

<?php

} else {

    while ($order = $result->fetch_assoc()) {

?>

    <div class="order">

        <h3>
            Order #<?php echo $order["order_id"]; ?>
        </h3>

        <p>
            Total:
            <strong>
                Rs. <?php echo number_format($order["total_amount"], 2); ?>
            </strong>
        </p>

        <p class="status">
            Status:
            <?php echo $order["status"]; ?>
        </p>

        <p>
            Date:
            <?php echo $order["order_date"]; ?>
        </p>

    </div>

<?php

    }

}

?>


    <div class="buttons">

        <a class="button" href="products.php">
            Continue Shopping
        </a>

        <a class="button" href="index.php">
            Home
        </a>

    </div>


</div>


</body>

</html>