<?php
    include 'config.php';
    require 'vendor/autoload.php';

    $apiEndpoint = $bookstackURL . '/api/pages';
    $apiEndpointBooks = $bookstackURL . '/api/books';

    // Check if a success message exists in the URL parameters
    $success = isset($_GET['success']) && $_GET['success'] === 'true';

    // Initialize the error message variable
    $errorMessage = '';

    use GuzzleHttp\Client;
    use GuzzleHttp\Exception\ClientException;
    use GuzzleHttp\Exception\GuzzleException;

    // Bookstack authentication credentials
    $apiToken = $token . ':' . $key;

    $apiHeader = [
        'Content-Type' => 'application/json',
        'Authorization' => 'Token ' . $apiToken
    ];

    // Function to extract the content from a DOCX file and convert it to HTML
    function extractContent($docxPath) {
        $phpWord = \PhpOffice\PhpWord\IOFactory::load($docxPath);
        $htmlPath = 'temp.html';
        $phpWord->save($htmlPath, 'HTML');
        $content = file_get_contents($htmlPath);
        unlink($htmlPath); // Remove the temporary HTML file

        // Clean up the HTML using tidy
        $config = [
            'indent' => true,
            'indent-spaces' => 2,
            'wrap' => 0,
            'show-body-only' => true,
        ];

        $tidy = new tidy();
        $tidy->parseString($content, $config, 'utf8');
        $tidy->cleanRepair();

        $content = $tidy->value;
    
        return $content;
    }

    function extractErrorMessage($message) {
        $start = strpos($message, 'Client error:') + strlen('Client error:');
        $end = strpos($message, 'resulted in a') + strlen('resulted in a');
        $errorMessage = substr($message, $start, $end - $start);
    
        // Find the second backtick after "resulted in a"
        $secondBacktick = strpos($message, '`', $end + strlen('resulted in a') + 1);
        if ($secondBacktick !== false) {
            $errorMessage .= substr($message, $end, $secondBacktick - $end + 1);
        }
    
        return trim($errorMessage);
    }

    function getAllBooks() {
        global $apiEndpointBooks, $apiHeader;
    
        $client = new Client();
        try {
            $response = $client->get($apiEndpointBooks, [
                'headers' => $apiHeader,
            ]);
    
            $statusCode = $response->getStatusCode();
            $responseBody = json_decode($response->getBody(), true);
    
            if ($statusCode === 200) {
                if (isset($responseBody['data']) && is_array($responseBody['data'])) {
                    return $responseBody['data'];
                } else {
                    throw new Exception('Invalid API response: Data array not found');
                }
            } if ($statusCode === 404) {
                throw new Exception('API endpoint not found.');
            } else {
                throw new Exception('Failed to fetch books. Status code: ' . $statusCode);
            }
        } catch (ClientException $e) {
            $errorMessage = 'Error in Guzzle client: ' . extractErrorMessage($e->getMessage());
            echo '<script>console.error("' . $errorMessage . '");</script>';
            throw new Exception('Error in Guzzle client');
        } catch (GuzzleException $e) {
            $errorMessage = 'Guzzle exception occurred: ' . extractErrorMessage($e->getMessage());
            echo '<script>console.error("' . $errorMessage . '");</script>';
            throw new Exception('Guzzle exception occurred');
        } catch (Exception $e) {
            throw new Exception('Error fetching books: ' . $e->getMessage());
        }
    }    

    // Function to get the book slug based on the book ID
    function getBookSlug($bookId) {
        global $apiEndpointBooks, $apiHeader;

        $client = new Client();
        $response = $client->get($apiEndpointBooks . '/' . $bookId, [
            'headers' => $apiHeader,
        ]);

        $statusCode = $response->getStatusCode();
        $responseBody = json_decode($response->getBody(), true);

        if ($statusCode === 200) {
            return $responseBody['slug'];
        } else {
            return null;
        }
    }

    // Function to create a new page in Bookstack
    function createPage($title, $content, $bookId) {
        global $apiEndpoint, $apiHeader, $jsonResponses;

        $data = [
            'name' => $title,
            'html' => $content,
            'book_id' => $bookId
        ];

        $client = new Client();
        $response = $client->post($apiEndpoint, [
            'headers' => $apiHeader,
            'json' => $data
        ]);

        $statusCode = $response->getStatusCode();
        $responseBody = json_decode($response->getBody(), true);
        $responseFormatted = json_encode($responseBody, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        if ($statusCode === 200) {
            $jsonResponses[] = "Page created: $title\n" . $responseFormatted;
        } else {
            $jsonResponses[] = "Page not created due to an error: $title\n" . $responseFormatted;
        }
    }

    // Retrieve all books from Bookstack
    try {
        $books = getAllBooks();
    } catch (Exception $e) {
        $errorMessage = $e->getMessage();
    }

    // Store JSON responses
    $jsonResponses = [];

    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $selectedBookId = $_POST['book_id'] ?? null;
        $docxFiles = $_FILES['docx_files'] ?? [];

        // Check if a book and docx files are selected
        if ($selectedBookId && $docxFiles && is_array($docxFiles['name'])) {
            // Process each selected DOCX file
            foreach ($docxFiles['name'] as $index => $docxName) {
                $docxPath = $docxFiles['tmp_name'][$index];
                $pageTitle = pathinfo($docxName, PATHINFO_FILENAME);

                // Extract content from DOCX and create page in Bookstack
                $content = extractContent($docxPath);
                createPage($pageTitle, $content, $selectedBookId);
            }

            // Get the book slug
            $bookSlug = getBookSlug($selectedBookId);
        } else {
            echo "Select a book and at least one docx file.";
        }
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Bulk import to Bookstack</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-9ndCyUaIbzAi2FUVXJi0CjmCapSmO7SnpJef0486qhLnuZ2cdeRhO02iuK6FUUVM" crossorigin="anonymous">
</head>
<body>
    <div class="container mt-5">
        <div class="text-end">
            <a href="edit-config.php" class="btn btn-primary">Edit Config</a>
        </div>

        <!-- Display success message if present -->
        <?php if ($success) : ?>
            <div class="alert alert-success mt-3" role="alert">
                Configuration updated successfully!
            </div>
        <?php endif; ?>

        <!-- Display error message if there is an issue with the config.php file -->
        <?php if (!empty($errorMessage)) : ?>
            <div class="alert alert-warning mt-3" role="alert">
                <?php echo $errorMessage; ?>
            </div>
        <?php endif; ?>

        <h1 class="mb-2">Bulk import Word files to Bookstack</h1>
        <p class="text-muted mb-4">Warning: All uploaded files will need to be removed manually from Bookstack!</p>
        <form method="POST" enctype="multipart/form-data">
            <div class="mb-3">
                <label for="book_id" class="form-label">Select a book:</label>
                <select name="book_id" id="book_id" class="form-select">
                    <?php foreach ($books as $book) : ?>
                        <option value="<?php echo $book['id']; ?>"><?php echo $book['name']; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3 form-check">
                <input type="checkbox" name="debugging" class="form-check-input" id="debugging">
                <label for="debugging" class="form-check-label">Debugging</label>
            </div>

            <div class="mb-3">
                <label for="docx_files" class="form-label">Select docx files:</label>
                <input type="file" name="docx_files[]" id="docx_files" class="form-control" accept=".docx" multiple>
            </div>

            <button type="submit" class="btn btn-primary">Import</button>
        </form>

        <!-- Display success message and button to open the book -->
        <?php if (isset($bookSlug)) : ?>
            <hr>
            <div class="mt-5">
                <p>Import successful!</p>
                <a href="<?php echo $bookstackURL . '/books/' . $bookSlug; ?>" target="_blank" class="btn btn-primary">Open book</a>
            </div>
        <?php endif; ?>

        <!-- Display JSON responses if debugging is enabled -->
        <?php if (!empty($jsonResponses) && isset($_POST['debugging'])) : ?>
            <div class="mt-5">
                <h2>JSON Responses</h2>
                <?php foreach ($jsonResponses as $response) : ?>
                    <pre><?php echo htmlspecialchars($response); ?></pre>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" integrity="sha384-geWF76RCwLtnZ8qwWowPQNguL3RmwHVBC9FhGdlKrxdiJJigb/j/68SIy3Te4Bkz" crossorigin="anonymous"></script>
</body>
</html>