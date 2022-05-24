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

	if(substr_count($url, '?') > 0){
		nr_match('(.*?)\?', $url, $url);
	}

	$import->url($url);

	$cache = nr_get_content($url);

	// CODE
	nr_match("'articleId':.*?'(.*?)'", $cache, $_code);
	$import->code($_code);

	// TITLE
	nr_match('<h1 class="product--title.*?>(.*?)</h1>', $cache, $_title);
	$import->title($_title);

	// DESCRIPTION
	nr_match('class="product--description" itemprop="description">(.*?)</div>', $cache, $_description);
	$import->description($_description);	

	// PRICE 
	nr_match('itemprop="highPrice" content="(.*?)"', $cache, $_price);
	if(!$_price){
		nr_match('itemprop="price" content="(.*?)"', $cache, $_price);
	}
	$import->price($_price);

	nr_match('class="price--content.*?temprop="price" content="(.*?)"', $cache, $_price);
	$import->price($_price);
	
	// price 2
	nr_match('class="product--price price--default price--discount">.*?class="">(.*?)<', $cache, $_price2);
	$import->price2($_price2);

	// IMAGE 
	nr_match_all('data-img-original="(.*?)"', $cache, $_img);
	$import->photo_insert($_img);

	// STOCK
	$import->stock('YES',['schema.org/InStock','delivery--text-available'], $cache);

	// CATEGORIES
	nr_match_all('class="breadcrumb--title" itemprop="name">(.*?)</', $cache, $_categorytags);
	$import->categorytags_insert($_categorytags);

	// BRAND
	nr_match('itemprop="brand" content="(.*?)"', $cache, $_brand);
	$import->add_brand($_brand);

	// SHIPPING
	$import->free_shipping('class="delivery--status-icon delivery--status-shipping-free"', $cache);

	// EAN
	nr_match('itemprop="gtin.*?content="(.*?)"', $cache, $_EAN);
	$import->add_code('EAN', $_EAN);

	// REF
	nr_match('itemprop="sku">(.*?)<', $cache, $_REF);
	$import->add_code('REF', $_REF);

	// SUB_PRODUCTS
	nr_match_all('class="configurator--label">(.*?)<', $cache, $_labels_array);

	//if select
	nr_match_all('name="group\[(.*?)\].*?</option>(.*?)</select>', $cache, $_selects_array);

	// if input
	nr_match('name="group\[(.*?)\].*?</option>(.*?)</select>', $cache, $_selects_array);
	if(strpos($cache, 'class="variant--group"')) {
		nr_match_all('class="product--configurator".*?class="variant--group".*?name="group\[(.*?)\](.*?)</form>', $cache, $_selects_array);
		nr_match_all('class="product--configurator".*?class="variant--name">(.*?)<', $cache, $_labels_array);
	}

	$variants = array();
	foreach($_selects_array[2] as $_select_block){
		nr_match_all('value="(.*?)"', $_select_block, $variants[]);
	}

	$_options_array = MultiSelectComb($variants);

	// CODE_CHILDS
	if(count($variants[0]) > 1){
		foreach ($_options_array as $_options){
			$sub_product = $import->createChild();

			// DETAILS
			$_subtitle = '';
			$_params = '';
			// if input
			if(strpos($cache, 'class="variant--group"')) {
				foreach($_options as $i => $_opt){
					// TITLE
					nr_match('for="group\[.*?\]\['.$_opt.'\]" class="option--label.*?">(.*?)</label>', $_selects_array[2][$i], $_subtitle_temp);
					$_subtitle .= $_labels_array[$i]. ' ' .$_subtitle_temp.' ';

					// PARAMS
					$_params .= '&group['.$_selects_array[1][$i].']='.$_opt;
				}
			}else { 
				foreach($_options as $i => $_opt){
					// TITLE
					nr_match('value="'.$_opt.'".*?>(.*?)</option>', $_selects_array[2][$i], $_subtitle_temp);
					$_subtitle .= $_labels_array[$i]. ' ' .$_subtitle_temp.' ';

					// PARAMS
					$_params .= '&group['.$_selects_array[1][$i].']='.$_opt;
				}
			}

			// SUBCACHE
			$_subcache = nr_get_content($url.'?template=ajax'.$_params, array('usecache'=>false));

			// CODE
			nr_match('itemprop="productID" content="(.*?)"', $_subcache, $_subcode);
			$sub_product->code($_subcode);

			// TITLE
			$sub_product->title($_subtitle);

			// PRICE 
			nr_match('class="price--content content--default">.*?itemprop="price" content="(.*?)"', $_subcache, $_subprice);
			$sub_product->price($_subprice);

			// STOCK
			$sub_product->stock('YES', 'InStock', $_subcache);

			// EAN
			nr_match('itemprop="gtin.*?content="(.*?)"', $_subcache, $_EAN);
			$sub_product->add_code('EAN', $_EAN);

			// REF
			nr_match('itemprop="sku">(.*?)<', $_subcache, $_REF);
			$sub_product->add_code('REF', $_REF);

			$import->add_product($sub_product);
		}
	}
	$import->save();
}