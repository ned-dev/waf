In order to activate Web Application Firewall you must add the following setting to:
.htaccess file, php.ini or web server <virtualhost> config:
php_value auto_prepend_file 'path/to/waf/init.php'

-------------------------------------------------------------------------------------------
.htaccess example:
-------------------------------------------------------------------------------------------

# Enable Web Application Firewall
php_value auto_prepend_file 'path/to/waf/init.php'

# Makes sure php globals are always off
php_value register_globals off

# Hides the web server version number, and other sensitive information
ServerSignature Off

-------------------------------------------------------------------------------------------