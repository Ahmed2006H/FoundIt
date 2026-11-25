
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

// Count users
$users_result = $conn->query("SELECT COUNT(*) AS total FROM users");
$total_users = (int) $users_result->fetch_assoc()["total"];

// Count lost items
$lost_result = $conn->query(
    "SELECT COUNT(*) AS total FROM items WHERE type = 'lost'"
);
$total_lost = (int) $lost_result->fetch_assoc()["total"];

// Count found items
$found_result = $conn->query(
    "SELECT COUNT(*) AS total FROM items WHERE type = 'found'"
);
$total_found = (int) $found_result->fetch_assoc()["total"];

// Count pending claims
$claims_result = $conn->query(
    "SELECT COUNT(*) AS total FROM claims WHERE status = 'pending'"
);
$total_claims = (int) $claims_result->fetch_assoc()["total"];

// Latest reports
$latest_items = $conn->query(
    "SELECT items.id, items.item_name, items.type,
            items.location, items.status, items.created_at,
            users.full_name
     FROM items
     JOIN users ON items.user_id = users.id
     ORDER BY items.created_at DESC
     LIMIT 10"
);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - FoundIt Campus</title>

    <style>
        * { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f3f6fb;
            color: #243047;
        }
        header nav a.back-btn {
    background: #526783;
}

header nav a.back-btn:hover {
    background: #3b4e68;
}

       
header {
    background: #172b4d;
    color: white;
    padding: 20px 7%;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 20px;
}

header h2 {
    margin: 0;
    font-size: 23px;
}

header h2 span {
    color: #a9c8ff;
    font-size: 15px;
    margin-left: 8px;
}

header nav {
    display: flex;
    align-items: center;
    gap: 25px;
    flex-wrap: wrap;
}

header nav a {
    color: white;
    text-decoration: none;
    font-size: 15px;
    padding: 10px 14px;
    border-radius: 7px;
    transition: background 0.2s;
}

header nav a:hover {
    background: #2d4770;
}

header nav a.logout {
    background: #d9534f;
}

header nav a.logout:hover {
    background: #bd3935;
}

.container {
    max-width: 1150px;
    margin: 40px auto;
    padding: 0 25px;
}

.stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
    gap: 22px;
    margin: 30px 0;
}

.stat {
    background: white;
    padding: 26px;
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(0,0,0,.06);
    border: 1px solid #e8edf5;
}

.stat p {
    color: #68758a;
    font-size: 15px;
    margin: 0;
}

.stat h2 {
    color: #2457a7;
    font-size: 34px;
    margin: 14px 0 0;
}

.table-box {
    background: white;
    padding: 25px;
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(0,0,0,.06);
    margin-top: 30px;
}

table {
    width: 100%;
    border-collapse: collapse;
}

th, td {
    padding: 16px 14px;
    border-bottom: 1px solid #e8edf5;
    text-align: left;
}

th {
    background: #f3f6fb;
    color: #344563;
}

@media (max-width: 650px) {
    header {
        flex-direction: column;
        align-items: flex-start;
    }

    header nav {
        gap: 8px;
    }

    .container {
        padding: 0 15px;
    }

    .table-box {
        overflow-x: auto;
    }
}


        .container {
            max-width: 1100px;
            margin: 35px auto;
            padding: 0 20px;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(190px, 1fr));
            gap: 18px;
            margin: 25px 0;
        }

        .stat {
            background: white;
            padding: 24px;
            border-radius: 10px;
            box-shadow: 0 3px 12px rgba(0,0,0,.05);
        }

        .stat h2 {
            color: #2457a7;
            font-size: 30px;
            margin: 8px 0;
        }

        .table-box {
            background: white;
            padding: 22px;
            border-radius: 10px;
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 650px;
        }

        th, td {
            padding: 13px;
            border-bottom: 1px solid #e5e9f0;
            text-align: left;
        }

        th {
            background: #f3f6fb;
        }

        .type {
            font-weight: bold;
            color: #2457a7;
        }

        @media (max-width: 600px) {
            header {
                align-items: flex-start;
                gap: 10px;
                flex-direction: column;
            }
        }
    </style>
</head>

<body>



<header>
    <h2>FoundIt Campus <span>Admin Panel</span></h2>

   
<nav>
    <a href="javascript:history.back()" class="back-btn">← Back</a>
    <a href="/foundit/admin/index.php">Dashboard</a>
    <a href="/foundit/admin/users.php">Manage Users</a>
    <a href="/foundit/auth/logout.php" class="logout">Logout</a>
    <a href="items.php">Manage Items</a>
</nav>

</header>



<div class="container">

    <h1>Welcome, Admin!</h1>
    <p>Monitor the activity of FoundIt Campus.</p>

    <div class="stats">

        <div class="stat">
            <p>Total Users</p>
            <h2><?php echo $total_users; ?></h2>
        </div>

        <div class="stat">
            <p>Lost Items</p>
            <h2><?php echo $total_lost; ?></h2>
        </div>

        <div class="stat">
            <p>Found Items</p>
            <h2><?php echo $total_found; ?></h2>
        </div>

        <div class="stat">
            <p>Pending Claims</p>
            <h2><?php echo $total_claims; ?></h2>
        </div>

    </div>

    <div class="table-box">

        <h2>Latest Reports</h2>

        <?php if ($latest_items && $latest_items->num_rows > 0): ?>

            <table>
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Type</th>
                        <th>Reporter</th>
                        <th>Location</th>
                        <th>Status</th>
                        <th>Date Added</th>
                    </tr>
                </thead>

                <tbody>
                    <?php while ($item = $latest_items->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <?php echo htmlspecialchars($item["item_name"]); ?>
                            </td>
                            <td class="type">
                                <?php echo htmlspecialchars(ucfirst($item["type"])); ?>
                            </td>
                            <td>
                                <?php echo htmlspecialchars($item["full_name"]); ?>
                            </td>
                            <td>
                                <?php echo htmlspecialchars($item["location"]); ?>
                            </td>
                            <td>
                                <?php echo htmlspecialchars(ucfirst($item["status"])); ?>
                            </td>
                            <td>
                                <?php echo htmlspecialchars($item["created_at"]); ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>

        <?php else: ?>

            <p>No reports have been submitted yet.</p>

        <?php endif; ?>

    </div>

</div>

</body>
</html>
