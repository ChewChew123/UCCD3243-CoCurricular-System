<?php
// File path: modules/achievements/edit_achievement.php
session_start();
require('../../includes/db_connect.php');

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

if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$id = $_GET['id'];

// 3. 获取数据
$sql = "SELECT a.*, u.full_name, u.username 
        FROM achievements a 
        JOIN users u ON a.user_id = u.user_id 
        WHERE a.achievement_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();

if (!$data) {
    die("Achievement not found or access denied.");
}
?>

<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Edit Milestone | Academic Curator</title>
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
                    <div class="w-16 h-16 bg-blue-100 text-blue-600 rounded-2xl flex items-center justify-center mx-auto mb-4 shadow-inner">
                        <span class="material-symbols-outlined text-3xl">edit_document</span>
                    </div>
                    <h1 class="font-headline text-3xl font-black text-slate-900 tracking-tight">Edit Milestone</h1>
                    <p class="text-slate-500 mt-2 font-medium">Update the details of this official record.</p>
                </div>

                <form action="process_edit.php" method="POST" enctype="multipart/form-data" class="space-y-6">
                    <input type="hidden" name="achievement_id" value="<?php echo $data['achievement_id']; ?>">

                    <div class="p-4 bg-slate-50 border border-slate-200 rounded-xl mb-6 flex items-center gap-4">
                        <div class="w-10 h-10 bg-slate-200 rounded-full flex items-center justify-center text-slate-500">
                            <span class="material-symbols-outlined">person</span>
                        </div>
                        <div>
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Record belongs to</p>
                            <p class="font-bold text-slate-800"><?php echo htmlspecialchars($data['full_name']); ?> <span class="text-slate-500 font-normal">(<?php echo htmlspecialchars($data['username']); ?>)</span></p>
                        </div>
                    </div>

                    <div>
                        <label class="block font-headline text-xs font-bold uppercase tracking-wider text-slate-500 mb-2">Achievement Title</label>
                        <div class="relative">
                            <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-slate-400">title</span>
                            <input type="text" name="achievement_title" class="input-elegant pl-12" value="<?php echo htmlspecialchars($data['achievement_title']); ?>" required>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block font-headline text-xs font-bold uppercase tracking-wider text-slate-500 mb-2">Category</label>
                            <select name="achievement_category" class="input-elegant" required>
                                <option value="Academic" <?php if($data['achievement_category'] == 'Academic') echo 'selected'; ?>>Academic</option>
                                <option value="Sports" <?php if($data['achievement_category'] == 'Sports') echo 'selected'; ?>>Sports</option>
                                <option value="Arts & Culture" <?php if($data['achievement_category'] == 'Arts & Culture') echo 'selected'; ?>>Arts & Culture</option>
                                <option value="Innovation/Tech" <?php if($data['achievement_category'] == 'Innovation/Tech') echo 'selected'; ?>>Innovation & Tech</option>
                                <option value="Leadership" <?php if($data['achievement_category'] == 'Leadership') echo 'selected'; ?>>Leadership</option>
                            </select>
                        </div>
                        <div>
                            <label class="block font-headline text-xs font-bold uppercase tracking-wider text-slate-500 mb-2">Level</label>
                            <select name="level" class="input-elegant" required>
                                <option value="University" <?php if($data['level'] == 'University') echo 'selected'; ?>>University</option>
                                <option value="State" <?php if($data['level'] == 'State') echo 'selected'; ?>>State</option>
                                <option value="National" <?php if($data['level'] == 'National') echo 'selected'; ?>>National</option>
                                <option value="International" <?php if($data['level'] == 'International') echo 'selected'; ?>>International</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block font-headline text-xs font-bold uppercase tracking-wider text-slate-500 mb-2">Issuer / Organizer</label>
                            <div class="relative">
                                <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-slate-400">corporate_fare</span>
                                <input type="text" name="issuer" class="input-elegant pl-12" value="<?php echo htmlspecialchars($data['issuer']); ?>" required>
                            </div>
                        </div>
                        <div>
                            <label class="block font-headline text-xs font-bold uppercase tracking-wider text-slate-500 mb-2">Date Received</label>
                            <div class="relative">
                                <input type="date" name="date_received" class="input-elegant text-slate-600" value="<?php echo htmlspecialchars($data['date_received']); ?>" required>
                            </div>
                        </div>
                    </div>

                    <div class="mt-8 border border-slate-100 rounded-3xl p-6 bg-slate-50 shadow-inner relative">
                        <label class="block font-headline text-sm font-black uppercase tracking-widest text-primary mb-4 flex items-center gap-2">
                             <span class="material-symbols-outlined text-sm">image</span> Update Certificate
                        </label>
                        
                        <div id="dropzone" class="p-8 border-2 border-dashed border-slate-300 rounded-3xl bg-white text-center group cursor-pointer relative hover:border-primary transition-all min-h-[200px] flex items-center justify-center">
                            <input type="file" id="certificateInput" name="certificate_image" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-20" accept="image/jpeg,image/png">
                            
                            <div id="defaultMsg" class="w-full flex flex-col items-center">
                                <?php if (!empty($data['certificate_image'])): ?>
                                    <div class="relative">
                                        <img src="../../uploads/certificates/<?php echo htmlspecialchars($data['certificate_image']); ?>" class="max-h-[140px] rounded-xl shadow-md border-4 border-white" alt="Current Certificate">
                                        <div class="absolute -bottom-2 -right-2 bg-primary text-white text-[9px] font-bold px-2 py-1 rounded-full uppercase tracking-tighter shadow-lg">Current File</div>
                                    </div>
                                    <p class="mt-4 text-xs font-bold text-slate-400 uppercase tracking-widest group-hover:text-primary transition-colors">Click to replace image</p>
                                <?php else: ?>
                                    <span class="material-symbols-outlined text-4xl text-slate-300 group-hover:text-primary transition-colors">cloud_upload</span>
                                    <p class="mt-2 font-bold text-sm text-slate-500">No certificate uploaded. Click to add.</p>
                                <?php endif; ?>
                            </div>

                            <div id="previewMsg" class="w-full flex-col items-center hidden relative z-30">
                                <div class="relative group/preview">
                                    <img src="" id="imagePreview" class="max-h-[160px] rounded-2xl shadow-xl border-4 border-emerald-400" alt="New Preview">
                                    <button type="button" id="removeImageBtn" class="absolute -top-3 -right-3 w-8 h-8 rounded-full bg-red-600 text-white flex items-center justify-center shadow-lg transition-all hover:bg-red-700 active:scale-95">
                                        <span class="material-symbols-outlined text-lg">close</span>
                                    </button>
                                </div>
                                <p class="text-xs font-bold text-emerald-600 mt-3 flex items-center gap-1.5"><span class="material-symbols-outlined text-xs">check_circle</span> New Image Selected</p>
                            </div>
                        </div>
                    </div>

                    <div class="pt-4 border-t border-slate-100">
                        <label class="block font-headline text-xs font-bold uppercase tracking-wider text-slate-500 mb-2">Related Campus Event</label>
                        <select name="event_id" class="input-elegant">
                            <option value="">-- No linked event (Off-campus) --</option>
                            <?php 
                            $event_query = "SELECT event_id, event_name FROM events WHERE deleted = 0 ORDER BY event_name ASC";
                            $event_result = mysqli_query($conn, $event_query);
                            if ($event_result) {
                                while($evt = mysqli_fetch_assoc($event_result)) { 
                                    $selected = ($data['event_id'] == $evt['event_id']) ? 'selected' : '';
                                    echo "<option value='".$evt['event_id']."' $selected>".htmlspecialchars($evt['event_name'])."</option>";
                                }
                            }
                            ?>
                        </select>
                    </div>

                    <div>
                        <label class="block font-headline text-xs font-bold uppercase tracking-wider text-slate-500 mb-2">Description / Remarks</label>
                        <textarea name="achievement_description" class="input-elegant" rows="3"><?php echo htmlspecialchars($data['achievement_description'] ?? ''); ?></textarea>
                    </div>

                    <div class="pt-6 flex gap-4">
                        <button type="submit" class="flex-1 signature-gradient text-white py-4 rounded-xl font-headline font-black tracking-widest uppercase shadow-xl hover:shadow-2xl hover:-translate-y-1 active:scale-95 transition-all flex items-center justify-center gap-2">
                            <span class="material-symbols-outlined">save</span> Save Changes
                        </button>
                        <a href="index.php" class="px-8 py-4 rounded-xl font-headline font-bold uppercase tracking-widest text-slate-400 bg-slate-100 hover:bg-slate-200 transition-colors flex items-center justify-center">
                            Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <script>
        const fileInput = document.getElementById('certificateInput');
        const defaultMsg = document.getElementById('defaultMsg');
        const previewMsg = document.getElementById('previewMsg');
        const imagePreview = document.getElementById('imagePreview');
        const removeBtn = document.getElementById('removeImageBtn');

        fileInput.addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                if (!file.type.match('image.*')) {
                    alert('Warning: Only image files are accepted!');
                    this.value = '';
                    return;
                }
                
                const reader = new FileReader();
                reader.addEventListener('load', function() {
                    imagePreview.src = reader.result;
                    defaultMsg.classList.add('hidden');
                    previewMsg.classList.remove('hidden');
                    previewMsg.classList.add('flex');
                });
                reader.readAsDataURL(file);
            }
        });

        removeBtn.addEventListener('click', function(e) {
            e.preventDefault(); e.stopPropagation();
            fileInput.value = '';
            defaultMsg.classList.remove('hidden');
            previewMsg.classList.add('hidden');
            previewMsg.classList.remove('flex');
        });
    </script>
</body>
</html>