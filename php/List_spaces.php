<?php
// list_spaces.php
// Returns all active spaces as JSON.
// The frontend (spaces.html) fetches this file to render the cards dynamically
// instead of having them hardcoded in the HTML.

require __DIR__ . '/Connection.php';

header('Content-Type: application/json; charset=utf-8');

try {
    // Filters (type, max price) arrive as optional GET parameters.
    // Example: list_spaces.php?type=meeting room&max_price=20
    $type = $_GET['type'] ?? null;
    $maxPrice = $_GET['max_price'] ?? null;

    $sql = 'SELECT id, name, type, capacity, price_per_hour, description, image 
            FROM spaces 
            WHERE active = 1';
    $params = [];

    if ($type && $type !== 'All') {
        $sql .= ' AND type = :type';
        $params[':type'] = $type;
    }

    if ($maxPrice) {
        $sql .= ' AND price_per_hour <= :max_price';
        $params[':max_price'] = $maxPrice;
    }

    $sql .= ' ORDER BY price_per_hour ASC';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $spaces = $stmt->fetchAll();

    echo json_encode([
        'success' => true,
        'spaces' => $spaces
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching spaces.'
    ]);
}
