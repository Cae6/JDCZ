<?php
require_once('db_connection.php');
require_once('path.inc');
require_once('get_host_info.inc');
require_once('rabbitMQLib.inc');  



function requestProcessor($request) {
    echo "Request received" . PHP_EOL;
    var_dump($request);

    // Ensure the request contains a 'type'
    if (!isset($request['type'])) {
        return ["error" => "Unsupported message type"];
    }

    switch ($request['type']) {
        case "register":
            // Ensure required fields are present
            if (!isset($request['username']) || !isset($request['password']) || !isset($request['firstName']) || !isset($request['lastName'])) {
                return ["error" => "Missing required fields"];
            }
            return doRegister($request['username'], $request['password'], $request['firstName'], $request['lastName']);

        case "login":
            // Ensure 'username' and 'password' are provided
            if (!isset($request['username']) || !isset($request['password'])) {
                return ["error" => "Missing username or password"];
            }

            // Perform login
            return doLogin($request['username'], $request['password']);

        case "authenticate":
            // Ensure 'session_id' is provided
            if (!isset($request['session_id'])) {
                return ["error" => "Missing session ID"];
            }

            // Perform session authentication
            return doAuthenticate($request['session_id']);

        case "clear_expired_sessions":
            // Clear expired sessions
            return sessionExpire();

        default:
            return ["error" => "Unknown request type"];
    }
}

$server = new rabbitMQServer("testRabbitMQ.ini", "testServer");
echo "testRabbitMQServer BEGIN".PHP_EOL;
$server->process_requests('requestProcessor');
echo "rabbitMQServer END".PHP_EOL;
exit();
?>
