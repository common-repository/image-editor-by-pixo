<?php

add_shortcode( 'pixoeditor', 'pixoeditor__shortcode' );
function pixoeditor__shortcode( $atts = array() ) {
   if ( defined( 'REST_REQUEST' ) ) {
      return;
   }

   $attrs = shortcode_atts( array(
      'fileinput' => 'input[type=file]',
      'download'  => '',
      'stickers'  => '',
   ), $atts );

   $stickers_libraries     = explode( ',', $attrs[ 'stickers' ] );
?>
<style type="text/css">
.pixo-modal {
   z-index: 9999999;
}
</style>
<script src="https://pixoeditor.com/editor/scripts/bridge.m.js"></script>
<script>
window.addEventListener( 'load', function () {
   if ( window.PixoEditor ) {
      return;
   }
   const config = {
      apikey      : '<?php echo pixoeditor__getOption( PIXOEDITOR_OPTION_APIKEY ) ?>',
      type        : 'modal',
      language    : '<?php echo str_replace( '_', '-', get_locale() ) ?>',
      fileinput   : '<?php echo str_replace(
         "'", '"', html_entity_decode( html_entity_decode ( $attrs[ 'fileinput' ] ) )
      ) ?>' || 'input[type=file]',
      filterStickers : function ( stickers ) {
         const custom_stickers = JSON.parse(
            '<?php echo pixoeditor__getCustomStickers( $stickers_libraries ) ?>' || '[]'
         )
         return custom_stickers.length ? custom_stickers : stickers;
      },
      onSave : function ( image ) {
         if ( '<?php echo $attrs[ 'download' ] ?>' != 0 ) {
            image.download();
         }
      },
   };

   const options = JSON.parse(
      JSON.parse( '<?php echo pixoeditor__getOption( PIXOEDITOR_OPTION_FRONTEND_CONFIG ) ?>' || null )
   );

   if ( options ) {
      for ( const name in options ) {
         config[ name ] = options[ name ];
      }
   }

   window.PixoEditor = new Pixo.Bridge( config );
});
</script>
<?php
}

function pixoeditor__printFrontendSettingsPage() {
?>
<p>
   Pixo attaches to every file input field on your front-end website. When the end user selects an image, Pixo opens the image for editing. When the image is saved, it is updated in the file input field and is ready to be submitted.
</p>

<input type="hidden" name="<?php echo PIXOEDITOR_KEY_NONCE ?>" value="<?php echo wp_create_nonce( PIXOEDITOR_KEY_NONCE ) ?>" />

<?php if (pixoeditor__currentUserCan(PIXOEDITOR_OPTION_MANAGE)) : ?>
<input
   type="hidden"
   id="pixo_config"
   name="<?php echo PIXOEDITOR_OPTION_FRONTEND_CONFIG ?>"
/>

<h3>Global Editor</h3>

<style type="text/css">
#pixo_global_editor_options,
#pixo_global_editor:checked ~ div { display: none }
#pixo_global_editor:checked ~ #pixo_global_editor_options { display: block }
</style>

<div>
   <input type="hidden" name="<?php echo PIXOEDITOR_OPTION_FRONTEND_GLOBAL ?>" value="0" />
   <input
      type="checkbox"
      id="pixo_global_editor"
      name="<?php echo PIXOEDITOR_OPTION_FRONTEND_GLOBAL ?>"
      <?php echo pixoeditor__getOption( PIXOEDITOR_OPTION_FRONTEND_GLOBAL ) != '0' ? 'checked' : '' ?>
   />
   <label for="pixo_global_editor">Init Pixo Editor globally for each File Input element</label>
   <div id="pixo_global_editor_options">
      <p>
         <label>
            <input
               type="text"
               name="<?php echo PIXOEDITOR_OPTION_FRONTEND_SELECTOR ?>"
               value="<?php echo pixoeditor__getOption( PIXOEDITOR_OPTION_FRONTEND_SELECTOR ) ?>"
               placeholder="input[type=file]"
            />
            Global CSS Selector
         </label>
      </p>
      <p>
         <label>
            <input type="hidden" name="<?php echo PIXOEDITOR_OPTION_FRONTEND_DOWNLOAD ?>" value="0" />
            <input
               type="checkbox"
               name="<?php echo PIXOEDITOR_OPTION_FRONTEND_DOWNLOAD ?>"
               <?php echo pixoeditor__getOption( PIXOEDITOR_OPTION_FRONTEND_DOWNLOAD ) != '0' ? 'checked' : '' ?>
            />
            Users can download edited image
         </label>
      </p>
   </div>
   <div>
      <p>
         To attach Pixo to a file input field in a post or a page, add the following shortcode: <br>
         <code>[pixoeditor]</code>
      </p>
      <p>
         By default, Pixo will attach to every file input (<code>input[type=file]</code>). To attach to a specific field, add the following shortcode: <br>
         <code>[pixoeditor fileinput="id-or-css-selector"]</code>
      </p>
      <p>
         By default, when the user saves the image, Pixo will update the file input. If you want your site visitors to be able also to download the edited image, add the following shortcode: <br>
         <code>[pixoeditor download="true"]</code>
      </p>
      <p>
         By default, Pixo will load it's stock stickers collection. If you want to use your own stickers collections, <a href="edit.php?post_type=<?php echo PIXOEDITOR_POST_TYPE_STICKERS ?>">create few</a>, and add the following shortcode: <br>
         <code>[pixoeditor stickers="111,121,131"]</code> where 111, 121 and 131 are the IDs of the collections
      </p>
   </div>
</div>

<p class="submit">
   <input type="submit" name="save-settings" id="submit" class="button button-primary" value="Save">
</p>
<?php endif ?>

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
   id="pixo_global_editor_stickers"
   name="<?php echo PIXOEDITOR_OPTION_FRONTEND_STICKERS ?>"
   value="<?php echo get_option( PIXOEDITOR_OPTION_FRONTEND_STICKERS ) ?>"
/>
<div>
   <?php foreach ( $stickers as $lib ) : ?>
   <label>
      <input
         type="checkbox"
         id="pixo_global_editor_stickers_<?php echo $lib['id'] ?>"
         value="<?php echo $lib['id'] ?>"
      />
      <?php echo $lib['title'] ?>
   </label>
   <?php endforeach ?>
</div>
<p class="submit">
   <input type="submit" name="save-settings" class="button button-primary" value="Set Custom Stickers Libraries">
</p>
<script>
(function () {
const global_editor_stickers_field = document.getElementById( 'pixo_global_editor_stickers' );
const global_editor_stickers = global_editor_stickers_field.value
      ? global_editor_stickers_field.value.split( ',' ) : [];

global_editor_stickers.slice().reverse().forEach( function ( id ) {
   const el = document.getElementById( 'pixo_global_editor_stickers_' + id );
   if (el) {
      el.parentNode.parentNode.insertBefore(
         el.parentNode,
         el.parentNode.parentNode.firstChild
      );
   }
});

[].forEach.call(
   document.body.querySelectorAll('[id^=pixo_global_editor_stickers_]'),
   function ( el ) {
      el.checked = global_editor_stickers.includes( el.value );
      el.addEventListener( 'change', function () {
         const all = document.body.querySelectorAll('[id^=pixo_global_editor_stickers_]');
         global_editor_stickers.length = 0;
         for ( let i=0, l=all.length; i < l; i++ ) {
            if ( all[i].checked ) {
               global_editor_stickers.push( all[i].value );
            }
         }
         global_editor_stickers_field.value = global_editor_stickers.join( ',' );
      });
   }
);
})();
</script>
<?php else: ?>
<p>
   You don't have any Sticker Library yet. You have to 
   <a href="post-new.php?post_type=<?php echo PIXOEDITOR_POST_TYPE_STICKERS ?>">
      create at least one first
   </a>.
</p>
<?php endif; ?>

<?php if (pixoeditor__currentUserCan(PIXOEDITOR_OPTION_MANAGE)) : ?>
<h3>Theme</h3>

<p>Choose a color theme or skin for the editor:</p>

<p>
   <label>
      <input type="radio" name="theme" id="pixo_config_theme" value="Default" checked />
      Default
      (<a href="https://pixoeditor.com/wp-content/uploads/2020/01/default.png" target="_blank">
         see screenshot
      </a>)
   </label>
</p>
<p>
   <label>
      <input type="radio" name="theme" id="pixo_config_theme" value="Light" />
      Light
      (<a href="https://pixoeditor.com/wp-content/uploads/2020/01/light.png" target="_blank">
         see screenshot
      </a>)
   </label>
</p>
<p>
   <label>
      <input type="radio" name="theme" id="pixo_config_theme" value="Dark" />
      Dark
      (<a href="https://pixoeditor.com/wp-content/uploads/2020/01/dark.png" target="_blank">
         see screenshot
      </a>)
   </label>
</p>
<p>
   <label>
      <input type="radio" name="theme" id="pixo_config_theme" value="WordPress" />
      WordPress
      (<a href="https://pixoeditor.com/wp-content/uploads/2020/01/wordpress.png" target="_blank">
         see screenshot
      </a>)
   </label>
</p>
<p>
   <label>
      <input type="radio" name="theme" id="pixo_config_theme" value="iOS" />
      iOS
      (<a href="https://pixoeditor.com/wp-content/uploads/2020/01/ios.png" target="_blank">
         see screenshot
      </a>)
   </label>
</p>
<p>
   <label>
      <input type="radio" name="theme" id="pixo_config_theme" value="Android" />
      Android
      (<a href="https://pixoeditor.com/wp-content/uploads/2020/01/android.png" target="_blank">
         see screenshot
      </a>)
   </label>
</p>

<p class="submit">
   <input type="submit" name="save-settings" id="submit" class="button button-primary" value="Set Theme">
</p>

<h3>Styles</h3>

Define your own look & feel of the editor.

<p>
   <label>
      <input type="text" id="pixo_config_styles_logosrc" value="" placeholder="https://yourdomain.com/logo.png" />
      URL to your custom logo
   </label>
</p>
<p>
   <label>
      <input type="color" id="pixo_config_styles_canvasbgcolor" value="" />
      CSS color for the canvas background
   </label>
</p>
<p>
   <label>
      <input type="color" id="pixo_config_styles_editmenubgcolor" value="" />
      CSS color for the edit menu background
   </label>
</p>
<p>
   <label>
      <input type="color" id="pixo_config_styles_actionsmenubgcolor" value="" />
      CSS color for the actions menu background
   </label>
</p>
<p>
   <label>
      <input type="color" id="pixo_config_styles_propertiespanelbgcolor" value="" />
      CSS color for the properties panel background
   </label>
</p>
<p>
   <label>
      <input type="color" id="pixo_config_styles_textcolor" value="" />
      CSS color for body text
   </label>
</p>
<p>
   <label>
      <input type="color" id="pixo_config_styles_editmenutextcolor" value="" />
      CSS color for edit menu icons and text
   </label>
</p>
<p>
   <label>
      <input type="color" id="pixo_config_styles_actionsmenutextcolor" value="" />
      CSS color for actions menu icons and text
   </label>
</p>
<p>
   <label>
      <input type="color" id="pixo_config_styles_propertiespaneltextcolor" value="" />
      CSS color for property panel text
   </label>
</p>
<p>
   <label>
      <input type="color" id="pixo_config_styles_buttonstextcolor" value="" />
      CSS color for property panel buttons text
   </label>
</p>
<p>
   <label>
      <input type="color" id="pixo_config_styles_buttonsbgcolor" value="" />
      CSS color for property panel buttons background
   </label>
</p>
<p>
   <label for="pixo_config_styles_css">
      CSS string declaring custom styles (overriding defaults):
   </label>
</p>
<p>
   <textarea id="pixo_config_styles_css" value=""></textarea>
</p>

<p class="submit">
   <input type="submit" name="save-settings" id="submit" class="button button-primary" value="Set Styles">
</p>

<h3>Features</h3>

<p>List of features to include (drag to reorder):</p>

<style type="text/css">
label[draggable] {
   display: block;
   padding: 0.5rem 0;
   cursor: move;
}
label[draggable]:hover { background: rgba(0, 0, 0, .1)}
</style>

<div>
   <label draggable="true">
      <input type="checkbox" id="pixo_config_features[]filters" value="filters" checked />
      Filters
   </label>
   <label draggable="true">
      <input type="checkbox" id="pixo_config_features[]stickers" value="stickers" checked />
      Stickers
   </label>
   <label draggable="true">
      <input type="checkbox" id="pixo_config_features[]text" value="text" checked />
      Text
   </label>
   <label draggable="true">
      <input type="checkbox" id="pixo_config_features[]blur" value="blur" checked />
      Blur
   </label>
   <label draggable="true">
      <input type="checkbox" id="pixo_config_features[]frame" value="frame" checked />
      Frame
   </label>
   <label draggable="true">
      <input type="checkbox" id="pixo_config_features[]shape" value="shape" checked />
      Shape
   </label>
   <label draggable="true">
      <input type="checkbox" id="pixo_config_features[]draw" value="draw" checked />
      Draw
   </label>
   <label draggable="true">
      <input type="checkbox" id="pixo_config_features[]crop" value="crop" checked />
      Crop
   </label>
   <label draggable="true">
      <input type="checkbox" id="pixo_config_features[]resize" value="resize" checked />
      Resize
   </label>
   <label draggable="true">
      <input type="checkbox" id="pixo_config_features[]background" value="background" checked />
      Background
   </label>
   <label draggable="true">
      <input type="checkbox" id="pixo_config_features[]transform" value="transform" checked />
      Transform
   </label>
   <label draggable="true">
      <input type="checkbox" id="pixo_config_features[]adjustments" value="adjustments" checked />
      Color adjustments
   </label>
</div>

<p class="submit">
   <input type="submit" name="save-settings" id="submit" class="button button-primary" value="Set Features">
</p>

<script>
let dragged;

function handleDragStart(e) {
   this.style.opacity = '0.4';
   dragged = this;
}

function handleDragEnd(e) {
   this.style.opacity = '';
}

function handleDragOver(e) {
   e.preventDefault();
   e.dataTransfer.dropEffect = 'move';
   return false;
}

function handleDragEnter(e) {
   this.style.outline = '2px dashed';
}

function handleDragLeave(e) {
   this.style.outline = '';
}

function handleDrop(e) {
   e.stopPropagation();
   this.style.outline = '';
   this.parentNode.insertBefore(dragged, this);
   
   const checkbox = this.querySelector('input');
   checkbox.dispatchEvent( new Event('change', { bubbles: true }) );

   return false;
}

var els = document.body.querySelectorAll('label[draggable=true]');
[].forEach.call(els, function(el) {
   el.addEventListener('dragstart', handleDragStart, false);
   el.addEventListener('dragend', handleDragEnd, false);
   el.addEventListener('dragenter', handleDragEnter, false);
   el.addEventListener('dragover', handleDragOver, false);
   el.addEventListener('dragleave', handleDragLeave, false);
   el.addEventListener('drop', handleDrop, false);
});
</script>

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

const config_field = document.getElementById( 'pixo_config' );

const config = JSON.parse( JSON.parse('<?php echo pixoeditor__getOption( PIXOEDITOR_OPTION_FRONTEND_CONFIG ) ?>' || null) ) || Pixo.DEFAULT_CONFIG;
config_field.value = JSON.stringify( config );

[].forEach.call(
   document.querySelectorAll('[id^=pixo_config_]'),
   function ( el ) {
      const is_checkbox = el.type === 'checkbox';
      const is_radio    = el.type === 'radio';
      const namespace   = el.id.replace( 'pixo_config_', '' ).split( '_' );
      const propname    = namespace.pop();
      const reference   = namespace.reduce( function ( conf, name ) {
         return conf[ name ] || ( conf[ name ] = {} );
      }, config );

      if ( ~propname.indexOf( '[]' ) ) {
         const real_propname = propname.split( '[]' )[0];
         reference[ real_propname ] = reference[ real_propname ] || [].reduce.call(
            document.body.querySelectorAll( '[id^=pixo_config_' + real_propname + ']' ),
            function ( collection, el ) {
               if ( el.checked ) {
                  collection.push( el.value );
               }
               return collection;
            },
            []
         );
         el.checked = reference[ real_propname ].includes( el.value );
      }
      
      if ( typeof reference[ propname ] !== 'undefined' ) {
         if ( is_radio ) {
            el.form.elements[ el.name ].value = reference[ propname ];
         } else {
            el[ is_checkbox ? 'checked' : 'value' ] = reference[ propname ];
         }
      }
      
      el.addEventListener( 'change', function () {
         if ( ~propname.indexOf( '[]' ) ) {
            const real_propname = propname.split( '[]' )[0];
            reference[ real_propname ] = [].reduce.call(
               document.body.querySelectorAll( '[id^=pixo_config_features]' ),
               function ( features, el ) {
                  if ( el.checked ) {
                     features.push( el.value );
                  }
                  return features;
               },
               []
            );
         } else {
            reference[ propname ] = is_checkbox
                  ? el.checked
                  : is_radio ? el.form.elements[ propname ].value : el.value;
         }
         config_field.value = JSON.stringify( config );
      });
   }
);

config.features.slice().reverse().forEach( function ( feature ) {
   const el = document.getElementById( 'pixo_config_features[]' + feature );
   el.parentNode.parentNode.insertBefore( el.parentNode, el.parentNode.parentNode.firstChild );
});

});
</script>
<?php
endif;
}

function pixoeditor__initGlobalEditor() {
   pixoeditor__shortcode( array(
      'fileinput' => pixoeditor__getOption( PIXOEDITOR_OPTION_FRONTEND_SELECTOR ),
      'download'  => pixoeditor__getOption( PIXOEDITOR_OPTION_FRONTEND_DOWNLOAD ),
      'stickers'  => get_option( PIXOEDITOR_OPTION_FRONTEND_STICKERS ),
   ));
}

if ( pixoeditor__getOption( PIXOEDITOR_OPTION_FRONTEND_GLOBAL ) != '0' ) {
   add_action( 'wp_head', 'pixoeditor__initGlobalEditor' );
}
