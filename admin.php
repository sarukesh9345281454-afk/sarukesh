<?php 
require_once 'auth.php'; 

// --- DELETE STUDENT LOGIC ---
if (isset($_GET['delete_roll'])) {
    $roll = $_GET['delete_roll'];
    
    // Find photo path to delete file
    $stmt = $pdo->prepare("SELECT photo FROM students WHERE roll_no = ?");
    $stmt->execute([$roll]);
    $student = $stmt->fetch();
    
    if ($student && file_exists($student['photo'])) {
        unlink($student['photo']); 
    }
    
    // Delete by roll_no since it's your unique identifier
    $stmt = $pdo->prepare("DELETE FROM students WHERE roll_no = ?");
    $stmt->execute([$roll]);
    header("Location: admin.php?tab=register&msg=deleted");
    exit();
}

// --- STUDENT REGISTRATION LOGIC ---
$reg_message = "";
if (isset($_POST['register_student'])) {
    $name = htmlspecialchars($_POST['std_name']);
    $roll = htmlspecialchars($_POST['std_roll']);
    $target_dir = "uploads/";
    
    if (!file_exists($target_dir)) { mkdir($target_dir, 0777, true); }

    $file_ext = strtolower(pathinfo($_FILES["std_photo"]["name"], PATHINFO_EXTENSION));
    $file_name = $roll . "." . $file_ext; 
    $target_file = $target_dir . $file_name;

    if (getimagesize($_FILES["std_photo"]["tmp_name"]) !== false) {
        if (move_uploaded_file($_FILES["std_photo"]["tmp_name"], $target_file)) {
            try {
                $stmt = $pdo->prepare("INSERT INTO students (name, roll_no, photo) VALUES (?, ?, ?)");
                $stmt->execute([$name, $roll, $target_file]);
                $reg_message = "✅ Success: Student Registered!";
            } catch (PDOException $e) {
                if(file_exists($target_file)) unlink($target_file); 
                $reg_message = "❌ Error: Roll Number '$roll' already exists.";
            }
        } else {
            $reg_message = "❌ Failed to upload photo.";
        }
    } else {
        $reg_message = "❌ Invalid image file.";
    }
}

$current_tab = isset($_GET['tab']) ? $_GET['tab'] : 'logs';
if(isset($_GET['msg']) && $_GET['msg'] == 'deleted') $reg_message = "🗑️ Student removed successfully.";
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard - AAC</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .active-tab { background: #2563eb !important; color: white !important; }
        .glass-card { background: #0f172a; border: 1px solid #1e293b; color: white; }
        .dark-input { background: #1e293b; border: 1px solid #475569; color: white; border-radius: 0.75rem; padding: 0.75rem; width: 100%; outline: none; }
        .dark-input:focus { border-color: #3b82f6; }
        .btn-glow { background: linear-gradient(135deg, #2563eb, #7c3aed); color: white; transition: all 0.3s ease; }
        .btn-glow:hover { transform: translateY(-2px); box-shadow: 0 10px 15px -3px rgba(37, 99, 235, 0.4); }
    </style>
</head>
<body class="bg-slate-100 min-h-screen p-4 md:p-10 font-sans">
    <div class="max-w-7xl mx-auto">
        
        <?php if (!isset($_SESSION['admin_logged_in'])): ?>
             <div class="max-w-md mx-auto mt-20 p-10 bg-white rounded-3xl shadow-xl border-t-8 border-blue-600">
                <h2 class="text-2xl font-black text-slate-800 text-center mb-6 uppercase tracking-tighter">Admin Login</h2>
                <form method="POST" class="space-y-4">
                    <input type="text" name="user" placeholder="Username" class="w-full p-4 border rounded-xl outline-none focus:border-blue-500" required>
                    <input type="password" name="pass" placeholder="Password" class="w-full p-4 border rounded-xl outline-none focus:border-blue-500" required>
                    <button name="admin_login_btn" class="w-full bg-blue-600 text-white py-4 rounded-xl font-bold hover:bg-blue-700 transition-all">Login to Dashboard</button>
                </form>
            </div>
        <?php else: ?>

            <div class="flex flex-col md:flex-row justify-between items-center mb-8 gap-4">
                <div>
                    <h2 class="text-3xl font-black text-slate-900 italic">AAC ADMIN</h2>
                    <p class="text-blue-600 font-bold text-xs uppercase tracking-widest"><?php echo $_SESSION['admin_dept']; ?> Department</p>
                </div>

                <div class="flex gap-2 bg-slate-200 p-1.5 rounded-2xl border border-slate-300">
                    <a href="?tab=logs" class="px-8 py-2 rounded-xl text-sm font-bold <?php echo $current_tab == 'logs' ? 'active-tab shadow-lg' : 'text-slate-600'; ?>">LOGS</a>
                    <a href="?tab=register" class="px-8 py-2 rounded-xl text-sm font-bold <?php echo $current_tab == 'register' ? 'active-tab shadow-lg' : 'text-slate-600'; ?>">STUDENTS</a>
                </div>

                <div class="flex gap-3">
                    <a href="index.php" class="bg-white px-5 py-2.5 rounded-xl text-xs font-bold border border-slate-300 hover:bg-slate-50">Open Scanner</a>
                    <a href="?action=logout" class="bg-red-600 text-white px-5 py-2.5 rounded-xl text-xs font-bold shadow-lg shadow-red-200 hover:bg-red-700">Logout</a>
                </div>
            </div>

            <?php if ($current_tab == 'register'): ?>
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
                    
                    <div class="lg:col-span-5 glass-card p-8 rounded-[2.5rem] shadow-2xl">
                        <h3 class="text-xl font-bold mb-6 text-blue-400">Enroll Student</h3>
                        
                        <?php if($reg_message): ?>
                            <div class="mb-6 p-4 rounded-xl text-xs font-bold text-center border <?php echo strpos($reg_message, '✅') !== false ? 'bg-blue-900/40 text-blue-200 border-blue-700' : 'bg-red-900/40 text-red-200 border-red-700'; ?>">
                                <?php echo $reg_message; ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST" enctype="multipart/form-data" class="space-y-5">
                            <div>
                                <label class="text-[10px] text-slate-400 font-bold uppercase ml-1">Student Full Name</label>
                                <input type="text" name="std_name" required class="dark-input" placeholder="Full Name">
                            </div>
                            <div>
                                <label class="text-[10px] text-slate-400 font-bold uppercase ml-1">Roll Number (Unique)</label>
                                <input type="text" name="std_roll" required class="dark-input" placeholder="Roll No">
                            </div>
                            <div>
                                <label class="text-[10px] text-slate-400 font-bold uppercase ml-1">Upload Photo</label>
                                <input type="file" name="std_photo" accept="image/*" required class="w-full text-xs text-slate-400 file:bg-blue-600 file:text-white file:border-0 file:px-4 file:py-2 file:rounded-lg file:mr-4 cursor-pointer">
                            </div>
                            <button name="register_student" class="w-full btn-glow py-4 rounded-2xl font-black uppercase tracking-widest shadow-xl">Save to Database</button>
                        </form>
                    </div>

                    <div class="lg:col-span-7 glass-card p-8 rounded-[2.5rem] shadow-2xl">
                        <h3 class="text-xl font-bold mb-6 text-slate-400">Registered Students</h3>
                        <div class="overflow-y-auto max-h-[550px] custom-scrollbar">
                            <table class="w-full text-left">
                                <thead class="border-b border-slate-800 sticky top-0 bg-[#0f172a]">
                                    <tr class="text-[10px] text-slate-500 uppercase tracking-widest">
                                        <th class="pb-4">Student Info</th>
                                        <th class="pb-4">Roll No</th>
                                        <th class="pb-4 text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="text-sm divide-y divide-slate-800/50">
                                    <?php
                                    // Use roll_no for ordering since 'id' might be missing
                                    $students = $pdo->query("SELECT * FROM students ORDER BY roll_no DESC")->fetchAll();
                                    foreach ($students as $s): ?>
                                    <tr class="hover:bg-slate-800/30 transition-colors">
                                        <td class="py-4 flex items-center gap-4">
                                            <div class="w-10 h-10 rounded-full overflow-hidden border border-slate-700">
                                                <img src="<?php echo $s['photo']; ?>" class="w-full h-full object-cover" onerror="this.src='https://ui-avatars.com/api/?name=<?php echo $s['name']; ?>'">
                                            </div>
                                            <span class="font-bold text-slate-200"><?php echo $s['name']; ?></span>
                                        </td>
                                        <td class="py-4 font-mono text-blue-400"><?php echo $s['roll_no']; ?></td>
                                        <td class="py-4 text-right">
                                            <a href="?delete_roll=<?php echo $s['roll_no']; ?>" 
                                               onclick="return confirm('Permanently delete student <?php echo $s['roll_no']; ?>?')" 
                                               class="bg-red-500/10 text-red-500 border border-red-500/20 px-3 py-1 rounded-lg text-[10px] font-black hover:bg-red-500 hover:text-white transition-all">
                                               DELETE
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            <?php else: ?>
                <div class="bg-white rounded-[2.5rem] shadow-2xl overflow-hidden border border-slate-200">
                    <table class="w-full text-left">
                        <thead class="bg-slate-900 text-blue-400 text-[10px] uppercase tracking-widest">
                            <tr>
                                <th class="p-6">Roll No</th>
                                <th class="p-6">Staff Name</th>
                                <th class="p-6">Subject</th>
                                <th class="p-6">Department</th>
                                <th class="p-6">Entry Time</th>
                                <th class="p-6">Status</th>
                            </tr>
                        </thead>
                        <tbody class="text-sm divide-y divide-slate-100">
                            <?php
                            $logs = $pdo->query("SELECT * FROM lab_logs ORDER BY time_in DESC LIMIT 100")->fetchAll();
                            foreach ($logs as $row): ?>
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="p-6 font-mono font-bold text-slate-900"><?php echo $row['barcode']; ?></td>
                                <td class="p-6 font-semibold"><?php echo $row['staff_name']; ?></td>
                                <td class="p-6 text-slate-500"><?php echo $row['subject_name']; ?></td>
                                <td class="p-6 text-xs font-bold text-slate-400"><?php echo $row['department']; ?></td>
                                <td class="p-6 text-xs text-slate-500"><?php echo date('d M, h:i A', strtotime($row['time_in'])); ?></td>
                                <td class="p-6">
                                    <span class="px-3 py-1 rounded-full text-[9px] font-black <?php echo $row['status']=='active' ? 'bg-blue-100 text-blue-600' : 'bg-slate-100 text-slate-500'; ?>">
                                        <?php echo strtoupper($row['status']); ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</body>
</html>