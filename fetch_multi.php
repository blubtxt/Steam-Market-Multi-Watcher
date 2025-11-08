<?php
declare(strict_types=1);
const APPID=730; 
const CURRENCY=3; 
const CACHE_TTL=90; 
const ITEMS_FILE=__DIR__.'/items.json'; 
const CACHE_DIR=__DIR__.'/cache_multi';

function ensureDir($d)
{ 
	if(!is_dir($d)) 
		@mkdir($d,0775,true);
} 

ensureDir(CACHE_DIR);

function parseP($s)
{ 
	if(!$s) return null; 
	$c=preg_replace('/[^\d,\.]/u','',$s); 
	
	if($c==='') 
		return null; 
	
	$a=strrpos($c,','); 
	$b=strrpos($c,'.'); 
	
	if($a===false&&$b===false) 
		return (float)$c; 
	
	if($a!==false&&($b===false||$a>$b))
	{
		$dec=',';$thou='.'; 
	} 
	else
	{ 
		$dec='.';$thou=','; 
	} 
		
	$no=str_replace($thou,'',$c); 
		
	$n=str_replace($dec,'.',$no); 
		
	return is_numeric($n)?(float)$n:null; 
}

function priceoverview($appid,$hash,$cur)
{ 
	$u='https://steamcommunity.com/market/priceoverview/?'.http_build_query(['appid'=>$appid,'currency'=>$cur,'market_hash_name'=>$hash]);

	$ch=curl_init($u); 
	
	curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_TIMEOUT=>15,CURLOPT_HTTPHEADER=>['User-Agent: Mozilla/5.0 (Dash QMER)']]); 
	
	$r=curl_exec($ch); 
	
	$e=curl_error($ch); 
	
	$code=(int)curl_getinfo($ch,CURLINFO_HTTP_CODE); 
	
	curl_close($ch); 
	
	if($e||$code>=400) 
		throw new RuntimeException('HTTP '.$code.': '.$e); 
	
	$d=json_decode($r,true); 
	
	if(!is_array($d)) 
		throw new RuntimeException('No JSON'); return $d; 
}

header('Content-Type: application/json; charset=utf-8');

$items=json_decode(@file_get_contents(ITEMS_FILE),true); 

if(!is_array($items))
{ 
	http_response_code(500); 
	echo json_encode(['success'=>false]); exit; 
}

$now=time(); 
$res=[]; 

foreach($items as $it)
{ 
	$name=(string)($it['name']??''); 
	$target=isset($it['target'])?(float)$it['target']:null;

	if($name==='') 
		continue; 
	$cf=CACHE_DIR.'/'.substr(sha1($name),0,16).'.json'; $fromCache=false; 
	
	if(is_file($cf)&&($now-filemtime($cf)<CACHE_TTL)) 
	{ 
		$p=json_decode(file_get_contents($cf),true);
		$fromCache=true; 
	} 
	else 
	{ 
		try
		{ 
			$d=priceoverview(APPID,$name,CURRENCY); 
		
			$low=parseP($d['lowest_price']??null)??parseP($d['median_price']??null); 
			$p=['item'=>$name,'lowest_price'=>$d['lowest_price']??null,'median_price'=>$d['median_price']??null,'volume'=>$d['volume']??null,'lowest_float'=>$low,'fetched_at'=>date('c'),'success'=>(bool)($d['success']??false)]; 
		
			file_put_contents($cf,json_encode($p)); 
			usleep(500000);
		}
		catch(Throwable $t)
		{ 
			if(is_file($cf)) $p=json_decode(file_get_contents($cf),true);
			else $p=['item'=>$name,'success'=>false,'error'=>$t->getMessage()]; 
		} 
	}

	$under=(isset($p['lowest_float'],$target)&&$p['lowest_float']<=$target); 
	$res[]=[ 'item'=>$name,'target'=>$target,'lowest_price'=>$p['lowest_price']??null,'median_price'=>$p['median_price']??null,'volume'=>$p['volume']??null,'lowest_float'=>$p['lowest_float']??null,'fetched_at'=>$p['fetched_at']??null,'success'=>$p['success']??false,'from_cache'=>$fromCache,'market_url'=>'https://steamcommunity.com/market/listings/'.APPID.'/'.rawurlencode($name), 'under_target'=>$under ]; 
}
echo json_encode(['success'=>true,'generated_at'=>date('c'),'items'=>$res]);
