<?php 

function nr_url_utf8($url){
   $url_parsed = nr_parse_url($url);
   $url_path = utf8_decode($url_parsed['path']);
   $url = $url_parsed['scheme'].'://'.$url_parsed['host'].$url_path;
   return $url;
}

function details($url){
   global $import;
   $import->url($url);
   $url = nr_url_utf8($url);
   $cache = nr_get_content($url);

   // CODE
   nr_match('"Viewed Product",.*?"productId":(.*?),', $cache, $_code);
   $import->code($_code);

   // TITLE
   nr_match('class="h2 product-single__title">(.*?)</', $cache, $_title);
   $import->title($_title);

   // DESCRIPTION
   nr_match('class="product-single__description rte">(.*?)</div', $cache, $_description);
   $import->description($_description);

   // PRICE
   nr_match('"Viewed Product",.*?"price":"(.*?)"', $cache, $_price);
   $import->price($_price);

   // IMAGE
   nr_match_all('data-photoswipe-src="(.*?)"', $cache, $_imgaes);
   foreach($_imgaes as $img){
      $import->photo_insert('https:'.$img);
   }

   // STOCK
   $import->stock('NO','schema.org/OutOfStock', $cache);

   // CATEGORIES
   nr_match('class="breadcrumb".*?>(.*?)</nav', $cache, $_categorytags_block);
   nr_match_all('<a.*?>(.*?)</a>', $_categorytags_block, $_categorytags);
   $import->categorytags_insert($_categorytags);

   // BRAND
   nr_match('"Viewed Product",.*?"brand":"(.*?)"', $cache, $_brand);
   $import->add_brand($_brand);

   // EAN
   nr_match('"gtin.* ?": "(.*?)"', $cache, $_EAN);
   $import->add_code('EAN', $_EAN);

   // REF
   nr_match('"Viewed Product",.*?"sku":"(.*?)"', $cache, $_SKU);
   $import->add_code('REF', $_SKU);

   // N RATINGS
   nr_match("id='judgeme_product_reviews'.*?data-auto-install='false'>(.*?)</div>",$cache, $_n_comments_block);
   nr_match("itemprop='reviewCount' content='(.*?)'",$_n_comments_block, $_n_comments);
   $import->add_n_comments($_n_comments);

   // SCORE
   nr_match("itemprop='ratingValue' content='(.*?)'",$_n_comments_block, $_score);
   $import->add_score($_score, 5);

   // SUB_PRODUCTS todo
   nr_match('id="VariantsJson-'.$import->code.'".*?>(.*?)</', $cache, $_table_prices);
   $_table_prices = json_decode($_table_prices, true);

   if(count($_table_prices) > 1) {
      // CODE_CHILDS
      foreach ($_table_prices as $_key => $_price_data){
         $sub_product = $import->createChild();

         // CODE
         $sub_product->code($_price_data["id"]);

         // TITLE
         $sub_product->title($_price_data["title"]);

         // PRICE
         $sub_product->price($_price_data["price"] / 100);

         // PRICE2
         $sub_product->price2($_price_data["compare_at_price"] / 100);  

         // IMAGE
         $subimg = $_price_data["featured_image"]["src"];
         if(!is_null($subimg)){
            $sub_product->photo_insert($subimg);
         }

         // STOCK
         if($_price_data["available"]){
            $sub_product->stock('YES');
         }else{
            $sub_product->stock('NO');
         }

         // SCORE
         $sub_product->add_score($_score, 5);

         // N RATINGS
         $sub_product->add_n_comments($_n_comments);

         // EAN
         $sub_product->add_code('EAN', $_price_data["barcode"]);

         // REF
         $sub_product->add_code('REF', $_price_data["sku"]);

         $import->add_product($sub_product);
      }
   }


   $import->save();
}