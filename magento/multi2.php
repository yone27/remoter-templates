<?php 
function details($url){
	global $import;

	$import->url($url);

	$cache = nr_get_content($url);

	// CODE
	nr_match('name="product" value="(.*?)"', $cache, $_code);
	$import->code($_code);

	nr_match('"product":{.*?"id":"(.*?)"', $cache, $_code);
	$import->code($_code);

	// TITLE
	nr_match('<h1.*?>(.*?)</h1>', $cache, $_title);
	$import->title($_title);

	// DESCRIPTION
	nr_match('description__first-section-text">(.*?)</div>', $cache, $_description);
	$import->description($_description);	

	// PRICE
	nr_match('itemprop="price" content="(.*?)"', $cache, $_price);
	$import->price($_price);

	// OLD PRICE
	nr_match('class="old-price flex relative.*?class="price">(.*?)</span>', $cache, $_price2);
	$import->price2($_price2);

	// IMAGE
	nr_match_all('"full":"(.*?)"', $cache, $_img);
	$import->photo_insert($_img);

	// STOCK
	$import->stock('YES', ['schema.org\/InStock','>Auf Lager<'], $cache);

	// CATEGORIES
	nr_match('</div></footer>.*?"itemListElement":(.*?)}</', $cache, $_categorytagsBlock);
	nr_match_all('"ListItem".*?"name":"(.*?)"', $_categorytagsBlock, $_categorytags);
	$import->categorytags_insert($_categorytags,1,1);

	// BRAND
	nr_match('itemprop="brand".*?itemprop="name" content="(.*?)"', $cache, $_brand);
	$import->add_brand($_brand);

	// EAN
	nr_match('itemprop="gtin" content="(.*?)"', $cache, $_EAN);
	$import->add_code('EAN',$_EAN);

	// REF
	nr_match('itemprop="sku" content="(.*?)"', $cache, $_REF);
	$import->add_code('REF', $_REF);

	// SUB_PRODUCTS
	nr_match('ata: Object.assign\((.*?), { productId:', $cache, $_details_block);
	$_details_block = json_decode($_details_block,true);
	nr_match('swatchConfig.*?data:(.*?)};', $cache, $_sizesBlock);
	$_sizes = json_decode($_sizesBlock,true);

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
					$subtitle_part = $_sizes[$i][$_options["id"]]["promo_size_eu"];
					if(is_null($subtitle_part)){
						//$subtitle_part = $_sizes[$i][$_options["id"]]["label"];
						$subtitle_part = $_options["label"];
					}
					$_subtitle .= $label.': '.$subtitle_part.'|';

					// REF
					nr_match('"size":"'.$subtitle_part.'","sku":"(.*?)"', $cache, $_subSku);
					$sub_product->add_code('REF',$_subSku);

					if($_options["isInStock"] === false){
						$sub_product->stock('NO');
					}else{
						$sub_product->stock('YES');
					}
				}
			}
		}
		$sub_product->title($_subtitle);	

		// PRICE
		$sub_product->price($_subdetails["finalPrice"]["amount"]);

		// OLD PRICE
		$sub_product->price2($_subdetails["oldPrice"]["amount"]);

		$import->add_product($sub_product);
	}

	$import->save();
}