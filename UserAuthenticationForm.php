<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/DatabaseConfiguration.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim(isset($_POST['email']) ? $_POST['email'] : '');
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    if ($email === '' || $password === '') {
        $error = 'Please enter email and password.';
    } else {
        try {
            $pdo = db();
            $stmt = $pdo->prepare('SELECT id, name, email, password_hash, currency, dark_mode FROM users WHERE email = ? LIMIT 1');
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            if ($user && password_verify($password, $user['password_hash'])) {
                $_SESSION['user_id'] = (int)$user['id'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_currency'] = $user['currency'] ? $user['currency'] : 'USD';
                $_SESSION['user_dark_mode'] = (bool)$user['dark_mode'];
                header('Location: DashboardOverview.php');
                exit;
            } else {
                $error = 'Invalid email or password.';
            }
        } catch (Exception $e) {
            $error = 'Login failed. Please try again.';
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
        <h2>Welcome Back!</h2>
        <p class="text-muted mb-2">Manage Your Money, Master Your Budget.</p>

        <form action="UserAuthenticationForm.php" method="POST">
            <div class="form-group" style="text-align:left;">
                <label>Email Address</label>
                <input type="email" name="email" placeholder="you@example.com" required>
            </div>
            
            <div class="form-group" style="text-align:left;">
                <label>Password</label>
                <input type="password" name="password" placeholder="Enter your password" required>
                <div style="text-align: right; margin-top: 5px;">
                    <a href="#" style="font-size: 0.85rem; color: #10b981; text-decoration: none;">Forgot Password?</a>
                </div>
            </div>

            <?php if (!empty($error)): ?>
                <div class="text-red" style="margin-bottom:10px; font-size:0.9rem;"><?php echo e($error); ?></div>
            <?php endif; ?>
            <button type="submit" class="btn btn-primary">Login</button>
        </form>

        <div class="auth-links">
            Don't have an account? <a href="UserRegistrationForm.php">Register</a>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
