<?php 
function details($url){
	global $import;

	$import->url($url);

	$cache = nr_get_content($url);

	// CODE
	nr_match('name="product" value="(.*?)"', $cache, $_code);
	$import->code($_code);

	// TITLE
	nr_match('<h1 class="page-title".*?>(.*?)</h1', $cache, $_title);
	$import->title($_title);

	// DESCRIPTION
	nr_match('class="product attribute description">(.*?)</table>', $cache, $_description);
	$import->description($_description);	

	// PRICE
	nr_match('id="product-price-'.$import->code.'".*?data-price-amount="(.*?)"', $cache, $_price);
	$import->price($_price);

	// IMAGE
	nr_match_all('class="gallery-placeholder__image.*?src="(.*?)"', $cache, $_img);
	$import->photo_insert($_img);

	// ADITIONAL IMAGES
	nr_match('class="gallery-placeholder__image".*?/>(.*?)</script>', $cache, $_imgBlock);
	nr_match_all('"img":"(.*?)"', $_imgBlock, $_img);
	$import->photo_insert($_img);


	// CATEGORIES
	nr_match('class="breadcrumbs".*?>(.*?)</ul>', $cache, $_categorytagsBlocks);
	nr_match_all('<a.*?>(.*?)</a>', $_categorytagsBlocks, $_categorytags);
	$import->categorytags_insert($_categorytags,1,0);

	// BRAND
	nr_match('product_brand : "(.*?)"', $cache, $_brand);
	$import->add_brand($_brand);

	// SKU
	nr_match('data-product-sku="(.*?)"', $cache, $_SKU);
	$import->add_code('REF', $_SKU);
	
	// SCORE
	nr_match('"ratingValue":"(.*?)"',$cache, $_score);
	$import->add_score($_score, 5);

	// N RATINGS
	nr_match('"reviewCount":"(.*?)"',$cache, $_n_comments);
	$import->add_n_comments($_n_comments);

	// SUB_PRODUCTS
	nr_match('"spConfig":(.*?)"gallerySwitchStrategy"', $cache, $_table_prices);
	$_table_prices = json_decode(substr(trim($_table_prices), 0, -1),true);
	
	// CODE_CHILDS
	foreach ($_table_prices["optionPrices"] as $_key => $_price_data){
		$sub_product = $import->createChild();

		// CODE
		$sub_product->code($_key);

		// TITLE
		$subtitle = '|';
		foreach($_table_prices["attributes"] as $attribute){
			foreach($attribute["options"] as $option){
				if(array_search($_key, $option["products"]) !== false) {
					$subtitle .= $option["label"]. '|';
				}
			}
		}

		$sub_product->title($subtitle);

		// PRICE
		$price = $_price_data["finalPrice"]["amount"];
		$sub_product->price($price);

		// SKU
		$sub_product->add_code('REF',  $_table_prices["sku"][$_key]);		

		// STOCK
		$sub_product->stock('YES','green', $_table_prices["pxAvailability"][$_key]["cssClass"]);

		$import->add_product($sub_product);
	}


	$import->save();
}