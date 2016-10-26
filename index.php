<?php
/*************************************系统配置区*************************************/
define('DEBUG',2);
define('ROOT',__DIR__.DIRECTORY_SEPARATOR);
define('APP_PATH',ROOT.'app'.DIRECTORY_SEPARATOR);
define('VAR_PATH',ROOT.'var'.DIRECTORY_SEPARATOR);
define('LIB_PATH','/data/git/mvc/app/system/');
define('VIEW_PATH',APP_PATH.'view'.DIRECTORY_SEPARATOR);
define('MODEL_PATH',APP_PATH.'model'.DIRECTORY_SEPARATOR);
define('CONTROLLER_PATH',APP_PATH.'controller'.DIRECTORY_SEPARATOR);
require LIB_PATH.'core.php';

if(DEBUG)
{
	define('DB_DSN','mysql:host=127.0.0.1;port=3306;dbname=mediacenter;charset=utf8');
	define('DB_USER','work');
	define('DB_PASS','123456');
}
else
{
	define('DB_DSN','mysql:unix_socket=/var/run/mysql.sock;dbname=blog;charset=utf8');
	define('DB_USER','root');
	define('DB_PASS','root');
}



/*************************************应用程序配置区*************************************/



/*************************************应用程序配置区*************************************/

//配置完,可以启动啦!
app::start();

