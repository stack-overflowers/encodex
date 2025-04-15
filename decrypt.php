<?php
header("Content-Type: application/json");

$requestBody = json_decode(file_get_contents("php://input"), true);
$decryptType = $requestBody['decryptType'] ?? '';
$encryptedMessage = $requestBody['encryptedMessage'] ?? '';
$key = $requestBody['key'] ?? '';

if (empty($decryptType) || empty($encryptedMessage) || empty($key)) {
    echo json_encode(['error' => 'Decryption type, encrypted message, and key are required.']);
    exit;
}

if ($decryptType === 'symmetric') {
    $key = base64_decode($key);
    $encryptedData = base64_decode($encryptedMessage);

    // Extract the IV and the ciphertext
    $iv = substr($encryptedData, 0, 16); // First 16 bytes
    $cipherText = substr($encryptedData, 16); // Remaining bytes

    // Perform decryption
    $decryptedMessage = openssl_decrypt($cipherText, 'AES-128-CBC', $key, 0, $iv);

    if ($decryptedMessage === false) {
        echo json_encode(['error' => 'Symmetric decryption failed.']);
        exit;
    }

    echo json_encode(['decryptedMessage' => $decryptedMessage]);
} elseif ($decryptType === 'asymmetric') {
    $privateKeyFile = 'private.pem';

    if (!file_exists($privateKeyFile)) {
        echo json_encode(['error' => 'Private key file missing.']);
        exit;
    }

    $privateKey = openssl_pkey_get_private(file_get_contents($privateKeyFile));

    if (!$privateKey) {
        echo json_encode(['error' => 'Failed to load private key.']);
        exit;
    }

    $encryptedMessage = base64_decode($encryptedMessage);

    if (!openssl_private_decrypt($encryptedMessage, $decryptedMessage, $privateKey)) {
        echo json_encode(['error' => 'Asymmetric decryption failed.']);
        exit;
    }

    echo json_encode(['decryptedMessage' => $decryptedMessage]);
} else {
    echo json_encode(['error' => 'Invalid decryption type selected.']);
    exit;
}
?>
