<?php
session_start();
require_once 'config/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];

    $query = "DELETE FROM tasks WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $id);

    if ($stmt->execute()) {
        header('Location: tasks.php?success=Task deleted successfully');
    } else {
        header('Location: tasks.php?error=Error deleting task');
    }
    $stmt->close();
} else {
    header('Location: tasks.php');
}
exit();
?>
