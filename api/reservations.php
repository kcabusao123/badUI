<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$db     = getDB();
$action = $_POST['action'] ?? $_GET['action'] ?? '';
$userId = $_SESSION['user']['id'] ?? null;

/* ── Fetch rooms from DB keyed by room_number ── */
function getRooms(mysqli $db): array {
    $result = $db->query(
        'SELECT room_number, type, price_per_night FROM rooms WHERE is_active = 1 ORDER BY room_number'
    );
    $out = [];
    while ($r = $result->fetch_assoc()) {
        $out[(int)$r['room_number']] = [
            'type'  => $r['type'],
            'price' => (float)$r['price_per_night'],
        ];
    }
    return $out;
}

/* ── Map a DB reservations row to the shape the JS front-end expects ── */
function mapRow(array $r): array {
    return [
        'id'       => (int)$r['id'],
        'room'     => (int)$r['room_number'],
        'type'     => $r['room_type'],
        'price'    => (float)$r['price_per_night'],
        'guest'    => $r['guest_name'],
        'phone'    => $r['guest_phone'] ?? '',
        'checkin'  => $r['checkin_date'],
        'checkout' => $r['checkout_date'],
        'status'   => $r['status'],
        'created'  => $r['created_at'],
    ];
}

/* ── List ── */
if ($action === 'list') {
    $result = $db->query('SELECT * FROM reservations ORDER BY id ASC');
    $rows   = $result->fetch_all(MYSQLI_ASSOC);
    $rooms  = getRooms($db);
    echo json_encode([
        'success'      => true,
        'reservations' => array_map('mapRow', $rows),
        'rooms'        => $rooms,
    ]);
    exit;
}

/* ── Book ── */
if ($action === 'book') {
    $room     = (int)($_POST['room']    ?? 0);
    $guest    = trim($_POST['guest']    ?? '');
    $phone    = trim($_POST['phone']    ?? '');
    $checkin  = trim($_POST['checkin']  ?? '');
    $checkout = trim($_POST['checkout'] ?? '');

    if (!$room || !$guest || !$checkin || !$checkout) {
        echo json_encode(['success' => false, 'message' => 'All required fields must be filled.']);
        exit;
    }
    if ($checkin >= $checkout) {
        echo json_encode(['success' => false, 'message' => 'Check-out must be after check-in.']);
        exit;
    }

    $rooms = getRooms($db);
    if (!isset($rooms[$room])) {
        echo json_encode(['success' => false, 'message' => 'Invalid room.']);
        exit;
    }

    /* Overlap check */
    $overlap = $db->prepare(
        'SELECT COUNT(*) AS cnt FROM reservations
         WHERE room_number = ?
           AND status IN ("reserved","checked_in")
           AND ? < checkout_date
           AND ? > checkin_date'
    );
    $overlap->bind_param('iss', $room, $checkin, $checkout);
    $overlap->execute();
    $cnt = $overlap->get_result()->fetch_assoc()['cnt'];
    $overlap->close();
    if ((int)$cnt > 0) {
        echo json_encode(['success' => false, 'message' => 'Room ' . $room . ' is not available for those dates.']);
        exit;
    }

    $type  = $rooms[$room]['type'];
    $price = $rooms[$room]['price'];
    $stmt  = $db->prepare(
        'INSERT INTO reservations
            (room_number, room_type, price_per_night, guest_name, guest_phone,
             checkin_date, checkout_date, status, created_by)
         VALUES (?, ?, ?, ?, ?, ?, ?, "reserved", ?)'
    );
    $stmt->bind_param('isdssssi', $room, $type, $price, $guest, $phone, $checkin, $checkout, $userId);
    $stmt->execute();
    $newId = $db->insert_id;
    $stmt->close();

    $row = $db->prepare('SELECT * FROM reservations WHERE id = ?');
    $row->bind_param('i', $newId);
    $row->execute();
    $reservation = $row->get_result()->fetch_assoc();
    $row->close();
    echo json_encode(['success' => true, 'reservation' => mapRow($reservation)]);
    exit;
}

/* ── Check In ── */
if ($action === 'checkin') {
    $id   = (int)($_POST['id'] ?? 0);
    $stmt = $db->prepare(
        'UPDATE reservations SET status = "checked_in" WHERE id = ? AND status = "reserved"'
    );
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $affected = $stmt->affected_rows;
    $stmt->close();
    echo json_encode($affected ? ['success' => true] : ['success' => false, 'message' => 'Cannot check in this reservation.']);
    exit;
}

/* ── Check Out ── */
if ($action === 'checkout') {
    $id   = (int)($_POST['id'] ?? 0);
    $stmt = $db->prepare(
        'UPDATE reservations SET status = "checked_out" WHERE id = ? AND status = "checked_in"'
    );
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $affected = $stmt->affected_rows;
    $stmt->close();
    echo json_encode($affected ? ['success' => true] : ['success' => false, 'message' => 'Cannot check out this reservation.']);
    exit;
}

/* ── Cancel ── */
if ($action === 'cancel') {
    $id   = (int)($_POST['id'] ?? 0);
    $stmt = $db->prepare(
        'UPDATE reservations SET status = "cancelled" WHERE id = ? AND status IN ("reserved","checked_in")'
    );
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $affected = $stmt->affected_rows;
    $stmt->close();
    echo json_encode($affected ? ['success' => true] : ['success' => false, 'message' => 'Cannot cancel this reservation.']);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Unknown action.']);
