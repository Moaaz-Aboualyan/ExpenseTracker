<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/DatabaseConfiguration.php';
require_login();

// Compute simple aggregates for current month
$pdo = db();
$uid = (int)$_SESSION['user_id'];
$ym = date('Y-m');

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
    <div class="card">
        <div class="text-muted">Total Income</div>
        <h2 class="text-green"><?php echo get_currency_symbol(); ?><?php echo number_format($income, 2); ?></h2>
        <div class="text-muted" style="font-size: 0.8rem;">Current month</div>
    </div>
    <div class="card">
        <div class="text-muted">Total Expenses</div>
        <h2 class="text-red"><?php echo get_currency_symbol(); ?><?php echo number_format($expenses, 2); ?></h2>
        <div class="text-muted" style="font-size: 0.8rem;">Current month</div>
    </div>
    <div class="card">
        <div class="text-muted">Net (Income - Expenses)</div>
        <h2 style="color: #3b82f6;"><?php echo get_currency_symbol(); ?><?php echo number_format($remaining, 2); ?></h2>
        <div class="text-muted" style="font-size: 0.8rem;">Current month</div>
    </div>
</div>

<!-- Main Dashboard Content -->
<div class="grid-2">
    <!-- Spending Chart Placeholder -->
    <div class="card">
        <h3>Spending by Category</h3>
        <div class="chart-container" style="height: 250px; display: flex; align-items: center; justify-content: center; border-radius: 8px; margin-top: 15px;">
            <!-- In a real app, a Chart.js canvas would go here -->
            <div style="width: 150px; height: 150px; border-radius: 50%; border: 20px solid #10b981; border-right-color: #3b82f6; border-bottom-color: #f59e0b;"></div>
        </div>
    </div>

    <!-- Budget Progress -->
    <div class="card">
        <h3>Monthly Budget</h3>
        <div style="margin-top: 20px;">
            <?php
            $stmt = $pdo->prepare("SELECT c.id, c.name, c.monthly_budget,
                SUM(CASE WHEN t.type='expense' THEN t.amount ELSE 0 END) AS spent
              FROM categories c
              LEFT JOIN transactions t ON t.category_id = c.id AND t.user_id = c.user_id AND DATE_FORMAT(t.date,'%Y-%m') = ?
              WHERE c.user_id = ?
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
                <div class="text-muted">No categories yet. <a href="BudgetCategoryManager.php" style="color:#3b82f6;">Add one in Categories</a>.</div>
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
