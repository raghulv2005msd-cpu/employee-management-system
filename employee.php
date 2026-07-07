<?php


session_start();
if (!isset($_SESSION['emp_logged_in']) || $_SESSION['emp_logged_in'] !== true) {
    header('Location: index.php'); exit;
}

$loggedUser = $_SESSION['emp_user'] ?? 'User';
$fullName   = $_SESSION['emp_full_name'] ?? $loggedUser;

/*Derive role from logged-in username*/
$roleMap = [
    'admin'   => 'admin',
    'hr'      => 'hr',
    'manager' => 'manager',
];
$role = $roleMap[$loggedUser] ?? 'manager';

/* ── Role permission helpers ── */
$canAdd    = in_array($role, ['admin','hr','manager']);
$canEdit   = in_array($role, ['admin','hr','manager']); 
$canDelete = ($role === 'admin');                        
$canExport = in_array($role, ['admin','hr']);

/* ── Role display ── */
$roleLabels = ['admin'=>'Administrator','hr'=>'HR Officer','manager'=>'Manager'];
$roleColors = ['admin'=>'#f0a500','hr'=>'#7ca4ff','manager'=>'#3ec98a'];
$roleLabel  = $roleLabels[$role] ?? $role;
$roleColor  = $roleColors[$role] ?? '#8aafc8';

/* ── DB ── */
$servername = "localhost";
$username   = "root";
$password   = "";
$dbname     = "empdb";
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

/* ── Image Upload Helper ── */
function uploadSingleImage($fileInput, $oldPath = '') {
    $uploadDir = 'uploads/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
    $allowed = ['image/jpeg','image/png','image/gif','image/webp'];
    if (!isset($_FILES[$fileInput]) || $_FILES[$fileInput]['error'] !== UPLOAD_ERR_OK) return $oldPath;
    $file = $_FILES[$fileInput];
    if (!in_array($file['type'], $allowed)) return $oldPath;
    if ($oldPath && file_exists($oldPath)) unlink($oldPath);
    $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $dest = $uploadDir . uniqid('emp_', true) . '.' . $ext;
    return move_uploaded_file($file['tmp_name'], $dest) ? $dest : $oldPath;
}

/* ── Excel Export ── */
if (isset($_GET['export']) && $_GET['export'] === 'excel' && $canExport) {
    $filename = 'Employee_Details_' . date('Y-m-d') . '.xls';
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: max-age=0');
    $es  = isset($_GET['search']) ? $conn->real_escape_string(trim($_GET['search'])) : '';
    $sql = $es !== ''
        ? "SELECT empid,empname,email,mob,addres,designation,salary FROM empreg
           WHERE empname LIKE '%$es%' OR email LIKE '%$es%' OR empid LIKE '%$es%'
           OR designation LIKE '%$es%' OR mob LIKE '%$es%' ORDER BY empname"
        : "SELECT empid,empname,email,mob,addres,designation,salary FROM empreg ORDER BY empname";
    $result = $conn->query($sql);
    echo '<table border="1"><tr style="background:#c98a00;color:#fff;font-weight:bold;">
        <th>ID</th><th>Name</th><th>Email</th><th>Mobile</th><th>Address</th><th>Designation</th><th>Salary</th></tr>';
    if ($result && $result->num_rows > 0) {
        $n = 0;
        while ($r = $result->fetch_assoc()) {
            $bg = ($n++ % 2 === 0) ? '#f9f3e3' : '#ffffff';
            echo "<tr style='background:$bg;'>";
            foreach (['empid','empname','email','mob','addres','designation','salary'] as $col)
                echo '<td>'.htmlspecialchars($r[$col]).'</td>';
            echo '</tr>';
        }
    }
    echo '</table>'; exit;
}

/* ── Autocomplete AJAX ── */
if (isset($_GET['q'])) {
    header('Content-Type: application/json');
    $q = trim($conn->real_escape_string($_GET['q']));
    if ($q === '') { echo json_encode([]); exit; }
    $sql = "SELECT empid,empname,email,mob,addres,designation,salary FROM empreg
            WHERE empname LIKE '%$q%' OR email LIKE '%$q%' OR empid LIKE '%$q%'
               OR designation LIKE '%$q%' OR mob LIKE '%$q%' LIMIT 8";
    $res = $conn->query($sql); $data = [];
    if ($res && $res->num_rows > 0)
        while ($row = $res->fetch_assoc())
            $data[] = ['id'=>$row['empid'],'name'=>$row['empname'],'email'=>$row['email'],
                       'mob'=>$row['mob'],'address'=>$row['addres'],'designation'=>$row['designation'],'salary'=>$row['salary']];
    echo json_encode($data); exit;
}

/* ── VIEW Employee ── */
if (isset($_GET['view'])) {
    $view_id  = $conn->real_escape_string($_GET['view']);
    $view_res = $conn->query("SELECT * FROM empreg WHERE empid='$view_id'");
    $v        = $view_res ? $view_res->fetch_assoc() : null;
    if (!$v) { echo "<script>alert('Employee not found!'); window.close();</script>"; exit; }
    $vPhoto = $v['empimg'] ?? '';
?>
<!DOCTYPE html><html lang="en"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title><?= htmlspecialchars($v['empname']) ?> – Profile</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<style>
:root{--navy:#0d1b2a;--navy-mid:#1b2e45;--navy-light:#243b55;--gold:#f0a500;--gold-dark:#c98a00;
    --text-main:#d8eaf8;--text-soft:#8aafc8;--success:#3ec98a;--border:rgba(240,165,0,.2)}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
body{font-family:'DM Sans',sans-serif;background:var(--navy);
    background-image:radial-gradient(ellipse at 10% 15%,rgba(240,165,0,.13) 0%,transparent 50%),
    radial-gradient(ellipse at 88% 78%,rgba(62,201,138,.09) 0%,transparent 50%);
    min-height:100vh;color:var(--text-main);display:flex;align-items:center;justify-content:center;padding:40px 16px}
.card{width:100%;max-width:640px;background:var(--navy-mid);border:1px solid var(--border);
    border-radius:22px;box-shadow:0 30px 80px rgba(0,0,0,.55);overflow:hidden;animation:fadeUp .35s ease}
@keyframes fadeUp{from{opacity:0;transform:translateY(22px)}to{opacity:1;transform:translateY(0)}}
.card-header{background:linear-gradient(135deg,var(--gold-dark),var(--gold));padding:30px 32px 26px;display:flex;align-items:center;gap:22px}
.passport-wrap{width:100px;height:128px;border-radius:8px;overflow:hidden;flex-shrink:0;border:4px solid rgba(255,255,255,.85);box-shadow:0 8px 24px rgba(0,0,0,.35);background:rgba(13,27,42,.25);cursor:pointer;transition:transform .2s}
.passport-wrap:hover{transform:scale(1.05)}
.passport-wrap img{width:100%;height:100%;object-fit:cover;object-position:top center}
.pp-empty{width:100px;height:128px;border-radius:8px;flex-shrink:0;background:rgba(13,27,42,.3);border:4px solid rgba(255,255,255,.4);display:flex;align-items:center;justify-content:center;font-size:2.8rem}
.header-info{flex:1}
.emp-name{font-family:'DM Serif Display',serif;font-size:1.75rem;color:var(--navy);line-height:1.2}
.emp-desg{display:inline-block;margin-top:7px;background:rgba(13,27,42,.22);color:var(--navy);font-weight:700;font-size:.82rem;padding:4px 13px;border-radius:20px}
.emp-id{margin-top:8px;color:rgba(13,27,42,.55);font-size:.8rem;font-weight:600}
.card-body{padding:28px 32px}
.info-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px}
.info-item{display:flex;flex-direction:column;gap:4px}
.info-item.full{grid-column:1/-1}
.info-label{font-size:.68rem;font-weight:700;color:var(--text-soft);text-transform:uppercase;letter-spacing:.9px}
.info-value{font-size:.94rem;color:var(--text-main);background:var(--navy-light);padding:10px 14px;border-radius:9px;border:1px solid rgba(240,165,0,.15);word-break:break-word}
.info-value.salary{color:var(--success);font-weight:700;font-size:1.1rem}
.card-footer{padding:16px 32px 24px;display:flex;gap:12px;justify-content:flex-end;border-top:1px solid rgba(255,255,255,.06)}
.btn-close-tab{padding:10px 26px;background:linear-gradient(135deg,var(--gold),var(--gold-dark));color:var(--navy);font-family:'DM Sans',sans-serif;font-weight:700;font-size:.9rem;border:none;border-radius:24px;cursor:pointer;box-shadow:0 4px 16px rgba(240,165,0,.35);transition:transform .15s}
.btn-close-tab:hover{transform:translateY(-2px)}
.lightbox{display:none;position:fixed;inset:0;background:rgba(0,0,0,.9);z-index:2000;align-items:center;justify-content:center}
.lightbox.open{display:flex}
.lightbox img{max-width:40vw;max-height:80vh;border-radius:10px;box-shadow:0 24px 80px rgba(0,0,0,.8)}
.lb-close{position:absolute;top:18px;right:24px;color:#fff;font-size:2rem;cursor:pointer;background:none;border:none;line-height:1}
.lb-close:hover{color:var(--gold)}
</style></head><body>
<div class="card">
    <div class="card-header">
        <?php if ($vPhoto && file_exists($vPhoto)): ?>
            <div class="passport-wrap" onclick="openLb()"><img src="<?= htmlspecialchars($vPhoto) ?>" alt="photo"></div>
        <?php else: ?><div class="pp-empty">👤</div><?php endif; ?>
        <div class="header-info">
            <div class="emp-name"><?= htmlspecialchars($v['empname']) ?></div>
            <span class="emp-desg"><?= htmlspecialchars($v['designation']) ?></span>
            <div class="emp-id">🪪 ID: <?= htmlspecialchars($v['empid']) ?></div>
        </div>
    </div>
    <div class="card-body"><div class="info-grid">
        <div class="info-item"><span class="info-label">📧 Email</span><span class="info-value"><?= htmlspecialchars($v['email']) ?></span></div>
        <div class="info-item"><span class="info-label">📱 Mobile</span><span class="info-value"><?= htmlspecialchars($v['mob']) ?></span></div>
        <div class="info-item full"><span class="info-label">📍 Address</span><span class="info-value"><?= htmlspecialchars($v['addres']) ?></span></div>
        <div class="info-item"><span class="info-label">💼 Designation</span><span class="info-value"><?= htmlspecialchars($v['designation']) ?></span></div>
        <div class="info-item"><span class="info-label">💰 Salary</span><span class="info-value salary">₹<?= number_format((int)$v['salary']) ?></span></div>
    </div></div>
    <div class="card-footer"><button class="btn-close-tab" onclick="window.close()">✕ Close Tab</button></div>
</div>
<?php if ($vPhoto && file_exists($vPhoto)): ?>
<div class="lightbox" id="lightbox">
    <button class="lb-close" onclick="document.getElementById('lightbox').classList.remove('open')">✕</button>
    <img src="<?= htmlspecialchars($vPhoto) ?>" alt="photo">
</div>
<script>
function openLb(){document.getElementById('lightbox').classList.add('open');}
document.getElementById('lightbox').addEventListener('click',e=>{if(e.target===document.getElementById('lightbox'))document.getElementById('lightbox').classList.remove('open');});
document.addEventListener('keydown',e=>{if(e.key==='Escape')document.getElementById('lightbox').classList.remove('open');});
</script>
<?php endif; ?>
</body></html>
<?php exit; }

/* ── Delete Photo (AJAX) ── */
if (isset($_POST['delete_photo']) && $canEdit) {
    header('Content-Type: application/json');
    $empid = $conn->real_escape_string($_POST['empid']);
    $res   = $conn->query("SELECT empimg FROM empreg WHERE empid='$empid'");
    if ($res && $row = $res->fetch_assoc()) {
        $photo = $row['empimg'];
        if ($photo && file_exists($photo)) unlink($photo);
        $conn->query("UPDATE empreg SET empimg='' WHERE empid='$empid'");
        echo json_encode(['success'=>true]);
    } else { echo json_encode(['success'=>false]); }
    exit;
}

/* ── Delete Employee ── */
if (isset($_GET['delete']) && $canDelete) {
    $del_id = $conn->real_escape_string($_GET['delete']);
    $res    = $conn->query("SELECT empimg FROM empreg WHERE empid='$del_id'");
    if ($res && $row = $res->fetch_assoc())
        if ($row['empimg'] && file_exists($row['empimg'])) unlink($row['empimg']);
    $conn->query("DELETE FROM empreg WHERE empid='$del_id'");
    echo "<script>alert('Record Deleted'); window.location='employee.php';</script>"; exit;
}

/* ── Fetch for Edit ── */
$edit_data = null;
if (isset($_GET['edit']) && $canEdit) {
    $edit_id   = $conn->real_escape_string($_GET['edit']);
    $edit_res  = $conn->query("SELECT * FROM empreg WHERE empid='$edit_id'");
    $edit_data = $edit_res ? $edit_res->fetch_assoc() : null;
}

/* ── UPDATE ── */
if (isset($_POST['update']) && $canEdit) {
    $empid=$conn->real_escape_string($_POST['empid']);
    $empname=$conn->real_escape_string($_POST['empname']);
    $email=$conn->real_escape_string($_POST['email']);
    $mob=$conn->real_escape_string($_POST['mob']);
    $addres=$conn->real_escape_string($_POST['address']);
    $designation=$conn->real_escape_string($_POST['designation']);
    $salary=$conn->real_escape_string($_POST['salary']);
    $res=$conn->query("SELECT empimg FROM empreg WHERE empid='$empid'");
    $oldRow=$res?$res->fetch_assoc():[];
    $oldPath=$oldRow['empimg']??'';
    $empimg=$conn->real_escape_string(uploadSingleImage('img',$oldPath));
    $sql="UPDATE empreg SET empname='$empname',email='$email',mob='$mob',addres='$addres',designation='$designation',salary='$salary',empimg='$empimg' WHERE empid='$empid'";
    if ($conn->query($sql)===TRUE){echo "<script>alert('Record Updated Successfully');window.location='employee.php';</script>";exit;}
    else{echo "Error: ".$conn->error;}
}

/* ── INSERT ── */
if (isset($_POST['submit']) && $canAdd) {
    $empid=$conn->real_escape_string($_POST['empid']);
    $empname=$conn->real_escape_string($_POST['empname']);
    $email=$conn->real_escape_string($_POST['email']);
    $mob=$conn->real_escape_string($_POST['mob']);
    $addres=$conn->real_escape_string($_POST['address']);
    $designation=$conn->real_escape_string($_POST['designation']);
    $salary=$conn->real_escape_string($_POST['salary']);
    $empimg=$conn->real_escape_string(uploadSingleImage('img'));
    $chk=$conn->query("SELECT empid FROM empreg WHERE empid='$empid' LIMIT 1");
    if($chk&&$chk->num_rows>0){echo "<script>alert('Employee ID already exists!');window.history.back();</script>";exit;}
    $chkE=$conn->query("SELECT empid FROM empreg WHERE email='$email' LIMIT 1");
    if($chkE&&$chkE->num_rows>0){echo "<script>alert('Email already registered!');window.history.back();</script>";exit;}
    $sql="INSERT INTO empreg(empid,empname,email,mob,addres,designation,salary,empimg) VALUES('$empid','$empname','$email','$mob','$addres','$designation','$salary','$empimg')";
    if($conn->query($sql)===TRUE){echo "<script>alert('Employee Added Successfully!');</script>";}
    else{echo "Error: ".$conn->error;}
}

/* ── Fetch table ── */
$search = isset($_GET['search']) ? $conn->real_escape_string(trim($_GET['search'])) : '';
$sql    = $search !== ''
    ? "SELECT * FROM empreg WHERE empname LIKE '%$search%' OR email LIKE '%$search%'
       OR empid LIKE '%$search%' OR designation LIKE '%$search%'
       OR mob LIKE '%$search%' OR addres LIKE '%$search%' OR salary LIKE '%$search%'"
    : "SELECT * FROM empreg ORDER BY empname";
$tableResult = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Employee Manager</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display&family=DM+Sans:ital,wght@0,300;0,400;0,500;0,600;1,400&display=swap" rel="stylesheet">
<style>
:root{
    --navy:#0d1b2a;--navy-mid:#1b2e45;--navy-light:#243b55;
    --gold:#f0a500;--gold-dark:#c98a00;--cream:#fdf6e3;
    --text-main:#d8eaf8;--text-soft:#8aafc8;
    --danger:#e05c5c;--success:#3ec98a;
    --radius:12px;--border:rgba(240,165,0,.2);
}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
body{font-family:'DM Sans',sans-serif;background:var(--navy);
    background-image:radial-gradient(ellipse at 10% 15%,rgba(240,165,0,.13) 0%,transparent 50%),
    radial-gradient(ellipse at 88% 78%,rgba(62,201,138,.09) 0%,transparent 50%);
    min-height:100vh;color:var(--text-main);padding-bottom:70px}
.topbar{
    position:sticky;top:0;z-index:200;
    display:flex;align-items:center;justify-content:space-between;
    padding:0 28px;height:58px;
    background:rgba(17,34,58,.95);backdrop-filter:blur(12px);
    border-bottom:1px solid rgba(240,165,0,.18);
    box-shadow:0 4px 22px rgba(0,0,0,.4);
}
.topbar-brand{display:flex;align-items:center;gap:12px;}
.topbar-icon{width:36px;height:36px;border-radius:9px;
    background:linear-gradient(135deg,var(--gold-dark),var(--gold));
    display:flex;align-items:center;justify-content:center;font-size:17px;
    box-shadow:0 3px 12px rgba(240,165,0,.35);flex-shrink:0;}
.topbar-name{font-family:'DM Serif Display',serif;color:var(--gold);font-size:1.1rem;letter-spacing:-.2px;}
.topbar-right{display:flex;align-items:center;gap:14px;}
.topbar-user{display:flex;align-items:center;gap:8px;font-size:.83rem;color:var(--text-soft);}
.topbar-user .avatar{width:30px;height:30px;border-radius:50%;
    background:linear-gradient(135deg,rgba(240,165,0,.25),rgba(62,201,138,.2));
    border:1px solid rgba(240,165,0,.3);display:flex;align-items:center;justify-content:center;font-size:13px;}
.topbar-user strong{color:var(--text-main);font-size:.88rem;}
.role-pill{display:inline-flex;align-items:center;font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.8px;padding:3px 10px;border-radius:20px;}
.role-admin  {background:rgba(240,165,0,.15);color:var(--gold);border:1px solid rgba(240,165,0,.3);}
.role-hr     {background:rgba(79,124,255,.15);color:#7ca4ff;border:1px solid rgba(79,124,255,.3);}
.role-manager{background:rgba(62,201,138,.15);color:var(--success);border:1px solid rgba(62,201,138,.3);}
.btn-signout{display:inline-flex;align-items:center;gap:7px;padding:7px 16px;
    background:rgba(224,92,92,.12);color:#f87171;border:1px solid rgba(224,92,92,.3);border-radius:8px;
    font-family:'DM Sans',sans-serif;font-size:.82rem;font-weight:600;cursor:pointer;
    transition:background .2s,transform .15s;white-space:nowrap;}
.btn-signout:hover{background:rgba(224,92,92,.22);transform:translateY(-1px);color:#fca5a5;}
.form-wrap{width:520px;margin:24px auto;padding:34px 30px;background:var(--navy-mid);
    border:1px solid var(--border);border-radius:18px;box-shadow:0 24px 70px rgba(0,0,0,.5)}
.form-wrap h2{font-family:'DM Serif Display',serif;color:var(--gold);font-size:1.45rem;margin-bottom:22px;text-align:center}
.form-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px}
.form-grid .full{grid-column:1/-1}
.field-label{display:block;font-size:.73rem;font-weight:600;color:var(--text-soft);
    text-transform:uppercase;letter-spacing:.9px;margin-bottom:5px}
input[type=text],input[type=email],input[type=number],select{
    width:100%;padding:11px 14px;background:var(--navy-light);
    border:1px solid rgba(240,165,0,.22);border-radius:9px;color:var(--cream);
    font-family:'DM Sans',sans-serif;font-size:.92rem;outline:none;
    transition:border-color .22s,box-shadow .22s}
input::placeholder{color:var(--text-soft)}
input:focus,select:focus{border-color:var(--gold);box-shadow:0 0 0 3px rgba(240,165,0,.14)}
select{appearance:none;cursor:pointer}
select option{background:var(--navy-mid)}
.passport-upload-wrap{display:flex;gap:16px;align-items:flex-start}
.pp-frame{position:relative;flex-shrink:0;width:88px;height:112px}
.pp-frame .pp-img{width:88px;height:112px;border-radius:8px;object-fit:cover;object-position:top center;
    border:2px solid var(--gold);cursor:pointer;display:block;transition:transform .18s,box-shadow .18s}
.pp-frame .pp-img:hover{transform:scale(1.05);box-shadow:0 6px 20px rgba(0,0,0,.5)}
.pp-frame .pp-empty{width:88px;height:112px;border-radius:8px;background:var(--navy-light);
    border:2px dashed rgba(240,165,0,.4);display:flex;flex-direction:column;align-items:center;
    justify-content:center;color:var(--text-soft);gap:4px}
.pp-frame .pp-empty span{font-size:2rem}
.pp-frame .pp-empty small{font-size:.65rem;text-align:center}
.pp-del-btn{position:absolute;top:-8px;right:-8px;background:var(--danger);color:#fff;border:none;
    border-radius:50%;width:22px;height:22px;font-size:12px;cursor:pointer;
    display:flex;align-items:center;justify-content:center;
    box-shadow:0 2px 8px rgba(0,0,0,.45);transition:transform .15s;z-index:5}
.pp-del-btn:hover{transform:scale(1.2);background:#c0392b}
.dz-box{flex:1;border:2px dashed rgba(240,165,0,.35);border-radius:10px;padding:20px 12px;
    text-align:center;cursor:pointer;background:var(--navy-light);transition:border-color .2s,background .2s}
.dz-box:hover,.dz-box.drag-over{border-color:var(--gold);background:rgba(240,165,0,.06)}
.dz-box input[type=file]{display:none}
.dz-box .dz-ico{font-size:1.8rem;margin-bottom:6px}
.dz-box p{color:var(--text-soft);font-size:.83rem}
.dz-box p strong{color:var(--gold)}
.dz-box .dz-hint{font-size:.7rem;margin-top:4px;color:rgba(138,175,200,.55)}
.btn-submit{width:100%;margin-top:18px;padding:13px;
    background:linear-gradient(135deg,var(--gold),var(--gold-dark));
    color:var(--navy);font-family:'DM Sans',sans-serif;font-weight:700;font-size:1rem;
    border:none;border-radius:10px;cursor:pointer;letter-spacing:.4px;
    box-shadow:0 5px 18px rgba(240,165,0,.35);transition:transform .15s,box-shadow .2s}
.btn-submit:hover{transform:translateY(-2px);box-shadow:0 9px 26px rgba(240,165,0,.48)}
.section-title{font-family:'DM Serif Display',serif;font-size:1.7rem;color:var(--gold);text-align:center;margin:38px 0 18px}
.search-wrap{display:flex;flex-direction:column;align-items:center;gap:10px;margin-bottom:20px}
.search-box{position:relative;width:360px}
.search-box input{width:100%;padding:12px 46px 12px 20px;border-radius:24px;background:var(--navy-mid);font-size:.94rem;margin:0}
.search-icon{position:absolute;right:14px;top:50%;transform:translateY(-50%);
    background:none;border:none;color:var(--text-soft);font-size:16px;cursor:pointer;padding:4px;width:auto;transition:color .2s}
.search-icon:hover{color:var(--gold)}
#suggestions{position:absolute;top:calc(100% + 8px);left:50%;transform:translateX(-50%);
    width:900px;background:var(--navy-mid);border:1px solid rgba(240,165,0,.35);
    border-radius:14px;overflow:hidden;z-index:999;box-shadow:0 18px 54px rgba(0,0,0,.65);display:none}
.suggest-hdr,.suggest-row{display:grid;grid-template-columns:80px 130px 180px 110px 140px 130px 80px;gap:6px;padding:9px 14px}
.suggest-hdr{background:linear-gradient(90deg,var(--gold-dark),var(--gold))}
.suggest-hdr span{font-size:.71rem;font-weight:700;color:var(--navy);text-transform:uppercase;letter-spacing:.6px}
.suggest-row{align-items:center;cursor:pointer;border-bottom:1px solid rgba(255,255,255,.05);transition:background .15s}
.suggest-row:hover,.suggest-row.active{background:rgba(240,165,0,.1)}
.suggest-row span{font-size:.81rem;color:var(--text-main);overflow:hidden;white-space:nowrap;text-overflow:ellipsis}
.suggest-row .s-id{color:var(--success);font-weight:600}
.suggest-row .s-name{font-weight:600}
.suggest-row .s-email{color:var(--text-soft);font-size:.76rem}
.suggest-row .s-desg{background:rgba(240,165,0,.12);color:var(--gold);border:1px solid rgba(240,165,0,.25);border-radius:20px;padding:2px 8px;font-size:.7rem;font-weight:600;text-align:center}
.suggest-row .s-sal{color:var(--success);font-weight:600}
#suggestions mark{background:transparent;color:var(--gold);font-weight:700}
.btn-search{width:360px;padding:11px;background:linear-gradient(135deg,var(--gold),var(--gold-dark));
    color:var(--navy);font-family:'DM Sans',sans-serif;font-weight:700;font-size:.9rem;
    border:none;border-radius:24px;cursor:pointer;box-shadow:0 4px 14px rgba(240,165,0,.3);
    transition:transform .15s,box-shadow .2s;margin:0}
.btn-search:hover{transform:translateY(-2px);box-shadow:0 8px 22px rgba(240,165,0,.42)}
.btn-export{display:inline-flex;align-items:center;gap:8px;margin:0 auto 16px;padding:10px 26px;
    background:linear-gradient(135deg,#3ec98a,#27a86e);color:#0d1b2a;font-family:'DM Sans',sans-serif;
    font-weight:700;font-size:.88rem;border:none;border-radius:24px;cursor:pointer;text-decoration:none;
    letter-spacing:.3px;box-shadow:0 4px 16px rgba(62,201,138,.35);transition:transform .15s,box-shadow .2s}
.btn-export:hover{transform:translateY(-2px);box-shadow:0 8px 24px rgba(62,201,138,.5)}
.table-container{width:92%;margin:0 auto 30px;overflow-x:auto;border-radius:var(--radius);
    border:1px solid rgba(240,165,0,.15);box-shadow:0 10px 40px rgba(0,0,0,.4)}
table{width:100%;border-collapse:collapse;min-width:900px}
thead tr{background:linear-gradient(90deg,var(--gold-dark),var(--gold))}
th{padding:13px 12px;text-align:left;font-size:.8rem;font-weight:700;color:var(--navy);text-transform:uppercase;letter-spacing:.4px;white-space:nowrap}
tbody tr{border-bottom:1px solid rgba(255,255,255,.05);transition:background .15s}
tbody tr:last-child{border-bottom:none}
tbody tr:hover td{background:rgba(240,165,0,.06)}
td{padding:11px 12px;font-size:.87rem;color:var(--text-main);vertical-align:middle;max-width:160px;overflow:hidden;white-space:nowrap;text-overflow:ellipsis}
tbody{background:var(--navy-mid)}
.photo-cell{display:flex;justify-content:center;align-items:center}
.photo-cell .tbl-pp{width:45px;height:58px;border-radius:5px;object-fit:cover;object-position:top center;
    border:2px solid var(--gold);cursor:pointer;box-shadow:0 3px 10px rgba(0,0,0,.4);transition:transform .15s,box-shadow .15s}
.photo-cell .tbl-pp:hover{transform:scale(1.14);box-shadow:0 6px 18px rgba(0,0,0,.55)}
.no-photo{color:var(--text-soft);font-size:.78rem;font-style:italic}
.desg-badge{background:rgba(240,165,0,.12);color:var(--gold);border:1px solid rgba(240,165,0,.25);
    border-radius:20px;padding:3px 9px;font-size:.74rem;font-weight:600;white-space:nowrap}
.btn-view,.btn-edit,.btn-delete{display:inline-block;padding:5px 12px;border-radius:6px;
    text-decoration:none;font-size:.78rem;font-weight:600;margin:2px;
    cursor:pointer;border:none;font-family:'DM Sans',sans-serif;transition:transform .15s,opacity .2s}
.btn-view  {background:rgba(240,165,0,.14);color:var(--gold);border:1px solid rgba(240,165,0,.4)}
.btn-edit  {background:rgba(62,201,138,.14);color:var(--success);border:1px solid rgba(62,201,138,.4)}
.btn-delete{background:rgba(224,92,92,.14);color:var(--danger);border:1px solid rgba(224,92,92,.4)}
.btn-view:hover,.btn-edit:hover,.btn-delete:hover{transform:translateY(-1px);opacity:.85}
.action-cell{display:flex;flex-wrap:wrap;gap:4px;align-items:center;max-width:none;white-space:normal}
.no-records{text-align:center;color:var(--text-soft);padding:30px;font-style:italic}
.lightbox{display:none;position:fixed;inset:0;background:rgba(0,0,0,.9);z-index:2000;align-items:center;justify-content:center}
.lightbox.open{display:flex}
.lightbox img{max-height:80vh;max-width:40vw;border-radius:10px;box-shadow:0 24px 80px rgba(0,0,0,.8)}
.lb-close{position:absolute;top:18px;right:24px;color:#fff;font-size:2rem;cursor:pointer;background:none;border:none;line-height:1;transition:color .2s}
.lb-close:hover{color:var(--gold)}
.toast{position:fixed;top:72px;right:22px;background:var(--success);color:#fff;
    padding:12px 22px;border-radius:8px;font-weight:600;font-size:.9rem;
    opacity:0;transform:translateY(-10px);transition:all .3s;pointer-events:none;z-index:9999}
.toast.show{opacity:1;transform:translateY(0)}
.confirm-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.75);z-index:3000;align-items:center;justify-content:center;}
.confirm-overlay.open{display:flex;}
.confirm-box{background:var(--navy-mid);border:1px solid var(--border);border-radius:18px;
    padding:38px 40px;max-width:380px;width:90%;text-align:center;
    box-shadow:0 30px 80px rgba(0,0,0,.6);animation:fadeUp .3s ease;}
@keyframes fadeUp{from{opacity:0;transform:translateY(18px)}to{opacity:1;transform:translateY(0)}}
.confirm-box .ico{font-size:2.6rem;margin-bottom:14px;}
.confirm-box h3{font-family:'DM Serif Display',serif;color:var(--cream);font-size:1.3rem;margin-bottom:8px;}
.confirm-box p{color:var(--text-soft);font-size:.88rem;margin-bottom:26px;line-height:1.6;}
.confirm-actions{display:flex;gap:12px;justify-content:center;}
.cbtn{padding:11px 28px;border-radius:9px;font-family:'DM Sans',sans-serif;font-size:.88rem;font-weight:600;cursor:pointer;transition:all .18s;border:none;}
.cbtn-yes{background:linear-gradient(135deg,#e05c5c,#c0392b);color:#fff;box-shadow:0 4px 14px rgba(224,92,92,.35);}
.cbtn-yes:hover{transform:translateY(-1px);box-shadow:0 7px 20px rgba(224,92,92,.5);}
.cbtn-no{background:var(--navy-light);color:var(--text-soft);border:1px solid rgba(255,255,255,.1);}
.cbtn-no:hover{color:var(--text-main);border-color:rgba(255,255,255,.2);}
</style>
</head>
<body>

<!-- TOPBAR -->
<nav class="topbar">
    <div class="topbar-brand">
        <div class="topbar-icon">👥</div>
        <span class="topbar-name">StaffPortal</span>
    </div>
    <div class="topbar-right">
        <div class="topbar-user">
            <div class="avatar">👤</div>
            <strong><?= htmlspecialchars($fullName) ?></strong>
        </div>
        <span class="role-pill role-<?= $role ?>"><?= htmlspecialchars(strtoupper($role)) ?></span>
        <button class="btn-signout" onclick="openConfirm()">🚪 Sign Out</button>
    </div>
</nav>

<!-- Sign-Out Confirm -->
<div class="confirm-overlay" id="confirmOverlay">
    <div class="confirm-box">
        <div class="ico">🚪</div>
        <h3>Sign Out?</h3>
        <p>Are you sure you want to sign out of <strong>StaffPortal</strong>?<br/>Your session will be ended.</p>
        <div class="confirm-actions">
            <button class="cbtn cbtn-no" onclick="closeConfirm()">Cancel</button>
            <a href="logout.php" class="cbtn cbtn-yes">Yes, Sign Out</a>
        </div>
    </div>
</div>

<!-- FORM — visible to ALL roles -->
<div class="form-wrap">
<?php if ($edit_data): ?>
    <h2>✏️ Edit Employee</h2>
    <form method="post" enctype="multipart/form-data">
        <input type="hidden" name="empid" value="<?= htmlspecialchars($edit_data['empid']) ?>">
        <div class="form-grid">
            <div><label class="field-label">Full Name</label>
                <input type="text" name="empname" value="<?= htmlspecialchars($edit_data['empname']) ?>" placeholder="Full Name" required></div>
            <div><label class="field-label">Email</label>
                <input type="email" name="email" value="<?= htmlspecialchars($edit_data['email']) ?>" placeholder="Email" required></div>
            <div><label class="field-label">Mobile</label>
                <input type="text" name="mob" value="<?= htmlspecialchars($edit_data['mob']) ?>" placeholder="Mobile" required></div>
            <div><label class="field-label">Designation</label>
                <select name="designation" required>
                    <option value="">Select Position</option>
                    <?php foreach(['Software Developer','Web Designer','Data Analyst','HR Manager','Sales Executive','Support Engineer'] as $pos):
                        $sel=($edit_data['designation']===$pos)?'selected':''; ?>
                        <option <?=$sel?>><?=$pos?></option>
                    <?php endforeach; ?>
                </select></div>
            <div class="full"><label class="field-label">Address</label>
                <input type="text" name="address" value="<?= htmlspecialchars($edit_data['addres']) ?>" placeholder="Address" required></div>
            <div><label class="field-label">Salary (₹)</label>
                <input type="number" name="salary" value="<?= htmlspecialchars((int)$edit_data['salary']) ?>" placeholder="Salary" required></div>
            <div class="full"><label class="field-label">Passport Photo</label>
                <div class="passport-upload-wrap">
                    <div class="pp-frame" id="editFrame">
                        <?php if($edit_data['empimg']&&file_exists($edit_data['empimg'])): ?>
                            <img class="pp-img" id="editPreviewImg" src="<?= htmlspecialchars($edit_data['empimg']) ?>" alt="photo">
                            <button type="button" class="pp-del-btn" id="editDelBtn"
                                onclick="deleteFormPhoto('<?= htmlspecialchars($edit_data['empid']) ?>')">✕</button>
                        <?php else: ?>
                            <div class="pp-empty" id="editEmpty"><span>👤</span><small>No Photo</small></div>
                        <?php endif; ?>
                    </div>
                    <div class="dz-box" id="editDz"
                         onclick="document.getElementById('editFileInput').click()"
                         ondragover="dzOver(event,'editDz')" ondragleave="dzLeave('editDz')"
                         ondrop="dzDrop(event,'editFileInput','editFrame','editEmpty','editPreviewImg','editDelBtn')">
                        <div class="dz-ico">🖼️</div><p>Drop photo or <strong>browse</strong></p>
                        <p class="dz-hint">JPG / PNG / WEBP</p>
                        <input type="file" id="editFileInput" name="img" accept="image/*">
                    </div>
                </div>
            </div>
        </div>
        <button type="submit" name="update" class="btn-submit">💾 Update Employee</button>
    </form>
<?php else: ?>
    <h2>📋 Employee Registration Form</h2>
    <form method="post" enctype="multipart/form-data">
        <div class="form-grid">
            <div><label class="field-label">Employee ID</label>
                <input type="text" name="empid" placeholder="EMP-001" required></div>
            <div><label class="field-label">Full Name</label>
                <input type="text" name="empname" placeholder="Full Name" required></div>
            <div><label class="field-label">Email</label>
                <input type="email" name="email" placeholder="email@company.com" required></div>
            <div><label class="field-label">Mobile</label>
                <input type="text" name="mob" placeholder="+91 XXXXX XXXXX" required></div>
            <div class="full"><label class="field-label">Address</label>
                <input type="text" name="address" placeholder="Address" required></div>
            <div><label class="field-label">Designation</label>
                <select name="designation" required>
                    <option value="">Select Position</option>
                    <option>Software Developer</option><option>Web Designer</option>
                    <option>Data Analyst</option><option>HR Manager</option>
                    <option>Sales Executive</option><option>Support Engineer</option>
                </select></div>
            <div><label class="field-label">Salary (₹)</label>
                <input type="number" name="salary" placeholder="0" required></div>
            <div class="full"><label class="field-label">Passport Photo</label>
                <div class="passport-upload-wrap">
                    <div class="pp-frame" id="addFrame">
                        <div class="pp-empty" id="addEmpty"><span>👤</span><small>No Photo</small></div>
                    </div>
                    <div class="dz-box" id="addDz"
                         onclick="document.getElementById('addFileInput').click()"
                         ondragover="dzOver(event,'addDz')" ondragleave="dzLeave('addDz')"
                         ondrop="dzDrop(event,'addFileInput','addFrame','addEmpty','addPreviewImg',null)">
                        <div class="dz-ico">🖼️</div><p>Drop photo or <strong>browse</strong></p>
                        <p class="dz-hint">JPG / PNG / WEBP</p>
                        <input type="file" id="addFileInput" name="img" accept="image/*">
                    </div>
                </div>
            </div>
        </div>
        <button type="submit" name="submit" class="btn-submit">✚ Add Employee</button>
    </form>
<?php endif; ?>
</div>

<!-- TABLE -->
<h2 class="section-title">Employee Details</h2>

<form method="GET" id="searchForm" autocomplete="off" style="text-align:center;">
    <div class="search-wrap">
        <div class="search-box">
            <input type="text" id="searchInput" name="search"
                   placeholder="Search by Name, Email, ID..."
                   value="<?= htmlspecialchars($_GET['search'] ?? '') ?>" autocomplete="off">
            <button type="submit" class="search-icon" title="Search">🔍</button>
            <div id="suggestions"></div>
        </div>
        <button type="submit" class="btn-search">Search</button>
    </div>
</form>

<?php if ($tableResult && $tableResult->num_rows > 0): ?>
    <?php if ($canExport): ?>
    <div style="text-align:center;margin-bottom:12px;">
        <a href="employee.php?export=excel&search=<?= urlencode($search) ?>" class="btn-export">📊 Export to Excel</a>
    </div>
    <?php endif; ?>
    <div class="table-container" id="results">
    <table>
        <thead><tr>
            <th>ID</th><th>Name</th><th>Email</th><th>Mobile</th>
            <th>Address</th><th>Designation</th><th>Salary</th>
            <th>Photo</th><th>Action</th>
        </tr></thead>
        <tbody>
        <?php $tableResult->data_seek(0); while ($row = $tableResult->fetch_assoc()):
            $photo = $row['empimg'] ?? ''; ?>
        <tr>
            <td><?= htmlspecialchars($row['empid']) ?></td>
            <td><?= htmlspecialchars($row['empname']) ?></td>
            <td><?= htmlspecialchars($row['email']) ?></td>
            <td><?= htmlspecialchars($row['mob']) ?></td>
            <td title="<?= htmlspecialchars($row['addres']) ?>"><?= htmlspecialchars($row['addres']) ?></td>
            <td><span class="desg-badge"><?= htmlspecialchars($row['designation']) ?></span></td>
            <td>₹<?= number_format((int)$row['salary']) ?></td>
            <td>
                <div class="photo-cell" id="pcell_<?= htmlspecialchars($row['empid']) ?>">
                <?php if ($photo && file_exists($photo)): ?>
                    <img class="tbl-pp" src="<?= htmlspecialchars($photo) ?>"
                         onclick="openLb('<?= htmlspecialchars($photo) ?>')"
                         title="Click to enlarge" alt="photo">
                <?php else: ?>
                    <span class="no-photo">No photo</span>
                <?php endif; ?>
                </div>
            </td>
            <td>
               <div class="action-cell"> <a href="employee.php?view=<?= urlencode($row['empid']) ?>" target="_blank" class="btn-view">👁 View</a> <?php if ($canEdit): ?> <a href="employee.php?edit=<?= urlencode($row['empid']) ?>" class="btn-edit">✏️ Edit</a> <?php endif; ?> <?php if ($canDelete): ?> <a href="employee.php?delete=<?= urlencode($row['empid']) ?>" onclick="return confirm('Delete this employee?')" class="btn-delete">🗑 Delete</a> <?php endif; ?> </div>
            </td>
        </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
    </div>
<?php else: ?>
    <p class="no-records">No records found.</p>
<?php endif; ?>

<div class="lightbox" id="lightbox">
    <button class="lb-close" onclick="closeLb()">✕</button>
    <img id="lbImg" src="" alt="Employee Photo">
</div>
<div class="toast" id="toast"></div>

<script>
function openConfirm(){document.getElementById('confirmOverlay').classList.add('open');}
function closeConfirm(){document.getElementById('confirmOverlay').classList.remove('open');}
document.getElementById('confirmOverlay').addEventListener('click',e=>{if(e.target===document.getElementById('confirmOverlay'))closeConfirm();});
function openLb(src){document.getElementById('lbImg').src=src;document.getElementById('lightbox').classList.add('open')}
function closeLb(){document.getElementById('lightbox').classList.remove('open')}
document.getElementById('lightbox').addEventListener('click',e=>{if(e.target===document.getElementById('lightbox'))closeLb()})
document.addEventListener('keydown',e=>{if(e.key==='Escape'){closeLb();closeConfirm();}})
function deleteFormPhoto(empid){
    if(!confirm('Remove this photo?'))return;
    fetch('employee.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:`delete_photo=1&empid=${encodeURIComponent(empid)}`})
    .then(r=>r.json()).then(data=>{
        if(data.success){
            document.getElementById('editFrame').innerHTML='<div class="pp-empty" id="editEmpty"><span>👤</span><small>No Photo</small></div>';
            showToast('🗑 Photo removed!');
        }
    });
}
function dzOver(e,id){e.preventDefault();document.getElementById(id).classList.add('drag-over')}
function dzLeave(id){document.getElementById(id).classList.remove('drag-over')}
function dzDrop(e,inputId,frameId,emptyId,previewImgId,delBtnId){
    e.preventDefault();dzLeave(e.currentTarget.id);
    const file=e.dataTransfer.files[0];
    if(file)previewPhoto(file,frameId,emptyId,previewImgId,delBtnId);
}
['addFileInput','editFileInput'].forEach(id=>{
    const el=document.getElementById(id);if(!el)return;
    el.addEventListener('change',function(){
        if(!this.files[0])return;
        if(id==='addFileInput')previewPhoto(this.files[0],'addFrame','addEmpty','addPreviewImg',null);
        else previewPhoto(this.files[0],'editFrame','editEmpty','editPreviewImg','editDelBtn');
    });
});
function previewPhoto(file,frameId,emptyId,previewImgId,delBtnId){
    if(!file.type.startsWith('image/'))return;
    const url=URL.createObjectURL(file);
    const frame=document.getElementById(frameId);
    const ph=document.getElementById(emptyId);if(ph)ph.remove();
    let img=document.getElementById(previewImgId);
    if(!img){img=document.createElement('img');img.className='pp-img';img.id=previewImgId;frame.appendChild(img);}
    img.src=url;
    if(delBtnId&&!document.getElementById(delBtnId)){
        const btn=document.createElement('button');btn.type='button';btn.className='pp-del-btn';
        btn.id=delBtnId;btn.textContent='✕';
        btn.onclick=()=>{img.remove();btn.remove();frame.innerHTML='<div class="pp-empty" id="'+emptyId+'"><span>👤</span><small>No Photo</small></div>';};
        frame.appendChild(btn);
    }
}
const searchInput=document.getElementById('searchInput');
const suggestBox=document.getElementById('suggestions');
let debTimer=null,activeIdx=-1,currentData=[];
function hl(text,q){if(!q)return text;return String(text).replace(new RegExp('('+q.replace(/[.*+?^${}()|[\]\\]/g,'\\$&')+')','gi'),'<mark>$1</mark>')}
function renderSuggestions(items,q){
    suggestBox.innerHTML='';activeIdx=-1;currentData=items;
    if(!items.length){suggestBox.style.display='none';return}
    const hdr=document.createElement('div');hdr.className='suggest-hdr';
    hdr.innerHTML='<span>ID</span><span>Name</span><span>Email</span><span>Mobile</span><span>Address</span><span>Designation</span><span>Salary</span>';
    suggestBox.appendChild(hdr);
    items.forEach(item=>{
        const d=document.createElement('div');d.className='suggest-row';
        d.innerHTML=`<span class="s-id">${hl(item.id,q)}</span><span class="s-name">${hl(item.name,q)}</span><span class="s-email">${hl(item.email,q)}</span><span>${hl(item.mob,q)}</span><span>${hl(item.address,q)}</span><span class="s-desg">${hl(item.designation,q)}</span><span class="s-sal">₹${Number(item.salary).toLocaleString('en-IN')}</span>`;
        d.addEventListener('mousedown',e=>{e.preventDefault();searchInput.value=item.name;suggestBox.style.display='none';document.getElementById('searchForm').submit();});
        suggestBox.appendChild(d);
    });
    suggestBox.style.display='block';
}
if(searchInput){
    searchInput.addEventListener('input',()=>{
        clearTimeout(debTimer);const q=searchInput.value.trim();
        if(!q){suggestBox.style.display='none';return;}
        debTimer=setTimeout(()=>{fetch(`employee.php?q=${encodeURIComponent(q)}`).then(r=>r.json()).then(data=>renderSuggestions(data,q)).catch(()=>{suggestBox.style.display='none';});},200);
    });
    searchInput.addEventListener('keydown',e=>{
        const rows=suggestBox.querySelectorAll('.suggest-row');if(!rows.length)return;
        if(e.key==='ArrowDown'){e.preventDefault();activeIdx=(activeIdx+1)%rows.length;setActive(rows);}
        else if(e.key==='ArrowUp'){e.preventDefault();activeIdx=(activeIdx-1+rows.length)%rows.length;setActive(rows);}
        else if(e.key==='Enter'&&activeIdx>=0){e.preventDefault();searchInput.value=currentData[activeIdx].name;suggestBox.style.display='none';document.getElementById('searchForm').submit();}
        else if(e.key==='Escape'){suggestBox.style.display='none';}
    });
}
function setActive(rows){rows.forEach((r,i)=>r.classList.toggle('active',i===activeIdx));if(activeIdx>=0)searchInput.value=currentData[activeIdx].name;}
document.addEventListener('click',e=>{if(!e.target.closest('.search-box'))suggestBox.style.display='none';});
function showToast(msg='✔ Done!'){const t=document.getElementById('toast');t.textContent=msg;t.classList.add('show');setTimeout(()=>t.classList.remove('show'),2400);}
window.onload=()=>{const p=new URLSearchParams(window.location.search);if(p.has('search')&&document.getElementById('results'))document.getElementById('results').scrollIntoView({behavior:'smooth'});};
</script>
</body>
</html>