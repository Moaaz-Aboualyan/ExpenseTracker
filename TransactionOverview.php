<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/DatabaseConfiguration.php';
require_login();

$pdo = db();
$uid = (int)$_SESSION['user_id'];

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

<div class="card">
    <h3>Recent</h3>
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
        <?php if (empty($rows)): ?>
            <tr><td colspan="4" class="text-muted">No transactions yet.</td></tr>
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
            </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include 'includes/footer.php'; ?>
