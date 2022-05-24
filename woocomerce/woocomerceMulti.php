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
	nr_match('id="mcjs">.*?"description":"(.*?)"', $cache, $_description);
	$import->description($_description);	

	// PRICE
	nr_match('\'price\': \'(.*?)\'', $cache, $_price);
	$import->price($_price);

	// IMAGE
	nr_match('og:image" content="(.*?)"', $cache, $_img);
	$import->photo_insert($_img);

	// ADITIONAL IMAGES
	nr_match('class="woocommerce-product-gallery__wrapper">(.*?)</figure', $cache, $_imgBlock);
	nr_match_all(' src="(.*?)"', $_imgBlock, $_img);
	$import->photo_insert($_img);

	// STOCK
	$import->stock('YES','class="stock in-stock"', $cache);

	// CATEGORIES
	nr_match('class="woocommerce-breadcrumb">(.*?)</nav>', $cache, $_categorytags_block);
	nr_match_all('<a.*?>(.*?)</a>', $_categorytags_block, $_categorytags);
	$import->categorytags_insert($_categorytags);

	// BRAND
	nr_match('rel="canonical" href="https://petnest.com.au/shop/brands/(.*?)/', $cache, $_brand);
	$_brand = str_replace('-', '',$_brand);
	$import->add_brand($_brand);

	// REF
	nr_match(',"sku":"(.*?)"', $cache, $_REF);
	if(!$_REF) {
		nr_match(',"sku":(.*?),', $cache, $_REF);
	}
	$import->add_code('REF', $_REF);

	// SUB_PRODUCTS
	nr_match('data-product_variations="(.*?)">', $cache, $_data_block);
	$_data_block = json_decode(html_entity_decode($_data_block), true);
	
	if(count($_data_block) > 1){
		foreach ($_data_block as $_data){
			$sub_product = $import->createChild();

			// CODE
			$sub_product->code($_data["variation_id"]);

			// TITLE
			$_subtitle = '|';
			foreach($_data["attributes"] as $subtitles){
				$_subtitle .= $subtitles . '|';
			}
			$sub_product->title($_subtitle);

			// PRICE
			$sub_product->price($_data["display_price"]);

			// PRICE 2
			$sub_product->price2($_data["display_regular_price"]);

			// IMAGE 
			$sub_product->photo_insert($_data["image"]["src"]);

			// STOCK
			if($_data["is_in_stock"]) {
				$sub_product->stock('YES');
			}else{
				$sub_product->stock('NO');
			}

			// REF
			$sub_product->add_code('REF', $_data["sku"]);		

			$import->add_product($sub_product);	
		}
	}

	$import->save();
}