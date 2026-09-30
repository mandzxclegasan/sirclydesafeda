<?php
session_start();
 
// Security Guard: Check for wristband AND admin status
if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'admin') {
    die("Access Denied. You must be an administrator.");
}
 
// Existing database connection
require_once 'db.php';
 
$message = "";
 
// --- CREATE & UPDATE ACTIONS ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Create User
    if (isset($_POST['action']) && $_POST['action'] === 'create') {
        $username = trim($_POST['username']);
        $password = $_POST['password'];
        $role     = $_POST['role'];
 
        if (!empty($username) && !empty($password)) {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
            if ($stmt->execute([$username, $hashedPassword, $role])) {
                $message = "User created successfully.";
            }
        } else {
            $message = "Username and password cannot be empty.";
        }
    }
 
    // 2. Update User Role
    if (isset($_POST['action']) && $_POST['action'] === 'update') {
        $id   = intval($_POST['id']);
        $role = $_POST['role'];
 
        $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
        if ($stmt->execute([$role, $id])) {
            $message = "User updated successfully.";
        }
    }
}
 
// --- DELETE ACTION ---
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    
    // Prevent admin from deleting themselves accidentally
    if ($delete_id === $_SESSION['user_id']) {
        $message = "You cannot delete your own account.";
    } else {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        if ($stmt->execute([$delete_id])) {
            $message = "User deleted successfully.";
        }
    }
}
 
// --- READ ACTION ---
// Fetch all users
$stmt = $pdo->query("SELECT id, username, role FROM users ORDER BY id DESC");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
 
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
 
    <header class="topbar">
        <div class="topbar__inner">
            <span class="brand">
                <span class="brand__mark" aria-hidden="true"></span>
                Task Manager
            </span>
            <nav class="nav">
                <a class="nav__link" href="logout.php">Log Out</a>
            </nav>
        </div>
    </header>
 
    <main class="container">
 
        <div class="page-header">
            <h1>Welcome to the Admin Area, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h1>
        </div>
 
        <?php if (!empty($message)): ?>
            <p class="alert" role="status"><?php echo htmlspecialchars($message); ?></p>
        <?php endif; ?>
 
        <!-- CREATE FORM -->
        <section class="card">
            <h2 class="card__title">Create New User</h2>
            <form action="admin.php" method="POST" class="form-row">
                <input type="hidden" name="action" value="create">
 
                <label class="field form-row__field">
                    <span class="field__label">Username</span>
                    <input class="input" type="text" name="username" required>
                </label>
 
                <label class="field form-row__field">
                    <span class="field__label">Password</span>
                    <input class="input" type="password" name="password" required>
                </label>
 
                <label class="field form-row__field form-row__field--narrow">
                    <span class="field__label">Role</span>
                    <select class="select" name="role">
                        <option value="user">User</option>
                        <option value="admin">Admin</option>
                    </select>
                </label>
 
                <button class="btn" type="submit">Add User</button>
            </form>
        </section>
 
        <!-- READ, UPDATE, DELETE TABLE -->
        <section class="card">
            <h2 class="card__title">All Users</h2>
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th class="table__narrow">ID</th>
                            <th>Username</th>
                            <th>Role</th>
                            <th class="table__narrow">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($user['id']); ?></td>
                                <td><?php echo htmlspecialchars($user['username']); ?></td>
                                <td>
                                    <!-- UPDATE FORM (Inline) -->
                                    <form action="admin.php" method="POST">
                                        <input type="hidden" name="action" value="update">
                                        <input type="hidden" name="id" value="<?php echo $user['id']; ?>">
                                        <select name="role"
                                                class="select select--inline <?php echo $user['role'] === 'admin' ? 'select--admin' : ''; ?>"
                                                aria-label="Role for <?php echo htmlspecialchars($user['username']); ?>"
                                                onchange="this.form.submit()">
                                            <option value="user" <?php echo $user['role'] === 'user' ? 'selected' : ''; ?>>User</option>
                                            <option value="admin" <?php echo $user['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                                        </select>
                                    </form>
                                </td>
                                <td>
                                    <!-- DELETE LINK -->
                                    <a class="link-danger"
                                       href="admin.php?delete_id=<?php echo $user['id']; ?>"
                                       onclick="return confirm('Are you sure you want to delete this user?');">
                                        Delete
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
 
    </main>
 
</body>
</html>