<?php
require_once 'core/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Fetch the user, including the role column
    $stmt = $db->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$_POST['username']]);
    $user = $stmt->fetch();

    // 2. Verify password
    if ($user && password_verify($_POST['password'], $user['password'])) {
        
        // 3. SAVE TO SESSION
        // These values are now available across your entire admin panel
        $_SESSION['user_id']  = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role']     = $user['role']; // THIS IS THE KEY LINE

        // 4. Redirect to admin
        header('Location: admin');
        exit; 
    } else {
        $error = "Invalid credentials";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>CMS Login</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-slate-100 flex items-center justify-center h-screen">
    <form method="POST" class="bg-white p-8 rounded-xl shadow-lg w-96 border border-slate-200">
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-blue-600 text-white rounded-full mb-4 shadow-blue-200 shadow-lg">
                <i class="fa fa-lock text-2xl"></i>
            </div>
            <h2 class="text-2xl font-black text-slate-800 tracking-tight">Welcome Back</h2>
            <p class="text-slate-400 text-sm">Sign in to manage Locawork</p>
        </div>

        <?php if(isset($error)): ?>
            <div class="bg-red-50 text-red-600 p-3 rounded-lg mb-4 text-xs font-bold border border-red-100 flex items-center">
                <i class="fa fa-exclamation-circle mr-2"></i><?= $error ?>
            </div>
        <?php endif; ?>

        <div class="space-y-4">
            <div>
                <label class="block text-[10px] uppercase font-bold text-slate-400 mb-1 ml-1">Username</label>
                <input type="text" name="username" required 
                       class="w-full border border-slate-200 p-3 rounded-lg outline-none focus:ring-2 focus:ring-blue-500 transition-all">
            </div>
            
            <div>
                <label class="block text-[10px] uppercase font-bold text-slate-400 mb-1 ml-1">Password</label>
                <input type="password" name="password" required 
                       class="w-full border border-slate-200 p-3 rounded-lg outline-none focus:ring-2 focus:ring-blue-500 transition-all">
            </div>

            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold p-3 rounded-lg shadow-lg shadow-blue-200 transition-all transform active:scale-[0.98]">
                Sign In
            </button>
        </div>

        <div class="mt-8 text-center text-slate-400 text-xs italic">
            Secure Administrator Access
        </div>
    </form>
</body>
</html>