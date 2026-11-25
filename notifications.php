
<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit;
}

require_once __DIR__ . "/../config/database.php";

$user_id = (int) $_SESSION["user_id"];
$error = "";

// Mark one notification as read.
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $notification_id = filter_input(
        INPUT_POST,
        "notification_id",
        FILTER_VALIDATE_INT
    );

    if ($notification_id && $notification_id > 0) {
        $stmt = $conn->prepare(
            "UPDATE notifications
             SET is_read = 1
             WHERE id = ? AND user_id = ?"
        );

        $stmt->bind_param("ii", $notification_id, $user_id);

        if (!$stmt->execute()) {
            $error = "Could not update the notification.";
        }

        $stmt->close();
    }
}

// Get this user's notifications.
$stmt = $conn->prepare(
    "SELECT id, message, is_read, created_at
     FROM notifications
     WHERE user_id = ?
     ORDER BY created_at DESC"
);

$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$unread_count = 0;
$notifications = [];

while ($notification = $result->fetch_assoc()) {
    $notifications[] = $notification;

    if ((int) $notification["is_read"] === 0) {
        $unread_count++;
    }
}

$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Notifications - FoundIt Campus</title>

    <style>
        * {
            box-sizing: border-box;
        }

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
            max-width: 850px;
            margin: 35px auto;
            padding: 0 20px;
        }

        .notification {
            background: white;
            padding: 20px;
            margin-bottom: 15px;
            border-radius: 10px;
            border-left: 5px solid #ccd4e0;
            box-shadow: 0 3px 12px rgba(0,0,0,.05);
        }

        .unread {
            border-left-color: #2457a7;
            background: #f8faff;
        }

        .badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 5px;
            background: #e8efff;
            color: #2457a7;
            font-size: 13px;
        }

        button {
            padding: 9px 14px;
            background: #2457a7;
            color: white;
            border: 0;
            border-radius: 6px;
            cursor: pointer;
        }

        .empty, .error {
            background: white;
            padding: 22px;
            border-radius: 10px;
        }

        .error {
            color: #a32121;
            background: #ffe9e9;
        }

        .date {
            color: #777;
            font-size: 13px;
        }
    </style>
</head>

<body>

<header>
    <h2>FoundIt Campus</h2>
    <a href="../dashboard.php">Dashboard</a>
    <a href="items.php">Browse Items</a>
    <a href="my_claims.php">My Claims</a>
</header>

<div class="container">

    <h1>My Notifications</h1>

    <p>
        You have
        <strong><?php echo $unread_count; ?></strong>
        unread notification(s).
    </p>

    <?php if ($error !== ""): ?>
        <div class="error">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <?php if (count($notifications) > 0): ?>

        <?php foreach ($notifications as $notification): ?>

            <div class="notification
                <?php echo (int) $notification["is_read"] === 0
                    ? "unread" : ""; ?>">

                <p>
                    <?php echo nl2br(
                        htmlspecialchars($notification["message"])
                    ); ?>
                </p>

                <p class="date">
                    <?php echo htmlspecialchars(
                        $notification["created_at"]
                    ); ?>
                </p>

<?php if (stripos($notification["message"], "AI found a possible match") !== false): ?>

    <a href="../ai/matches.php"
       style="
           display: inline-block;
           margin-top: 10px;
           padding: 9px 16px;
           background: #2563eb;
           color: white;
           text-decoration: none;
           border-radius: 6px;
       ">
        ✨ View AI Matches
    </a>

<?php endif; ?>


                <?php if ((int) $notification["is_read"] === 0): ?>

                    <span class="badge">Unread</span>

                    <form method="POST" style="margin-top: 12px;">
                        <input
                            type="hidden"
                            name="notification_id"
                            value="<?php echo (int) $notification["id"]; ?>"
                        >

                        <button type="submit">Mark as Read</button>
                    </form>

                <?php else: ?>

                    <span class="badge">Read</span>

                <?php endif; ?>

            </div>

        <?php endforeach; ?>

    <?php else: ?>

        <div class="empty">
            <h3>No notifications yet</h3>
            <p>New updates about your items and claims will appear here.</p>
        </div>

    <?php endif; ?>

</div>

</body>
</html>
