<?php

/**
*
*/
class Resource extends Database
{

	const table='resource';

	function __construct()
	{

	}

	function info($rid)
	{
		return self::findOne(['id'=>$rid]);
	}

	function add($item)
	{
		return self::insert($item);
	}

	function hot($rid)
	{
		return self::update(['id'=>$rid],['!hot'=>'hot+1']);
	}

	function series($sid)
	{
		return self::find(['sid'=>$sid]);
	}

	/**
	 * 获取playinfo,没有则现场解析,缓存策略,失效时间有所不同
	 */
	function play($rid)
	{
		$key="play-{$rid}";
		$info=M::get($key);
		if($info)
		{
			return $info;
		}
		$info=$this->info($rid);
		if($info)
		{
			$info=Media::parse($info['origin']);
			if($info['code']===0)
			{
				M::set($key,$info,20);
				self::hot($rid);
				return $info;
			}
			throw $info['e'];
		}
		throw new Exception("resource not found",404);
	}

}
