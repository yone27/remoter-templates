<?php 
function details($url){
	global $import;

	$import->url($url);

	$cache = nr_get_content($url,array('usecache'=>false));

	// CODE  
	nr_match('dataLayerData =.*?"productId":"(.*?)"', $cache, $_code);
	$import->code($_code);

	// TITLE
	nr_match('<h1.*?>(.*?)</h1>', $cache, $_title);
	$import->title($_title);

	// DESCRIPTION
	nr_match('"og:description" content="(.*?)"', $cache, $_description);
	$import->description($_description);	

	// PRICE
	nr_match('"productPrice":(.*?),', $cache, $_price);
	$import->price($_price);

	nr_match("'productPrice' : '(.*?)'", $cache, $_price);
	$import->price($_price);

	// IMAGE
	nr_match_all('srczoom="(.*?)"', $cache, $_img);
	$import->photo_insert($_img);

	// CATEGORIES
	nr_match('<div class="s-breadcrumbs-bar">(.*?)</div>', $cache, $_categorytags_block);
	nr_match_all('<li.*?>(.*?)</li>', $_categorytags_block, $_categorytags);
	$import->categorytags_insert($_categorytags, 1, 1);

	// STOCK
	$import->stock('NO', 'schema.org/OutStock"', $cache);

	// BRAND 
	nr_match('\'productBrand\' : \'(.*?)\'', $cache, $_brand);
	$import->add_brand($_brand);

	nr_match('"productBrand":"(.*?)"', $cache, $_brand);
	$import->add_brand($_brand);

	// REF
	nr_match('"sku:": "(.*?)"', $cache, $_REF);
	$import->add_code('REF', $_REF);

	// EAN
	nr_match('"gtin13": "(.*?)"', $cache, $_EAN);
	$import->add_code('EAN', $_EAN);

	// SUB_PRODUCTS
	nr_match('data-variants="(.*?)" data', $cache, $_data_variations_block);
	$_subproducts_block = html_entity_decode($_data_variations_block);
	$_subproducts_block_json = json_decode($_subproducts_block,true);

	// CODE_CHILDS
	foreach ($_subproducts_block_json as $_colors){
		foreach($_colors["SizeVariants"] as $_key => $_content_product){
			$sub_product = $import->createChild();

			// CODE
			$sub_product->code($_content_product["SizeVarId"]);

			// TITLE
			$subtitle = $_colors["ColourName"].'|'.$_content_product["SizeName"];
			$sub_product->title($subtitle);

			// PRICE
			$sub_product->price($_content_product["ProdSizePrices"]["SellPrice"]);

			// PRICE 2
			$sub_product->price2($_content_product["ProdSizePrices"]["RefPrice"]);

			// IMAGE
			foreach($_colors["ProdImages"]["AlternateImages"] as $AlternateImages){
				$sub_product->photo_insert($AlternateImages["ImgUrlXXLarge"]);
			}

			// STOCK
			if($_content_product["InStock"] === true){
				$sub_product->stock('YES');
			}
			else{
				$sub_product->stock('NO');
			}

			$import->add_product($sub_product);
		}
	}

	$import->save();
}