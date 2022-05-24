<?php 
function details($url){
	global $import;

	$import->url($url);

	$cache = nr_get_content($url);

	// CODE
	nr_match('\[productId\]" value="(.*?)"', $cache, $_code);
	$import->code($_code);
	nr_match(',"productID":"(.*?)"', $cache, $_code);
	$import->code($_code);

	// TITLE
	nr_match('"\@type": "WebPage".*?"name": "(.*?)"', $cache, $_title);
	$import->title($_title);
	nr_match('<h1.*?>(.*?)</h1>', $cache, $_title);
	$import->title($_title);

	// DESCRIPTION
	nr_match('name="description" content="(.*?)"', $cache, $_description);
	$import->description($_description);	

	// PRICE
	nr_match('"product:price:amount" content="(.*?)"', $cache, $_price);
	$import->price($_price);

	// PRICE2
	nr_match('class="price-regular".*?class="price".*?>(.*?)<', $cache, $_price2);
	$import->price2($_price2);

	// IMAGE
	nr_match('"image":(.*?),"review"', $cache, $_imgBlock);
	$_imgJson = json_decode($_imgBlock);
	foreach($_imgJson as $_img){
		$import->photo_insert($_img);
	}


	nr_match_all('og:image" content="(.*?)"', $cache, $_img);
	$import->photo_insert($_img);

	// STOCK
	$import->stock('YES','schema.org/InStock', $cache);

	// CATEGORIES
	nr_match('"itemListElement":\[(.*?)\]', $cache, $_categorytags_block);
	nr_match_all('"name":"(.*?)"', $_categorytags_block, $_categorytags);
	$import->categorytags_insert($_categorytags,1,1);

	// BRAND
	nr_match('property="product:brand" content="(.*?)"', $cache, $_brand);
	$import->add_brand($_brand);

	// REF
	nr_match('"sku":"(.*?)"', $cache, $_REF);
	if(is_numeric($_REF) && strlen($_REF)>8){
		$import->add_code('EAN', $_REF);
	}
	$import->add_code('REF',$_REF);
	
	
	// SUB_PRODUCTS
	nr_match('__NUXT__=.*?,variants:(.*?),', $cache, $_code_variant);
	
	nr_match_all('\;'.$_code_variant.'\.(.*?)={id:.*?ean:{value:"(.*?)"', $cache, $_table_prices);
	if(count($_table_prices[0])==0){
		nr_match_all('\;'.$_code_variant.'\["(.*?)"\].*?ean:{value:"(.*?)"', $cache, $_table_prices);
	}
    // CODE_CHILDS
    foreach ($_table_prices[0] as $_key => $_price_data){
        $sub_product = $import->createChild();

        // CODE
        $sub_product->code($_table_prices[2][$_key]);

        // TITLE
		$_subtitle = $_table_prices[1][$_key];
        $sub_product->title($_subtitle);

        // PRICE
        $_subprice = $import->price;
        $sub_product->price($_subprice);
		
		// PRICE2
        $_subprice2 = $import->price2;
        $sub_product->price2($_subprice2);
		
		// EAN
		$sub_product->add_code('EAN', $_table_prices[2][$_key]);
		
        $import->add_product($sub_product);
    }

	$import->save();
}