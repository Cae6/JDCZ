#!/usr/bin/php

<?php
require_once('path.inc');
require_once('get_host_info.inc');
require_once('rabbitMQLib.inc');

// Database connection function
function connectToDatabase() {
    // Set your DB credentials here
    $mydb = new mysqli('10.144.59.102', 'zb123', 'password', 'beginDB');
    
    // Check connection
    if ($mydb->connect_error) {
        echo "Failed to connect to the database: " . $mydb->connect_error . PHP_EOL;
        exit(0);
    }
    return $mydb;
}




$query = "SELECT * FROM users;";
$response = $mydb->query($query);
if ($mydb->error != 0) {
    echo "Failed to execute query: " . PHP_EOL;
    echo __FILE__ . ':' . __LINE__ . ": error: " . $mydb->error . PHP_EOL;
    exit(0);
}

// Login function
function doLogin($username, $password) {
    // Connect to the database
    $conn = connectToDatabase();
    
    // Prepare and execute the query to fetch password from the database
    $stmt = $conn->prepare("SELECT password FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->bind_result($dbPassword);
    $stmt->fetch();
    $stmt->close();
    
    // Check if password matches
    if ($password === $dbPassword) {
        // Generate a session token
        $token = bin2hex(random_bytes(32));
        
        // Update the database with the session token
        $stmt = $conn->prepare("UPDATE users SET token = ? WHERE username = ?");
        $stmt->bind_param("ss", $token, $username);
        $stmt->execute();
        $stmt->close();
        
        // Return success
        return ["success" => true, "message" => "Login successful", "token" => $token];
    } else {
        return ["success" => false, "message" => "Invalid credentials"];
    }
}

// Register function
function doRegister($username, $password, $firstName, $lastName) {
    // Connect to the database
    $conn = connectToDatabase();
    
    // Prepare and insert user into the database
    $stmt = $conn->prepare("INSERT INTO users (username, password, firstName, lastName) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $username, $hashedPassword, $firstName, $lastName);
    
    if ($stmt->execute()) {
        $stmt->close();
        return ["success" => true, "message" => "Registration successful"];
    } else {
        $stmt->close();
        return ["success" => false, "message" => "Username already exists"];
    }
}

function doAuthenticate($sessionID) {
    // Connect to the database
    $conn = connectToDatabase();

    // Get session details from the database
    $stmt = $conn->prepare("SELECT session_id, session_time FROM sessions WHERE session_id = ?");
    $stmt->bind_param("s", $sessionID);
    $stmt->execute();
    $stmt->bind_result($dbSessionID, $sessionTime);
    $stmt->fetch();
    $stmt->close();

    // If no session found, return error
    if (!$dbSessionID) {
        return ["success" => false, "message" => "Invalid session"];
    }

    // Check if session has expired (e.g., 5 minutes expiration time)
    $currentTime = time();
    $sessionLifetime = 300;

    if (($currentTime - $sessionTime) > $sessionLifetime) {
        // Session expired, remove it
        deleteSession($sessionID);
        return ["success" => false, "message" => "Session expired"];
    }

    // Session is valid
    return ["success" => true, "message" => "Session is valid"];
}

function deleteSession($sessionID) {
    // Connect to the database
    $conn = connectToDatabase();

    // Delete session from the database
    $stmt = $conn->prepare("DELETE FROM sessions WHERE session_id = ?");
    $stmt->bind_param("s", $sessionID);
    $stmt->execute();
    $stmt->close();
    $conn->close();
}


function initiateSession($username) {
    // Connect to the database
    $conn = connectToDatabase();

    // Generate a secure session ID
    $sessionID = bin2hex(random_bytes(32));  //32 bytes

    // Generate a secure session ID 
    $sessionID = bin2hex(random_bytes(32)); //32 bytes

    // Get the current time 
    $sessionTime = time();

    // Insert session into the database
    $stmt = $conn->prepare("INSERT INTO sessions (session_id, username, session_time) VALUES (?, ?, ?)");
    $stmt->bind_param("ssi", $sessionID, $username, $sessionTime);
    
    // Execute the query and check if successful
    if ($stmt->execute()) {
        $stmt->close();
        $conn->close();
        return ["success" => true, "session_id" => $sessionID];  // Return the session ID
    } else {
        $stmt->close();
        $conn->close();
        return ["success" => false, "message" => "Failed to create session"];
    }
}

function sessionExpire() {
   
    $conn = connectToDatabase();

    //  session lifetime 
    $sessionLifetime = 300;

    // current time
    $currentTime = time();

    // Delete sessions that have expired (session_time + sessionLifetime < currentTime)
    $stmt = $conn->prepare("DELETE FROM sessions WHERE (session_time + ?) < ?");
    $stmt->bind_param("ii", $sessionLifetime, $currentTime);
    
    //check if sessions were removed
    if ($stmt->execute()) {
        $stmt->close();
        $conn->close();
        return ["success" => true, "message" => "Expired sessions cleared"];
    } else {
        $stmt->close();
        $conn->close();
        return ["success" => false, "message" => "Failed to clear expired sessions"];
    }
}

?>
