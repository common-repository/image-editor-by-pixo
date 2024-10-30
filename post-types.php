<?php

add_action( 'init', 'pixoeditor__registerLibraries' );
function pixoeditor__registerLibraries() {
   pixoeditor__registerStickersLibraries();
}

function pixoeditor__registerStickersLibraries() {
   $labels = array(
      'name'               => _x( 'Pixo Stickers', '' ),
      'singular_name'      => _x( 'Pixo Sticker', '' ),
      'add_new'            => _x( 'Add New', '' ),
      'add_new_item'       => __( 'Add New Stickers Library' ),
      'edit_item'          => __( 'Edit Stickers' ),
      'new_item'           => __( 'New Stickers Library' ),
      'all_items'          => __( 'All Stickers Libraries' ),
      'view_item'          => __( 'View Stickers Library' ),
      'search_items'       => __( 'Search Stickers Libraries' ),
      'not_found'          => __( 'No Stickers Libraries found' ),
      'not_found_in_trash' => __( 'No Stickers Libraries found in the Trash' ),
      'menu_name'          => 'Pixo Stickers'
   );
   $args = array(
      'labels'        => $labels,
      'description'   => 'Pixo Stickers Libraries',
      'public'        => true,
      'menu_position' => PIXOEDITOR_MENU_POSITION + 1,
      'menu_icon'     => 'dashicons-heart',
      'supports'      => array( 'title', 'editor', 'revisions', 'page-attributes' ),
      'has_archive'   => false,
      'hierarchical'  => false,
   );
   register_post_type( PIXOEDITOR_POST_TYPE_STICKERS, $args );
}

add_filter( 'tiny_mce_before_init', 'pixoeditor__tinyMceBeforeInit' );
function pixoeditor__tinyMceBeforeInit( $in ) {
   if( get_post_type() == PIXOEDITOR_POST_TYPE_STICKERS && is_admin() ) {
      $in['toolbar1'] = "";
      $in['toolbar2'] = "";
      $in['plugins'] = "wordpress,wpview,wpgallery";
      $in['toolbar'] = false;
   }
   return $in; 
}

add_filter( 'wp_editor_settings', 'pixoeditor__editorSettings' );
function pixoeditor__editorSettings( $settings ) {
   $post_type = get_post_type();
   if (
      is_admin() && (
         $post_type == PIXOEDITOR_POST_TYPE_STICKERS
      )
   ) {
      $settings[ 'quicktags' ] = false;
   }
   return $settings;
}

add_filter( 'default_content', 'pixoeditor__getDefaultPostContent', 10, 2 );
function pixoeditor__getDefaultPostContent( $content = '', $post ) {
   switch( $post->post_type ) {
   case PIXOEDITOR_POST_TYPE_STICKERS:
      return '[gallery]';
   default:
      return $content;
   }
}

add_action( 'edit_form_top', 'pixoeditor__editFormTop' );
function pixoeditor__editFormTop( $post ) {
   switch( $post->post_type ) {
   case PIXOEDITOR_POST_TYPE_STICKERS:
      ?>
      <style type="text/css">
      #wp-content-editor-tools,
      #wp_delgallery,
      .mce-btn[role="button"][aria-label="Remove"] { display: none }
      </style>
      <script>
      window.addEventListener('load', function () {
         const win = frames[0];
         const doc = win.document;
         const sel = win.getSelection();
         const rng = doc.createRange();

         rng.selectNodeContents(doc.body);
         sel.removeAllRanges();
         sel.addRange(rng);

         doc.body.setAttribute('contenteditable', 'false');

         const style = doc.head.appendChild(document.createElement('style'));
         style.innerHTML = '.wpview-error p:after{ content: "Click the Edit button to add Stickers to this library" }';

         const edit = wp.media.gallery.edit;
         wp.media.gallery.edit = function (shortcode) {
            setTimeout(function () {
               if (['[gallery]', '[gallery ids=""]'].includes(shortcode)) {
                  (
                     document.getElementById('menu-item-gallery-library')
                           || document.body.querySelector('.media-menu-item:last-child')
                  ).click();
               }
            });
            return edit.apply(this, arguments);
         }
      });
      </script>
      <?php
      break;
   }
}

function pixoeditor__copyMenuSeparator( $src ) {
   $copy = array();
   foreach ($src as $value) {
      array_push($copy, $value);
   }
   return $copy;
}

add_action( 'admin_menu', 'pixoeditor__addMenuSeparators', 1 );
function pixoeditor__addMenuSeparators() {
   global $menu;
   $separator  = pixoeditor__copyMenuSeparator( $menu[ 4 ] );
   $menu[ PIXOEDITOR_MENU_POSITION - 1 ] = $separator;
}
