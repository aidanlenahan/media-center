<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

requireAdmin();

// Check if user is root
$stmt = $pdo->prepare("SELECT role FROM librarians WHERE id = ?");
$stmt->execute([$_SESSION['librarian_id']]);
$user = $stmt->fetch();

if ($user['role'] !== 'root') {
    header('Location: ../dashboard.php');
    exit;
}

// Get current settings
$settings = $pdo->query("SELECT * FROM settings LIMIT 1")->fetch();
$devSettings = $pdo->query("SELECT * FROM dev_settings LIMIT 1")->fetch();

// Get current time info
$currentTime = date('H:i:s');
$currentDay = date('l'); // Day name
$dayOfWeek = date('N'); // 1-7

// Test isFormOpen
$formStatus = isFormOpen($pdo);

// Manual time comparison
$current = strtotime($currentTime);
$open = strtotime($settings['form_open_time']);
$close = strtotime($settings['form_close_time']);
$timeInRange = ($current >= $open && $current <= $close);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Form Status Debug</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 40px 20px;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        }
        h1 { color: #333; margin-bottom: 30px; }
        h2 { color: #667eea; margin: 30px 0 15px 0; font-size: 1.3em; }
        .status {
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            font-size: 1.2em;
            font-weight: bold;
            text-align: center;
        }
        .status.open { background: #d4edda; color: #155724; }
        .status.closed { background: #f8d7da; color: #721c24; }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th { background: #f8f9fa; font-weight: bold; width: 300px; }
        .true { color: green; font-weight: bold; }
        .false { color: red; font-weight: bold; }
        .section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Form Status Debug</h1>
        
        <div class="status <?php echo $formStatus ? 'open' : 'closed'; ?>">
            Form is currently: <?php echo $formStatus ? 'OPEN ✓' : 'CLOSED ✗'; ?>
        </div>

        <h2>Current Time Information</h2>
        <div class="section">
            <table>
                <tr>
                    <th>Current Server Time</th>
                    <td><?php echo date('Y-m-d H:i:s'); ?></td>
                </tr>
                <tr>
                    <th>Current Time (H:i:s)</th>
                    <td><?php echo $currentTime; ?></td>
                </tr>
                <tr>
                    <th>Current Day</th>
                    <td><?php echo $currentDay; ?> (<?php echo $dayOfWeek; ?>)</td>
                </tr>
                <tr>
                    <th>Is Weekend?</th>
                    <td class="<?php echo ($dayOfWeek == 6 || $dayOfWeek == 7) ? 'true' : 'false'; ?>">
                        <?php echo ($dayOfWeek == 6 || $dayOfWeek == 7) ? 'YES' : 'NO'; ?>
                    </td>
                </tr>
            </table>
        </div>

        <h2>Database Settings</h2>
        <div class="section">
            <table>
                <tr>
                    <th>Form Auto Open</th>
                    <td class="<?php echo $settings['form_auto_open'] ? 'true' : 'false'; ?>">
                        <?php echo $settings['form_auto_open'] ? 'ENABLED' : 'DISABLED'; ?>
                    </td>
                </tr>
                <tr>
                    <th>Form Open Time</th>
                    <td><?php echo $settings['form_open_time']; ?></td>
                </tr>
                <tr>
                    <th>Form Close Time</th>
                    <td><?php echo $settings['form_close_time']; ?></td>
                </tr>
                <tr>
                    <th>Disable Weekends</th>
                    <td class="<?php echo $settings['disable_weekends'] ? 'true' : 'false'; ?>">
                        <?php echo $settings['disable_weekends'] ? 'YES' : 'NO'; ?>
                    </td>
                </tr>
                <tr>
                    <th>Manual Override Active</th>
                    <td class="<?php echo $settings['form_status_override'] ? 'true' : 'false'; ?>">
                        <?php echo $settings['form_status_override'] ? 'YES' : 'NO'; ?>
                    </td>
                </tr>
                <tr>
                    <th>Manual Status</th>
                    <td><?php echo strtoupper($settings['form_status_manual']); ?></td>
                </tr>
            </table>
        </div>

        <h2>Developer Settings</h2>
        <div class="section">
            <table>
                <tr>
                    <th>Bypass Time Restrictions</th>
                    <td class="<?php echo $devSettings['bypass_time_restrictions'] ? 'true' : 'false'; ?>">
                        <?php echo $devSettings['bypass_time_restrictions'] ? 'YES (Form always open)' : 'NO'; ?>
                    </td>
                </tr>
            </table>
        </div>

        <h2>Logic Evaluation</h2>
        <div class="section">
            <table>
                <tr>
                    <th>1. Dev Bypass Active?</th>
                    <td class="<?php echo $devSettings['bypass_time_restrictions'] ? 'true' : 'false'; ?>">
                        <?php echo $devSettings['bypass_time_restrictions'] ? 'YES - Skip all checks' : 'NO - Continue checks'; ?>
                    </td>
                </tr>
                <tr>
                    <th>2. Manual Override Active?</th>
                    <td class="<?php echo $settings['form_status_override'] ? 'true' : 'false'; ?>">
                        <?php echo $settings['form_status_override'] ? 'YES - Use manual status: ' . $settings['form_status_manual'] : 'NO - Continue checks'; ?>
                    </td>
                </tr>
                <tr>
                    <th>3. Is Weekend + Disabled?</th>
                    <td class="<?php echo ($settings['disable_weekends'] && ($dayOfWeek == 6 || $dayOfWeek == 7)) ? 'true' : 'false'; ?>">
                        <?php 
                        if ($settings['disable_weekends'] && ($dayOfWeek == 6 || $dayOfWeek == 7)) {
                            echo 'YES - Form closed (weekend)';
                        } else {
                            echo 'NO - Continue checks';
                        }
                        ?>
                    </td>
                </tr>
                <tr>
                    <th>4. Auto-Open Enabled?</th>
                    <td class="<?php echo $settings['form_auto_open'] ? 'true' : 'false'; ?>">
                        <?php echo $settings['form_auto_open'] ? 'YES - Check time' : 'NO - Form closed'; ?>
                    </td>
                </tr>
                <tr>
                    <th>5. Current Time in Range?</th>
                    <td class="<?php echo $timeInRange ? 'true' : 'false'; ?>">
                        <?php 
                        echo $timeInRange ? 'YES' : 'NO';
                        echo " ({$currentTime} between {$settings['form_open_time']} and {$settings['form_close_time']})";
                        ?>
                    </td>
                </tr>
            </table>
        </div>

        <h2>Raw Timestamps</h2>
        <div class="section">
            <table>
                <tr>
                    <th>Current timestamp</th>
                    <td><?php echo $current; ?> (<?php echo date('H:i:s', $current); ?>)</td>
                </tr>
                <tr>
                    <th>Open timestamp</th>
                    <td><?php echo $open; ?> (<?php echo date('H:i:s', $open); ?>)</td>
                </tr>
                <tr>
                    <th>Close timestamp</th>
                    <td><?php echo $close; ?> (<?php echo date('H:i:s', $close); ?>)</td>
                </tr>
                <tr>
                    <th>Current >= Open?</th>
                    <td class="<?php echo ($current >= $open) ? 'true' : 'false'; ?>">
                        <?php echo ($current >= $open) ? 'YES' : 'NO'; ?> (<?php echo $current - $open; ?> seconds diff)
                    </td>
                </tr>
                <tr>
                    <th>Current <= Close?</th>
                    <td class="<?php echo ($current <= $close) ? 'true' : 'false'; ?>">
                        <?php echo ($current <= $close) ? 'YES' : 'NO'; ?> (<?php echo $close - $current; ?> seconds diff)
                    </td>
                </tr>
            </table>
        </div>

        <div style="margin-top: 30px;">
            <a href="../dev_panel.php" style="padding: 12px 24px; background: #667eea; color: white; text-decoration: none; border-radius: 6px;">← Back to Developer Panel</a>
            <a href="../dashboard.php" style="padding: 12px 24px; background: #6c757d; color: white; text-decoration: none; border-radius: 6px; margin-left: 10px;">Dashboard</a>
            <a href="javascript:location.reload()" style="padding: 12px 24px; background: #6c757d; color: white; text-decoration: none; border-radius: 6px; margin-left: 10px;">Refresh</a>
        </div>
    </div>
</body>
</html>
