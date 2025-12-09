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

$tx = $pdo->prepare("SELECT t.id, t.date, t.type, t.amount, t.note, c.name AS category_name
                     FROM transactions t
                     LEFT JOIN categories c ON c.id = t.category_id
                     WHERE t.user_id = ?
                     ORDER BY t.date DESC, t.id DESC
                     LIMIT 20");
$tx->execute([$uid]);
$rows = $tx->fetchAll();

include 'includes/header.php';
?>

<header>
    <h2>All Transactions</h2>
    <div class="actions" style="display: flex; gap: 10px;">
        <input type="text" placeholder="Search transactions..." style="width: 250px;">
        <select style="width: auto;">
            <option>All Categories</option>
        </select>
        <a href="TransactionEntryForm.php">
            <button class="btn btn-primary" style="width: auto; margin-left:auto;">
                <i class="fas fa-plus"></i> Add New
            </button>
        </a>
    </div>
</header>

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
    <h3>Recent</h3>
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

<?php include 'includes/footer.php'; ?>
