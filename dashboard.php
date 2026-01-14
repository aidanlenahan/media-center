<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

requireAdmin();

// Get user role
$stmt = $pdo->prepare("SELECT role FROM librarians WHERE id = ?");
$stmt->execute([$_SESSION['librarian_id']]);
$userRole = $stmt->fetchColumn();

$librarian = $_SESSION['librarian_username'];
$action = $_GET['action'] ?? 'overview';
$message = '';
$messageType = '';

// Handle admin actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postAction = $_POST['action'] ?? $action;
    
    if ($postAction === 'settings') {
        try {
            $formAutoOpen = isset($_POST['form_auto_open']) ? 1 : 0;
            $disableWeekends = isset($_POST['disable_weekends']) ? 1 : 0;
            $formOpenTime = $_POST['form_open_time'] ?? '07:30';
            $formCloseTime = $_POST['form_close_time'] ?? '14:30';
            $autoApproval = isset($_POST['auto_approval']) ? 1 : 0;
            $recentEntriesLimit = (int)($_POST['recent_entries_limit'] ?? 10);
            
            // Validate limit between 5 and 50
            if ($recentEntriesLimit < 5) $recentEntriesLimit = 5;
            if ($recentEntriesLimit > 50) $recentEntriesLimit = 50;
            
            $stmt = $pdo->prepare("
                UPDATE settings 
                SET form_auto_open = ?, disable_weekends = ?, form_open_time = ?, form_close_time = ?, auto_approval = ?, recent_entries_limit = ?
                WHERE id = 1
            ");
            $stmt->execute([$formAutoOpen, $disableWeekends, $formOpenTime . ':00', $formCloseTime . ':00', $autoApproval, $recentEntriesLimit]);
            
            $message = 'Settings updated successfully.';
            $messageType = 'success';
        } catch (Exception $e) {
            $message = 'Error updating settings.';
            $messageType = 'danger';
        }
    } elseif ($postAction === 'override_status') {
        try {
            $formStatusOverride = isset($_POST['form_status_override']) ? 1 : 0;
            $formStatusManual = $_POST['form_status_manual'] ?? 'open';
            
            $stmt = $pdo->prepare("
                UPDATE settings 
                SET form_status_override = ?, form_status_manual = ?
                WHERE id = 1
            ");
            $stmt->execute([$formStatusOverride, $formStatusManual]);
            
            $message = 'Form status override updated successfully.';
            $messageType = 'success';
        } catch (Exception $e) {
            $message = 'Error updating override settings.';
            $messageType = 'danger';
        }
    } elseif ($postAction === 'approve_passes') {
        try {
            $passIds = $_POST['pass_ids'] ?? [];
            if (!empty($passIds)) {
                $placeholders = implode(',', array_fill(0, count($passIds), '?'));
                
                // Get passes to send emails
                $stmt = $pdo->prepare("
                    SELECT id, email, first_name, last_name, pass_code, `mod`, activities 
                    FROM passes_current 
                    WHERE id IN ($placeholders) AND status = 'pending'
                ");
                $stmt->execute($passIds);
                $passes = $stmt->fetchAll();
                
                // Update status and send emails
                foreach ($passes as $pass) {
                    $activities = json_decode($pass['activities'], true);
                    sendPassEmail($pass['email'], $pass['first_name'], $pass['last_name'], 
                                 $pass['pass_code'], $pass['mod'], $activities);
                    
                    $updateStmt = $pdo->prepare("
                        UPDATE passes_current 
                        SET status = 'approved', sent_at = NOW()
                        WHERE id = ?
                    ");
                    $updateStmt->execute([$pass['id']]);
                }
                
                $message = count($passes) . ' pass(es) approved and emails sent.';
                $messageType = 'success';
            }
        } catch (Exception $e) {
            $message = 'Error approving passes.';
            $messageType = 'danger';
        }
    } elseif ($postAction === 'reject_passes') {
        try {
            $passIds = $_POST['pass_ids'] ?? [];
            if (!empty($passIds)) {
                $placeholders = implode(',', array_fill(0, count($passIds), '?'));
                
                $stmt = $pdo->prepare("
                    UPDATE passes_current 
                    SET status = 'rejected'
                    WHERE id IN ($placeholders)
                ");
                $stmt->execute($passIds);
                
                $message = count($passIds) . ' pass(es) rejected.';
                $messageType = 'success';
            }
        } catch (Exception $e) {
            $message = 'Error rejecting passes.';
            $messageType = 'danger';
        }
    } elseif ($postAction === 'delete_pass') {
        try {
            $passId = (int)$_POST['pass_id'];
            $stmt = $pdo->prepare("DELETE FROM passes_current WHERE id = ?");
            $stmt->execute([$passId]);
            $message = 'Pass deleted.';
            $messageType = 'success';
        } catch (Exception $e) {
            $message = 'Error deleting pass.';
            $messageType = 'danger';
        }
    }
}

// Get current settings
$settings = getSettings($pdo);

// Auto-reject old pending passes if auto-approval is disabled
if (!$settings['auto_approval']) {
    try {
        $pdo->exec("
            UPDATE passes_current 
            SET status = 'rejected' 
            WHERE status = 'pending' 
            AND DATE(created_at) < CURDATE()
        ");
    } catch (Exception $e) {
        error_log("Error auto-rejecting old pending passes: " . $e->getMessage());
    }
}

// Get current passes
$currentPassesStmt = $pdo->query("
    SELECT * FROM passes_current 
    ORDER BY created_at DESC 
    LIMIT 100
");
$currentPasses = $currentPassesStmt->fetchAll();

// Count passes by status
$countStmt = $pdo->query("
    SELECT 
        status, 
        COUNT(*) as count 
    FROM passes_current 
    GROUP BY status
");
$statusCounts = [];
while ($row = $countStmt->fetch()) {
    $statusCounts[$row['status']] = $row['count'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Media Center Pass System</title>
    <link rel="icon" type="image/svg+xml" href="img/buc.svg">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Librarian Dashboard</h1>
            <p>Welcome, <?php echo htmlspecialchars($librarian); ?></p>
        </div>
        
        <!-- Navigation -->
        <nav style="background: #f8f9fa; border-radius: 8px; padding: 10px 15px; margin-bottom: 25px; display: flex; align-items: center; gap: 5px; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
            <a href="?action=overview" style="padding: 10px 18px; text-decoration: none; color: #333; border-radius: 6px; transition: all 0.2s; <?php echo ($action === 'overview' || empty($action)) ? 'background: #690000; color: white; font-weight: 500;' : 'background: transparent;'; ?>" onmouseover="if(this.style.background !== 'rgb(105, 0, 0)') this.style.background='#e9ecef'" onmouseout="if(this.style.background !== 'rgb(105, 0, 0)') this.style.background='transparent'">Overview</a>
            <a href="?action=passes" style="padding: 10px 18px; text-decoration: none; color: #333; border-radius: 6px; transition: all 0.2s; <?php echo $action === 'passes' ? 'background: #690000; color: white; font-weight: 500;' : 'background: transparent;'; ?>" onmouseover="if(this.style.background !== 'rgb(105, 0, 0)') this.style.background='#e9ecef'" onmouseout="if(this.style.background !== 'rgb(105, 0, 0)') this.style.background='transparent'">Manage Passes</a>
            <a href="?action=history" style="padding: 10px 18px; text-decoration: none; color: #333; border-radius: 6px; transition: all 0.2s; <?php echo $action === 'history' ? 'background: #690000; color: white; font-weight: 500;' : 'background: transparent;'; ?>" onmouseover="if(this.style.background !== 'rgb(105, 0, 0)') this.style.background='#e9ecef'" onmouseout="if(this.style.background !== 'rgb(105, 0, 0)') this.style.background='transparent'">History</a>
            <a href="?action=settings" style="padding: 10px 18px; text-decoration: none; color: #333; border-radius: 6px; transition: all 0.2s; <?php echo $action === 'settings' ? 'background: #690000; color: white; font-weight: 500;' : 'background: transparent;'; ?>" onmouseover="if(this.style.background !== 'rgb(105, 0, 0)') this.style.background='#e9ecef'" onmouseout="if(this.style.background !== 'rgb(105, 0, 0)') this.style.background='transparent'">Settings</a>
            <?php if ($userRole === 'root'): ?>
            <a href="dev_panel.php" style="padding: 10px 18px; text-decoration: none; color: white; background: #e74c3c; border-radius: 6px; transition: all 0.2s; font-weight: 500;" onmouseover="this.style.background='#c0392b'" onmouseout="this.style.background='#e74c3c'">⚙️ Developer</a>
            <?php endif; ?>
            <div style="flex: 1;"></div>
            <a href="form.php" style="padding: 10px 18px; text-decoration: none; background: #690000; color: white; border-radius: 6px; transition: all 0.2s;" target="_blank" onmouseover="this.style.background='#5e0000ff'" onmouseout="this.style.background='#690000'">View Form</a>
            <a href="logout.php" style="padding: 10px 18px; text-decoration: none; background: #6c757d; color: white; border-radius: 6px; transition: all 0.2s;" onmouseover="this.style.background='#5a6268'" onmouseout="this.style.background='#6c757d'">Logout</a>
        </nav>
        
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <!-- Overview Tab -->
        <?php if ($action === 'overview' || empty($action)): ?>
            <h2 style="margin-bottom: 20px;">Today's Summary</h2>
            
            <div class="dashboard-grid">
                <div class="card">
                    <h3>Pending Passes</h3>
                    <div class="card-value"><?php echo $statusCounts['pending'] ?? 0; ?></div>
                    <p>Awaiting approval</p>
                </div>
                
                <div class="card">
                    <h3>Approved Passes</h3>
                    <div class="card-value"><?php echo $statusCounts['approved'] ?? 0; ?></div>
                    <p>Sent to students</p>
                </div>
                
                <div class="card">
                    <h3>Rejected Passes</h3>
                    <div class="card-value"><?php echo $statusCounts['rejected'] ?? 0; ?></div>
                    <p>Not approved</p>
                </div>
                
                <div class="card" style="position: relative;">
                    <h3>Form Status
                        <button onclick="document.getElementById('statusOverrideModal').style.display='block'" 
                                style="border: none; background: none; cursor: pointer; font-size: 0.9em; color: #666; padding: 0 5px;"
                                title="Override Settings">⚙️</button>
                    </h3>
                    <div class="card-value"><?php echo isFormOpen($pdo) ? 'OPEN' : 'CLOSED'; ?></div>
                    <p>
                        <?php if ($settings['form_status_override']): ?>
                            <span style="color: #856404; font-weight: bold;">⚠️ Manual Override</span>
                        <?php else: ?>
                            <?php echo $settings['form_open_time'] . ' - ' . $settings['form_close_time']; ?>
                        <?php endif; ?>
                    </p>
                </div>
            </div>
            
            <h2 style="margin-top: 40px; margin-bottom: 20px;">Recent Submissions</h2>
            <table>
                <thead>
                    <tr>
                        <th>Student Name</th>
                        <th>Email</th>
                        <th>Mod</th>
                        <th>Teacher</th>
                        <th>Status</th>
                        <th>Submitted</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $recentLimit = $settings['recent_entries_limit'] ?? 10;
                    foreach (array_slice($currentPasses, 0, $recentLimit) as $pass): 
                    ?>
                        <tr>
                            <td><?php echo htmlspecialchars($pass['first_name'] . ' ' . $pass['last_name']); ?></td>
                            <td><?php echo htmlspecialchars($pass['email']); ?></td>
                            <td><?php echo $pass['mod']; ?></td>
                            <td><?php echo htmlspecialchars($pass['teacher_name']); ?></td>
                            <td>
                                <span style="padding: 5px 10px; border-radius: 3px; 
                                    <?php
                                    if ($pass['status'] === 'approved') echo 'background: #d4edda; color: #155724;';
                                    elseif ($pass['status'] === 'pending') echo 'background: #fff3cd; color: #856404;';
                                    else echo 'background: #f8d7da; color: #721c24;';
                                    ?>
                                ">
                                    <?php echo ucfirst($pass['status']); ?>
                                </span>
                            </td>
                            <td><?php echo date('M d, H:i', strtotime($pass['created_at'])); ?></td>
                            <td>
                                <?php if ($pass['status'] === 'pending'): ?>
                                    <form method="POST" action="" style="display: inline;">
                                        <input type="hidden" name="action" value="approve_passes">
                                        <input type="hidden" name="pass_ids[]" value="<?php echo $pass['id']; ?>">
                                        <button type="submit" class="btn btn-success" style="padding: 5px 10px; font-size: 0.85em;">Approve</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        
        <!-- Settings Tab -->
        <?php elseif ($action === 'settings'): ?>
            <h2 style="margin-bottom: 20px;">System Settings</h2>
            
            <form method="POST" action="?action=settings" style="max-width: 600px;">
                <div class="form-group">
                    <label style="display: flex; align-items: center; margin-bottom: 15px;">
                        <input type="checkbox" name="form_auto_open" id="form_auto_open" 
                               <?php echo $settings['form_auto_open'] ? 'checked' : ''; ?> 
                               onchange="toggleWeekendDisable()"
                               style="width: auto; margin-right: 10px;">
                        <span>Enable automatic form opening/closing</span>
                    </label>
                </div>
                
                <div class="form-group">
                    <label style="display: flex; align-items: center; margin-bottom: 15px; <?php echo !$settings['form_auto_open'] ? 'opacity: 0.5;' : ''; ?>">
                        <input type="checkbox" name="disable_weekends" id="disable_weekends" 
                               <?php echo $settings['disable_weekends'] ? 'checked' : ''; ?> 
                               <?php echo !$settings['form_auto_open'] ? 'disabled' : ''; ?>
                               style="width: auto; margin-right: 10px;">
                        <span>Disable form on weekends</span>
                    </label>
                    <small style="color: #666;">Form will be closed on Saturdays and Sundays</small>
                </div>
                
                <div class="form-group" style="<?php echo !$settings['form_auto_open'] ? 'opacity: 0.5;' : ''; ?>">
                    <label for="form_open_time">Form Opens At <span class="required">*</span></label>
                    <input type="time" id="form_open_time" name="form_open_time" 
                           value="<?php echo substr($settings['form_open_time'], 0, 5); ?>" 
                           <?php echo !$settings['form_auto_open'] ? 'disabled' : 'required'; ?>>
                </div>
                
                <div class="form-group" style="<?php echo !$settings['form_auto_open'] ? 'opacity: 0.5;' : ''; ?>">
                    <label for="form_close_time">Form Closes At <span class="required">*</span></label>
                    <input type="time" id="form_close_time" name="form_close_time" 
                           value="<?php echo substr($settings['form_close_time'], 0, 5); ?>" 
                           <?php echo !$settings['form_auto_open'] ? 'disabled' : 'required'; ?>>
                </div>
                
                <div class="form-group">
                    <label style="display: flex; align-items: center; margin-bottom: 15px;">
                        <input type="checkbox" name="auto_approval" <?php echo $settings['auto_approval'] ? 'checked' : ''; ?> 
                               style="width: auto; margin-right: 10px;">
                        <span>Automatically approve and send passes immediately</span>
                    </label>
                    <small style="color: #666;">If unchecked, passes will be pending for your approval.</small>
                </div>
                
                <div class="form-group">
                    <label for="recent_entries_limit">Recent Submissions Display Limit <span class="required">*</span></label>
                    <input type="number" id="recent_entries_limit" name="recent_entries_limit" 
                           value="<?php echo $settings['recent_entries_limit'] ?? 10; ?>" 
                           min="5" max="50" required>
                    <small style="color: #666;">Number of recent entries to show on Overview tab (5-50)</small>
                </div>
                
                <button type="submit" class="btn btn-primary">Save Settings</button>
            </form>
        
        <!-- Passes Tab -->
        <?php elseif ($action === 'passes'): ?>
            <h2 style="margin-bottom: 20px;">Manage Passes</h2>
            
            <div style="margin-bottom: 20px;">
                <h3>Pending Passes (<?php echo $statusCounts['pending'] ?? 0; ?>)</h3>
                
                <?php
                $pendingPasses = array_filter($currentPasses, function($p) { return $p['status'] === 'pending'; });
                if (empty($pendingPasses)): 
                ?>
                    <p style="color: #666;">No pending passes.</p>
                <?php else: ?>
                    <form method="POST" action="?action=passes">
                        <table>
                            <thead>
                                <tr>
                                    <th><input type="checkbox" id="select-all" onclick="toggleAll(this)"></th>
                                    <th>Student Name</th>
                                    <th>Email</th>
                                    <th>Mod</th>
                                    <th>Activities</th>
                                    <th>Submitted</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pendingPasses as $pass): ?>
                                    <tr>
                                        <td><input type="checkbox" name="pass_ids[]" value="<?php echo $pass['id']; ?>" class="pass-checkbox"></td>
                                        <td><?php echo htmlspecialchars($pass['first_name'] . ' ' . $pass['last_name']); ?></td>
                                        <td><?php echo htmlspecialchars($pass['email']); ?></td>
                                        <td><?php echo $pass['mod']; ?></td>
                                        <td><?php echo htmlspecialchars(implode(', ', json_decode($pass['activities'], true))); ?></td>
                                        <td><?php echo date('M d, H:i', strtotime($pass['created_at'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        
                        <div style="margin-top: 15px;">
                            <button type="submit" name="action" value="approve_passes" class="btn btn-success">Approve Selected</button>
                            <button type="submit" name="action" value="reject_passes" class="btn btn-danger">Reject Selected</button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
            
            <div>
                <h3>Approved Passes (<?php echo $statusCounts['approved'] ?? 0; ?>)</h3>
                <?php
                $approvedPasses = array_filter($currentPasses, function($p) { return $p['status'] === 'approved'; });
                if (empty($approvedPasses)): 
                ?>
                    <p style="color: #666;">No approved passes.</p>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Student Name</th>
                                <th>Pass Code</th>
                                <th>Email</th>
                                <th>Mod</th>
                                <th>Sent At</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($approvedPasses as $pass): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($pass['first_name'] . ' ' . $pass['last_name']); ?></td>
                                    <td><strong><?php echo htmlspecialchars($pass['pass_code']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($pass['email']); ?></td>
                                    <td><?php echo $pass['mod']; ?></td>
                                    <td><?php echo $pass['sent_at'] ? date('M d, H:i', strtotime($pass['sent_at'])) : 'N/A'; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        
        <!-- History Tab -->
        <?php elseif ($action === 'history'): ?>
            <h2 style="margin-bottom: 20px;">Historical Records</h2>
            
            <?php
            $historyDate = $_GET['date'] ?? date('Y-m-d');
            
            // Get historical passes from the selected date
            $historyStmt = $pdo->prepare("
                SELECT * FROM passes_archive 
                WHERE pass_date = ? 
                ORDER BY created_at DESC
            ");
            $historyStmt->execute([$historyDate]);
            $historyPasses = $historyStmt->fetchAll();
            
            // Also check current passes table for today's date
            if ($historyDate === date('Y-m-d')) {
                $todayStmt = $pdo->query("
                    SELECT * FROM passes_current 
                    ORDER BY created_at DESC
                ");
                $historyPasses = array_merge($historyPasses, $todayStmt->fetchAll());
            }
            
            // Calculate previous and next dates
            $prevDate = date('Y-m-d', strtotime($historyDate . ' -1 day'));
            $nextDate = date('Y-m-d', strtotime($historyDate . ' +1 day'));
            $isToday = $historyDate === date('Y-m-d');
            ?>
            
            <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 20px;">
                <a href="?action=history&date=<?php echo $prevDate; ?>" 
                   style="padding: 10px 15px; background: #667eea; color: white; text-decoration: none; border-radius: 6px; font-size: 1.2em; transition: all 0.2s;"
                   onmouseover="this.style.background='#5568d3'" 
                   onmouseout="this.style.background='#667eea'"
                   title="Previous day">◀</a>
                
                <form method="GET" action="" style="margin: 0;">
                    <input type="hidden" name="action" value="history">
                    <div class="form-group" style="margin: 0;">
                        <label for="date" style="display: block; margin-bottom: 5px; font-weight: 500;">View records from date:</label>
                        <input type="date" id="date" name="date" value="<?php echo htmlspecialchars($historyDate); ?>" 
                               onchange="this.form.submit()"
                               style="padding: 8px; border: 1px solid #ddd; border-radius: 4px; font-size: 1em;">
                    </div>
                </form>
                
                <?php if (!$isToday): ?>
                    <a href="?action=history&date=<?php echo $nextDate; ?>" 
                       style="padding: 10px 15px; background: #667eea; color: white; text-decoration: none; border-radius: 6px; font-size: 1.2em; transition: all 0.2s;"
                       onmouseover="this.style.background='#5568d3'" 
                       onmouseout="this.style.background='#667eea'"
                       title="Next day">▶</a>
                <?php else: ?>
                    <span style="padding: 10px 15px; background: #e9ecef; color: #6c757d; border-radius: 6px; font-size: 1.2em;" title="Cannot go beyond today">▶</span>
                <?php endif; ?>
            </div>
            
            <?php if (empty($historyPasses)): ?>
                <p style="color: #666;">No records found for <?php echo date('F d, Y', strtotime($historyDate)); ?>.</p>
            <?php else: ?>
                <h3 style="margin-bottom: 15px;">Records for <?php echo date('F d, Y', strtotime($historyDate)); ?> (<?php echo count($historyPasses); ?> total)</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Student Name</th>
                            <th>Email</th>
                            <th>Mod</th>
                            <th>Teacher</th>
                            <th>Activities</th>
                            <th>Status</th>
                            <th>Pass Code</th>
                            <th>Submitted</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($historyPasses as $pass): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($pass['first_name'] . ' ' . $pass['last_name']); ?></td>
                                <td><?php echo htmlspecialchars($pass['email']); ?></td>
                                <td><?php echo $pass['mod']; ?></td>
                                <td><?php echo htmlspecialchars($pass['teacher_name']); ?></td>
                                <td><?php echo htmlspecialchars(implode(', ', json_decode($pass['activities'], true))); ?></td>
                                <td>
                                    <span style="padding: 5px 10px; border-radius: 3px; 
                                        <?php
                                        if ($pass['status'] === 'approved') echo 'background: #d4edda; color: #155724;';
                                        elseif ($pass['status'] === 'pending') echo 'background: #fff3cd; color: #856404;';
                                        else echo 'background: #f8d7da; color: #721c24;';
                                        ?>
                                    ">
                                        <?php echo ucfirst($pass['status']); ?>
                                    </span>
                                </td>
                                <td><strong><?php echo htmlspecialchars($pass['pass_code']); ?></strong></td>
                                <td><?php echo date('M d, H:i', strtotime($pass['created_at'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    
    <!-- Status Override Modal -->
    <div id="statusOverrideModal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.4);">
        <div style="background-color: #fefefe; margin: 10% auto; padding: 30px; border: 1px solid #888; border-radius: 8px; width: 90%; max-width: 500px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h2 style="margin: 0;">Form Status Override</h2>
                <span onclick="document.getElementById('statusOverrideModal').style.display='none'" 
                      style="cursor: pointer; font-size: 28px; font-weight: bold; color: #aaa;">&times;</span>
            </div>
            
            <form method="POST" action="?action=override_status">
                <div class="form-group">
                    <label style="display: flex; align-items: center; margin-bottom: 20px;">
                        <input type="checkbox" name="form_status_override" id="overrideCheckbox" 
                               <?php echo $settings['form_status_override'] ? 'checked' : ''; ?>
                               onchange="document.getElementById('manualStatusSelect').disabled = !this.checked;"
                               style="width: auto; margin-right: 10px;">
                        <span><strong>Form Status Override</strong></span>
                    </label>
                    <p style="margin: -10px 0 20px 30px; font-size: 0.9em; color: #666;">
                        Enable to manually control form status, ignoring time-based settings
                    </p>
                </div>
                
                <div class="form-group">
                    <label for="manualStatusSelect">Form Status <span class="required">*</span></label>
                    <select id="manualStatusSelect" name="form_status_manual" 
                            <?php echo !$settings['form_status_override'] ? 'disabled' : ''; ?>
                            style="padding: 10px; font-size: 1em;">
                        <option value="open" <?php echo $settings['form_status_manual'] === 'open' ? 'selected' : ''; ?>>Open</option>
                        <option value="closed" <?php echo $settings['form_status_manual'] === 'closed' ? 'selected' : ''; ?>>Closed</option>
                    </select>
                </div>
                
                <div style="margin-top: 25px; text-align: right;">
                    <button type="button" onclick="document.getElementById('statusOverrideModal').style.display='none'" 
                            class="btn btn-secondary" style="margin-right: 10px;">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function toggleAll(checkbox) {
            const checkboxes = document.querySelectorAll('.pass-checkbox');
            checkboxes.forEach(cb => cb.checked = checkbox.checked);
        }
        
        function toggleWeekendDisable() {
            const autoOpenCheckbox = document.getElementById('form_auto_open');
            const weekendCheckbox = document.getElementById('disable_weekends');
            const weekendLabel = weekendCheckbox.closest('label');
            const openTimeInput = document.getElementById('form_open_time');
            const closeTimeInput = document.getElementById('form_close_time');
            const openTimeGroup = openTimeInput.closest('.form-group');
            const closeTimeGroup = closeTimeInput.closest('.form-group');
            
            if (autoOpenCheckbox.checked) {
                weekendCheckbox.disabled = false;
                weekendLabel.style.opacity = '1';
                openTimeInput.disabled = false;
                openTimeInput.required = true;
                closeTimeInput.disabled = false;
                closeTimeInput.required = true;
                openTimeGroup.style.opacity = '1';
                closeTimeGroup.style.opacity = '1';
            } else {
                weekendCheckbox.disabled = true;
                weekendLabel.style.opacity = '0.5';
                openTimeInput.disabled = true;
                openTimeInput.required = false;
                closeTimeInput.disabled = true;
                closeTimeInput.required = false;
                openTimeGroup.style.opacity = '0.5';
                closeTimeGroup.style.opacity = '0.5';
            }
        }
    </script>
</body>
</html>
