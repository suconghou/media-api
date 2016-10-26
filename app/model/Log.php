<?php


/**
*
*/
class Log extends Database
{

	const table='apilog';

	static function add($uri,$request,$time,$ip)
	{
		return self::insert(['uri'=>$uri,'request'=>$request,'time'=>$time,'ip'=>$ip]);
	}

}
