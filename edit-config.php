<?php
    // Include the config file
    require_once 'config.php';

    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Update the values from the form
        $bookstackURL = $_POST['bookstackURL'];
        $apiEndpoint = $_POST['apiEndpoint'];
        $apiEndpointBooks = $_POST['apiEndpointBooks'];
        $token = $_POST['token'];
        $key = $_POST['key'];

        // Update the config file with the new values
        $configContent = "<?php\n";
        $configContent .= "\$bookstackURL = '$bookstackURL';\n";
        $configContent .= "\$apiEndpoint = '$apiEndpoint';\n";
        $configContent .= "\$apiEndpointBooks = '$apiEndpointBooks';\n";
        $configContent .= "\$token = '$token';\n";
        $configContent .= "\$key = '$key';\n";
        $configContent .= "?>";

        file_put_contents('config.php', $configContent);

        header('Location: index.php?success=true');
        exit();
    }
?>

<!DOCTYPE html>
<html lang="nl">
<head>
    <title>Wijzig configuratie</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-9ndCyUaIbzAi2FUVXJi0CjmCapSmO7SnpJef0486qhLnuZ2cdeRhO02iuK6FUUVM" crossorigin="anonymous">
</head>
<body>
    <div class="container mt-5">
        <h1>Edit Config</h1>
        <form action="" method="POST">
            <div class="mb-3">
                <label for="bookstackURL" class="form-label">Bookstack URL:</label>
                <input type="text" name="bookstackURL" class="form-control" id="bookstackURL" value="<?php echo $bookstackURL; ?>">
            </div>
            <div class="mb-3">
                <label for="apiEndpoint" class="form-label">API Endpoint:</label>
                <input type="text" name="apiEndpoint" class="form-control" id="apiEndpoint" value="<?php echo $apiEndpoint; ?>">
            </div>
            <div class="mb-3">
                <label for="apiEndpointBooks" class="form-label">API Endpoint Books:</label>
                <input type="text" name="apiEndpointBooks" class="form-control" id="apiEndpointBooks" value="<?php echo $apiEndpointBooks; ?>">
            </div>
            <div class="mb-3">
                <label for="token" class="form-label">Token:</label>
                <input type="text" name="token" class="form-control" id="token" value="<?php echo $token; ?>">
            </div>
            <div class="mb-3">
                <label for="key" class="form-label">Key:</label>
                <input type="text" name="key" class="form-control" id="key" value="<?php echo $key; ?>">
            </div>
            <button type="submit" class="btn btn-primary">Save</button>
        </form>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" integrity="sha384-geWF76RCwLtnZ8qwWowPQNguL3RmwHVBC9FhGdlKrxdiJJigb/j/68SIy3Te4Bkz" crossorigin="anonymous"></script>
</body>
</html>
