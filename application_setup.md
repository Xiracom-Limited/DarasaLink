# Application Setup Guide

## Prerequisites
- PHP (likely version 7.x or higher)
- Web server (Apache/Nginx)
- MySQL/MariaDB database

## Steps to Run the Application

### 1. Web Server Configuration

#### For Apache:
Create a virtual host configuration in `/etc/apache2/sites-available/darasalink.conf`:

```apache
<VirtualHost *:80>
    ServerName darasalink.local
    DocumentRoot /home/eugene/Task/DarasaLink
    
    <Directory /home/eugene/Task/DarasaLink>
        Options Indexes FollowSymLinks MultiViews
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/darasalink_error.log
    CustomLog ${APACHE_LOG_DIR}/darasalink_access.log combined
</VirtualHost>
```

Enable the site and restart Apache:
```bash
sudo a2ensite darasalink.conf
sudo systemctl restart apache2
```

#### For Nginx:
Create a server block in `/etc/nginx/sites-available/darasalink`:

```nginx
server {
    listen 80;
    server_name darasalink.local;
    root /home/eugene/Task/DarasaLink;
    
    index index.php index.html;
    
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;  # Adjust PHP version
    }
}
```

Enable the site and restart Nginx:
```bash
sudo ln -s /etc/nginx/sites-available/darasalink /etc/nginx/sites-enabled/
sudo systemctl restart nginx
```

### 2. Add local domain to hosts file
```bash
sudo echo "127.0.0.1 darasalink.local" >> /etc/hosts
```

### 3. Check application permissions
```bash
sudo chown -R www-data:www-data /home/eugene/Task/DarasaLink
sudo chmod -R 755 /home/eugene/Task/DarasaLink
```

### 4. Access the application
Open your browser and navigate to: http://darasalink.local