<?php 

$ajaxUrl = "https://animo-boutik.com/index.php?controller=product&token=";

// Funcion Permutaciones para Combinaciones
function MultiSelectComb($variants){
	if (!$variants) {
		return array(array());
	}
	$subVariant = array_shift($variants);
	$cartesianVariant = MultiSelectComb($variants);
	$result = array();
	foreach ($subVariant as $value) {
		foreach ($cartesianVariant as $p) {
			array_unshift($p, $value);
			if(count($result)<100){
				$result[] = $p;
			}else{
				break;
			}
		}
	}
	return $result;
}

function details($url){
	global $import;
	global $ajaxUrl;
	global $customHeader;

	$import->url($url);
	$cache = nr_get_content($url);

	// CODE
	nr_match('property="product:retailer_item_id" content="(.*?)"', $cache, $_code);
	$import->code($_code);

	// TITLE
	nr_match('"meta":{"title":"(.*?)"', $cache, $_title);
	$import->title($_title);

	// DESCRIPTION
	nr_match('class="product-description">(.*?)</div>', $cache, $_description);
	$import->description($_description);	

	// PRICE
	nr_match("product', value:(.*?),", $cache, $_price);
	$import->price($_price);

	// PRICE 2
	nr_match('class="regular-price">(.*?)</span>', $cache, $_price2);
	$import->price2($_price2);

	// IMAGE
	nr_match('class="js-qv-product-cover" src="(.*?)"', $cache, $_img);
	$import->photo_insert($_img);

	// ADITIONAL IMAGES
	nr_match_all('class="thumb-container".*?src="(.*?)"', $cache, $_img);
	$import->photo_insert($_img);

		// STOCK
		$import->stock('YES','schema.org/InStock', $cache);

	// CATEGORIES
	nr_match('"breadcrumb":(.*?)"count"', $cache, $_categorytags_block);
	nr_match_all('"title":"(.*?)"', $_categorytags_block, $_categorytags);
	$import->categorytags_insert($_categorytags);

	// FREE SHIPPING todo
	//$import->free_shipping('"freeshipping":true,',$cache);

	// SHIPPING COST todo
	//nr_match('"shipping_cost":"(.*?)"', $cache, $_shipping_cost);
	//$import->shipping($_shipping_cost);

	// BRAND
	nr_match('property="product:brand" content="(.*?)"', $cache, $_brand);
	$import->add_brand($_brand);

	// SCORE
	nr_match('\@type":"Product".*?AggregateRating.*?"ratingValue":(.*?),',$cache, $_score);
	$import->add_score($_score, 5);

	// N RATINGS
	nr_match('\@type":"Product".*?AggregateRating.*?"reviewCount":(.*?),',$cache, $_n_comments);
	$import->add_n_comments($_n_comments);

	// EAN
	$_basename = basename($url);
	nr_match('(.*?)\.html', $_basename, $_EAN_str);
	$_EAN_arr = explode('-', $_EAN_str);
	$_EAN = array_pop($_EAN_arr);
	if(is_numeric($_EAN)){
		$import->add_code('EAN', $_EAN);
	}

	// capturando el token
	nr_match('name="token" value="(.*?)"', $cache, $_token);
	nr_match('name="id_customization" value="(.*?)"', $cache, $_idcustomization);
	
	// colors
	nr_match_all('id="group_(.*?)">(.*?)</ul>', $cache, $_colors_block);

	$test = array();
	foreach($_colors_block[2] as $value){
		nr_match_all('class="input-radio".*?value="(.*?)"', $value, $_subcodes);
		$test[]= $_subcodes;
	}
	$permutaciones = MultiSelectComb($test);
	
	// CODE_CHILDS	
	foreach ($permutaciones as $_subcodes){
		$sub_product = $import->createChild();

		// CODE
		$sub_product->code(implode("-", $_subcodes));

		// TITLE
		$_subtitle = '|';
		$_params= '';
		
		foreach($_subcodes as $i => $_subcodesTitle){
			nr_match('id="group.*?value="'.$_subcodesTitle.'">.*?<span class="radio-label">(.*?)</span>', $cache, $_subtitlePart);
			$_subtitle .= $_subtitlePart.'|';
			$_params .='group['.$_colors_block[1][$i].']='.$_subcodesTitle.'&';
		}
		
		// TITLE
		$sub_product->title($_subtitle);
		$subUrl =  $ajaxUrl.$_token.'&id_product='.$import->code.'&id_customization='.$_idcustomization.'&'.$_params.'qty=1';
		$params = 'ajax=1&action=refresh&quantity_wanted=1';
		$subchache = nr_get_content($subUrl, array('post'=>$params,'usecache'=>false));
		
		$subchache = json_decode($subchache, true);
		str_replace(search, replace, subject)
		// IMAGES
		nr_match_all('data-image-large-src="(.*?)"', $subchache["product_cover_thumbnails"], $_img);
		$sub_product->photo_insert($_img);
		
		// PRICE
		nr_match('itemprop="price" content="(.*?)"', $subchache["product_prices"], $_subprice);
		$sub_product->price($_subprice);
		
		// STOCK
		$sub_product->stock('NO','product-unavailable', $subchache["product_add_to_cart"]);
		
		// EAN
		nr_match('class="name">ean13.*?class="value">(.*?)</dd>', $subchache["product_add_to_cart"], $_EAN);
		$sub_product->add_code('EAN', $_EAN);	
		
		$import->add_product($sub_product);
	}

	$import->save();
}