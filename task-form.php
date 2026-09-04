<?php
session_start();
require_once 'config/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$error = '';
$success = '';
$taskDate = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
$taskId = isset($_GET['id']) ? (int)$_GET['id'] : null;
$task = null;

// Get list of people
$peopleQuery = "SELECT id, name FROM people ORDER BY name";
$peopleResult = $conn->query($peopleQuery);
$people = [];
while ($row = $peopleResult->fetch_assoc()) {
    $people[] = $row;
}

// If editing, get task details
if ($taskId) {
    $query = "SELECT * FROM tasks WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $taskId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows == 1) {
        $task = $result->fetch_assoc();
        $taskDate = $task['task_date'];
    }
    $stmt->close();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $description = isset($_POST['description']) ? trim($_POST['description']) : '';
    $task_date = isset($_POST['task_date']) ? $_POST['task_date'] : $taskDate;
    $assigned_to = isset($_POST['assigned_to']) && !empty($_POST['assigned_to']) ? (int)$_POST['assigned_to'] : null;
    $status = isset($_POST['status']) ? $_POST['status'] : 'Not Yet Started';
    $priority = isset($_POST['priority']) ? $_POST['priority'] : 'Medium';
    $notes = isset($_POST['notes']) ? trim($_POST['notes']) : '';
    $anomalies_detected = isset($_POST['anomalies_detected']) ? trim($_POST['anomalies_detected']) : '';

    if (empty($description)) {
        $error = 'Task description is required';
    } elseif (empty($task_date)) {
        $error = 'Task date is required';
    } else {
        // Validate assigned_to person exists if provided
        if ($assigned_to !== null) {
            $validateQuery = "SELECT id FROM people WHERE id = ?";
            $validateStmt = $conn->prepare($validateQuery);
            $validateStmt->bind_param('i', $assigned_to);
            $validateStmt->execute();
            $validateResult = $validateStmt->get_result();
            
            if ($validateResult->num_rows == 0) {
                $error = 'Selected person does not exist';
                $assigned_to = null;
            }
            $validateStmt->close();
        }

        if (empty($error)) {
            if ($taskId) {
                // Update existing task
                $query = "UPDATE tasks SET description = ?, task_date = ?, assigned_to = ?, status = ?, priority = ?, notes = ?, anomalies_detected = ? WHERE id = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param('ssissssi', $description, $task_date, $assigned_to, $status, $priority, $notes, $anomalies_detected, $taskId);
            } else {
                // Insert new task
                $query = "INSERT INTO tasks (description, task_date, assigned_to, status, priority, notes, anomalies_detected) VALUES (?, ?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($query);
                $stmt->bind_param('ssissss', $description, $task_date, $assigned_to, $status, $priority, $notes, $anomalies_detected);
            }

            if ($stmt->execute()) {
                $success = $taskId ? 'Task updated successfully!' : 'Task created successfully!';
                header("Location: index.php?month=" . date('m', strtotime($task_date)) . "&year=" . date('Y', strtotime($task_date)));
                exit();
            } else {
                $error = 'Error saving task: ' . $stmt->error;
            }
            $stmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $taskId ? 'Edit' : 'Create'; ?> Task</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1><?php echo $taskId ? '✏️ Edit Task' : '➕ Create New Task'; ?></h1>
            <nav>
                <a href="index.php">Calendar</a>
                <a href="tasks.php">All Tasks</a>
                <a href="people.php">People</a>
                <a href="logout.php">Logout</a>
            </nav>
        </header>

        <main style="padding: 30px;">
            <div class="form-container">
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="form-group">
                        <label for="description">Task Description *</label>
                        <input type="text" id="description" name="description" value="<?php echo $task ? htmlspecialchars($task['description']) : ''; ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="task_date">Date *</label>
                        <input type="date" id="task_date" name="task_date" value="<?php echo $taskDate; ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="assigned_to">Assign To</label>
                        <select id="assigned_to" name="assigned_to">
                            <option value="">-- Unassigned --</option>
                            <?php foreach ($people as $person): ?>
                                <option value="<?php echo $person['id']; ?>" <?php echo $task && $task['assigned_to'] == $person['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($person['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="status">Status *</label>
                        <select id="status" name="status" required>
                            <option value="Not Yet Started" <?php echo $task && $task['status'] == 'Not Yet Started' ? 'selected' : ''; ?>>Not Yet Started</option>
                            <option value="Ongoing" <?php echo $task && $task['status'] == 'Ongoing' ? 'selected' : ''; ?>>Ongoing</option>
                            <option value="Done" <?php echo $task && $task['status'] == 'Done' ? 'selected' : ''; ?>>Done</option>
                            <option value="Overdue" <?php echo $task && $task['status'] == 'Overdue' ? 'selected' : ''; ?>>Overdue</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="priority">Priority</label>
                        <select id="priority" name="priority">
                            <option value="Low" <?php echo $task && $task['priority'] == 'Low' ? 'selected' : ''; ?>>Low</option>
                            <option value="Medium" <?php echo !$task || $task['priority'] == 'Medium' ? 'selected' : ''; ?>>Medium</option>
                            <option value="High" <?php echo $task && $task['priority'] == 'High' ? 'selected' : ''; ?>>High</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="notes">Notes</label>
                        <textarea id="notes" name="notes"><?php echo $task ? htmlspecialchars($task['notes']) : ''; ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="anomalies_detected">Anomalies Detected</label>
                        <textarea id="anomalies_detected" name="anomalies_detected" placeholder="Describe any anomalies detected for this task (you can create a separate PDCA for each)"><?php echo $task ? htmlspecialchars($task['anomalies_detected']) : ''; ?></textarea>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary"><?php echo $taskId ? 'Update Task' : 'Create Task'; ?></button>
                        <a href="index.php" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </main>
    </div>
</body>
</html>
