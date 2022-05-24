<?php 
$custom_headers[]= 'x-requested-with: XMLHttpRequest';
$url_add='https://www.'.$store_info["Domain"].'/cart/addItemCart'; 
$url_checkout='https://www.'.$store_info["Domain"].'/checkout'; 

function nr_calculate_shipping($params){
	global $custom_headers;
	global $url_add;
	global $url_checkout;

	// COOKIES ADD
	$cacheCookies_add = nr_get_content($url_add, array('post'=>$params,'header'=>true,'custom_headers'=>$custom_headers));
	$cookies_add = nr_get_cookies_from_headers($cacheCookies_add);

	// CACHE CHECKOUT
	$cache_shipping = nr_get_content($url_checkout,array('cookies'=>$cookies_add)); 
	nr_match('class=" selectedShipping ship-method".*?type="radio" price = "(.*?)"', $cache_shipping, $_shipping);

	return $_shipping;
}

function details($url){
	global $import;


	$cache = nr_get_content($url);	
	$import->url($url);

	// CODE
	nr_match('itemprop="productID" content="id:(.*?)"', $cache, $_code);	
	$import->code($_code);

	// TITLE
	nr_match('<h1 class="product-name".*?>(.*?)</h1>', $cache, $_title);
	$import->title($_title);

	// DESCRIPTION
	nr_match('<ul class="tab--content-features">(.*?)</ul>', $cache, $_description);
	$import->description($_description);

	// PRICE
	nr_match("'detail':.*?'price': '(.*?)'", $cache, $_price);
	if(empty($_price)){
		nr_match('"cost_price":(.*?),', $cache, $_price);
	}
	$import->price($_price);

	nr_match('itemprop="price" content="(.*?)"', $cache, $_price);
	$import->price($_price);

	// OLD PRICE
	nr_match('class="product-old-price" id="pvp_price_ref">(.*?)</div>', $cache, $_old_price);
	$import->price2($_old_price);

	// STOCK
	$import->stock('NO', array('schema.org/OutOfStock','schema.org/Discontinued'), $cache);

	// IMAGEN
	nr_match('id="mainPhoto".*?src="(.*?)"', $cache, $img);
	$import->photo_insert($img);

	nr_match_all('rel="\{gallery:\'carousel\',smallimage:\'(.*?)\'', $cache, $img);
	$import->photo_insert($img);

	// CATEGORIAS
	nr_match_all('<span itemprop="name">(.*?)</span>', $cache, $categorytags);
	$import->categorytags_insert($categorytags);

	// SHIPPING 
	if(shipping_enabled($url) === true){

		// PARAMS
		nr_match('name="offer_id" value="(.*?)"', $cache, $offer_id);
		$params = 'offer_id='.$offer_id.'&quantity=1';

		// SHIPPING
		$_shipping = nr_calculate_shipping($params);

		if($_shipping == '0.00'){
			$import->shipping(-1);
		}else{
			$import->shipping($_shipping);
		}
	}

	// BRAND
	nr_match('itemprop="brand">(.*?)<', $cache, $_brand);
	$import->add_brand($_brand);

	// SCORE
	nr_match('itemprop="AggregateRating".*?>.*?itemprop="ratingValue" content="(.*?)"',$cache, $_score);
	$import->add_score($_score, 5);

	// N RATINGS	 
	nr_match('itemprop="AggregateRating".*?>.*?itemprop="reviewCount" content="(.*?)"',$cache, $_n_comments);
	$import->add_n_comments($_n_comments);

	// EAN
	nr_match('itemprop="gtin.*?content="(.*?)"', $cache, $_EAN);
	$import->add_code('EAN', $_EAN);

	// REF
	nr_match('id="products-variations">.*?itemprop="sku" content="(.*?)"', $cache, $_REF);
	$import->add_code('REF', $_REF);

	// SUBPRODUCTS
	nr_match('; var __COMBS =.*?{return(.*?);\}\)', $cache, $_subproducts_block);
	$_subproducts_json = json_decode($_subproducts_block, true);

	foreach ($_subproducts_json as  $_code => $_subproduct_data){
		$has_subproducts=true;

		if(strlen(trim($_subproduct_data["variant"]))>0){
			$sub_product = $import->createChild();

			// CODE
			$sub_product->code($_code);

			// TITLE
			$sub_product->title($_subproduct_data["variant"]);

			// PRICE
			$sub_product->price($_subproduct_data["price"]);

			// OLD PRICE
			$sub_product->price2($_subproduct_data["pvp_price"]);

			// IMAGE
			$_img = str_replace('_p.', '_g.', $_subproduct_data["rel_img"]);
			$sub_product->photo_insert($_img);

			// STOCK
			$sub_product->stock('YES', 'InStock', $_subproduct_data["microdata_availability"]);

			// SCORE
			$sub_product->add_score($_score, 5);

			// N RATINGS	 
			$sub_product->add_n_comments($_n_comments);

			// EAN
			$sub_product->add_code('EAN', $_subproduct_data["ref"]);

			$import->add_product($sub_product);		
		}
	}
	$import->save();
}
?>