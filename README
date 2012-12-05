ispCP driver for Roundcube password plugin
=============

Introduction
-------------
This is a driver for the Roundcube password-plugin. It works with installations of ispCP version 1.0.7 and Roundcube 0.8. There are other drivers for some of the older versions (both ispCP and Roundcube).

Installation
-------------
Download and copy the the file ispcp.php into "[RC]/plugins/password/drivers".
In the file "[RC]/plugins/password/config.inc.php" add the following lines
```php
// ispCP Driver options
$rcmail_config['ispcp_db_pass_key'] = 'ispcp_db_pass_key';
$rcmail_config['ispcp_db_pass_iv'] = 'ispcp_db_pass_iv'; 
```
where 'ispcp_db_pass_key' and 'ispcp_db_pass_iv' are special strings from your ispCP installation. There usally placed in "/var/www/ispcp/gui/include/ispcp-db-keys.php".

You also have to change the following two lines
```php
$rcmail_config['password_driver'] = 'ispcp';                                    // should be Line 7
$rcmail_config['password_db_dsn'] = 'mysql://user:password@host/ispcpdatabase';    // should be Line 33
```
where of course 'user', 'password', 'host' and 'ispcpdatabase' refer to your database structure. In most cases the whole database connection string will look something like this:
'mysql://roundcube:password@localhost/ispcp'

Make sure the user has permission to read and write the 'mail_users'-table.

Notes
-------------
This driver relys on a running ispcp daemon. Also there are some users reporting the driver is not working at all. So you should take it as somekind of an beta-version.


License
-------------
Should be somthing like the ispCP and roundcube license, because most code is copy and paste.


Credits
-------------
Previous versions were made by Sascha alias TheCry and Aleksander 'A.L.E.C' Machniak <alec@alec.pl>.
