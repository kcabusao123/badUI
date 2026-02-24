<?php
/**
 * setup.php — One-time database initializer
 * Run via browser : http://localhost/BadUI/setup.php
 * Run via CLI     : php setup.php
 * DELETE after use.
 */

error_reporting(E_ALL);
$isCLI = PHP_SAPI === 'cli';

function logLine(string $msg, string $type = 'ok'): void {
    global $isCLI;
    $isCLI
        ? print(($type==='ok' ? '✅' : ($type==='info' ? 'ℹ️ ' : '⚠️ ')) . " $msg\n")
        : print('<li class="' . $type . '">' . htmlspecialchars($msg) . '</li>');
}

if (!$isCLI) {
    echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>Grand Inn Setup</title><style>
    body{font-family:monospace;background:#0a0a0f;color:#ccc;padding:40px}
    h1{color:#7b2fff}ul{list-style:none;padding:0;line-height:2}
    .ok{color:#44dd77}.info{color:#4a90d9}.warn{color:#ffaa00}
    .done{margin-top:32px;padding:16px 24px;background:#12121a;border:1px solid #44dd77;border-radius:8px;color:#44dd77}
    a{color:#7b2fff}</style></head><body><h1>🏨 Grand Inn — Database Setup</h1><ul>';
}

/* ── 1. Connect (no DB selected yet) ── */
try {
    $db = new PDO('mysql:host=localhost;charset=utf8mb4', 'root', '', [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
} catch (PDOException $e) {
    $msg = '❌ Cannot connect to MySQL: ' . $e->getMessage();
    $isCLI ? die($msg . "\n") : die('<pre>' . htmlspecialchars($msg) . '</pre>');
}

/* ── 2. Create database ── */
$db->exec('CREATE DATABASE IF NOT EXISTS `grand_inn` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
$db->exec('USE `grand_inn`');
logLine('Database `grand_inn` ready.');

/* ── 3. Create tables ── */
$db->exec("CREATE TABLE IF NOT EXISTS users (
    id         INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    username   VARCHAR(60)   NOT NULL UNIQUE,
    password   VARCHAR(255)  NOT NULL,
    name       VARCHAR(120)  NOT NULL,
    role       ENUM('admin','staff') NOT NULL DEFAULT 'staff',
    created_at DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    last_login DATETIME               DEFAULT NULL,
    PRIMARY KEY (id),
    INDEX idx_username (username)
) ENGINE=InnoDB");
logLine("Table `users` ready.");

$db->exec("CREATE TABLE IF NOT EXISTS rooms (
    id              INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    room_number     TINYINT UNSIGNED NOT NULL UNIQUE,
    type            ENUM('Standard','Deluxe','Suite','Family') NOT NULL,
    price_per_night DECIMAL(10,2)    NOT NULL,
    description     VARCHAR(255)     DEFAULT NULL,
    is_active       TINYINT(1)       NOT NULL DEFAULT 1,
    PRIMARY KEY (id),
    INDEX idx_room_number (room_number)
) ENGINE=InnoDB");
logLine("Table `rooms` ready.");

$db->exec("CREATE TABLE IF NOT EXISTS reservations (
    id              INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    room_number     TINYINT UNSIGNED NOT NULL,
    room_type       ENUM('Standard','Deluxe','Suite','Family') NOT NULL,
    price_per_night DECIMAL(10,2)    NOT NULL,
    guest_name      VARCHAR(120)     NOT NULL,
    guest_phone     VARCHAR(30)      DEFAULT NULL,
    checkin_date    DATE             NOT NULL,
    checkout_date   DATE             NOT NULL,
    status          ENUM('reserved','checked_in','checked_out','cancelled') NOT NULL DEFAULT 'reserved',
    created_by      INT UNSIGNED     DEFAULT NULL,
    created_at      DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_room   (room_number),
    INDEX idx_dates  (checkin_date, checkout_date),
    INDEX idx_status (status)
) ENGINE=InnoDB");
logLine("Table `reservations` ready.");

/* ── 4. Seed users ── */
$users = [
    ['admin', 'admin123', 'Admin Manager', 'admin'],
    ['staff', 'staff123', 'Front Desk',    'staff'],
];

$checkU  = $db->prepare('SELECT COUNT(*) FROM users WHERE username = ?');
$insertU = $db->prepare('INSERT INTO users (username, password, name, role) VALUES (?, ?, ?, ?)');
$updateU = $db->prepare('UPDATE users SET password = ?, name = ?, role = ? WHERE username = ?');

foreach ($users as [$uname, $plain, $name, $role]) {
    $hash = password_hash($plain, PASSWORD_BCRYPT, ['cost' => 12]);
    $checkU->execute([$uname]);
    if ((int)$checkU->fetchColumn() === 0) {
        $insertU->execute([$uname, $hash, $name, $role]);
        logLine("User '$uname' created (password: $plain).");
    } else {
        $updateU->execute([$hash, $name, $role, $uname]);
        logLine("User '$uname' password rehashed.", 'info');
    }
}

/* ── 5. Seed rooms ── */
$roomCount = (int)$db->query('SELECT COUNT(*) FROM rooms')->fetchColumn();
if ($roomCount === 0) {
    $ins = $db->prepare('INSERT INTO rooms (room_number, type, price_per_night, description) VALUES (?,?,?,?)');
    $roomData = [
        [1,  'Standard', 800.00,  'Standard room, ground floor, garden view'],
        [2,  'Standard', 800.00,  'Standard room, ground floor, street view'],
        [3,  'Standard', 800.00,  'Standard room, ground floor, courtyard view'],
        [4,  'Deluxe',   1500.00, 'Deluxe room, upper floor, city view'],
        [5,  'Deluxe',   1500.00, 'Deluxe room, upper floor, pool view'],
        [6,  'Deluxe',   1500.00, 'Deluxe room, upper floor, mountain view'],
        [7,  'Suite',    3000.00, 'Suite, top floor, panoramic view, king bed'],
        [8,  'Suite',    3000.00, 'Suite, top floor, panoramic view, twin beds'],
        [9,  'Family',   2000.00, 'Family room, ground floor, 2 rooms connected'],
        [10, 'Family',   2000.00, 'Family room, upper floor, 2 rooms connected'],
    ];
    foreach ($roomData as $r) $ins->execute($r);
    logLine('10 rooms seeded.');
} else {
    logLine("Rooms already exist ($roomCount rows) — skipped.", 'info');
}

/* ── 6. Seed sample reservations ── */
$resCount = (int)$db->query('SELECT COUNT(*) FROM reservations')->fetchColumn();
if ($resCount === 0) {
    $ins = $db->prepare(
        'INSERT INTO reservations (room_number,room_type,price_per_night,guest_name,guest_phone,checkin_date,checkout_date,status,created_at)
         VALUES (?,?,?,?,?,?,?,?,?)'
    );
    $seeds = [
        [2,  'Standard', 800.00,  'Santos, Maria',     '09171234567', '2026-02-20', '2026-02-24', 'checked_in',  '2026-02-20 14:00:00'],
        [5,  'Deluxe',   1500.00, 'Reyes, Juan',        '09281234567', '2026-02-22', '2026-02-25', 'reserved',    '2026-02-21 10:00:00'],
        [7,  'Suite',    3000.00, 'Cruz, Ana',           '09351234567', '2026-02-18', '2026-02-22', 'checked_out', '2026-02-18 09:00:00'],
        [9,  'Family',   2000.00, 'Dela Cruz, Robert',  '09461234567', '2026-02-23', '2026-02-26', 'reserved',    '2026-02-22 08:00:00'],
    ];
    foreach ($seeds as $s) $ins->execute($s);
    logLine('4 sample reservations seeded.');
} else {
    logLine("Reservations already exist ($resCount rows) — skipped.", 'info');
}

/* ── Done ── */
if ($isCLI) {
    echo "\n✅ Setup complete. Delete setup.php when done.\n";
} else {
    echo '</ul><div class="done">✅ Setup complete. <a href="index.php">Go to login →</a><br><br>';
    echo '<strong>⚠ Delete this file:</strong> <code>setup.php</code></div></body></html>';
}
