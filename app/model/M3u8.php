<?php

/**
*
*/
class M3u8 extends Database
{

	const table='m3u8';

	function __construct()
	{

	}

	function get($k)
	{
		return self::findVar(['k'=>$k,'t >'=>time()],null,'v');
	}

	function set($k,$v,$t=600)
	{
		return self::replace(['k'=>$k,'v'=>$v,'t'=>time()+$t]);
	}

	function clean()
	{
		return self::delete(['t <'=>time()]);
	}

}
