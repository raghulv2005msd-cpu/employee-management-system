<?php

$servername = "localhost";
$username   = "root";
$password   = "";
$dbname     = "empdb";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) die("DB Error: " . $conn->connect_error);

/* ── Create users table ── */
$conn->query("
CREATE TABLE IF NOT EXISTS users (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    username   VARCHAR(60)  NOT NULL UNIQUE,
    password   VARCHAR(255) NOT NULL,
    full_name  VARCHAR(100) NOT NULL,
    email      VARCHAR(120) NOT NULL UNIQUE,
    role       ENUM('admin','hr','manager') NOT NULL DEFAULT 'manager',
    is_active  TINYINT(1)   NOT NULL DEFAULT 1,
    created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

/* ── Add created_at to empreg if missing ── */
$conn->query("ALTER TABLE empreg ADD COLUMN IF NOT EXISTS created_at DATETIME DEFAULT CURRENT_TIMESTAMP");

/* ── Users to seed ── */
$users = [
    ['admin',   'admin123',      'System Admin',       'admin@company.com',   'admin'],
    ['hr',      'hr@2025',       'HR Officer',         'hr@company.com',      'hr'],
    ['manager', 'manager@2025',  'Department Manager', 'manager@company.com', 'manager'],
];

$conn->query("DELETE FROM users"); // clear old
$stmt = $conn->prepare("INSERT INTO users (username,password,full_name,email,role) VALUES (?,?,?,?,?)");

$results = [];
foreach ($users as [$uname, $plain, $full, $email, $role]) {
    $hash = password_hash($plain, PASSWORD_BCRYPT);
    $stmt->bind_param('sssss', $uname, $hash, $full, $email, $role);
    $ok = $stmt->execute();
    $results[] = ['user' => $uname, 'pass' => $plain, 'role' => $role, 'ok' => $ok];
}
$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Setup Complete</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;600&family=DM+Serif+Display&display=swap" rel="stylesheet">
<style>
body{font-family:'DM Sans',sans-serif;background:#0d1b2a;color:#d8eaf8;
    display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0}
.box{background:#1b2e45;border:1px solid rgba(240,165,0,.25);border-radius:18px;
    padding:44px 48px;max-width:520px;width:90%;box-shadow:0 30px 70px rgba(0,0,0,.5)}
h2{font-family:'DM Serif Display',serif;color:#f0a500;margin-bottom:6px}
p{color:#8aafc8;font-size:.9rem;margin-bottom:24px}
table{width:100%;border-collapse:collapse;font-size:.88rem}
th{text-align:left;padding:8px 12px;background:rgba(240,165,0,.15);color:#f0a500;
    font-size:.72rem;text-transform:uppercase;letter-spacing:.8px}
td{padding:10px 12px;border-bottom:1px solid rgba(255,255,255,.06)}
.ok{color:#3ec98a;font-weight:600}
.warn{background:rgba(224,92,92,.12);border:1px solid rgba(224,92,92,.3);
    color:#f87171;border-radius:9px;padding:14px 16px;margin-top:22px;font-size:.85rem;line-height:1.6}
.warn strong{color:#f87171}
</style>
</head>
<body>
<div class="box">
    <h2>✅ Users Setup Complete</h2>
    <p>The following users were inserted into <strong>empdb.users</strong> with bcrypt-hashed passwords.</p>
    <table>
        <tr><th>Username</th><th>Password</th><th>Role</th><th>Status</th></tr>
        <?php foreach($results as $r): ?>
        <tr>
            <td><strong><?= $r['user'] ?></strong></td>
            <td><code><?= $r['pass'] ?></code></td>
            <td><?= strtoupper($r['role']) ?></td>
            <td class="ok"><?= $r['ok'] ? '✓ Inserted' : '✗ Failed' ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
    <div class="warn">
        ⚠️ <strong>Security:</strong> Delete <code>setup_users.php</code> from your server immediately after this step. This file should never be publicly accessible.
    </div>
</div>
</body>
</html>