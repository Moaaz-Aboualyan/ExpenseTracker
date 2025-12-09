<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/DatabaseConfiguration.php';
require_once __DIR__ . '/includes/recurring-income.php';
require_login();

// Process any pending recurring income for this user
processRecurringIncome(db(), (int)$_SESSION['user_id']);

// Compute simple aggregates for current month
$pdo = db();
$uid = (int)$_SESSION['user_id'];
$ym = date('Y-m');

// Chart filter: default to current month, allow date range
$chartFilter = isset($_POST['chart_filter']) ? trim($_POST['chart_filter']) : 'month';
$chartStartDate = date('Y-m-d', strtotime('first day of this month'));
$chartEndDate = date('Y-m-d');

if ($chartFilter === 'week') {
    $chartStartDate = date('Y-m-d', strtotime('monday this week'));
} elseif ($chartFilter === 'quarter') {
    $quarter = ceil(date('m') / 3);
    $chartStartDate = date('Y-' . str_pad(($quarter * 3 - 2), 2, '0', STR_PAD_LEFT) . '-01');
} elseif ($chartFilter === 'year') {
    $chartStartDate = date('Y-01-01');
}

// Fetch chart data: expense by category for the selected period (expenses only)
$chartStmt = $pdo->prepare(
    "SELECT c.id, c.name, SUM(t.amount) as spent
     FROM categories c
     LEFT JOIN transactions t ON t.category_id = c.id AND t.user_id = c.user_id AND t.date >= ? AND t.date <= ? AND t.type = 'expense'
     WHERE c.user_id = ? AND c.type = 'expense'
     GROUP BY c.id, c.name
     HAVING spent > 0
     ORDER BY spent DESC"
);
$chartStmt->execute([$chartStartDate, $chartEndDate, $uid]);
$chartData = $chartStmt->fetchAll();

// Calculate total for percentages
$chartTotal = array_sum(array_column($chartData, 'spent'));

// Fetch income data separately
$incomeStmt = $pdo->prepare(
    "SELECT c.id, c.name, SUM(t.amount) as earned
     FROM categories c
     LEFT JOIN transactions t ON t.category_id = c.id AND t.user_id = c.user_id AND t.date >= ? AND t.date <= ? AND t.type = 'income'
     WHERE c.user_id = ? AND c.type = 'income'
     GROUP BY c.id, c.name
     HAVING earned > 0
     ORDER BY earned DESC"
);
$incomeStmt->execute([$chartStartDate, $chartEndDate, $uid]);
$incomeData = $incomeStmt->fetchAll();
$incomeTotal = array_sum(array_column($incomeData, 'earned'));
// Generate colors
$colors = ['#3b82f6', '#10b981', '#f59e0b', '#8b5cf6', '#ef4444', '#06b6d4', '#ec4899', '#14b8a6', '#f97316', '#6366f1'];

// Total income and expenses for current month
$stmt = $pdo->prepare("SELECT 
    SUM(CASE WHEN type='income' THEN amount ELSE 0 END) AS income,
    SUM(CASE WHEN type='expense' THEN amount ELSE 0 END) AS expenses
  FROM transactions 
  WHERE user_id = ? AND DATE_FORMAT(`date`, '%Y-%m') = ?");
$stmt->execute([$uid, $ym]);
$totals = $stmt->fetch() ?: ['income'=>0,'expenses'=>0];
$income = (float)($totals['income'] ?: 0);
$expenses = (float)($totals['expenses'] ?: 0);
$remaining = $income - $expenses;

include 'includes/header.php';
?>

<header>
    <div>
        <h2>Dashboard</h2>
        <p class="text-muted">Here's a look at your spending habits.</p>
    </div>
</header>

<!-- Summary Cards -->
<div class="grid-3">
    <div class="card" style="border-left: 4px solid #10b981;">
        <div class="text-muted" style="font-size: 0.85rem;"><i class="fas fa-plus-circle" style="color:#10b981; margin-right:6px;"></i>Total Income</div>
        <h2 class="text-green" style="margin: 8px 0;"><?php echo get_currency_symbol(); ?><?php echo number_format($income, 2); ?></h2>
        <div class="text-muted" style="font-size: 0.8rem;">This month</div>
    </div>
    <div class="card" style="border-left: 4px solid #ef4444;">
        <div class="text-muted" style="font-size: 0.85rem;"><i class="fas fa-minus-circle" style="color:#ef4444; margin-right:6px;"></i>Total Expenses</div>
        <h2 class="text-red" style="margin: 8px 0;"><?php echo get_currency_symbol(); ?><?php echo number_format($expenses, 2); ?></h2>
        <div class="text-muted" style="font-size: 0.8rem;">This month</div>
    </div>
    <div class="card" style="border-left: 4px solid #3b82f6;">
        <div class="text-muted" style="font-size: 0.85rem;"><i class="fas fa-scale-balanced" style="color:#3b82f6; margin-right:6px;"></i>Net Balance</div>
        <h2 style="color: #3b82f6; margin: 8px 0;"><?php echo get_currency_symbol(); ?><?php echo number_format($remaining, 2); ?></h2>
        <div class="text-muted" style="font-size: 0.8rem;">Income - Expenses</div>
    </div>
</div>

<!-- Main Dashboard Content -->
<div class="grid-2" style="gap: 20px;">
    <!-- Spending & Income Charts -->
    <div class="card" style="grid-column: 1 / -1;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 15px;">
            <div>
                <h3 style="margin: 0 0 4px 0;">Spending & Income Analysis</h3>
                <p class="text-muted" style="margin: 0; font-size: 0.85rem;">Track your expenses and income by category</p>
            </div>
            <form method="POST" style="display: flex; gap: 8px;">
                <select name="chart_filter" onchange="this.form.submit();" style="padding: 8px 12px; border-radius: 4px; border: 1px solid #d1d5db; background: white; cursor: pointer; font-size: 0.9rem;">
                    <option value="week" <?php echo $chartFilter === 'week' ? 'selected' : ''; ?>>This Week</option>
                    <option value="month" <?php echo $chartFilter === 'month' ? 'selected' : ''; ?>>This Month</option>
                    <option value="quarter" <?php echo $chartFilter === 'quarter' ? 'selected' : ''; ?>>This Quarter</option>
                    <option value="year" <?php echo $chartFilter === 'year' ? 'selected' : ''; ?>>This Year</option>
                </select>
            </form>
        </div>
        
        <div class="grid-2" style="gap: 20px;">
            <!-- Expenses Chart -->
            <div>
                <h4 style="margin-bottom: 15px; display: flex; align-items: center; gap: 8px;"><i class="fas fa-minus-circle" style="color:#ef4444;"></i>Expenses</h4>
                <?php if (empty($chartData)): ?>
                    <div class="text-muted" style="text-align: center; padding: 30px; background: #f9fafb; border-radius: 8px;">No expense data for this period.</div>
                <?php else: ?>
                    <div class="chart-wrapper" style="display: flex; flex-direction: column; gap: 15px;">
                        <!-- Hollow Pie Chart -->
                        <div class="chart-svg-container" style="display: flex; justify-content: center; align-items: center; min-height: 200px; position: relative;">
                            <svg class="pie-chart" width="200" height="200" style="transform: rotate(-90deg);">
                                <?php 
                                    $circumference = 2 * M_PI * 70;
                                    $currentOffset = 0;
                                    foreach ($chartData as $idx => $cat): 
                                        $spent = (float)$cat['spent'];
                                        $pct = $chartTotal > 0 ? ($spent / $chartTotal) * 100 : 0;
                                        $color = $colors[$idx % count($colors)];
                                        $strokeDasharray = ($pct / 100) * $circumference;
                                ?>
                                    <circle 
                                        cx="100" 
                                        cy="100" 
                                        r="70" 
                                        fill="none" 
                                        stroke="<?php echo $color; ?>" 
                                        stroke-width="16" 
                                        stroke-dasharray="<?php echo $strokeDasharray; ?> <?php echo $circumference; ?>"
                                        stroke-dashoffset="<?php echo -$currentOffset; ?>"
                                        stroke-linecap="round"
                                        opacity="0.9"
                                    />
                                <?php 
                                        $currentOffset += $strokeDasharray;
                                    endforeach; 
                                ?>
                                <!-- Center circle for hollow effect -->
                                <circle cx="100" cy="100" r="45" class="chart-center" stroke-width="1" />
                            </svg>
                            <div class="chart-total-label" style="position: absolute; text-align: center; font-size: 0.9rem; font-weight: 600;">
                                <div style="font-size: 1.1rem;" class="chart-text-dark"><?php echo get_currency_symbol(); ?><?php echo number_format($chartTotal, 2); ?></div>
                                <div style="font-size: 0.75rem;" class="chart-label-dark">Spent</div>
                            </div>
                        </div>
                        
                        <!-- Legend -->
                        <div class="chart-legend">
                            <ul style="list-style: none; padding: 0; margin: 0; max-height: 200px; overflow-y: auto;">
                                <?php foreach ($chartData as $idx => $cat): ?>
                                    <?php 
                                        $spent = (float)$cat['spent'];
                                        $pct = $chartTotal > 0 ? ($spent / $chartTotal) * 100 : 0;
                                        $color = $colors[$idx % count($colors)];
                                    ?>
                                    <li style="margin-bottom: 8px; display: flex; align-items: center; gap: 8px; font-size: 0.85rem;">
                                        <div style="width: 12px; height: 12px; background: <?php echo $color; ?>; border-radius: 2px; flex-shrink: 0;"></div>
                                        <div style="flex: 1; min-width: 0;">
                                            <div style="font-weight: 500;" class="chart-text-dark"><?php echo e(substr($cat['name'], 0, 16)); ?></div>
                                        </div>
                                        <div style="font-weight: 600;" class="chart-text-dark"><?php echo number_format($pct, 0); ?>%</div>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Income Chart -->
            <div>
                <h4 style="margin-bottom: 15px; display: flex; align-items: center; gap: 8px;"><i class="fas fa-plus-circle" style="color:#10b981;"></i>Income</h4>
                <?php if (empty($incomeData)): ?>
                    <div class="text-muted" style="text-align: center; padding: 30px; background: #f9fafb; border-radius: 8px;">No income data for this period.</div>
                <?php else: ?>
                    <div class="chart-wrapper" style="display: flex; flex-direction: column; gap: 15px;">
                        <!-- Income Pie Chart -->
                        <div class="chart-svg-container" style="display: flex; justify-content: center; align-items: center; min-height: 200px; position: relative;">
                            <svg class="pie-chart" width="200" height="200" style="transform: rotate(-90deg);">
                                <?php 
                                    $circumference = 2 * M_PI * 70;
                                    $currentOffset = 0;
                                    foreach ($incomeData as $idx => $cat): 
                                        $earned = (float)$cat['earned'];
                                        $pct = $incomeTotal > 0 ? ($earned / $incomeTotal) * 100 : 0;
                                        $color = $colors[$idx % count($colors)];
                                        $strokeDasharray = ($pct / 100) * $circumference;
                                ?>
                                    <circle 
                                        cx="100" 
                                        cy="100" 
                                        r="70" 
                                        fill="none" 
                                        stroke="<?php echo $color; ?>" 
                                        stroke-width="16" 
                                        stroke-dasharray="<?php echo $strokeDasharray; ?> <?php echo $circumference; ?>"
                                        stroke-dashoffset="<?php echo -$currentOffset; ?>"
                                        stroke-linecap="round"
                                        opacity="0.9"
                                    />
                                <?php 
                                        $currentOffset += $strokeDasharray;
                                    endforeach; 
                                ?>
                                <!-- Center circle for hollow effect -->
                                <circle cx="100" cy="100" r="45" class="chart-center" stroke-width="1" />
                            </svg>
                            <div class="chart-total-label" style="position: absolute; text-align: center; font-size: 0.9rem; font-weight: 600;">
                                <div style="font-size: 1.1rem;" class="chart-text-dark"><?php echo get_currency_symbol(); ?><?php echo number_format($incomeTotal, 2); ?></div>
                                <div style="font-size: 0.75rem;" class="chart-label-dark">Earned</div>
                            </div>
                        </div>
                        
                        <!-- Legend -->
                        <div class="chart-legend">
                            <ul style="list-style: none; padding: 0; margin: 0; max-height: 200px; overflow-y: auto;">
                                <?php foreach ($incomeData as $idx => $cat): ?>
                                    <?php 
                                        $earned = (float)$cat['earned'];
                                        $pct = $incomeTotal > 0 ? ($earned / $incomeTotal) * 100 : 0;
                                        $color = $colors[$idx % count($colors)];
                                    ?>
                                    <li style="margin-bottom: 8px; display: flex; align-items: center; gap: 8px; font-size: 0.85rem;">
                                        <div style="width: 12px; height: 12px; background: <?php echo $color; ?>; border-radius: 2px; flex-shrink: 0;"></div>
                                        <div style="flex: 1; min-width: 0;">
                                            <div style="font-weight: 500;" class="chart-text-dark"><?php echo e(substr($cat['name'], 0, 16)); ?></div>
                                        </div>
                                        <div style="font-weight: 600;" class="chart-text-dark"><?php echo number_format($pct, 0); ?>%</div>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Budget Progress -->
    <div class="card" style="grid-column: 1 / -1;">
        <h3 style="display: flex; align-items: center; gap: 8px; margin-bottom: 20px;"><i class="fas fa-chart-pie" style="color:#8b5cf6;"></i>Monthly Budget Progress</h3>
        <div style="margin-top: 20px;">
            <?php
            $stmt = $pdo->prepare("SELECT c.id, c.name, c.type, c.monthly_budget,
                SUM(CASE WHEN t.type='expense' THEN t.amount ELSE 0 END) AS spent
              FROM categories c
              LEFT JOIN transactions t ON t.category_id = c.id AND t.user_id = c.user_id AND DATE_FORMAT(t.date,'%Y-%m') = ?
              WHERE c.user_id = ? AND c.type = 'expense'
              GROUP BY c.id
              ORDER BY c.name ASC
              LIMIT 8");
            $stmt->execute([$ym, $uid]);
            $hasCategories = false;
            foreach ($stmt as $row):
                $hasCategories = true;
                $spent = (float)($row['spent'] ?: 0);
                $budget = isset($row['monthly_budget']) ? (float)$row['monthly_budget'] : 0;
                $pct = $budget > 0 ? min(100, ($spent / $budget) * 100) : 0;
                
                // Determine status color and icon
                $statusColor = '#10b981'; // green
                $statusIcon = 'fa-check-circle';
                $statusClass = '';
                if ($budget > 0) {
                    if ($pct >= 100) {
                        $statusColor = '#ef4444'; // red
                        $statusIcon = 'fa-exclamation-circle';
                        $statusClass = 'over-budget';
                    } elseif ($pct >= 80) {
                        $statusColor = '#f59e0b'; // amber
                        $statusIcon = 'fa-exclamation-triangle';
                        $statusClass = 'near-limit';
                    }
                } else {
                    $statusColor = '#9ca3af';
                    $statusIcon = 'fa-circle';
                }
            ?>
            <div style="display:flex; justify-content:space-between; margin-bottom:5px; align-items:center;">
                <div style="display:flex; align-items:center; gap:8px; flex:1;">
                    <i class="fas <?php echo $statusIcon; ?>" style="color:<?php echo $statusColor; ?>; font-size:0.9rem;"></i>
                    <span><?php echo e($row['name']); ?></span>
                </div>
                <span style="font-size:0.9rem; font-weight:600;"><?php echo get_currency_symbol(); ?><?php echo number_format($spent,2); ?><?php if ($budget>0): ?> / <?php echo get_currency_symbol(); ?><?php echo number_format($budget,2); ?><?php else: ?> <span style="color:#999;">(no limit)</span><?php endif; ?></span>
            </div>
            <div style="height: 8px; background: #eee; border-radius: 4px; margin-bottom: 12px;">
                <div style="height: 100%; width: <?php echo ($budget > 0 ? (int)$pct : 0); ?>%; background: <?php echo $statusColor; ?>; border-radius: 4px;"></div>
            </div>
            <?php endforeach; ?>
            <?php if (!$hasCategories): ?>
                <div class="text-muted" style="text-align: center; padding: 30px; background: #f9fafb; border-radius: 8px;">No expense categories yet. <a href="BudgetCategoryManager.php" style="color:#3b82f6; text-decoration: none; font-weight: 600;"><i class="fas fa-arrow-right" style="margin-right: 4px;"></i>Add categories</a>.</div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Quick Add on Dashboard -->
<div class="card mb-2">
    <h3>Quick Add</h3>
    <div style="display:flex; flex-wrap: wrap; gap: 10px; margin-top: 10px;">
        <?php
        // Load user's quick presets; tolerate absence of table
        $dashboard_presets = array();
        try {
            $ps = $pdo->prepare('SELECT id, label, type, amount FROM quick_presets WHERE user_id = ? ORDER BY created_at DESC, id DESC');
            $ps->execute(array($uid));
            $dashboard_presets = $ps->fetchAll();
        } catch (Exception $e) {
            $dashboard_presets = array();
        }
        ?>
        <?php if (empty($dashboard_presets)): ?>
            <div class="text-muted">No quick presets yet. Create them in Add New Transaction.</div>
        <?php else: ?>
            <?php foreach ($dashboard_presets as $p): ?>
                <?php
                    $isInc = ($p['type'] === 'income');
                    $btnClass = $isInc ? 'btn-primary' : 'btn-secondary';
                    $iconClass = $isInc ? 'fa-plus-circle' : 'fa-minus-circle';
                    $badgeClass = $isInc ? 'badge-income' : 'badge-expense';
                    $amountStr = number_format($p['amount'], 2);
                ?>
                <form action="TransactionEntryForm.php" method="POST" style="margin:0;">
                    <input type="hidden" name="action" value="quick_add">
                    <input type="hidden" name="preset_id" value="<?php echo (int)$p['id']; ?>">
                    <input type="hidden" name="redirect" value="DashboardOverview.php">
                    <button class="btn <?php echo $btnClass; ?>" style="width:auto;">
                        <i class="fas <?php echo $iconClass; ?>"></i>
                        <?php echo e($p['label']); ?>
                        <span class="badge <?php echo $badgeClass; ?>" style="margin-left:8px;"><?php echo get_currency_symbol(); ?><?php echo $amountStr; ?></span>
                    </button>
                </form>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    <div style="margin-top:10px;">
        <a href="TransactionEntryForm.php#quick-add-section">
            <button class="btn btn-primary" style="width: auto; margin-left:auto;">
                <i class="fas fa-cog"></i> Manage Quick Add
            </button>
        </a>
    </div>
    
</div>

<!-- Quick Actions -->
<div class="card">
    <div style="display:flex; justify-content: space-between; align-items: center;">
        <h3>Recent Transactions</h3>
        <a href="TransactionEntryForm.php">
            <button class="btn btn-primary" style="width: auto; margin-left:auto;">
                <i class="fas fa-plus"></i> Add New
            </button>
        </a>
    </div>
    <?php
    // Load the 5 most recent transactions for this user
    $recentStmt = $pdo->prepare("SELECT t.id, t.date, t.type, t.amount, t.note, c.name AS category_name
                                 FROM transactions t
                                 LEFT JOIN categories c ON c.id = t.category_id
                                 WHERE t.user_id = ?
                                 ORDER BY t.date DESC, t.id DESC
                                 LIMIT 5");
    $recentStmt->execute([$uid]);
    $recent = $recentStmt->fetchAll();
    ?>
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Category</th>
                <th>Note</th>
                <th style="text-align:right;">Amount</th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($recent)): ?>
            <tr><td colspan="4" class="text-muted">No transactions yet.</td></tr>
        <?php else: ?>
            <?php foreach ($recent as $r): ?>
            <?php
                $isIncome = ($r['type'] === 'income');
                $cls = $isIncome ? 'text-green' : 'text-red';
                $sign = $isIncome ? '+' : '-';
                $cat = $r['category_name'] ? $r['category_name'] : ($isIncome ? 'Income' : 'Uncategorized');
            ?>
            <tr>
                <td><?php echo e(date('M d, Y', strtotime($r['date']))); ?></td>
                <td><span class="badge <?php echo $isIncome ? 'badge-income' : 'badge-expense'; ?>"><?php echo e($cat); ?></span></td>
                <td><?php echo e($r['note']); ?></td>
                <td style="text-align:right; font-weight:bold;" class="<?php echo $cls; ?>"><?php echo $sign; ?><?php echo get_currency_symbol(); ?><?php echo number_format($r['amount'], 2); ?></td>
            </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include 'includes/footer.php'; ?>
