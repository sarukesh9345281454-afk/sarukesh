<?php
require_once 'auth.php';

// --- STAFF LOGIN LOGIC ---
if (isset($_POST['staff_login_btn'])) {
    $_SESSION['staff_name'] = htmlspecialchars($_POST['staff_name']);
    $_SESSION['staff_dept'] = htmlspecialchars($_POST['staff_dept']);
    $_SESSION['staff_subject'] = htmlspecialchars($_POST['staff_subject']);
    header("Location: index.php");
    exit();
}

// --- LOGOUT LOGIC ---
if (isset($_GET['action']) && $_GET['action'] == 'staff_logout') {
    session_destroy();
    header("Location: index.php");
    exit();
}

$message = "";
$message_type = "blue"; // Default color
$student_data = null;

// --- SCANNER LOGIC (Only if Logged In) ---
if (isset($_SESSION['staff_name']) && isset($_POST['barcode']) && !empty($_POST['barcode'])) {
    $scanned_code = htmlspecialchars($_POST['barcode']);
    
    // 1. Check if student exists (Using 'roll_no' column)
    $stmt_std = $pdo->prepare("SELECT * FROM students WHERE roll_no = ?");
    $stmt_std->execute([$scanned_code]);
    $student_data = $stmt_std->fetch();

    if ($student_data) {
        // 2. Check for active session
        $stmt_log = $pdo->prepare("SELECT * FROM lab_logs WHERE barcode = ? AND status = 'active' LIMIT 1");
        $stmt_log->execute([$scanned_code]);
        $active_session = $stmt_log->fetch();

        if ($active_session) {
            $update = $pdo->prepare("UPDATE lab_logs SET time_out = NOW(), status = 'completed' WHERE id = ?");
            $update->execute([$active_session['id']]);
            $message = "✅ EXIT LOGGED";
            $message_type = "red"; // Change type to red for exit
        } else {
            $insert = $pdo->prepare("INSERT INTO lab_logs (barcode, staff_name, subject_name, department, status) VALUES (?, ?, ?, ?, 'active')");
            $insert->execute([
                $scanned_code, 
                $_SESSION['staff_name'], 
                $_SESSION['staff_subject'], 
                $_SESSION['staff_dept']
            ]);
            $message = "🚀 ENTRY LOGGED";
            $message_type = "green"; // Keep green for entry
        }
    } else {
        $message = "❌ STUDENT NOT REGISTERED AND CONTACT YOUR ADMIN ";
        $message_type = "red";
    }
}
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>AAC Lab Scanner</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="style.css" rel="stylesheet">
    <style>
        .glass { background: rgba(15, 23, 42, 0.9); backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.1); }
        .scan-input { background: #020617; border: 2px solid #3b82f6; color: #60a5fa; text-align: center; font-family: monospace; }
        .scan-input:focus { box-shadow: 0 0 20px rgba(59, 130, 246, 0.5); border-color: #60a5fa; outline: none; }
        
        /* Dynamic message colors */
        .msg-blue { background-color: #0dba04; } /* blue-600 */
        .msg-red { background-color: #dc2626; }  /* red-600 */
    </style>
</head>
<body class="bg-slate-950 text-white min-h-screen font-sans">

    <?php if (!isset($_SESSION['staff_name'])): ?>
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="glass max-w-md w-full p-10 rounded-[2.5rem] shadow-2xl">
                <h2 class="text-blue-500 font-black text-3xl mb-1 text-center italic">AAC LABS</h2>
                <p class="text-slate-500 text-[10px] uppercase text-center mb-8 tracking-[0.3em]">Staff Initialization</p>
                
                <form method="POST" class="space-y-4">
                    <input type="text" name="staff_name" placeholder="Lecturer Name" class="w-full p-4 rounded-2xl bg-slate-900 border border-slate-800 focus:border-blue-500 outline-none" required>
                    <input type="text" name="staff_dept" placeholder="Department" class="w-full p-4 rounded-2xl bg-slate-900 border border-slate-800 focus:border-blue-500 outline-none" required>
                    <input type="text" name="staff_subject" placeholder="Subject / Lab Name" class="w-full p-4 rounded-2xl bg-slate-900 border border-slate-800 focus:border-blue-500 outline-none" required>
                    <button name="staff_login_btn" class="w-full bg-blue-600 hover:bg-blue-700 py-4 rounded-2xl font-bold uppercase tracking-widest transition-all shadow-lg shadow-blue-900/20">Start Session</button>
                </form>
            </div>
        </div>

    <?php else: ?>
        <div class="max-w-5xl mx-auto p-4 md:p-10">
            <div class="flex justify-between items-center mb-10 glass p-6 rounded-3xl">
                <div>
                    <h1 class="text-blue-500 font-black text-xl">LIVE TRACKING ACTIVE</h1>
                    <p class="text-[10px] text-slate-400 font-bold uppercase">Staff: <?php echo $_SESSION['staff_name']; ?> | Sub: <?php echo $_SESSION['staff_subject']; ?></p>
                </div>
                <a href="?action=staff_logout" class="bg-red-500/10 text-red-500 border border-red-500/20 px-4 py-2 rounded-xl text-xs font-bold hover:bg-red-600 hover:text-white transition-all">End Session</a>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-10">
                <div class="glass p-10 rounded-[3rem] flex flex-col items-center justify-center min-h-[450px] relative overflow-hidden">
                    <?php if ($student_data || !empty($message)): ?>
                        <?php if ($student_data): ?>
                            <div class="w-56 h-56 rounded-full border-4 border-blue-500 overflow-hidden mb-6 shadow-2xl">
                                <img src="<?php echo $student_data['photo']; ?>" 
                                     onerror="this.src='https://ui-avatars.com/api/?name=<?php echo urlencode($student_data['name']); ?>&background=2563eb&color=fff&size=200'" 
                                     class="w-full h-full object-cover">
                            </div>
                            <h2 class="text-3xl font-black mb-1"><?php echo strtoupper($student_data['name']); ?></h2>
                            <p class="text-blue-400 font-mono tracking-widest mb-6"><?php echo $student_data['roll_no']; ?></p>
                        <?php endif; ?>

                        <div class="px-8 py-3 rounded-full font-black text-sm animate-pulse msg-<?php echo $message_type; ?>">
                            <?php echo $message; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center opacity-10">
                            <span class="text-[10rem]">👤</span>
                            <p class="font-bold tracking-[0.5em] uppercase">Ready</p>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="flex flex-col justify-center space-y-6">
                    <div class="glass p-8 rounded-[2.5rem]">
                        <label class="block text-blue-500 text-xs font-bold uppercase tracking-widest mb-4 text-center">Place Cursor & Scan Barcode</label>
                        <form method="POST" id="scanForm">
                            <input type="text" name="barcode" id="barcode_input" autofocus autocomplete="off"
                                   class="scan-input w-full p-8 rounded-2xl text-4xl shadow-inner"
                                   placeholder="|||||||||||">
                        </form>
                    </div>
                    
                    <div class="text-center text-slate-600 text-[10px] uppercase font-bold tracking-widest">
                        Auto-focus enabled • Data saves instantly
                    </div>
                </div>
            </div>
        </div>

        <script>
            const input = document.getElementById('barcode_input');
            // Force focus
            setInterval(() => { if(document.activeElement !== input) input.focus(); }, 100);
            
            // Auto-refresh after scan to clear the screen for the next student
            <?php if (!empty($message)): ?>
            setTimeout(() => { window.location.href = 'index.php'; }, 3500);
            <?php endif; ?>
        </script>
    <?php endif; ?>
</body>
</html>