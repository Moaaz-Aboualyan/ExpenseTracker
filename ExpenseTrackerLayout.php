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