<?php 
function details($url){
	global $import;

	$import->url($url);

	$cache = nr_get_content($url);

	// CODE
	nr_match('data-container_id="(.*?)"', $cache, $code);
	$import->code($code);

	// TITLE
	nr_match('<h1 class="name" itemprop="name".*?>(.*?)</', $cache, $_title);
	$import->title($_title);
	
	$import->format("test");

	// DESCRIPTION
	nr_match('id="tab-description">(.*?)</div', $cache, $_description);
	$import->description($_description);
	
	// PRICE
	nr_match('itemprop="price">(.*?)<', $cache, $_price);
	$import->price($_price);

	// IMAGE
	nr_match('itemprop="image".*?src="(.*?)"', $cache, $_img);
	$import->photo_insert($_img);

	// ADITIONAL IMAGES
	nr_match_all('class="min-photo".*?src="(.*?)"', $cache, $_img);
	$import->photo_insert($_img);

	// STOCK
	$import->stock('YES','schema.org/InStock', $cache);

	// CATEGORIES
	nr_match_all('ListItem">.*?itemprop="name">(.*?)<', $cache, $_categorytags);
	$import->categorytags_insert($_categorytags, 0, 1);

	// BRAND
	nr_match('title-brand-product">(.*?)</', $cache, $_brand);
	$import->add_brand($_brand);

	// MPN
	nr_match('itemprop="mpn" content="(.*?)"', $_price_data, $_submpn);
	$import->add_code('MPN',  $_submpn);		


	// SUB_PRODUCTS
	nr_match('class="mt10 mb0 table produits">.*?<tbody>(.*?)</tbody>', $cache, $_table_prices_block);
	nr_match_all('tr>(.*?)</tr>', $_table_prices_block, $_table_prices);

	// CODE_CHILDS
	foreach ($_table_prices as $_price_data){
		$sub_product = $import->createChild();

		// CODE todo
		nr_match('class="fancybox mr5".*?rel="(.*?)"', $_price_data, $_subcode);
		$_subcode= 	str_replace("produit", "", $_subcode);

		$sub_product->code($_subcode);

		// TITLE
		nr_match('class="fancybox mr5" title="(.*?)"', $_price_data, $subTitle);
		$_subTitle = str_replace($import->title, '', $subTitle);
		$sub_product->title($_subTitle);

		// PRICE
		nr_match('itemprop="price" content="(.*?)"', $_price_data, $subPrice);
		$sub_product->price($subPrice);

		// OLD PRICE
		nr_match('<span class="striked">(.*?)</span>', $_price_data, $_price2);
		$sub_product->price2($_price2);

		// STOCK
		$sub_product->stock('YES','schema.org/InStock', $_price_data);

		// MPN
		nr_match('itemprop="mpn" content="(.*?)"', $_price_data, $_submpn);
		$sub_product->add_code('MPN',  $_submpn);		

		$import->add_product($sub_product);
	}
	$import->save();
}