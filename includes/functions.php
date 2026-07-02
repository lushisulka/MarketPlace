<?php

// ---- AUTH HELPERS ----
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ' . SITE_URL . '/auth/login.php');
        exit;
    }
}

function requireRole($role) {
    requireLogin();
    if ($_SESSION['user_role'] !== $role && $_SESSION['user_role'] !== 'admin') {
        header('Location: ' . SITE_URL . '/index.php');
        exit;
    }
}

function currentUser() {
    return $_SESSION ?? null;
}

// ---- SANITIZE ----
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

// ---- SLUG GENERATOR ----
function createSlug($string) {
    $string = strtolower($string);
    $string = preg_replace('/[^a-z0-9\s-]/', '', $string);
    $string = preg_replace('/[\s-]+/', '-', $string);
    return trim($string, '-');
}

// ---- ORDER NUMBER ----
function generateOrderNumber() {
    return 'ORD-' . strtoupper(uniqid());
}

// ---- CART COUNT ----
function getCartCount($conn, $user_id) {
    $stmt = $conn->prepare("SELECT SUM(quantity) as total FROM cart WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    return $result['total'] ?? 0;
}

// ---- STAR RATING HTML ----
function renderStars($rating) {
    $stars = '';
    for ($i = 1; $i <= 5; $i++) {
        $stars .= $i <= $rating ? '⭐' : '☆';
    }
    return $stars;
}

// ---- UPLOAD FILE ----
function uploadFile($file, $folder = 'products') {
    $allowed = ['jpg', 'jpeg', 'png', 'webp', 'pdf'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    if (!in_array($ext, $allowed)) return false;
    
    $filename = uniqid() . '_' . time() . '.' . $ext;
    $path = UPLOAD_PATH . $folder . '/' . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $path)) {
        return $filename;
    }
    return false;
}

// ---- FORMAT PRICE ----
function formatPrice($price) {
    return number_format($price, 2, '.', ',') . ' ALL';
}

// ---- FLASH MESSAGES ----
function setFlash($type, $message) {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}
console.log('functions.php loaded');