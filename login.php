<?php
session_start(); // Must be the very first line!
require 'db.php';

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';

    // 1. Fetch user safely using PDO
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :username");
    $stmt->execute(['username' => $username]);
    $user = $stmt->fetch();

    // 2. Verify plain-text password directly
    // CHANGED: Removed password_verify() and replaced with raw string comparison (===)
    if ($user && $password === $user['password']) {
        // Regenerate session ID to prevent session fixation attacks
        session_regenerate_id(true);

        $_SESSION['logged_in'] = true;
        $_SESSION['user_id'] = $user['id']; // Essential for your user tasks workspace!
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role']; 

        // 3. The Traffic Cop (Redirection)
        if ($user['role'] === 'admin') {
            header("Location: admin.php");
            exit();
        } else {
            header("Location: user.php"); 
            exit();
        }
    } else {
        $error = "Invalid username or password!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="auth-page">

    <main class="auth">
        <aside class="auth__brand">
            <span class="brand__mark brand__mark--light" aria-hidden="true"></span>
            <h1 class="auth__title">Task Manager</h1>
            <p class="auth__tagline">Keep track of what needs doing.</p>
        </aside>

        <section class="auth__panel">
            <form method="POST">
                <h2>System Login</h2>

                <?php if (!empty($error)): ?>
                    <p class="alert alert--error" role="alert"><?php echo htmlspecialchars($error); ?></p>
                <?php endif; ?>

                <label class="field">
                    <span class="field__label">Username</span>
                    <input class="input" type="text" name="username" autocomplete="username" required>
                </label>

                <label class="field">
                    <span class="field__label">Password</span>
                    <input class="input" type="password" name="password" autocomplete="current-password" required>
                </label>

                <button class="btn btn--block" type="submit">Log In</button>

                <p class="form__foot">
                    Don't have an account?
                    <a href="register.php">Register</a>
                </p>
            </form>
        </section>
    </main>

</body>
</html>
