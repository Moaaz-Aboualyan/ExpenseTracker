<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/DatabaseConfiguration.php';
require_login();

$pdo = db();
$uid = (int)$_SESSION['user_id'];
$msg = '';
$err = '';

// Ensure quick_presets table exists (minimal runtime safety)
try {
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS quick_presets (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id INT UNSIGNED NOT NULL,
            label VARCHAR(60) NOT NULL,
            type ENUM('income','expense') NOT NULL DEFAULT 'expense',
            amount DECIMAL(10,2) NOT NULL,
            category_id INT UNSIGNED NULL,
            note VARCHAR(255) NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_qp_user (user_id),
            CONSTRAINT fk_qp_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            CONSTRAINT fk_qp_category FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );
} catch (Exception $e) {
    // ignore if lacks permission; install.sql also creates it
}

// Load categories for this user
$catsStmt = $pdo->prepare('SELECT id, name FROM categories WHERE user_id = ? ORDER BY name');
$catsStmt->execute([$uid]);
$cats = $catsStmt->fetchAll();

// Load quick presets for this user
$presets = [];
try {
    $ps = $pdo->prepare('SELECT id, label, type, amount, category_id, note FROM quick_presets WHERE user_id = ? ORDER BY created_at DESC, id DESC');
    $ps->execute([$uid]);
    $presets = $ps->fetchAll();
} catch (Exception $e) {
    $presets = [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';

    if ($action === 'quick_add') {
        $pid = isset($_POST['preset_id']) ? (int)$_POST['preset_id'] : 0;
        if ($pid > 0) {
            $q = $pdo->prepare('SELECT id, type, amount, category_id, note FROM quick_presets WHERE id = ? AND user_id = ? LIMIT 1');
            $q->execute([$pid, $uid]);
            $p = $q->fetch();
            if ($p) {
                $today = date('Y-m-d');
                $stmt = $pdo->prepare('INSERT INTO transactions(user_id, category_id, type, amount, note, date) VALUES(?,?,?,?,?,?)');
                $stmt->execute(array($uid, ($p['category_id'] !== null ? (int)$p['category_id'] : null), $p['type'], (float)$p['amount'], $p['note'], $today));
                // On quick add, redirect back to dashboard by default (or to a safe provided redirect)
                $redirect = isset($_POST['redirect']) ? $_POST['redirect'] : '';
                if ($redirect === 'DashboardOverview.php') {
                    header('Location: DashboardOverview.php');
                    exit;
                }
                header('Location: DashboardOverview.php');
                exit;
            } else {
                $err = 'Preset not found.';
            }
        }
    } elseif ($action === 'preset_create') {
        $plabel = trim(isset($_POST['preset_label']) ? $_POST['preset_label'] : '');
        $ptype = isset($_POST['preset_type']) ? $_POST['preset_type'] : 'expense';
        $pamount = isset($_POST['preset_amount']) ? (float)$_POST['preset_amount'] : 0;
        $pcat = isset($_POST['preset_category']) && $_POST['preset_category'] !== '' ? (int)$_POST['preset_category'] : null;
        $pnote = isset($_POST['preset_note']) ? trim($_POST['preset_note']) : '';

        if ($plabel === '') {
            $err = 'Preset label is required.';
        } elseif (!in_array($ptype, array('income','expense'), true)) {
            $err = 'Invalid preset type.';
        } elseif ($pamount <= 0) {
            $err = 'Preset amount must be greater than 0.';
        } else {
            $ins = $pdo->prepare('INSERT INTO quick_presets(user_id, label, type, amount, category_id, note) VALUES(?,?,?,?,?,?)');
            $ins->execute(array($uid, $plabel, $ptype, $pamount, $pcat, $pnote));
            $msg = 'Quick preset saved.';
            // refresh presets list
            $ps = $pdo->prepare('SELECT id, label, type, amount, category_id, note FROM quick_presets WHERE user_id = ? ORDER BY created_at DESC, id DESC');
            $ps->execute([$uid]);
            $presets = $ps->fetchAll();
        }
    } elseif ($action === 'preset_delete') {
        $pid = isset($_POST['preset_id']) ? (int)$_POST['preset_id'] : 0;
        if ($pid > 0) {
            $del = $pdo->prepare('DELETE FROM quick_presets WHERE id = ? AND user_id = ?');
            $del->execute(array($pid, $uid));
            $msg = 'Preset deleted.';
            // refresh presets list
            $ps = $pdo->prepare('SELECT id, label, type, amount, category_id, note FROM quick_presets WHERE user_id = ? ORDER BY created_at DESC, id DESC');
            $ps->execute([$uid]);
            $presets = $ps->fetchAll();
        }
    } else {
        // Default: normal transaction save
        $date = isset($_POST['date']) ? $_POST['date'] : date('Y-m-d');
        $type = isset($_POST['type']) ? $_POST['type'] : 'expense';
        $category_id = isset($_POST['category']) && $_POST['category'] !== '' ? (int)$_POST['category'] : null;
        $amount = isset($_POST['amount']) ? (float)$_POST['amount'] : 0;
        $notes = isset($_POST['notes']) ? trim($_POST['notes']) : '';

        if (!in_array($type, array('income','expense'), true)) {
            $type = 'expense';
        }
        if ($amount <= 0) {
            $err = 'Amount must be greater than 0.';
        } else {
            $stmt = $pdo->prepare('INSERT INTO transactions(user_id, category_id, type, amount, note, date) VALUES(?,?,?,?,?,?)');
            $stmt->execute(array($uid, $category_id, $type, $amount, $notes, $date));
            $msg = 'Transaction saved.';
        }
    }
}

include 'includes/header.php';
?>

<header>
    <h2>Add New Transaction</h2>
</header>

<div class="card" style="max-width: 800px; margin: 0 auto;">
    <form action="TransactionEntryForm.php" method="POST">
        <div class="grid-2" style="grid-template-columns: 1fr 1fr; margin-bottom: 0;">
            <div class="form-group">
                <label>Date</label>
                <input type="date" name="date" value="<?php echo date('Y-m-d'); ?>">
            </div>
            <div class="form-group">
                <label>Type</label>
                <div style="display:flex; gap:10px; align-items:center;">
                    <label style="display:flex; align-items:center; gap:6px;"><input type="radio" name="type" value="expense" checked> Expense</label>
                    <label style="display:flex; align-items:center; gap:6px;"><input type="radio" name="type" value="income"> Income</label>
                </div>
            </div>
        </div>

        <div class="grid-2" style="grid-template-columns: 1fr 1fr; margin-bottom: 0;">
            <div class="form-group">
                <label>Category</label>
                <select name="category">
                    <option value="">Choose a Category...</option>
                    <?php foreach ($cats as $c): ?>
                        <option value="<?php echo (int)$c['id']; ?>"><?php echo e($c['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Amount</label>
                <input type="number" name="amount" placeholder="0.00" step="0.01">
            </div>
        </div>

        <div class="form-group">
            <label>Notes</label>
            <textarea name="notes" rows="3" placeholder="Add a note (optional)..."></textarea>
        </div>

        <!-- AI Receipt Scanner Integration Mockup -->
        <div class="form-group">
            <div style="border: 2px dashed #d1d5db; padding: 20px; text-align: center; border-radius: 8px; color: #6b7280; cursor: pointer;">
                <i class="fas fa-camera" style="font-size: 24px; margin-bottom: 10px;"></i><br>
                <strong>Scan Receipt</strong><br>
                <span style="font-size: 0.85rem;">Upload image to auto-fill details</span>
            </div>
        </div>

        <?php if ($msg): ?><div class="text-green" style="margin-bottom:10px; font-size:0.9rem;"><?php echo e($msg); ?></div><?php endif; ?>
        <?php if ($err): ?><div class="text-red" style="margin-bottom:10px; font-size:0.9rem;"><?php echo e($err); ?></div><?php endif; ?>
        <button type="submit" class="btn btn-primary">Save Transaction</button>
    </form>
</div>

<!-- Quick Add Presets -->
<div id="quick-add-section" style="max-width: 800px; margin: 30px auto;">
    <h4>Quick Add</h4>
    <div style="display: flex; flex-wrap: wrap; gap: 10px; margin-top: 10px;">
        <?php if (empty($presets)): ?>
            <div class="text-muted">No quick presets yet. Create one below.</div>
        <?php else: ?>
            <?php foreach ($presets as $p): ?>
                <form action="TransactionEntryForm.php" method="POST" style="margin:0;">
                    <input type="hidden" name="action" value="quick_add">
                    <input type="hidden" name="preset_id" value="<?php echo (int)$p['id']; ?>">
                    <?php $isInc = ($p['type'] === 'income'); ?>
                    <button class="btn <?php echo $isInc ? 'btn-primary' : 'btn-secondary'; ?>" style="width:auto;">
                        <i class="fas <?php echo $isInc ? 'fa-plus-circle' : 'fa-minus-circle'; ?>"></i>
                        <?php echo e($p['label']); ?>
                        <span class="badge <?php echo $isInc ? 'badge-income' : 'badge-expense'; ?>" style="margin-left:8px;"><?php echo get_currency_symbol(); ?><?php echo number_format($p['amount'],2); ?></span>
                    </button>
                </form>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Customize Quick Add -->
    <div class="card" style="margin-top:20px;">
        <h3 style="margin-bottom:10px;">Customize Quick Add</h3>
        <form action="TransactionEntryForm.php" method="POST" class="grid-2" style="grid-template-columns: 2fr 1fr; gap: 15px;">
            <input type="hidden" name="action" value="preset_create">
            <div class="form-group">
                <label>Label</label>
                <input type="text" name="preset_label" placeholder="e.g., Morning Coffee" required>
            </div>
            <div class="form-group">
                <label>Type</label>
                <select name="preset_type">
                    <option value="expense">Expense</option>
                    <option value="income">Income</option>
                </select>
            </div>
            <div class="form-group">
                <label>Category</label>
                <select name="preset_category">
                    <option value="">Choose a Category...</option>
                    <?php foreach ($cats as $c): ?>
                        <option value="<?php echo (int)$c['id']; ?>"><?php echo e($c['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Amount</label>
                <input type="number" name="preset_amount" step="0.01" placeholder="0.00" required>
            </div>
            <div class="form-group" style="grid-column: 1 / -1;">
                <label>Note (optional)</label>
                <input type="text" name="preset_note" placeholder="Short note to save with the transaction">
            </div>
            <div style="grid-column: 1 / -1; text-align:right;">
                <button class="btn btn-primary" style="width:auto;">
                    <i class="fas fa-save"></i> Save Preset
                </button>
            </div>
        </form>

        <?php if (!empty($presets)): ?>
            <div class="mt-2">
                <h4 style="margin:10px 0;">Your Presets</h4>
                <div style="display:flex; flex-direction:column; gap:8px;">
                    <?php foreach ($presets as $p): ?>
                        <div style="display:flex; align-items:center; gap:10px;">
                            <div style="flex:1;">
                                <strong><?php echo e($p['label']); ?></strong>
                                <span class="text-muted" style="margin-left:8px; font-size:0.9rem;"><?php echo get_currency_symbol(); ?><?php echo number_format($p['amount'],2); ?> • <?php echo e($p['type']); ?><?php if (!empty($p['note'])): ?> • <?php echo e($p['note']); ?><?php endif; ?></span>
                            </div>
                            <form action="TransactionEntryForm.php" method="POST" onsubmit="return confirm('Delete this preset?');" style="margin:0;">
                                <input type="hidden" name="action" value="preset_delete">
                                <input type="hidden" name="preset_id" value="<?php echo (int)$p['id']; ?>">
                                <button class="btn btn-secondary" style="width:auto; padding:6px 10px;" title="Delete">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
