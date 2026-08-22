<?php

session_start();

require_once "../config/database.php";


/* =========================
   ADMIN AUTHENTICATION
========================= */

if (
    !isset($_SESSION["user_id"]) ||
    !isset($_SESSION["role"]) ||
    $_SESSION["role"] !== "admin"
) {
    header("Location: login.php");
    exit();
}


$message = "";


/* =========================
   ADD CATEGORY
========================= */

if (
    $_SERVER["REQUEST_METHOD"] === "POST"
) {

    $category_name =
        trim($_POST["category_name"]);


    if ($category_name === "") {

        $message =
            "Category name cannot be empty.";

    } else {


        /* Check duplicate category */

        $check = $conn->prepare(
            "SELECT category_id
             FROM categories
             WHERE category_name = ?"
        );

        $check->bind_param(
            "s",
            $category_name
        );

        $check->execute();

        $existing =
            $check->get_result();


        if ($existing->num_rows > 0) {

            $message =
                "Category already exists.";

        } else {


            /* Add category */

            $stmt = $conn->prepare(
                "INSERT INTO categories
                (
                    category_name
                )
                VALUES (?)"
            );

            $stmt->bind_param(
                "s",
                $category_name
            );


            if ($stmt->execute()) {

                $message =
                    "Category added successfully.";

            } else {

                $message =
                    "Failed to add category.";

            }

        }

    }

}


/* =========================
   DELETE CATEGORY
========================= */

if (
    isset($_GET["delete"])
) {

    $category_id =
        (int) $_GET["delete"];


    /* Check products using category */

    $check = $conn->prepare(
        "SELECT COUNT(*) AS total
         FROM products
         WHERE category_id = ?"
    );

    $check->bind_param(
        "i",
        $category_id
    );

    $check->execute();

    $check_result =
        $check->get_result();

    $product_count =
        $check_result
        ->fetch_assoc()["total"];


    if ($product_count > 0) {

        $message =
            "Cannot delete this category because products are using it.";

    } else {


        /* Delete category */

        $stmt = $conn->prepare(
            "DELETE FROM categories
             WHERE category_id = ?"
        );

        $stmt->bind_param(
            "i",
            $category_id
        );


        if ($stmt->execute()) {

            $message =
                "Category deleted successfully.";

        } else {

            $message =
                "Failed to delete category.";

        }

    }

}


/* =========================
   GET CATEGORIES
========================= */

$result = $conn->query(
    "SELECT
        category_id,
        category_name
     FROM categories
     ORDER BY category_name ASC"
);

?>

<!DOCTYPE html>
<html>

<head>

    <title>
        Manage Categories - Gauley Ko Pasal
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

        <a href="categories.php">
            Categories
        </a>

        <a href="logout.php">
            Logout
        </a>

    </nav>

</header>


<div class="container">


    <!-- PAGE HEADER -->

    <div class="hero">

        <h2>
            Manage Categories 🗂️
        </h2>

        <p>
            Add and manage product categories
            for sellers to use.
        </p>

    </div>


    <!-- MESSAGE -->

    <?php if ($message !== ""): ?>

        <div class="card">

            <p>

                <?php
                echo htmlspecialchars(
                    $message
                );
                ?>

            </p>

        </div>

    <?php endif; ?>


    <!-- ADD CATEGORY -->

    <div class="card">

        <h3>
            Add New Category
        </h3>


        <form method="POST">

            <label>
                Category Name
            </label>

            <input
                type="text"
                name="category_name"
                placeholder="Example: Drinks"
                maxlength="100"
                required
            >

            <br><br>


            <button type="submit">
                Add Category
            </button>

        </form>

    </div>


    <br>


    <!-- CATEGORY LIST -->

    <div class="card">

        <h3>
            Existing Categories
        </h3>


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
                            Category Name
                        </th>

                        <th>
                            Action
                        </th>

                    </tr>

                </thead>


                <tbody>


                    <?php while (
                        $category =
                        $result->fetch_assoc()
                    ): ?>


                        <tr>

                            <td>

                                <?php
                                echo htmlspecialchars(
                                    $category[
                                        "category_id"
                                    ]
                                );
                                ?>

                            </td>


                            <td>

                                <?php
                                echo htmlspecialchars(
                                    $category[
                                        "category_name"
                                    ]
                                );
                                ?>

                            </td>


                            <td>

                                <a
                                    href="categories.php?delete=<?php echo (int) $category["category_id"]; ?>"
                                    onclick="return confirm('Are you sure you want to delete this category?');"
                                >
                                    Delete
                                </a>

                            </td>

                        </tr>


                    <?php endwhile; ?>


                </tbody>

            </table>


        <?php else: ?>


            <p>
                No categories found.
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
        © <?php echo date("Y"); ?>
        Gauley Ko Pasal — Admin Panel 🇳🇵
    </p>

</footer>


</body>

</html>