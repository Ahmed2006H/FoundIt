
<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit;
}

require_once __DIR__ . "/../config/database.php";

$user_id = (int) $_SESSION["user_id"];
$item_id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

if (!$item_id || $item_id < 1) {
    exit("Invalid item.");
}

// Only open found items can be claimed.
$stmt = $conn->prepare(
    "SELECT id, user_id, item_name
     FROM items
     WHERE id = ? AND type = 'found' AND status = 'open'"
);
$stmt->bind_param("i", $item_id);
$stmt->execute();
$item = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$item) {
    exit("This item is not available for claiming.");
}

if ((int) $item["user_id"] === $user_id) {
    exit("You cannot claim an item you reported yourself.");
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $claim_message = trim($_POST["claim_message"] ?? "");

    if ($claim_message === "") {
        $error = "Please explain why this item belongs to you.";
    } elseif (mb_strlen($claim_message) < 10) {
        $error = "Please write at least 10 characters.";
    } elseif (mb_strlen($claim_message) > 2000) {
        $error = "Your message is too long.";
    } else {
        // Prevent duplicate pending or approved claims.
        $check = $conn->prepare(
            "SELECT id FROM claims
             WHERE item_id = ? AND user_id = ?
             AND status IN ('pending', 'approved')
             LIMIT 1"
        );
        $check->bind_param("ii", $item_id, $user_id);
        $check->execute();
        $existing = $check->get_result()->fetch_assoc();
        $check->close();

        if ($existing) {
            $error = "You already have an active claim for this item.";
        } else {
            $insert = $conn->prepare(
                "INSERT INTO claims (item_id, user_id, claim_message)
                 VALUES (?, ?, ?)"
            );
            $insert->bind_param("iis", $item_id, $user_id, $claim_message);

            if ($insert->execute()) {
                $insert->close();

                // Notify the person who reported the found item.
                $owner_id = (int) $item["user_id"];
                $notice_text = "Someone submitted a claim for your found item: "
                    . $item["item_name"];

                $notice = $conn->prepare(
                    "INSERT INTO notifications (user_id, message)
                     VALUES (?, ?)"
                );
                $notice->bind_param("is", $owner_id, $notice_text);
                $notice->execute();
                $notice->close();

                header("Location: my_claims.php?sent=1");
                exit;
            } else {
                $error = "Could not submit your claim. Please try again.";
                $insert->close();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Claim Item - FoundIt Campus</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: Arial, sans-serif;
            background: #f3f6fb;
            margin: 0;
            color: #243047;
        }
        .container {
            max-width: 600px;
            margin: 50px auto;
            padding: 28px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 18px rgba(0,0,0,.07);
        }
        textarea {
            width: 100%;
            min-height: 140px;
            padding: 12px;
            border: 1px solid #ccd4e0;
            border-radius: 7px;
            font: inherit;
        }
        button {
            width: 100%;
            margin-top: 18px;
            padding: 13px;
            background: #2457a7;
            color: white;
            border: 0;
            border-radius: 7px;
            cursor: pointer;
            font-size: 16px;
        }
        .error {
            background: #ffe9e9;
            color: #a32121;
            padding: 12px;
            border-radius: 7px;
        }
    </style>
</head>
<body>
<div class="container">
    <a href="items.php">← Back to items</a>

    <h1>Claim This Item</h1>
    <h3><?php echo htmlspecialchars($item["item_name"]); ?></h3>

    <p>
        Explain identifying details that can help the reporter verify
        that this item belongs to you. Avoid sharing passwords or sensitive information.
    </p>

    <?php if ($error !== ""): ?>
        <p class="error"><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>

    <form method="POST">
        <label for="claim_message">Why does this item belong to you?</label>
        <textarea
            id="claim_message"
            name="claim_message"
            minlength="10"
            maxlength="2000"
            required><?php
                echo htmlspecialchars($_POST["claim_message"] ?? "");
            ?></textarea>

        <button type="submit">Submit Claim</button>
    </form>
</div>
</body>
</html>
