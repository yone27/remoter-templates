<?php 
function details($url){
	global $import;

	$import->url($url);

	$cache = nr_get_content($url);

	// CODE
	nr_match('name="add-to-cart" value="(.*?)"', $cache, $_code);
	$import->code($_code);

		// TITLE
	nr_match('<h1.*?>(.*?)</h1>', $cache, $_title);
	$import->title($_title);	

	// DESCRIPTION
	nr_match('aria-labelledby="tab-title-description">(.*?)</div>', $cache, $_description);
	$import->description($_description);

	// PRICE
	nr_match('<p class="price(.*?)</p>', $cache, $_price_block);
	nr_match('<ins>(.*?)</ins>', $_price_block, $_price);
	if(!$_price){
		$_price = $_price_block;
	}
	$import->price($_price);

	// IMAGE
	nr_match('id="wpgis-gallery".*?>(.*?)</div', $cache, $_imgBlock);
	nr_match_all('src="(.*?)"', $_imgBlock, $img);
	$import->photo_insert($img);

	// STOCK
	$import->stock('YES', 'schema.org\/InStock', $cache);

	// CATEGORIES
	nr_match('class="woocommerce-breadcrumb">(.*?)</nav>', $cache, $_categorytags_block);
	nr_match_all('<a.*?>(.*?)</a>', $_categorytags_block, $_categorytags);
	$import->categorytags_insert($_categorytags);

	// BRAND
	nr_match('itemprop="brand">(.*?)<', $cache, $_brand);
	$import->add_brand($_brand);

	// REF
	nr_match('class="sku">(.*?)</', $cache, $_REF);
	$import->add_code('REF', $_REF);

	$import->save();
}