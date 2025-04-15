<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Initialize session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Store active connections
if (!isset($_SESSION['connections'])) {
    $_SESSION['connections'] = [];
}

// Handle different types of requests
$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'register':
        handleRegistration();
        break;
    case 'offer':
        handleOffer();
        break;
    case 'answer':
        handleAnswer();
        break;
    case 'ice-candidate':
        handleIceCandidate();
        break;
    case 'exchange-keys':
        handleKeyExchange();
        break;
    default:
        sendError('Invalid action');
}

function handleRegistration() {
    $userId = uniqid('user_');
    $_SESSION['connections'][$userId] = [
        'status' => 'waiting',
        'timestamp' => time()
    ];
    
    echo json_encode([
        'success' => true,
        'userId' => $userId
    ]);
}

function handleOffer() {
    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data || !isset($data['offer']) || !isset($data['from']) || !isset($data['to'])) {
        sendError('Invalid offer data');
        return;
    }

    $from = $data['from'];
    $to = $data['to'];
    
    if (!isset($_SESSION['connections'][$to])) {
        sendError('Recipient not found');
        return;
    }

    $_SESSION['connections'][$to]['pending_offer'] = $data['offer'];
    $_SESSION['connections'][$to]['from'] = $from;

    echo json_encode(['success' => true]);
}

function handleAnswer() {
    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data || !isset($data['answer']) || !isset($data['from']) || !isset($data['to'])) {
        sendError('Invalid answer data');
        return;
    }

    $from = $data['from'];
    $to = $data['to'];
    
    if (!isset($_SESSION['connections'][$to])) {
        sendError('Recipient not found');
        return;
    }

    $_SESSION['connections'][$to]['pending_answer'] = $data['answer'];
    $_SESSION['connections'][$to]['from'] = $from;

    echo json_encode(['success' => true]);
}

function handleIceCandidate() {
    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data || !isset($data['candidate']) || !isset($data['from']) || !isset($data['to'])) {
        sendError('Invalid ICE candidate data');
        return;
    }

    $from = $data['from'];
    $to = $data['to'];
    
    if (!isset($_SESSION['connections'][$to])) {
        sendError('Recipient not found');
        return;
    }

    if (!isset($_SESSION['connections'][$to]['ice_candidates'])) {
        $_SESSION['connections'][$to]['ice_candidates'] = [];
    }

    $_SESSION['connections'][$to]['ice_candidates'][] = $data['candidate'];

    echo json_encode(['success' => true]);
}

function handleKeyExchange() {
    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data || !isset($data['publicKey']) || !isset($data['from']) || !isset($data['to'])) {
        sendError('Invalid key exchange data');
        return;
    }

    $from = $data['from'];
    $to = $data['to'];
    
    if (!isset($_SESSION['connections'][$to])) {
        sendError('Recipient not found');
        return;
    }

    $_SESSION['connections'][$to]['pending_public_key'] = $data['publicKey'];
    $_SESSION['connections'][$to]['from'] = $from;

    echo json_encode(['success' => true]);
}

function sendError($message) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $message
    ]);
}

// Clean up old connections
function cleanupOldConnections() {
    $currentTime = time();
    foreach ($_SESSION['connections'] as $userId => $connection) {
        if ($currentTime - $connection['timestamp'] > 3600) { // 1 hour timeout
            unset($_SESSION['connections'][$userId]);
        }
    }
}

// Run cleanup
cleanupOldConnections();
?> 