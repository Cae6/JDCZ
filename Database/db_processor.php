<?php
require_once('db_connection.php');
require_once('path.inc');
require_once('get_host_info.inc');
require_once('rabbitMQLib.inc');  // Include your database connection function


function requestProcessor($request)
{

    echo "request recieved".PHP_EOL;
    var_dump($request);

    // Ensure request contains 'type'
    if (!isset($request['type'])) {
        return ["error" => "Unsupported message type"];
    }

    switch ($request['type']) {
        case "login":
            // Ensure 'username' and 'password'
            if (!isset($request['username']) || !isset($request['password'])) {
                return ["error" => "Missing username or password"];
            }

           
            $response = doLogin($request['username'], $request['password']);
            
            // Check if login was successful
            if ($response["success"] === true) {
                
                $sessionDB = createSession($response["user"]["id"]);
                
                // Add session data to response
                $response["session"] = $sessionDB;
                
                sessionExpire();
            } else {
                return $response;  
            }
            return $response; 

        case "register":
            if (!isset($request['username']) || !isset($request['password']) || !isset($request['firstName']) || !isset($request['lastName'])) {
                return ["error" => "Missing required fields"];
            }
            return doRegister($request['username'], $request['password'], $request['firstName'], $request['lastName']);

        case "authenticate":
            // Ensure 'session_id'
            if (!isset($request['session_id'])) {
                return ["error" => "Missing session ID"];
            }
            return doAuthenticate($request['session_id']);

        default:
            return ["error" => "Unknown request type"];
    }
}

$server = new rabbitMQServer("testRabbitMQ.ini", "server");
$server->process_requests('requestProcessor');
?>
