
<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit;
}

require_once __DIR__ . "/../config/database.php";

$user_id = (int) $_SESSION["user_id"];

$stmt = $conn->prepare(
    "SELECT
        claims.id,
        claims.claim_message,
        claims.status,
        claims.created_at,
        items.item_name,
        items.location,
        items.type
     FROM claims
     JOIN items ON claims.item_id = items.id
     WHERE claims.user_id = ?
     ORDER BY claims.created_at DESC"
);

$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Claims - FoundIt Campus</title>

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
            max-width: 950px;
            margin: 35px auto;
            padding: 0 20px;
        }

        .claim {
            background: white;
            padding: 22px;
            margin-bottom: 18px;
            border-radius: 10px;
            box-shadow: 0 3px 12px rgba(0,0,0,.05);
        }

        .status {
            display: inline-block;
            padding: 7px 12px;
            border-radius: 6px;
            background: #e8efff;
            color: #2457a7;
            font-weight: bold;
        }

        .approved {
            background: #e3f8e9;
            color: #176b32;
        }

        .rejected {
            background: #ffe9e9;
            color: #a32121;
        }

        .empty, .success {
            background: white;
            padding: 25px;
            border-radius: 10px;
        }

        .success {
            background: #e3f8e9;
            color: #176b32;
        }
    </style>
</head>

<body>

<header>
    <h2>FoundIt Campus</h2>
    <a href="../dashboard.php">Dashboard</a>
    <a href="items.php">Browse Items</a>
</header>

<div class="container">

    <h1>My Claims</h1>
    <p>Track the status of your submitted claims.</p>

    <?php if (isset($_GET["sent"])): ?>
        <div class="success">
            Your claim was submitted successfully!
        </div>
    <?php endif; ?>

    <?php if ($result->num_rows > 0): ?>

        <?php while ($claim = $result->fetch_assoc()): ?>

            <?php
            $status_class = "";

            if ($claim["status"] === "approved") {
                $status_class = "approved";
            } elseif ($claim["status"] === "rejected") {
                $status_class = "rejected";
            }
            ?>

            <div class="claim">

                <h3>
                    <?php echo htmlspecialchars($claim["item_name"]); ?>
                </h3>

                <p>
                    <strong>Location:</strong>
                    <?php echo htmlspecialchars($claim["location"]); ?>
                </p>

                <p>
                    <strong>Your message:</strong>
                    <?php echo nl2br(htmlspecialchars($claim["claim_message"])); ?>
                </p>

                <p>
                    <strong>Submitted:</strong>
                    <?php echo htmlspecialchars($claim["created_at"]); ?>
                </p>

                <p>
                    <strong>Status:</strong>
                    <span class="status <?php echo $status_class; ?>">
                        <?php echo htmlspecialchars(ucfirst($claim["status"])); ?>
                    </span>
                </p>

            </div>

        <?php endwhile; ?>

    <?php else: ?>

        <div class="empty">
            <h3>You haven't submitted any claims yet.</h3>
            <p>Browse found items to submit a claim.</p>
        </div>

    <?php endif; ?>

</div>

</body>
</html>

<?php
$stmt->close();
?>
