#!/bin/bash

# Get database info from config file (this is a simple parser and might need adjustments)
DB_USER=$(grep -oP "(?<='username' => ')[^']*" /home/eugene/Task/DarasaLink/application/config/database.php)
DB_PASS=$(grep -oP "(?<='password' => ')[^']*" /home/eugene/Task/DarasaLink/application/config/database.php)
DB_NAME=$(grep -oP "(?<='database' => ')[^']*" /home/eugene/Task/DarasaLink/application/config/database.php)

# Import the default data
echo "Importing default data into database '$DB_NAME'..."
mysql -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" < /home/eugene/Task/DarasaLink/default_data.sql

# Make the script executable
chmod +x /home/eugene/Task/DarasaLink/import_default_data.sh

echo "Default data imported successfully!"
echo "Now the application should have students, fee types, and fee records for the M-Pesa integration to work."