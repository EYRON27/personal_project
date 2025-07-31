<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$type = $_POST['type'];
$amount = $_POST['amount'];
$description = $_POST['description'];

// Simple validation
if (!in_array($type, ['personal', 'business']) || !is_numeric($amount) || empty($description)) {
    $_SESSION['message'] = "Invalid input.";
    header("Location: dashboard.php");
    exit();
}

// Insert into DB (you need to add `description` column if it's not there)
$stmt = $conn->prepare("INSERT INTO money (user_id, type, amount, created_at) VALUES (?, ?, ?, NOW())");
$stmt->bind_param("isd", $user_id, $type, $amount);
if ($stmt->execute()) {
    $_SESSION['message'] = "Transaction recorded successfully.";
} else {
    $_SESSION['message'] = "Failed to add transaction.";
}
$stmt->close();

header("Location: dashboard.php");
exit();
