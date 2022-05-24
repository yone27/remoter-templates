<?php 

function details($url){
   global $import;

   $import->url($url);

   $cache = nr_get_content($url);

   // CODE
   nr_match('"rid":(.*?)}', $cache, $_code);
   $import->code($_code);

   // TITLE
   nr_match('<h1 class="product-title".*?>(.*?)</h1>', $cache, $_title);
   $import->title($_title);

   // DESCRIPTION
   nr_match('property="og:description" content="(.*?)"', $cache, $_description);
   $import->description($_description);   

   // PRICE
   nr_match('okendo-reviews-json-ld -->.*?"price":"(.*?)"', $cache, $_price);
   $import->price($_price);

   // IMAGE
   nr_match('og:image".*?content="(.*?)"', $cache, $_img);
   $import->photo_insert($_img);

   // STOCK
   $import->stock('YES','schema.org/InStock', $cache);

   // CATEGORIES
   nr_match('class="breadcrumbs-container".*?>(.*?)</nav>', $cache, $_categorytags_block);
   nr_match_all('<a.*?>(.*?)</', $_categorytags_block, $_categorytags);
   $import->categorytags_insert($_categorytags);

   // SCORE
   nr_match('okendo-reviews-json-ld -->.*?"ratingValue": "(.*?)"',$cache, $_score);
   $import->add_score($_score, 5);

   // N RATINGS  
   nr_match('okendo-reviews-json-ld -->.*?"ratingCount": "(.*?)"',$cache, $_n_comments);
   $import->add_n_comments($_n_comments);

   // BRAND
   nr_match('ShopifyAnalytics.lib.page.*?"brand":"(.*?)"', $cache, $_brand);
   $import->add_brand($_brand);

   // EAN
   nr_match('"gtin12": "(.*?)"', $cache, $_EAN);
   $import->add_code('EAN', $_EAN);

   // MPN
   nr_match('"mpn": "(.*?)"', $cache, $_mpn);
   $import->add_code('MPN', $_mpn);

   // sub products
   nr_match('data-section-id="static-product" data-section-data>(.*?)</', $cache, $_dataProducts);
   $_data = json_decode($_dataProducts, true);
   $_table_prices = $_data['product']['variants'];
   
   if(count($_table_prices) > 1){
      // CODE_CHILDS
      foreach ($_table_prices as $key => $_price_data){
         $sub_product = $import->createChild();
         // CODE
         $sub_product->code($_price_data["id"]);
         // TITLE
         $sub_product->title($_price_data["title"]);
         // PRICE
         $sub_product->price($_price_data["price"]/100);

         // OLD PRICE
         $sub_product->price2($_price_data["compare_at_price"]/100);

         // IMAGE
         $sub_product->photo_insert($_price_data["featured_image"]["src"]);
         // STOCK
         if(!$_price_data["available"]){
            $sub_product->stock('NO');
         }
         // EAN
         $sub_product->add_code('EAN', $_price_data["barcode"]);
         $import->add_product($sub_product);

         // REF
         $sub_product->add_code('REF', $_price_data["sku"]);
      }
   }

   $import->save();
}

