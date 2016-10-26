<?php

/**
*





*/
class api
{

	private $uri;
	function __construct(array $route)
	{
		$this->uri=implode('/',$route);
	}


	function index()
	{
		var_dump(app::cost());
	}



	function channel($cid=0,$type=0)
	{
		$channel=with('Channel');
		$list=with('Lists');
		$page=request::get('page',1);
		$limit=request::get('limit',20);
		if($cid&&$type)
		{
			$data=$list->listPage(['cid'=>$cid,'type'=>$type],$page,$limit);
		}
		else if($cid)
		{
			$data=$list->listPage(['cid'=>$cid],$page,$limit);
		}
		else if($type)
		{
			$data=$list->listPage(['type'=>$type],$page,$limit);
		}
		else
		{
			$data=$channel->all();
		}
		self::end($data);
	}


	function video($sid=0)
	{
		if($sid)
		{
			$info=with('Lists')->info($sid);
			if($info)
			{
				$list=with('Resource')->series($sid);
				return self::end(['code'=>0,'info'=>$info,'list'=>$list]);
			}
			else
			{
				return self::end(['code'=>205,'msg'=>'not found']);
			}
		}
		else
		{
			return self::end(['code'=>204,'msg'=>'error param']);
		}
	}

	function audio()
	{

	}

	function resource($rid=0)
	{
		$info=with('Resource')->info($rid);
		if($info)
		{
			self::end(['code'=>0,'info'=>$info]);
		}
		else
		{
			self::end(['code'=>404,'msg'=>'not found']);
		}
	}

	function play($rid=0)
	{
		if($rid)
		{
			if(is_numeric($rid))
			{
				try
				{
					$play=with('Resource')->play($rid);
					return self::end($play);
				}
				catch(Exception $e)
				{
					return self::end(['code'=>$e->getCode(),'msg'=>$e->getMessage()]);
				}
			}
			else if(preg_match('/([a-z0-9]{13})\.m3u8$/',$rid,$matches))
			{
				if($data=M3u8::get($matches[1]))
				{
					header('Content-Encoding:deflate');
					exit($data);
				}
				else
				{
					return self::end(['code'=>404,'msg'=>'error m3u8 id']);
				}
			}
			else
			{
				return self::end(['code'=>501,'msg'=>'error param']);
			}
		}
		else
		{
			return self::end(['code'=>500,'msg'=>'error param']);
		}
	}

	private static function end(array $data=[],closure $cb=null)
	{
		return json($data);
	}

	function __destruct()
	{
		Log::add($this->uri,$_SERVER['REQUEST_TIME'],app::cost('time')*1000,request::ip());
	}



}
