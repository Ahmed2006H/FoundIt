
<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit;
}

if (($_SESSION["role"] ?? "") !== "admin") {
    http_response_code(403);
    exit("Access denied. Admins only.");
}

require_once __DIR__ . "/../config/database.php";

$stmt = $conn->prepare(
    "SELECT id, full_name, email, role, created_at
     FROM users
     ORDER BY created_at DESC"
);

$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - FoundIt Campus</title>

    <style>
        * { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f3f6fb;
            color: #243047;
        }

        header {
            background: #172b4d;
            color: white;
            padding: 20px 7%;
        }

        header a {
            color: white;
            text-decoration: none;
            margin-right: 20px;
        }

        .container {
            max-width: 1100px;
            margin: 35px auto;
            padding: 0 20px;
        }

        .table-box {
            background: white;
            padding: 22px;
            border-radius: 10px;
            overflow-x: auto;
            box-shadow: 0 3px 12px rgba(0,0,0,.05);
        }

        table {
            width: 100%;
            min-width: 650px;
            border-collapse: collapse;
        }

        th, td {
            padding: 14px;
            text-align: left;
            border-bottom: 1px solid #e5e9f0;
        }

        th {
            background: #f3f6fb;
        }

        .role {
            display: inline-block;
            padding: 6px 10px;
            border-radius: 5px;
            background: #e8efff;
            color: #2457a7;
            font-weight: bold;
        }

        .admin {
            background: #f0e8ff;
            color: #7138a8;
        }

        .empty {
            padding: 20px;
            background: white;
            border-radius: 10px;
        }
    </style>
</head>

<body>

<header>
    <h2>FoundIt Campus | Admin</h2>
    <a href="/foundit/admin/index.php">Dashboard</a>
    <a href="users.php">Manage Users</a>
    <a href="../auth/logout.php">Logout</a>
</header>

<div class="container">

    <h1>Manage Users</h1>
    <p>View all registered accounts in the system.</p>

    <div class="table-box">

        <?php if ($result->num_rows > 0): ?>

            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Full Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Registered At</th>
                    </tr>
                </thead>

                <tbody>
                    <?php while ($user = $result->fetch_assoc()): ?>

                        <tr>
                            <td>
                                <?php echo (int) $user["id"]; ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($user["full_name"]); ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($user["email"]); ?>
                            </td>

                            <td>
                                <span class="role <?php
                                    echo $user["role"] === "admin" ? "admin" : "";
                                ?>">
                                    <?php echo htmlspecialchars(ucfirst($user["role"])); ?>
                                </span>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($user["created_at"]); ?>
                            </td>
                        </tr>

                    <?php endwhile; ?>
                </tbody>
            </table>

        <?php else: ?>

            <div class="empty">
                No users found.
            </div>

        <?php endif; ?>

    </div>

</div>

</body>
</html>

<?php
$stmt->close();
?>
