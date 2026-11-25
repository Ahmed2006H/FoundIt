
<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: auth/login.php");
    exit;
}

$full_name = htmlspecialchars($_SESSION["full_name"] ?? "User");
$role = $_SESSION["role"] ?? "user";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Dashboard | FoundIt Campus</title>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background: #f1f5f9;
            color: #1e293b;
        }

        header {
            background: #ffffff;
            padding: 20px 7%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .logo {
            color: #2563eb;
            font-size: 24px;
            font-weight: bold;
        }

        .logout {
            color: #dc2626;
            text-decoration: none;
            font-weight: bold;
        }

        .container {
            width: 86%;
            max-width: 1100px;
            margin: 40px auto;
        }

        .welcome {
            margin-bottom: 30px;
        }

        .welcome h1 {
            margin-bottom: 10px;
        }

        .welcome p {
            color: #64748b;
        }

        .cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
        }

        .card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.04);
        }

        .card h2 {
            color: #2563eb;
            font-size: 20px;
            margin-bottom: 12px;
        }

        .card p {
            color: #64748b;
            line-height: 1.6;
            margin-bottom: 20px;
        }

        .card a {
            display: inline-block;
            background: #2563eb;
            color: white;
            padding: 10px 16px;
            border-radius: 7px;
            text-decoration: none;
        }

        footer {
            text-align: center;
            padding: 25px;
            color: #64748b;
        }
    </style>
</head>

<body>

<header>
    <div class="logo">FoundIt Campus</div>
    <a class="logout" href="auth/logout.php">Logout</a>
</header>

<main class="container">

    <section class="welcome">
        <h1>Welcome, <?= $full_name ?>!</h1>
        <p>Manage your lost and found reports from your dashboard.</p>
    </section>

    <section class="cards">
        

        <div class="card">
            <h2>Report Lost Item</h2>
            <p>Report an item that you have lost on campus.</p>
            <a href="user/add_item.php?type=lost">Report Now</a>
        </div>


<div class="card">
    <h2>Report Found Item</h2>
    <p>Help someone recover an item you have found.</p>
    <a href="user/add_item.php?type=found">Report Now</a>
</div>

<div class="card">
    <h2>Manage Claims</h2>
    <p>Review and respond to claim requests.</p>
    <a href="user/manage_claims.php">Manage Claims</a>
</div>


        <div class="card">
            <h2>Browse Items</h2>
            <p>Search through lost and found items.</p>
            <a href="user/items.php">Browse Now</a>
        </div>


<div class="card">
    <h2>✨ AI Smart Matches</h2>
    <p>Discover possible matches between your lost and found reports using AI.</p>
    <a href="/foundit/ai/matches.php">View AI Matches</a>
</div>



        <div class="card">
            <h2>My Claims</h2>
            <p>View and manage your claim requests.</p>
            <a href="user/my_claims.php">View Claims</a>
        </div>

        <div class="card">
            <h2>Notifications</h2>
            <p>Check updates about your reports and claims.</p>
            <a href="user/notifications.php">View Notifications</a>
        </div>

        <?php if ($role === "admin"): ?>
            <div class="card">
                <h2>Admin Panel</h2>
                <p>Manage users and monitor reported items.</p>
                <a href="admin/index.php">Open Admin Panel</a>
            </div>
        <?php endif; ?>

    </section>

</main>

<footer>
    FoundIt Campus &copy; 2026
</footer>

</body>
</html>
