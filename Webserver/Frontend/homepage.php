<?php

if (!isset($_COOKIE['session_id'])) {
    header("Location: login.html");
    exit();
}

$authResponse = doAuthenticate($_COOKIE['session_id']);
if (!$authResponse['success']) {
    header("Location: login.html");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome</title>
    <link rel="stylesheet" href="homepage.css">
</head>
<body>
    <div class="container">
        <h1>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h1>
        <p>You have successfully logged in.</p>
    </div>
</body>
</html>
