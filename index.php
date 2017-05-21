<?php
/*************************************系统配置区*************************************/
define('DEBUG',getenv('debug'));
define('ROOT',__DIR__.DIRECTORY_SEPARATOR);
define('APP_PATH',ROOT.'app'.DIRECTORY_SEPARATOR);
define('VAR_PATH',ROOT.'var'.DIRECTORY_SEPARATOR);
define('LIB_PATH',DEBUG?'/data/git/mvc/app/system/':'/home/xsucongh/');
define('VIEW_PATH',APP_PATH.'view'.DIRECTORY_SEPARATOR);
define('MODEL_PATH',APP_PATH.'model'.DIRECTORY_SEPARATOR);
define('CONTROLLER_PATH',APP_PATH.'controller'.DIRECTORY_SEPARATOR);
require LIB_PATH.'core.php';

if(DEBUG)
{
	define('DB_DSN','mysql:host=db.suconghou.cn;port=3306;dbname=xsucongh_test;charset=utf8');
	define('DB_USER','xsucongh_test');
	define('DB_PASS','123456');
}
else
{
	define('DB_DSN','mysql:host=127.0.0.1;port=3306;dbname=xsucongh_media;charset=utf8');
	define('DB_USER','xsucongh_media');
	define('DB_PASS','123456');
}

$cfg=[];


/*************************************应用程序配置区*************************************/



/*************************************应用程序配置区*************************************/

//配置完,可以启动啦!
app::start($cfg);

