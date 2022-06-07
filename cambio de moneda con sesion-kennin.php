<?php 
$cookies=Array();
$session_timestamp=0;

$currency=strtoupper($store_info['Currency']);

switch($store_info['CountryCode']){
	case 'gb':
		$_currencyiso = 'GBP';
		$_shippingcountryid = '1903';
		$_languageiso = 'en';
		$hreflang = 'en-GB';
		break;

	case 'us':
		$_currencyiso = 'USD';
		$_shippingcountryid = '1904';
		$_languageiso = 'en';
		$hreflang = 'en-US';
		break;

	case 'ca':
		$_currencyiso = 'CA';
		$_shippingcountryid = '1736';
		$_languageiso = 'en';
		$hreflang = 'en-US';
		break;	

	case 'ch':
		$_currencyiso = 'CHF';
		$_shippingcountryid = '1882';
		$_languageiso = 'de';
		$hreflang = 'de-DE';
		break;	

	case 'ie':
		$_currencyiso = 'EU';
		$_shippingcountryid = '1791';
		$_languageiso = 'DE';
		$hreflang = 'de-DE';
		break;	

}

$change_currency_url = 'https://www.thevisorshop.com/SetProperty.aspx?currencyiso='.$_currencyiso.'&languageiso='.$_languageiso.'&shippingcountryid='.$_shippingcountryid;

function session_refresh(){
	global $store_info;
	global $cookies;
	global $session_timestamp;
	global $import;
	global $change_currency_url;

	$now=time();
	$now_milliseconds=round(microtime(true) * 1000);

	if ($session_timestamp==0 || ($now-$session_timestamp)>300){ //ACTUALIZAMOS LA SESION CADA 5 MINUTOS
		$main_url = 'https://www.'.$store_info['Domain'];

		// IR A PAGINA MAIN
		$home = nr_get_content($main_url, array('header'=>true));
		$session_ip = $import->last_used_ip;

		// OBTENER COOKIES I GUARDAR LA COOKIE DE SESION
		$cookies = nr_get_cookies_from_headers($home);

		// CAMBIAMOS LA MONEDA
		$cache_session = nr_get_content($change_currency_url,array('header'=>true,'cookies'=>$cookies,'follow_location'=>'false'));
		$cookies = nr_get_cookies_from_headers($cache_session);

		$session_timestamp = time();
	}
}

function details($url){
	global $import;
	global $store_info;
	global $cookies;
	global $hreflang;
	global $_languageiso;

	// ACTUALIZAMOS LA INFO DE LA SESSION
	session_refresh();

	/*En caso de que sea url mobile cambiar a la version web*/
	if(stripos($url, '/Mobile/')!==false){
		$cache_mobile = nr_get_content($url);
		// Validar que sea una pagina de producto
		if(stripos($cache_mobile, '"PageType":"Product"')!==false){
			nr_match('<html>(.*?)rel="canonical"', $cache_mobile, $_canonical_block);
			nr_match_all('<link href="(.*?)"', $_canonical_block, $_canonical_arr);
			$_canonical = array_pop($_canonical_arr);
			$url = strlen($_canonical)>0 ? $_canonical : $url;
		}

	}

	// VISITAR PAGINAS CON LA COOKIE DE SESION
	$cache = nr_get_content($url,array('cookies'=>$cookies,'header'=>true));

	/*Capturar la url para el pais correcto*/
	nr_match('hreflang="'.$hreflang.'" href="(.*?)"', $cache, $_canonical);

	if(strlen($_canonical)==0 && stripos($_canonical, '/'.$_languageiso.'/')===false){
		$import->url($url);
		$import->save();
		return;
	}

	if(strlen($_canonical)>0 && $_canonical != $url){
		$url = $_canonical;
		$cache = nr_get_content($url,array('cookies'=>$cookies,'header'=>true));
	}

	// ULR
	$import->url($url);

	// CODE
	nr_match('HidModelID" value="(.*?)"', $cache, $_code);
	$import->code($_code);

	nr_match('"ParameterValue":"(.*?)"', $cache, $_code);
	$import->code($_code);

	// TITLE
	nr_match('class="main-content".*?<h1.*?>(.*?)</h1>', $cache, $_title);
	$import->title($_title);

	// DESCRIPTION
	nr_match('model-description">(.*?)</div>', $cache, $_description);
	$import->description($_description);	

	// PRICE
	nr_match('ecomm_totalvalue:(.*?),', $cache, $_price);
	$import->price($_price);

	// IMAGE
	nr_match('og:image" content="(.*?)"', $cache, $_img);
	$import->photo_insert($_img);

	// STOCK
	$import->stock('YES','class="add-to-basket-button"', $cache);

	// CATEGORIES
	nr_match('"CategoryName":"(.*?)"', $cache, $_categorytags);
	$import->categorytags_insert($_categorytags);

	// BRAND
	nr_match("brand: '(.*?)'", $cache, $_brand);
	$import->add_brand($_brand);

	// EAN
	nr_match('"gtin.*?:.*?"(.*?)"', $cache, $_EAN);
	$import->add_code('EAN', $_EAN);

	// SUB PRODUCT
	nr_match('type="application/ld\+json">(.*?)</script>', $cache, $_sub_details_block);
	$_sub_details_block = (array)json_decode($_sub_details_block, true);

	// CODE_CHILDS
	foreach ($_sub_details_block as $_price_data){
		$sub_product = $import->createChild();

		// CODE
		$sub_product->code($_price_data["sku"]);

		// TITLE
		$sub_product->title($_price_data["name"]);

		// PRICE
		$sub_product->price($_price_data["offers"]["price"]);

		// STOCK
		$sub_product->stock('NO', 'OutOfStock', $_price_data["availability"]);

		// IMAGE
		$import->photo_insert($_price_data["image"]);

		// EAN
		$sub_product->add_code('EAN',  $_price_data["gtin13"]);	

		// REF
		$sub_product->add_code('REF',$_price_data["sku"]);

		// MPN
		$sub_product->add_code('EAN',  $_price_data["mpn"]);

		$import->add_product($sub_product);
	}


	if(empty($_sub_details_block)){

		// SUB_PRODUCTS
		nr_match_all('schema.org",.*?"sku".*?"(.*?)".*?"name".*?"(.*?)".*?offers".*?}', $cache, $_table_prices);

		// CODE_CHILDS
		foreach ($_table_prices[0] as $_key => $_price_data){
			$sub_product = $import->createChild();

			// CODE
			$sub_product->code($_table_prices[1][$_key]);

			// TITLE
			$sub_product->title($_table_prices[2][$_key]);

			// PRICE
			nr_match('"offers".*?"price".*?:.*?"(.*?)"', $_table_prices[0][$_key], $_subprice);
			$sub_product->price($_subprice);

			// STOCK
			$sub_product->stock('NO', 'OutOfStock', $_table_prices[0][$_key]);

			// IMAGE
			nr_match('"image".*?:.*?"(.*?)"', $_table_prices[0][$_key], $_subimg);
			$import->photo_insert(_subimg);

			// EAN
			nr_match('"gtin.*?:.*?"(.*?)"', $_table_prices[0][$_key], $_subEAN);
			$sub_product->add_code('EAN',  $_subEAN); 

			// REF
			$sub_product->add_code('REF',$_table_prices[1][$_key]);

			// MPN
			nr_match('"mpn".*?:.*?"(.*?)"', $_table_prices[0][$_key], $_subMPN);
			$sub_product->add_code('MPN',  $_subMPN);


			$import->add_product($sub_product);
		}
		$import->save();

	}
	$import->save();
}

https://rubik.netrivals.com/scripts/edit.php?script_type=details&import_type=public&storeid=81186


	// OLD PRICE
	nr_match('PRVP: <span class="convert_price">(.*?)<', $cache, $_price2);
	$import->price2($_price2);

	nr_match('class="conv_price">(.*?)<', $cache, $_price2);
	
	if(!$_price2) {
		nr_match('SRP: <span class="convert_price">(.*?)<', $cache, $_price2);
	}
	
	$import->price2($_price2);