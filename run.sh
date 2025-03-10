#!/usr/bin/bash

echo "Setting up the project environment..."

# Copy Apache configuration files
sudo cp -f 001-sample.conf /etc/apache2/sites-available/

# Enable the site
sudo a2ensite 001-sample.conf

# Restart Apache
echo "Restarting Apache server..."
sudo service apache2 restart

# Navigate to WebServer directory
cd Webserver

# Build frontend (if applicable)
if [ -d "Frontend" ]; then
    echo "Setting up frontend..."
    # No npm build command since it's just HTML/CSS
    sudo cp -r Frontend /var/www/sample/
fi

# Move backend to web server directory
if [ -d "Backend" ]; then
    echo "Setting up backend..."
    sudo cp -r Backend /var/www/sample/
fi

# Start RabbitMQ server process
echo "Starting RabbitMQ server..."
cd ../Database
php db_processor.php &

# Open the website in the default browser
echo "Opening homepage..."
xdg-open http://www.sample.com

echo "Setup complete!"
