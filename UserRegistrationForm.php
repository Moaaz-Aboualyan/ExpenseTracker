<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/DatabaseConfiguration.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim(isset($_POST['fullname']) ? $_POST['fullname'] : '');
    $email = trim(isset($_POST['email']) ? $_POST['email'] : '');
    $password = isset($_POST['password']) ? $_POST['password'] : '';

    if ($name === '' || $email === '' || $password === '') {
        $error = 'All fields are required.';
    } else {
        try {
            $pdo = db();
            // Check if email exists
            $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $error = 'Email is already registered.';
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare('INSERT INTO users(name, email, password_hash) VALUES(?,?,?)');
                $stmt->execute([$name, $email, $hash]);
                $user_id = $pdo->lastInsertId();

                // Auto-login after registration
                $_SESSION['user_id'] = (int)$user_id;
                $_SESSION['user_email'] = $email;
                $_SESSION['user_name'] = $name;
                header('Location: DashboardOverview.php');
                exit;
            }
        } catch (Exception $e) {
            $error = 'Registration failed. Please try again.';
        }
    }
}

include 'includes/header.php';
?>

<div class="auth-container">
    <div class="auth-card">
        <div class="logo" style="justify-content: center; margin-bottom: 20px;">
            <i class="fas fa-chart-pie text-green"></i> ExpenseTracker
        </div>
        <h2>Create Account</h2>
        <p class="text-muted mb-2">Start your financial journey today.</p>

        <form action="UserRegistrationForm.php" method="POST">
            <div class="form-group" style="text-align:left;">
                <label>Full Name</label>
                <input type="text" name="fullname" placeholder="Alex Doe" required>
            </div>

            <div class="form-group" style="text-align:left;">
                <label>Email Address</label>
                <input type="email" name="email" placeholder="you@example.com" required>
            </div>
            
            <div class="form-group" style="text-align:left;">
                <label>Password</label>
                <input type="password" name="password" placeholder="Create a password" required>
            </div>

            <?php if (!empty($error)): ?>
                <div class="text-red" style="margin-bottom:10px; font-size:0.9rem;"><?php echo e($error); ?></div>
            <?php endif; ?>
            <button type="submit" class="btn btn-primary">Sign Up</button>
        </form>

        <div class="auth-links">
            Already have an account? <a href="UserAuthenticationForm.php">Login</a>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
