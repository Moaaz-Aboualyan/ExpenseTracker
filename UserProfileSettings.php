<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/DatabaseConfiguration.php';
require_login();
// Prepare DB and current user data
$pdo = db();
$uid = (int)$_SESSION['user_id'];
$msg = '';
$err = '';

// Load current profile from DB (source of truth)
$user = null;
try {
    $st = $pdo->prepare('SELECT id, name, email, currency, dark_mode FROM users WHERE id = ? LIMIT 1');
    $st->execute(array($uid));
    $user = $st->fetch();
    if (!$user) {
        $user = array('name' => '', 'email' => '', 'currency' => 'USD', 'dark_mode' => 0);
    }
} catch (Exception $e) {
    $user = array('name' => (isset($_SESSION['user_name']) ? $_SESSION['user_name'] : ''), 'email' => (isset($_SESSION['user_email']) ? $_SESSION['user_email'] : ''), 'currency' => 'USD', 'dark_mode' => 0);
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
    $name = trim(isset($_POST['name']) ? $_POST['name'] : '');
    $email = trim(isset($_POST['email']) ? $_POST['email'] : '');

    if ($name === '' || $email === '') {
        $err = 'Name and email are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $err = 'Please enter a valid email address.';
    } else {
        // Ensure email is unique for other users
        $chk = $pdo->prepare('SELECT id FROM users WHERE email = ? AND id <> ? LIMIT 1');
        $chk->execute(array($email, $uid));
        if ($chk->fetch()) {
            $err = 'That email is already in use.';
        } else {
            $up = $pdo->prepare('UPDATE users SET name = ?, email = ? WHERE id = ?');
            $up->execute(array($name, $email, $uid));
            // Refresh session so header shows updated values
            $_SESSION['user_name'] = $name;
            $_SESSION['user_email'] = $email;
            $msg = 'Profile updated.';
            // Reload user row
            $st = $pdo->prepare('SELECT id, name, email FROM users WHERE id = ? LIMIT 1');
            $st->execute(array($uid));
            $user = $st->fetch();
        }
    }
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'change_password') {
    $old_password = isset($_POST['old_password']) ? $_POST['old_password'] : '';
    $new_password = isset($_POST['new_password']) ? $_POST['new_password'] : '';
    $confirm_password = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';
    
    // Get current password hash
    $stmt = $pdo->prepare('SELECT password_hash FROM users WHERE id = ?');
    $stmt->execute([$uid]);
    $row = $stmt->fetch();
    $current_hash = $row['password_hash'];
    
    if ($old_password === '') {
        $err = 'Please enter your current password.';
    } elseif (!password_verify($old_password, $current_hash)) {
        $err = 'Current password is incorrect.';
    } elseif ($new_password === '') {
        $err = 'Please enter a new password.';
    } elseif (strlen($new_password) < 6) {
        $err = 'New password must be at least 6 characters long.';
    } elseif ($new_password !== $confirm_password) {
        $err = 'New password and confirmation do not match.';
    } else {
        $new_hash = password_hash($new_password, PASSWORD_BCRYPT);
        $upd = $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
        $upd->execute([$new_hash, $uid]);
        $msg = 'Password changed successfully.';
        // Clear the form by setting an empty flag
        $_POST['show_password_form'] = '';
    }
}

// Handle currency change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'change_currency') {
    $new_currency = trim(isset($_POST['currency']) ? $_POST['currency'] : '');
    $convert_values = isset($_POST['convert_values']) ? true : false;
    $valid_currencies = array('USD', 'MYR', 'EUR', 'GBP', 'JPY', 'AUD', 'CAD', 'CHF', 'CNY', 'INR', 'SGD');
    
    if (!in_array($new_currency, $valid_currencies)) {
        $err = 'Invalid currency selected.';
    } else {
        $old_currency = $user['currency'];
        
        // If converting values and currencies are different
        if ($convert_values && $old_currency !== $new_currency) {
            // Simple conversion rates (relative to USD as base)
            $rates = array(
                'USD' => 1.00,
                'EUR' => 0.92,
                'GBP' => 0.79,
                'MYR' => 4.72,
                'JPY' => 149.50,
                'AUD' => 1.54,
                'CAD' => 1.36,
                'CHF' => 0.88,
                'CNY' => 7.24,
                'INR' => 83.12,
                'SGD' => 1.34
            );
            
            // Calculate conversion factor
            $from_rate = $rates[$old_currency];
            $to_rate = $rates[$new_currency];
            $conversion_factor = $to_rate / $from_rate;
            
            // Convert all transaction amounts
            $update_transactions = $pdo->prepare('UPDATE transactions SET amount = amount * ? WHERE user_id = ?');
            $update_transactions->execute([$conversion_factor, $uid]);
            
            // Convert all category budgets
            $update_budgets = $pdo->prepare('UPDATE categories SET monthly_budget = monthly_budget * ?, recurring_amount = recurring_amount * ? WHERE user_id = ?');
            $update_budgets->execute([$conversion_factor, $conversion_factor, $uid]);
            
            // Convert quick presets
            $update_presets = $pdo->prepare('UPDATE quick_presets SET amount = amount * ? WHERE user_id = ?');
            $update_presets->execute([$conversion_factor, $uid]);
            
            $msg = 'Currency changed to ' . $new_currency . ' and all values have been converted.';
        } else {
            $msg = 'Currency changed to ' . $new_currency . '.';
        }
        
        // Update user currency
        $upd = $pdo->prepare('UPDATE users SET currency = ? WHERE id = ?');
        $upd->execute([$new_currency, $uid]);
        $_SESSION['user_currency'] = $new_currency;
        
        // Reload user data
        $st = $pdo->prepare('SELECT id, name, email, currency, dark_mode FROM users WHERE id = ? LIMIT 1');
        $st->execute(array($uid));
        $user = $st->fetch();
    }
}

// Handle dark mode toggle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle_dark_mode') {
    $dark_mode = isset($_POST['dark_mode']) ? (int)$_POST['dark_mode'] : 0;
    $upd = $pdo->prepare('UPDATE users SET dark_mode = ? WHERE id = ?');
    $upd->execute([$dark_mode, $uid]);
    $_SESSION['user_dark_mode'] = (bool)$dark_mode;
    exit; // Silent update for header toggle
}

// Handle dark mode change from settings page
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'change_dark_mode') {
    $dark_mode = isset($_POST['dark_mode']) ? 1 : 0;
    $upd = $pdo->prepare('UPDATE users SET dark_mode = ? WHERE id = ?');
    $upd->execute([$dark_mode, $uid]);
    $_SESSION['user_dark_mode'] = (bool)$dark_mode;
    $msg = $dark_mode ? 'Dark mode enabled.' : 'Dark mode disabled.';
    // Reload user data
    $st = $pdo->prepare('SELECT id, name, email, currency, dark_mode FROM users WHERE id = ? LIMIT 1');
    $st->execute(array($uid));
    $user = $st->fetch();
}

include 'includes/header.php';
?>

<header>
    <h2>Settings</h2>
</header>

<div class="card mb-2">
    <h3>Profile</h3>
    <div style="display:flex; align-items:center; gap:20px; margin-top:20px;">
        <div class="avatar avatar-placeholder" style="width:80px; height:80px; font-size:1.6rem;">
        </div>
    </div>
    <form action="UserProfileSettings.php" method="POST" class="grid-2 mt-2" style="grid-template-columns: 1fr 1fr;">
        <input type="hidden" name="action" value="update_profile">
        <div class="form-group">
            <label>Username</label>
            <input type="text" name="name" value="<?php echo e(isset($user['name']) ? $user['name'] : ''); ?>" required>
        </div>
        <div class="form-group">
            <label>Email Address</label>
            <input type="email" name="email" value="<?php echo e(isset($user['email']) ? $user['email'] : ''); ?>" required>
        </div>
        <div style="grid-column:1 / -1;">
            <?php if ($msg): ?><div class="text-green" style="margin-bottom:10px; font-size:0.9rem;">&nbsp;<?php echo e($msg); ?></div><?php endif; ?>
            <?php if ($err): ?><div class="text-red" style="margin-bottom:10px; font-size:0.9rem;">&nbsp;<?php echo e($err); ?></div><?php endif; ?>
            <button class="btn btn-primary" style="width:auto;">
                <i class="fas fa-check"></i> Confirm Changes
            </button>
        </div>
    </form>
</div>

<div class="card mb-2">
    <h3>Security</h3>
    <div style="display:flex; justify-content:space-between; align-items:center; margin-top:15px;">
        <div>
            <strong>Password</strong>
            <div class="text-muted">Keep your account secure with a strong password</div>
        </div>
        <button class="btn btn-secondary" style="width:auto;" onclick="document.getElementById('changePasswordForm').style.display = document.getElementById('changePasswordForm').style.display === 'none' ? 'block' : 'none';">
            <i class="fas fa-key"></i> Change Password
        </button>
    </div>
    
    <!-- Change Password Form (Hidden by default) -->
    <div id="changePasswordForm" class="form-highlight" style="display:none; margin-top:20px; padding:20px; border-radius:8px; border-left:4px solid #3b82f6;">
        <form action="UserProfileSettings.php" method="POST" onsubmit="return confirm('Are you sure you want to change your password?');">
            <input type="hidden" name="action" value="change_password">
            <div class="form-group">
                <label>Current Password</label>
                <input type="password" name="old_password" placeholder="Enter your current password" required>
            </div>
            <div class="form-group">
                <label>New Password</label>
                <input type="password" name="new_password" placeholder="Enter new password (min 6 characters)" required>
            </div>
            <div class="form-group">
                <label>Confirm New Password</label>
                <input type="password" name="confirm_password" placeholder="Confirm new password" required>
            </div>
            <div style="display:flex; gap:10px;">
                <button type="submit" class="btn btn-primary" style="width:auto;">
                    <i class="fas fa-check"></i> Change Password
                </button>
                <button type="button" class="btn btn-secondary" style="width:auto;" onclick="document.getElementById('changePasswordForm').style.display='none';">
                    <i class="fas fa-times"></i> Cancel
                </button>
            </div>
            <?php if ($msg && isset($_POST['action']) && $_POST['action'] === 'change_password'): ?>
                <div class="text-green" style="margin-top:15px; font-size:0.9rem;">✓ <?php echo e($msg); ?></div>
            <?php endif; ?>
            <?php if ($err && isset($_POST['action']) && $_POST['action'] === 'change_password'): ?>
                <div class="text-red" style="margin-top:15px; font-size:0.9rem;">✗ <?php echo e($err); ?></div>
            <?php endif; ?>
        </form>
    </div>
</div>

<div class="grid-2">
    <div class="card">
        <h3>Preferences</h3>
        <form action="UserProfileSettings.php" method="POST" style="margin-top:15px;">
            <input type="hidden" name="action" value="change_currency">
            <div class="form-group" style="margin:0;">
                <label>Currency</label>
                <div style="display:flex; gap:10px; align-items:center; margin-bottom:12px;">
                    <div style="flex:0 0 auto; padding:10px 16px; background:rgba(59, 130, 246, 0.1); border:2px solid #3b82f6; border-radius:6px; text-align:center;">
                        <div style="font-size:0.75rem; opacity:0.7; margin-bottom:2px;">Current</div>
                        <div style="font-weight:700; font-size:1.2rem; color:#3b82f6;">
                            <?php echo e(isset($user['currency']) ? $user['currency'] : 'USD'); ?>
                        </div>
                    </div>
                    <i class="fas fa-arrow-right" style="font-size:1.2rem; opacity:0.5; flex:0 0 auto;"></i>
                    <select name="currency" style="flex:1;">
                        <option value="USD" <?php echo (isset($user['currency']) && $user['currency'] === 'USD' ? 'selected' : ''); ?>>USD - US Dollar</option>
                        <option value="EUR" <?php echo (isset($user['currency']) && $user['currency'] === 'EUR' ? 'selected' : ''); ?>>EUR - Euro</option>
                        <option value="GBP" <?php echo (isset($user['currency']) && $user['currency'] === 'GBP' ? 'selected' : ''); ?>>GBP - British Pound</option>
                        <option value="MYR" <?php echo (isset($user['currency']) && $user['currency'] === 'MYR' ? 'selected' : ''); ?>>MYR - Malaysian Ringgit</option>
                        <option value="JPY" <?php echo (isset($user['currency']) && $user['currency'] === 'JPY' ? 'selected' : ''); ?>>JPY - Japanese Yen</option>
                        <option value="AUD" <?php echo (isset($user['currency']) && $user['currency'] === 'AUD' ? 'selected' : ''); ?>>AUD - Australian Dollar</option>
                        <option value="CAD" <?php echo (isset($user['currency']) && $user['currency'] === 'CAD' ? 'selected' : ''); ?>>CAD - Canadian Dollar</option>
                        <option value="CHF" <?php echo (isset($user['currency']) && $user['currency'] === 'CHF' ? 'selected' : ''); ?>>CHF - Swiss Franc</option>
                        <option value="CNY" <?php echo (isset($user['currency']) && $user['currency'] === 'CNY' ? 'selected' : ''); ?>>CNY - Chinese Yuan</option>
                        <option value="INR" <?php echo (isset($user['currency']) && $user['currency'] === 'INR' ? 'selected' : ''); ?>>INR - Indian Rupee</option>
                        <option value="SGD" <?php echo (isset($user['currency']) && $user['currency'] === 'SGD' ? 'selected' : ''); ?>>SGD - Singapore Dollar</option>
                    </select>
                    <button type="submit" class="btn btn-primary" style="width:auto; flex:0 0 auto;">Save</button>
                </div>
                <div style="margin-top: 12px; padding: 12px; background: rgba(255, 193, 7, 0.1); border: 1px solid rgba(255, 193, 7, 0.3); border-radius: 6px;">
                    <label style="display: flex; align-items: flex-start; gap: 10px; cursor: pointer; margin: 0;">
                        <input type="checkbox" name="convert_values" value="1" style="width: 18px; height: 18px; margin: 2px 0 0 0; flex-shrink: 0; cursor: pointer;">
                        <div style="flex: 1;">
                            <div style="font-size: 0.9rem; font-weight: 500; margin-bottom: 4px;">
                                <i class="fas fa-exchange-alt" style="margin-right: 6px; opacity: 0.8;"></i>
                                Convert all existing values to new currency
                            </div>
                            <div style="font-size: 0.8rem; opacity: 0.7; line-height: 1.4;">
                                This will automatically convert all your transactions, budgets, and presets using current exchange rates.
                            </div>
                        </div>
                    </label>
                </div>
            </div>
            <?php if ($msg && isset($_POST['action']) && $_POST['action'] === 'change_currency'): ?>
                <div class="text-green" style="margin-top:10px; font-size:0.9rem;">✓ <?php echo e($msg); ?></div>
            <?php endif; ?>
            <?php if ($err && isset($_POST['action']) && $_POST['action'] === 'change_currency'): ?>
                <div class="text-red" style="margin-top:10px; font-size:0.9rem;">✗ <?php echo e($err); ?></div>
            <?php endif; ?>
        </form>
    </div>
    <div class="card">
        <h3>Appearance</h3>
        <div style="margin-top:15px;">
            <div style="display:flex; justify-content:space-between; align-items:center;">
                <div class="form-group" style="margin:0;">
                    <label>Dark Mode</label>
                    <div class="text-muted" style="font-size:0.85rem;">Enable dark theme for easier viewing</div>
                </div>
                <div style="display:flex; align-items:center; gap:15px;">
                    <!-- Modern Toggle Switch -->
                    <label class="toggle-switch">
                        <input type="checkbox" id="darkModeCheckbox" <?php echo (isset($user['dark_mode']) && $user['dark_mode'] ? 'checked' : ''); ?>>
                        <span class="toggle-slider"></span>
                    </label>
                </div>
            </div>
            <div id="darkModeMessage" style="margin-top:10px; font-size:0.9rem; display:none;"></div>
        </div>
    </div>
</div>

<!-- Global save removed; handled in profile form above -->

<style>
    .toggle-switch {
        position: relative;
        display: inline-block;
        width: 60px;
        height: 30px;
        cursor: pointer;
    }
    
    .toggle-switch input {
        opacity: 0;
        width: 0;
        height: 0;
    }
    
    .toggle-slider {
        position: absolute;
        cursor: pointer;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: #ccc;
        transition: 0.3s;
        border-radius: 30px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0 8px;
        font-size: 12px;
        font-weight: bold;
        color: white;
    }
    
    .toggle-slider::before {
        content: '';
        position: absolute;
        height: 24px;
        width: 24px;
        left: 3px;
        bottom: 3px;
        background-color: white;
        transition: 0.3s;
        border-radius: 50%;
    }
    
    .toggle-switch input:checked + .toggle-slider {
        background-color: #10b981;
    }
    
    .toggle-switch input:checked + .toggle-slider::before {
        transform: translateX(30px);
    }
    
    html[data-theme="dark"] .toggle-slider {
        background-color: #555;
    }
    
    html[data-theme="dark"] .toggle-switch input:checked + .toggle-slider {
        background-color: #10b981;
    }
</style>

<script>
    const darkModeCheckbox = document.getElementById('darkModeCheckbox');
    const darkModeMessage = document.getElementById('darkModeMessage');
    
    function applyTheme(isDark) {
        if (isDark) {
            document.documentElement.setAttribute('data-theme', 'dark');
            document.documentElement.classList.add('dark-mode');
            document.body.classList.add('dark-mode');
            localStorage.setItem('isDarkMode', 'true');
        } else {
            document.documentElement.setAttribute('data-theme', 'light');
            document.documentElement.classList.remove('dark-mode');
            document.body.classList.remove('dark-mode');
            localStorage.setItem('isDarkMode', 'false');
        }
    }
    
    if (darkModeCheckbox) {
        darkModeCheckbox.addEventListener('change', function() {
            const isDarkMode = this.checked;
            
            // Update theme immediately
            applyTheme(isDarkMode);
            
            // Show message
            darkModeMessage.textContent = isDarkMode ? '✓ Dark mode enabled.' : '✓ Dark mode disabled.';
            darkModeMessage.style.color = '#10b981';
            darkModeMessage.style.display = 'block';
            setTimeout(() => {
                darkModeMessage.style.display = 'none';
            }, 2000);
            
            // Save to server
            const formData = new FormData();
            formData.append('action', 'change_dark_mode');
            formData.append('dark_mode', isDarkMode ? '1' : '0');
            
            fetch('UserProfileSettings.php', {
                method: 'POST',
                body: formData
            }).catch(error => {
                console.log('Saved to server');
            });
        });
    }
</script>

<?php include 'includes/footer.php'; ?>
