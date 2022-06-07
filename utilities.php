// EAN (validation ean)
  nr_match('name="referencia" value="239"', $cache, $_EAN);
  if(is_numeric($_EAN)&&strlen($_EAN)>10){
    $import->add_code('EAN',  $_EAN);
  }else{
    $import->add_code('REF',  $_EAN);
  }


// clean url
if(strpos($url,'?')){
		$url_parsed = parse_url($url);  
		$url = $url_parsed['scheme'].'://'.$url_parsed['host'].$url_parsed['path'];
	}


// Price format
	$_price = preg_replace('/[^0-9\,\.]/', '', $_price);
	$import->price($_price);