<?php
/*
Plugin Name: Image Editor by Pixo
Plugin URI: https://pixoeditor.com
Description: Provides Pixo as a replacement of the default image editor in your WordPress installation, as well as integrates to your website front-end
Version: 2.3.5
*/

define( 'PIXOEDITOR_PAGE_PARENT',   'admin.php' );
define( 'PIXOEDITOR_PAGE_NAME',     'image-editor-pixo' );
define( 'PIXOEDITOR_OPTIONS_URL',   PIXOEDITOR_PAGE_PARENT . '?page=' . PIXOEDITOR_PAGE_NAME );
define( 'PIXOEDITOR_QUERY_VAR_TAB', 'tab' );
define( 'PIXOEDITOR_TAB_ADMIN',     'admin' );
define( 'PIXOEDITOR_TAB_FRONTEND',  'frontend' );
define( 'PIXOEDITOR_SETTINGS_VIEW', 'settings' );
define( 'PIXOEDITOR_MENU_POSITION', 42 );

define( 'PIXOEDITOR_OPTION_APIKEY',       '__pixoeditor_apikey' );
define( 'PIXOEDITOR_OPTION_MANAGE',       '__pixoeditor_manage' );
define( 'PIXOEDITOR_OPTION_USAGE',        '__pixoeditor_usage' );
define( 'PIXOEDITOR_OPTION_CONFIG',       '__pixoeditor_config' );
define( 'PIXOEDITOR_OPTION_IMAGE_SIZE',   '__pixoeditor_image_size' );
define( 'PIXOEDITOR_OPTION_STICKERS',     '__pixoeditor_stickers' );
define( 'PIXOEDITOR_OPTION_FRONTEND_CONFIG',    '__pixoeditor_frontend_config' );
define( 'PIXOEDITOR_OPTION_FRONTEND_GLOBAL',    '__pixoeditor_frontend_is_global' );
define( 'PIXOEDITOR_OPTION_FRONTEND_SELECTOR',  '__pixoeditor_frontend_selector' );
define( 'PIXOEDITOR_OPTION_FRONTEND_DOWNLOAD',  '__pixoeditor_frontend_download' );
define( 'PIXOEDITOR_OPTION_FRONTEND_STICKERS',  '__pixoeditor_frontend_stickers' );
define( 'PIXOEDITOR_KEY_NONCE',           '__pixoeditor_nonce' );
define( 'PIXOEDITOR_PARAM_BATCH',         '__pixoeditor_batch' );
define( 'PIXOEDITOR_PARAM_MULTI',         '__pixoeditor_multi' );

define( 'PIXOEDITOR_ACTION_SAVE_OVERWRITE',        'SAVE_OVERWRITE' );
define( 'PIXOEDITOR_ACTION_SAVE_NEW',              'SAVE_NEW' );
define( 'PIXOEDITOR_ACTION_SAVE_OVERWRITE_UPDATE', 'SAVE_OVERWRITE_UPDATE' );
define( 'PIXOEDITOR_ACTION_SAVE_NEW_UPDATE',       'SAVE_NEW_UPDATE' );

define( 'PIXOEDITOR_IMAGE_SIZE_THUMBNAIL',   'thumbnail' );
define( 'PIXOEDITOR_IMAGE_SIZE_OTHER',       'other' );
define( 'PIXOEDITOR_IMAGE_SIZE_ALL',         'all' );
define( 'PIXOEDITOR_IMAGE_SIZE_REMEMBER',    'remember' );

define( 'PIXOEDITOR_POST_TYPE_STICKERS',  'pixoeditor-stickers' );


include( dirname( __FILE__ ) . '/post-types.php' );
include( dirname( __FILE__ ) . '/get-custom-stickers.php' );
include( dirname( __FILE__ ) . '/frontend.php' );


/****************************************************** PLUGIN INITIALIZATION */

add_action( 'admin_init', 'pixoeditor__onInit' );
function pixoeditor__onInit() {
   if ( ! pixoeditor__getOption( PIXOEDITOR_OPTION_USAGE ) ) {
      pixoeditor__addOption( PIXOEDITOR_OPTION_USAGE,   '["edit_posts"]' );
   }
   if (!pixoeditor__getOption(PIXOEDITOR_OPTION_MANAGE)) {
      pixoeditor__addOption(PIXOEDITOR_OPTION_MANAGE, '["install_plugins"]');
   }
}


/*************************************************** IMAGE EDITOR INTEGRATION */

add_filter( 'wp_image_editors', 'pixoeditor__bypassBackendLibraryDetection' );
function pixoeditor__bypassBackendLibraryDetection( $image_editors ) {
   if ( pixoeditor__currentUserCan( PIXOEDITOR_OPTION_USAGE ) ) {
      array_push( $image_editors, 'PixoEditor_Image_Editor' );
   }
   return $image_editors;
}

class PixoEditor_Image_Editor {
   public static function load() {
      return true;
   }
   public static function test( $args = array() ) {
      return true;
   }
   public static function supports_mime_type( $mime_type ) {
      return true;
   }
   public function multi_resize() {
      return array();
   }
}

add_action( 'admin_head', 'pixoeditor__registerImageEditor' );
function pixoeditor__registerImageEditor() {
   $path = array_reverse( explode( '/', $_SERVER[ 'REQUEST_URI' ] ) );
   if (
      ! pixoeditor__getOption( PIXOEDITOR_OPTION_APIKEY )
            and ! isset( $_POST[ PIXOEDITOR_OPTION_APIKEY ] )
            and $path[0] !== PIXOEDITOR_OPTIONS_URL
   ) {
      // Show notification to user asking him to put his API key
      add_action( 'admin_notices', 'pixoeditor__showCompleteInstallationNotification' );
   }
}

add_action( 'admin_print_styles', 'pixoeditor__printAdminStyles' );
function pixoeditor__printAdminStyles() {
   wp_enqueue_style( 'pixoeditor__admin', plugin_dir_url( __FILE__ ) . 'admin.css', array( 'wp-jquery-ui-dialog' ), '1.5' );
}

add_action( 'admin_print_scripts', 'pixoeditor__printAdminScripts' );
function pixoeditor__printAdminScripts() {
   if ( ! pixoeditor__currentUserCan( PIXOEDITOR_OPTION_USAGE ) ) {
      return;
   }
   wp_enqueue_script( 'pixoeditor__bridge', 'https://pixoeditor.com/editor/scripts/bridge.m.js' );
   wp_enqueue_script( 'pixoeditor__admin', plugin_dir_url( __FILE__ ) . 'admin.js', array( 'media-models', 'jquery-ui-dialog' ), '2.2.1' );
}

// Backward-compatibility with WP < 4.5
add_action( 'admin_footer', 'pixoeditor__printInlineScripts' );
function pixoeditor__printInlineScripts() {
   global $wp_version;

   if ( ! pixoeditor__currentUserCan( PIXOEDITOR_OPTION_USAGE ) ) {
      return;
   }

   echo "<script>
      Pixo = window.Pixo || {};
      Pixo.WP_MINOR_VERSION = parseFloat('$wp_version');
      Pixo.APIKEY = '" . pixoeditor__getOption( PIXOEDITOR_OPTION_APIKEY ) . "';
      Pixo.CONFIG = JSON.parse( JSON.parse('" . pixoeditor__getOption( PIXOEDITOR_OPTION_CONFIG ) . "' || null) );
      Pixo.LOCALE = '" . str_replace( '_', '-', get_locale() ) . "';
      Pixo.OPTIONS_URL = '" . PIXOEDITOR_OPTIONS_URL . "';
      Pixo.ACTIONS     = Pixo.ACTIONS || {};
      Pixo.ACTIONS." . PIXOEDITOR_ACTION_SAVE_NEW . " = '" . PIXOEDITOR_ACTION_SAVE_NEW . "';
      Pixo.ACTIONS." . PIXOEDITOR_ACTION_SAVE_OVERWRITE . " = '" . PIXOEDITOR_ACTION_SAVE_OVERWRITE . "';
      Pixo.ACTIONS." . PIXOEDITOR_ACTION_SAVE_NEW_UPDATE . " = '" . PIXOEDITOR_ACTION_SAVE_NEW_UPDATE . "';
      Pixo.ACTIONS." . PIXOEDITOR_ACTION_SAVE_OVERWRITE_UPDATE . " = '" . PIXOEDITOR_ACTION_SAVE_OVERWRITE_UPDATE . "';
      Pixo.IMAGE_SIZES = {
         " . PIXOEDITOR_IMAGE_SIZE_THUMBNAIL . ": '" . PIXOEDITOR_IMAGE_SIZE_THUMBNAIL . "',
         " . PIXOEDITOR_IMAGE_SIZE_OTHER . ": '" . PIXOEDITOR_IMAGE_SIZE_OTHER . "',
         " . PIXOEDITOR_IMAGE_SIZE_ALL . ": '" . PIXOEDITOR_IMAGE_SIZE_ALL . "',
         " . PIXOEDITOR_IMAGE_SIZE_REMEMBER . ": '" . PIXOEDITOR_IMAGE_SIZE_REMEMBER . "',
         optionname: '" . PIXOEDITOR_OPTION_IMAGE_SIZE . "',
      };
      Pixo.IMAGE_SIZE = '" . get_option( PIXOEDITOR_OPTION_IMAGE_SIZE ) . "';
      Pixo.STICKERS = JSON.parse(
         '" . pixoeditor__getCustomStickers( explode( ',', get_option( PIXOEDITOR_OPTION_STICKERS ) ) ) . "' || '[]'
      );
      
      " . pixoeditor__printBatchEditScript(PIXOEDITOR_PARAM_BATCH) . pixoeditor__printBatchEditScript(PIXOEDITOR_PARAM_MULTI) . "
   </script>";
}

function pixoeditor__printBatchEditScript($param) {
   if (!isset($_GET[ $param ]) || !$_GET[ $param ]) {
      return '';
   }

   $args = json_encode(array_map('pixoeditor__resolveImageSrc', explode(',', $_GET[$param])));
   $method = $param === PIXOEDITOR_PARAM_BATCH ? 'batchEdit' : 'editMultiple';

   return "
         Pixo.$method( $args, function () {
            const params = Pixo.getSearchParams();
            delete params[ '$param' ];
            location.search = Pixo.buildSearchParams( params );
         });
   ";
}

add_action( 'wp_ajax_pixoeditor__uploadFiles', 'pixoeditor__uploadFiles' );
function pixoeditor__uploadFiles() {
   $files = array();
   $ids = json_decode( $_POST[ 'ids' ] );
   $target = $_POST[ PIXOEDITOR_OPTION_IMAGE_SIZE ];

   if ( $_POST[ PIXOEDITOR_IMAGE_SIZE_REMEMBER ] === 'true' ) {
      update_option( PIXOEDITOR_OPTION_IMAGE_SIZE, sanitize_text_field( $target ) );
   }
   
   foreach ( $ids as $i => $id ) {
      array_push(
         $files,
         pixoeditor__uploadFile(
            $id,
            $_FILES[ 'image' . $i ],
            $_POST[ 'upload-action' ],
            $target
         )
      );
   }
   
   die( json_encode( $files ) );
}

add_filter( 'bulk_actions-upload', 'pixoeditor__registerBatchEditing' );
function pixoeditor__registerBatchEditing( $bulk_actions ) {
   return array_merge(
      array( PIXOEDITOR_PARAM_MULTI => 'Edit' ),
      array( PIXOEDITOR_PARAM_BATCH => 'Batch Edit' ),
      $bulk_actions
   );
}

add_filter( 'handle_bulk_actions-upload', 'pixoeditor__batchEdit', 10, 3 );
function pixoeditor__batchEdit( $redirect_to, $action, $post_ids ) {
   switch ( $action ) {
   case PIXOEDITOR_PARAM_BATCH:
      return add_query_arg(
         PIXOEDITOR_PARAM_BATCH,
         implode( ',', $post_ids ),
         $redirect_to
      );
   
   case PIXOEDITOR_PARAM_MULTI:
      return add_query_arg(
         PIXOEDITOR_PARAM_MULTI,
         implode( ',', $post_ids ),
         $redirect_to
      );
   }
   return $redirect_to;
}

if ( version_compare( $wp_version, '4.7', '<' ) ) {
   add_action( 'admin_footer', 'pixoeditor__legacyAddBulkActions' );
   add_action( 'load-upload.php', 'pixoeditor__legacyHandleBulkActions' );
}

function pixoeditor__legacyAddBulkActions() {
   global $post_type;
   
   if ( $post_type === 'attachment' ) {
?>
      <script type="text/javascript">
      jQuery( function ( $ ) {
         var batch = '<?php echo PIXOEDITOR_PARAM_BATCH; ?>';
         var multi = '<?php echo PIXOEDITOR_PARAM_MULTI; ?>';
         $( '<option>' ).val( multi ).text( 'Edit' ).appendTo( "select[name='action']" );
         $( '<option>' ).val( batch ).text( 'Batch Edit' ).appendTo( "select[name='action']" );
         $( '<option>' ).val( multi ).text( 'Edit' ).appendTo( "select[name='action2']" );
         $( '<option>' ).val( batch ).text( 'Batch Edit' ).appendTo( "select[name='action2']" );
      });
      </script>
<?php
   }
}

function pixoeditor__legacyHandleBulkActions() {
   // get the action
   $wp_list_table = _get_list_table( 'WP_Media_List_Table' );  // depending on your resource type this could be WP_Users_List_Table, WP_Comments_List_Table, etc
   $action = $wp_list_table->current_action();
   
   $allowed_actions = array( PIXOEDITOR_PARAM_BATCH, PIXOEDITOR_PARAM_MULTI );
   if ( ! in_array( $action, $allowed_actions ) ) {
      return;
   }
   
   // security check
   check_admin_referer( 'bulk-media' );
   
   // make sure ids are submitted.  depending on the resource type, this may be 'media' or 'ids'
   if ( isset( $_REQUEST[ 'media' ] ) ) {
      $post_ids = array_map( 'intval', $_REQUEST[ 'media' ] );
   }
   
   if ( empty( $post_ids ) ) {
      return;
   }
   
   // this is based on wp-admin/edit.php
   $url = pixoeditor__batchEdit( wp_get_referer(), $action, $post_ids );
   
   wp_redirect( $url );
   
   exit();
}


/********************************************************************** PAGES */

add_action( 'admin_menu', 'pixoeditor__registerAdminMenu' );
add_action( 'network_admin_menu', 'pixoeditor__registerAdminMenu' );
function pixoeditor__registerAdminMenu()
{
   $caps = json_decode( pixoeditor__getOption( PIXOEDITOR_OPTION_USAGE ) );
   add_menu_page(
      'Pixo Editor Settings',
      'Pixo Settings',
      $caps[0],
      PIXOEDITOR_PAGE_NAME,
      'pixoeditor__printAdminPage',
      'dashicons-format-image',
      PIXOEDITOR_MENU_POSITION
   );
}

function pixoeditor__printAdminPage() {
   pixoeditor__handleFormSubmission();
   if (
      ! pixoeditor__getOption( PIXOEDITOR_OPTION_APIKEY )
            and ! isset( $_POST[ PIXOEDITOR_OPTION_APIKEY ] )
            and ! isset( $_GET[ PIXOEDITOR_SETTINGS_VIEW ] )
   ) {
      pixoeditor__printCompleteRegistrationForm();
   } else {
      pixoeditor__printSettingsPage();
   }
}

function pixoeditor__printCompleteRegistrationForm() {
?>
<div class="wrap">
   <h1>Pixo Image Editor Registration</h1>
   <div class="widget-liquid-left">
      <form id="pixo-registration" action="" method="post" autocomplete="off">
         <p>
            Thanks for using <a href="https://pixoeditor.com">Pixo</a>!
         </p>
         <p>
            This WordPress plugin wraps <a href="https://pixoeditor.com">Pixo Image Editor service</a>.
            The service <strong>requires registration</strong> with email and password. Without registration the Image Editor will not work for you.
         </p>
         <h2>I already have registration</h2>
         <p>
            <a href="<?php echo PIXOEDITOR_OPTIONS_URL . '&' . PIXOEDITOR_SETTINGS_VIEW; ?>">
               Click here to enter your API key
            </a>
         </p>
         <h2>Register now</h2>
         <p><input type="email" name="email" placeholder="Your email" value="<?php echo isset($_POST['email']) ? esc_attr(sanitize_text_field($_POST['email'])) : ''; ?>" autocomplete="false" /></p>
         <p><input type="password" name="password" placeholder="Desired password" value="<?php echo isset($_POST['password']) ? esc_attr(sanitize_text_field($_POST['password'])) : ''; ?>" autocomplete="false" /></p>
         <p>
            On successful registration you will get a confirmation email at the address you typed above.
            <strong>You have to confirm your registration within 24 hours</strong>, otherwise the Image Editor will stop working.
         </p>
         <p>
            <label>
               <input type="checkbox" name="newsletter" value="1" /> Subscribe me for newsletter
            </label>
         </p>
         <p>Pixo sends you only updates for new features and bug fixes, and important changes concerning privacy policy, terms of use, etc.</p>
         <p>
            <label>
               <input type="checkbox" name="terms"<?php echo isset( $_POST['terms'] ) ? ' checked="checked"' : ''; ?> />
               I agree with the
               <a href="https://pixoeditor.com/terms-and-conditions/" target="_blank">Terms &amp; Conditions</a>
            </label>
         </p>
         <p>
            <label>
               <input type="checkbox" name="policy"<?php echo isset( $_POST['policy'] ) ? ' checked="checked"' : ''; ?> />
               I accept the
               <a href="https://pixoeditor.com/privacy-policy/" target="_blank">Privacy Policy</a>
            </label>
         </p>
         <input type="hidden" name="<?php echo PIXOEDITOR_KEY_NONCE ?>" value="<?php echo wp_create_nonce( PIXOEDITOR_KEY_NONCE ) ?>" />
         <p class="submit">
            <input type="submit" name="complete-registration" id="submit" class="button button-primary" value="Register Now" disabled="disabled">
         </p>
      </form>
      <script type="text/javascript">
      ( function () {
      
      var form = document.getElementById( 'pixo-registration' );
      var checkboxes = [].slice.call( form.querySelectorAll( 'input[type=checkbox]' ), 1 );
      var inputs = form.querySelectorAll( 'input[type=text], input[type=password]' );
      var submit = form.querySelector( 'input[type=submit]' );
      
      [].forEach.call( checkboxes, function ( checkbox ) {
         checkbox.addEventListener( 'click', enableOrDisableForm );
      });
      
      [].forEach.call( inputs, function ( input ) {
         input.addEventListener( 'keyup', enableOrDisableForm );
      });
      
      enableOrDisableForm();
      function enableOrDisableForm() {
         submit.disabled = ! [].map.call( checkboxes, function ( checkbox ) {
            return checkbox.checked;
         }).concat(
            [].map.call( inputs, function ( input ) {
               return !! input.value;
            })
         ).every( Boolean );
      }
      
      })();
      </script>
   </div>
</div>
<?php
}

function pixoeditor__printSettingsPage() {
?>
<div class="wrap wp-clearfix">
   <h1>Pixo Image Editor Settings</h1>
   <div>
      <form action="" method="post">
         <?php if (pixoeditor__currentUserCan(PIXOEDITOR_OPTION_MANAGE)) : ?>
         <p>
            Thanks for using <a href="https://pixoeditor.com">Pixo</a>!
         </p>
         <p>
            Pixo is online image editor designed to be integrated into 3rd party web apps.
            In order to operate properly, Pixo requires API key. Such was automatically
            generated for you during plugin's activation. 
         </p>
         <p>
            To see plugin activity (editor openings, saved images) please login to 
            <a href="https://pixoeditor.com/cp">Pixo's Control Panel</a>.
         </p>

         <h2>Your API key</h2>
         <input type="text" name="<?php echo PIXOEDITOR_OPTION_APIKEY ?>" value="<?php echo pixoeditor__getOption( PIXOEDITOR_OPTION_APIKEY ) ?>" />
         <input type="submit" name="save-settings" id="submit" class="button button-primary" value="Update">
         
         <h2>Integration options</h2>
         <?php endif ?>

         <?php
         $active_tab = isset( $_GET[ PIXOEDITOR_QUERY_VAR_TAB ] ) ? $_GET[ PIXOEDITOR_QUERY_VAR_TAB ] : PIXOEDITOR_TAB_ADMIN;
         ?>

         <h2 class="nav-tab-wrapper">
            <a href="<?php echo PIXOEDITOR_OPTIONS_URL ?>&<?php echo PIXOEDITOR_QUERY_VAR_TAB ?>=<?php echo PIXOEDITOR_TAB_ADMIN ?>" class="nav-tab <?php echo $active_tab == PIXOEDITOR_TAB_ADMIN ? 'nav-tab-active' : ''; ?>">Admin</a>
            <a href="<?php echo PIXOEDITOR_OPTIONS_URL ?>&<?php echo PIXOEDITOR_QUERY_VAR_TAB ?>=<?php echo PIXOEDITOR_TAB_FRONTEND ?>" class="nav-tab <?php echo $active_tab == PIXOEDITOR_TAB_FRONTEND ? 'nav-tab-active' : ''; ?>">Frontend</a>
         </h2>

         <?php
         switch ( $active_tab ) {
         case PIXOEDITOR_TAB_ADMIN:
            return pixoeditor__printAdminSettingsPage();
         case PIXOEDITOR_TAB_FRONTEND:
            return pixoeditor__printFrontendSettingsPage();
         }
         ?>
      </form>
   </div>
</div>
<?php
}

function pixoeditor__printAdminSettingsPage() {
?>
<p>
   Pixo replaces the default image editor everywhere in wp-admin.
</p>
<script type="text/javascript">
   function Pixo__selectMultiple( select, selected ) {
      for ( var i=0, l=select.options.length; i<l; i++ ) {
         select.options[i].selected = !!~selected.indexOf( select.options[i].value );
      }
   }
</script>

<input
   type="hidden"
   id="<?php echo PIXOEDITOR_OPTION_CONFIG ?>"
   name="<?php echo PIXOEDITOR_OPTION_CONFIG ?>"
/>

<h3>Stickers Libraries</h3>
<?php $stickers = pixoeditor__getCustomStickers( null, true, false ); ?>
<?php if ( ! empty( $stickers ) ) : ?>
<p>
   Choose these 
   <a href="edit.php?post_type=<?php echo PIXOEDITOR_POST_TYPE_STICKERS ?>">
      Stickers Libraries
   </a> 
   which you want to include in Pixo Editor, or choose none to use Pixo's stock stickers (drag to reorder them):
</p>
<input
   type="hidden"
   id="pixo_editor_stickers"
   name="<?php echo PIXOEDITOR_OPTION_STICKERS ?>"
   value="<?php echo get_option( PIXOEDITOR_OPTION_STICKERS ) ?>"
/>
<div>
   <?php foreach ( $stickers as $lib ) : ?>
   <label draggable="true">
      <input
         type="checkbox"
         id="pixo_editor_stickers_<?php echo $lib['id'] ?>"
         value="<?php echo $lib['id'] ?>"
      />
      <?php echo $lib['title'] ?>
   </label>
   <?php endforeach ?>
</div>
<p class="submit">
   <input type="submit" name="save-settings" class="button button-primary" value="Set Custom Stickers Libraries">
</p>

<script type="text/javascript">
jQuery(function ($) {
const admin_editor_stickers_field = document.getElementById( 'pixo_editor_stickers' );
const admin_editor_stickers = admin_editor_stickers_field.value
      ? admin_editor_stickers_field.value.split( ',' ) : [];

admin_editor_stickers.slice().reverse().forEach( function ( id ) {
   const el = document.getElementById( 'pixo_editor_stickers_' + id );
   if (el) {
      el.parentNode.parentNode.insertBefore(
         el.parentNode,
         el.parentNode.parentNode.firstChild
      );
   }
});

[].forEach.call(
   document.body.querySelectorAll('[id^=pixo_editor_stickers_]'),
   function ( el ) {
      el.checked = admin_editor_stickers.includes( el.value );
      el.addEventListener( 'change', function () {
         const all = document.body.querySelectorAll('[id^=pixo_editor_stickers_]');
         admin_editor_stickers.length = 0;
         for ( let i=0, l=all.length; i < l; i++ ) {
            if ( all[i].checked ) {
               admin_editor_stickers.push( all[i].value );
            }
         }
         admin_editor_stickers_field.value = admin_editor_stickers.join( ',' );
      });
   }
);
});
</script>
<?php else : ?>
<p>
   You don't have any Sticker Library yet. You have to 
   <a href="post-new.php?post_type=<?php echo PIXOEDITOR_POST_TYPE_STICKERS ?>">
      create at least one first
   </a>.
</p>
<?php endif; ?>

<?php if (pixoeditor__currentUserCan(PIXOEDITOR_OPTION_MANAGE)) : ?>
<h3>Output</h3>

<h4>Format</h4>
Image format (type), can be one of the following:
<ul>
   <li>“auto” Default Detects output format by smart image analysis (transparency (alpha), number of colors, gradients, and more) and determines the best format (png or jpeg) in terms of alpha, higher quality and lower filesize</li>
   <li>“input” Preserves the input format, but only in case it is jpeg or png. For any other format, the above behavior is applied</li>
   <li>“jpeg” Forces jpeg format. Note: transparent pixels will appear black</li>
   <li>“png” Forces png format. Note: output image may become very large (megabytes)</li>
</ul>
<select id="pixo_config_output_format">
   <option>auto</option>
   <option>input</option>
   <option>jpeg</option>
   <option>png</option>
</select>

<h4>Quality</h4>
<p>
   Value between 0 and 1, closer value to 1 results in higher quality, and vice-versa. Applies only if the output format will be jpeg image.
</p>
<input id="pixo_config_output_quality" type="range" min="0" max="1" step="0.01" />

<h4>Image Optimizations</h4>
<p>
   This is a Premium feature and is not included into the FREE package.
   Image compression by <a href="https://tinypng.com" target="_blank">TinyPNG</a>. Optimizing images will result in less KB (optimzied filesize) as well as slower image export.
</p>
Enabled: <input type="checkbox" id="pixo_config_output_optimize" />

<p class="submit">
   <input type="submit" name="save-settings" id="submit" class="button button-primary" value="Update Integration Options">
</p>

<script type="text/javascript">
jQuery( function () {

const config_field = document.getElementById(
   '<?php echo PIXOEDITOR_OPTION_CONFIG ?>'
);

const config = Pixo.CONFIG || Pixo.DEFAULT_CONFIG;
config_field.value = JSON.stringify( config );

[].forEach.call(
   document.querySelectorAll('[id^=pixo_config_]'),
   function ( el ) {
      const is_checkbox = el.type === 'checkbox';
      const namespace   = el.id.replace( 'pixo_config_', '' ).split( '_' );
      const propname    = namespace.pop();
      const reference   = namespace.reduce( function ( conf, name ) {
         return conf[ name ];
      }, config );
      
      el[ is_checkbox ? 'checked' : 'value' ] = reference[ propname ];
      
      el.addEventListener( 'change', function () {
         reference[ propname ] = is_checkbox ? el.checked : el.value;
         config_field.value = JSON.stringify( config );
      });
   }
);

});
</script>

<h2>Image Sizes</h2>

<p>When saving edited images, apply changes to:</p>
<p>
   <label>
      <input type="radio" name="<?php echo PIXOEDITOR_OPTION_IMAGE_SIZE; ?>" value="<?php echo PIXOEDITOR_IMAGE_SIZE_ALL; ?>" <?php echo get_option( PIXOEDITOR_OPTION_IMAGE_SIZE ) === PIXOEDITOR_IMAGE_SIZE_ALL ? 'checked="checked"' : ''; ?> />
      All image sizes
   </label>
</p>
<p>
   <label>
      <input type="radio" name="<?php echo PIXOEDITOR_OPTION_IMAGE_SIZE; ?>" value="<?php echo PIXOEDITOR_IMAGE_SIZE_THUMBNAIL; ?>" <?php echo get_option( PIXOEDITOR_OPTION_IMAGE_SIZE ) === PIXOEDITOR_IMAGE_SIZE_THUMBNAIL ? 'checked="checked"' : ''; ?> />
      Thumbnail
   </label>
</p>
<p>
   <label>
      <input type="radio" name="<?php echo PIXOEDITOR_OPTION_IMAGE_SIZE; ?>" value="<?php echo PIXOEDITOR_IMAGE_SIZE_OTHER; ?>" <?php echo get_option( PIXOEDITOR_OPTION_IMAGE_SIZE ) === PIXOEDITOR_IMAGE_SIZE_OTHER ? 'checked="checked"' : ''; ?> />
      All sizes except thumbnail
   </label>
</p>
<p>
   <label>
      <input type="radio" name="<?php echo PIXOEDITOR_OPTION_IMAGE_SIZE; ?>" value="" <?php echo !get_option( PIXOEDITOR_OPTION_IMAGE_SIZE ) ? 'checked="checked"' : ''; ?> />
      Ask every time when saving image
   </label>
</p>
<p class="submit">
   <input type="submit" name="save-settings" class="button button-primary" value="Update Image Sizes Options">
</p>

<h2>Capabilities</h2>

<h3>Who can use the image editor</h3>
<p>
   All users having at least one of the selected capabilities below will be able to
   edit images with Pixo:
</p>
<select id="pixo_select_usage" multiple name="<?php echo PIXOEDITOR_OPTION_USAGE ?>[]">
   <?php $caps = pixoeditor__getCapabilities(); foreach ( $caps as $name ) : ?>
   <option><?php echo $name; ?></option>
   <?php endforeach; ?>
</select>
<script type="text/javascript">
   Pixo__selectMultiple( pixo_select_usage, <?php echo pixoeditor__getOption( PIXOEDITOR_OPTION_USAGE ) ?> );
</script>

<h3>Who can update these settings</h3>
<p>
   All users having at least one of the selected capabilities below will be able to
   update these settings:
</p>
<select id="pixo_select_manage" multiple name="<?php echo PIXOEDITOR_OPTION_MANAGE ?>[]">
   <?php $caps = pixoeditor__getCapabilities(); foreach ( $caps as $name ) : ?>
   <option><?php echo $name; ?></option>
   <?php endforeach; ?>
</select>
<script type="text/javascript">
   Pixo__selectMultiple( pixo_select_manage, <?php echo pixoeditor__getOption( PIXOEDITOR_OPTION_MANAGE ) ?> );
</script>

<p class="submit">
   <input type="submit" name="save-settings" id="submit" class="button button-primary" value="Update Capabilities">
</p>
<?php endif ?>

<input type="hidden" name="<?php echo PIXOEDITOR_KEY_NONCE ?>" value="<?php echo wp_create_nonce( PIXOEDITOR_KEY_NONCE ) ?>" />
<?php
}

function pixoeditor__handleFormSubmission() {
   if ( isset( $_POST[ PIXOEDITOR_KEY_NONCE ] ) ) {
      if ( wp_verify_nonce( $_POST[ PIXOEDITOR_KEY_NONCE ], PIXOEDITOR_KEY_NONCE ) ) {
         if ( isset( $_POST[ 'save-settings' ] ) ) {
            isset( $_POST[ PIXOEDITOR_OPTION_APIKEY ] ) && pixoeditor__updateOption(
               PIXOEDITOR_OPTION_APIKEY,
               sanitize_text_field( $_POST[ PIXOEDITOR_OPTION_APIKEY ] )
            );
            isset( $_POST[ PIXOEDITOR_OPTION_STICKERS ] ) && update_option(
               PIXOEDITOR_OPTION_STICKERS,
               sanitize_text_field( $_POST[ PIXOEDITOR_OPTION_STICKERS ] )
            );
            isset( $_POST[ PIXOEDITOR_OPTION_MANAGE ] ) && pixoeditor__updateOption(
               PIXOEDITOR_OPTION_MANAGE,
               sanitize_text_field( json_encode( $_POST[ PIXOEDITOR_OPTION_MANAGE ] ) )
            );
            isset( $_POST[ PIXOEDITOR_OPTION_USAGE ] ) && pixoeditor__updateOption(
               PIXOEDITOR_OPTION_USAGE,
               sanitize_text_field( json_encode( $_POST[ PIXOEDITOR_OPTION_USAGE ] ) )
            );
            isset( $_POST[ PIXOEDITOR_OPTION_CONFIG ] ) && pixoeditor__updateOption(
               PIXOEDITOR_OPTION_CONFIG,
               sanitize_text_field( json_encode( $_POST[ PIXOEDITOR_OPTION_CONFIG ] ) )
            );
            isset( $_POST[ PIXOEDITOR_OPTION_FRONTEND_CONFIG ] ) && pixoeditor__updateOption(
               PIXOEDITOR_OPTION_FRONTEND_CONFIG,
               sanitize_text_field( json_encode( $_POST[ PIXOEDITOR_OPTION_FRONTEND_CONFIG ] ) )
            );
            isset( $_POST[ PIXOEDITOR_OPTION_IMAGE_SIZE ] ) && update_option(
               PIXOEDITOR_OPTION_IMAGE_SIZE,
               sanitize_text_field( $_POST[ PIXOEDITOR_OPTION_IMAGE_SIZE ] )
            );
            isset( $_POST[ PIXOEDITOR_OPTION_FRONTEND_GLOBAL ] ) && pixoeditor__updateOption(
               PIXOEDITOR_OPTION_FRONTEND_GLOBAL,
               sanitize_text_field( $_POST[ PIXOEDITOR_OPTION_FRONTEND_GLOBAL ] )
            );
            isset( $_POST[ PIXOEDITOR_OPTION_FRONTEND_SELECTOR ] ) && pixoeditor__updateOption(
               PIXOEDITOR_OPTION_FRONTEND_SELECTOR,
               sanitize_text_field( $_POST[ PIXOEDITOR_OPTION_FRONTEND_SELECTOR ] )
            );
            isset( $_POST[ PIXOEDITOR_OPTION_FRONTEND_DOWNLOAD ] ) && pixoeditor__updateOption(
               PIXOEDITOR_OPTION_FRONTEND_DOWNLOAD,
               sanitize_text_field( $_POST[ PIXOEDITOR_OPTION_FRONTEND_DOWNLOAD ] )
            );
            isset( $_POST[ PIXOEDITOR_OPTION_FRONTEND_STICKERS ] ) && update_option(
               PIXOEDITOR_OPTION_FRONTEND_STICKERS,
               sanitize_text_field( $_POST[ PIXOEDITOR_OPTION_FRONTEND_STICKERS ] )
            );
?>
            <div class="updated notice notice-success is-dismissible">
               <p><b>Pixo Editor settings updated.</b></p>
            </div>
<?php
         } else if ( isset( $_POST[ 'complete-registration' ] ) ) {
            $email      = $_POST[ 'email' ];
            $password   = $_POST[ 'password' ];
            
            // Check whether the user is already registered
            $response = pixoeditor__sendAPIRequest( 'users/emails/' . $email );
            if ( $response->code === 'HttpError' ) {
?>
               <div class="error notice notice-error is-dismissible">
                  <p><b>Registration is unsuccessful! Your WordPress installation does not support any HTTP transportation.</b></p>
                  <p>
                     You can still workaround this. Please
                     <a href="https://pixoeditor.com/cp/#/register" target="_blank">
                        register at Pixo Control Panel
                     </a>
                     and retrieve your API key from there.
                  </p>
                  <p>
                     Then paste your API key to the
                     <a href="<?php echo PIXOEDITOR_OPTIONS_URL . '&' . PIXOEDITOR_SETTINGS_VIEW; ?>">
                        Settings Page
                     </a>
                  </p>
                  <p>Error: <?php echo $response->message; ?></p>
               </div>
<?php
            } else if ( ! $response->email ) {
               // Register user with email and password
               $response = pixoeditor__sendAPIRequest( 'users', 'POST', '', '', array(
                  'email'     => $email,
                  'password'  => $password,
                  'meta'      => $_POST[ 'newsletter' ] == 1
                        ? array( 'newsletter_subscribed' => true )
                        : NULL,
                  'client'    => 'WordPress',
               ) );
               if ( ! $response->code ) {
                  $url = get_bloginfo('url');
                  // Register default project and store the API key
                  $response = pixoeditor__sendAPIRequest(
                     'projects',
                     'POST',
                     $email,
                     $password,
                     array(
                        'title'        => get_bloginfo( 'name' ) ?: $url ?: 'My WordPress site',
                        'restrictions' => '',
                     )
                  );
                  if ( $response->id ) {
                     pixoeditor__updateOption( PIXOEDITOR_OPTION_APIKEY,  $response->id );
?>
                     <div class="updated notice notice-success is-dismissible">
                        <p><b>Pixo Editor registration completed.</b></p>
                     </div>
<?php
                  } else {
                     // Creating project failed
?>
                     <div class="error notice notice-error is-dismissible">
                        <p><b>Registration is successful, but API key generation failed!</b></p>
                        <p>Error: <?php echo $response->message; ?></p>
                     </div>
<?php
                  }
               } else {
                  // Registration failed
?>
                  <div class="error notice notice-error is-dismissible">
                     <p><b>Registration is unsuccessful! Please try again!</b></p>
                     <p>Error: <?php echo $response->message; ?></p>
                  </div>
<?php
               }
            } else {
               // This email is already registered
               // Try to retrieve the API key with the provided password
               $response = pixoeditor__sendAPIRequest(
                  'projects',
                  'GET',
                  $email,
                  $password
               );
               if ( ! $response->code && isset( $response[0] ) ) {
                  pixoeditor__updateOption( PIXOEDITOR_OPTION_APIKEY,  $response[0]->id );
?>
                  <div class="updated notice notice-success is-dismissible">
                     <p><b>Pixo Editor registration completed.</b></p>
                  </div>
<?php
               } else {
?>
                  <div class="update-nag notice notice-warning is-dismissible">
                     <p><b>This email is already registered!</b></p>
                     <p>
                        Please
                        <a href="https://pixoeditor.com/cp/" target="_blank">
                           login to Pixo Control Panel
                        </a>
                        (or
                        <a href="https://pixoeditor.com/cp/#/forgotten-password" target="_blank">
                           reset password
                        </a>
                        in case you don't remember it) and retrieve your API key.
                     </p>
                     <p>
                        Then paste your API key to the
                        <a href="<?php echo PIXOEDITOR_OPTIONS_URL . '&' . PIXOEDITOR_SETTINGS_VIEW; ?>">
                           Settings Page
                        </a>
                     </p>
                  </div>
<?php
               }
            }
         }
      } else {
         wp_die( 'Invalid nonce specified' );
      }
   }
}


/******************************************************************** HELPERS */

function pixoeditor__sendAPIRequest( $path, $method = 'GET', $user = '', $pass = '', $data = array() ) {
   $headers    = array();
   if ( $user and $pass ) {
      $headers[ 'Authorization' ] = 'Basic ' . base64_encode( $user . ':' . $pass );
   }
   $response   = wp_remote_request( 'https://pixoeditor.com/api/' . $path,
      array(
         'method'       => $method,
         'headers'      => $headers,
         'body'         => $data,
         'sslverify'    => false,
      )
   );
   if ( is_wp_error( $response ) ) {
      if ( $response->get_error_code() === 'http_failure' ) {
         return json_decode( '{"code":"HttpError","message":"' . $response->get_error_message() . '"}' );
      }
      
      $json = json_decode( $response->get_error_message() );
      if ( ! $json ) {
         $json = json_decode(
            '{"code":"NetworkError","message":"' . $response->get_error_message() . '"}'
         );
      }
      return $json;
   }
   return json_decode( wp_remote_retrieve_body( $response ) );
}

function pixoeditor__showCompleteInstallationNotification() {
?>
   <div class="update-nag notice notice-warning">
      <p>
         Image Editor by Pixo was <b>successfully activated</b>.
         <a href="<?php echo PIXOEDITOR_OPTIONS_URL; ?>">
            Click here to complete the installation process
         </a>.
      </p>
   </div>
<?php
}

function pixoeditor__currentUserCan( $capability ) {
   $caps = json_decode( pixoeditor__getOption( $capability ) );
   foreach ( $caps as $cap ) {
      if ( current_user_can( $cap ) ) {
         return true;
      }
   }
   return false;
}

function pixoeditor__getCapabilities() {
   $roles = get_editable_roles();
   $caps = array();
   foreach ( $roles as $role ) {
      foreach ( $role[ 'capabilities' ] as $name => $bool ) {
         if ( ! array_search( $name, $caps ) ) {
            array_push( $caps, $name );
         }
      }
   }
   sort( $caps );
   return $caps;
}

function pixoeditor__resolveImageSrc( $id ) {
   $props = wp_get_attachment_image_src( $id, 'full' );
   if ( $props ) {
      return array( 'id' => $id, 'src' => $props[0] );
   }
   return array();
}

$pixoeditor__filepath = '';

function pixoeditor__uniqueFilename( $dir, $name, $ext ) {
   global $pixoeditor__filepath;
   return basename( $pixoeditor__filepath );
}

function pixoeditor__uploadFile( $id, $file, $action, $target ) {
   global $pixoeditor__filepath;
   $pixoeditor__filepath = get_attached_file( $id );
   
   $metadata = wp_get_attachment_metadata( $id );
   $overrides = array( 'test_form' => false );

   $uploads = wp_upload_dir();
   if (!($uploads && false === $uploads['error'])) {
      return json_decode('{"error":"Error retrieving upload directory"}');
   }

   $original_file = $uploads[ 'basedir' ] . '/' . $metadata[ 'file' ];
   $original_file_backup = $original_file . '.original.backup';
   copy( $original_file, $original_file_backup );
   
   switch ( $action ) {
   case PIXOEDITOR_ACTION_SAVE_NEW_UPDATE:
      $filename = wp_unique_filename( $uploads['path'], basename( $pixoeditor__filepath ) );
      $new_file = $uploads['path'] . "/$filename";
      copy( $pixoeditor__filepath, $new_file );
      unlink( $pixoeditor__filepath );
      
      update_attached_file( $id, $new_file );
      wp_update_attachment_metadata(
         $id,
         wp_generate_attachment_metadata( $id, $new_file )
      );
   case PIXOEDITOR_ACTION_SAVE_OVERWRITE_UPDATE:
      $overrides[ 'unique_filename_callback' ] = 'pixoeditor__uniqueFilename';
      foreach ( $metadata[ 'paths' ] as $path ) {
         copy( $file[ 'tmp_name' ], $path );
         pixoeditor__createImageSizes( $path, $target );
      }
      break;
   }
   
   $movedfile = wp_handle_upload( $file, $overrides );
   
   if ( $movedfile ) {
      if ( ! $movedfile[ 'error' ] ) {
         if ( $id ) {
            $id = intval( $id );
            
            switch ( $action ) {
            case PIXOEDITOR_ACTION_SAVE_NEW:
            case PIXOEDITOR_ACTION_SAVE_NEW_UPDATE:
               $filename = $movedfile[ 'file' ];
               $filetype = $movedfile[ 'type' ];
               
               // Prepare an array of post data for the attachment.
               $attachment = array(
                  'guid'           => $uploads['url'] . '/' . basename( $filename ),
                  'post_mime_type' => $filetype,
                  'post_title'     => preg_replace( '/\.[^.]+$/', '', basename( $filename ) ),
                  'post_content'   => '',
                  'post_status'    => 'inherit'
               );
                
               // Insert the attachment.
               $attach_id = wp_insert_attachment( $attachment, $filename );
               $movedfile[ 'id' ] = $attach_id;
                
               // Generate the metadata for the attachment, and update the database record.
               $attach_data = wp_generate_attachment_metadata( $attach_id, $filename );
               
               if ( $target !== PIXOEDITOR_IMAGE_SIZE_ALL ) {
                  $movedfile_backup = $filename . '.new.backup';
                  copy( $filename, $movedfile_backup );
                  copy( $original_file_backup, $filename );

                  $attach_data[ 'sizes' ] = array_merge(
                     $attach_data[ 'sizes' ],
                     pixoeditor__createImageSizes(
                        $filename,
                        $target === PIXOEDITOR_IMAGE_SIZE_THUMBNAIL
                              ? PIXOEDITOR_IMAGE_SIZE_OTHER
                              : PIXOEDITOR_IMAGE_SIZE_THUMBNAIL
                     )
                  );

                  if ( $target === PIXOEDITOR_IMAGE_SIZE_OTHER ) {
                     copy( $movedfile_backup, $filename );
                  }
                  unlink( $movedfile_backup );
               }

               wp_update_attachment_metadata( $attach_id, $attach_data );
               break;
            
            default:
               update_attached_file( $id, $movedfile[ 'file' ] );
               $metadata[ 'sizes' ] = array_merge(
                  $metadata[ 'sizes' ],
                  pixoeditor__createImageSizes( $movedfile[ 'file' ], $target )
               );

               if ( $target === PIXOEDITOR_IMAGE_SIZE_THUMBNAIL ) {
                  copy( $original_file_backup, $movedfile[ 'file' ] );
               }
               
               if ( ! isset( $metadata[ 'paths' ] ) ) {
                  $metadata[ 'paths' ] = array();
               }
               if ( array_search ( $pixoeditor__filepath, $metadata[ 'paths' ] ) === false ) {
                  array_push( $metadata[ 'paths' ], $pixoeditor__filepath );
               }
               
               wp_update_attachment_metadata( $id, $metadata );
               break;
            }
         }
      }
      unlink( $original_file_backup );
      return $movedfile;
   }
   unlink( $original_file_backup );
   return json_decode( '{"error":"Something went wrong"}' );
}

function pixoeditor__createImageSizes( $path, $target = PIXOEDITOR_IMAGE_SIZE_ALL ) {
   global $_wp_additional_image_sizes;
   foreach ( get_intermediate_image_sizes() as $s ) {
      if (
         $target === PIXOEDITOR_IMAGE_SIZE_THUMBNAIL && $s !== $target
               || $target === PIXOEDITOR_IMAGE_SIZE_OTHER && $s === PIXOEDITOR_IMAGE_SIZE_THUMBNAIL
      ) {
         continue;
      }

      $sizes[$s] = array( 'width' => '', 'height' => '', 'crop' => false );
      if ( isset( $_wp_additional_image_sizes[$s]['width'] ) )
         $sizes[$s]['width'] = intval( $_wp_additional_image_sizes[$s]['width'] ); // For theme-added sizes
      else
         $sizes[$s]['width'] = get_option( "{$s}_size_w" ); // For default sizes set in options
      if ( isset( $_wp_additional_image_sizes[$s]['height'] ) )
         $sizes[$s]['height'] = intval( $_wp_additional_image_sizes[$s]['height'] ); // For theme-added sizes
      else
         $sizes[$s]['height'] = get_option( "{$s}_size_h" ); // For default sizes set in options
      if ( isset( $_wp_additional_image_sizes[$s]['crop'] ) )
         $sizes[$s]['crop'] = intval( $_wp_additional_image_sizes[$s]['crop'] ); // For theme-added sizes
      else
         $sizes[$s]['crop'] = get_option( "{$s}_crop" ); // For default sizes set in options
   }

   $sizes = apply_filters( 'intermediate_image_sizes_advanced', $sizes );
   if ( $sizes ) {
      $editor = wp_get_image_editor( $path );
      if ( ! is_wp_error( $editor ) ) {
         return $editor->multi_resize( $sizes );
      }
      return $editor;
   }

   return NULL;
}

function pixoeditor__isNetworkActive() {
   $plugins = get_site_option('active_sitewide_plugins');
   return isset($plugins['pixo/pixo.php']);
}

function pixoeditor__addOption($name, $value) {
   if (pixoeditor__isNetworkActive()) {
      return add_network_option(null, $name, $value);
   }
   return add_option($name, $value);
}

function pixoeditor__updateOption($name, $value) {
   if (pixoeditor__isNetworkActive()) {
      return update_network_option(null, $name, $value);
   }
   return update_option($name, $value);
}

function pixoeditor__getOption($name) {
   if (pixoeditor__isNetworkActive()) {
      return get_network_option(null, $name);
   }
   return get_option($name);
}
