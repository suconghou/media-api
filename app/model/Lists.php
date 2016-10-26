<?php

/**
*
*/
class Lists extends Database
{

	const table='list';

	function __construct()
	{

	}

	function listPage(array $where,$page=1,$limit=20)
	{
		return self::findPage($where,null,'id,cid,sid,type,name,img',$page,$limit);
	}

	function info($id)
	{
		return self::findOne(['id'=>$id]);
	}

}
