<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    login();
}
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>tests - login</title>
</head>

<body>
    <div>
        <h1>Login</h1>
        <div>
            <form method="post">
                <input type="user" name="user" id="user" />
                <input type="text" name="password" id="password" />
                <button type="submit">login</button>
            </form>
        </div>
    </div>
</body>

</html>