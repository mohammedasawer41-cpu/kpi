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

// Build query to get only tasks with anomalies
$query = "SELECT t.*, p.name as person_name FROM tasks t 
          LEFT JOIN people p ON t.assigned_to = p.id 
          WHERE t.anomalies_detected IS NOT NULL AND t.anomalies_detected != ''";
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

$query .= " ORDER BY t.task_date DESC";

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

// Get statistics for anomalies
$statsQuery = "SELECT 
                COUNT(*) as total_anomalies,
                SUM(CASE WHEN t.status = 'Done' THEN 1 ELSE 0 END) as resolved,
                SUM(CASE WHEN t.status = 'Overdue' THEN 1 ELSE 0 END) as overdue,
                SUM(CASE WHEN t.status = 'Ongoing' THEN 1 ELSE 0 END) as ongoing,
                SUM(CASE WHEN t.status = 'Not Yet Started' THEN 1 ELSE 0 END) as not_started
               FROM tasks t 
               WHERE t.anomalies_detected IS NOT NULL AND t.anomalies_detected != ''";
$statsResult = $conn->query($statsQuery);
$stats = $statsResult->fetch_assoc();

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
    <title>Anomalies</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .anomalies-container {
            padding: 30px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            border-left: 4px solid #dc3545;
        }
        
        .stat-card.resolved {
            border-left-color: #28a745;
        }
        
        .stat-card.ongoing {
            border-left-color: #ffc107;
        }
        
        .stat-card.overdue {
            border-left-color: #dc3545;
        }
        
        .stat-card h4 {
            margin: 0 0 10px 0;
            color: #666;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .stat-card .number {
            font-size: 32px;
            font-weight: bold;
            color: #333;
        }
        
        .anomaly-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #dc3545;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .anomaly-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 15px;
        }
        
        .anomaly-title {
            font-size: 18px;
            font-weight: bold;
            color: #333;
            margin: 0 0 5px 0;
        }
        
        .anomaly-meta {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            font-size: 14px;
            color: #666;
        }
        
        .anomaly-meta span {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .anomaly-description {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 15px;
            border-left: 3px solid #dc3545;
        }
        
        .anomaly-description strong {
            display: block;
            margin-bottom: 8px;
            color: #333;
        }
        
        .anomaly-actions {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }
        
        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .badge-danger {
            background: #f8d7da;
            color: #721c24;
        }
        
        .badge-warning {
            background: #fff3cd;
            color: #856404;
        }
        
        .badge-success {
            background: #d4edda;
            color: #155724;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background: #f9f9f9;
            border-radius: 8px;
        }
        
        .empty-state h3 {
            color: #999;
            margin-bottom: 10px;
        }
        
        .empty-state p {
            color: #ccc;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>⚠️ Anomalies</h1>
            <nav>
                <a href="index.php">Calendar</a>
                <a href="tasks.php">All Tasks</a>
                <a href="anomalies.php">Anomalies</a>
                <a href="people.php">People</a>
                <a href="logout.php">Logout</a>
            </nav>
        </header>

        <main class="anomalies-container">
            <!-- Statistics Section -->
            <div class="stats-grid">
                <div class="stat-card">
                    <h4>Total Anomalies</h4>
                    <div class="number"><?php echo $stats['total_anomalies'] ?? 0; ?></div>
                </div>
                <div class="stat-card resolved">
                    <h4>Resolved</h4>
                    <div class="number"><?php echo $stats['resolved'] ?? 0; ?></div>
                </div>
                <div class="stat-card ongoing">
                    <h4>Ongoing</h4>
                    <div class="number"><?php echo $stats['ongoing'] ?? 0; ?></div>
                </div>
                <div class="stat-card overdue">
                    <h4>Overdue</h4>
                    <div class="number"><?php echo $stats['overdue'] ?? 0; ?></div>
                </div>
            </div>

            <!-- Filters Section -->
            <div style="background: white; padding: 20px; border-radius: 8px; margin-bottom: 30px;">
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
                        <button type="submit" class="btn btn-primary">Filter</button>
                        <a href="anomalies.php" class="btn btn-secondary">Clear</a>
                    </div>
                </form>
            </div>

            <!-- Anomalies List -->
            <?php if (count($tasks) > 0): ?>
                <div>
                    <?php foreach ($tasks as $task): ?>
                        <div class="anomaly-card">
                            <div class="anomaly-header">
                                <div>
                                    <p class="anomaly-title"><?php echo htmlspecialchars($task['description']); ?></p>
                                    <div class="anomaly-meta">
                                        <span>📅 <?php echo date('M d, Y', strtotime($task['task_date'])); ?></span>
                                        <span>👤 <?php echo $task['person_name'] ? htmlspecialchars($task['person_name']) : '<em>Unassigned</em>'; ?></span>
                                        <span>
                                            <?php
                                            $statusClass = 'badge-secondary';
                                            if ($task['status'] == 'Done') $statusClass = 'badge-success';
                                            elseif ($task['status'] == 'Ongoing') $statusClass = 'badge-warning';
                                            elseif ($task['status'] == 'Overdue') $statusClass = 'badge-danger';
                                            ?>
                                            <span class="badge <?php echo $statusClass; ?>"><?php echo htmlspecialchars($task['status']); ?></span>
                                        </span>
                                        <span>
                                            <span class="badge <?php echo $task['priority'] == 'High' ? 'badge-danger' : ($task['priority'] == 'Medium' ? 'badge-warning' : 'badge-success'); ?>">
                                                <?php echo htmlspecialchars($task['priority']); ?>
                                            </span>
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div class="anomaly-description">
                                <strong>🔴 Anomaly Details:</strong>
                                <?php echo nl2br(htmlspecialchars($task['anomalies_detected'])); ?>
                            </div>

                            <div class="anomaly-actions">
                                <a href="task-form.php?id=<?php echo $task['id']; ?>" class="btn btn-sm">✏️ Edit Task</a>
                                <a href="delete-task.php?id=<?php echo $task['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this task?')">🗑️ Delete</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <h3>✓ No Anomalies Found</h3>
                    <p>All tasks are running smoothly without any detected anomalies.</p>
                </div>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>
