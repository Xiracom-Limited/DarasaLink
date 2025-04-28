#!/bin/bash

# Ensure the error directories exist
mkdir -p /home/eugene/Task/DarasaLink/application/views/errors/html
mkdir -p /home/eugene/Task/DarasaLink/application/views/errors/cli

# Give execute permissions to the script
chmod +x /home/eugene/Task/DarasaLink/run.sh

# Run PHP with suppressed deprecation warnings
cd /home/eugene/Task/DarasaLink
php -d error_reporting=E_ALL^E_DEPRECATED^E_STRICT -S localhost:8000 server.php