<?php
session_start();

if (isset($_SESSION['emp_logged_in']) && $_SESSION['emp_logged_in'] === true) {
    header('Location: employee.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = trim($_POST['username'] ?? '');
    $pass = trim($_POST['password'] ?? '');

    $valid_users = [
        'admin'   => 'admin123',
        'hr'      => 'hr@2025',
        'manager' => 'manager@2025',
    ];

    if ($user === '' || $pass === '') {
        $error = 'Please enter both username and password.';
    } elseif (isset($valid_users[$user]) && $valid_users[$user] === $pass) {
        $_SESSION['emp_logged_in'] = true;
        $_SESSION['emp_user']      = $user;
        $_SESSION['emp_login_time'] = time();
        header('Location: employee.php');
        exit;
    } else {
        $error = 'Invalid username or password. Please try again.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Login – Employee Manager</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display&family=DM+Sans:ital,wght@0,300;0,400;0,500;0,600;1,400&display=swap" rel="stylesheet"/>
<style>
:root{
    --navy:#0d1b2a; --navy-mid:#1b2e45; --navy-light:#243b55;
    --gold:#f0a500; --gold-dark:#c98a00; --cream:#fdf6e3;
    --text-main:#d8eaf8; --text-soft:#8aafc8;
    --danger:#e05c5c; --success:#3ec98a;
    --border:rgba(240,165,0,.22);
}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
body{
    font-family:'DM Sans',sans-serif;
    background:var(--navy);
    background-image:
        radial-gradient(ellipse at 10% 15%, rgba(240,165,0,.13) 0%, transparent 50%),
        radial-gradient(ellipse at 88% 78%, rgba(62,201,138,.09) 0%, transparent 50%);
    min-height:100vh;
    display:flex;
    align-items:center;
    justify-content:center;
    padding:20px;
    color:var(--text-main);
}
.grid-lines{
    position:fixed;inset:0;pointer-events:none;
    background-image:
        linear-gradient(rgba(255,255,255,.02) 1px, transparent 1px),
        linear-gradient(90deg, rgba(255,255,255,.02) 1px, transparent 1px);
    background-size:60px 60px;
}
.wrapper{
    width:100%;max-width:460px;
    background:var(--navy-mid);
    border:1px solid var(--border);
    border-radius:22px;
    box-shadow:0 30px 80px rgba(0,0,0,.6);
    animation:fadeUp .45s ease;
    position:relative;
}
.wrapper::before{
    content:'';
    position:absolute;top:0;left:10%;right:10%;height:1px;
    background:linear-gradient(90deg,transparent,var(--gold),transparent);
    opacity:.5;border-radius:100%;
}
@keyframes fadeUp{from{opacity:0;transform:translateY(24px)}to{opacity:1;transform:translateY(0)}}
.card-brand{
    display:flex;align-items:center;justify-content:center;gap:12px;
    padding:32px 44px 0;
}
.brand-icon{
    width:42px;height:42px;border-radius:11px;
    background:linear-gradient(135deg,var(--gold-dark),var(--gold));
    display:flex;align-items:center;justify-content:center;font-size:20px;
    box-shadow:0 5px 16px rgba(240,165,0,.35);
}
.brand-name{
    font-family:'DM Serif Display',serif;
    font-size:22px;color:var(--cream);letter-spacing:-.3px;
}
.brand-name span{color:var(--gold);}
.right{
    padding:28px 44px 44px;
    display:flex;flex-direction:column;
}
.form-title{
    font-family:'DM Serif Display',serif;
    font-size:26px;color:var(--gold);
    margin-bottom:6px;text-align:center;
}
.form-sub{color:var(--text-soft);font-size:13.5px;margin-bottom:28px;text-align:center;}
.alert-error{
    background:rgba(224,92,92,.1);border:1px solid rgba(224,92,92,.35);
    color:#f87171;font-size:13px;padding:11px 14px;
    border-radius:9px;margin-bottom:20px;
    display:flex;align-items:center;gap:8px;
}
.field{display:flex;flex-direction:column;gap:7px;margin-bottom:18px;}
.field label{
    font-size:.72rem;font-weight:600;color:var(--text-soft);
    text-transform:uppercase;letter-spacing:.9px;
}
.input-wrap{position:relative;}
.input-icon{
    position:absolute;left:14px;top:50%;transform:translateY(-50%);
    color:var(--text-soft);font-size:14px;pointer-events:none;
}
.field input{
    width:100%;
    padding:12px 14px 12px 40px;
    background:var(--navy-light);
    border:1px solid rgba(240,165,0,.22);
    border-radius:9px;
    color:var(--cream);
    font-family:'DM Sans',sans-serif;font-size:.92rem;
    outline:none;
    transition:border-color .22s,box-shadow .22s;
}
.field input::placeholder{color:rgba(138,175,200,.55);}
.field input:focus{
    border-color:var(--gold);
    box-shadow:0 0 0 3px rgba(240,165,0,.14);
}
.toggle-pw{
    position:absolute;right:13px;top:50%;transform:translateY(-50%);
    background:none;border:none;cursor:pointer;
    color:var(--text-soft);font-size:14px;transition:color .2s;
}
.toggle-pw:hover{color:var(--cream);}
.row{
    display:flex;align-items:center;justify-content:space-between;
    margin-bottom:22px;
}
.remember{
    display:flex;align-items:center;gap:7px;
    font-size:13px;color:var(--text-soft);cursor:pointer;user-select:none;
}
.remember input[type=checkbox]{accent-color:var(--gold);width:14px;height:14px;cursor:pointer;}
.forgot{font-size:13px;color:var(--gold);text-decoration:none;}
.forgot:hover{text-decoration:underline;}
.btn-login{
    width:100%;padding:14px;
    background:linear-gradient(135deg,var(--gold),var(--gold-dark));
    color:var(--navy);font-family:'DM Serif Display',serif;
    font-size:1rem;font-weight:700;letter-spacing:.3px;
    border:none;border-radius:10px;cursor:pointer;
    box-shadow:0 5px 18px rgba(240,165,0,.35);
    transition:transform .15s,box-shadow .2s,opacity .2s;
}
.btn-login:hover{transform:translateY(-2px);box-shadow:0 9px 26px rgba(240,165,0,.48);}
.btn-login:active{transform:translateY(0);}
.demo-hint{
    margin-top:22px;
    background:rgba(62,201,138,.07);
    border:1px solid rgba(62,201,138,.2);
    border-radius:9px;padding:12px 16px;
}
.demo-hint p{
    font-size:.73rem;color:var(--text-soft);
    margin-bottom:6px;font-weight:600;
    text-transform:uppercase;letter-spacing:.6px;
}
.cred-row{
    display:flex;justify-content:space-between;
    font-size:.8rem;color:var(--text-main);margin-top:4px;
}
.cred-row span:first-child{color:var(--text-soft);}
.cred-row strong{color:var(--success);font-family:monospace;font-size:.85rem;}
@media(max-width:500px){
    .right{padding:24px 24px 36px;}
    .card-brand{padding:24px 24px 0;}
}
</style>
</head>
<body>

<div class="grid-lines"></div>

<div class="wrapper">

    <!-- Brand -->
    <div class="card-brand">
        <div class="brand-icon">🏢</div>
        <div class="brand-name">Staff<span>Portal</span></div>
    </div>

    <!-- Form Panel -->
    <div class="right">

        <div class="form-title">Welcome back</div>
        <p class="form-sub">Sign in to access the Employee Portal.</p>

        <?php if ($error): ?>
            <div class="alert-error">⚠️ <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" autocomplete="off">

            <div class="field">
                <label>Username</label>
                <div class="input-wrap">
                    <span class="input-icon">👤</span>
                    <input type="text" name="username"
                           placeholder="Enter your username"
                           value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                           required autofocus/>
                </div>
            </div>

            <div class="field">
                <label>Password</label>
                <div class="input-wrap">
                    <span class="input-icon">🔒</span>
                    <input type="password" name="password" id="pwInput"
                           placeholder="Enter your password" required/>
                    <button type="button" class="toggle-pw" id="eyeBtn" onclick="togglePw()">👁</button>
                </div>
            </div>

            <div class="row">
                <label class="remember">
                    <input type="checkbox" name="remember"/> Remember me
                </label>
                <a href="#" class="forgot">Forgot password?</a>
            </div>

            <button type="submit" class="btn-login">Sign In →</button>

        </form>

        <!-- Demo Credentials Hint -->
        <div class="demo-hint">
            <p>Demo Credentials</p>
            <div class="cred-row"><span>Admin</span><strong>admin / admin123</strong></div>
            <div class="cred-row"><span>HR</span><strong>hr / hr@2025</strong></div>
            <div class="cred-row"><span>Manager</span><strong>manager / manager@2025</strong></div>
        </div>

    </div><!-- /.right -->

</div><!-- /.wrapper -->

<script>
function togglePw(){
    const pw = document.getElementById('pwInput');
    pw.type = pw.type === 'password' ? 'text' : 'password';
    document.getElementById('eyeBtn').textContent = pw.type === 'password' ? '👁' : '🙈';
}
</script>

</body>
</html>