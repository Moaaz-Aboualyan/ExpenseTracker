<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/DatabaseConfiguration.php';
require_login();

$pdo = db();
$uid = (int)$_SESSION['user_id'];

// Get last 12 months of spending data for trend
$monthlyData = [];
for ($i = 11; $i >= 0; $i--) {
    $date = date('Y-m', strtotime("-$i months"));
    $stmt = $pdo->prepare("SELECT SUM(amount) as total FROM transactions WHERE user_id = ? AND type = 'expense' AND DATE_FORMAT(date, '%Y-%m') = ?");
    $stmt->execute([$uid, $date]);
    $result = $stmt->fetch();
    $monthlyData[$date] = (float)($result['total'] ?? 0);
}

// Get category-wise expense breakdown
$categoryData = [];
$stmt = $pdo->prepare("SELECT c.id, c.name, SUM(t.amount) as total FROM categories c LEFT JOIN transactions t ON t.category_id = c.id AND t.user_id = ? AND t.type = 'expense' WHERE c.user_id = ? GROUP BY c.id ORDER BY total DESC");
$stmt->execute([$uid, $uid]);
$categoryResults = $stmt->fetchAll();

$totalExpenses = 0;
foreach ($categoryResults as $cat) {
    $categoryData[] = [
        'name' => $cat['name'],
        'amount' => (float)($cat['total'] ?? 0)
    ];
    $totalExpenses += (float)($cat['total'] ?? 0);
}

// Generate colors for categories
$colors = ['#3b82f6', '#10b981', '#f59e0b', '#8b5cf6', '#ef4444', '#06b6d4', '#ec4899', '#14b8a6'];
$colorIndex = 0;
foreach ($categoryData as &$cat) {
    $cat['color'] = $colors[$colorIndex % count($colors)];
    $cat['percentage'] = $totalExpenses > 0 ? ($cat['amount'] / $totalExpenses * 100) : 0;
    $colorIndex++;
}

// Handle CSV export
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'export_csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="expense_report_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Date', 'Category', 'Amount', 'Type', 'Note']);
    
    $stmt = $pdo->prepare("SELECT t.date, c.name, t.amount, t.type, t.note FROM transactions t LEFT JOIN categories c ON c.id = t.category_id WHERE t.user_id = ? ORDER BY t.date DESC");
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
    <h2>Financial Reports</h2>
    <div class="actions">
        <form method="POST" style="display: inline;">
            <input type="hidden" name="action" value="export_csv">
            <button type="submit" class="btn btn-secondary" style="width:auto;"><i class="fas fa-download"></i> Export CSV</button>
        </form>
    </div>
</header>

<div class="grid-2">
    <div class="card">
        <h3>Monthly Spending Trend (Last 12 Months)</h3>
        <div class="chart-container" style="height: 300px; margin-top: 20px; display: flex; align-items: flex-end; justify-content: space-around; padding-bottom: 10px; padding-top: 10px;">
            <!-- Dynamic Bar Chart -->
            <?php
            $maxAmount = max($monthlyData) > 0 ? max($monthlyData) : 1;
            foreach ($monthlyData as $month => $amount) {
                $percentage = ($amount / $maxAmount) * 100;
                $percentage = max($percentage, 5); // Minimum height for visibility
                echo '<div class="chart-bar" style="width: 30px; height: ' . $percentage . '%;" title="' . get_currency_symbol() . number_format($amount, 2) . '"></div>';
            }
            ?>
        </div>
        <div class="chart-labels" style="display: flex; justify-content: space-around; margin-top: 10px; font-size: 0.8rem; flex-wrap: wrap;">
            <?php
            foreach ($monthlyData as $month => $amount) {
                echo '<span>' . date('M', strtotime($month . '-01')) . '</span>';
            }
            ?>
        </div>
    </div>

    <div class="card">
        <h3>Expense Breakdown by Category</h3>
        <ul style="list-style: none; margin-top: 20px;">
            <?php if (empty($categoryData)): ?>
                <li class="text-muted">No expense data available</li>
            <?php else: ?>
                <?php foreach ($categoryData as $cat): ?>
                    <li style="margin-bottom: 15px; display: flex; justify-content: space-between; align-items: center;">
                        <div style="display: flex; align-items: center; gap: 10px; flex: 1;">
                            <div style="width: 12px; height: 12px; background: <?php echo $cat['color']; ?>; border-radius: 50%;"></div>
                            <span><?php echo htmlspecialchars($cat['name']); ?></span>
                        </div>
                        <span style="text-align: right;">
                            <?php echo get_currency_symbol(); ?><?php echo number_format($cat['amount'], 2); ?><br>
                            <small style="color: #666;"><?php echo number_format($cat['percentage'], 1); ?>%</small>
                        </span>
                    </li>
                <?php endforeach; ?>
            <?php endif; ?>
        </ul>
        <?php if (!empty($categoryData)): ?>
            <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #e5e7eb;">
                <strong>Total Expenses:</strong> <?php echo get_currency_symbol(); ?><?php echo number_format($totalExpenses, 2); ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
