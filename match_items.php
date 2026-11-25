
<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit;
}

require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/run_matching.php";

$message = "";
$error = "";

// CSRF protection
if (empty($_SESSION["ai_csrf"])) {
    $_SESSION["ai_csrf"] = bin2hex(random_bytes(32));
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (
        !isset($_POST["csrf_token"]) ||
        !hash_equals($_SESSION["ai_csrf"], $_POST["csrf_token"])
    ) {
        $error = "Invalid request. Please refresh the page.";
    } else {

        try {
            $savedCount = runAIMatching($conn);

            if ($savedCount === 0) {
                $message = "Matching completed. No new or updated matches were found.";
            } else {
                $message = "Matching completed successfully. Saved/updated matches: " . $savedCount;
            }

        } catch (Throwable $e) {
            error_log("AI Matching Error: " . $e->getMessage());
            $error = "AI matching failed. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>AI Matching - FoundIt Campus</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f6f9;
            padding: 40px;
        }

        .container {
            max-width: 650px;
            margin: auto;
            background: white;
            padding: 30px;
            border-radius: 12px;
        }

        button {
            background: #2563eb;
            color: white;
            border: none;
            padding: 12px 22px;
            border-radius: 7px;
            cursor: pointer;
            font-size: 16px;
        }

        .message {
            color: green;
            margin: 20px 0;
        }

        .error {
            color: #c62828;
            margin: 20px 0;
        }
    </style>
</head>

<body>

<div class="container">

    <h1>AI Matching</h1>

    <p>
        Compare open lost and found reports using Groq AI.
    </p>

    <?php if ($message !== ""): ?>
        <p class="message">
            <?= htmlspecialchars($message) ?>
        </p>
    <?php endif; ?>

    <?php if ($error !== ""): ?>
        <p class="error">
            <?= htmlspecialchars($error) ?>
        </p>
    <?php endif; ?>

    <form method="POST">
        <input
            type="hidden"
            name="csrf_token"
            value="<?= htmlspecialchars($_SESSION["ai_csrf"]) ?>"
        >

        <button type="submit">
            Run AI Matching
        </button>
    </form>

</div>

</body>
</html>
