<?php
/**
 * Handle automatic recurring income transaction logging
 * Should be called periodically (e.g., on page load for admin, or via cron)
 */

function processRecurringIncome($pdo, $userId = null) {
    try {
        $today = new DateTime();
        $currentDay = (int)$today->format('d');
        $currentMonth = $today->format('Y-m');
        
        // If userId is provided, only process that user; otherwise process all users
        if ($userId) {
            $sql = "SELECT id, user_id, name, recurring_frequency, recurring_amount, recurring_date, last_recurring_date 
                    FROM categories 
                    WHERE user_id = ? AND type = 'income' AND recurring_frequency IS NOT NULL AND recurring_amount IS NOT NULL AND recurring_date IS NOT NULL";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$userId]);
        } else {
            $sql = "SELECT id, user_id, name, recurring_frequency, recurring_amount, recurring_date, last_recurring_date 
                    FROM categories 
                    WHERE type = 'income' AND recurring_frequency IS NOT NULL AND recurring_amount IS NOT NULL AND recurring_date IS NOT NULL";
            $stmt = $pdo->query($sql);
        }
        
        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $processed = 0;
        
        foreach ($categories as $cat) {
            $catId = (int)$cat['id'];
            $catUserId = (int)$cat['user_id'];
            $frequency = $cat['recurring_frequency'];
            $amount = (float)$cat['recurring_amount'];
            $dayOfMonth = (int)$cat['recurring_date'];
            $lastDate = $cat['last_recurring_date'];
            
            $shouldProcess = false;
            $transactionDate = null;
            
            // Determine if we should process based on frequency
            switch ($frequency) {
                case 'weekly':
                    // Process on the specified day of week (1-7, Monday-Sunday)
                    $targetDayOfWeek = min(7, max(1, $dayOfMonth)); // Normalize to 1-7
                    $todayDayOfWeek = (int)$today->format('N'); // 1=Monday, 7=Sunday
                    if ($todayDayOfWeek === $targetDayOfWeek && (!$lastDate || strtotime($lastDate) < strtotime($today->format('Y-m-d')))) {
                        $shouldProcess = true;
                        $transactionDate = $today->format('Y-m-d');
                    }
                    break;
                    
                case 'biweekly':
                    // Process every other week on the specified day
                    if ($currentDay === $dayOfMonth && (!$lastDate || strtotime($lastDate) < strtotime('-14 days', strtotime($today->format('Y-m-d'))))) {
                        $shouldProcess = true;
                        $transactionDate = $today->format('Y-m-d');
                    }
                    break;
                    
                case 'monthly':
                    // Process on the specified day of month
                    if ($currentDay === $dayOfMonth && (!$lastDate || substr($lastDate, 0, 7) !== $currentMonth)) {
                        $shouldProcess = true;
                        $transactionDate = $today->format('Y-m-d');
                    }
                    break;
                    
                case 'quarterly':
                    // Process 4 times a year (every 3 months) on specified day
                    $currentMonthNum = (int)$today->format('m');
                    $currentYear = $today->format('Y');
                    $isQuarterStart = in_array($currentMonthNum, [1, 4, 7, 10]);
                    if ($isQuarterStart && $currentDay === $dayOfMonth && (!$lastDate || strtotime($lastDate) < strtotime('-3 months', strtotime($today->format('Y-m-d'))))) {
                        $shouldProcess = true;
                        $transactionDate = $today->format('Y-m-d');
                    }
                    break;
                    
                case 'yearly':
                    // Process once a year on specified day/month
                    // Use recurring_date as day, and we'll use current month or assume January
                    $targetDate = new DateTime($today->format('Y') . '-01-' . str_pad($dayOfMonth, 2, '0', STR_PAD_LEFT));
                    if ($today >= $targetDate && (!$lastDate || substr($lastDate, 0, 4) !== $today->format('Y'))) {
                        $shouldProcess = true;
                        $transactionDate = $targetDate->format('Y-m-d');
                    }
                    break;
            }
            
            // Create the transaction if conditions are met
            if ($shouldProcess && $transactionDate) {
                try {
                    $insertStmt = $pdo->prepare("INSERT INTO transactions(user_id, category_id, type, amount, note, date) 
                                                 VALUES(?, ?, 'income', ?, ?, ?)");
                    $note = "Auto: " . ucfirst($frequency) . " recurring income";
                    $insertStmt->execute([$catUserId, $catId, $amount, $note, $transactionDate]);
                    
                    // Update last_recurring_date
                    $updateStmt = $pdo->prepare("UPDATE categories SET last_recurring_date = ? WHERE id = ?");
                    $updateStmt->execute([$transactionDate, $catId]);
                    
                    $processed++;
                } catch (Exception $e) {
                    // Log error but continue processing other categories
                    error_log("Error processing recurring income for category {$catId}: " . $e->getMessage());
                }
            }
        }
        
        return $processed;
    } catch (Exception $e) {
        error_log("Error in processRecurringIncome: " . $e->getMessage());
        return 0;
    }
}
?>
