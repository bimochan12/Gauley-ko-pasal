<?php

session_start();

require_once "../config/database.php";

if (
    !isset($_SESSION["user_id"]) ||
    !isset($_SESSION["role"]) ||
    $_SESSION["role"] !== "admin"
) {
    header("Location: login.php");
    exit();
}

$sql = "SELECT user_id, name, email, role
        FROM users
        ORDER BY user_id DESC";

$result = $conn->query($sql);

?>

<!DOCTYPE html>
<html>

<head>

    <title>Manage Users - Gauley Ko Pasal</title>

    <link rel="stylesheet" href="../style.css">

</head>

<body>

<header>

    <h1>Gauley Ko Pasal</h1>

    <nav>

        <a href="dashboard.php">Dashboard</a>

        <a href="products.php">Products</a>

        <a href="orders.php">Orders</a>

        <a href="users.php">Users</a>

        <a href="logout.php">Logout</a>

    </nav>

</header>


<div class="container">

    <div class="hero">

        <h2>Registered Customers & Sellers</h2>

        <p>
            View all registered users of Gauley Ko Pasal.
        </p>

    </div>


    <div class="card">

        <?php if ($result && $result->num_rows > 0): ?>

            <table>

                <thead>

                    <tr>

                        <th>ID</th>

                        <th>Name</th>

                        <th>Email</th>

                        <th>Role</th>

                    </tr>

                </thead>

                <tbody>

                    <?php while ($user = $result->fetch_assoc()): ?>

                        <tr>

                            <td>
                                <?php
                                echo htmlspecialchars(
                                    $user["user_id"]
                                );
                                ?>
                            </td>

                            <td>
                                <?php
                                echo htmlspecialchars(
                                    $user["name"]
                                );
                                ?>
                            </td>

                            <td>
                                <?php
                                echo htmlspecialchars(
                                    $user["email"]
                                );
                                ?>
                            </td>

                            <td>
                                <?php
                                echo htmlspecialchars(
                                    $user["role"]
                                );
                                ?>
                            </td>

                        </tr>

                    <?php endwhile; ?>

                </tbody>

            </table>

        <?php else: ?>

            <p>No registered users found.</p>

        <?php endif; ?>

    </div>


    <br>

    <a href="dashboard.php">
        ← Back to Dashboard
    </a>

</div>


<footer>

    <p>
        © <?php echo date("Y"); ?> Gauley Ko Pasal
    </p>

</footer>

</body>

</html>