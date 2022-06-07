<?php 

function details($url){
	global $import;

	$import->url($url);

	// CACHE
	$cache = nr_get_content($url);

	// CODE
	nr_match('id="product_addtocart_form".*?name="product" value="(.*?)"', $cache, $_code);
	$import->code($_code);

	// TITLE
	nr_match('"\@type":"Product".*?"name":"(.*?)"', $cache, $_title);
	$import->title($_title);

	// DESCRIPTION
	nr_match('<div class="product attribute description">(.*?)</div>', $cache, $_description);
	$import->description($_description);	

	// PRICE
	nr_match('property="product:price:amount" content="(.*?)"', $cache, $_price);
	$import->price($_price);

	// IMAGE
	nr_match('property="og:image".*?content="(.*?)"', $cache, $_img);
	$import->photo_insert($_img);

	nr_match_all('"full":"(.*?)"', $cache, $_img);
	$import->photo_insert($_img);

	// STOCK
	$import->stock('YES',['schema.org\/InStock','>ADD TO BASKET<'], $cache);

	// CATEGORIES
	nr_match('<div class="breadcrumbs">(.*?)</div>', $cache, $_categorytagsblock);
	nr_match_all('<a.*?>(.*?)</a>', $_categorytagsblock, $_categorytags);
	$import->categorytags_insert($_categorytags);

	// BRAND
	nr_match('data-brand="(.*?)"', $cache, $_brand);
	$import->add_brand($_brand);

	// REF
	nr_match('"sku":"(.*?)"', $cache, $_REF);
	$import->add_code('REF', $_REF);

	// SUB_PRODUCTS
	nr_match('"jsonConfig":(.*?)"jsonSwatchConfig"', $cache, $_details_block);
	$_details_block = json_decode(trim(substr(trim($_details_block),0,-1)),true);

	nr_match('gallerySwitchStrategy.*?spConfig":(.*?),"superSelector"', $cache, $sub_block);
	$sub_block = json_decode($sub_block,true);

	foreach ($_details_block["optionPrices"] as $_key => $_subdetails){
		$sub_product = $import->createChild();

		// CODE
		$sub_product->code($_key);

		// TITLE
		$_subtitle = '|';
		foreach($_details_block["attributes"] as $i => $_attributes){
			$label = $_attributes["label"];
			foreach($_attributes["options"] as $k => $_options){
				if(array_search($_key,$_options["products"])!==false){
					$_subtitle .= $label.': '.$_options["label"].'|';
				}else{
					foreach ($sub_block["index"] as $key => $sub_product_title){  
						foreach($sub_product_title as $sub_product_titles){
							if($key == $sub_product->code){
								nr_match('"value_index":"'.$sub_product_titles.'","label":"(.*?)",', $cache, $_subtitle_part);
								$_subtitle = $label.': '.$_subtitle_part.'|';
							}
						}
					}
				}
			}
		}
		$sub_product->title($_subtitle);	

		// PRICE
		$sub_product->price($_subdetails["finalPrice"]["amount"]);

		// OLD PRICE
		$sub_product->price2($_subdetails["oldPrice"]["amount"]);

		// IMAGE
		foreach($_details_block["images"] as $k => $images){
			if($k==$_key){
				foreach($images as $image){
					$sub_product->photo_insert($image["full"]);
				}				
			}
		}

		// STOCK
		//dump($_details_block["optionsStockQty"]);
		dump($_details_block["stockQty"][$_key]);
		$sub_product->stock('NO', 'Nog 1', $_details_block["optionsStockQty"][$_key]);

		$import->add_product($sub_product);
	}

	$import->save();
}