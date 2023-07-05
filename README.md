# bookstack-bulkimport
This is a small PHP application with a web frontend to bulk import docx files into Bookstack
The docx files are converted the phpoffice/phpword library into raw HTML, after which the HTML formatted content is being uploaded to a new page in Bookstack using the native Bookstack API.

## Installation

Clone the files onto your local/hosted webserver, something like XAMPP will work fine.
Run `composer install` to get the necessary libraries and you're ready to go.

## Running the application
Go to the web frontend of this application (e.g. http://localhost/bookstack_import)
Click the 'Edit config' button in the upper right corner and fill in the URL, token and key. The API endpoints shouldn't need any changing.
After saving you'll return to the homepage to start the upload.

**Upload proces:**

 1. Select a book to upload the docx files into as a new page (the page will create a dropdown with your current Bookstack books)
 2. Select one or more docx files to upload
 3. Check the debug option to see the API JSON response from the Bookstack server
 4. After uploading, you can click the 'Open book' button to view the uploaded pages in the selected book

