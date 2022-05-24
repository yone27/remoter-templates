<?php 
$custom_headers[] = 'authority: www.parrotessentials.co.uk' ;
  $custom_headers[] = 'accept: */*' ;
  $custom_headers[] = 'accept-language: es-ES,es;q=0.9' ;
  $custom_headers[] = 'content-type: application/x-www-form-urlencoded; charset=UTF-8' ;
  $custom_headers[] = 'cookie: fornax_anonymousId=cf0c1d41-2838-42ea-88a8-458e0ec591c3; SHOP_SESSION_TOKEN=qoto8tg7pp7j17gjpldk0eioeq; XSRF-TOKEN=ca3c4b1eb5b5f952a1cd7a7e492327481d490fdcb3a2d0988629e3871335405c; _ga=GA1.3.241715913.1651520872; _gid=GA1.3.523570537.1651520872; Affc=; sloyalty-vid=1SB4JG8Y; _fbp=fb.2.1651520872817.868347247; STORE_VISITOR=1; soundestID=20220502194753-Aant84Q5qPKoTACIpLcMzryPeFd19kb2pAPQXSQ4dPvm1FgHC; omnisendAnonymousID=BE5KERjfFXqtRG-20220502194753; soundest-cart=%7B%22lastProductsCount%22%3A0%7D; soundest-form-626ba11c98bc3f001e66d008-closed-at=2022-05-02T19:47:57.976Z; lastVisitedCategory=57; _uetsid=c11e1170ca5011ecbee42da891e61f7e; _uetvid=c11e4450ca5011ec8cd1916c7eb0121b; soundest-views=17; page-views=17; Shopper-Pref=82F06B0BDB98A36233190F03921BA658B8233194-1652128947223-x%7B%22cur%22%3A%22GBP%22%7D; _clck=5ui12f|1|f15|0; _clsk=ghz9fr|1651580486396|1|1|h.clarity.ms/collect' ;
  $custom_headers[] = 'origin: https://www.parrotessentials.co.uk' ;
  $custom_headers[] = 'referer: https://www.parrotessentials.co.uk/harrisons-high-potency-coarse-complete-parrot-food/' ;
  $custom_headers[] = 'sec-ch-ua: " Not A;Brand";v="99", "Chromium";v="100", "Google Chrome";v="100"' ;
  $custom_headers[] = 'sec-ch-ua-mobile: ?0' ;
  $custom_headers[] = 'sec-ch-ua-platform: "Windows"' ;
  $custom_headers[] = 'sec-fetch-dest: empty' ;
  $custom_headers[] = 'sec-fetch-mode: cors' ;
  $custom_headers[] = 'sec-fetch-site: same-origin' ;
  $custom_headers[] = 'stencil-config: {}' ;
  $custom_headers[] = 'stencil-options: {"render_with":"products/bulk-discount-rates"}' ;
  $custom_headers[] = 'user-agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/100.0.4896.127 Safari/537.36' ;
  $custom_headers[] = 'x-requested-with: stencil-utils' ;
  $custom_headers[] = 'x-xsrf-token: ca3c4b1eb5b5f952a1cd7a7e492327481d490fdcb3a2d0988629e3871335405c' ;

function details($url){
	global $import;
	global $custom_headers;

	$import->url($url);

	$cache = nr_get_content($url);

	// CODE
	nr_match('name="product_id" value="(.*?)"', $cache, $_code);
	$import->code($_code);

	// TITLE
	nr_match('<h1 class="productView-title".*?>(.*?)</h1>', $cache, $_title);
	$import->title($_title);

	// DESCRIPTION
	nr_match('id="tab-description">(.*?)</div>', $cache, $_description);
	$import->description($_description);	

	// PRICE
	nr_match('"product:price:amount" content="(.*?)"', $cache, $_price);
	$import->price($_price);

	// PRICE 2
	nr_match('class="productView-product">.*?class="price price--non-sale">(.*?)</', $cache, $_price2);
	$import->price2($_price2);

	// IMAGE
	nr_match_all('data-zoom-image="(.*?)"', $cache, $_img);
	$import->photo_insert($_img);

	// STOCK
	$import->stock('NO', array('"og:availability" content="outofstock"', '"instock":false,'), $cache);

	// CATEGORIES
	nr_match('itemtype="https://schema.org/BreadcrumbList">(.*?)</ol', $cache, $_categorytags_block);
	nr_match_all('<a.*?>(.*?)</', $_categorytags_block, $_categorytags);
	$import->categorytags_insert($_categorytags, 0, 1);

	// BRAND
	nr_match('"brand": {.*?"name": "(.*?)"', $cache, $_brand);
	$import->add_brand($_brand);

	// EAN
	nr_match('itemprop="gtin" content="(.*?)"', $cache, $_EAN);
	$import->add_code('EAN', $_EAN);

	// REF
	nr_match('"sku":"(.*?)"', $cache, $_REF);
	$import->add_code('REF', $_REF);

	// SUB_PRODUCTS

	// sizes
	nr_match('radio-group-label-(.*?)">(.*?)</div>', $cache, $_sizes_block);
	nr_match_all('data-product-attribute-value="(.*?)"', $_sizes_block[2], $_subcodes);
	$newURL = "https://www.parrotessentials.co.uk/remote/v1/product-attributes/".$import->code;
	
	// CODE_CHILDS
	foreach ($_subcodes as $_price_data){
		$sub_product = $import->createChild();
		
		// req
		$params = 'action=add&attribute['.$_sizes_block[1].']='.$_price_data.'&qty%5B%5D=1&product_id='.$import->code;
		$cache = nr_get_content($newURL , array('custom_headers'=>$custom_headers,'post'=>$params,'usecache'=>false));

		//$subCache = nr_get_content($newURL, array('post'=>$params,'usecache'=>false));
		//$cookies = nr_get_cookies_from_headers($cache);
		dump($cache);
		exit();

		// CODE
		$sub_product->code($_table_prices[1][$_key]);

		// TITLE
		$sub_product->title($_table_prices[2][$_key]);

		// PRICE
		$sub_product->price($_table_prices[3][$_key]);

		// STOCK
		$sub_product->stock('NO','outOfStock', $_table_prices[4][$_key]);

		// EAN, REF, ISBN, UPC, ASIN, MPN
		$sub_product->add_code('XXXXX',  $_table_prices[5][$_key]);		

		$import->add_product($sub_product);
	}


	$import->save();
}