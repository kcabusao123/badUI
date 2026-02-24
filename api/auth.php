<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';

$action = $_POST['action'] ?? '';

/* ── Login ── */
if ($action === 'login') {
    $u = trim($_POST['username'] ?? '');
    $p = trim($_POST['password'] ?? '');

    if (!$u || !$p) {
        echo json_encode(['success' => false, 'message' => 'wrong']);
        exit;
    }

    $db   = getDB();
    $stmt = $db->prepare('SELECT id, username, password, name, role FROM users WHERE username = ?');
    $stmt->bind_param('s', $u);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($user && password_verify($p, $user['password'])) {
        /* Update last_login timestamp */
        $upd = $db->prepare('UPDATE users SET last_login = NOW() WHERE id = ?');
        $upd->bind_param('i', $user['id']);
        $upd->execute();
        $upd->close();

        $_SESSION['user'] = [
            'id'       => $user['id'],
            'username' => $user['username'],
            'name'     => $user['name'],
            'role'     => $user['role'],
        ];
        echo json_encode(['success' => true, 'name' => $user['name']]);
    } else {
        echo json_encode(['success' => false, 'message' => 'wrong']);
    }
    exit;
}

/* ── Logout ── */
if ($action === 'logout') {
    session_destroy();
    echo json_encode(['success' => true]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Unknown action']);

