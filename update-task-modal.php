<?php
session_start();
require_once 'config/db.php';

// Check if user is authenticated
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

$taskId = isset($_POST['task_id']) ? (int)$_POST['task_id'] : null;
$status = isset($_POST['status']) ? $_POST['status'] : null;
$anomalies = isset($_POST['anomalies_detected']) ? trim($_POST['anomalies_detected']) : '';

if (!$taskId) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Task ID is required']);
    exit();
}

if (!$status) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Status is required']);
    exit();
}

// Validate status value
$validStatuses = ['Not Yet Started', 'Ongoing', 'Done', 'Overdue'];
if (!in_array($status, $validStatuses)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid status']);
    exit();
}

// Update task
$query = "UPDATE tasks SET status = ?, anomalies_detected = ? WHERE id = ?";
$stmt = $conn->prepare($query);
if (!$stmt) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
    exit();
}

$stmt->bind_param('ssi', $status, $anomalies, $taskId);

if ($stmt->execute()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => 'Task updated successfully']);
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Error updating task: ' . $stmt->error]);
}

$stmt->close();
?>
