<?php
// create_booking.php
// Receives the booking.html form data (via POST/fetch)
// and saves it to MySQL, checking beforehand that the time slot doesn't overlap.

require 'Connection.php';

header('Content-Type: application/json; charset=utf-8');

// Data arrives as JSON from the fetch() call in booking.js
$data = json_decode(file_get_contents('php://input'), true);

$spaceId = $data['space_id'] ?? null;
$date = $data['date'] ?? null;
$startTime = $data['start_time'] ?? null;
$endTime = $data['end_time'] ?? null;
$name = trim($data['name'] ?? '');
$email = trim($data['email'] ?? '');

// --- Server-side validation ---
// Client-side JS validation is never enough: anyone could bypass the form
// and call this endpoint directly, so the important checks are repeated here.
if (!$spaceId || !$date || !$startTime || !$endTime || !$name || !$email) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Required fields are missing.']);
    exit;
}

if ($endTime <= $startTime) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'End time must be after start time.']);
    exit;
}

if ($date < date('Y-m-d')) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'The date cannot be earlier than today.']);
    exit;
}

try {
    // 1) Check the space exists and get its hourly price
    $stmt = $pdo->prepare('SELECT price_per_hour FROM spaces WHERE id = :id AND active = 1');
    $stmt->execute([':id' => $spaceId]);
    $space = $stmt->fetch();

    if (!$space) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'The selected space does not exist.']);
        exit;
    }

    // 2) Check for overlap: is there already a pending/confirmed booking
    //    for that space, that day, overlapping the requested time slot?
    $overlapStmt = $pdo->prepare('
        SELECT COUNT(*) AS total
        FROM bookings
        WHERE space_id = :space_id
          AND booking_date = :booking_date
          AND status IN ("pending", "confirmed")
          AND start_time < :end_time
          AND end_time > :start_time
    ');
    $overlapStmt->execute([
        ':space_id' => $spaceId,
        ':booking_date' => $date,
        ':start_time' => $startTime,
        ':end_time' => $endTime,
    ]);
    $overlap = $overlapStmt->fetch();

    if ($overlap['total'] > 0) {
        http_response_code(409); // conflict
        echo json_encode(['success' => false, 'message' => 'That space is already booked for this time slot.']);
        exit;
    }

    // 3) Calculate the total price based on the booked hours
    $start = new DateTime($startTime);
    $end = new DateTime($endTime);
    $hours = ($end->getTimestamp() - $start->getTimestamp()) / 3600;
    $totalPrice = round($hours * $space['price_per_hour'], 2);

    // 4) Find or create the user by email (simplified: no login yet)
    $userStmt = $pdo->prepare('SELECT id FROM users WHERE email = :email');
    $userStmt->execute([':email' => $email]);
    $user = $userStmt->fetch();

    if ($user) {
        $userId = $user['id'];
    } else {
        $newUserStmt = $pdo->prepare(
            'INSERT INTO users (name, email, password, role) VALUES (:name, :email, :password, "customer")'
        );
        // Temporary random password: in the version with real login,
        // the user would sign up before booking.
        $tempPassword = password_hash(bin2hex(random_bytes(8)), PASSWORD_DEFAULT);
        $newUserStmt->execute([
            ':name' => $name,
            ':email' => $email,
            ':password' => $tempPassword,
        ]);
        $userId = $pdo->lastInsertId();
    }

    // 5) Insert the booking
    $bookingStmt = $pdo->prepare('
        INSERT INTO bookings (user_id, space_id, booking_date, start_time, end_time, status, total_price)
        VALUES (:user_id, :space_id, :booking_date, :start_time, :end_time, "pending", :total_price)
    ');
    $bookingStmt->execute([
        ':user_id' => $userId,
        ':space_id' => $spaceId,
        ':booking_date' => $date,
        ':start_time' => $startTime,
        ':end_time' => $endTime,
        ':total_price' => $totalPrice,
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'Booking created successfully.',
        'total_price' => $totalPrice,
        'booking_id' => $pdo->lastInsertId(),
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error saving the booking.']);
}
