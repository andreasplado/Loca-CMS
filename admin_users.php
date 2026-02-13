<?php
// admin_users.php
require_once 'core/config.php';

$edit_user = null;

// 1. HANDLE DELETE
if (isset($_GET['delete_user'])) {
    $id = (int)$_GET['delete_user'];
    if ($id !== (int)$_SESSION['user_id']) {
        $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$id]);
        header("Location: ?page=users&msg=deleted");
        exit;
    } else {
        $error = "You cannot delete your own account!";
    }
}

// 2. FETCH USER FOR EDITING
if (isset($_GET['edit_id'])) {
    $stmt = $db->prepare("SELECT id, username, role FROM users WHERE id = ?");
    $stmt->execute([(int)$_GET['edit_id']]);
    $edit_user = $stmt->fetch();
}

// 3. HANDLE CREATE OR UPDATE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['add_user']) || isset($_POST['update_user']))) {
    $user = trim($_POST['username']);
    $pass = $_POST['password'];
    $role = $_POST['role'] ?? 'viewer'; // Default to viewer for safety
    $id   = isset($_POST['user_id']) ? (int)$_POST['user_id'] : null;

    if (!empty($user)) {
        if ($id) {
            // UPDATE
            if (!empty($pass)) {
                $hashed_pass = password_hash($pass, PASSWORD_DEFAULT);
                $stmt = $db->prepare("UPDATE users SET username = ?, password = ?, role = ? WHERE id = ?");
                $stmt->execute([$user, $hashed_pass, $role, $id]);
            } else {
                $stmt = $db->prepare("UPDATE users SET username = ?, role = ? WHERE id = ?");
                $stmt->execute([$user, $role, $id]);
            }
            header("Location: ?page=users&msg=updated");
            exit;
        } else {
            // CREATE
            if (!empty($pass)) {
                $hashed_pass = password_hash($pass, PASSWORD_DEFAULT);
                $stmt = $db->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
                $stmt->execute([$user, $hashed_pass, $role]);
                header("Location: ?page=users&msg=created");
                exit;
            } else {
                $error = "Password is required for new users.";
            }
        }
    }
}

$all_users = $db->query("SELECT id, username, role FROM users ORDER BY id DESC")->fetchAll();
?>

<div class="max-w-5xl mx-auto p-6">
    <?php if(isset($_GET['msg'])): ?>
        <div class="mb-6 p-4 bg-green-100 text-green-700 rounded-lg border border-green-200">
            <i class="fa fa-check-circle mr-2"></i> Action completed successfully.
        </div>
    <?php endif; ?>

    <div class="bg-white p-6 rounded-xl border shadow-sm mb-8 <?= $edit_user ? 'border-orange-400 ring-1 ring-orange-100' : '' ?>">
        <h3 class="text-lg font-bold text-slate-700 mb-4">
            <?= $edit_user ? '<i class="fa fa-user-shield text-orange-500 mr-2"></i>Edit User Permissions' : '<i class="fa fa-user-plus text-blue-500 mr-2"></i>Create New User' ?>
        </h3>
        
        <form method="POST" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
            <?php if($edit_user): ?>
                <input type="hidden" name="user_id" value="<?= $edit_user['id'] ?>">
            <?php endif; ?>

            <div>
                <label class="block text-[10px] uppercase font-bold text-slate-400 mb-1 ml-1">Username</label>
                <input type="text" name="username" value="<?= $edit_user ? htmlspecialchars($edit_user['username']) : '' ?>" required 
                       class="w-full border p-2 rounded-lg outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <div>
                <label class="block text-[10px] uppercase font-bold text-slate-400 mb-1 ml-1">Password</label>
                <input type="password" name="password" <?= $edit_user ? '' : 'required' ?>
                       class="w-full border p-2 rounded-lg outline-none focus:ring-2 focus:ring-blue-500" placeholder="<?= $edit_user ? 'Leave blank to keep' : '••••••••' ?>">
            </div>

            <div>
                <label class="block text-[10px] uppercase font-bold text-slate-400 mb-1 ml-1">User Role</label>
                <select name="role" class="w-full border p-2 rounded-lg outline-none focus:ring-2 focus:ring-blue-500 bg-white">
                    <option value="viewer" <?= ($edit_user && $edit_user['role'] == 'viewer') ? 'selected' : '' ?>>Viewer (Stats Only)</option>
                    <option value="admin" <?= ($edit_user && $edit_user['role'] == 'admin') ? 'selected' : '' ?>>Admin (Full Access)</option>
                </select>
            </div>

            <div class="flex gap-2">
                <button type="submit" name="<?= $edit_user ? 'update_user' : 'add_user' ?>" 
                        class="<?= $edit_user ? 'bg-orange-500' : 'bg-blue-600' ?> text-white w-full py-2 rounded-lg font-bold hover:opacity-90 transition">
                    <?= $edit_user ? 'Update' : 'Create' ?>
                </button>
                <?php if($edit_user): ?>
                    <a href="?page=users" class="bg-slate-100 text-slate-500 px-4 py-2 rounded-lg flex items-center justify-center"><i class="fa fa-times"></i></a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <div class="bg-white rounded-xl border shadow-sm overflow-hidden">
        <table class="w-full text-left">
            <thead>
                <tr class="bg-slate-50 border-b">
                    <th class="p-4 text-slate-600 font-bold uppercase text-[10px]">User</th>
                    <th class="p-4 text-slate-600 font-bold uppercase text-[10px]">Role</th>
                    <th class="p-4 text-slate-600 font-bold uppercase text-[10px] text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($all_users as $u): ?>
                    <tr class="border-b hover:bg-slate-50 transition">
                        <td class="p-4 font-medium text-slate-700">
                            <?= htmlspecialchars($u['username']) ?>
                            <?php if($u['id'] == $_SESSION['user_id']) echo '<span class="ml-2 text-[9px] bg-blue-100 text-blue-600 px-2 py-0.5 rounded-full">YOU</span>'; ?>
                        </td>
                        <td class="p-4">
                            <?php if($u['role'] == 'admin'): ?>
                                <span class="text-[10px] font-bold text-purple-600 bg-purple-50 px-2 py-1 rounded border border-purple-100"><i class="fa fa-shield-alt mr-1"></i> ADMIN</span>
                            <?php else: ?>
                                <span class="text-[10px] font-bold text-slate-500 bg-slate-50 px-2 py-1 rounded border border-slate-100"><i class="fa fa-eye mr-1"></i> VIEWER</span>
                            <?php endif; ?>
                        </td>
                        <td class="p-4 text-right flex justify-end gap-2">
                            <a href="?page=users&edit_id=<?= $u['id'] ?>" class="text-slate-400 hover:text-blue-500 p-2"><i class="fa fa-edit"></i></a>
                            <?php if($u['id'] != $_SESSION['user_id']): ?>
                                <a href="?page=users&delete_user=<?= $u['id'] ?>" onclick="return confirm('Delete user?')" class="text-slate-300 hover:text-red-500 p-2"><i class="fa fa-trash-alt"></i></a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>