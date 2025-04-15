<?php
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $message = $_POST['message'];
    $encryptionType = $_POST['encryptionType'];

    if (empty($message) || empty($encryptionType)) {
        echo json_encode(['error' => 'Message or encryption type cannot be empty.']);
        exit;
    }

    if ($encryptionType === 'symmetric') {
        // Symmetric Key Encryption (AES)
        $key = openssl_random_pseudo_bytes(16); // Generate random key
        $iv = openssl_random_pseudo_bytes(16);  // Generate random initialization vector
        $cipherText = openssl_encrypt($message, 'AES-128-CBC', $key, 0, $iv);

        if ($cipherText === false) {
            echo json_encode(['error' => 'Symmetric encryption failed.']);
            exit;
        }

        // Combine IV and ciphertext, and encode in base64
        $encryptedMessage = base64_encode($iv . $cipherText);

        echo json_encode([
            'encryptedMessage' => $encryptedMessage,
            'key' => base64_encode($key)
        ]);
    } elseif ($encryptionType === 'asymmetric') {
        // Asymmetric Key Encryption (RSA)
        $privateKeyFile = 'private.pem';
        $publicKeyFile = 'public.pem';

        // Check if key files exist
        if (!file_exists($privateKeyFile) || !file_exists($publicKeyFile)) {
            echo json_encode(['error' => 'Key files missing.']);
            exit;
        }

        $privateKey = openssl_pkey_get_private(file_get_contents($privateKeyFile));
        $publicKey = openssl_pkey_get_public(file_get_contents($publicKeyFile));

        if (!$publicKey) {
            echo json_encode(['error' => 'Failed to load public key.']);
            exit;
        }

        // Encrypt the message using the public key
        if (!openssl_public_encrypt($message, $encryptedMessage, $publicKey)) {
            echo json_encode(['error' => 'Encryption failed.']);
            exit;
        }

        // Convert the public key to a readable format
        $publicKeyDetails = openssl_pkey_get_details($publicKey);
        $formattedPublicKey = $publicKeyDetails['key'];

        echo json_encode([
            'encryptedMessage' => base64_encode($encryptedMessage),
            'publicKey' => $formattedPublicKey
        ]);
    } else {
        echo json_encode(['error' => 'Invalid encryption type selected.']);
        exit;
    }
} else {
    echo json_encode(['error' => 'Invalid request method.']);
    exit;
}
?>
