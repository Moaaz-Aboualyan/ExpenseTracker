<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/DatabaseConfiguration.php';
require_login();

$pdo = db();
$uid = (int)$_SESSION['user_id'];
$msg = '';
$err = '';

// Handle create
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create') {
    $name = trim(isset($_POST['name']) ? $_POST['name'] : '');
    $type = isset($_POST['type']) && in_array($_POST['type'], ['income', 'expense']) ? $_POST['type'] : 'expense';
    $budget = trim(isset($_POST['monthly_budget']) ? $_POST['monthly_budget'] : '');
    $recurFreq = isset($_POST['recurring_frequency']) && in_array($_POST['recurring_frequency'], ['weekly','biweekly','monthly','quarterly','yearly']) ? $_POST['recurring_frequency'] : null;
    $recurAmount = isset($_POST['recurring_amount']) ? trim($_POST['recurring_amount']) : '';
    $recurDate = isset($_POST['recurring_date']) ? (int)$_POST['recurring_date'] : null;
    
    if ($name === '') {
        $err = 'Category name is required';
    } else {
        $budgetVal = ($budget === '' ? null : (float)$budget);
        $recurAmountVal = ($recurAmount === '' ? null : (float)$recurAmount);
        $recurDateVal = ($recurDate === 0 || $recurDate === null) ? null : $recurDate;
        
        $stmt = $pdo->prepare('INSERT INTO categories(user_id, name, type, monthly_budget, recurring_frequency, recurring_amount, recurring_date) VALUES(?,?,?,?,?,?,?)');
        $stmt->execute([$uid, $name, $type, $budgetVal, $recurFreq, $recurAmountVal, $recurDateVal]);
        $msg = 'Category added';
    }
}

// Handle update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update') {
    $cid = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $name = trim(isset($_POST['name']) ? $_POST['name'] : '');
    $type = isset($_POST['type']) && in_array($_POST['type'], ['income', 'expense']) ? $_POST['type'] : 'expense';
    $budget = trim(isset($_POST['monthly_budget']) ? $_POST['monthly_budget'] : '');
    $recurFreq = isset($_POST['recurring_frequency']) && in_array($_POST['recurring_frequency'], ['weekly','biweekly','monthly','quarterly','yearly']) ? $_POST['recurring_frequency'] : null;
    $recurAmount = isset($_POST['recurring_amount']) ? trim($_POST['recurring_amount']) : '';
    $recurDate = isset($_POST['recurring_date']) ? (int)$_POST['recurring_date'] : null;
    
    if ($cid > 0 && $name !== '') {
        $budgetVal = ($budget === '' ? null : (float)$budget);
        $recurAmountVal = ($recurAmount === '' ? null : (float)$recurAmount);
        $recurDateVal = ($recurDate === 0 || $recurDate === null) ? null : $recurDate;
        
        $stmt = $pdo->prepare('UPDATE categories SET name = ?, type = ?, monthly_budget = ?, recurring_frequency = ?, recurring_amount = ?, recurring_date = ? WHERE id = ? AND user_id = ?');
        $stmt->execute([$name, $type, $budgetVal, $recurFreq, $recurAmountVal, $recurDateVal, $cid, $uid]);
        $msg = 'Category updated';
    } else {
        $err = 'Invalid category name';
    }
}

// Handle delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $cid = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    if ($cid > 0) {
        $stmt = $pdo->prepare('DELETE FROM categories WHERE id = ? AND user_id = ?');
        $stmt->execute([$cid, $uid]);
        $msg = 'Category removed';
    }
}

// Fetch categories
$categories = [];
$stmt = $pdo->prepare('SELECT id, name, type, monthly_budget, recurring_frequency, recurring_amount, recurring_date, last_recurring_date FROM categories WHERE user_id = ? ORDER BY type DESC, name');
$stmt->execute([$uid]);
$categories = $stmt->fetchAll();

include 'includes/header.php';
?>

<header>
    <h2>Manage Categories</h2>
    <form action="BudgetCategoryManager.php" method="POST" style="display:flex; gap:10px; align-items:end; flex-wrap:wrap;">
        <input type="hidden" name="action" value="create">
        <div class="form-group" style="margin:0;">
            <label style="display:block;font-size:0.85rem;color:#666;">Type</label>
            <select name="type" id="createType" style="width:150px;" onchange="toggleRecurringFields('createType');">
                <option value="expense">Expense</option>
                <option value="income">Income</option>
            </select>
        </div>
        <div class="form-group" style="margin:0;">
            <label style="display:block;font-size:0.85rem;color:#666;">Name</label>
            <input type="text" name="name" placeholder="e.g., Groceries" required>
        </div>
        <div class="form-group" style="margin:0;" id="budgetField">
            <label style="display:block;font-size:0.85rem;color:#666;">Monthly Budget</label>
            <input type="number" step="0.01" name="monthly_budget" placeholder="Optional">
        </div>
        <div class="form-group" style="margin:0; display:none;" id="recurFreqField">
            <label style="display:block;font-size:0.85rem;color:#666;">Frequency</label>
            <select name="recurring_frequency" style="width:150px;">
                <option value="">None</option>
                <option value="weekly">Weekly</option>
                <option value="biweekly">Bi-weekly</option>
                <option value="monthly">Monthly</option>
                <option value="quarterly">Quarterly</option>
                <option value="yearly">Yearly</option>
            </select>
        </div>
        <div class="form-group" style="margin:0; display:none;" id="recurAmountField">
            <label style="display:block;font-size:0.85rem;color:#666;">Amount</label>
            <input type="number" step="0.01" name="recurring_amount" placeholder="Optional">
        </div>
        <div class="form-group" style="margin:0; display:none;" id="recurDateField">
            <label style="display:block;font-size:0.85rem;color:#666;">Date (Day 1-31)</label>
            <input type="number" min="1" max="31" name="recurring_date" placeholder="e.g., 1">
        </div>
        <button class="btn btn-primary" style="width:auto; margin-top:20px;"><i class="fas fa-plus"></i> Add</button>
    </form>
    <?php if ($msg): ?><div class="text-green" style="margin-top:8px; font-size:0.9rem;"><?php echo e($msg); ?></div><?php endif; ?>
    <?php if ($err): ?><div class="text-red" style="margin-top:8px; font-size:0.9rem;"><?php echo e($err); ?></div><?php endif; ?>
    <script>
    function toggleRecurringFields(selectId) {
        const type = document.getElementById(selectId).value;
        document.getElementById('budgetField').style.display = type === 'expense' ? 'block' : 'none';
        document.getElementById('recurFreqField').style.display = type === 'income' ? 'block' : 'none';
        document.getElementById('recurAmountField').style.display = type === 'income' ? 'block' : 'none';
        document.getElementById('recurDateField').style.display = type === 'income' ? 'block' : 'none';
    }
    </script>
</header>

<div class="grid-3">
    <?php if (empty($categories)): ?>
        <div class="card">
            <div class="text-muted">No categories yet. Add one above.</div>
        </div>
    <?php endif; ?>
    <?php foreach ($categories as $cat): ?>
        <?php
            $isExpense = $cat['type'] === 'expense';
            // compute current month spend/income for this category
            $s = $pdo->prepare("SELECT SUM(amount) AS spent FROM transactions WHERE user_id=? AND category_id=? AND type=? AND DATE_FORMAT(`date`,'%Y-%m') = ?");
            $s->execute([$uid, $cat['id'], $cat['type'], date('Y-m')]);
            $rowSpent = $s->fetch();
            $spent = isset($rowSpent['spent']) && $rowSpent['spent'] !== null ? (float)$rowSpent['spent'] : 0;
            $budget = $cat['monthly_budget'] !== null ? (float)$cat['monthly_budget'] : 0;
            $pct = $budget > 0 ? min(100, ($spent/$budget)*100) : 0;
            
            $recurFreq = $cat['recurring_frequency'];
            $recurAmount = $cat['recurring_amount'] !== null ? (float)$cat['recurring_amount'] : 0;
            $recurDate = $cat['recurring_date'];
            
            // Determine status color
            $statusColor = '#10b981'; // green
            $statusText = 'On track';
            if ($budget > 0) {
                if ($pct >= 100) {
                    $statusColor = '#ef4444'; // red - over budget
                    $statusText = 'Over budget';
                } elseif ($pct >= 80) {
                    $statusColor = '#f59e0b'; // amber - warning
                    $statusText = 'Near limit';
                }
            }
        ?>
        <div class="card">
            <div style="display:flex; justify-content:space-between; align-items:flex-start;">
                <div style="display:flex; gap:10px; align-items:center; flex:1;">
                    <div style="background:<?php echo $isExpense ? '#d1fae5' : '#dbeafe'; ?>; padding:10px; border-radius:8px; color:<?php echo $isExpense ? '#065f46' : '#1e40af'; ?>;"><i class="fas <?php echo $isExpense ? 'fa-tags' : 'fa-dollar-sign'; ?>"></i></div>
                    <div style="flex:1;">
                        <div style="display:flex; align-items:center; gap:8px;">
                            <strong><?php echo e($cat['name']); ?></strong>
                            <span class="badge <?php echo $isExpense ? 'badge-expense' : 'badge-income'; ?>" style="font-size:0.7rem;"><?php echo ucfirst($cat['type']); ?></span>
                        </div>
                        <div class="text-muted" style="font-size:0.8rem;">
                            <?php if ($isExpense && $budget>0): ?>
                                <?php echo get_currency_symbol(); ?><?php echo number_format($spent,2); ?> / <?php echo get_currency_symbol(); ?><?php echo number_format($budget,2); ?>
                            <?php elseif ($isExpense): ?>
                                <span style="color:#f59e0b;">Budget not set</span>
                            <?php elseif ($recurFreq): ?>
                                <?php echo get_currency_symbol(); ?><?php echo number_format($recurAmount,2); ?> <?php echo ucfirst($recurFreq); ?> on day <?php echo (int)$recurDate; ?>
                            <?php else: ?>
                                Total: <?php echo get_currency_symbol(); ?><?php echo number_format($spent,2); ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div style="display:flex; gap:5px;">
                    <button class="btn btn-secondary" style="padding:6px 10px;" onclick="document.getElementById('editForm<?php echo (int)$cat['id']; ?>').style.display='block';" title="Edit"><i class="fas fa-edit"></i></button>
                    <form action="BudgetCategoryManager.php" method="POST" onsubmit="return confirm('Delete this category?');" style="margin:0;">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?php echo (int)$cat['id']; ?>">
                        <button class="btn btn-secondary" style="padding:6px 10px;" title="Delete"><i class="fas fa-trash"></i></button>
                    </form>
                </div>
            </div>
            <?php if ($isExpense && $budget>0): ?>
            <div class="mt-2">
                <div style="display:flex; justify-content:space-between; font-size:0.8rem; margin-bottom:5px;">
                    <span>Monthly Budget</span>
                    <span style="color:<?php echo $statusColor; ?>; font-weight:bold;"><?php echo (int)$pct; ?>% - <?php echo $statusText; ?></span>
                </div>
                <div style="height: 8px; background: #eee; border-radius: 4px;">
                    <div style="height: 100%; width: <?php echo (int)$pct; ?>%; background: <?php echo $statusColor; ?>; border-radius: 4px;"></div>
                </div>
                <div style="font-size:0.75rem; color:#999; margin-top:4px;">
                    Remaining: <strong style="color:<?php echo $budget-$spent < 0 ? '#ef4444' : '#065f46'; ?>"><?php echo get_currency_symbol(); ?><?php echo number_format(max(0, $budget-$spent),2); ?></strong>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Edit Form (Hidden by default) -->
            <div id="editForm<?php echo (int)$cat['id']; ?>" class="form-highlight" style="display:none; margin-top:15px; padding:15px; border-radius:6px; border-left:3px solid #3b82f6;">
                <form action="BudgetCategoryManager.php" method="POST">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="id" value="<?php echo (int)$cat['id']; ?>">
                    <div style="display:flex; gap:10px; margin-bottom:10px; align-items:end; flex-wrap:wrap;">
                        <div class="form-group" style="margin:0; flex:1; min-width:150px;">
                            <label style="display:block;font-size:0.85rem;color:#666;">Type</label>
                            <select name="type" id="editType<?php echo (int)$cat['id']; ?>" style="width:100%;" onchange="toggleEditRecurringFields(<?php echo (int)$cat['id']; ?>);">
                                <option value="expense" <?php echo $cat['type'] === 'expense' ? 'selected' : ''; ?>>Expense</option>
                                <option value="income" <?php echo $cat['type'] === 'income' ? 'selected' : ''; ?>>Income</option>
                            </select>
                        </div>
                        <div class="form-group" style="margin:0; flex:1; min-width:150px;">
                            <label style="display:block;font-size:0.85rem;color:#666;">Category Name</label>
                            <input type="text" name="name" value="<?php echo e($cat['name']); ?>" required style="width:100%;">
                        </div>
                        <div class="form-group" style="margin:0; flex:1; min-width:150px;" id="budgetFieldEdit<?php echo (int)$cat['id']; ?>" <?php echo $isExpense ? '' : 'style="display:none;"'; ?>>
                            <label style="display:block;font-size:0.85rem;color:#666;">Monthly Budget Limit</label>
                            <input type="number" step="0.01" name="monthly_budget" value="<?php echo $budget > 0 ? $budget : ''; ?>" placeholder="Leave empty for no limit" style="width:100%;">
                        </div>
                    </div>
                    <div style="display:flex; gap:10px; margin-bottom:10px; align-items:end; flex-wrap:wrap;" id="recurringFieldsEdit<?php echo (int)$cat['id']; ?>" <?php echo !$isExpense ? '' : 'style="display:none;"'; ?>>
                        <div class="form-group" style="margin:0; flex:1; min-width:150px;">
                            <label style="display:block;font-size:0.85rem;color:#666;">Frequency</label>
                            <select name="recurring_frequency" style="width:100%;">
                                <option value="">None</option>
                                <option value="weekly" <?php echo $recurFreq === 'weekly' ? 'selected' : ''; ?>>Weekly</option>
                                <option value="biweekly" <?php echo $recurFreq === 'biweekly' ? 'selected' : ''; ?>>Bi-weekly</option>
                                <option value="monthly" <?php echo $recurFreq === 'monthly' ? 'selected' : ''; ?>>Monthly</option>
                                <option value="quarterly" <?php echo $recurFreq === 'quarterly' ? 'selected' : ''; ?>>Quarterly</option>
                                <option value="yearly" <?php echo $recurFreq === 'yearly' ? 'selected' : ''; ?>>Yearly</option>
                            </select>
                        </div>
                        <div class="form-group" style="margin:0; flex:1; min-width:150px;">
                            <label style="display:block;font-size:0.85rem;color:#666;">Amount</label>
                            <input type="number" step="0.01" name="recurring_amount" value="<?php echo $recurAmount > 0 ? $recurAmount : ''; ?>" placeholder="e.g., 1000" style="width:100%;">
                        </div>
                        <div class="form-group" style="margin:0; flex:1; min-width:150px;">
                            <label style="display:block;font-size:0.85rem;color:#666;">Date (Day 1-31)</label>
                            <input type="number" min="1" max="31" name="recurring_date" value="<?php echo $recurDate ? (int)$recurDate : ''; ?>" placeholder="e.g., 1" style="width:100%;">
                        </div>
                    </div>
                    <div style="display:flex; gap:10px;">
                        <button type="submit" class="btn btn-primary" style="width:auto;"><i class="fas fa-save"></i> Save</button>
                        <button type="button" class="btn btn-secondary" onclick="document.getElementById('editForm<?php echo (int)$cat['id']; ?>').style.display='none';" style="width:auto;"><i class="fas fa-times"></i> Cancel</button>
                    </div>
                </form>
            </div>
            <script>
            function toggleEditRecurringFields(catId) {
                const type = document.getElementById('editType' + catId).value;
                document.getElementById('budgetFieldEdit' + catId).style.display = type === 'expense' ? 'block' : 'none';
                document.getElementById('recurringFieldsEdit' + catId).style.display = type === 'income' ? 'flex' : 'none';
            }
            </script>
        </div>
    <?php endforeach; ?>
</div>

<?php include 'includes/footer.php'; ?>