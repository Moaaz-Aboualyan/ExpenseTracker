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

// Load categories separated by type
$catsStmt = $pdo->prepare('SELECT id, name, type FROM categories WHERE user_id = ? ORDER BY type DESC, name');
$catsStmt->execute([$uid]);
$allCats = $catsStmt->fetchAll();
$expenseCats = [];
$incomeCats = [];
foreach ($allCats as $c) {
    if ($c['type'] === 'income') {
        $incomeCats[] = $c;
    } else {
        $expenseCats[] = $c;
    }
}

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
        if ($category_id === null) {
            $err = 'Please select a category.';
        } elseif ($amount <= 0) {
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
                <input type="date" name="date" value="<?php echo date('Y-m-d'); ?>" required>
            </div>
            <div class="form-group">
                <label>Type</label>
                <div style="display:flex; gap:10px; align-items:center;">
                    <label style="display:flex; align-items:center; gap:6px; cursor:pointer;">
                        <input type="radio" name="type" value="expense" checked onchange="updateCategoryOptions()"> 
                        <i class="fas fa-minus-circle" style="color:#ef4444;"></i> Expense
                    </label>
                    <label style="display:flex; align-items:center; gap:6px; cursor:pointer;">
                        <input type="radio" name="type" value="income" onchange="updateCategoryOptions()"> 
                        <i class="fas fa-plus-circle" style="color:#10b981;"></i> Income
                    </label>
                </div>
            </div>
        </div>

        <div class="grid-2" style="grid-template-columns: 1fr 1fr; margin-bottom: 0;">
            <div class="form-group">
                <label>Category</label>
                <select name="category" id="categorySelect" required>
                    <option value="">Choose a Category...</option>
                    <?php if (!empty($expenseCats)): ?>
                        <optgroup label="Expenses">
                            <?php foreach ($expenseCats as $c): ?>
                                <option value="<?php echo (int)$c['id']; ?>" data-type="expense"><?php echo e($c['name']); ?></option>
                            <?php endforeach; ?>
                        </optgroup>
                    <?php endif; ?>
                    <?php if (!empty($incomeCats)): ?>
                        <optgroup label="Income">
                            <?php foreach ($incomeCats as $c): ?>
                                <option value="<?php echo (int)$c['id']; ?>" data-type="income"><?php echo e($c['name']); ?></option>
                            <?php endforeach; ?>
                        </optgroup>
                    <?php endif; ?>
                </select>
                <small class="text-muted" style="display:block; margin-top:4px; font-size:0.8rem;" id="recurringNote" style="display:none;"></small>
            </div>
            <div class="form-group">
                <label>Amount</label>
                <input type="number" name="amount" placeholder="0.00" step="0.01" required>
            </div>
        </div>

        <div class="form-group">
            <label>Notes</label>
            <textarea name="notes" id="notesInput" rows="3" placeholder="Add a note (optional)..."></textarea>
        </div>

        <!-- Receipt OCR Scanner -->
        <div class="form-group">
            <label>Receipt Image (Optional)</label>
            <div id="receipt-upload-area" style="border: 2px dashed #d1d5db; padding: 20px; text-align: center; border-radius: 8px; color: #6b7280; cursor: pointer; transition: all 0.3s;">
                <input type="file" id="receipt-input" accept="image/jpeg,image/jpg,image/png,image/webp" style="display: none;">
                <i class="fas fa-camera" style="font-size: 24px; margin-bottom: 10px;"></i><br>
                <strong>Scan Receipt with OCR</strong><br>
                <span style="font-size: 0.85rem;">Upload image to auto-fill transaction details</span><br>
                <small style="font-size: 0.75rem; color: #9ca3af; margin-top: 5px; display: inline-block;">Supports JPEG, PNG, WEBP (max 5MB)</small>
            </div>
            <div id="receipt-preview" style="display: none; margin-top: 15px;">
                <div style="display: flex; gap: 15px; align-items: start;">
                    <img id="preview-image" src="" alt="Receipt preview" style="max-width: 200px; max-height: 200px; border-radius: 8px; border: 1px solid #e5e7eb;">
                    <div style="flex: 1;">
                        <h4 style="margin: 0 0 10px 0;">Processing Receipt...</h4>
                        <div id="ocr-status" style="font-size: 0.9rem; color: #6b7280;">
                            <i class="fas fa-spinner fa-spin"></i> Analyzing receipt with OCR...
                        </div>
                        <div id="ocr-results" style="margin-top: 10px; display: none;"></div>
                    </div>
                    <button type="button" id="clear-receipt" class="btn btn-secondary" style="width: auto; padding: 8px 12px;">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
        </div>

        <?php if ($msg): ?><div class="text-green" style="margin-bottom:10px; font-size:0.9rem;"><?php echo e($msg); ?></div><?php endif; ?>
        <?php if ($err): ?><div class="text-red" style="margin-bottom:10px; font-size:0.9rem;"><?php echo e($err); ?></div><?php endif; ?>
        <button type="submit" class="btn btn-primary" style="width:100%;">
            <i class="fas fa-save"></i> Save Transaction
        </button>
    </form>
</div>

<script>
function updateCategoryOptions() {
    const typeRadios = document.querySelectorAll('input[name="type"]');
    const selectedType = Array.from(typeRadios).find(r => r.checked)?.value || 'expense';
    const categorySelect = document.getElementById('categorySelect');
    const optgroups = categorySelect.querySelectorAll('optgroup');
    const options = categorySelect.querySelectorAll('option:not([value=""])');
    
    // Hide/show optgroups based on type
    optgroups.forEach(optgroup => {
        const groupType = optgroup.getAttribute('label').toLowerCase();
        const matchesType = (selectedType === 'expense' && groupType === 'expenses') || 
                           (selectedType === 'income' && groupType === 'income');
        optgroup.style.display = matchesType ? 'block' : 'none';
    });
    
    // Hide/show options based on type
    options.forEach(option => {
        const optionType = option.getAttribute('data-type');
        option.style.display = optionType === selectedType ? 'block' : 'none';
    });
    
    // Reset to empty if current selection doesn't match type
    const currentOption = categorySelect.selectedOptions[0];
    if (currentOption && currentOption.value !== '' && currentOption.getAttribute('data-type') !== selectedType) {
        categorySelect.value = '';
    }
}
updateCategoryOptions();

// Receipt OCR Upload Handler
const uploadArea = document.getElementById('receipt-upload-area');
const fileInput = document.getElementById('receipt-input');
const previewContainer = document.getElementById('receipt-preview');
const previewImage = document.getElementById('preview-image');
const ocrStatus = document.getElementById('ocr-status');
const ocrResults = document.getElementById('ocr-results');
const clearButton = document.getElementById('clear-receipt');

// Click to upload
uploadArea.addEventListener('click', () => fileInput.click());

// Drag and drop
uploadArea.addEventListener('dragover', (e) => {
    e.preventDefault();
    uploadArea.style.borderColor = '#3b82f6';
    uploadArea.style.backgroundColor = 'rgba(59, 130, 246, 0.05)';
});

uploadArea.addEventListener('dragleave', () => {
    uploadArea.style.borderColor = '#d1d5db';
    uploadArea.style.backgroundColor = 'transparent';
});

uploadArea.addEventListener('drop', (e) => {
    e.preventDefault();
    uploadArea.style.borderColor = '#d1d5db';
    uploadArea.style.backgroundColor = 'transparent';
    
    const files = e.dataTransfer.files;
    if (files.length > 0) {
        fileInput.files = files;
        handleFileUpload(files[0]);
    }
});

// File input change
fileInput.addEventListener('change', (e) => {
    if (e.target.files.length > 0) {
        handleFileUpload(e.target.files[0]);
    }
});

// Clear receipt
clearButton.addEventListener('click', () => {
    fileInput.value = '';
    previewContainer.style.display = 'none';
    uploadArea.style.display = 'block';
    ocrResults.style.display = 'none';
    ocrResults.innerHTML = '';
});

// Handle file upload and OCR
async function handleFileUpload(file) {
    // Validate file type
    const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
    if (!allowedTypes.includes(file.type)) {
        alert('Invalid file type. Please upload a JPEG, PNG, or WEBP image.');
        return;
    }
    
    // Validate file size (5MB)
    if (file.size > 5 * 1024 * 1024) {
        alert('File size exceeds 5MB limit.');
        return;
    }
    
    // Show preview
    const reader = new FileReader();
    reader.onload = (e) => {
        previewImage.src = e.target.result;
        uploadArea.style.display = 'none';
        previewContainer.style.display = 'block';
        ocrStatus.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Analyzing receipt with OCR...';
        ocrResults.style.display = 'none';
    };
    reader.readAsDataURL(file);
    
    // Upload and process
    const formData = new FormData();
    formData.append('receipt_image', file);
    
    try {
        const response = await fetch('upload_receipt.php', {
            method: 'POST',
            body: formData
        });
        
        // First check if response is valid
        const responseText = await response.text();
        
        // Try to parse JSON
        let result;
        try {
            result = JSON.parse(responseText);
        } catch (parseError) {
            // JSON parse failed - server error
            console.error('Invalid JSON response:', responseText);
            ocrStatus.innerHTML = '<i class="fas fa-times-circle" style="color: #ef4444;"></i> Server error';
            ocrResults.style.display = 'block';
            ocrResults.innerHTML = '<p style="color: #ef4444; font-size: 0.85rem;">Server returned invalid response. Please check:<br>1. API key is configured in .env file<br>2. Server error logs<br>3. PHP configuration</p>';
            return;
        }
        
        if (result.success) {
            ocrStatus.innerHTML = '<i class="fas fa-check-circle" style="color: #10b981;"></i> Receipt processed successfully!';
            displayOCRResults(result.data);
            autoFillForm(result.data);
        } else {
            ocrStatus.innerHTML = '<i class="fas fa-exclamation-triangle" style="color: #f59e0b;"></i> ' + result.message;
            ocrResults.style.display = 'block';
            
            // Check if it's an API key error
            if (result.message.includes('API key') || result.message.includes('not configured')) {
                ocrResults.innerHTML = '<p style="color: #ef4444; font-size: 0.85rem;"><strong>API Key Not Configured</strong><br>Please add your Google Vision API key to the .env file. See documentation for setup instructions.</p>';
            } else {
                ocrResults.innerHTML = '<p style="color: #ef4444; font-size: 0.85rem;">OCR processing failed: ' + result.message + '<br>You can still fill in the form manually.</p>';
            }
        }
    } catch (error) {
        ocrStatus.innerHTML = '<i class="fas fa-times-circle" style="color: #ef4444;"></i> Upload failed';
        ocrResults.style.display = 'block';
        ocrResults.innerHTML = '<p style="color: #ef4444; font-size: 0.85rem;">Error: ' + error.message + '<br>Please check your internet connection and try again.</p>';
        console.error('Upload error:', error);
    }
}

// Display OCR extracted data
function displayOCRResults(data) {
    ocrResults.style.display = 'block';
    
    let html = '<div style="background: #f9fafb; padding: 12px; border-radius: 6px; font-size: 0.85rem;">';
    html += '<strong style="display: block; margin-bottom: 8px;">Extracted Data:</strong>';
    
    if (data.amount) {
        html += '<div><i class="fas fa-dollar-sign" style="width: 20px;"></i> <strong>Amount:</strong> $' + data.amount.toFixed(2) + '</div>';
    }
    
    if (data.date) {
        html += '<div><i class="fas fa-calendar" style="width: 20px;"></i> <strong>Date:</strong> ' + data.date + '</div>';
    }
    
    if (data.merchant) {
        html += '<div><i class="fas fa-store" style="width: 20px;"></i> <strong>Merchant:</strong> ' + data.merchant + '</div>';
    }
    
    if (data.suggested_category) {
        html += '<div><i class="fas fa-tag" style="width: 20px; color: #10b981;"></i> <strong>Category:</strong> Auto-selected ✓</div>';
    }
    
    if (data.items && data.items.length > 0) {
        html += '<div style="margin-top: 8px;"><strong>Items:</strong><ul style="margin: 5px 0; padding-left: 20px;">';
        data.items.forEach(item => {
            html += '<li>' + item.name + ' - $' + item.price.toFixed(2) + '</li>';
        });
        html += '</ul></div>';
    }
    
    html += '<small style="display: block; margin-top: 8px; color: #6b7280;">Review and adjust the auto-filled values before saving.</small>';
    html += '</div>';
    
    ocrResults.innerHTML = html;
}

// Auto-fill form with OCR data
function autoFillForm(data) {
    // Fill amount
    if (data.amount) {
        const amountInput = document.querySelector('input[name="amount"]');
        if (amountInput) {
            amountInput.value = data.amount.toFixed(2);
        }
    }
    
    // Fill date
    if (data.date) {
        const dateInput = document.querySelector('input[name="date"]');
        if (dateInput) {
            dateInput.value = data.date;
        }
    }
    
    // Auto-select category if suggested
    if (data.suggested_category) {
        const categorySelect = document.getElementById('categorySelect');
        if (categorySelect) {
            categorySelect.value = data.suggested_category;
            // Trigger change event to update any dependent UI
            categorySelect.dispatchEvent(new Event('change'));
        }
    }
    
    // Fill notes with merchant name
    if (data.merchant) {
        const notesInput = document.getElementById('notesInput');
        if (notesInput && !notesInput.value) {
            notesInput.value = data.merchant;
        }
    }
    
    // Highlight filled fields briefly
    setTimeout(() => {
        const filledElements = [];
        
        // Highlight amount and date inputs
        const amountInput = document.querySelector('input[name="amount"]');
        const dateInput = document.querySelector('input[name="date"]');
        if (amountInput && amountInput.value) filledElements.push(amountInput);
        if (dateInput && dateInput.value) filledElements.push(dateInput);
        
        // Highlight category select if auto-selected
        if (data.suggested_category) {
            const categorySelect = document.getElementById('categorySelect');
            if (categorySelect) filledElements.push(categorySelect);
        }
        
        filledElements.forEach(element => {
            element.style.transition = 'background-color 0.5s';
            element.style.backgroundColor = '#dcfce7';
            setTimeout(() => {
                element.style.backgroundColor = '';
            }, 2000);
        });
    }, 100);
}
</script>

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
                <select name="preset_type" id="presetTypeSelect" onchange="updatePresetCategoryOptions();">
                    <option value="expense">Expense</option>
                    <option value="income">Income</option>
                </select>
            </div>
            <div class="form-group">
                <label>Category</label>
                <select name="preset_category" id="presetCategorySelect" required>
                    <option value="">Choose a Category...</option>
                    <?php if (!empty($expenseCats)): ?>
                        <optgroup label="Expenses" data-type="expense">
                            <?php foreach ($expenseCats as $c): ?>
                                <option value="<?php echo (int)$c['id']; ?>" data-type="expense"><?php echo e($c['name']); ?></option>
                            <?php endforeach; ?>
                        </optgroup>
                    <?php endif; ?>
                    <?php if (!empty($incomeCats)): ?>
                        <optgroup label="Income" data-type="income">
                            <?php foreach ($incomeCats as $c): ?>
                                <option value="<?php echo (int)$c['id']; ?>" data-type="income"><?php echo e($c['name']); ?></option>
                            <?php endforeach; ?>
                        </optgroup>
                    <?php endif; ?>
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
                                <span class="text-muted" style="margin-left:8px; font-size:0.9rem;">
                                    <i class="fas <?php echo $p['type'] === 'income' ? 'fa-plus-circle' : 'fa-minus-circle'; ?>" style="color:<?php echo $p['type'] === 'income' ? '#10b981' : '#ef4444'; ?>;"></i>
                                    <?php echo get_currency_symbol(); ?><?php echo number_format($p['amount'],2); ?>
                                    <span style="color:#999;"> • <?php echo ucfirst($p['type']); ?></span>
                                    <?php if (!empty($p['note'])): ?><span style="color:#999;"> • <?php echo e($p['note']); ?></span><?php endif; ?>
                                </span>
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

<script>
function updatePresetCategoryOptions() {
    const typeSelect = document.getElementById('presetTypeSelect');
    const selectedType = typeSelect.value;
    const categorySelect = document.getElementById('presetCategorySelect');
    const optgroups = categorySelect.querySelectorAll('optgroup');
    const options = categorySelect.querySelectorAll('option:not([value=""])');
    
    optgroups.forEach(optgroup => {
        const groupType = optgroup.getAttribute('data-type');
        optgroup.style.display = groupType === selectedType ? 'block' : 'none';
    });
    
    options.forEach(option => {
        const optionType = option.getAttribute('data-type');
        option.style.display = optionType === selectedType ? 'block' : 'none';
    });
    
    // Reset to empty
    categorySelect.value = '';
}

// Initialize category display on page load (default to expense)
document.addEventListener('DOMContentLoaded', updatePresetCategoryOptions);
</script>

<?php include 'includes/footer.php'; ?>
