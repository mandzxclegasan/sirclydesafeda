<?php
session_start();
require 'db.php';

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    // Check if username already exists
    $stmt = $pdo->prepare("SELECT * FROM Users WHERE username = :username");
    $stmt->execute(['username' => $username]);
    $user = $stmt->fetch();

    if ($user) {
        $error = "Username already exists!";
    } else {
        // Store password as plain text
        $stmt = $pdo->prepare(
            "INSERT INTO Users (username, password, role) 
             VALUES (:username, :password, :role)"
        );

        $stmt->execute([
            'username' => $username,
            'password' => $password,
            'role' => 'user'
        ]);

        $success = "Registration successful! You can now log in.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Register</title>
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
                <h2>System Registration</h2>

                <?php if ($error): ?>
                    <p class="alert alert--error" role="alert">
                        <?php echo htmlspecialchars($error); ?>
                    </p>
                <?php endif; ?>

                <?php if ($success): ?>
                    <p class="alert alert--success" role="status">
                        <?php echo htmlspecialchars($success); ?>
                    </p>
                <?php endif; ?>

                <label class="field">
                    <span class="field__label">Username</span>
                    <input class="input" type="text" name="username" autocomplete="username" required>
                </label>

                <label class="field">
                    <span class="field__label">Password</span>
                    <input class="input" type="password" name="password" autocomplete="new-password" required>
                </label>

                <button class="btn btn--block" type="submit">Register</button>

                <p class="form__foot">
                    Already have an account?
                    <a href="login.php">Log In</a>
                </p>
            </form>
        </section>
    </main>

</body>
</html>
