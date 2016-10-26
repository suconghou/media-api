<?php

/**
*
*/
class Channel extends Database
{

	const table='channel';

	function __construct()
	{

	}

	function all()
	{
		return self::find();
	}


}
