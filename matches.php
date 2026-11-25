
<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit;
}

require_once __DIR__ . "/../config/database.php";

$userId = (int) $_SESSION["user_id"];

$sql = "
    SELECT
        m.match_score,
        m.match_explanation,
        m.created_at,

        l.id AS lost_id,
        l.item_name AS lost_name,
        l.description AS lost_description,
        l.category AS lost_category,
        l.location AS lost_location,

        f.id AS found_id,
        f.item_name AS found_name,
        f.description AS found_description,
        f.category AS found_category,
        f.location AS found_location

    FROM ai_matches m

    INNER JOIN items l ON m.lost_item_id = l.id
    INNER JOIN items f ON m.found_item_id = f.id

    WHERE l.user_id = ? OR f.user_id = ?

    ORDER BY m.match_score DESC, m.created_at DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $userId, $userId);
$stmt->execute();

$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>AI Matches - FoundIt Campus</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f6f9;
            margin: 0;
            padding: 30px;
            color: #222;
        }

        .container {
            max-width: 950px;
            margin: auto;
        }

        .match-card {
            background: white;
            padding: 24px;
            margin: 20px 0;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.06);
        }

        .items {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .item {
            background: #f8fafc;
            padding: 16px;
            border-radius: 9px;
        }

        .score {
            color: #16834a;
            font-size: 24px;
            font-weight: bold;
        }

        .explanation {
            background: #eef6ff;
            padding: 14px;
            border-radius: 8px;
            margin-top: 18px;
        }

        .empty {
            background: white;
            padding: 25px;
            border-radius: 10px;
        }

        .back {
            display: inline-block;
            margin: 15px 0;
            color: #2563eb;
            text-decoration: none;
        }

        @media (max-width: 600px) {
            .items {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>

<div class="container">

    <h1>✨ AI Suggested Matches</h1>

    <p>Possible matches discovered by Groq AI.</p>

        <a class="back" href="/foundit/dashboard.php">
          ← Back to Dashboard
        </a>

    <?php if ($result->num_rows === 0): ?>

        <div class="empty">
            <h3>No AI matches found yet.</h3>
            <p>When a possible match is discovered for one of your reports, it will appear here.</p>
        </div>

    <?php else: ?>

        <?php while ($match = $result->fetch_assoc()): ?>

            <div class="match-card">

                <div class="score">
                    <?= htmlspecialchars($match["match_score"]) ?>% Match
                </div>

                <div class="items">

                    <div class="item">
                        <h3>🔍 Lost Item</h3>

                        <h4>
                            <?= htmlspecialchars($match["lost_name"]) ?>
                        </h4>

                        <p>
                            <?= htmlspecialchars($match["lost_description"]) ?>
                        </p>

                        <p>
                            <strong>Category:</strong>
                            <?= htmlspecialchars($match["lost_category"]) ?>
                        </p>

                        <p>
                            <strong>Location:</strong>
                            <?= htmlspecialchars($match["lost_location"]) ?>
                        </p>
                    </div>

                    <div class="item">
                        <h3>📦 Found Item</h3>

                        <h4>
                            <?= htmlspecialchars($match["found_name"]) ?>
                        </h4>

                        <p>
                            <?= htmlspecialchars($match["found_description"]) ?>
                        </p>

                        <p>
                            <strong>Category:</strong>
                            <?= htmlspecialchars($match["found_category"]) ?>
                        </p>

                        <p>
                            <strong>Location:</strong>
                            <?= htmlspecialchars($match["found_location"]) ?>
                        </p>
                    </div>

                </div>

                <div class="explanation">
                    <strong>🤖 AI Explanation:</strong>
                    <p>
                        <?= nl2br(htmlspecialchars($match["match_explanation"])) ?>
                    </p>
                </div>

                <small>
                    Suggested match — please verify the item before making a claim.
                </small>

            </div>

        <?php endwhile; ?>

    <?php endif; ?>

</div>

</body>
</html>

<?php
$stmt->close();
?>
