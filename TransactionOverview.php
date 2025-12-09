<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/DatabaseConfiguration.php';
require_login();

$pdo = db();
$uid = (int)$_SESSION['user_id'];
$msg = '';
$err = '';

// Handle delete transaction
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_transaction') {
    $tid = isset($_POST['transaction_id']) ? (int)$_POST['transaction_id'] : 0;
    
    if ($tid <= 0) {
        $err = 'Invalid transaction ID.';
    } else {
        // Verify transaction belongs to user
        $verify = $pdo->prepare('SELECT id FROM transactions WHERE id = ? AND user_id = ? LIMIT 1');
        $verify->execute([$tid, $uid]);
        if (!$verify->fetch()) {
            $err = 'Transaction not found or access denied.';
        } else {
            // Delete the transaction
            $del = $pdo->prepare('DELETE FROM transactions WHERE id = ? AND user_id = ?');
            $del->execute([$tid, $uid]);
            $msg = 'Transaction deleted successfully.';
        }
    }
}

// Get filters from query parameters
$selectedCategory = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$selectedType = isset($_GET['type']) ? $_GET['type'] : 'all';
$searchTerm = isset($_GET['search']) ? trim($_GET['search']) : '';
$dateFrom = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$dateTo = isset($_GET['date_to']) ? $_GET['date_to'] : '';
$amountMin = isset($_GET['amount_min']) && $_GET['amount_min'] !== '' ? (float)$_GET['amount_min'] : null;
$amountMax = isset($_GET['amount_max']) && $_GET['amount_max'] !== '' ? (float)$_GET['amount_max'] : null;
$sortBy = isset($_GET['sort']) ? $_GET['sort'] : 'date_desc';

// Build query based on filters
$query = "SELECT t.id, t.date, t.type, t.amount, t.note, c.name AS category_name
          FROM transactions t
          LEFT JOIN categories c ON c.id = t.category_id
          WHERE t.user_id = ?";
$params = [$uid];

if ($selectedCategory > 0) {
    $query .= " AND t.category_id = ?";
    $params[] = $selectedCategory;
}

if ($selectedType === 'income' || $selectedType === 'expense') {
    $query .= " AND t.type = ?";
    $params[] = $selectedType;
}

if ($searchTerm !== '') {
    $query .= " AND (t.note LIKE ? OR c.name LIKE ?)";
    $searchParam = '%' . $searchTerm . '%';
    $params[] = $searchParam;
    $params[] = $searchParam;
}

if ($dateFrom !== '') {
    $query .= " AND t.date >= ?";
    $params[] = $dateFrom;
}

if ($dateTo !== '') {
    $query .= " AND t.date <= ?";
    $params[] = $dateTo;
}

if ($amountMin !== null) {
    $query .= " AND t.amount >= ?";
    $params[] = $amountMin;
}

if ($amountMax !== null) {
    $query .= " AND t.amount <= ?";
    $params[] = $amountMax;
}

switch ($sortBy) {
    case 'date_asc':
        $query .= " ORDER BY t.date ASC, t.id ASC";
        break;
    case 'amount_desc':
        $query .= " ORDER BY t.amount DESC, t.date DESC";
        break;
    case 'amount_asc':
        $query .= " ORDER BY t.amount ASC, t.date DESC";
        break;
    default:
        $query .= " ORDER BY t.date DESC, t.id DESC";
        break;
}

$query .= " LIMIT 100";

$tx = $pdo->prepare($query);
$tx->execute($params);
$rows = $tx->fetchAll();

// Fetch all categories for dropdown (separated by type)
$catStmt = $pdo->prepare("SELECT id, name, type FROM categories WHERE user_id = ? ORDER BY type DESC, name ASC");
$catStmt->execute([$uid]);
$allCategories = $catStmt->fetchAll();

// Separate into income and expense
$expenseCategories = [];
$incomeCategories = [];
foreach ($allCategories as $cat) {
    if ($cat['type'] === 'income') {
        $incomeCategories[] = $cat;
    } else {
        $expenseCategories[] = $cat;
    }
}

include 'includes/header.php';
?>

<header>
    <h2>All Transactions</h2>
    <div class="actions">
        <a href="TransactionEntryForm.php">
            <button class="btn btn-primary" style="width: auto;">
                <i class="fas fa-plus"></i> Add New
            </button>
        </a>
        <button id="filter-toggle" class="btn btn-secondary" style="width: auto; display: none; margin-left: 10px;">
            <i class="fas fa-sliders-h"></i> Filters
        </button>
    </div>
</header>

<!-- Filter Form - Sidebar on Right -->
<div id="transactions-container" style="display: grid; grid-template-columns: 1fr 280px; gap: 20px; align-items: start;">
    
    <!-- Main Content -->
    <main>

<?php if ($msg): ?>
    <div class="text-green" style="margin-bottom: 15px; padding: 12px; background: #ecfdf5; border-radius: 8px; border-left: 4px solid #10b981; font-size: 0.9rem;">
        ✓ <?php echo e($msg); ?>
    </div>
<?php endif; ?>
<?php if ($err): ?>
    <div class="text-red" style="margin-bottom: 15px; padding: 12px; background: #fef2f2; border-radius: 8px; border-left: 4px solid #ef4444; font-size: 0.9rem;">
        ✗ <?php echo e($err); ?>
    </div>
<?php endif; ?>

<div class="card">
    <h3>
        Transactions 
        <span style="font-size: 0.85rem; opacity: 0.7; font-weight: 400;">
            (<?php echo count($rows); ?> result<?php echo count($rows) !== 1 ? 's' : ''; ?>)
        </span>
    </h3>
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Category</th>
                <th>Note</th>
                <th style="text-align:right;">Amount</th>
                <th style="text-align:center; width: 60px;">Action</th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($rows)): ?>
            <tr><td colspan="5" class="text-muted">No transactions yet.</td></tr>
        <?php else: ?>
            <?php foreach ($rows as $r): ?>
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
                <td style="text-align:center;">
                    <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this transaction?');">
                        <input type="hidden" name="action" value="delete_transaction">
                        <input type="hidden" name="transaction_id" value="<?php echo (int)$r['id']; ?>">
                        <button type="submit" style="background: none; border: none; color: #ef4444; cursor: pointer; font-size: 1rem; padding: 0;" title="Delete transaction">
                            <i class="fas fa-trash"></i>
                        </button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
    </div>
    </main>

    <!-- Sidebar Filters -->
    <aside id="filters-sidebar" style="position: relative;">
        <div class="card" style="position: sticky; top: 20px;">
            <h3 style="margin-bottom: 15px; margin-top: 0;">
                <i class="fas fa-filter"></i> Filters
                <?php if ($selectedCategory > 0 || $selectedType !== 'all' || $searchTerm !== '' || $dateFrom !== '' || $dateTo !== '' || $amountMin !== null || $amountMax !== null): ?>
                    <a href="TransactionOverview.php" style="font-size: 0.8rem; color: #ef4444; text-decoration: none; margin-left: 8px; float: right;">
                        <i class="fas fa-times-circle"></i> Clear
                    </a>
                <?php endif; ?>
            </h3>
            <form method="GET" action="TransactionOverview.php" style="display: flex; flex-direction: column; gap: 15px;">
                
                <div class="form-group" style="margin: 0;">
                    <label><i class="fas fa-search"></i> Search</label>
                    <input type="text" name="search" placeholder="Notes or category..." value="<?php echo e($searchTerm); ?>">
                </div>

                <div class="form-group" style="margin: 0;">
                    <label><i class="fas fa-exchange-alt"></i> Type</label>
                    <select name="type">
                        <option value="all" <?php echo $selectedType === 'all' ? 'selected' : ''; ?>>All Types</option>
                        <option value="income" <?php echo $selectedType === 'income' ? 'selected' : ''; ?>>Income Only</option>
                        <option value="expense" <?php echo $selectedType === 'expense' ? 'selected' : ''; ?>>Expense Only</option>
                    </select>
                </div>

                <div class="form-group" style="margin: 0;">
                    <label><i class="fas fa-tag"></i> Category</label>
                    <select name="category">
                        <option value="0" <?php echo $selectedCategory == 0 ? 'selected' : ''; ?>>All Categories</option>
                        <?php if (!empty($expenseCategories)): ?>
                            <optgroup label="Expenses">
                                <?php foreach ($expenseCategories as $cat): ?>
                                    <option value="<?php echo (int)$cat['id']; ?>" <?php echo $selectedCategory == $cat['id'] ? 'selected' : ''; ?>>
                                        <?php echo e($cat['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </optgroup>
                        <?php endif; ?>
                        <?php if (!empty($incomeCategories)): ?>
                            <optgroup label="Income">
                                <?php foreach ($incomeCategories as $cat): ?>
                                    <option value="<?php echo (int)$cat['id']; ?>" <?php echo $selectedCategory == $cat['id'] ? 'selected' : ''; ?>>
                                        <?php echo e($cat['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </optgroup>
                        <?php endif; ?>
                    </select>
                </div>

                <div class="form-group" style="margin: 0;">
                    <label><i class="fas fa-calendar-alt"></i> Date From</label>
                    <input type="date" name="date_from" value="<?php echo e($dateFrom); ?>">
                </div>

                <div class="form-group" style="margin: 0;">
                    <label><i class="fas fa-calendar-check"></i> Date To</label>
                    <input type="date" name="date_to" value="<?php echo e($dateTo); ?>">
                </div>

                <div class="form-group" style="margin: 0;">
                    <label><i class="fas fa-dollar-sign"></i> Min Amount</label>
                    <input type="number" name="amount_min" placeholder="0.00" step="0.01" min="0" value="<?php echo $amountMin !== null ? e($amountMin) : ''; ?>">
                </div>

                <div class="form-group" style="margin: 0;">
                    <label><i class="fas fa-dollar-sign"></i> Max Amount</label>
                    <input type="number" name="amount_max" placeholder="9999.99" step="0.01" min="0" value="<?php echo $amountMax !== null ? e($amountMax) : ''; ?>">
                </div>

                <div class="form-group" style="margin: 0;">
                    <label><i class="fas fa-sort"></i> Sort By</label>
                    <select name="sort">
                        <option value="date_desc" <?php echo $sortBy === 'date_desc' ? 'selected' : ''; ?>>Date (Newest)</option>
                        <option value="date_asc" <?php echo $sortBy === 'date_asc' ? 'selected' : ''; ?>>Date (Oldest)</option>
                        <option value="amount_desc" <?php echo $sortBy === 'amount_desc' ? 'selected' : ''; ?>>Amount (High)</option>
                        <option value="amount_asc" <?php echo $sortBy === 'amount_asc' ? 'selected' : ''; ?>>Amount (Low)</option>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%;">
                    <i class="fas fa-check"></i> Apply
                </button>
            </form>
        </div>
    </aside>
</div>

<script>
// Mobile filter sidebar toggle
document.addEventListener('DOMContentLoaded', function() {
    const filterToggle = document.getElementById('filter-toggle');
    const filtersSidebar = document.getElementById('filters-sidebar');
    
    // Show toggle button on mobile
    function updateFilterToggleVisibility() {
        if (window.innerWidth <= 768) {
            filterToggle.style.display = 'block';
        } else {
            filterToggle.style.display = 'none';
            filtersSidebar.classList.remove('active');
        }
    }
    
    // Initial check
    updateFilterToggleVisibility();
    
    // Toggle filters on button click
    filterToggle.addEventListener('click', function(e) {
        e.preventDefault();
        filtersSidebar.classList.toggle('active');
        filterToggle.querySelector('i').classList.toggle('fa-sliders-h');
        filterToggle.querySelector('i').classList.toggle('fa-times');
    });
    
    // Update visibility on window resize
    window.addEventListener('resize', updateFilterToggleVisibility);
});
</script>

<?php include 'includes/footer.php'; ?>
