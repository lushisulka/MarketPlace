<?php
require_once __DIR__ . '/../config/config.php';
header('Content-Type: application/json');

// Lejo client DHE b2b
if (!isLoggedIn() || !in_array($_SESSION['user_role'], ['client', 'b2b'])) {
    echo json_encode(['error' => 'Duhet të hysh si klient ose B2B për të shtuar në shportë.']);
    exit;
}

$action     = $_POST['action'] ?? '';
$product_id = (int)($_POST['product_id'] ?? 0);
$user_id    = $_SESSION['user_id'];

if ($action === 'add') {
    $qty = (float)($_POST['quantity'] ?? 1);
    if ($qty <= 0) $qty = 1;

    $stmt = $conn->prepare("SELECT * FROM products WHERE id = ? AND status = 'active'");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $product = $stmt->get_result()->fetch_assoc();

    if (!$product) {
        echo json_encode(['error' => 'Produkti nuk u gjet.']);
        exit;
    }

    // Shiko nëse ekziston tashmë në shportë
    $stmt = $conn->prepare("SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ?");
    $stmt->bind_param("ii", $user_id, $product_id);
    $stmt->execute();
    $existing = $stmt->get_result()->fetch_assoc();

    if ($existing) {
        $new_qty = $existing['quantity'] + $qty;
        $stmt = $conn->prepare("UPDATE cart SET quantity = ? WHERE id = ?");
        $stmt->bind_param("di", $new_qty, $existing['id']);
    } else {
        $stmt = $conn->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)");
        $stmt->bind_param("iid", $user_id, $product_id, $qty);
    }
    $stmt->execute();

    $count = getCartCount($conn, $user_id);
    echo json_encode(['success' => true, 'cart_count' => $count, 'message' => 'Produkti u shtua në shportë!']);

} elseif ($action === 'remove') {
    $stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ? AND product_id = ?");
    $stmt->bind_param("ii", $user_id, $product_id);
    $stmt->execute();
    echo json_encode(['success' => true]);

} elseif ($action === 'update') {
    $qty = (float)($_POST['quantity'] ?? 1);
    if ($qty <= 0) {
        $stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ? AND product_id = ?");
        $stmt->bind_param("ii", $user_id, $product_id);
    } else {
        $stmt = $conn->prepare("UPDATE cart SET quantity = ? WHERE user_id = ? AND product_id = ?");
        $stmt->bind_param("dii", $qty, $user_id, $product_id);
    }
    $stmt->execute();
    echo json_encode(['success' => true]);

} else {
    echo json_encode(['error' => 'Veprim i panjohur.']);
}
<h1>1</h1>