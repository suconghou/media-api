<?php

/**
*
*/
class home
{

	function __construct()
	{

	}

	function index()
	{
		var_dump(Media::parse("https://www.douyu.com/833314"));
	}


	function index2()
	{
		$arr=json_decode(Media::get('http://live.bilibili.com/index/refresh?area=all'),true);
		$items=[];
		foreach ($arr['data'] as $item)
		{
			$items=array_merge($items,$item['onlineList']);
		}
		unset($arr);
		$item=$items[rand(0,count($items)-1)];
		$url=sprintf('http://live.bilibili.com/api/playurl?player=1&cid=%d&quality=0',$item['roomid']);
		$text=Media::get($url);
		$xml=simplexml_load_string($text);
		$url=(string)($xml->durl->url[0]);
		echo "<video width='100%' height='95%' autoplay controls src='{$url}'></video>";
	}


}
