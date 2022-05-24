<?php 

$cookies = array();
$session_timestamp = 0;

$currency = strtoupper($store_info['Currency']);
$country = strtoupper($store_info['CountryCode']);
$lang = $store_info['DefaultLang'];

function session_refresh(){
	global $store_info;
	global $cookies;
	global $session_timestamp;
	global $import;
	global $currency;
	global $country;
	global $lang;
	
	$now=time();
	$now_milliseconds=round(microtime(true) * 1000);
	
	if ($session_timestamp==0 || ($now-$session_timestamp)>300){
		$main_url = 'https://www.'.$store_info['Domain'];
		
		// IR A PAGINA MAIN
		$cache = nr_get_content($main_url, array('header'=>true, 'save_html'=>false));

		// OBTENER COOKIES I GUARDAR LA COOKIE DE SESION
		$cookies = nr_get_cookies_from_headers($cache);
	
		// CAMBIAMOS PAIS DESTINO
		$microtime = number_format(microtime(true),3,'','');
		
		if($currency== 'GBP'){
			$change_currency_url =$main_url.'/?GlobalEData={%22countryISO%22:%22'.$country.'%22,%22currencyCode%22:%22'.$currency.'%22,%22defaultCurrencyCode%22:%22'.$currency.'%22,%22cultureCode%22%3A%22'.$lang.'-'.$country.'%22}&t='.$microtime;
		}else{
			$change_currency_url = $main_url.'/?GlobalEData={%22countryISO%22:%22'.$country.'%22,%22currencyCode%22:%22'.$currency.'%22,%22defaultCurrencyCode%22:%22'.$currency.'%22,%22cultureCode%22:%22'.$lang.'%22}&t='.$microtime;
		}
		
		$cache = nr_get_content($change_currency_url, array('header'=>true,'cookies'=>$cookies,'follow_location'=>'false','save_html'=>false));
		$cookies = nr_get_cookies_from_headers($cache);
		
		$cookies = str_replace('GlobalE_Data=deleted', '', $cookies);
		$cookies = array_filter($cookies);
		
		//Marcamos la hora que se ha seteado la config de la sesion
		$session_timestamp = time();
	}
}
function details($url){
	global $import;
	global $currency;
	global $cookies;
	global $crawlera_keys_by_country;
	global $store_info;
	$import->url($url);
	
	// ACTUALIZAMOS LA INFO DE LA SESSION
	session_refresh();
	
		// VISITAR PAGINAS CON LA COOKIE DE SESION
	$cache = nr_get_content($url, array('cookies'=>$cookies,'header'=>true, 'usecache'=>false));
	
	// CODE
	nr_match('name="product" value="(.*?)"', $cache, $_code);
	$import->code($_code);
	
	// TITLE
	nr_match('<h1 class="page-title".*?>(.*?)</h1>', $cache, $_title);
	$import->title($_title);
	
	// DESCRIPTION
	nr_match('itemprop="description">(.*?)</div>', $cache, $_description);
	$import->description($_description);	
	
	// PRICE
	if($currency === 'GBP'){
		nr_match('"price": "(.*?)"', $cache, $_price);
	}else{
		nr_match('class="product-info-main.*?data-price-amount="(.*?)"', $cache, $_price);
	}
	$import->price($_price);
	
	// OLD PRICE
	nr_match('class="price-box-inner">.*?id="old-price-'.$import->code.'.*?data-price-amount="(.*?)"', $cache, $_price2);
	$import->price2($_price2);
	
	// IMAGE
	nr_match('itemprop="image"  src="(.*?)"', $cache, $_img);
    $import->photo_insert($_img);
	
	nr_match_all('rel="useZoom:\'zoom1\', smallImage: \'(.*?)\'', $cache, $_img);
    $import->photo_insert($_img);
	
	nr_match_all('"full":"(.*?)"', $cache, $_img);
	$_img = str_replace('\\/', '/', $_img);
    $import->photo_insert($_img);

	// STOCK
	$import->stock('YES', array('schema.org/InStock', 'class="stock available"'), $cache);
	
    // BRAND
	nr_match('itemprop="brand" content="(.*?)"', $cache, $_brand);
	$import->add_brand($_brand);
	
	nr_match('"brand": {.*?"name": "(.*?)"', $cache, $_brand);
	$import->add_brand($_brand);
	
	//REF
	nr_match('itemprop="sku">(.*?)<',$cache, $_ref);
	$import->add_code('REF',$_ref);
	
	// MPN
	nr_match('"mpn": "(.*?)"',$cache, $_mpn);
	$import->add_code('MPN',$_mpn);
	
	// EAN
	nr_match('"gtin13": "(.*?)"',$cache, $_ean);
	$import->add_code('EAN',$_ean);
	
	$import->save();
}
?>