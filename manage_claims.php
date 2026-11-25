
<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit;
}

require_once __DIR__ . "/../config/database.php";

$user_id = (int) $_SESSION["user_id"];
$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $claim_id = filter_input(INPUT_POST, "claim_id", FILTER_VALIDATE_INT);
    $action = $_POST["action"] ?? "";

    if (!$claim_id || !in_array($action, ["approve", "reject"], true)) {
        $error = "Invalid request.";
    } else {
        try {
            $conn->begin_transaction();

            // Make sure this claim belongs to an item reported by this user.
            $check = $conn->prepare(
                "SELECT claims.id, claims.user_id AS claimant_id,
                        claims.item_id, items.item_name, items.status AS item_status
                 FROM claims
                 JOIN items ON claims.item_id = items.id
                 WHERE claims.id = ?
                   AND items.user_id = ?
                   AND claims.status = 'pending'
                 FOR UPDATE"
            );

            $check->bind_param("ii", $claim_id, $user_id);
            $check->execute();
            $claim = $check->get_result()->fetch_assoc();
            $check->close();

            if (!$claim) {
                throw new Exception("This request is no longer available.");
            }

            if ($claim["item_status"] !== "open") {
                throw new Exception("This item is no longer open.");
            }

            $claimant_id = (int) $claim["claimant_id"];
            $item_id = (int) $claim["item_id"];
            $item_name = $claim["item_name"];

            if ($action === "approve") {
                $update_claim = $conn->prepare(
                    "UPDATE claims SET status = 'approved'
                     WHERE id = ? AND status = 'pending'"
                );
                $update_claim->bind_param("i", $claim_id);
                $update_claim->execute();

                if ($update_claim->affected_rows !== 1) {
                    throw new Exception("Could not approve this request.");
                }
                $update_claim->close();

                $update_item = $conn->prepare(
                    "UPDATE items SET status = 'resolved'
                     WHERE id = ? AND user_id = ? AND status = 'open'"
                );
                $update_item->bind_param("ii", $item_id, $user_id);
                $update_item->execute();

                if ($update_item->affected_rows !== 1) {
                    throw new Exception("Could not update the item.");
                }
                $update_item->close();

                // Reject other pending claims for this same item.
                $reject_others = $conn->prepare(
                    "UPDATE claims SET status = 'rejected'
                     WHERE item_id = ? AND id <> ? AND status = 'pending'"
                );
                $reject_others->bind_param("ii", $item_id, $claim_id);
                $reject_others->execute();
                $reject_others->close();

                $message = "Your claim for \"" . $item_name .
                    "\" was approved. Please contact the reporter to arrange collection.";

            } else {
                $update_claim = $conn->prepare(
                    "UPDATE claims SET status = 'rejected'
                     WHERE id = ? AND status = 'pending'"
                );
                $update_claim->bind_param("i", $claim_id);
                $update_claim->execute();

                if ($update_claim->affected_rows !== 1) {
                    throw new Exception("Could not reject this request.");
                }
                $update_claim->close();

                $message = "Your claim for \"" . $item_name . "\" was rejected.";
            }

            $notice = $conn->prepare(
                "INSERT INTO notifications (user_id, message)
                 VALUES (?, ?)"
            );
            $notice->bind_param("is", $claimant_id, $message);
            $notice->execute();
            $notice->close();

            $conn->commit();

            header("Location: manage_claims.php?updated=1");
            exit;

        } catch (Throwable $e) {
            $conn->rollback();
            $error = $e->getMessage();
        }
    }
}

// Show pending claims only for items reported by the logged-in user.
$stmt = $conn->prepare(
    "SELECT claims.id, claims.claim_message, claims.created_at,
            users.full_name AS claimant_name,
            items.item_name, items.location
     FROM claims
     JOIN items ON claims.item_id = items.id
     JOIN users ON claims.user_id = users.id
     WHERE items.user_id = ? AND claims.status = 'pending'
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
    <title>Manage Claims - FoundIt Campus</title>

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

        .actions {
            display: flex;
            gap: 10px;
            margin-top: 18px;
        }

        button {
            padding: 11px 20px;
            border: 0;
            border-radius: 6px;
            color: white;
            cursor: pointer;
        }

        .approve { background: #198754; }
        .reject { background: #c0392b; }

        .empty, .success, .error {
            padding: 18px;
            border-radius: 8px;
            background: white;
            margin-bottom: 18px;
        }

        .success {
            background: #e3f8e9;
            color: #176b32;
        }

        .error {
            background: #ffe9e9;
            color: #a32121;
        }

        @media (max-width: 500px) {
            .actions { flex-direction: column; }
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

    <h1>Manage Claim Requests</h1>
    <p>Review requests submitted for items you reported.</p>

    <?php if (isset($_GET["updated"])): ?>
        <div class="success">
            The claim request was updated successfully.
        </div>
    <?php endif; ?>

    <?php if ($error !== ""): ?>
        <div class="error">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <?php if ($result->num_rows > 0): ?>

        <?php while ($claim = $result->fetch_assoc()): ?>

            <div class="claim">

                <h3>
                    <?php echo htmlspecialchars($claim["item_name"]); ?>
                </h3>

                <p>
                    <strong>Claimant:</strong>
                    <?php echo htmlspecialchars($claim["claimant_name"]); ?>
                </p>

                <p>
                    <strong>Location:</strong>
                    <?php echo htmlspecialchars($claim["location"]); ?>
                </p>

                <p>
                    <strong>Claim message:</strong><br>
                    <?php echo nl2br(htmlspecialchars($claim["claim_message"])); ?>
                </p>

                <p>
                    <strong>Submitted:</strong>
                    <?php echo htmlspecialchars($claim["created_at"]); ?>
                </p>

                <div class="actions">

                    <form method="POST"
                          onsubmit="return confirm('Approve this claim?');">
                        <input type="hidden" name="claim_id"
                               value="<?php echo (int) $claim["id"]; ?>">
                        <input type="hidden" name="action" value="approve">
                        <button class="approve" type="submit">
                            Approve
                        </button>
                    </form>

                    <form method="POST"
                          onsubmit="return confirm('Reject this claim?');">
                        <input type="hidden" name="claim_id"
                               value="<?php echo (int) $claim["id"]; ?>">
                        <input type="hidden" name="action" value="reject">
                        <button class="reject" type="submit">
                            Reject
                        </button>
                    </form>

                </div>

            </div>

        <?php endwhile; ?>

    <?php else: ?>

        <div class="empty">
            <h3>No pending claims</h3>
            <p>You don't have any claim requests waiting for review.</p>
        </div>

    <?php endif; ?>

</div>

</body>
</html>

<?php
$stmt->close();
?>
