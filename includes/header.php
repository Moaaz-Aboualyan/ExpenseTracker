<?php
// Common header include for all pages
// Start session and auth helpers
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/auth.php';

$current_page = basename($_SERVER['PHP_SELF']);

// Define excluded pages (Login/Register) where sidebar shouldn't show
$excluded_pages = ['UserAuthenticationForm.php', 'UserRegistrationForm.php'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Expense Tracker</title>
    <!-- specific CSS file name from project structure -->
    <link rel="stylesheet" href="styles_and_layout.css">
    <!-- Simple icon library CDN -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <script>
        // Apply dark mode preference on page load - must run BEFORE page renders
        function applyDarkMode() {
            // Check user preference from session (PHP)
            const userDarkMode = <?php echo (is_logged_in() && isset($_SESSION['user_dark_mode']) && $_SESSION['user_dark_mode']) ? 'true' : 'false'; ?>;
            
            // Check localStorage as fallback/override
            const storedDarkMode = localStorage.getItem('isDarkMode');
            
            // Use session preference if set, otherwise use localStorage
            let isDarkMode = userDarkMode;
            if (storedDarkMode !== null) {
                isDarkMode = storedDarkMode === 'true';
            }
            
            // Apply the theme
            if (isDarkMode) {
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
        
        // Apply theme immediately, before DOM is rendered
        applyDarkMode();
        
        // Reapply on load events just to be safe
        document.addEventListener('DOMContentLoaded', applyDarkMode);
        window.addEventListener('load', applyDarkMode);
    </script>
</head>
<body>

<?php if (!in_array($current_page, $excluded_pages)): ?>
    <aside class="sidebar" id="mobileSidebar">
        <div class="logo">
            <i class="fas fa-chart-pie text-green"></i> ExpenseTracker
        </div>

        <ul class="nav-links">
            <li>
                <a href="DashboardOverview.php" class="<?php echo $current_page == 'DashboardOverview.php' ? 'active' : ''; ?>">
                    <i class="fas fa-th-large mr-2"></i> &nbsp; Dashboard
                </a>
            </li>
            <li>
                <a href="TransactionOverview.php" class="<?php echo $current_page == 'TransactionOverview.php' ? 'active' : ''; ?>">
                    <i class="fas fa-exchange-alt mr-2"></i> &nbsp; Transactions
                </a>
            </li>
            <li>
                <a href="BudgetCategoryManager.php" class="<?php echo $current_page == 'BudgetCategoryManager.php' ? 'active' : ''; ?>">
                    <i class="fas fa-tags mr-2"></i> &nbsp; Categories
                </a>
            </li>
            <li>
                <a href="FinancialReportsDashboard.php" class="<?php echo $current_page == 'FinancialReportsDashboard.php' ? 'active' : ''; ?>">
                    <i class="fas fa-chart-bar mr-2"></i> &nbsp; Reports
                </a>
            </li>
            <li>
                <a href="UserProfileSettings.php" class="<?php echo $current_page == 'UserProfileSettings.php' ? 'active' : ''; ?>">
                    <i class="fas fa-cog mr-2"></i> &nbsp; Settings
                </a>
            </li>
        </ul>

        <?php 
            $cu = current_user();
        ?>

        <div class="user-profile">
            <a href="UserProfileSettings.php" style="flex-shrink: 0;">
                <div class="avatar avatar-placeholder"></div>
            </a>
            <div class="user-info" style="flex: 1; min-width: 0;">
                <?php if ($cu): ?>
                    <div style="font-weight:bold; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">&nbsp;<?php echo e($cu['name'] ?: 'User'); ?></div>
                    <div style="font-size:0.8rem; color:#888; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">&nbsp;<?php echo e($cu['email'] ?: ''); ?></div>
                <?php else: ?>
                    <div style="font-weight:bold;">Guest</div>
                    <div style="font-size:0.8rem; color:#888;">Not signed in</div>
                <?php endif; ?>
            </div>
            <div style="display:flex; gap:10px; margin-left:8px; flex-shrink: 0;">
                <?php if ($cu): ?>
                    <button id="darkModeToggleHeader" onclick="toggleDarkMode()" style="background:none; border:none; color:#888; cursor:pointer; font-size:1.2rem; transition:color 0.3s; padding: 4px;" title="Toggle Dark Mode"><i class="fas fa-moon"></i></button>
                    <a href="logout.php" style="color:#888; font-size:1.2rem; padding: 4px;" title="Logout"><i class="fas fa-sign-out-alt"></i></a>
                <?php else: ?>
                    <a href="UserAuthenticationForm.php" style="color:#888; font-size:1.2rem;" title="Login"><i class="fas fa-sign-in-alt"></i></a>
                <?php endif; ?>
            </div>
        </div>
    </aside>

    <!-- Mobile Bottom Navbar (visible on mobile only) -->
    <nav class="mobile-bottom-navbar" style="display: none;">
        <div class="navbar-left">
            <a href="DashboardOverview.php" class="navbar-item <?php echo $current_page == 'DashboardOverview.php' ? 'active' : ''; ?>">
                <i class="fas fa-th-large"></i>
                <span>Dashboard</span>
            </a>
        </div>
        <div class="navbar-left">
            <a href="TransactionOverview.php" class="navbar-item <?php echo $current_page == 'TransactionOverview.php' ? 'active' : ''; ?>">
                <i class="fas fa-exchange-alt"></i>
                <span>Trans</span>
            </a>
        </div>
        <div class="navbar-center">
            <button class="quick-add-btn" id="mobileQuickAddBtn" onclick="openQuickAddModal()">
                <i class="fas fa-plus"></i>
            </button>
        </div>
        <div class="navbar-right">
            <a href="BudgetCategoryManager.php" class="navbar-item <?php echo $current_page == 'BudgetCategoryManager.php' ? 'active' : ''; ?>">
                <i class="fas fa-tags"></i>
                <span>Cats</span>
            </a>
        </div>
        <div class="navbar-right">
            <a href="FinancialReportsDashboard.php" class="navbar-item <?php echo $current_page == 'FinancialReportsDashboard.php' ? 'active' : ''; ?>">
                <i class="fas fa-chart-bar"></i>
                <span>Reports</span>
            </a>
        </div>
    </nav>

    <!-- Mobile Settings Avatar (top right) -->
    <div class="mobile-settings-avatar" id="mobileSettingsAvatar" onclick="openSettingsMenu()" style="display: none;"></div>

<?php endif; ?>

<script>
    // Initialize mobile navbar
    function initMobileUI() {
        const navbar = document.querySelector('.mobile-bottom-navbar');
        
        if (window.innerWidth <= 768) {
            if (navbar) navbar.style.display = 'flex';
        } else {
            if (navbar) navbar.style.display = 'none';
        }
    }
    
    window.addEventListener('load', function() {
        initMobileUI();
        initMobileSettingsAvatar();
    });
    window.addEventListener('resize', function() {
        initMobileUI();
        initMobileSettingsAvatar();
    });
    
    function initMobileSettingsAvatar() {
        const avatar = document.querySelector('.mobile-settings-avatar');
        if (window.innerWidth <= 768) {
            if (avatar) avatar.style.display = 'block';
        } else {
            if (avatar) avatar.style.display = 'none';
        }
    }
    
    function openQuickAddModal() {
        window.location.href = 'TransactionEntryForm.php';
    }
    
    function openSettingsMenu() {
        window.location.href = 'UserProfileSettings.php';
    }
    
    function toggleDarkMode() {
        const isDarkMode = document.documentElement.getAttribute('data-theme') === 'dark';
        const newDarkMode = !isDarkMode;
        
        if (newDarkMode) {
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
        
        const formData = new FormData();
        formData.append('action', 'toggle_dark_mode');
        formData.append('dark_mode', newDarkMode ? '1' : '0');
        fetch('UserProfileSettings.php', {
            method: 'POST',
            body: formData
        }).catch(() => {});
    }
</script>

<main class="main-content">

