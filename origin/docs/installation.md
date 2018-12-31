# Install

## Create the Virtual Host on Apache

````xml
<VirtualHost *:443>
    ServerAdmin admin@example.com
    ServerName example.com
    DocumentRoot /var/www/project
    ErrorLog ${APACHE_LOG_DIR}/error.log
    CustomLog ${APACHE_LOG_DIR}/access.log combined  

<Directory /var/www/project>
    Options Indexes FollowSymLinks
    AllowOverride All
    Require all granted
</Directory>

    SSLEngine on
    SSLCertificateFile  /etc/ssl/certs/example.com.crt
    SSLCertificateKeyFile /etc/ssl/private/example.com.key
</VirtualHost>
````