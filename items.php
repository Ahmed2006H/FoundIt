
<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit;
}

require_once __DIR__ . "/../config/database.php";

$filter = $_GET["type"] ?? "all";

if (!in_array($filter, ["all", "lost", "found"], true)) {
    $filter = "all";
}

if ($filter === "all") {
    $stmt = $conn->prepare(
        "SELECT items.*, users.full_name
         FROM items
         JOIN users ON items.user_id = users.id
         WHERE items.status = 'open'
         ORDER BY items.created_at DESC"
    );
} else {
    $stmt = $conn->prepare(
        "SELECT items.*, users.full_name
         FROM items
         JOIN users ON items.user_id = users.id
         WHERE items.status = 'open' AND items.type = ?
         ORDER BY items.created_at DESC"
    );
    $stmt->bind_param("s", $filter);
}

$stmt->execute();
$result = $stmt->get_result();

$success = isset($_GET["added"]);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Browse Items - FoundIt Campus</title>

    <style>
        * { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f3f6fb;
            color: #243047;
        }

        header {
            background: #2457a7;
            color: white;
            padding: 20px 7%;
        }

        header a {
            color: white;
            text-decoration: none;
            margin-right: 18px;
        }

        .container {
            max-width: 1100px;
            margin: 35px auto;
            padding: 0 20px;
        }

        .filters {
            margin: 20px 0;
        }

        .filters a {
            display: inline-block;
            padding: 10px 18px;
            margin: 5px;
            background: white;
            color: #2457a7;
            text-decoration: none;
            border-radius: 7px;
            border: 1px solid #ccd4e0;
        }

        .filters a.active {
            background: #2457a7;
            color: white;
        }

        .success {
            background: #e3f8e9;
            color: #176b32;
            padding: 12px;
            border-radius: 7px;
        }

        .items {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }

        .item {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,.06);
        }

        .item img {
            width: 100%;
            height: 190px;
            object-fit: cover;
        }

        .item-content {
            padding: 18px;
        }

        .tag {
            display: inline-block;
            padding: 6px 10px;
            border-radius: 5px;
            font-size: 13px;
            background: #e8efff;
            color: #2457a7;
        }

        .empty {
            background: white;
            padding: 30px;
            border-radius: 10px;
            text-align: center;
        }

        .item p {
            line-height: 1.5;
            overflow-wrap: anywhere;
        }
    </style>
</head>

<body>

<header>
    <h2>FoundIt Campus</h2>
    <a href="../dashboard.php">Dashboard</a>
    <a href="add_item.php?type=lost">Report Lost</a>
    <a href="add_item.php?type=found">Report Found</a>
</header>

<div class="container">

    <h1>Browse Campus Items</h1>
    <p>Find lost items or browse items reported as found.</p>

    <?php if ($success): ?>
        <p class="success">Your report was added successfully!</p>
    <?php endif; ?>

    <div class="filters">
        <a href="items.php"
           class="<?php echo $filter === 'all' ? 'active' : ''; ?>">
            All Items
        </a>

        <a href="items.php?type=lost"
           class="<?php echo $filter === 'lost' ? 'active' : ''; ?>">
            Lost Items
        </a>

        <a href="items.php?type=found"
           class="<?php echo $filter === 'found' ? 'active' : ''; ?>">
            Found Items
        </a>
    </div>

    <div class="items">

        <?php if ($result->num_rows > 0): ?>

            <?php while ($item = $result->fetch_assoc()): ?>

                <div class="item">

                    <?php if (!empty($item["image_path"])): ?>
                        <img
                            src="../<?php echo htmlspecialchars($item["image_path"]); ?>"
                            alt="Item image">
                    <?php endif; ?>

                    <div class="item-content">

                        <span class="tag">
                            <?php echo htmlspecialchars(ucfirst($item["type"])); ?>
                        </span>

                        <h3>
                            <?php echo htmlspecialchars($item["item_name"]); ?>
                        </h3>

                        <p>
                            <?php echo nl2br(htmlspecialchars($item["description"])); ?>
                        </p>

                        <p>
                            <strong>Category:</strong>
                            <?php echo htmlspecialchars($item["category"]); ?>
                        </p>

                        <p>
                            <strong>Location:</strong>
                            <?php echo htmlspecialchars($item["location"]); ?>
                        </p>

                        <p>
                            <strong>Date:</strong>
                            <?php echo htmlspecialchars($item["item_date"]); ?>
                        </p>

                        <p>
                            <strong>Reported by:</strong>
                            <?php echo htmlspecialchars($item["full_name"]); ?>
                        </p>
                        <?php if (
    $item["type"] === "found" &&
    (int) $item["user_id"] !== (int) $_SESSION["user_id"]
): ?>
    <p>
        <a href="claim.php?id=<?php echo (int) $item["id"]; ?>">
            Claim This Item
        </a>
    </p>
<?php endif; ?>

                    </div>
                </div>

            <?php endwhile; ?>

        <?php else: ?>

            <div class="empty">
                <h3>No items found</h3>
                <p>There are no reports in this category yet.</p>
            </div>

        <?php endif; ?>

    </div>

</div>

</body>
</html>

<?php
$stmt->close();
?>
