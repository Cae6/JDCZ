<?php
require_once('path.inc');
require_once('get_host_info.inc');
require_once('rabbitMQLib.inc');


function connectToDatabase() {
    // 
    $mydb = new mysqli('localhost', 'zb123', 'password', 'begindb');
    
    // Check connection
    if ($mydb->connect_error) {
        echo "Failed to connect to the database: " . $mydb->connect_error . PHP_EOL;
        exit(0);
    }
    return $mydb;
}

// Login function
function doLogin($username, $password) {
    // Connect to the database
    $conn = connectToDatabase();
    
    
    $stmt = $conn->prepare("SELECT id, password FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->bind_result($userId, $dbPassword);
    $stmt->fetch();
    $stmt->close();
    
   
    if ($userId && $password === $dbPassword) {
        // a session token
        $token = bin2hex(random_bytes(32));
        $sessionTime = time();
        
      
        $stmt = $conn->prepare("UPDATE users SET token = ? WHERE id = ?");
        $stmt->bind_param("si", $token, $userId);
        $stmt->execute();
        $stmt->close();

        $stmt = $conn->prepare("INSERT INTO session (session_id, user_id, session_time) VALUES (?, ?, ?)");
        $stmt->bind_param("sii", $token, $userId, $sessionTime);
        if (!$stmt->execute()) {
            $stmt->close();
            $conn->close();
            return ["success" => false, "message" => "Failed to create session"];
        }
        $stmt->close();
        $conn->close();
        
        // Return success
        return ["success" => true, "message" => "Login successful", "session_id" => $token, "user_id" => $userId];
    } else {
        return ["success" => false, "message" => "Invalid credentials"];
    }
}

// Register function
function doRegister($username, $password, $firstName, $lastName) {
    
    $conn = connectToDatabase();
    
    
    $stmt = $conn->prepare("INSERT INTO users (username, password, firstName, lastName) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $username, $password, $firstName, $lastName);
    
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
    $stmt = $conn->prepare("SELECT session_id, session_time FROM session WHERE session_id = ?");
    $stmt->bind_param("s", $sessionID);
    $stmt->execute();
    $stmt->bind_result($dbSessionID, $sessionTime);
    $stmt->fetch();
    $stmt->close();

    // If no session found, return error
    if (!$dbSessionID) {
        return ["success" => false, "message" => "Invalid session"];
    }

    // Check if session has expired 
    $currentTime = time();
    $sessionLifetime = 300;

    if (($currentTime - $sessionTime) > $sessionLifetime) {
  
        deleteSession($sessionID);
        return ["success" => false, "message" => "Session expired"];
    }

    
    return ["success" => true, "message" => "Session is valid"];
}

function deleteSession($sessionID) {
    // Connect to the database
    $conn = connectToDatabase();

    // Delete session from the database
    $stmt = $conn->prepare("DELETE FROM session WHERE session_id = ?");
    $stmt->bind_param("s", $sessionID);
    $stmt->execute();
    $stmt->close();
    $conn->close();
}



function sessionExpire() {
   
    $conn = connectToDatabase();

   
    $sessionLifetime = 300;

   
    $currentTime = time();

    // (session_time + sessionLifetime < currentTime)
    $stmt = $conn->prepare("DELETE FROM session WHERE (session_time + ?) < ?");
    $stmt->bind_param("ii", $sessionLifetime, $currentTime);
    
   
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
