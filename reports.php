<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Total money
$stmt = $conn->prepare("SELECT SUM(amount) FROM money WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($total_money);
$stmt->fetch();
$stmt->close();

// Today
$stmt = $conn->prepare("SELECT SUM(amount) FROM money WHERE user_id = ? AND DATE(created_at) = CURDATE()");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($today_total);
$stmt->fetch();
$stmt->close();

// This week
$stmt = $conn->prepare("SELECT SUM(amount) FROM money WHERE user_id = ? AND YEARWEEK(created_at, 1) = YEARWEEK(CURDATE(), 1)");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($week_total);
$stmt->fetch();
$stmt->close();

// This month
$stmt = $conn->prepare("SELECT SUM(amount) FROM money WHERE user_id = ? AND MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($month_total);
$stmt->fetch();
$stmt->close();

// Transaction history
$stmt = $conn->prepare("SELECT type, amount, created_at FROM money WHERE user_id = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>MoneyTracker Reports</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles/sidebar.css">
    <link rel="stylesheet" href="styles/reports.css">
</head>
<body>
<div class="sidebar">
    <div class="logo">
        <h2>💰 MoneyTracker</h2>
    </div>

    <nav class="nav-links">
        <a href="dashboard.php"><span>🏠</span> Dashboard</a>
        <a href="reports.php"><span>📊</span> Reports</a>
    </nav>

    <a href="index.php" class="logout">Logout</a>
</div>


<div class="main">
    <header>
        <h1>Reports</h1>
        <p>Overview of your money activity</p>
    </header>

    <div class="balance-cards">
        <div class="card">
            <h3>Total Money</h3>
            <p>₱<?= number_format($total_money ?? 0, 2) ?></p>
        </div>
        <div class="card">
            <h3>Today's Flow</h3>
            <p>₱<?= number_format($today_total ?? 0, 2) ?></p>
        </div>
        <div class="card">
            <h3>This Week</h3>
            <p>₱<?= number_format($week_total ?? 0, 2) ?></p>
        </div>
        <div class="card">
            <h3>This Month</h3>
            <p>₱<?= number_format($month_total ?? 0, 2) ?></p>
        </div>
    </div>

    <div class="form-card">
        <h3>Transaction History</h3>
        <table>
            <thead>
                <tr>
                    <th>Type</th>
                    <th>Amount</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($row['type']) ?></td>
                    <td>₱<?= number_format($row['amount'], 2) ?></td>
                    <td><?= date("F j, Y - g:i A", strtotime($row['created_at'])) ?></td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>
