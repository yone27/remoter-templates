<?php 
function details($url){
	global $import;

	$import->url($url);

	$cache = nr_get_content($url);

	// CODE
	nr_match('id="add-to-cart-or-refresh">.*?name="id_product" value="(.*?)"', $cache, $_code);
	$import->code($_code);

	// TITLE
	nr_match('<h1.*?>(.*?)</h1>', $cache, $_title);
	$import->title($_title);

	// DESCRIPTION
	nr_match(' class="product-description">(.*?)</div>', $cache, $_description);
	$import->description($_description);	

	// PRICE
	nr_match('itemprop="price" content="(.*?)"', $cache, $_price);
	$import->price($_price);

	// IMAGE
	nr_match('class="product-cover".*?src="(.*?)"', $cache, $_img);
	$import->photo_insert($_img);

	// IMAGE 2
	nr_match_all('class="thumb js-thumb.*?".*?src="(.*?)"', $cache, $_img);
	$import->photo_insert($_img);

	// STOCK
	$import->stock('YES','https://schema.org/InStock', $cache);

	// CATEGORIES
	nr_match('{"id":"'.$import->code.'.*?category":"(.*?)"', $cache, $_categorytags);
	$_categorytags= explode('>', $_categorytags);
	$import->categorytags_insert($_categorytags);

	// BRAND 
	nr_match('{"id":"'.$import->code.'.*?brand":"(.*?)"', $cache, $_brand);
	$import->add_brand($_brand);

	// REF
	nr_match('itemprop="sku">(.*?)<', $cache, $_REF);
	$import->add_code('REF', $_REF);

	$import->save();
}
