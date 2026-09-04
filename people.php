<?php
session_start();
require_once 'config/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] == 'add') {
        $name = isset($_POST['name']) ? trim($_POST['name']) : '';
        $email = isset($_POST['email']) ? trim($_POST['email']) : '';

        if (empty($name)) {
            $error = 'Name is required';
        } else {
            $query = "INSERT INTO people (name, email) VALUES (?, ?)";
            $stmt = $conn->prepare($query);
            $stmt->bind_param('ss', $name, $email);

            if ($stmt->execute()) {
                $success = 'Person added successfully!';
            } else {
                $error = 'Error adding person: ' . $stmt->error;
            }
            $stmt->close();
        }
    } elseif ($_POST['action'] == 'delete') {
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

        if ($id > 0) {
            $query = "DELETE FROM people WHERE id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param('i', $id);

            if ($stmt->execute()) {
                $success = 'Person deleted successfully!';
            } else {
                $error = 'Error deleting person: ' . $stmt->error;
            }
            $stmt->close();
        }
    }
}

// Get all people
$query = "SELECT p.*, COUNT(t.id) as task_count FROM people p 
          LEFT JOIN tasks t ON p.id = t.assigned_to 
          GROUP BY p.id 
          ORDER BY p.name";
$result = $conn->query($query);
$people = [];
while ($row = $result->fetch_assoc()) {
    $people[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>People Management</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>👥 People Management</h1>
            <nav>
                <a href="index.php">Calendar</a>
                <a href="tasks.php">All Tasks</a>
                <a href="people.php">People</a>
                <a href="logout.php">Logout</a>
            </nav>
        </header>

        <main style="padding: 30px;">
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>

            <div style="background: white; padding: 20px; border-radius: 8px; margin-bottom: 30px;">
                <h3>Add New Person</h3>
                <form method="POST" action="" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-top: 15px;">
                    <div class="form-group" style="margin-bottom: 0;">
                        <input type="text" name="name" placeholder="Name" required>
                    </div>

                    <div class="form-group" style="margin-bottom: 0;">
                        <input type="email" name="email" placeholder="Email">
                    </div>

                    <div class="form-group" style="margin-bottom: 0;">
                        <input type="hidden" name="action" value="add">
                        <button type="submit" class="btn btn-primary">Add Person</button>
                    </div>
                </form>
            </div>

            <?php if (count($people) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Assigned Tasks</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($people as $person): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($person['name']); ?></td>
                                <td><?php echo $person['email'] ? htmlspecialchars($person['email']) : '<em>No email</em>'; ?></td>
                                <td><?php echo $person['task_count']; ?></td>
                                <td>
                                    <form method="POST" action="" style="display: inline;">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo $person['id']; ?>">
                                        <button type="submit" class="btn-sm btn-danger" onclick="return confirm('Delete this person?')">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div style="text-align: center; padding: 40px; background: #f9f9f9; border-radius: 8px;">
                    <p>No people added yet. Add one to get started!</p>
                </div>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>