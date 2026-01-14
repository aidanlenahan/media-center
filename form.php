<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

$formOpen = isFormOpen($pdo);
$settings = getSettings($pdo);
$submitted = false;
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $formOpen) {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = 'Invalid request. Please try again.';
    } else {
        try {
            // Collect and sanitize form data
            $firstName = sanitizeInput($_POST['first_name'] ?? '');
            $lastName = sanitizeInput($_POST['last_name'] ?? '');
            $email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
            $teacherName = sanitizeInput($_POST['teacher_name'] ?? '');
            $mod = (int)($_POST['mod'] ?? 0);
            $activities = $_POST['activities'] ?? [];
            $otherText = sanitizeInput($_POST['other_text'] ?? '');
            $agreement = isset($_POST['agreement']) ? 1 : 0;
            
            // If 'Other' is selected, add the text to activities
            if (in_array('Other', $activities) && $otherText) {
                $activities[] = 'Other: ' . $otherText;
            }
            
            // Validate required fields
            if (!$firstName || !$lastName || !$email || !$teacherName || !$mod || !$agreement) {
                $error = 'Please fill out all required fields.';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error = 'Please enter a valid email address.';
            } else {
                // Check if school email is required
                $devStmt = $pdo->query("SELECT require_school_email FROM dev_settings LIMIT 1");
                $devSettings = $devStmt->fetch();
                if ($devSettings && $devSettings['require_school_email']) {
                    if (!preg_match('/@students\.rbrhs\.org$/i', $email)) {
                        $error = 'Please use your school email address (@students.rbrhs.org).';
                    }
                }
            }
            
            if (!$error && count($activities) === 0) {
                $error = 'Please select at least one activity.';
            }
            
            if (!$error) {
                // Generate pass code
                $passCode = generatePassCode();
                $activitiesJson = json_encode($activities);
                $status = $settings['auto_approval'] ? 'approved' : 'pending';
                
                // Insert into database
                $stmt = $pdo->prepare("
                    INSERT INTO passes_current 
                    (first_name, last_name, email, teacher_name, `mod`, activities, agreement_checked, status, pass_code)
                    VALUES (?, ?, ?, ?, ?, ?, 1, ?, ?)
                ");
                $stmt->execute([$firstName, $lastName, $email, $teacherName, $mod, $activitiesJson, $status, $passCode]);
                
                // Send email if auto-approval enabled
                if ($settings['auto_approval']) {
                    sendPassEmail($email, $firstName, $lastName, $passCode, $mod, $activities);
                    $success = 'Your pass has been approved! Check your email for your pass code.';
                } else {
                    $success = 'Your request has been submitted. The librarian will review it and send your pass via email.';
                }
                
                $submitted = true;
            }
        } catch (Exception $e) {
            $error = 'An error occurred. Please try again.';
            error_log($e->getMessage());
        }
    }
}

// Generate CSRF token for form
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Media Center Study Hall Pass</title>
    <link rel="icon" type="image/svg+xml" href="img/buc.svg">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <!-- Hidden Admin Login Button -->
        <div id="adminLoginBtn" style="display: none; margin-bottom: 20px; text-align: center;">
            <a href="login.php" style="display: inline-block; padding: 12px 30px; background: #690000; color: white; text-decoration: none; border-radius: 6px; font-weight: 600; transition: all 0.3s;" onmouseover="this.style.background='#4a0000'" onmouseout="this.style.background='#690000'">🔐 Admin Login</a>
        </div>
        
        <div class="header">
            <h1>Media Center Study Hall Pass Request</h1>
            <p>Solicitud de Pase de Estudio en la Biblioteca</p>
        </div>
        
        <?php if (!$formOpen && !$submitted): ?>
            <div class="alert alert-warning">
                <strong>Form Closed:</strong> The pass request form is currently closed. Please check back during study hall hours.
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger">
                <strong>Error:</strong> <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success">
                <strong>Success:</strong> <?php echo $success; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($formOpen && !$submitted): ?>
            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                
                <!-- First Name -->
                <div class="form-group">
                    <label>First Name / Nombre <span class="required">*</span></label>
                    <span class="bilingual-label">Primer nombre (corta respuesta)</span>
                    <input type="text" name="first_name" required>
                </div>
                
                <!-- Last Name -->
                <div class="form-group">
                    <label>Last Name / Apellido <span class="required">*</span></label>
                    <span class="bilingual-label">Apellido (corta respuesta)</span>
                    <input type="text" name="last_name" required>
                </div>
                
                <!-- Email Note -->
                <div class="form-group">
                    <?php
                    // Check if school email is required
                    $devStmt = $pdo->query("SELECT require_school_email FROM dev_settings LIMIT 1");
                    $devSettings = $devStmt->fetch();
                    $requireSchoolEmail = $devSettings && $devSettings['require_school_email'];
                    ?>
                    <p style="color: #666; font-size: 0.95em;">
                        <strong>This form collected the email account you're signed into. Your pass will be emailed to you.</strong><br>
                        <em>Este formulario recopiló la cuenta de correo electrónico en la que inició sesión. Su pase será enviado a esta dirección.</em>
                        <?php if ($requireSchoolEmail): ?>
                            <br><br>
                            <span style="color: #690000; font-weight: bold;">⚠️ You must use your school email address (@students.rbrhs.org)</span><br>
                            <em style="color: #690000;">Debe usar su correo electrónico escolar (@students.rbrhs.org)</em>
                        <?php endif; ?>
                    </p>
                    <label>Email Address / Email <span class="required">*</span></label>
                    <input type="email" name="email" <?php echo $requireSchoolEmail ? 'pattern=".*@students\.rbrhs\.org$" title="Please use your school email address (@students.rbrhs.org)"' : ''; ?> required>
                </div>
                
                <!-- Teacher Name -->
                <div class="form-group">
                    <label>Study Hall Teacher Name / Nombre de Maestro <span class="required">*</span></label>
                    <span class="bilingual-label">Estos pases son sólo para estudiantes en Study Hall.</span>
                    <input type="text" name="teacher_name" required>
                </div>
                
                <!-- Mod Selection -->
                <div class="form-group">
                    <label>Confirm you are requesting the correct Mod <span class="required">*</span></label>
                    <span class="bilingual-label">This is NOT for your lunch period. Those passes are given in person at Mrs. Hansen's desk in the library.</span>
                    <select name="mod" required>
                        <option value="">-- Select Mod --</option>
                        <option value="1">Mod 1</option>
                        <option value="2">Mod 2</option>
                    </select>
                </div>
                
                <!-- Activities -->
                <div class="form-group">
                    <label>What activity will you be doing today? <span class="required">*</span></label>
                    <span class="bilingual-label">¿Qué actividad harás hoy? (Selecciona al menos una)</span>
                    <div class="checkbox-group">
                        <div class="checkbox-item">
                            <input type="checkbox" name="activities[]" value="Studying" id="studying">
                            <label for="studying">Studying / Estudiando</label>
                        </div>
                        <div class="checkbox-item">
                            <input type="checkbox" name="activities[]" value="Working on a project" id="project">
                            <label for="project">Working on a project / Trabajando en un proyecto</label>
                        </div>
                        <div class="checkbox-item">
                            <input type="checkbox" name="activities[]" value="Reading" id="reading">
                            <label for="reading">Reading / Leyendo</label>
                        </div>
                        <div class="checkbox-item">
                            <input type="checkbox" name="activities[]" value="Meeting with tutor/teacher" id="meeting">
                            <label for="meeting">Meeting tutor/teacher / Trabajando con un profesor</label>
                        </div>
                        <div class="checkbox-item">
                            <input type="checkbox" name="activities[]" value="Other" id="other" onchange="toggleOtherText(this)">
                            <label for="other">Other / Otro</label>
                        </div>
                        <div id="other-text-container" style="display: none; margin-top: 10px; margin-left: 25px;">
                            <input type="text" 
                                   name="other_text" 
                                   id="other_text" 
                                   placeholder="Please specify (max 100 characters)" 
                                   maxlength="100"
                                   style="width: 100%;">
                            <div style="font-size: 0.85em; color: #666; margin-top: 5px;">
                                <span id="char-count">0</span>/100 characters
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Agreement -->
                <div class="form-group">
                    <label style="margin-bottom: 15px;">Agreement of behavior and activity / Acuerdo de comportamiento y actividad <span class="required">*</span></label>
                    <div style="background: #f8f9fa; padding: 15px; border-radius: 4px; margin-bottom: 15px; font-size: 0.9em; line-height: 1.6;">
                        <p style="margin-bottom: 10px;"><strong>English:</strong></p>
                        <p>I understand and agree that Library Study Hall is a QUIET space for STUDYING, READING, Meeting with a Peer Tutor/Teacher. If I am asked to leave, or I lose Library Study Hall privileges for violating the rules of Library Study Hall, I will go directly back to my classroom.</p>
                        
                        <p style="margin: 15px 0 10px 0;"><strong>Español:</strong></p>
                        <p>Entiendo que la sala de estudio de la biblioteca es para trabajar o leer en silencio. Si me piden que me vaya, o pierdo los privilegios del Salón de Estudio de la Biblioteca por violar las reglas del Salón de Estudio de la Biblioteca, volveré directamente con mi Maestro.</p>
                    </div>
                    <div class="checkbox-item">
                        <input type="checkbox" name="agreement" id="agreement" required>
                        <label for="agreement">I understand and agree to the above / Entiendo y estoy de acuerdo</label>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 20px;">Submit Pass Request / Enviar Solicitud</button>
            </form>
        <?php endif; ?>
    </div>
    <script>
        // Admin shortcut: Ctrl+Alt+D to show admin login button
        document.addEventListener('keydown', function(e) {
            if (e.ctrlKey && e.altKey && e.key === 'd') {
                e.preventDefault();
                const adminBtn = document.getElementById('adminLoginBtn');
                if (adminBtn.style.display === 'none') {
                    adminBtn.style.display = 'block';
                } else {
                    adminBtn.style.display = 'none';
                }
            }
        });
        
        function toggleOtherText(checkbox) {
            const container = document.getElementById('other-text-container');
            const textInput = document.getElementById('other_text');
            if (checkbox.checked) {
                container.style.display = 'block';
                textInput.required = true;
            } else {
                container.style.display = 'none';
                textInput.required = false;
                textInput.value = '';
                updateCharCount();
            }
        }
        
        function updateCharCount() {
            const textInput = document.getElementById('other_text');
            const charCount = document.getElementById('char-count');
            if (textInput && charCount) {
                charCount.textContent = textInput.value.length;
            }
        }
        
        document.getElementById('other_text')?.addEventListener('input', updateCharCount);
    </script>
</body>
</html>
