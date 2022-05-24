<?php
switch($store_info["Currency"]){
	case 'eur':
		$_currency = 'EUR';
		break;

	case 'usd':
		$_currency = 'USD';
		break;

	case 'gbp':
		$_currency = 'GBP';
		break;

	case 'ca':
		$_currency = 'CAD';
		break;

	case 'au':
		$_currency = 'AUD';
		break;
}
$cookies[] = 'cart_currency='.$_currency;
$cookies[] = 'localization='.strtoupper($store_info["CountryCode"]);
$CountryCode = $store_info["CountryCode"];

function details($url){
	global $import;
	global $currency_store;
	global $cookies;
	global $CountryCode;

	$import->url($url);

	$cache = nr_get_content($url,array('cookies'=>$cookies,'usecache'=>false,'header'=>true));

	// CODE
	nr_match('"product":{"id":(.*?),', $cache, $_code);
	if(is_numeric($_code)){
		$import->code($_code);
	}

	// TITLE
	nr_match('property="og:title" content="(.*?)"', $cache, $_title);
	$import->title($_title);

	// DESCRIPTION
	nr_match('box-text" itemprop="description".*?>(.*?)</div>', $cache, $_description);
	$import->description($_description);

	// PRICE
	nr_match('"Viewed Product",.*?,"price":"(.*?)"', $cache, $_price);
	$import->price($_price);
	
	// OLD PRICE
	nr_match('data-compare="(.*?)"', $cache, $_price2);
	$import->price2($_price2/100);

	// IMAGE
	nr_match_all('property="og:image" content="(.*?)"', $cache, $_img);
	$import->photo_insert($_img);

	// STOCK
	$import->stock('YES','schema.org/InStock', $cache);

	// CATEGORIES
	nr_match('"Viewed Product",.*?,"category":"(.*?)"', $cache, $_categorytags);
	$import->categorytags_insert(nr_unescape_unicode_chars($_categorytags));

	// BRAND
	nr_match('{"product":{.*?,"vendor":"(.*?)"', $cache, $_brand);
	$import->add_brand($_brand);

	// EAN
	nr_match('"gtin.*?": "(.*?)"', $cache, $_EAN);
	$import->add_code('EAN', $_EAN);

	// REF
	nr_match('"product":{"id":.*?,"sku":"(.*?)"', $cache, $_REF);
	$import->add_code('REF', $_REF);

	// SUB_PRODUCTS
	nr_match('{"product":{"id":'.$import->code.'.*?variants":\[(.*?)\]},', $cache, $_subproducts_block);
	nr_match_all('{"id":(.*?),.*?"price":(.*?),.*?"public_title":"(.*?)".*?"sku":"(.*?)"}', $_subproducts_block, $_table_prices);

	if(count($_table_prices[0]) > 1){ 
		// CODE_CHILDS
		foreach ($_table_prices[1] as $_key => $_price_data){
			$sub_product = $import->createChild();

			// CODE
			$sub_product->code($_table_prices[1][$_key]);

			// TITLE
			$sub_product->title(str_replace('Default Title', '', nr_unescape_unicode_chars($_table_prices[3][$_key])));

			// PRICE
			$_subprice = (($_table_prices[2][$_key]/100)*$_convertion_tax_from) / $_convertion_tax_to;
			$sub_product->price($_subprice);

			// PRICE2
			nr_match('id="'.$sub_product->code.'"(.*?)/>', $cache, $_sub_price2_block);
			nr_match('data-original-price=".*?>(.*?)</span>" ', $_sub_price2_block, $_sub_price2);
			$sub_product->price2($_sub_price2);

			// STOCK
			$sub_product->stock('NO', '"available":false', $_table_prices[0][$_key]);

			// REF
			$sub_product->add_code('REF', str_replace('"', '', $_table_prices[4][$_key]));

			$import->add_product($sub_product);
		}
	}
	$import->save();
}
?>