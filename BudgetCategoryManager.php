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
    $budget = trim(isset($_POST['monthly_budget']) ? $_POST['monthly_budget'] : '');
    if ($name === '') {
        $err = 'Category name is required';
    } else {
        $budgetVal = ($budget === '' ? null : (float)$budget);
        $stmt = $pdo->prepare('INSERT INTO categories(user_id, name, monthly_budget) VALUES(?,?,?)');
        $stmt->execute([$uid, $name, $budgetVal]);
        $msg = 'Category added';
    }
}

// Handle update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update') {
    $cid = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $name = trim(isset($_POST['name']) ? $_POST['name'] : '');
    $budget = trim(isset($_POST['monthly_budget']) ? $_POST['monthly_budget'] : '');
    if ($cid > 0 && $name !== '') {
        $budgetVal = ($budget === '' ? null : (float)$budget);
        $stmt = $pdo->prepare('UPDATE categories SET name = ?, monthly_budget = ? WHERE id = ? AND user_id = ?');
        $stmt->execute([$name, $budgetVal, $cid, $uid]);
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
$stmt = $pdo->prepare('SELECT id, name, monthly_budget FROM categories WHERE user_id = ? ORDER BY name');
$stmt->execute([$uid]);
$categories = $stmt->fetchAll();

include 'includes/header.php';
?>

<header>
    <h2>Manage Categories</h2>
    <form action="BudgetCategoryManager.php" method="POST" style="display:flex; gap:10px; align-items:end;">
        <input type="hidden" name="action" value="create">
        <div class="form-group" style="margin:0;">
            <label style="display:block;font-size:0.85rem;color:#666;">Name</label>
            <input type="text" name="name" placeholder="e.g., Groceries" required>
        </div>
        <div class="form-group" style="margin:0;">
            <label style="display:block;font-size:0.85rem;color:#666;">Monthly Budget</label>
            <input type="number" step="0.01" name="monthly_budget" placeholder="Optional">
        </div>
        <button class="btn btn-primary" style="width:auto; margin-top:20px;"><i class="fas fa-plus"></i> Add</button>
    </form>
    <?php if ($msg): ?><div class="text-green" style="margin-top:8px; font-size:0.9rem;"><?php echo e($msg); ?></div><?php endif; ?>
    <?php if ($err): ?><div class="text-red" style="margin-top:8px; font-size:0.9rem;"><?php echo e($err); ?></div><?php endif; ?>
</header>

<div class="grid-3">
    <?php if (empty($categories)): ?>
        <div class="card">
            <div class="text-muted">No categories yet. Add one above.</div>
        </div>
    <?php endif; ?>
    <?php foreach ($categories as $cat): ?>
        <?php
            // compute current month spend for this category
            $s = $pdo->prepare("SELECT SUM(amount) AS spent FROM transactions WHERE user_id=? AND category_id=? AND type='expense' AND DATE_FORMAT(`date`,'%Y-%m') = ?");
            $s->execute([$uid, $cat['id'], date('Y-m')]);
            $rowSpent = $s->fetch();
            $spent = isset($rowSpent['spent']) && $rowSpent['spent'] !== null ? (float)$rowSpent['spent'] : 0;
            $budget = $cat['monthly_budget'] !== null ? (float)$cat['monthly_budget'] : 0;
            $pct = $budget > 0 ? min(100, ($spent/$budget)*100) : 0;
            
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
                    <div style="background:#d1fae5; padding:10px; border-radius:8px; color:#065f46;"><i class="fas fa-tags"></i></div>
                    <div style="flex:1;">
                        <strong><?php echo e($cat['name']); ?></strong>
                        <div class="text-muted" style="font-size:0.8rem;">
                            <?php if ($budget>0): ?><?php echo get_currency_symbol(); ?><?php echo number_format($spent,2); ?> / <?php echo get_currency_symbol(); ?><?php echo number_format($budget,2); ?><?php else: ?><span style="color:#f59e0b;">Budget not set</span><?php endif; ?>
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
            <?php if ($budget>0): ?>
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
                    <div style="display:flex; gap:10px; margin-bottom:10px; align-items:end;">
                        <div class="form-group" style="margin:0; flex:1;">
                            <label style="display:block;font-size:0.85rem;color:#666;">Category Name</label>
                            <input type="text" name="name" value="<?php echo e($cat['name']); ?>" required style="width:100%;">
                        </div>
                        <div class="form-group" style="margin:0; flex:1;">
                            <label style="display:block;font-size:0.85rem;color:#666;">Monthly Budget Limit</label>
                            <input type="number" step="0.01" name="monthly_budget" value="<?php echo $budget > 0 ? $budget : ''; ?>" placeholder="Leave empty for no limit" style="width:100%;">
                        </div>
                    </div>
                    <div style="display:flex; gap:10px;">
                        <button type="submit" class="btn btn-primary" style="width:auto;"><i class="fas fa-save"></i> Save</button>
                        <button type="button" class="btn btn-secondary" onclick="document.getElementById('editForm<?php echo (int)$cat['id']; ?>').style.display='none';" style="width:auto;"><i class="fas fa-times"></i> Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?php include 'includes/footer.php'; ?>