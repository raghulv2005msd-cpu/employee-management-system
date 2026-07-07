<?php
session_start();

/* ── Capture info before destroying session ── */
$logged_user  = $_SESSION['emp_user']       ?? 'User';
$login_time   = $_SESSION['emp_login_time'] ?? time();

/* ── Destroy the session ── */
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $p['path'], $p['domain'], $p['secure'], $p['httponly']);
}
session_destroy();

/* ── Calculate session duration ── */
$duration_secs = time() - $login_time;
$dur_h  = floor($duration_secs / 3600);
$dur_m  = floor(($duration_secs % 3600) / 60);
$dur_s  = $duration_secs % 60;
if ($dur_h > 0)       $dur_label = "{$dur_h}h {$dur_m}m";
elseif ($dur_m > 0)   $dur_label = "{$dur_m}m {$dur_s}s";
else                  $dur_label = "{$dur_s} seconds";

$signed_out_at = date('h:i A', time());
$signed_out_date = date('d M Y');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Signed Out – Employee Manager</title>
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
        radial-gradient(ellipse at 15% 20%, rgba(240,165,0,.12) 0%, transparent 50%),
        radial-gradient(ellipse at 85% 75%, rgba(62,201,138,.09) 0%, transparent 50%);
    min-height:100vh;
    display:flex;align-items:center;justify-content:center;
    padding:20px;color:var(--text-main);overflow:hidden;
}
.grid-lines{
    position:fixed;inset:0;pointer-events:none;
    background-image:
        linear-gradient(rgba(255,255,255,.02) 1px, transparent 1px),
        linear-gradient(90deg,rgba(255,255,255,.02) 1px,transparent 1px);
    background-size:60px 60px;
}

/* ── Card ── */
.card{
    position:relative;z-index:1;
    background:var(--navy-mid);
    border:1px solid var(--border);
    border-radius:22px;
    padding:52px 48px;
    max-width:480px;width:100%;
    text-align:center;
    box-shadow:0 30px 80px rgba(0,0,0,.55);
    animation:cardIn .55s cubic-bezier(.22,1,.36,1) both;
}
@keyframes cardIn{
    from{opacity:0;transform:translateY(28px) scale(.97)}
    to{opacity:1;transform:translateY(0) scale(1)}
}
.card::before{
    content:'';
    position:absolute;top:0;left:10%;right:10%;height:1px;
    background:linear-gradient(90deg,transparent,var(--gold),transparent);
    opacity:.45;border-radius:100%;
}

/* ── Icon ── */
.icon-wrap{
    width:84px;height:84px;margin:0 auto 28px;position:relative;
    animation:iconPop .5s .25s cubic-bezier(.22,1,.36,1) both;
}
@keyframes iconPop{from{opacity:0;transform:scale(.5)}to{opacity:1;transform:scale(1)}}
.icon-bg{
    width:84px;height:84px;border-radius:22px;
    background:linear-gradient(135deg,rgba(62,201,138,.14),rgba(240,165,0,.1));
    border:1px solid rgba(62,201,138,.25);
    display:flex;align-items:center;justify-content:center;font-size:36px;
}
.icon-badge{
    position:absolute;bottom:-6px;right:-6px;
    width:28px;height:28px;
    background:linear-gradient(135deg,var(--success),#10b981);
    border-radius:50%;
    display:flex;align-items:center;justify-content:center;font-size:13px;
    border:2px solid var(--navy);
}

/* ── Text ── */
.card-title{
    font-family:'DM Serif Display',serif;
    font-size:26px;color:var(--cream);letter-spacing:-.3px;
    margin-bottom:8px;
    animation:textIn .5s .35s ease both;
}
.card-sub{
    color:var(--text-soft);font-size:14px;line-height:1.65;
    margin-bottom:28px;
    animation:textIn .5s .42s ease both;
}
@keyframes textIn{from{opacity:0;transform:translateY(10px)}to{opacity:1;transform:translateY(0)}}

/* ── Session info box ── */
.session-info{
    background:rgba(255,255,255,.03);
    border:1px solid rgba(255,255,255,.07);
    border-radius:12px;padding:16px 20px;
    display:flex;flex-direction:column;gap:11px;
    margin-bottom:26px;text-align:left;
    animation:textIn .5s .5s ease both;
}
.info-row{display:flex;justify-content:space-between;align-items:center;font-size:13px;}
.info-label{color:var(--text-soft);}
.info-val{font-weight:600;font-size:13px;}
.info-val.green{color:var(--success);}
.info-val.gold{color:var(--gold);}

/* ── Countdown ── */
.redirect-msg{
    font-size:13px;color:var(--text-soft);margin-bottom:14px;
    animation:textIn .5s .57s ease both;
}
.redirect-msg span{color:var(--gold);font-weight:700;}

/* ── Progress bar ── */
.progress-track{
    height:3px;background:rgba(255,255,255,.06);
    border-radius:100px;overflow:hidden;
    margin-bottom:26px;
    animation:textIn .5s .57s ease both;
}
.progress-fill{
    height:100%;
    background:linear-gradient(90deg,var(--gold),var(--success));
    border-radius:100px;
    transform-origin:left;
    animation:drain 5s linear forwards;
}
@keyframes drain{from{transform:scaleX(1)}to{transform:scaleX(0)}}

/* ── Buttons ── */
.actions{
    display:flex;gap:12px;
    animation:textIn .5s .64s ease both;
}
.btn{
    flex:1;padding:13px;border-radius:10px;
    font-family:'DM Sans',sans-serif;font-size:14px;font-weight:600;
    cursor:pointer;transition:all .2s;
}
.btn-primary{
    background:linear-gradient(135deg,var(--gold),var(--gold-dark));
    border:none;color:var(--navy);
    font-family:'DM Serif Display',serif;font-size:.95rem;
    box-shadow:0 5px 18px rgba(240,165,0,.3);
}
.btn-primary:hover{transform:translateY(-2px);box-shadow:0 9px 26px rgba(240,165,0,.45);}
.btn-secondary{
    background:transparent;
    border:1px solid rgba(255,255,255,.1);color:var(--text-soft);
}
.btn-secondary:hover{border-color:rgba(255,255,255,.22);color:var(--text-main);background:rgba(255,255,255,.04);}

/* ── Footer ── */
.footer-note{
    margin-top:26px;font-size:12px;color:var(--text-soft);
    animation:textIn .5s .72s ease both;
}
.footer-note a{color:var(--gold);text-decoration:none;}
.footer-note a:hover{text-decoration:underline;}

/* ── Brand strip ── */
.brand-strip{
    position:fixed;bottom:22px;left:50%;transform:translateX(-50%);
    display:flex;align-items:center;gap:8px;
    color:var(--text-soft);font-size:12px;z-index:2;
}
.brand-icon-sm{
    width:22px;height:22px;border-radius:6px;
    background:linear-gradient(135deg,var(--gold-dark),var(--gold));
    display:flex;align-items:center;justify-content:center;font-size:11px;
}
</style>
</head>
<body>
<div class="grid-lines"></div>

<div class="card">

    <div class="icon-wrap">
        <div class="icon-bg">👋</div>
        <div class="icon-badge">✓</div>
    </div>

    <h1 class="card-title">Goodbye, <?= htmlspecialchars(ucfirst($logged_user)) ?>!</h1>
    <p class="card-sub">
        You have been signed out securely.<br/>
        Your session data has been cleared.
    </p>

    <div class="session-info">
        <div class="info-row">
            <span class="info-label">Session status</span>
            <span class="info-val green">● Terminated</span>
        </div>
        <div class="info-row">
            <span class="info-label">Signed in as</span>
            <span class="info-val gold"><?= htmlspecialchars($logged_user) ?></span>
        </div>
        <div class="info-row">
            <span class="info-label">Signed out at</span>
            <span class="info-val"><?= $signed_out_at ?>, <?= $signed_out_date ?></span>
        </div>
        <div class="info-row">
            <span class="info-label">Session duration</span>
            <span class="info-val"><?= htmlspecialchars($dur_label) ?></span>
        </div>
    </div>

    <p class="redirect-msg">Redirecting to login in <span id="countdown">5</span>s…</p>
    <div class="progress-track"><div class="progress-fill" id="progressFill"></div></div>

    <div class="actions">
        <button class="btn btn-primary" onclick="goLogin()">Sign In Again</button>
        <button class="btn btn-secondary" id="cancelBtn" onclick="cancelRedirect()">Stay Here</button>
    </div>

    <div class="footer-note">
        Need help? <a href="mailto:hr@company.com">Contact HR Support</a> &nbsp;·&nbsp;
        <a href="mailto:it@company.com">IT Helpdesk</a>
    </div>
</div>

<div class="brand-strip">
    <div class="brand-icon-sm">👥</div>
    StaffPortal &copy; <?= date('Y') ?>
</div>

<script>
let count = 5, timer;
const cd = document.getElementById('countdown');

function startCountdown() {
    timer = setInterval(() => {
        count--;
        cd.textContent = count;
        if (count <= 0) { clearInterval(timer); goLogin(); }
    }, 1000);
}

function goLogin() {
    window.location.href = 'login.php';
}

function cancelRedirect() {
    clearInterval(timer);
    document.querySelector('.redirect-msg').textContent = 'Redirect cancelled. Click "Sign In Again" when ready.';
    document.getElementById('progressFill').style.animation = 'none';
    document.getElementById('progressFill').style.transform = 'scaleX(0)';
    document.getElementById('cancelBtn').disabled = true;
    document.getElementById('cancelBtn').style.opacity = '.4';
}

startCountdown();
</script>
</body>
</html>