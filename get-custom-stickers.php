<?php

function pixoeditor__getCustomStickers( $stickers_libraries, $libs_only = false, $tojson = true ) {
   $the_query = new WP_Query(
      array(
         'post_type'    => PIXOEDITOR_POST_TYPE_STICKERS,
         'post_status'  => 'publish',
         'order'        => 'ASC',
         'orderby'      => 'menu_order',
      )
   );

   $stickers = array();
   if ( $the_query->have_posts() ) {
      while ( $the_query->have_posts() ) {
         $the_query->the_post();
         $keyword = get_the_title();

         if (
            is_array( $stickers_libraries ) && (
                empty( $stickers_libraries )
                  || $stickers_libraries[0] === ''
                  || array_search( get_the_id(), $stickers_libraries ) === false
            )
         ) {
            continue;
         }

         if ( $libs_only ) {
            array_push( $stickers, array(
               'id'     => get_the_id(),
               'title'  => $keyword ? $keyword : 'Untitled library',
            ));
            continue;
         }
         
         $matches = array();
         if (preg_match('/\\[gallery ids="([^"]+)"\\]/', get_the_content(), $matches)) {
            $ids_string = $matches[1];
            $images_ids = explode(',', $ids_string);

            foreach ($images_ids as $id) {
               $caption = wp_get_attachment_caption( $id );
               array_push($stickers, array(
                  'caption'   => $caption ? $caption : get_the_title($id),
                  'src'       => wp_get_attachment_url($id),
                  'keywords'  => array($keyword),
               ));
            }
         }
      }
   }

   wp_reset_postdata();

   return $tojson ? json_encode( $stickers ) : $stickers;
}

