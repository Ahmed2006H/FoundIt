
<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit;
}

require_once __DIR__ . "/../config/database.php";

$type = $_GET["type"] ?? "lost";

if (!in_array($type, ["lost", "found"], true)) {
    $type = "lost";
}

$page_title = ($type === "lost")
    ? "Report Lost Item"
    : "Report Found Item";

$message = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $item_name = trim($_POST["item_name"] ?? "");
    $description = trim($_POST["description"] ?? "");
    $category = trim($_POST["category"] ?? "");
    $location = trim($_POST["location"] ?? "");
    $item_date = $_POST["item_date"] ?? "";
    $type = $_POST["type"] ?? "lost";

    if (!in_array($type, ["lost", "found"], true)) {
        $error = "Invalid report type.";
    } elseif (
        $item_name === "" || $description === "" ||
        $category === "" || $location === "" || $item_date === ""
    ) {
        $error = "Please fill in all required fields.";
    } else {

        $page_title = ($type === "lost")
            ? "Report Lost Item"
            : "Report Found Item";

        $image_path = null;

        if (
            isset($_FILES["item_image"]) &&
            $_FILES["item_image"]["error"] !== UPLOAD_ERR_NO_FILE
        ) {
            $file = $_FILES["item_image"];

            if ($file["error"] !== UPLOAD_ERR_OK) {
                $error = "Image upload failed.";
            } elseif ($file["size"] > 3 * 1024 * 1024) {
                $error = "Image must be 3 MB or smaller.";
            } else {

                $allowed = [
                    "image/jpeg" => "jpg",
                    "image/png" => "png",
                    "image/webp" => "webp"
                ];

                $mime = mime_content_type($file["tmp_name"]);

                if (!isset($allowed[$mime])) {
                    $error = "Only JPG, PNG, and WEBP images are allowed.";
                } else {

                    $upload_dir = __DIR__ . "/../uploads/items/";

                    if (!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0755, true);
                    }

                    $filename = bin2hex(random_bytes(16)) . "." . $allowed[$mime];
                    $destination = $upload_dir . $filename;

                    if (move_uploaded_file($file["tmp_name"], $destination)) {
                        $image_path = "uploads/items/" . $filename;
                    } else {
                        $error = "Could not save the uploaded image.";
                    }
                }
            }
        }

        if ($error === "") {

            $user_id = $_SESSION["user_id"];

            $stmt = $conn->prepare(
                "INSERT INTO items
                (user_id, item_name, description, category, type, location, item_date, image_path)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
            );

            if (!$stmt) {
                $error = "Could not prepare database query.";
            } else {

                $stmt->bind_param(
                    "isssssss",
                    $user_id,
                    $item_name,
                    $description,
                    $category,
                    $type,
                    $location,
                    $item_date,
                    $image_path
                );

                if ($stmt->execute()) {

                    $stmt->close();

                    // Run AI matching after saving the report
                    try {
                        require_once __DIR__ . "/../ai/run_matching.php";
                        runAIMatching($conn);
                    } catch (Throwable $e) {
                        // AI failure must not affect report submission
                        error_log("AI Matching Error: " . $e->getMessage());
                    }

                    header("Location: items.php?added=1");
                    exit;

                } else {

                    $error = "Could not save the report. Please try again.";

                    if ($image_path !== null) {
                        $saved_file = __DIR__ . "/../" . $image_path;

                        if (is_file($saved_file)) {
                            unlink($saved_file);
                        }
                    }

                    $stmt->close();
                }
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

    <title><?php echo htmlspecialchars($page_title); ?> - FoundIt Campus</title>

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

        .container {
            max-width: 650px;
            margin: 45px auto;
            padding: 30px;
            background: white;
            border-radius: 14px;
            box-shadow: 0 5px 22px rgba(0,0,0,.07);
        }

        h1 {
            color: #2457a7;
            margin-top: 0;
        }

        label {
            display: block;
            margin: 17px 0 7px;
            font-weight: bold;
        }

        input, textarea, select {
            width: 100%;
            padding: 12px;
            border: 1px solid #ccd4e0;
            border-radius: 7px;
            font-size: 15px;
        }

        textarea {
            min-height: 110px;
            resize: vertical;
        }

        button {
            margin-top: 22px;
            width: 100%;
            padding: 14px;
            border: 0;
            border-radius: 7px;
            background: #2457a7;
            color: white;
            font-size: 16px;
            cursor: pointer;
        }

        .error {
            background: #ffe9e9;
            color: #a32121;
            padding: 12px;
            border-radius: 7px;
        }

        .back {
            display: inline-block;
            margin-bottom: 20px;
        }
    </style>
</head>

<body>

<div class="container">

    <a class="back" href="../dashboard.php">
        ← Back to Dashboard
    </a>

    <h1><?php echo htmlspecialchars($page_title); ?></h1>

    <?php if ($error !== ""): ?>
        <p class="error">
            <?php echo htmlspecialchars($error); ?>
        </p>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">

        <input
            type="hidden"
            name="type"
            value="<?php echo htmlspecialchars($type); ?>"
        >

        <label>Item Name</label>
        <input
            type="text"
            name="item_name"
            required
            value="<?php echo htmlspecialchars($_POST["item_name"] ?? ""); ?>"
        >

        <label>Description</label>
        <textarea name="description" required><?php
            echo htmlspecialchars($_POST["description"] ?? "");
        ?></textarea>

        <label>Category</label>
        <select name="category" required>
            <option value="">Choose category</option>

            <?php
            $categories = [
                "Electronics",
                "Books",
                "Bags",
                "Clothing",
                "Keys",
                "ID Cards",
                "Other"
            ];

            foreach ($categories as $cat):
            ?>
                <option
                    value="<?php echo htmlspecialchars($cat); ?>"
                    <?php echo (($_POST["category"] ?? "") === $cat) ? "selected" : ""; ?>
                >
                    <?php echo htmlspecialchars($cat); ?>
                </option>
            <?php endforeach; ?>

        </select>

        <label>Location on Campus</label>
        <input
            type="text"
            name="location"
            required
            placeholder="Example: Faculty of Engineering"
            value="<?php echo htmlspecialchars($_POST["location"] ?? ""); ?>"
        >

        <label>Date</label>
        <input
            type="date"
            name="item_date"
            required
            max="<?php echo date('Y-m-d'); ?>"
            value="<?php echo htmlspecialchars($_POST["item_date"] ?? ""); ?>"
        >

        <label>Item Image (Optional - JPG, PNG, WEBP; max 3 MB)</label>
        <input
            type="file"
            name="item_image"
            accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
        >

        <button type="submit">Submit Report</button>

    </form>

</div>

</body>
</html>
