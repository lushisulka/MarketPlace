<?php
require_once __DIR__ . '/config.php';

$email = "plaza@gmail.com";
$newPassword = "123456";

$hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

$stmt = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
$stmt->bind_param("ss", $hashedPassword, $email);

if ($stmt->execute()) {
    echo "✅ Password u ndryshua me sukses për: " . $email;
} else {
    echo "❌ Gabim gjatë ndryshimit!";
}