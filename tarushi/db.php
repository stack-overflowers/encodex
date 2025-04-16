<?php
//SIGN-UP PAGE

//database connection details
$host = "localhost";//server name
$dbname = "finalprj"; //database name
$username = "root"; //mysql default username
$password = "";

//try block-> contains code that might throw an error
try {
    $conn = new mysqli($host, $username, $password, $dbname);
    
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    $user = $conn->real_escape_string($_POST['username'] ?? '');
    $email = $conn->real_escape_string($_POST['email'] ?? '');
    $pass = $conn->real_escape_string($_POST['password'] ?? '');

    if(empty($user) || empty($email) || empty($pass)) {
        throw new Exception("All fields are required");
    }

    $hashedPass = password_hash($pass, PASSWORD_DEFAULT);

    $sql = "INSERT INTO signup (username, email, password) VALUES ('$user', '$email', '$hashedPass')";
    
    if ($conn->query($sql)) {
        echo json_encode(['success' => true, 'message' => 'Registration successful']);
    } else {
        throw new Exception("Insert failed: " . $conn->error);
    }
} 

//catch block -> works if try block throws an exception
catch(Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} 
finally {
    //closes the database connection if the connection was successfully established
    if(isset($conn)) $conn->close();
}
?>