<?php 
function details($url){
	global $import;

	$import->url($url);

	$cache = nr_get_content($url);

	nr_match('name="add-to-cart" value="(.*?)"', $cache, $_code);
	$import->code($_code);

	// TITLE
	nr_match('<h1 itemprop="name.*?>(.*?)</h1>', $cache, $_title);
	$import->title($_title);

	nr_match('<h1 class="page-title">(.*?)</h1>', $cache, $_title);
	$import->title($_title);

	// DESCRIPTION
	nr_match('property="og:description" content="(.*?)"', $cache, $_description);
	$import->description($_description);	

	// PRICE
	nr_match('\@type":"Offer".*?"price":"(.*?)"', $cache, $_price);
	$import->price($_price);

	// OLD PRICE
	nr_match('class="blocco-prezzo".*?<del.*?>(.*?)</del>', $cache, $_price2);
	$import->price2($_price2);

	// IMAGE
	nr_match_all('product-gallery__image".*?href="(.*?)"', $cache, $_img);
	$import->photo_insert($_img);

	nr_match('class="product-thumbnails thumbnails">(.*?)class="product-details">', $cache, $_img_block);
	nr_match_all('data-lazy-src="(.*?)"', $_img_block, $_img);
	$import->photo_insert($_img);

	// STOCK
	$import->stock('YES',array('schema.org\/InStock','schema.org/InStock'), $cache);

	// CATEGORIES
	nr_match_all('itemprop="name">(.*?)<', $cache, $_categorytags);
	$import->categorytags_insert($_categorytags);

	// FREE SHIPPING
	$import->free_shipping('class="blocco-prezzi-spedizione spedizione-gratuita"',$cache);

	// SHIPPING COST
	nr_match('spedizione-standard">.*?<span>(.*?)</span>', $cache, $_shipping_cost);
	$import->shipping($_shipping_cost);

	// BRAND
	nr_match('"og:brand" content="(.*?)"', $cache, $_brand);
	$import->add_brand($_brand);

	// REF
	nr_match('"\@type":"Product".*?"sku":(.*?),', $cache, $_ref);
	$import->add_code('REF', $_ref);

	// EAN
	nr_match('"\@type":"Product".*?,"gtin13":"(.*?)"', $cache, $_EAN);
	$import->add_code('EAN', $_EAN);

	// SUB_PRODUCTS
	nr_match('data-product_variations="(.*?)"', $cache, $_subproduct);
	$_table_prices=json_decode(html_entity_decode($_subproduct),true);
	
	// CODE_CHILDS
	foreach ($_table_prices as $_content_product){
		$sub_product = $import->createChild();

		// CODE
		$sub_product->code($_content_product["variation_id"]);

		// TITLE
		$subtitle='';
		foreach($_content_product["attributes"] as $key => $subtitle_part)
		{
			$subtitle.=$subtitle_part.'|';
		}
		$sub_product->title($subtitle);

		// PRICE
		$sub_product->price($_content_product["display_price"]);

		// OLD PRICE
		$sub_product->price2($_content_product["display_regular_price"]);

		// IMAGE
		$sub_product->photo_insert($_content_product["image"]["url"]);

		// STOCK
		if($_content_product["variation_is_active"]==true)
			$sub_product->stock('YES');
		else
			$sub_product->stock('NO');

		// REF
		$sub_product->add_code('REF',  $_content_product["sku"]);


		$import->add_product($sub_product);
	}

	$import->save();
}