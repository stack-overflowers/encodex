<?php
//SIGN-UP PAGE

//database connection details
$host = "localhost";//server name
$dbname = "finalprj"; //database name
$username = "root"; //mysql default username
$password = "";

//try block-> contains code that might throw an error
try {

    $conn = new mysqli($host, $username, $password, $dbname); //connect to database
    //throws an exception with error message if connection fails
    if ($conn->connect_error)
     {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }


    //used to get and store inputs of the form
    //only takes the special characters and escape them
    $user = $conn->real_escape_string($_POST['username'] ?? '');
    $email = $conn->real_escape_string($_POST['email'] ?? '');
    $pass = $conn->real_escape_string($_POST['password'] ?? '');

    //checks if any field is empty
    if(empty($user) || empty($email) || empty($pass)) {
        throw new Exception("All fields are required");
    }

    //inserts new user data into the signup table
    $sql = "INSERT INTO signup (username, email, password) VALUES ('$user', '$email', '$pass')";
    
    //if query runs successfully it responds with a success message in JSON format
    if ($conn->query($sql)) 
    {
        echo json_encode(['success' => true, 'message' => 'Registration successful']);
    } 
    else
    //throws exception if query fails
    {
        throw new Exception("Insert failed: " . $conn->error);
    }
} 

//catch block -> works if try block throws an exception
catch(Exception $e) {
    //returns the error message in JSON format
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} 
finally {
    //closes the database connection whether the process is success or fail
    if(isset($conn)) $conn->close();
}
?>