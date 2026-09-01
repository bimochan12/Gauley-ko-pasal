
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
$sql = "
    SELECT
        activity_logs.activity_id,
        activity_logs.user_id,
        activity_logs.action,
        activity_logs.details,
        activity_logs.created_at,
        users.name AS admin_name
    FROM activity_logs
    LEFT JOIN users
        ON activity_logs.user_id = users.user_id
    ORDER BY activity_logs.created_at DESC
";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html>
<head>
    <title>
        Activity Logs - Gauley Ko Pasal
    </title>
    <link
        rel="stylesheet"
        href="../style.css"
    >
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
        <a href="sellers.php">
            Sellers
        </a>
        <a href="users.php">
            Users
        </a>
        <a href="activity_logs.php">
            Activity Logs
        </a>
        <a href="logout.php">
            Logout
        </a>
    </nav>
</header>
<div class="container">
    <div class="hero">
        <h2>
            Activity Logs 📋
        </h2>
        <p>
            View important actions performed by administrators.
        </p>
    </div>
    <div class="card">
        <h2>
            Recent Activities
        </h2>
        <?php if (
            $result &&
            $result->num_rows > 0
        ): ?>
            <table>
                <thead>
                    <tr>
                        <th>
                            ID
                        </th>
                        <th>
                            Admin
                        </th>
                        <th>
                            Action
                        </th>
                        <th>
                            Details
                        </th>
                        <th>
                            Date
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <?php while (
                        $log =
                        $result->fetch_assoc()
                    ): ?>
                        <tr>
                            <td>
                                <?php
                                echo (int)
                                    $log["activity_id"];
                                ?>
                            </td>
                            <td>
                                <?php
                                if (
                                    !empty(
                                        $log["admin_name"]
                                    )
                                ) {
                                    echo htmlspecialchars(
                                        $log["admin_name"]
                                    );
                                } else {
                                    echo
                                        "Deleted User";
                                }
                                ?>
                            </td>
                            <td>
                                <?php
                                echo htmlspecialchars(
                                    $log["action"]
                                );
                                ?>
                            </td>
                            <td>
                                <?php
                                echo htmlspecialchars(
                                    $log["details"]
                                    ?? "-"
                                );
                                ?>
                            </td>
                            <td>
                                <?php
                                echo htmlspecialchars(
                                    $log["created_at"]
                                );
                                ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>
                No activity has been recorded yet.
            </p>
        <?php endif; ?>
    </div>
    <br>
    <a href="dashboard.php">
        ← Back to Dashboard
    </a>
</div>
<footer>
    <p>
        Gauley Ko Pasal —
        Admin Panel 🇳🇵
    </p>
</footer>
</body>
</html>
```

