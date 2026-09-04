<?php
session_start();
require_once 'config/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Get filter options
$filter_status = isset($_GET['status']) ? $_GET['status'] : '';
$filter_person = isset($_GET['person']) ? (int)$_GET['person'] : '';
$filter_priority = isset($_GET['priority']) ? $_GET['priority'] : '';
$filter_has_anomalies = isset($_GET['has_anomalies']) ? (int)$_GET['has_anomalies'] : '';

// Build query
$query = "SELECT t.*, p.name as person_name FROM tasks t 
          LEFT JOIN people p ON t.assigned_to = p.id 
          WHERE 1=1";
$params = [];
$types = '';

if (!empty($filter_status)) {
    $query .= " AND t.status = ?";
    $params[] = $filter_status;
    $types .= 's';
}

if (!empty($filter_person)) {
    $query .= " AND t.assigned_to = ?";
    $params[] = $filter_person;
    $types .= 'i';
}

if (!empty($filter_priority)) {
    $query .= " AND t.priority = ?";
    $params[] = $filter_priority;
    $types .= 's';
}

if (!empty($filter_has_anomalies)) {
    if ($filter_has_anomalies == 1) {
        $query .= " AND t.anomalies_detected IS NOT NULL AND t.anomalies_detected != ''";
    } else {
        $query .= " AND (t.anomalies_detected IS NULL OR t.anomalies_detected = '')";
    }
}

$query .= " ORDER BY t.task_date ASC";

if (empty($params)) {
    $result = $conn->query($query);
} else {
    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
}

$tasks = [];
while ($row = $result->fetch_assoc()) {
    $tasks[] = $row;
}

if (!empty($params)) {
    $stmt->close();
}

// Get people for filter dropdown
$peopleQuery = "SELECT id, name FROM people ORDER BY name";
$peopleResult = $conn->query($peopleQuery);
$people = [];
while ($row = $peopleResult->fetch_assoc()) {
    $people[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Tasks</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>📋 All Tasks</h1>
            <nav>
                <a href="index.php">Calendar</a>
                <a href="tasks.php">All Tasks</a>
                <a href="anomalies.php">Anomalies</a>
                <a href="people.php">People</a>
                <a href="logout.php">Logout</a>
            </nav>
        </header>

        <main style="padding: 30px;">
            <div style="margin-bottom: 20px;">
                <a href="task-form.php" class="btn btn-primary">➕ Add New Task</a>
            </div>

            <div style="background: white; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
                <h3>Filters</h3>
                <form method="GET" action="" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-top: 15px;">
                    <div class="form-group" style="margin-bottom: 0;">
                        <select name="status">
                            <option value="">-- All Status --</option>
                            <option value="Not Yet Started" <?php echo $filter_status == 'Not Yet Started' ? 'selected' : ''; ?>>Not Yet Started</option>
                            <option value="Ongoing" <?php echo $filter_status == 'Ongoing' ? 'selected' : ''; ?>>Ongoing</option>
                            <option value="Done" <?php echo $filter_status == 'Done' ? 'selected' : ''; ?>>Done</option>
                            <option value="Overdue" <?php echo $filter_status == 'Overdue' ? 'selected' : ''; ?>>Overdue</option>
                        </select>
                    </div>

                    <div class="form-group" style="margin-bottom: 0;">
                        <select name="person">
                            <option value="">-- All People --</option>
                            <?php foreach ($people as $person): ?>
                                <option value="<?php echo $person['id']; ?>" <?php echo $filter_person == $person['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($person['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group" style="margin-bottom: 0;">
                        <select name="priority">
                            <option value="">-- All Priority --</option>
                            <option value="Low" <?php echo $filter_priority == 'Low' ? 'selected' : ''; ?>>Low</option>
                            <option value="Medium" <?php echo $filter_priority == 'Medium' ? 'selected' : ''; ?>>Medium</option>
                            <option value="High" <?php echo $filter_priority == 'High' ? 'selected' : ''; ?>>High</option>
                        </select>
                    </div>

                    <div class="form-group" style="margin-bottom: 0;">
                        <select name="has_anomalies">
                            <option value="">-- All --</option>
                            <option value="1" <?php echo $filter_has_anomalies == 1 ? 'selected' : ''; ?>>With Anomalies</option>
                            <option value="0" <?php echo $filter_has_anomalies == 0 ? 'selected' : ''; ?>>Without Anomalies</option>
                        </select>
                    </div>

                    <div class="form-group" style="margin-bottom: 0;">
                        <button type="submit" class="btn btn-primary">Filter</button>
                        <a href="tasks.php" class="btn btn-secondary">Clear</a>
                    </div>
                </form>
            </div>

            <?php if (count($tasks) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Description</th>
                            <th>Date</th>
                            <th>Assigned To</th>
                            <th>Status</th>
                            <th>Priority</th>
                            <th>Anomalies</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tasks as $task): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($task['description']); ?></td>
                                <td><?php echo date('M d, Y', strtotime($task['task_date'])); ?></td>
                                <td><?php echo $task['person_name'] ? htmlspecialchars($task['person_name']) : '<em>Unassigned</em>'; ?></td>
                                <td>
                                    <?php
                                    $statusClass = 'badge-secondary';
                                    if ($task['status'] == 'Done') $statusClass = 'badge-success';
                                    elseif ($task['status'] == 'Ongoing') $statusClass = 'badge-warning';
                                    elseif ($task['status'] == 'Overdue') $statusClass = 'badge-danger';
                                    ?>
                                    <span class="badge <?php echo $statusClass; ?>"><?php echo htmlspecialchars($task['status']); ?></span>
                                </td>
                                <td><?php echo htmlspecialchars($task['priority']); ?></td>
                                <td>
                                    <?php if ($task['anomalies_detected']): ?>
                                        <span class="badge badge-danger">🔴 Yes</span>
                                    <?php else: ?>
                                        <span class="badge badge-success">✓ No</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="task-form.php?id=<?php echo $task['id']; ?>" class="btn-sm">Edit</a>
                                    <a href="delete-task.php?id=<?php echo $task['id']; ?>" class="btn-sm btn-danger" onclick="return confirm('Delete this task?')">Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div style="text-align: center; padding: 40px; background: #f9f9f9; border-radius: 8px;">
                    <p>No tasks found</p>
                </div>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>
