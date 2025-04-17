<?php
header('Content-Type: application/json'); //tells the browser that the response is in JSON format
session_start();//starts the session

//establish a connection to the database
$conn = new mysqli("localhost", "root", "", "finalprj");
$response = ['success' => false, 'message' => '']; //initialized as a default failure message

try {
    if ($conn->connect_error) throw new Exception("DB connection failed");//if the connection to the database is failed

    //reads username and password from the submission form
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? ''; //if value not set -> sets it default to empty string

    //checks if username or password is missing
    if (!$username || !$password)
    {
         throw new Exception("Username and password are required");
    }
    //runs an sql query if it finds a match in the sign up table
    $sql="SELECT * FROM signup WHERE username='$username' AND password='$password'";
    $result=$conn->query($sql);//exceutes the sql query
    if ($result->num_rows == 0) throw new Exception("Invalid username or password"); //throws an error if no match is found in the table
    $user = $result->fetch_assoc();
    echo "successful login";//else displays a successful message on the top right corner
   
} 
catch (Exception $e) {
    //returns the error message in JSON format
    $response['message'] = $e->getMessage();
    echo json_encode($response);
}
