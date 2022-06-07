<?php 
$ajaxUrl = 'https://eu.'.$store_info['Domain'].'/'.$store_info['CountryCode'].'/'.$store_info['CountryCode'].'/variation?pid=';
function nr_url_encode($url){
	nr_match('>>>(.*?)://', '>>>'.$url, $_protocol);
	nr_match('://(.*?)/', '>>>'.$url, $_domain);
	if(substr_count($url, '?') > 0){
		nr_match('://.*?/(.*?)\?', $url, $url_part);
		nr_match('\?(.*?)', $url, $_url_params);
	}else{
		nr_match('//.*?/(.*?)', $url, $url_part);
	}

	$url_part = urlencode($url_part);
	$url = $_protocol.'://'.$_domain.'/'.$url_part;
	$url = str_replace('%2F', '/', $url);

	if($_url_params){
		$url .= '?'.$_url_params;
	}

	return $url;
}

function nr_url_utf8($url){

	$url_parsed = nr_parse_url($url);

	$url_path = utf8_decode($url_parsed['path']);
	$url = $url_parsed['scheme'].'://'.$url_parsed['host'].$url_path;

	return $url;
}

function details($url){
	global $import;
	global $store_info;
	global $ajaxUrl;

	$url = nr_url_utf8($url);
	$url = preg_replace('(\/[a-z]{2}\/[a-z]{2}\/)','/'.$store_info['CountryCode'].'/'.$store_info['DefaultLang'].'/',$url);

	$cache = nr_get_content(nr_url_encode($url));

	$import->url($url);

	// CODE
	nr_match('class="product-id d-none">(.*?)</', $cache, $_code);
	$import->code($_code);

	nr_match('data-pid="(.*?)"', $cache, $_code);
	$import->code($_code);

	nr_match('data-js-product-product-id=.*?>(.*?)<', $cache, $_code);
	$import->code($_code);

	// TITLE
	nr_match('<div class="product-detail.*?<h1 class="product-name">(.*?)</h1>', $cache, $_title);
	$import->title($_title);

	nr_match('<h1.*?>(.*?)</h1>', $cache, $_title);
	$import->title($_title);

	// DESCRIPTION
	nr_match('class="content" itemprop="description">(.*?)</div>', $cache, $_description);
	$import->description($_description);	

	nr_match('<div class="row details">(.*?)</div>', $cache, $_description);
	$import->description($_description);	

	// PRICE
	nr_match('itemprop="price" content="(.*?)"', $cache, $_price);
	$import->price($_price);

	nr_match('"price":(.*?),', $cache, $_price);
	$import->price($_price);

	// OLD PRICE
	nr_match('class="strike-through.*?class="value".*?>(.*?)</span>', $cache, $_price2);
	$import->price2($_price2);

	// IMAGE
	nr_match('type":"Product".*?"image":\[(.*?)\]', $cache, $_img_block);
	nr_match_all('"(.*?)"', $_img_block, $_img);
	$import->photo_insert($_img);

	// BRAND
	nr_match('"brand":"(.*?)"',$cache, $_brand);
	$import->add_brand($_brand);

	// STOCK
	$import->stock('YES', array('"inStock":true', '"inStock":"true"','schema.org/InStock'), $cache);

	// CATEGORIES 
	nr_match_all('itemprop="name" content="(.*?)"',$cache, $_categorytags);
	$import->categorytags_insert($_categorytags,0,1);

	// EAN
	$import->add_code('EAN', $_code);

	// UPC
	nr_match('\[{"productID":.*?"UPC":"(.*?)"', $cache, $_UPC);
	$import->add_code('UPC', $_UPC);

	// REF
	nr_match('"sku":"(.*?)"', $cache, $_REF);
	$import->add_code('REF',$_REF);

	// SUB_PRODUCTS
	nr_match('id="attributes-container-color">.*?data-component-options=\'(.*?)\'', $cache, $_table_pricesBlock);
	if(is_null($_table_pricesBlock)){
		nr_match('id="attributes-container-color".*?data-component-options="(.*?)"', $cache, $_table_pricesBlock);
	}
	$_table_prices_decode = html_entity_decode($_table_pricesBlock);
	$_table_prices = json_decode($_table_prices_decode,true);
	// CODE_CHILDS
	foreach ($_table_prices["swatches"] as $_key => $_subproduct){

		$subcacheBlock = nr_get_content($_subproduct["urlSelectVariant"]);

		if(substr_count($subcacheBlock,'<head>') > 0){
			nr_match('<pre.*?>(.*?)</pre>', $subcacheBlock, $subcacheBlock);
		}

		$subcache = json_decode($subcacheBlock,true);
		$pumaSwatches = array_pop($subcache["pumaSwatches"]);

		foreach($pumaSwatches["swatches"] as $key => $swatches){
			$sub_product = $import->createChild();
			$urlsize = $swatches["urlSelectVariant"];
			$size_cacheBlock = nr_get_content($urlsize);

			if(substr_count($size_cacheBlock,'<head>') > 0){
				nr_match('<pre.*?>(.*?)</pre>', $size_cacheBlock, $size_cacheBlock);
			}
			$size_cache = json_decode($size_cacheBlock,true);

			// CODE
			$sub_product->code($size_cache["product"]["id"]);

			// TITLE
			$sub_product->title($size_cache["product"]["variationAttributes"][0]["selectedValue"]["displayValue"].'|'.$swatches["displayValue"]);

			// PRICE
			$sub_product->price($size_cache["product"]["price"]["sales"]["decimalPrice"]);

			// OLD PRICE
			$sub_product->price2($size_cache["product"]["price"]["list"]["decimalPrice"]);

			// IMAGE
			foreach($size_cache["product"]["images"]["large"] as $_key => $large){
				$sub_product->photo_insert($large["picture"]["img"]["src"]);
			}

			// STOCK
			if($size_cache["product"]["analyticsData"]["inStock"] == true){
				$sub_product->stock('YES');
			}else{
				$sub_product->stock('NO');
			}

			// EAN
			$sub_product->add_code('EAN',  $size_cache["product"]["analyticsData"]["EAN"]);

			// UPC
			//$sub_product->add_code('UPC',  $size_cache["product"]["analyticsData"]["UPC"]);

			// REF
			$sub_product->add_code('REF',  $size_cache["product"]["analyticsData"]["skuID"]);
			$import->add_product($sub_product);
		}

	}


	$import->save();
}