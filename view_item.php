<?php 
function details($url){
	global $import;

	$import->url($url);

	$cache = nr_get_content($url);

	// CODE
	nr_match('itemprop="sku" content="(.*?)"', $cache, $_code);
	$import->code($_code);

	// TITLE
	nr_match('schema.org/Product">.*?itemprop="name" content="(.*?)"', $cache, $_title);
	$import->title($_title);

	// DESCRIPTION
	nr_match('class="product-description">(.*?)</div>', $cache, $_description);
	$import->description($_description);	

	// PRICE
	nr_match('itemprop="price" content="(.*?)"', $cache, $_price);
	$import->price($_price);

	// IMAGE
	nr_match('schema.org/Product">.*?itemprop="image" content="(.*?)"', $cache, $_img);
	$import->photo_insert($_img);

	// STOCK
	$import->stock('YES','schema.org/InStock', $cache);

	// CATEGORIES
	nr_match('class="breadCrumpSeparator">(.*?)</ol>', $cache, $_categorytags_block);
	nr_match_all('<span.*?>(.*?)</span>', $_categorytags_block, $_categorytags);
	$import->categorytags_insert($_categorytags);

	// BRAND
	nr_match('itemprop="brand" content="(.*?)"', $cache, $_brand);
	$import->add_brand($_brand);

	// SCORE
	nr_match('itemprop="ratingValue" content="(.*?)"',$cache, $_score);
	$import->add_score($_score, 5);

	// N RATINGS
	nr_match('itemprop="reviewCount" content="(.*?)"',$cache, $_n_comments);
	$import->add_n_comments($_n_comments);

	// REF
	nr_match('itemprop="sku" content="(.*?)"', $cache, $_REF);
	$import->add_code('REF', $_REF);

	// REF
	nr_match('itemprop="mpn" content="(.*?)"', $cache, $_MPN);
	$import->add_code('MPN', $_MPN);

	$import->save();
}

https://rubik.netrivals.com/scripts/edit.php?script_type=details&import_type=public&storeid=184452&sample_url=