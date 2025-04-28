# Fixing Error Templates and Running Your Application

The error messages you're seeing occur because CodeIgniter is looking for error template files that don't exist in your application. Let's fix this and get your application running properly.

## Step 1: Create Error Template Directories and Files

I've created all the necessary error template files for you:

1. `/home/eugene/Task/DarasaLink/application/views/errors/html/error_php.php`
2. `/home/eugene/Task/DarasaLink/application/views/errors/html/error_general.php`
3. `/home/eugene/Task/DarasaLink/application/views/errors/html/error_exception.php`
4. `/home/eugene/Task/DarasaLink/application/views/errors/html/error_db.php`

## Step 2: Create CLI Error Templates

For completeness, you should also create CLI error templates:

```bash
# Copy the HTML error templates to the CLI directory
cp /home/eugene/Task/DarasaLink/application/views/errors/html/error_php.php /home/eugene/Task/DarasaLink/application/views/errors/cli/
cp /home/eugene/Task/DarasaLink/application/views/errors/html/error_general.php /home/eugene/Task/DarasaLink/application/views/errors/cli/
cp /home/eugene/Task/DarasaLink/application/views/errors/html/error_exception.php /home/eugene/Task/DarasaLink/application/views/errors/cli/
cp /home/eugene/Task/DarasaLink/application/views/errors/html/error_db.php /home/eugene/Task/DarasaLink/application/views/errors/cli/
```

## Step 3: Fix the E_STRICT Deprecated Warning

The first error in your list (`Constant E_STRICT is deprecated`) is related to PHP 8.0+. To fix this, you can modify the Exceptions.php file, but since it's part of the system folder, it's better to use a different approach:

1. Create a custom error handler in your application that overrides the default one
2. Or run PHP with error reporting that excludes E_DEPRECATED warnings:

```bash
cd /home/eugene/Task/DarasaLink
php -d error_reporting=E_ALL^E_DEPRECATED -S localhost:8000 server.php
```

## Step 4: Run Your Application Again

Now run the PHP server again:

```bash
cd /home/eugene/Task/DarasaLink
php -S localhost:8000 server.php
```

This should resolve the error template issues you were experiencing.

## Step 5: Check for Controller Issues

If you're still experiencing problems, it may be related to your Mpesa_controller.php file. Make sure it has:

1. A proper class definition extending CI_Controller
2. A default method (usually called "index")

Example:

```php
<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Mpesa_controller extends CI_Controller {
    public function index() {
        echo "Hello World!";
        // or $this->load->view('some_view');
    }
}
```