#!/bin/bash

# Create the correct libraries directory if it doesn't exist
mkdir -p /home/eugene/Task/DarasaLink/application/libraries

# Copy the Mpesa_lib.php file to the correct location
cp /home/eugene/Task/DarasaLink/application/libralies/Mpesa_lib.php /home/eugene/Task/DarasaLink/application/libraries/

# Make the script executable
chmod +x /home/eugene/Task/DarasaLink/fix_libraries.sh

echo "Mpesa_lib.php has been copied to the correct 'libraries' directory."