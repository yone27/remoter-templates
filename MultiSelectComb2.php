<?php 

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
	
	$import->url($url);	
	$cache = nr_get_content($url);

	// CODE
	nr_match('"products".*?"ecomm_prodid":"(.*?)"', $cache, $_code);
	$import->code($_code);

	// TITLE
	nr_match('"products".*?"name":"(.*?)"', $cache, $_title);
	$import->title($_title);

	// DESCRIPTION
	nr_match('class="content--description">(.*?)</div>', $cache, $_description);
	$import->description($_description);	

	// PRICE
	nr_match('"products".*?"price":"(.*?)"', $cache, $_price);
	$import->price($_price);

	// IMAGE
	nr_match('image-slider--slide"(.*?)image--dots image-slider--dots panel--dot-nav"', $cache, $_imgblock);
	nr_match_all('data-img-large="(.*?)"', $_imgblock, $_img);
	$import->photo_insert($_img);

	// STOCK
	$import->stock('YES','schema.org/InStock', $cache);

	// CATEGORIES
	nr_match('BreadcrumbList">(.*?)</ul>', $cache, $_categorytags_block);
	nr_match_all('<a.*?>(.*?)</a>', $_categorytags_block, $_categorytags);
	$import->categorytags_insert($_categorytags);

	// BRAND
	nr_match('"products".*?"brand":"(.*?)"', $cache, $_brand);
	$import->add_brand($_brand);

	// SCORE todo
	nr_match('itemprop="ratingValue" class="ratingValue">(.*?)<',$cache, $_score);
	$import->add_score($_score, 5);

	// N RATINGS todo 
	nr_match('class="label"><span itemprop="reviewCount">(.*?)<',$cache, $_n_comments);
	$import->add_n_comments($_n_comments);

	// REF
	$import->add_code('REF', $_code);

	// CODE_CHILDS
	nr_match('class="configurator--form selection--form">(.*?)</form', $cache, $_options);
	if(strpos($_options,'value=""')) {
		nr_match_all('name="group(.*?)".*?</option>(.*?)</select', $_options, $_optionIds);
	}else{
		nr_match_all('name="group(.*?)"(.*?)</select', $_options, $_optionIds);		
	}
	
	// Option id es el id del select
	foreach($_optionIds[2] as  $_optionId){
		nr_match_all('<option.*?value="(.*?)"', $_optionId, $_optionValues[]);
	}
	$subcodesarray = MultiSelectComb($_optionValues);
		
	foreach ($subcodesarray as $_subcodes){
		$sub_product = $import->createChild();

		// CODE
		$subcode = implode('-',$_subcodes);
		$sub_product->code($subcode);

		// TITLE
		$_subtitle = '|';
		foreach($_subcodes as $subcodetitles){
			nr_match('value="'.$subcodetitles.'">(.*?)</option', $cache, $_subtitlepart);
			$_subtitle .= $_subtitlepart . '|';
		}
		$sub_product->title($_subtitle);

		// main url
		$params = "";
		foreach($_subcodes as $_key => $subcode){
			$params .= '&group'.$_optionIds[1][$_key].'='.$subcode;				
		}
		$urlAjax = $url .'?'.$params.'&template=ajax';
		$subcache = nr_get_content($urlAjax, array('usecache'=>false));
		// PRICE
		nr_match('itemprop="price" content="(.*?)"', $subcache, $_price);
		$sub_product->price($_price);
		
		// PRICE 2
		nr_match('class="price--line-through">(.*?)</span', $subcache, $_price2);
		$sub_product->price2($_price2);

		// STOCK
		$sub_product->stock('YES','schema.org/InStock', $subcache);

		// REF
		nr_match('itemprop="sku">(.*?)</span>', $cache, $_REF);
		$sub_product->add_code('REF',  $_REF);		

		$import->add_product($sub_product);
	}

	$import->save();
}