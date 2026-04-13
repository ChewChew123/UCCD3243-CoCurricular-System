<?php
// path : modules/achievements/add_achievement.php
session_start();
require('../../includes/db_connect.php'); // 确保路径正确
date_default_timezone_set('Asia/Kuala_Lumpur');

// 1. 基本登录检查
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit();
}

// 2. 🌟 严格权限拦截：如果不是 Admin，直接踢回列表页
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

$status = "";
$error = "";

// 3. 🌟 获取所有学生列表（供 Admin 颁发奖项时选择）
$student_sql = "SELECT user_id, full_name, username FROM users WHERE role = 'student' ORDER BY full_name ASC";
$student_result = $conn->query($student_sql);
?>

<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Add Milestone | Academic Curator</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Manrope:wght@700;800&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <script src="https://cdn.tailwindcss.com?plugins=forms"></script>
    
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: { primary: "#003f87", surface: "#f6faff" },
                    fontFamily: { headline: ["Manrope"], body: ["Inter"] }
                }
            }
        }
    </script>
    <style>
        .material-symbols-outlined { font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24; }
        .signature-gradient { background: linear-gradient(135deg, #003f87 0%, #0056b3 100%); }
        .input-elegant {
            background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 0.75rem; 
            padding: 0.75rem 1rem; width: 100%; transition: all 0.2s; color: #1e293b;
        }
        .input-elegant:focus {
            background-color: #ffffff; border-color: #003f87; outline: none; box-shadow: 0 0 0 3px rgba(0,63,135,0.1);
        }
        .achievement-bloom { position: relative; overflow: hidden; }
        .achievement-bloom::after {
            content: ''; position: absolute; top: -50%; right: -20%; width: 300px; height: 300px;
            background: linear-gradient(135deg, #f6d365 0%, #fda085 100%); opacity: 0.1; border-radius: 50%; pointer-events: none;
        }
    </style>
</head>
<body class="bg-surface font-body text-slate-800 min-h-screen">

    <nav class="bg-white border-b border-slate-200 px-6 py-4 fixed w-full top-0 z-50 shadow-sm flex justify-between items-center">
        <div class="flex items-center gap-3">
            <div class="w-8 h-8 signature-gradient rounded-lg flex items-center justify-center text-white">
                <span class="material-symbols-outlined text-sm">auto_stories</span>
            </div>
            <span class="font-headline font-bold text-primary tracking-tight">Academic Curator</span>
        </div>
        <a href="index.php" class="text-sm font-bold text-slate-500 hover:text-primary flex items-center gap-2 transition-colors">
            <span class="material-symbols-outlined text-lg">arrow_back</span> Return to Honor Roll
        </a>
    </nav>

    <main class="pt-28 pb-16 px-6 flex justify-center">
        <div class="w-full max-w-3xl bg-white rounded-[2rem] shadow-xl border border-slate-100 achievement-bloom relative">
            
            <div class="p-10 md:p-14">
                <div class="text-center mb-10">
                    <div class="w-16 h-16 bg-amber-100 text-amber-600 rounded-2xl flex items-center justify-center mx-auto mb-4 shadow-inner">
                        <span class="material-symbols-outlined text-3xl">workspace_premium</span>
                    </div>
                    <h1 class="font-headline text-3xl font-black text-slate-900 tracking-tight">Record New Milestone</h1>
                    <p class="text-slate-500 mt-2 font-medium">Issue an official achievement to a student's portfolio.</p>
                </div>

                <?php if($error != ""): ?>
                    <div class="mb-6 p-4 bg-red-50 border-l-4 border-red-500 text-red-700 flex items-center gap-3 rounded-r-xl">
                        <span class="material-symbols-outlined">error</span>
                        <span class="font-bold text-sm"><?php echo htmlspecialchars($error); ?></span>
                    </div>
                <?php endif; ?>

                <form action="process_add.php" method="POST" enctype="multipart/form-data" class="space-y-6">
                    
                    <div class="p-5 bg-blue-50 border border-blue-100 rounded-xl mb-6 shadow-inner relative">
                        <label class="block font-headline text-xs font-bold uppercase tracking-wider text-blue-800 mb-2 flex items-center gap-2">
                            <span class="material-symbols-outlined text-sm">person_add</span> Assign to Student
                        </label>
                        <select name="target_user_id" class="input-elegant bg-white border-blue-200" required>
                            <option value="">-- Select a student from the directory --</option>
                            <?php if ($student_result && $student_result->num_rows > 0): ?>
                                <?php while ($stu = $student_result->fetch_assoc()): ?>
                                    <option value="<?php echo $stu['user_id']; ?>">
                                        <?php echo htmlspecialchars($stu['full_name'] . ' (' . $stu['username'] . ')'); ?>
                                    </option>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </select>
                    </div>

                    <div>
                        <label class="block font-headline text-xs font-bold uppercase tracking-wider text-slate-500 mb-2">Achievement Title</label>
                        <div class="relative">
                            <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-slate-400">title</span>
                            <input type="text" name="achievement_title" class="input-elegant pl-12" placeholder="e.g. Dean's List, Hackathon Champion" required>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block font-headline text-xs font-bold uppercase tracking-wider text-slate-500 mb-2">Category</label>
                            <select name="achievement_category" class="input-elegant" required>
                                <option value="">-- Select Category --</option>
                                <option value="Academic">Academic</option>
                                <option value="Sports">Sports</option>
                                <option value="Arts & Culture">Arts & Culture</option>
                                <option value="Innovation/Tech">Innovation & Tech</option>
                                <option value="Leadership">Leadership</option>
                            </select>
                        </div>
                        <div>
                            <label class="block font-headline text-xs font-bold uppercase tracking-wider text-slate-500 mb-2">Level</label>
                            <select name="level" class="input-elegant" required>
                                <option value="">-- Select Level --</option>
                                <option value="University">University</option>
                                <option value="State">State</option>
                                <option value="National">National</option>
                                <option value="International">International</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block font-headline text-xs font-bold uppercase tracking-wider text-slate-500 mb-2">Issuer / Organizer</label>
                            <div class="relative">
                                <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-slate-400">corporate_fare</span>
                                <input type="text" name="issuer" class="input-elegant pl-12" placeholder="e.g. UTAR, MOHE" required>
                            </div>
                        </div>
                        <div>
                            <label class="block font-headline text-xs font-bold uppercase tracking-wider text-slate-500 mb-2">Date Received</label>
                            <div class="relative">
                                <input type="date" name="date_received" class="input-elegant text-slate-600" required>
                            </div>
                        </div>
                    </div>

                    <div class="mt-8 border border-slate-100 rounded-3xl p-6 bg-slate-50 shadow-inner relative">
                        <label class="block font-headline text-sm font-black uppercase tracking-widest text-primary mb-4 flex items-center gap-2">
                             <span class="material-symbols-outlined text-sm FILL">workspace_premium</span> Official Attachment <span class="text-xs font-bold text-slate-400">(Optional)</span>
                        </label>
                        
                        <div id="dropzone" class="p-8 border-2 border-dashed border-slate-300 rounded-3xl bg-white text-center group cursor-pointer relative hover:border-primary hover:bg-slate-50 transition-all flex items-center justify-center min-h-[220px]">
                            <input type="file" id="certificateInput" name="certificate_image" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-20" accept="image/jpeg,image/png">
                            
                            <div id="defaultMsg" class="w-full flex flex-col items-center">
                                <div class="w-16 h-16 rounded-full bg-blue-50 flex items-center justify-center border-4 border-white shadow-xl group-hover:scale-110 transition-transform">
                                    <span class="material-symbols-outlined text-4xl text-blue-600 transition-colors">cloud_upload</span>
                                </div>
                                <p class="mt-6 font-headline font-extrabold text-sm tracking-tight text-slate-800">
                                    Click to Upload Certificate
                                </p>
                                <p class="text-[11px] text-slate-400 font-bold mt-2 uppercase tracking-wider">Accepts JPG or PNG (Max 2MB)</p>
                            </div>

                            <div id="previewMsg" class="w-full flex-col items-center hidden relative z-30">
                                <div class="relative group">
                                    <img src="" id="imagePreview" class="max-h-[160px] rounded-2xl shadow-xl border-4 border-white" alt="Certificate Preview">
                                    <button type="button" id="removeImageBtn" class="absolute -top-3 -right-3 w-8 h-8 rounded-full bg-red-600 text-white flex items-center justify-center shadow-lg opacity-0 group-hover:opacity-100 transition-all hover:bg-red-700 active:scale-95">
                                        <span class="material-symbols-outlined text-lg">close</span>
                                    </button>
                                </div>
                                <p class="text-xs font-bold text-emerald-600 mt-3 flex items-center gap-1.5"><span class="material-symbols-outlined text-xs">check_circle</span> New Certificate Selected</p>
                            </div>
                        </div>
                    </div>

                    <div class="pt-4 border-t border-slate-100">
                        <label class="block font-headline text-xs font-bold uppercase tracking-wider text-slate-500 mb-2 flex items-center gap-1">
                            Related Campus Event <span class="material-symbols-outlined text-[14px] text-primary" title="Optional: Link to an internal UTAR event">info</span>
                        </label>
                        <select name="event_id" class="input-elegant">
                            <option value="">-- No linked event (Off-campus) --</option>
                            <?php 
                            $event_query = "SELECT event_id, event_name FROM events WHERE deleted = 0 ORDER BY event_name ASC";
                            $event_result = mysqli_query($conn, $event_query);
                            if ($event_result) {
                                while($evt = mysqli_fetch_assoc($event_result)) { 
                                    echo "<option value='".$evt['event_id']."'>".htmlspecialchars($evt['event_name'])."</option>";
                                }
                            }
                            ?>
                        </select>
                    </div>

                    <div>
                        <label class="block font-headline text-xs font-bold uppercase tracking-wider text-slate-500 mb-2">Description / Remarks</label>
                        <textarea name="achievement_description" class="input-elegant" rows="3" placeholder="Briefly describe the student's role or achievement details..."></textarea>
                    </div>

                    <div class="pt-8">
                        <button type="submit" class="w-full signature-gradient text-white py-5 rounded-2xl font-headline font-black tracking-widest uppercase shadow-xl hover:shadow-2xl hover:-translate-y-1 active:scale-95 transition-all flex items-center justify-center gap-2">
                            <span class="material-symbols-outlined">add_task</span> Publish Achievement
                        </button>
                    </div>
                </form>

            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        const dropzone = document.getElementById('dropzone');
        const fileInput = document.getElementById('certificateInput');
        const defaultMsg = document.getElementById('defaultMsg');
        const previewMsg = document.getElementById('previewMsg');
        const imagePreview = document.getElementById('imagePreview');
        const removeBtn = document.getElementById('removeImageBtn');

        // 核心：处理文件输入
        fileInput.addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                // 1. 基础验证：确保是图片
                if (!file.type.match('image.*')) {
                    alert('Warning: Only image files (.jpg, .png) are accepted!');
                    this.value = ''; // 清空选择
                    return;
                }
                
                // 2. 基础验证：确保不超过2MB
                if (file.size > 2 * 1024 * 1024) { 
                    alert('Warning: New file exceeds 2MB limit!');
                    this.value = '';
                    return;
                }

                // 3. 实时预览魔法
                const reader = new FileReader();
                reader.addEventListener('load', function() {
                    imagePreview.src = reader.result;
                    
                    // 切换显示状态
                    defaultMsg.classList.add('hidden');
                    defaultMsg.classList.remove('flex');
                    previewMsg.classList.add('flex');
                    previewMsg.classList.remove('hidden');
                });
                reader.readAsDataURL(file); // 读取文件内容为 Base64
            }
        });

        // 移除图片按钮逻辑
        removeBtn.addEventListener('click', function(e) {
            e.preventDefault(); // 防止按钮触发表单
            e.stopPropagation(); // 防止点击穿透到 Input
            
            fileInput.value = ''; // 彻底清空 Input
            
            // 恢复默认状态
            defaultMsg.classList.remove('hidden');
            defaultMsg.classList.add('flex');
            previewMsg.classList.remove('flex');
            previewMsg.classList.add('hidden');
        });
    </script>
</body>
</html>