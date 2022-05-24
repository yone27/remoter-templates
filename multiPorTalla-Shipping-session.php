<?php
$ajaxUrl = 'https://www.'.$store_info['Domain'].'/ajax/price/';

$_url_add = 'https://www.'.$store_info["Domain"].'/on/demandware.store/Sites-campz-'.$store_info["CountryCode"].'-Site/'.$store_info["CountryCode"].'_'.strtoupper($store_info["CountryCode"]).'/Cart-AddProduct';

switch($store_info["CountryCode"]){
	case 'ch':
		$_url_cart = 'https://www.'.$store_info["Domain"].'/warenkorb/';
		break;
		
	case 'es':
		$_url_cart = 'https://www.'.$store_info["Domain"].'/cart/';
		break;
		
	case 'fr':
		$_url_cart = 'https://www.'.$store_info["Domain"].'/cart/';
		break;
		
	case 'dk':
		$_url_cart = 'https://www.'.$store_info["Domain"].'/cart/';
		break;
		
	case 'it':
		$_url_cart = 'https://www.'.$store_info["Domain"].'/cart/';
		break;
}

$cookies = array();
$session_timestamp = 0;
function session_refresh($url){
	global $cookies;
	global $session_timestamp;
	
	$now = time();
	
	if ($session_timestamp==0 || ($now-$session_timestamp)>300){

		// COOKIES
		$cache_cookies = nr_get_content( $url , array('header'=>true,'follow_location'=>false));
		$cookies = nr_get_cookies_from_headers($cache_cookies);

		$session_timestamp = time();
	}
}

function details($url){
	global $import;
	global $ajaxUrl;
	global $cookies;

	session_refresh($url);
	
	$cache = nr_get_content($url,array('header'=>true, 'usecache'=>false));
	
	if(strpos($url,'/suche/')!==false||strpos($url,'/search/')!==false){
		nr_match('rel="canonical" href="(.*?)"', $cache, $url);
		$import->url($url);
	}else{
		$import->url($url);
	}

	// CODE
	nr_match('_review_productsId" value="(.*?)"', $cache, $_code);
	$import->code($_code);

	// TITLE
	nr_match('<div id="main".*?<h1.*?>(.*?)</h1>', $cache, $_title);
	$import->title($_title);

	nr_match('<h1.*?>(.*?)</h1>', $cache, $_title);
	$import->title($_title);

	// DESCRIPTION
	nr_match('itemprop="description">(.*?)</ul>', $cache, $_description);
	$import->description($_description);

	nr_match('class="description".*?>(.*?)</div>', $cache, $_description);
	$import->description($_description);

	// CATEGORIES
	nr_match_all('class="breadcrumb-element-inner".*?">(.*?)<', $cache, $_categorytags);
	$import->categorytags_insert($_categorytags, 1);

	// PRICE 
	nr_match('class="currentPrice.*?class="price">(.*?)</span>', $cache, $_price);
	$_price = preg_replace('/[^0-9\,\.]/', '', $_price);
	$import->price($_price);

	nr_match('"unit_sale_price":(.*?),', $cache, $_price);
	$import->price($_price);

	nr_match('<div class="product-promotion">(.*?)class="price-hint cyc-typo_secondary cyc-color-text_disabled', $cache, $_price_block);
	nr_match('itemprop="price" content="(.*?)"', $_price_block, $_price);
	$import->price($_price);

	nr_match('schema.org/Offer">.*?itemprop=\'price\' content=\'(.*?)\'', $cache, $_price);
	$import->price($_price);

	// PRICE 2
	nr_match('<div class="price-standard">(.*?)<', $_price_block, $_price2);
	$import->price2($_price2);

	nr_match('invalid cyc-color-text">(.*?)<', $cache, $_price2);
	$import->price2($_price2);

	nr_match(' js-productPriceCtr">.*?<div class="price-standard">(.*?)<', $cache, $_price2);
	$import->price2($_price2);

	// IMAGE
	nr_match_all('class="cyc-slider_item.*?href="(.*?)"', $cache, $_img);
	$import->photo_insert($_img);

	nr_match_all('data-photoswipe="(.*?)"', $cache, $_imgs);
	$_imgs = json_decode(html_entity_decode($_imgs[0]),true);
	foreach(json_decode($_imgs["imagesData"]) as $_key => $imagesData){
		$import->photo_insert($imagesData);
	}

	nr_match_all('data-zoomimage="(.*?)"', $cache, $_img);
	$import->photo_insert($_img);

	// CATEGORIES
	nr_match_all('<span itemprop="title">(.*?)<', $cache, $_categorytags);
	$import->categorytags_insert($_categorytags, 1);
	
	// SHIPPING 
	if(shipping_enabled($url) === false){

		// PARAMS
		nr_match('dw.ac._capture\({id: "(.*?)"', $cache, $_datapid);
		$params = 'format=ajax&cartAction=add&pid='.$_datapid; 

		// SHIPPING
		$_shipping = nr_calculate_shipping($params);

		if($_shipping == '0.00'){
			$import->shipping(-1);
		}else{
			$import->shipping($_shipping);
		}
	}

	// STOCK
	$import->stock('NO', array('>Ikke på lager<','mb10 cyc-typo_secondary cyc-color-text_sale', '"stock":0,','schema.org/OutOfStock'), $cache);

	// BRAND
	nr_match('"manufacturer":"(.*?)"', $cache, $_brand);
	$import->add_brand($_brand);

	nr_match('class="is-inlineblock gtm-brandlogo">.*?title="(.*?)"', $cache, $_brand);
	$import->add_brand($_brand);

	// SCORE
	nr_match('id="primary".*?"ratingValue":"(.*?)"',$cache, $_score);
	$import->add_score($_score, 5);

	// N RATINGS
	nr_match('id="primary".*?"reviewCount":"(.*?)"',$cache, $_n_comments);
	$import->add_n_comments($_n_comments);

	// EAN
	nr_match('productData:.*?"gtin.*?":"(.*?)"', $cache, $_EAN);
	$import->add_code('EAN', $_EAN);

	nr_match('"ean.*?:.*?"(.*?)"', $cache, $_EAN);
	$import->add_code('EAN', $_EAN);

	// REF
	nr_match('"sku_code":"(.*?)"', $cache, $_REF);
	$import->add_code('REF', $_REF);
	
	/* Se realiza el multi solo por talla ya que cada color tiene su propia URL */
	// SUB_PRODUCTS 
	nr_match_all('class="variation__option.*?data-productid="(.*?)".*?data-variationvalue="(.*?)".*?class="cyc-flexitem no-wrap is-right">(.*?)<', $cache, $_table_prices);

	// CODE_CHILDS
	foreach ($_table_prices[1] as $_key => $_price_data){
		$sub_product = $import->createChild();

		// CODE
		$_subcode_arr = explode("_", $_table_prices[1][$_key]);
		$subcode = $_subcode_arr[1];
		$sub_product->code($subcode);
		
		$shippingCodes[] = $subcode;

		// TITLE
		$sub_product->title($_table_prices[2][$_key]);

		// PRICE
		$sub_product->price($_table_prices[3][$_key]);
		
				// OLD PRICE
		$sub_product->price2($import->price2);
		
		// STOCK
		$sub_product->stock('NO', 'agotado', $_table_prices[2][$_key]);
		
			// SHIPPING 
			if(shipping_enabled($url) === false){

				// PARAMS
				nr_match('dw.ac._capture\({id: "(.*?)"', $cache, $_datapid);
				$params = 'format=ajax&cartAction=add&pid='.preg_replace('/[^0-9]/', '', $_datapid).'_'.$subcode; 

				// SHIPPING
				$_shipping = nr_calculate_shipping($params);

				if($_shipping == '0.00'){
					$import->shipping(-1);
				}else{
					$import->shipping($_shipping);
				}
			}

		$import->add_product($sub_product);
	}

	$import->save();
}

function nr_calculate_shipping($params){
	global $_url_add; 
	global $_url_cart; 
	global $cookies; 
	
	// ADD TO CART
	$cache_add = nr_get_content($_url_add, array('post'=>$params,'usecache'=>false, 'header'=>true, 'cookies'=>$cookies));
	$cookies_add = nr_get_cookies_from_headers($cache_add);
	
	// CART
	$cache_cart = nr_get_content($_url_cart, array('usecache'=>false, 'header'=>true, 'cookies'=>$cookies));

	// SHIPPING COST
	nr_match('class="order-shipping cyc-flex">.*?class="total cyc-typo_subheader cyc-padding_bottom.*?>(.*?)</div>', $cache_cart, $_shipping);

	return $_shipping;
}