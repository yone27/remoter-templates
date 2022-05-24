<?php 
function details($url){
	global $import;

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

	//https://rubik.netrivals.com/scripts/edit.php?script_type=details&import_type=public&storeid=31253&sample_url=https://www.parrotessentials.co.uk/bird-kabob-parrot-chips-pack-of-20/