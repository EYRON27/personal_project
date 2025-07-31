<?php
session_start();
include 'db.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'] ?? 'User'; // Assuming you set this during login

// Message handling
$message = '';
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    unset($_SESSION['message']);
}

// Fetch totals
$stmt1 = $conn->prepare("SELECT SUM(amount) FROM money WHERE user_id = ? AND type = 'personal'");
$stmt1->bind_param("i", $user_id);
$stmt1->execute();
$stmt1->bind_result($personal_total);
$stmt1->fetch();
$stmt1->close();

$stmt2 = $conn->prepare("SELECT SUM(amount) FROM money WHERE user_id = ? AND type = 'business'");
$stmt2->bind_param("i", $user_id);
$stmt2->execute();
$stmt2->bind_result($business_total);
$stmt2->fetch();
$stmt2->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>MoneyTracker Dashboard</title>
    <link rel="stylesheet" href="dashboard.css">
</head>
<body class="<?= isset($_COOKIE['night_mode']) && $_COOKIE['night_mode'] == '1' ? 'dark-mode' : '' ?>">
    <div class="sidebar">
        <h2>MoneyTracker</h2>
        <p>Manage your funds</p>
        <a href="#">🏠 Dashboard</a>
        <a href="reports.php">📊 Reports</a>
        <a href="index.php">🚪 Logout</a>
    </div>

    <div class="main">
        <header>
            <h1>Hello, <?= htmlspecialchars($username) ?>!</h1>
            <p>Track your personal and business money efficiently</p>
        </header>

        <div class="balance-cards">
            <div class="card">
                <h3>Personal Money</h3>
                <p>₱<?= number_format($personal_total ?? 0, 2) ?></p>
            </div>
            <div class="card">
                <h3>Business Money</h3>
                <p>₱<?= number_format($business_total ?? 0, 2) ?></p>
            </div>
        </div>

        <div class="form-card">
            <h3>Add or Subtract Money</h3>
            <?php if ($message): ?>
                <div class="message"><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>

            <form action="update_money.php" method="POST">
                <label for="type">Type:</label>
                <select name="type" required>
                    <option value="personal">Personal</option>
                    <option value="business">Business</option>
                </select>

                <label for="amount">Amount (+ or -):</label>
                <input type="number" step="0.01" name="amount" required>

                <label for="description">Description:</label>
                <input type="text" name="description" maxlength="255" required placeholder="Enter reason...">

                <button type="submit">Submit</button>
            </form>
        </div>
    </div>
</body>
</html>
