<?php
// Start session to handle login state (simulated)
session_start();
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
    </head>
<body>

<?php if (!in_array($current_page, $excluded_pages)): ?>
    <aside class="sidebar">
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

        <div class="user-profile">
            <div class="avatar"></div>
            <div class="user-info">
                <div style="font-weight:bold;">Alex Doe</div>
                <div style="font-size:0.8rem; color:#888;">alex@example.com</div>
            </div>
            <a href="UserAuthenticationForm.php" style="margin-left:auto; color:#888;"><i class="fas fa-sign-out-alt"></i></a>
        </div>
    </aside>

    <main class="main-content">
<?php endif; ?>

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

<!-- Mobile Profile Picture (top right) -->
<div class="mobile-profile-pic" id="mobileProfilePic" onclick="openProfileMenu()" style="display: none;"></div>

<script>
    // Show mobile navbar and profile pic on mobile
    function initMobileUI() {
        const navbar = document.querySelector('.mobile-bottom-navbar');
        const profilePic = document.querySelector('.mobile-profile-pic');
        
        if (window.innerWidth <= 768) {
            if (navbar) navbar.style.display = 'flex';
            if (profilePic) profilePic.style.display = 'block';
        } else {
            if (navbar) navbar.style.display = 'none';
            if (profilePic) profilePic.style.display = 'none';
        }
    }
    
    // Initialize on load and on resize
    window.addEventListener('load', initMobileUI);
    window.addEventListener('resize', initMobileUI);
    
    // Quick add modal function (you can customize this)
    function openQuickAddModal() {
        // Redirect to transaction entry form or open modal
        window.location.href = 'TransactionEntryForm.php';
    }
    
    // Profile menu function
    function openProfileMenu() {
        window.location.href = 'UserProfileSettings.php';
    }
</script>
