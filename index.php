<?php
session_start();
require_once 'config/db.php';

// Redirect to login if not authenticated
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Auto-update overdue tasks (only if still "Not Yet Started" or "Ongoing")
$updateQuery = "UPDATE tasks SET status = 'Overdue' 
                WHERE task_date < CURDATE() 
                AND status IN ('Not Yet Started', 'Ongoing')";
$conn->query($updateQuery);

// Get current month and year
$month = isset($_GET['month']) ? (int)$_GET['month'] : date('m');
$year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');

// Validate month and year
$month = max(1, min(12, $month));
$currentDate = new DateTime("$year-$month-01");

// Get all tasks for the current month
$startDate = $currentDate->format('Y-m-01');
$endDate = $currentDate->format('Y-m-t');

$query = "SELECT t.*, p.name as person_name FROM tasks t 
          LEFT JOIN people p ON t.assigned_to = p.id 
          WHERE t.task_date BETWEEN ? AND ? 
          ORDER BY t.task_date ASC";
$stmt = $conn->prepare($query);
$stmt->bind_param('ss', $startDate, $endDate);
$stmt->execute();
$result = $stmt->get_result();

$tasks = [];
while ($row = $result->fetch_assoc()) {
    $date = $row['task_date'];
    if (!isset($tasks[$date])) {
        $tasks[$date] = [];
    }
    $tasks[$date][] = $row;
}
$stmt->close();

// Get list of people for assignment
$peopleQuery = "SELECT id, name FROM people ORDER BY name";
$peopleResult = $conn->query($peopleQuery);
$people = [];
while ($row = $peopleResult->fetch_assoc()) {
    $people[$row['id']] = $row['name'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calendar & To-Do List</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>📅 Calendar & Task Manager</h1>
            <nav>
                <a href="index.php">Calendar</a>
                <a href="tasks.php">All Tasks</a>
                <a href="people.php">People</a>
                <a href="logout.php">Logout</a>
            </nav>
        </header>

        <main>
            <div class="calendar-container">
                <div class="calendar-header">
                    <a href="?month=<?php echo $month-1; ?>&year=<?php echo $month == 1 ? $year-1 : $year; ?>" class="nav-btn">← Previous</a>
                    <h2><?php echo $currentDate->format('F Y'); ?></h2>
                    <a href="?month=<?php echo $month+1; ?>&year=<?php echo $month == 12 ? $year+1 : $year; ?>" class="nav-btn">Next →</a>
                </div>

                <div class="calendar">
                    <div class="calendar-weekdays">
                        <div>Sun</div>
                        <div>Mon</div>
                        <div>Tue</div>
                        <div>Wed</div>
                        <div>Thu</div>
                        <div>Fri</div>
                        <div>Sat</div>
                    </div>

                    <div class="calendar-days">
                        <?php
                        $firstDay = (int)$currentDate->format('w');
                        $daysInMonth = (int)$currentDate->format('t');

                        // Empty cells for days before month starts
                        for ($i = 0; $i < $firstDay; $i++) {
                            echo '<div class="calendar-day empty"></div>';
                        }

                        // Days of the month
                        for ($day = 1; $day <= $daysInMonth; $day++) {
                            $dateStr = sprintf('%04d-%02d-%02d', $year, $month, $day);
                            $isToday = $dateStr === date('Y-m-d');
                            $hasTasks = isset($tasks[$dateStr]);

                            echo '<div class="calendar-day ' . ($isToday ? 'today' : '') . ' ' . ($hasTasks ? 'has-tasks' : '') . '">';
                            echo '<div class="day-number">' . $day . '</div>';

                            if ($hasTasks) {
                                echo '<div class="day-tasks">';
                                foreach ($tasks[$dateStr] as $task) {
                                    $statusClass = 'status-' . strtolower(str_replace(' ', '-', $task['status']));
                                    echo '<div class="task-badge ' . $statusClass . '" title="' . htmlspecialchars($task['description']) . '">';
                                    echo htmlspecialchars(substr($task['description'], 0, 15)) . '...';
                                    echo '</div>';
                                }
                                echo '</div>';
                            }

                            echo '<div class="day-actions">';
                            echo '<a href="task-form.php?date=' . $dateStr . '" class="btn-sm btn-add">+</a>';
                            echo '</div>';
                            echo '</div>';
                        }

                        // Empty cells for days after month ends
                        $totalCells = $firstDay + $daysInMonth;
                        $emptyCells = (7 - ($totalCells % 7)) % 7;
                        for ($i = 0; $i < $emptyCells; $i++) {
                            echo '<div class="calendar-day empty"></div>';
                        }
                        ?>
                    </div>
                </div>
            </div>

            <div class="sidebar">
                <h3>Quick Stats</h3>
                <div class="stats">
                    <?php
                    $statsQuery = "SELECT status, COUNT(*) as count FROM tasks 
                                   WHERE task_date BETWEEN ? AND ? 
                                   GROUP BY status";
                    $stmt = $conn->prepare($statsQuery);
                    $stmt->bind_param('ss', $startDate, $endDate);
                    $stmt->execute();
                    $statsResult = $stmt->get_result();

                    while ($row = $statsResult->fetch_assoc()) {
                        echo '<div class="stat-item">';
                        echo '<span class="stat-label">' . htmlspecialchars($row['status']) . ':</span>';
                        echo '<span class="stat-value">' . $row['count'] . '</span>';
                        echo '</div>';
                    }
                    $stmt->close();
                    ?>
                </div>

                <h3>Today's Tasks</h3>
                <div class="tasks-list">
                    <?php
                    $todayQuery = "SELECT t.*, p.name as person_name FROM tasks t 
                                   LEFT JOIN people p ON t.assigned_to = p.id 
                                   WHERE DATE(t.task_date) = CURDATE() 
                                   ORDER BY t.status DESC";
                    $todayResult = $conn->query($todayQuery);

                    if ($todayResult->num_rows > 0) {
                        while ($task = $todayResult->fetch_assoc()) {
                            $statusClass = 'status-' . strtolower(str_replace(' ', '-', $task['status']));
                            echo '<div class="task-item ' . $statusClass . '">';
                            echo '<strong>' . htmlspecialchars($task['description']) . '</strong><br>';
                            echo '<small>' . ($task['person_name'] ? 'Assigned to: ' . htmlspecialchars($task['person_name']) : 'Unassigned') . '</small><br>';
                            echo '<small>Status: ' . htmlspecialchars($task['status']) . '</small><br>';
                            echo '<a href="task-form.php?id=' . $task['id'] . '" class="btn-sm">Edit</a> ';
                            echo '<a href="delete-task.php?id=' . $task['id'] . '" class="btn-sm btn-danger" onclick="return confirm(\'Delete this task?\')">Delete</a>';
                            echo '</div>';
                        }
                    } else {
                        echo '<p>No tasks for today</p>';
                    }
                    ?>
                </div>
            </div>
        </main>
    </div>
</body>
</html>