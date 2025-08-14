<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

if (isset($_POST['delete_id']) && is_numeric($_POST['delete_id'])) {
    $delete_id = (int)$_POST['delete_id'];

    // Delete transaction only if it belongs to the logged-in user
    $stmt = $conn->prepare("DELETE FROM money WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $delete_id, $user_id);
    $stmt->execute();
    $stmt->close();

    $_SESSION['message'] = "Transaction deleted successfully.";
    header("Location: reports.php");
    exit();
}

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
$stmt = $conn->prepare("SELECT id, type, amount, reason, created_at FROM money WHERE user_id = ? ORDER BY created_at DESC");
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
    <style>
     /* Modern Confirmation Modal */
.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    top: 0; left: 0;
    width: 100%; height: 100%;
    background: rgba(0,0,0,0.4);
    animation: fadeIn 0.2s ease-in-out;
}

.modal-content {
    background: #fff;
    padding: 25px 20px;
    margin: 15% auto;
    width: 350px;
    border-radius: 12px;
    text-align: center;
    font-family: 'Poppins', sans-serif;
    box-shadow: 0 6px 20px rgba(0,0,0,0.15);
    animation: slideDown 0.25s ease-in-out;
}

.modal-content p {
    font-size: 15px;
    font-weight: 500;
    color: #333;
    margin-bottom: 20px;
}

.confirm-delete {
    background: #e63946;
    color: white;
    padding: 10px 18px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-size: 14px;
    transition: background 0.2s ease-in-out;
}
.confirm-delete:hover {
    background: #d62828;
}

.cancel-delete {
    background: #adb5bd;
    color: white;
    padding: 10px 18px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-size: 14px;
    margin-left: 10px;
    transition: background 0.2s ease-in-out;
}
.cancel-delete:hover {
    background: #6c757d;
}

/* Animations */
@keyframes fadeIn {
    from {opacity: 0;}
    to {opacity: 1;}
}
@keyframes slideDown {
    from {transform: translateY(-20px); opacity: 0;}
    to {transform: translateY(0); opacity: 1;}
}

    </style>
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
        <div class="card"><h3>Total Money</h3><p>₱<?= number_format($total_money ?? 0, 2) ?></p></div>
        <div class="card"><h3>Today's Flow</h3><p>₱<?= number_format($today_total ?? 0, 2) ?></p></div>
        <div class="card"><h3>This Week</h3><p>₱<?= number_format($week_total ?? 0, 2) ?></p></div>
        <div class="card"><h3>This Month</h3><p>₱<?= number_format($month_total ?? 0, 2) ?></p></div>
    </div>

    <div class="form-card">
        <h3>Transaction History</h3>
        <table>
            <thead>
                <tr>
                    <th>Type</th>
                    <th>Amount</th>
                    <th>Reasons</th>
                    <th>Date</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($row['type']) ?></td>
                    <td>₱<?= number_format($row['amount'], 2) ?></td>
                    <td><?= htmlspecialchars($row['reason']) ?></td>
                    <td><?= date("F j, Y - g:i A", strtotime($row['created_at'])) ?></td>
                    <td>
                        <button type="button" class="delete-btn" data-id="<?= $row['id'] ?>" style="background:red;color:white;border:none;padding:5px 10px;cursor:pointer;">
                            Delete
                        </button>
                    </td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal -->
<div id="confirmModal" class="modal">
    <div class="modal-content">
        <p>Are you sure you want to delete this transaction?</p>
        <form method="POST">
            <input type="hidden" name="delete_id" id="deleteId">
            <button type="submit" class="confirm-delete">Yes, Delete</button>
            <button type="button" class="cancel-delete">Cancel</button>
        </form>
    </div>
</div>

<script>
document.querySelectorAll('.delete-btn').forEach(button => {
    button.addEventListener('click', () => {
        document.getElementById('deleteId').value = button.dataset.id;
        document.getElementById('confirmModal').style.display = 'block';
    });
});
document.querySelector('.cancel-delete').addEventListener('click', () => {
    document.getElementById('confirmModal').style.display = 'none';
});
</script>
</body>
</html>
