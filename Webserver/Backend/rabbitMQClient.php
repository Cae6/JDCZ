#!/usr/bin/php 
<?php
require_once('path.inc');
require_once('get_host_info.inc');
require_once('rabbitMQLib.inc');
require_once('db_connection.php');

$client = new rabbitMQClient("testRabbitMQ.ini", "testServer");

if (!isset($_POST['action'])) {
    echo json_encode(["error" => "Missing action parameter"]);
    exit();
}

$action = $_POST['action'];

switch ($action) {
    case "register":
        if (!isset($_POST['username']) || !isset($_POST['password']) || !isset($_POST['firstname']) || !isset($_POST['lastname'])) {
            echo json_encode(["error" => "Missing required registration fields"]);
            exit();
        }
        
        $request = [
            'type' => "Register",
            'username' => $_POST['username'],
            'password' => $_POST['password'],
            'firstname' => $_POST['firstname'],
            'lastname' => $_POST['lastname']
        ];
        
        $response = $client->send_request($request);
        echo json_encode($response);
        break;

    case "login":
        if (!isset($_POST['username']) || !isset($_POST['password'])) {
            echo json_encode(["error" => "Missing username or password"]);
            exit();
        }
        
        $request = [
            'type' => "Login",
            'username' => $_POST['username'],
            'password' => $_POST['password']
        ];
        
        $response = $client->send_request($request);
        
        if ($response['status'] === "success") {
            setcookie("session_id", $response['session_id'], time() + 300, "/");
            echo json_encode(["message" => "Login successful, session started", "redirect_url" => "homepage.php"]);
        } else {
            echo json_encode(["error" => "Invalid credentials"]);
        }
        break;
    
    case "authenticate":
        if (!isset($_COOKIE['session_id'])) {
            echo json_encode(["error" => "Session not set"]);
            exit();
        }
        
        $request = [
            'type' => "Authenticate",
            'session_id' => $_COOKIE['session_id']
        ];
        
        $response = $client->send_request($request);
        
        if ($response['status'] === "valid") {
            echo json_encode(["message" => "Session is valid"]);
        } else {
            setcookie("session_id", "", time() - 300, "/");
            echo json_encode(["error" => "Invalid session, logging out"]);
        }
        break;

    default:
        echo json_encode(["error" => "Invalid action"]);
        break;
}
?>
