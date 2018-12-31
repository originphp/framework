# Install

## Create the Virtual Host on Apache

<VirtualHost *:443>
   ServerAdmin admin@origin.dev
    ServerName origin.dev
    DocumentRoot /var/www/ats
    ErrorLog ${APACHE_LOG_DIR}/error.log
    CustomLog ${APACHE_LOG_DIR}/access.log combined  

<Directory /var/www/ats>
    Options Indexes FollowSymLinks
    AllowOverride All
    Require all granted
</Directory>

    SSLEngine on
    SSLCertificateFile      /etc/ssl/certs/origin.dev.crt
    SSLCertificateKeyFile /etc/ssl/private/origin.dev.key
</VirtualHost>
