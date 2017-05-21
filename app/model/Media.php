<?php

/**
*
*/
class Media
{

	private static $map=
	[
		'iqiyi.com'=>'Media_Iqiyi',
		'youku.com'=>'Media_Youku',
		'tudou.com'=>'Media_Tudou',
		'mgtv.com'=>'Media_Mgtv',
		'le.com'=>'Media_Letv',
		'v.qq.com'=>'Media_QQ',
		'yinyuetai.com'=>'Media_Yinyuetai',
		'douyu.com'=>'Media_Douyu',
		'bilibili.com'=>'Media_BiliBili',
		'huya.com'=>'Media_HuYa',
		'kuwo.cn'=>'Media_Kuwo',
	];

	public static function parse($url)
	{
		try
		{
			foreach(self::$map as $domain => $handler)
			{
				if(strpos($url,$domain))
				{
					return (new $handler($url))->info();
				}
			}
		}
		catch(Exception $e)
		{
			return ['code'=>$e->getCode(),'msg'=>$e->getMessage(),'e'=>$e];
		}
		return false;
	}


	public static function http($urls,$data=null,array $headers=[],$timeout=30,array $opt=[])
	{
		if(!is_array($urls))
		{
			$ch=curl_init($urls);
			curl_setopt_array($ch,array(CURLOPT_HTTPHEADER=>$headers,CURLOPT_FOLLOWLOCATION=>1,CURLOPT_SSL_VERIFYPEER=>0,CURLOPT_RETURNTRANSFER=>1,CURLOPT_TIMEOUT=>$timeout,CURLOPT_CONNECTTIMEOUT=>$timeout));
			$data&&curl_setopt_array($ch,array(CURLOPT_POST=>1,CURLOPT_POSTFIELDS=>$data));
			$opt&&curl_setopt_array($ch,$opt);
			$content=curl_exec($ch);
			curl_close($ch);
			return $content;
		}
		else
		{
			$mh=curl_multi_init();
			foreach ($urls as &$url)
			{
				$ch=curl_init($url);
				curl_setopt_array($ch,array(CURLOPT_HTTPHEADER=>$headers,CURLOPT_FOLLOWLOCATION=>1,CURLOPT_SSL_VERIFYPEER=>0,CURLOPT_RETURNTRANSFER=>1,CURLOPT_TIMEOUT=>$timeout,CURLOPT_CONNECTTIMEOUT=>$timeout));
				$data&&curl_setopt_array($ch,array(CURLOPT_POST=>1,CURLOPT_POSTFIELDS=>$data));
				curl_multi_add_handle($mh,$ch);
				$url=$ch;
			}
			$runing=null;
			do {
				curl_multi_exec($mh,$runing);
				curl_multi_select($mh);
			}
			while($runing>0);
			foreach($urls as &$ch)
			{
				$content=curl_multi_getcontent($ch);
				curl_multi_remove_handle($mh,$ch);
				curl_close($ch);
				$ch=$content;
			}
			curl_multi_close($mh);
			$content=count($urls)>1?$urls:reset($urls);
			return $content;
		}
	}

	public static function get($urls,$headers=['User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/45.0.2454.85 Safari/537.36'],$timeout=20)
	{
		return self::http($urls,null,$headers,$timeout);
	}

	public static function post($urls,$data=null,$headers=['User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/45.0.2454.85 Safari/537.36'],$timeout=40)
	{
		return self::http($urls,$data,$headers,$timeout);
	}

	public static function download($url,$save,$headers=['User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/45.0.2454.85 Safari/537.36'],$timeout=3600)
	{
		$fp =  fopen($save,'w+');
		$ch = curl_init($url);
		curl_setopt_array($ch, [CURLOPT_FILE=>$fp,CURLOPT_HTTPHEADER=>$headers,CURLOPT_FOLLOWLOCATION=>1,CURLOPT_SSL_VERIFYPEER=>0,CURLOPT_TIMEOUT=>$timeout,CURLOPT_CONNECTTIMEOUT=>$timeout]);
		curl_exec ($ch);
		curl_close ($ch);
		fclose($fp);
		return true;
	}

}


/**
*
*/
class Media_Iqiyi
{

	function __construct($url)
	{

	}
}


/**
 * https://github.com/EvilCult/Video-Downloader/blob/master/Module/youkuClass.py
 * 必须使用cookie
 */
class Media_Youku
{

	protected $vid;
	protected $infoUrl;

	function __construct($url)
	{
		if(preg_match('/id_([\w=]{9,18})\.html/',$url,$matches))
		{
			$vid=$matches[1];
			$this->vid=$vid;
			$this->infoUrl="http://play.youku.com/play/get.json?ct=12&vid={$vid}";
		}
	}

	function info($m3u8=0)
	{
		$data=['code'=>100];
		if($this->infoUrl)
		{
			$headers=['User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/45.0.2454.85 Safari/537.36'];
			$opt=[CURLOPT_REFERER=>'http://static.youku.com/v1.0.0595/v/swf/player_yknpsv.swf',CURLOPT_COOKIE=>base64_decode("Il9feXN1aWQ9Ii50aW1lKCkuIjsi")];
			$text=Media::http($this->infoUrl,null,$headers,30,$opt);
			if($text)
			{
				$arr=json_decode($text,true);
				$arr=$arr['data'];
				$data['title']=$arr['video']['title'];
				$data['img']=$arr['video']['logo'];
				$data['duration']=$arr['video']['seconds'];
				$desc=isset($arr['dvd']['point'])?array_column($arr['dvd']['point'],'title'):[];
				$data['desc']=$desc?implode('',$desc):$arr['video']['title'];
				$data['code']=0;
				$data['type']=$m3u8?'m3u8':'file';
				$data['stream']=$this->getStream($arr,$m3u8);
				return $data;
			}
			else
			{
				throw new Exception(sprintf('Get %s Error:No Content',$this->infoUrl),304);
			}
		}
		return $data;
	}

    // $hd "标清","高清","超清","1080"   ->  "0","1","2","3"
	function getStream($info,$m3u8=true)
	{
	    $kurl ="http://k.youku.com/player/getFlvPath/sid/";
		$ep=$info['security']['encrypt_string'];
		$oip=$info['security']['ip'];
		$temp=RC4::Encrypt('becaf9be',base64_decode($ep));
		list($sid,$token)=explode('_',$temp);
		$ts=time();
		$items=[];
		$stream_types=[];
		$tmp=array_column($info['stream'],null,'stream_type');
		$map=['flvhd'=>'flv','flv'=>'flv','3gphd'=>'mp4','mp4hd'=>'mp4','mp4'=>'mp4','mp4hd2'=>'flv','hd2'=>'flv','mp4hd3'=>'flv','hd3'=>'flv'];
		$hdmap=['flvhd'=>0,'flv'=>0,'3gphd'=>0,'mp4hd'=>1,'mp4'=>1,'mp4hd2'=>2,'hd2'=>2,'mp4hd3'=>3,'hd3'=>3];
		$types=['mp4hd'=>'mp4','mp4hd2'=>'hd2','mp4hd3'=>'hd3'];
		array_walk($map,function($v,$k)use(&$stream_types,&$tmp){if(isset($tmp[$k])){$stream_types[$k]=$tmp[$k];}});
		unset($tmp,$info);
		foreach ($stream_types as $type => $item)
		{
			$uitem=[];
			if($m3u8)
			{
				$ep=base64_encode(RC4::Encrypt('bf7e5f01',sprintf('%s_%s_%s',$sid,$item['stream_fileid'],$token)));
				$type=isset($types[$type])?$types[$type]:'mp4';
				$uitem=sprintf('http://pl.youku.com/playlist/m3u8?ctype=12&ev=1&keyframe=0&ep=%s&oip=%s&sid=%s&token=%s&vid=%s&type=%s&ts=%s',$ep,$oip,$sid,$token,$this->vid,$type,time());
			}
			else
			{
				$container=$map[$type];
				$hd=$hdmap[$type];
				foreach ($item['segs'] as $i => $s)
				{
					$ep=base64_encode(RC4::Encrypt('bf7e5f01',sprintf('%s_%s_%s',$sid,$s['fileid'],$token)));
					$hex=strtoupper(dechex($i));
	                $hex=strlen($hex)<2?'0'.$hex:$hex;
	                $str=http_build_query(['token'=>$token,'oip'=>$oip,'ep'=>$ep]);
		            $uitem[]=$kurl.$sid.sprintf('_%s/st/%s/fileid/%s?K=%s&hd=%s&myp=0&ts=%s&ypp=0&ymovie=1&ctype=12&ev=1&',$hex,$container,$s['fileid'],$s['key'],$hd,intval($s['total_milliseconds_audio']/1000)).$str;
				}
			}
			$items[]=$uitem;
		}
		while (count($items)>3) {array_shift($items);}
		list($normal,$high,$super)=$items;
		return ['normal'=>is_array($normal)?$normal:[$normal] ,'high'=>is_array($high)?$high:[$high],'super'=>is_array($super)?$super:[$super]];
	}


}


/**
*
*/
class Media_Tudou extends Media_Youku
{

	function __construct($url)
	{
		$text=Media::get($url);
		$text=explode('g-mini-top',$text);
		$text=$text[0];
		if(preg_match("/vcode:\s'([\w=]{9,18})'/",$text,$matches))
		{
			$vid=$matches[1];
			$this->vid=$vid;
			$this->infoUrl="http://play.youku.com/play/get.json?ct=12&vid={$vid}";
		}
	}
}


/**
*
*/
class Media_Mgtv
{
	private $vid;
	private $infoUrl;

	function __construct($url)
	{
		if(preg_match('/\/(\d{7})\.html/',$url,$matches))
		{
			$vid=$matches[1];
			$this->vid=$vid;
			$this->infoUrl="http://v.api.mgtv.com/player/video?video_id={$vid}";
		}
	}

	function info()
	{
		$data=['code'=>100];
		if($this->infoUrl)
		{
			$text=Media::get($this->infoUrl);
			if($text)
			{
				$arr=json_decode($text,true);
				if($arr['status']==200)
				{
					$arr=$arr['data'];
					$data['title']=$arr['info']['title'];
					$data['desc']=$arr['info']['desc'];
					$data['img']=$arr['info']['thumb'];
					$data['duration']=$arr['info']['duration'];
					$data['type']='m3u8';
					list($normal,$high,$super)=Media::get([$arr['stream'][0]['url'],$arr['stream'][1]['url'],$arr['stream'][2]['url']]);
					$normal=json_decode($normal,true);
					$high=json_decode($high,true);
					$super=json_decode($super,true);
					$data['normal']=[$normal['info']];
					$data['high']=[$high['info']];
					$data['super']=[$super['info']];
					$data['code']=0;
				}
				else
				{
					throw new Exception(sprintf('Get %s Error:No 200,%s',$this->infoUrl,$arr['msg']),20);
				}
			}
			else
			{
				throw new Exception(sprintf('Get %s Error:No Content',$this->infoUrl),21);
			}
		}
		return $data;
	}
}


/**
*
*/
class Media_Letv
{

	private $vid;
	private $infoUrl;

	function __construct($url)
	{
		if(preg_match('/\/(\d{6,8})\.html/',$url,$matches))
		{
			$vid=$matches[1];
			$this->vid=$vid;
			$tkey=$this->getTkey(time());
			$this->infoUrl=sprintf('http://api.le.com/mms/out/video/playJson?id=%d&&platid=1&splatid=101&format=1&tkey=%s&domain=www.le.com&dvtype=1000&accessyx=1&devid=1951BC8CE74A33CD0079D71D4FD1131BE5F111B8',$vid,$tkey);
		}
	}

	function info()
	{
		$data=['code'=>100];
		if($this->infoUrl)
		{
			$text=Media::get($this->infoUrl);
			if($text)
			{
				$arr=json_decode($text,true);
				$playurl=$arr['playurl'];
				$data['title']=$playurl['title'];
				$data['img']=$playurl['pic'];
				$data['duration']=$playurl['duration'];
				$data['desc']=implode(' ',array_values($arr['point']['hot']));
				$stream_id='720p';
				$url=sprintf('%s%s&ctv=pc&m3v=1&termid=1&format=1&hwtype=un&ostype=Linux&tag=letv&sign=letv&expect=3&tn=%s&pay=0&iscpn=f9051&rateid=%s',$playurl["domain"][0],$playurl["dispatch"][$stream_id][0],rand(1,100),$stream_id);
				$text=Media::get($url);
				if($text)
				{
					$data['code']=0;
					$arr=json_decode($text,true);
					$info=$this->decrypt(Media::get($arr['location']));
					var_dump($data);die;
				}
				else
				{
					throw new Exception(sprintf('Get %s Error:No Content',$url),402);
				}
			}
			else
			{
				throw new Exception(sprintf('Get %s Error:No Content',$this->infoUrl),401);
			}
		}
		return $data;
	}

	private  function getTkey($stime)
	{
		$key=773625421;
		$value=$this->getLetvKey($stime,$key%13);
		$value=$value^$key;
		$value=$this->getLetvKey($value,$key%17);
		return$value;
	}

	private  function getLetvKey($value,$key)
	{
		$i=0;
		while($i<$key)
		{
			$value=2147483647&$value>>1|($value&1)<<31;
			++$i;
		}
		return $value;
	}

	private  function decrypt($data)
	{
		$loc2=substr($data,5);
		unset($data);
		$length=strlen($loc2);
		$loc4=array_fill(0,2*$length,0);
		for($i=0;$i<$length;$i++)
		{
			$loc4[2*$i]=ord($loc2[$i]) >> 4;
			$loc4[2*$i+1]=ord($loc2[$i]) & 15;
		}
		unset($loc2,$i);
		$loc6=array_merge(array_slice($loc4,count($loc4)-11),array_slice($loc4,0,count($loc4)-11));
		unset($loc4);
		$loc7=array_fill(0,$length,0);
		foreach ($loc7 as $i => &$item)
		{
			$item=chr(($loc6[2*$i] << 4) +$loc6[2*$i+1]);
		}
		unset($loc6);
		$loc7=implode('',$loc7);
		return $loc7;
	}

	function getStream()
	{

	}

}


/**
* 需要多次网络请求耗时较大
* https://github.com/soimort/you-get/blob/develop/src/you_get/extractors/qq.py
*/
class Media_QQ
{
	private $infoUrl;
	private $url;
	private $vid;
	function __construct($url)
	{
		if(preg_match('/qq\.com\/x\/cover\/\w{14,16}\/(\w{10,12})/',$url,$matches)||preg_match('/qq\.com\/x\/cover\/\w{12,16}\.html\?vid=(\w{8,12})/',$url,$matches))
		{
			$this->vid=$matches[1];
			$this->infoUrl='http://vv.video.qq.com/getinfo?otype=json&appver=3%2E2%2E19%2E333&platform=11&defnpayver=1&vid='.$matches[1];
			$this->url=$url;
		}
	}

	function info()
	{
		if($this->infoUrl)
		{
			$data=['code'=>100];
			list($text,$infoText)=Media::get([$this->infoUrl,$this->url]);
			if($text)
			{
				$arr=explode('rel="canonical"',$infoText);
				if(preg_match('/duration"\scontent="(\d+)"[\s\S]+og:title"\scontent="(.+)"[\s\S]+og:description"\scontent="(.+)"[\s\S]+og:image"\scontent="(.+)"/',$arr[0],$matches))
				{
					$data['title']=$matches[2];
					$data['desc']=$matches[3];
					$data['img']=$matches[4];
					$data['duration']=$matches[1];
				}
				$text=trim(str_replace('QZOutputJson=','',$text),';');
				$arr=json_decode($text,true);
				if($arr)
				{
					$parts_formats=$arr['fl']['fi'];
					$parts_vid = $arr['vl']['vi'][0]['vid'];
					$parts_ti = $arr['vl']['vi'][0]['ti'];
					$parts_prefix = $arr['vl']['vi'][0]['ul']['ui'][0]['url'];
					$map=array_column($parts_formats,null,'name');
					$tmp=[];
					foreach (['fhd','shd','hd','sd'] as $k)
					{
						if(isset($map[$k]))
						{
							$tmp[]=$map[$k];
						}
						if(count($tmp)>=3)
						{
							break;
						}
					}
					$parts_formats=array_reverse($tmp);
					unset($tmp,$map,$text);
					foreach ($parts_formats as &$part)
					{
						$urls=[];
						foreach (range(1,15) as $i)
						{
							$filename=$this->vid.'.p'.($part['id']%1000).'.'.$i.'.mp4';
		                    $key_api=sprintf('http://vv.video.qq.com/getkey?otype=json&platform=11&format=%s&vid=%s&filename=%s',$part['id'],$parts_vid,$filename);
							$part_info=Media::get($key_api);
							$part_info=json_decode(trim(str_replace('QZOutputJson=','',$part_info),';') ,true);
		                    if(isset($part_info['key']))
		                    {
		                    	$vkey=$part_info['key'];
			                    $url=sprintf('%s/%s?vkey=%s',$parts_prefix,$filename,$vkey);
			                    $urls[]=$url;
		                    }
		                    else
		                    {
		                    	break;
		                    }
						}
						$part=$urls;
					}
					list($normal,$high,$super)=$parts_formats;
					$data['code']=0;
					$data['type']='file';
					$data['stream']=['normal'=>$normal,'high'=>$high,'super'=>$super];
					return $data;
				}
				else
				{
					throw new Exception("Json Decode Error",2);
				}
			}
			else
			{
				throw new Exception("Get Url Content Error",1);
			}
		}
	}


}


/**
*
*/
class Media_Yinyuetai
{

	private $vid;
	private $infoUrl;

	private $title;
	private $desc;
	private $img;

	function __construct($url)
	{
		if(preg_match('/\/video\/(\d{6,8})/',$url,$matches))
		{
			$vid=$matches[1];
			$this->vid=$vid;
			$this->infoUrl="http://www.yinyuetai.com/api/info/get-video-urls?videoId={$vid}";
			$text=explode('X-UA-Compatible',Media::get($url));
			$text=$text[0];
			$pattern='/property="og:title"[\s]+content="([^"]*)"\/>[\S\s]*?property="og:image" content="([^"]*)"\/>[\S\s]*?\/video\/\d{6,8}"\/>[\S\s]*?content="(.+)"\/>/';
			if(preg_match($pattern,$text,$matches))
			{
				$this->title=$matches[1];
				$this->img=$matches[2];
				$this->desc=$matches[3];
			}
		}
	}

	function info()
	{
		$data=['code'=>100];
		if($this->infoUrl)
		{
			$text=Media::get($this->infoUrl);
			if($text)
			{
				$arr=json_decode($text,true);
				$data['duration']=$arr['duration'];
				$data['normal']=[$arr['hcVideoUrl']];
				$data['high']=[$arr['hdVideoUrl']];
				$data['super']=[$arr['heVideoUrl']];
				$data['title']=$this->title;
				$data['desc']=$this->desc;
				$data['code']=0;
				$data['type']='file';
			}
			else
			{
				throw new Exception(sprintf('Get %s Error:No Content',$this->infoUrl),31);
			}
		}
		return $data;
	}
}



/**
* https://github.com/soimort/you-get/blob/develop/src/you_get/extractors/douyutv.py
* https://github.com/steven7851/livestreamer/blob/develop/src/livestreamer/plugins/douyutv.py
* API文档 http://n1.other.hjfile.cn/st/2016/06/28/56df7b699702b05cd629b390cfaf2827.pdf
* http://www.douyu.com/api/RoomApi/live?limit=5
* http://www.douyu.com/api/RoomApi/game/
*/
class Media_Douyu
{

	private $infoUrl;
	const KEY='9TUk5fjjUjg9qIMH3sdnh';
	function __construct($url)
	{
		if(preg_match('/\.com\/(\w{5,12})/',$url,$matches))
		{
			$channel=$matches[1];
			$this->infoUrl=sprintf('https://m.douyu.com/html5/live?roomId=%s',$channel);
		}
	}

	function info()
	{
		$data=['code'=>100];
		if($this->infoUrl)
		{
			$text=Media::get($this->infoUrl);
			if($text)
			{
				$tt=time();
				$arr=json_decode($text,true);
				$data['img']=$arr['data']['room_src'];
				$data['title']=$arr['data']['room_name'];
				$data['online']=$arr['data']['online'];
				$data['nickname']=$arr['data']['nickname'];
				$data['avatar']=$arr['data']['avatar'];
				$roomId=$arr['data']['room_id'];
				$data['code']=0;
				if($arr['data']['show_status']==1)
				{
					$sign=md5(sprintf('lapi/live/thirdPart/getPlay/%s?aid=pcclient&rate=0&time=%s%s',$roomId,$tt,self::KEY));
					$url=sprintf('http://coapi.douyucdn.cn/lapi/live/thirdPart/getPlay/%s?rate=0',$roomId);
					$headers=["auth:{$sign}","time:{$tt}","aid:pcclient"];
					$text=Media::get($url,$headers);
					if($text)
					{
						$arr=json_decode($text,true);
						if($arr)
						{
							unset($text);
							if($arr['error']==0)
							{
								$data['hls_url']=$arr['data']['hls_url'];
								$data['live_url']=$arr['data']['live_url'];
							}
							else
							{
								$data['hls_url']=null;//主播不在线
								$data['live_url']=null;//主播不在线
							}
						}
						else
						{
							throw new Exception(sprintf('%s Json Error:%s',$url,$text),406);
						}
					}
					else
					{
						throw new Exception(sprintf('Get %s Error:No Content',$url),405);
					}
				}
			}
			else
			{
				throw new Exception(sprintf('Get %s Error:No Content',$this->infoUrl),404);
			}
		}
		return $data;
	}

}

/**
*
*/
class Media_BiliBili
{
	private $infoUrl;

	private $title;
	private $img;

	function __construct($url)
	{
		$text=Media::get($url);
		if($text)
		{
			$arr=explode('</head>',$text);
			$pattern='/og:title"\scontent="(.+)"[\s\S]+og:image"\s+content="(.+)[\s\S]+ROOMID\s+=\s+(\d+);/';
			if(preg_match($pattern,$arr[0],$matches))
			{
				$this->title=$matches[1];
				$this->img=$matches[2];
				$roomId=$matches[3];
				$this->infoUrl=sprintf('http://live.bilibili.com/api/playurl?player=1&cid=%d&quality=0',$matches[3]);
			}
		}
		else
		{

		}
	}

	private function stream($id)
	{
		$this->infoUrl=sprintf('http://live.bilibili.com/api/playurl?player=1&cid=%d&quality=0',$id);
	}

	private function info()
	{
		if($this->infoUrl)
		{
			$text=Media::get($this->infoUrl);
			if($text)
			{
				$xml=simplexml_load_string($text);
				$url=(string)($xml->durl->url[0]);
				$data=['title'=>$this->title,'img'=>$this->img,'stream'=>$url];
				return $data;
			}
			else
			{
				throw new Exception(sprintf("Get %s Error",$this->infoUrl), 1);
			}
		}
	}

	function all()
	{
		return json_decode(Media::get('http://live.bilibili.com/index/refresh?area=all'));
	}


}

/**
* 虎牙视频
*/
class Media_HuYa
{
	private $infoUrl;
	private $vid;
	private $id;
	function __construct($url)
	{
		if(preg_match('/v\.huya\.com\/play\/(\d{5,9})/',$url,$matches))
		{
			$this->vid=$matches[1];
			$this->infoUrl='http://playapi.v.duowan.com/index.php?r=play%2Fvideo&partner=&vid='.$this->vid;
		}
		else if(preg_match('/www\.huya\.com\/(\d{7,12})/',$url,$matches))
		{
			$this->vid=$matches[1];
		}

	}

	function info()
	{
		if($this->infoUrl)
		{
			$text=Media::get($this->infoUrl);
			if($text)
			{
				$arr=json_decode($text,true);
				if($arr)
				{
					if($arr['code']===1)
					{
						return $arr['result'];
					}
					else
					{
						throw new Exception("Remote Data Error",3);
					}
				}
				else
				{
					throw new Exception("Json Decode Error",2);
				}
			}
			else
			{
				throw new Exception("Get Url Content Error",1);
			}
		}
	}
}

/**
*
*/
class Media_Kuwo
{
	private $url;

	function __construct($url)
	{
		$this->url=$url;
	}

	function info()
	{
		$data=['code'=>100];


		return $data;

	}

	function getKuWoInfo($search,$index=0,$page=1,$limit=20)
	{
		if(is_numeric($search))
		{
			$url="http://antiserver.kuwo.cn/anti.s?response=url&type=convert_url&format=mp4|mp3|aac&rid=MUSIC_{$search}";
			return Media::get($url);
		}
		else if(substr($search,0,4)=='http' && preg_match('/\/(\d{6,7})\//',$search,$matches))
		{
			$id=$matches[1];
			return Media::get("http://antiserver.kuwo.cn/anti.s?response=url&type=convert_url&format=mp4|mp3|aac&rid=MUSIC_{$id}");
		}
		$url="http://search.kuwo.cn/r.s?all={$search}&rformat=json&encoding=utf8&pn={$page}&rn={$limit}";
		$json=json_decode(str_replace("'",'"',Media::get($url)),true);
		if($json)
		{
			if(is_int($index))
			{
				$id=isset($json['abslist'][$index]['MUSICRID'])?$json['abslist'][$index]['MUSICRID']:null;
				if($id)
				{
					return Media::get("http://antiserver.kuwo.cn/anti.s?response=url&type=convert_url&format=mp4|mp3|aac&rid={$id}");
				}
				return false;
			}
			return $json;
		}
		return false;

	}

}

/**
* 天天动听
*/
class Media_Ttpod
{
	private $infoUrl;

	private $lrcUrl='http://lp.music.ttpod.com/lrc/down?title=song_name';
	private $imgUrl='http://lp.music.ttpod.com/pic/down?artist=singer_name';

	function __construct($search,$page=1,$limit=20)
	{
		$this->infoUrl=sprintf('http://search.dongting.com/song/search?uid=A0000040EA25DF&hid=9144089111125873&from=android&net=0&api_version=1.0&q=%s&page=%d&size=%d',$search,$page,$limit);
	}

	function info()
	{
		if($this->infoUrl)
		{
			$text=Media::get($this->infoUrl);
			return json_decode($text,true);
		}
		return false;
	}
}


/**
*
*/
class Media_M163
{

	function __construct()
	{

	}
}


class RC4
{
   public static function Encrypt($a,$b)
   {
		for($i=0,$c=[];$i<256;$i++)$c[$i]=$i;
		for($i=0,$d=0,$e=0,$g=strlen($a);$i<256;$i++)
		{
			$d=($d+$c[$i]+ord($a[$i%$g]))%256;
			$e=$c[$i];
			$c[$i]=$c[$d];
			$c[$d]=$e;
		}
		for($y=0,$i=0,$d=0,$f=null;$y<strlen($b);$y++)
		{
			$i=($i+1)%256;
			$d=($d+$c[$i])%256;
			$e=$c[$i];
			$c[$i]=$c[$d];
			$c[$d]=$e;
			$f.=chr(ord($b[$y])^$c[($c[$i]+$c[$d])%256]);
		}
		return $f;
   }
   public static function Decrypt($a,$b)
   {
		return RC4::Encrypt($a,$b);
   }
}


