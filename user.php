<?php
session_start();

// Security Guard: Ensure the user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || !isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Ensure a CSRF token exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

require_once 'db.php';
$message = '';
$current_user_id = intval($_SESSION['user_id']);

// --- CREATE & UPDATE ACTIONS ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        header('HTTP/1.1 403 Forbidden');
        die("CSRF token validation failed.");
    }

    // 1. CREATE TASK
    if (isset($_POST['action']) && $_POST['action'] === 'create') {
        $title = isset($_POST['title']) ? trim($_POST['title']) : '';
        $description = isset($_POST['description']) ? trim($_POST['description']) : '';

        if (!empty($title)) {
            // Hardcode the current user's ID into the query for ownership isolation
            $stmt = $pdo->prepare("INSERT INTO tasks (user_id, title, description) VALUES (?, ?, ?)");
            if ($stmt->execute([$current_user_id, $title, $description])) {
                $message = "Task added successfully.";
            }
        } else {
            $message = "Task title cannot be empty.";
        }
    }

    // 2. TOGGLE STATUS (UPDATE)
    if (isset($_POST['action']) && $_POST['action'] === 'toggle_status') {
        $task_id = intval($_POST['task_id']);
        $new_status = ($_POST['current_status'] === 'pending') ? 'completed' : 'pending';

        // Critical: The WHERE clause checks BOTH task ID and user ID so users cannot update someone else's task
        $stmt = $pdo->prepare("UPDATE tasks SET status = ? WHERE id = ? AND user_id = ?");
        if ($stmt->execute([$new_status, $task_id, $current_user_id])) {
            $message = "Task updated.";
        }
    }
}

// --- DELETE ACTION ---
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['task_id'])) {
    
    if (!isset($_GET['token']) || !hash_equals($_SESSION['csrf_token'], $_GET['token'])) {
        header('HTTP/1.1 403 Forbidden');
        die("CSRF token validation failed.");
    }

    $task_id = intval($_GET['task_id']);

    // Critical: Ownership validation via SQL WHERE scope execution
    $stmt = $pdo->prepare("DELETE FROM tasks WHERE id = ? AND user_id = ?");
    if ($stmt->execute([$task_id, $current_user_id])) {
        $message = "Task deleted successfully.";
    }
}

// --- READ ACTION ---
// Isolate records so the user only pulls rows belonging to their specific account ID
$stmt = $pdo->prepare("SELECT id, title, description, status FROM tasks WHERE user_id = ? ORDER BY id DESC");
$stmt->execute([$current_user_id]);
$tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>My Tasks</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <header class="topbar">
        <div class="topbar__inner">
            <span class="brand">
                <span class="brand__mark" aria-hidden="true"></span>
                Task Manager
            </span>

            <!-- Navigation based on role architecture -->
            <nav class="nav">
                <a class="nav__link is-active" href="user.php" aria-current="page">My Tasks</a>
                <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                    <a class="nav__link nav__link--accent" href="admin.php">Admin Dashboard</a>
                <?php endif; ?>
                <a class="nav__link" href="logout.php">Log Out</a>
            </nav>
        </div>
    </header>

    <main class="container">

        <div class="page-header">
            <h1>Welcome, <?php echo htmlspecialchars($_SESSION['username'] ?? 'User', ENT_QUOTES, 'UTF-8'); ?>!</h1>
        </div>

        <?php if (!empty($message)): ?>
            <p class="alert" role="status"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></p>
        <?php endif; ?>

        <!-- CREATE FORM -->
        <section class="card">
            <h2 class="card__title">Add a New Task</h2>
            <form action="user.php" method="POST" class="form--narrow">
                <input type="hidden" name="action" value="create">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">

                <label class="field">
                    <span class="field__label">Task Title</span>
                    <input class="input" type="text" name="title" required>
                </label>

                <label class="field">
                    <span class="field__label">Description <span class="field__hint">(optional)</span></span>
                    <textarea class="textarea" name="description" rows="3"></textarea>
                </label>

                <button class="btn" type="submit">Add Task</button>
            </form>
        </section>

        <!-- READ & INTERACT TABLE -->
        <section class="card">
            <h2 class="card__title">My Active Workspace</h2>
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th class="table__narrow">Status</th>
                            <th>Task Details</th>
                            <th class="table__narrow">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($tasks)): ?>
                            <tr>
                                <td colspan="3" class="table__empty">You haven't added any tasks yet!</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($tasks as $task): ?>
                                <tr class="task-row <?php echo $task['status'] === 'completed' ? 'is-done' : ''; ?>">
                                    <td class="table__center">
                                        <!-- UPDATE STATUS FORM -->
                                        <form action="user.php" method="POST">
                                            <input type="hidden" name="action" value="toggle_status">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
                                            <input type="hidden" name="task_id" value="<?php echo $task['id']; ?>">
                                            <input type="hidden" name="current_status" value="<?php echo $task['status']; ?>">

                                            <button type="submit"
                                                    class="status-btn <?php echo $task['status'] === 'completed' ? 'status-btn--done' : ''; ?>">
                                                <?php echo $task['status'] === 'completed' ? '✅ Completed' : '⏳ Pending'; ?>
                                            </button>
                                        </form>
                                    </td>
                                    <td>
                                        <strong class="task__title">
                                            <?php echo htmlspecialchars($task['title'], ENT_QUOTES, 'UTF-8'); ?>
                                        </strong>
                                        <?php if (!empty($task['description'])): ?>
                                            <p class="task__desc">
                                                <?php echo nl2br(htmlspecialchars($task['description'], ENT_QUOTES, 'UTF-8')); ?>
                                            </p>
                                        <?php endif; ?>
                                    </td>
                                    <td class="table__center">
                                        <!-- SECURE DELETE LINK -->
                                        <?php 
                                        $deleteUrl = "user.php?action=delete&task_id=" . urlencode((string)$task['id']) . "&token=" . urlencode($_SESSION['csrf_token']);
                                        ?>
                                        <a class="link-danger"
                                           href="<?php echo htmlspecialchars($deleteUrl, ENT_QUOTES, 'UTF-8'); ?>"
                                           onclick="return confirm('Are you sure you want to delete this task?');">
                                            [X] Remove
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

    </main>

</body>
</html>
