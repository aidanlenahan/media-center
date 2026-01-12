<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

requireAdmin();

$librarian = $_SESSION['librarian_username'];
$action = $_GET['action'] ?? 'overview';
$message = '';
$messageType = '';

// Handle admin actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'settings') {
        try {
            $formAutoOpen = isset($_POST['form_auto_open']) ? 1 : 0;
            $formOpenTime = $_POST['form_open_time'] ?? '07:30';
            $formCloseTime = $_POST['form_close_time'] ?? '14:30';
            $autoApproval = isset($_POST['auto_approval']) ? 1 : 0;
            
            $stmt = $pdo->prepare("
                UPDATE settings 
                SET form_auto_open = ?, form_open_time = ?, form_close_time = ?, auto_approval = ?
                WHERE id = 1
            ");
            $stmt->execute([$formAutoOpen, $formOpenTime . ':00', $formCloseTime . ':00', $autoApproval]);
            
            $message = 'Settings updated successfully.';
            $messageType = 'success';
        } catch (Exception $e) {
            $message = 'Error updating settings.';
            $messageType = 'danger';
        }
    } elseif ($action === 'approve_passes') {
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
    } elseif ($action === 'reject_passes') {
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
    } elseif ($action === 'delete_pass') {
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
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Librarian Dashboard</h1>
            <p>Welcome, <?php echo htmlspecialchars($librarian); ?></p>
        </div>
        
        <!-- Navigation -->
        <div style="margin-bottom: 20px; border-bottom: 1px solid #ddd; padding-bottom: 15px;">
            <a href="?action=overview" class="btn btn-primary" style="padding: 8px 15px; margin-right: 10px;">Overview</a>
            <a href="?action=passes" class="btn btn-primary" style="padding: 8px 15px; margin-right: 10px;">Manage Passes</a>
            <a href="?action=history" class="btn btn-primary" style="padding: 8px 15px; margin-right: 10px;">History</a>
            <a href="?action=settings" class="btn btn-primary" style="padding: 8px 15px; margin-right: 10px;">Settings</a>
            <a href="logout.php" class="btn btn-secondary" style="padding: 8px 15px;">Logout</a>
        </div>
        
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
                
                <div class="card">
                    <h3>Form Status</h3>
                    <div class="card-value"><?php echo $settings['form_auto_open'] ? 'OPEN' : 'CLOSED'; ?></div>
                    <p><?php echo $settings['form_open_time'] . ' - ' . $settings['form_close_time']; ?></p>
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
                    <?php foreach (array_slice($currentPasses, 0, 10) as $pass): ?>
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
                        <input type="checkbox" name="form_auto_open" <?php echo $settings['form_auto_open'] ? 'checked' : ''; ?> 
                               style="width: auto; margin-right: 10px;">
                        <span>Enable automatic form opening/closing</span>
                    </label>
                </div>
                
                <div class="form-group">
                    <label for="form_open_time">Form Opens At <span class="required">*</span></label>
                    <input type="time" id="form_open_time" name="form_open_time" 
                           value="<?php echo substr($settings['form_open_time'], 0, 5); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="form_close_time">Form Closes At <span class="required">*</span></label>
                    <input type="time" id="form_close_time" name="form_close_time" 
                           value="<?php echo substr($settings['form_close_time'], 0, 5); ?>" required>
                </div>
                
                <div class="form-group">
                    <label style="display: flex; align-items: center; margin-bottom: 15px;">
                        <input type="checkbox" name="auto_approval" <?php echo $settings['auto_approval'] ? 'checked' : ''; ?> 
                               style="width: auto; margin-right: 10px;">
                        <span>Automatically approve and send passes immediately</span>
                    </label>
                    <small style="color: #666;">If unchecked, passes will be pending for your approval.</small>
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
            
            <div class="form-group" style="max-width: 300px;">
                <label for="history-date">View records from date:</label>
                <input type="date" id="history-date" value="<?php echo date('Y-m-d'); ?>">
            </div>
            
            <div id="history-content" style="margin-top: 20px;">
                <p style="color: #666;">Select a date to view historical records.</p>
            </div>
        <?php endif; ?>
    </div>
    
    <script>
        function toggleAll(checkbox) {
            const checkboxes = document.querySelectorAll('.pass-checkbox');
            checkboxes.forEach(cb => cb.checked = checkbox.checked);
        }
        
        document.getElementById('history-date')?.addEventListener('change', function() {
            // This would load historical data via AJAX
            document.getElementById('history-content').innerHTML = 'Loading...';
        });
    </script>
</body>
</html>
