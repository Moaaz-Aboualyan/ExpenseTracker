<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/DatabaseConfiguration.php';
require_login();

$pdo = db();
$uid = (int)$_SESSION['user_id'];

// Get last 12 months of income and expense data for trend
$monthlyData = [];
for ($i = 11; $i >= 0; $i--) {
    $date = date('Y-m', strtotime("-$i months"));
    
    $stmt = $pdo->prepare("SELECT 
        SUM(CASE WHEN type='expense' THEN amount ELSE 0 END) as expenses,
        SUM(CASE WHEN type='income' THEN amount ELSE 0 END) as income
        FROM transactions WHERE user_id = ? AND DATE_FORMAT(date, '%Y-%m') = ?");
    $stmt->execute([$uid, $date]);
    $result = $stmt->fetch();
    
    $monthlyData[$date] = [
        'expenses' => (float)($result['expenses'] ?? 0),
        'income' => (float)($result['income'] ?? 0),
        'net' => (float)($result['income'] ?? 0) - (float)($result['expenses'] ?? 0)
    ];
}

// Get category-wise expense breakdown
$expenseCategoryData = [];
$stmt = $pdo->prepare("SELECT c.id, c.name, SUM(t.amount) as total FROM categories c 
    LEFT JOIN transactions t ON t.category_id = c.id AND t.user_id = ? AND t.type = 'expense' 
    WHERE c.user_id = ? AND c.type = 'expense' GROUP BY c.id ORDER BY total DESC");
$stmt->execute([$uid, $uid]);
$expenseCategoryResults = $stmt->fetchAll();

$totalExpenses = 0;
foreach ($expenseCategoryResults as $cat) {
    $expenseCategoryData[] = [
        'name' => $cat['name'],
        'amount' => (float)($cat['total'] ?? 0)
    ];
    $totalExpenses += (float)($cat['total'] ?? 0);
}

// Get category-wise income breakdown
$incomeCategoryData = [];
$stmt = $pdo->prepare("SELECT c.id, c.name, SUM(t.amount) as total FROM categories c 
    LEFT JOIN transactions t ON t.category_id = c.id AND t.user_id = ? AND t.type = 'income' 
    WHERE c.user_id = ? AND c.type = 'income' GROUP BY c.id ORDER BY total DESC");
$stmt->execute([$uid, $uid]);
$incomeCategoryResults = $stmt->fetchAll();

$totalIncome = 0;
foreach ($incomeCategoryResults as $cat) {
    $incomeCategoryData[] = [
        'name' => $cat['name'],
        'amount' => (float)($cat['total'] ?? 0)
    ];
    $totalIncome += (float)($cat['total'] ?? 0);
}

// Calculate statistics
$currentMonth = date('Y-m');
$lastMonth = date('Y-m', strtotime('-1 month'));
$currentMonthExpenses = $monthlyData[$currentMonth]['expenses'] ?? 0;
$lastMonthExpenses = $monthlyData[$lastMonth]['expenses'] ?? 0;
$currentMonthIncome = $monthlyData[$currentMonth]['income'] ?? 0;
$lastMonthIncome = $monthlyData[$lastMonth]['income'] ?? 0;

$expenseChange = $lastMonthExpenses > 0 ? (($currentMonthExpenses - $lastMonthExpenses) / $lastMonthExpenses) * 100 : 0;
$incomeChange = $lastMonthIncome > 0 ? (($currentMonthIncome - $lastMonthIncome) / $lastMonthIncome) * 100 : 0;

// Calculate average
$avgExpenses = count(array_filter($monthlyData, fn($m) => $m['expenses'] > 0)) > 0 
    ? array_sum(array_column($monthlyData, 'expenses')) / 12 : 0;
$avgIncome = count(array_filter($monthlyData, fn($m) => $m['income'] > 0)) > 0 
    ? array_sum(array_column($monthlyData, 'income')) / 12 : 0;

// Generate colors for categories
$colors = ['#3b82f6', '#10b981', '#f59e0b', '#8b5cf6', '#ef4444', '#06b6d4', '#ec4899', '#14b8a6', '#f97316', '#6366f1'];

// Add colors to expense categories
$colorIndex = 0;
foreach ($expenseCategoryData as &$cat) {
    $cat['color'] = $colors[$colorIndex % count($colors)];
    $cat['percentage'] = $totalExpenses > 0 ? ($cat['amount'] / $totalExpenses * 100) : 0;
    $colorIndex++;
}

// Add colors to income categories
foreach ($incomeCategoryData as &$cat) {
    $cat['color'] = $colors[$colorIndex % count($colors)];
    $cat['percentage'] = $totalIncome > 0 ? ($cat['amount'] / $totalIncome * 100) : 0;
    $colorIndex++;
}

// Handle CSV export
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'export_csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="financial_report_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Date', 'Category', 'Amount', 'Type', 'Note']);
    
    $stmt = $pdo->prepare("SELECT t.date, c.name, t.amount, t.type, t.note FROM transactions t 
        LEFT JOIN categories c ON c.id = t.category_id WHERE t.user_id = ? ORDER BY t.date DESC");
    $stmt->execute([$uid]);
    
    while ($row = $stmt->fetch()) {
        fputcsv($output, [$row['date'], $row['name'] ?? 'Uncategorized', $row['amount'], $row['type'], $row['note']]);
    }
    fclose($output);
    exit;
}

include 'includes/header.php';
?>

<header>
    <div>
        <h2>Financial Reports</h2>
        <p class="text-muted">Comprehensive financial analysis and insights</p>
    </div>
    <div class="actions">
        <form method="POST" style="display: inline;">
            <input type="hidden" name="action" value="export_csv">
            <button type="submit" class="btn btn-secondary" style="width:auto;"><i class="fas fa-download"></i> Export CSV</button>
        </form>
    </div>
</header>

<!-- Key Metrics Summary -->
<div class="grid-4">
    <div class="card" style="border-left: 4px solid #10b981;">
        <div class="text-muted" style="font-size: 0.85rem;"><i class="fas fa-plus-circle" style="color:#10b981; margin-right:6px;"></i>Current Month Income</div>
        <h2 class="text-green" style="margin: 8px 0;"><?php echo get_currency_symbol(); ?><?php echo number_format($currentMonthIncome, 2); ?></h2>
        <div style="font-size: 0.8rem; color: <?php echo $incomeChange >= 0 ? '#10b981' : '#ef4444'; ?>;">
            <?php echo $incomeChange >= 0 ? '↑' : '↓'; ?> <?php echo number_format(abs($incomeChange), 1); ?>% vs last month
        </div>
    </div>
    
    <div class="card" style="border-left: 4px solid #ef4444;">
        <div class="text-muted" style="font-size: 0.85rem;"><i class="fas fa-minus-circle" style="color:#ef4444; margin-right:6px;"></i>Current Month Expenses</div>
        <h2 class="text-red" style="margin: 8px 0;"><?php echo get_currency_symbol(); ?><?php echo number_format($currentMonthExpenses, 2); ?></h2>
        <div style="font-size: 0.8rem; color: <?php echo $expenseChange >= 0 ? '#ef4444' : '#10b981'; ?>;">
            <?php echo $expenseChange >= 0 ? '↑' : '↓'; ?> <?php echo number_format(abs($expenseChange), 1); ?>% vs last month
        </div>
    </div>
    
    <div class="card" style="border-left: 4px solid #3b82f6;">
        <div class="text-muted" style="font-size: 0.85rem;"><i class="fas fa-chart-line" style="color:#3b82f6; margin-right:6px;"></i>12-Month Average</div>
        <div style="font-size: 0.9rem; margin: 8px 0;">
            <div style="color: #10b981; font-weight: 600;">Income: <?php echo get_currency_symbol(); ?><?php echo number_format($avgIncome, 2); ?></div>
            <div style="color: #ef4444; font-weight: 600; margin-top: 4px;">Expenses: <?php echo get_currency_symbol(); ?><?php echo number_format($avgExpenses, 2); ?></div>
        </div>
    </div>
    
    <div class="card" style="border-left: 4px solid #8b5cf6;">
        <div class="text-muted" style="font-size: 0.85rem;"><i class="fas fa-chart-pie" style="color:#8b5cf6; margin-right:6px;"></i>Total Data</div>
        <div style="font-size: 0.9rem; margin: 8px 0;">
            <div style="color: #10b981; font-weight: 600;">Income: <?php echo get_currency_symbol(); ?><?php echo number_format($totalIncome, 2); ?></div>
            <div style="color: #ef4444; font-weight: 600; margin-top: 4px;">Expenses: <?php echo get_currency_symbol(); ?><?php echo number_format($totalExpenses, 2); ?></div>
        </div>
    </div>
</div>

<!-- Main Charts Section -->
<div class="grid-2" style="gap: 20px; margin-bottom: 30px;">
    <!-- Monthly Trend Chart -->
    <div class="card">
        <h3 style="display: flex; align-items: center; gap: 8px; margin-bottom: 20px;"><i class="fas fa-chart-line" style="color:#3b82f6;"></i>12-Month Income vs Expenses Trend</h3>
        <div class="chart-container" style="height: 300px; margin-top: 20px; display: flex; align-items: flex-end; justify-content: space-around; padding-bottom: 30px; padding-top: 10px; gap: 4px;">
            <?php
            $maxAmount = max(
                max(array_column($monthlyData, 'expenses')),
                max(array_column($monthlyData, 'income'))
            ) > 0 ? max(
                max(array_column($monthlyData, 'expenses')),
                max(array_column($monthlyData, 'income'))
            ) : 1;
            
            foreach ($monthlyData as $month => $data) {
                $expenseHeight = ($data['expenses'] / $maxAmount) * 100;
                $incomeHeight = ($data['income'] / $maxAmount) * 100;
                $expenseHeight = max($expenseHeight, 3);
                $incomeHeight = max($incomeHeight, 3);
                ?>
                <div style="display: flex; flex-direction: column; align-items: center; flex: 1; height: 100%;">
                    <div style="display: flex; gap: 3px; align-items: flex-end; height: 100%; width: 100%; justify-content: center;">
                        <div style="width: 40%; height: <?php echo $expenseHeight; ?>%; background: #ef4444; border-radius: 2px 2px 0 0; transition: all 0.3s;" title="Expenses: <?php echo get_currency_symbol(); ?><?php echo number_format($data['expenses'], 2); ?>"></div>
                        <div style="width: 40%; height: <?php echo $incomeHeight; ?>%; background: #10b981; border-radius: 2px 2px 0 0; transition: all 0.3s;" title="Income: <?php echo get_currency_symbol(); ?><?php echo number_format($data['income'], 2); ?>"></div>
                    </div>
                    <span style="font-size: 0.65rem; margin-top: 8px; text-align: center; white-space: nowrap;"><?php echo date('M', strtotime($month . '-01')); ?></span>
                </div>
            <?php } ?>
        </div>
        <div style="margin-top: 20px; padding-top: 15px; border-top: 1px solid #e5e7eb; display: flex; gap: 20px; justify-content: center;">
            <div style="display: flex; align-items: center; gap: 8px;">
                <div style="width: 12px; height: 12px; background: #ef4444; border-radius: 2px;"></div>
                <span style="font-size: 0.85rem;">Expenses</span>
            </div>
            <div style="display: flex; align-items: center; gap: 8px;">
                <div style="width: 12px; height: 12px; background: #10b981; border-radius: 2px;"></div>
                <span style="font-size: 0.85rem;">Income</span>
            </div>
        </div>
    </div>
    
    <!-- Expense vs Income Summary -->
    <div class="card">
        <h3 style="display: flex; align-items: center; gap: 8px; margin-bottom: 20px;"><i class="fas fa-balance-scale" style="color:#8b5cf6;"></i>Overall Summary</h3>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
            <!-- Expense Summary -->
            <div style="padding: 15px; background: var(--card-bg); border-radius: 8px; border-left: 3px solid #ef4444;">
                <div class="text-muted" style="font-size: 0.85rem; margin-bottom: 8px;"><i class="fas fa-minus-circle" style="color:#ef4444;"></i> Total Expenses</div>
                <h3 style="color: #ef4444; margin: 0;"><?php echo get_currency_symbol(); ?><?php echo number_format($totalExpenses, 2); ?></h3>
                <div style="font-size: 0.8rem; margin-top: 8px; opacity: 0.7;">
                    Average per month: <?php echo get_currency_symbol(); ?><?php echo number_format($avgExpenses, 2); ?>
                </div>
                <div style="margin-top: 12px; padding-top: 12px; border-top: 1px solid var(--border-color);">
                    <div style="font-size: 0.75rem; font-weight: 600; opacity: 0.7;">Top 3 Categories</div>
                    <?php $count = 0; foreach ($expenseCategoryData as $cat): 
                        if ($count++ >= 3) break;
                    ?>
                    <div style="font-size: 0.8rem; margin-top: 6px; display: flex; justify-content: space-between;">
                        <span><?php echo e(substr($cat['name'], 0, 14)); ?></span>
                        <span style="font-weight: 600;"><?php echo number_format($cat['percentage'], 0); ?>%</span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Income Summary -->
            <div style="padding: 15px; background: var(--card-bg); border-radius: 8px; border-left: 3px solid #10b981;">
                <div class="text-muted" style="font-size: 0.85rem; margin-bottom: 8px;"><i class="fas fa-plus-circle" style="color:#10b981;"></i> Total Income</div>
                <h3 style="color: #10b981; margin: 0;"><?php echo get_currency_symbol(); ?><?php echo number_format($totalIncome, 2); ?></h3>
                <div style="font-size: 0.8rem; margin-top: 8px; opacity: 0.7;">
                    Average per month: <?php echo get_currency_symbol(); ?><?php echo number_format($avgIncome, 2); ?>
                </div>
                <div style="margin-top: 12px; padding-top: 12px; border-top: 1px solid var(--border-color);">
                    <div style="font-size: 0.75rem; font-weight: 600; opacity: 0.7;">Top 3 Sources</div>
                    <?php $count = 0; foreach ($incomeCategoryData as $cat): 
                        if ($count++ >= 3) break;
                    ?>
                    <div style="font-size: 0.8rem; margin-top: 6px; display: flex; justify-content: space-between;">
                        <span><?php echo e(substr($cat['name'], 0, 14)); ?></span>
                        <span style="font-weight: 600;"><?php echo number_format($cat['percentage'], 0); ?>%</span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Category Breakdown Section -->
<div class="grid-2" style="gap: 20px; margin-bottom: 30px;">
    <!-- Expense Breakdown -->
    <div class="card">
        <h3 style="display: flex; align-items: center; gap: 8px; margin-bottom: 20px;"><i class="fas fa-minus-circle" style="color:#ef4444;"></i>Expense Breakdown by Category</h3>
        <ul style="list-style: none; margin: 0; padding: 0;">
            <?php if (empty($expenseCategoryData)): ?>
                <li class="text-muted">No expense data available</li>
            <?php else: ?>
                <?php foreach ($expenseCategoryData as $cat): ?>
                    <li style="margin-bottom: 12px;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
                            <div style="display: flex; align-items: center; gap: 8px; flex: 1;">
                                <div style="width: 12px; height: 12px; background: <?php echo $cat['color']; ?>; border-radius: 2px;"></div>
                                <span style="font-weight: 500;"><?php echo e($cat['name']); ?></span>
                            </div>
                            <span style="font-weight: 600;"><?php echo get_currency_symbol(); ?><?php echo number_format($cat['amount'], 2); ?></span>
                        </div>
                        <div style="height: 6px; background: #eee; border-radius: 3px; overflow: hidden;">
                            <div style="height: 100%; width: <?php echo $cat['percentage']; ?>%; background: <?php echo $cat['color']; ?>;"></div>
                        </div>
                        <div style="font-size: 0.75rem; color: #666; margin-top: 4px;"><?php echo number_format($cat['percentage'], 1); ?>% of total</div>
                    </li>
                <?php endforeach; ?>
            <?php endif; ?>
        </ul>
    </div>
    
    <!-- Income Breakdown -->
    <div class="card">
        <h3 style="display: flex; align-items: center; gap: 8px; margin-bottom: 20px;"><i class="fas fa-plus-circle" style="color:#10b981;"></i>Income Breakdown by Source</h3>
        <ul style="list-style: none; margin: 0; padding: 0;">
            <?php if (empty($incomeCategoryData)): ?>
                <li class="text-muted">No income data available</li>
            <?php else: ?>
                <?php foreach ($incomeCategoryData as $cat): ?>
                    <li style="margin-bottom: 12px;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
                            <div style="display: flex; align-items: center; gap: 8px; flex: 1;">
                                <div style="width: 12px; height: 12px; background: <?php echo $cat['color']; ?>; border-radius: 2px;"></div>
                                <span style="font-weight: 500;"><?php echo e($cat['name']); ?></span>
                            </div>
                            <span style="font-weight: 600;"><?php echo get_currency_symbol(); ?><?php echo number_format($cat['amount'], 2); ?></span>
                        </div>
                        <div style="height: 6px; background: #eee; border-radius: 3px; overflow: hidden;">
                            <div style="height: 100%; width: <?php echo $cat['percentage']; ?>%; background: <?php echo $cat['color']; ?>;"></div>
                        </div>
                        <div style="font-size: 0.75rem; color: #666; margin-top: 4px;"><?php echo number_format($cat['percentage'], 1); ?>% of total</div>
                    </li>
                <?php endforeach; ?>
            <?php endif; ?>
        </ul>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
