Pixo = window.Pixo || {};

Pixo.DEFAULT_CONFIG = {
   output   : {
      format   : 'auto',
      quality  : 1,
      optimize : false,
   },
};

( function () {

const $ = jQuery;
const attachments_urls = {};

function fetchAttachmentUrl( id, callback ) {
   if ( attachments_urls[id] ) {
      return callback( attachments_urls[id] );
   }
   attachments_urls[id] = wp.media.attachment( id ).get( 'url' );
   if ( attachments_urls[id] ) {
      return callback( attachments_urls[id] );
   }
   wp.media.attachment( id ).fetch().then( function () {
      attachments_urls[id] = wp.media.attachment( id ).get( 'url' );
      callback( attachments_urls[id] );
   });
}

function getOptions() {
   return jQuery.extend( true, { language : Pixo.LOCALE }, Pixo.CONFIG || Pixo.DEFAULT_CONFIG, {
      apikey   : Pixo.APIKEY,
      type     : 'modal',
      theme    : 'wordpress',
      alert    : true,
      actions  : [{
         caption     : 'Save',
         action      : 'SAVEANDCLOSE',
         icon        : 'download',
         options     : [
            {
               caption     : 'and overwrite',
               action      : Pixo.ACTIONS.SAVE_OVERWRITE,
            },
            {
               caption     : 'as new',
               action      : Pixo.ACTIONS.SAVE_NEW,
            },
            {
               caption     : 'overwrite and update posts',
               action      : Pixo.ACTIONS.SAVE_OVERWRITE_UPDATE,
            },
            {
               caption     : 'as new and update posts',
               action      : Pixo.ACTIONS.SAVE_NEW_UPDATE,
            },
         ],
         onClick     : function ( action ) {
            this.setAction( action );
            this.saveAndClose( function () {} );
         },
      }].concat( Pixo.ACTIONS_MENU.slice(1) ),

      filterStickers: function (stickers) {
         return Pixo.STICKERS && Pixo.STICKERS.length ? Pixo.STICKERS : stickers;
      },
   });
}

function showImageSizeModalIfNeeded( callback ) {
   if (Pixo.IMAGE_SIZE) {
      return callback(Pixo.IMAGE_SIZE, false);
   }

   $([
      '<form>',
      '<p>Apply changes to:</p>',
      '<p><label><input type="radio" name="target" value="',
      Pixo.IMAGE_SIZES.all,
      '" checked="checked" /> All image sizes</label></p>',
      '<p><label><input type="radio" name="target" value="',
      Pixo.IMAGE_SIZES.thumbnail,
      '" /> Thumbnail</label></p>',
      '<p><label><input type="radio" name="target" value="',
      Pixo.IMAGE_SIZES.other,
      '" /> All sizes except thumbnail</label></p>',
      '<p><label><input type="checkbox" value="1" name="',
      Pixo.IMAGE_SIZES.other,
      '" /> Remember my choice and don\'t ask again</label><br />',
      '<small style="display: none">You can always update this setting from Settings –> Image Editor</small>',
      '</p>',
      '</form>',
   ].join('')).dialog({
      modal: true,
      title: 'Thumbnail Settings',
      dialogClass: 'wp-dialog pixo-dialog',
      autoOpen: true,
      closeOnEscape: false,
      'buttons': {
         Apply: function () {
            $(this).dialog('close');
         },
      },
      open: function () {
         const form = $(this);
         const small = form.find('small');
         const remember = form.find('input[type=checkbox]');
         remember.click(function () {
            small.css('display', this.checked ? '' : 'none');
         });
      },
      close: function () {
         const form = $(this);
         const target = form.find('input[type=radio]:checked')[0].value;
         const remember = form.find('input[type=checkbox]')[0].checked;
         callback(target, remember);
      },
   });
}

Pixo.editImage = function ( id, url, callback ) {
   let action = Pixo.ACTIONS.SAVE_OVERWRITE;
   const config = $.extend( true, getOptions(), {
      onCancel  : function () {
         callback( id );
      },
      onSave    : function ( output ) {
         function upload( target, remember ) {
            Pixo.upload([{
               id            : id,
               blob          : output.toBlob(),
               basefilename  : Pixo.getBaseFilename( Pixo.parseURL( url ).pathname ),
            }], action, target, remember, function ( error, files ) {
               if ( error || files[0].error ) {
                  return alert( error || files[0].error );
               }
               
               if ( ~[ Pixo.ACTIONS.SAVE_NEW, Pixo.ACTIONS.SAVE_NEW_UPDATE ].indexOf( action ) ) {
                  switch ( window.typenow ) {
                  case 'attachment':
                     const params = Pixo.getSearchParams();
                     params.post = files[0].id;
                     location.search = Pixo.buildSearchParams(params);
                     return;
                  case 'post':
                     function refresh() {
                        setTimeout( function () {
                           const gallery = wp.media.frame.content.get('gallery');
                           if (gallery.collection) {
                              gallery.collection.props.set({ ignore: (+ new Date()) });
                           } else {
                              refresh();
                           }
                        }, 50);
                     }
                     refresh();
                  }
               }
               
               var url = files[0].url;
               var newid = files[0].id || id;
               
               attachments_urls[newid] = url;
               wp.media && wp.media.attachment( newid ).fetch();
               ( window.attachment_url || '' ).value = url;
               
               var imgs = document.querySelectorAll([
                  '#thumbnail-head-' + newid + ' img',
                  '[data-id="' + newid + '"] img',
                  'img.details-image',
                  '.thumbnail.thumbnail-image img',
                  '.media-frame-content .image-details .image img',
               ]);
               url += '?' + Math.random();
               for ( var i=0, l=imgs.length; i < l; i++ ) {
                  imgs[i].src = url;
               }
               callback( newid, true );
            });
         }

         showImageSizeModalIfNeeded( upload );
      },
   });
   
   const bridge = new Pixo.Bridge( config );
   bridge.setAction = function ( selected_action ) {
      action = selected_action;
   };
   bridge.edit( Pixo.extendUrlSearchParams( url, { r: Math.random() } ) );
}

Pixo.batchEdit = function ( images, callback ) {
   let action = Pixo.ACTIONS.SAVE_OVERWRITE;
   const config = $.extend( true, getOptions(), {
      onSave : function ( output ) {
         function upload( target, remember ) {
            Pixo.upload( output.toBlobs().map( function ( blob, i ) {
               return {
                  id            : images[i].id,
                  blob          : blob,
                  basefilename  : Pixo.getBaseFilename( Pixo.parseURL( images[i].src ).pathname ),
               };
            }), action, target, remember, function ( error ) {
               if ( error ) {
                  alert( error );
               }
               callback();
            });
         }

         showImageSizeModalIfNeeded( upload );
      },
   });
   
   const bridge = new Pixo.Bridge( config );
   bridge.setAction = function ( selected_action ) {
      action = selected_action;
   };
   bridge.edit( images.map( function ( image ) { return image.src; } ) );
}

Pixo.editMultiple = function ( images, callback ) {
   let action = Pixo.ACTIONS.SAVE_OVERWRITE;
   const config = $.extend( true, getOptions(), {
      onSave : function () {
         const args = arguments;
         function upload( target, remember ) {
            Pixo.upload( [].map.call( args, function ( arg, i ) {
               return {
                  id            : images[i].id,
                  blob          : arg.toBlob(),
                  basefilename  : Pixo.getBaseFilename( Pixo.parseURL( images[i].src ).pathname ),
               };
            }), action, target, remember, function ( error ) {
               if ( error ) {
                  alert( error );
               }
               callback();
            });
         }

         showImageSizeModalIfNeeded( upload );
      },
   });
   const pixo = new Pixo.Bridge( config );
   pixo.setAction = function ( selected_action ) {
      action = selected_action;
   };
   pixo.edit.apply( pixo, images.map( function ( image ) { return image.src; } ) );
}

Pixo.upload = function ( files, action, target, remember, callback ) {
   var form = new FormData();
   form.append( 'action', 'pixoeditor__uploadFiles' );
   form.append( 'upload-action', action );
   form.append( Pixo.IMAGE_SIZES.optionname, target );
   form.append( Pixo.IMAGE_SIZES.remember, remember );
   
   Pixo.IMAGE_SIZE = remember ? target : Pixo.IMAGE_SIZE;
   
   var ids = [];
   for ( var i=0, l=files.length; i < l; i++ ) {
      with ( files[i] ) {
         ids.push( Number( id ) );
         form.append( 'image' + i, blob, basefilename + '.' + Pixo.getBlobExtension( blob ) );
      }
   }
   
   form.append( 'ids', JSON.stringify( ids ) );
   
   var xhr  = new XMLHttpRequest();
   xhr.open( 'post', ajaxurl );
   xhr.onload = function () {
      var response = [];
      try {
         response = JSON.parse( xhr.responseText );
      } catch ( err ) {
         return callback( err, response );
      };
      callback( null, response );
   };
   xhr.onerror = callback;
   xhr.send( form );
}

Pixo.parseURL = function ( url ) {
   var a = document.createElement( 'a' );
   a.href = url || location.href;
   return a;
}

Pixo.getBaseFilename = function ( path ) {
   return path.split( '/' ).pop().split( '.' ).slice( 0, -1 ).join( '.' );
}

Pixo.getBlobExtension = function ( blob ) {
   return blob.type.split( '/' ).pop() || 'png';
}

Pixo.getSearchParams = function ( url ) {
   return Pixo.parseURL( url ).search.slice( 1 ).split( '&' ).reduce( function ( params, pair ) {
      if ( ! pair ) {
         return params;
      }
      const parts = pair.split( '=' );
      params[ parts.shift() ] = parts.join( '=' );
      return params;
   }, {});
}

Pixo.buildSearchParams = function ( params ) {
   const parts = [];
   for ( var name in params ) {
      if ( params.hasOwnProperty( name ) ) {
         parts.push( name + '=' + params[ name ] );
      }
   }
   return '?' + parts.join( '&' );
}

Pixo.extendUrlSearchParams = function ( url, params ) {
   const parsed = Pixo.parseURL( url );
   parsed.search = Pixo.buildSearchParams(
      $.extend( true, {}, Pixo.getSearchParams( url ), params )
   );
   return parsed.href;
}

$( function ( $ ) {
   if ( Pixo.WP_MINOR_VERSION >= 5.5 ) {
      // TODO: Add support for older versions (till 5.0)
      integrateInBlockEditor();
   }

   if ( ! window.imageEdit ) {
      return;
   }
   
   var open    = imageEdit.open;
   var is_open = false;
   
   function onClose( id, is_saved ) {
      is_open = false;
      if ( is_saved && imageEdit._view ) {
         imageEdit._view.save();
      } else {
         imageEdit.close( id );
      }
   }
   
   imageEdit.open = function ( id ) {
      if ( ! Pixo.APIKEY ) {
         $( '<p>Pixo Editor installation is not complete. Please go to <a href="' + Pixo.OPTIONS_URL + '">Settings -> Image Editor</a> and finish the installation.</p>' ).dialog({
            modal: true,
            dialogClass    : 'wp-dialog pixo-dialog',
            autoOpen       : true,
            closeOnEscape  : true,
         });
         return callOriginalOpen( this, arguments );
      }
      if ( is_open ) {
         return callOriginalOpen( this, arguments );
      }
      is_open = true;
      
      var url = ( window.attachment_url || '' ).value;
      if ( url ) {
         Pixo.editImage( id, url, onClose );
      } else {
         fetchAttachmentUrl( id, function ( url ) {
            Pixo.editImage( id, url, onClose );
         });
      }
      return callOriginalOpen( this, arguments );
   };
   
   function callOriginalOpen( context, args ) {
      return open.apply( context, args );
   }
});

function integrateInBlockEditor() {
   if ( ! wp.element ) {
      return;
   }
   
   var el = wp.element.createElement;

   var withBlockControls = wp.compose.createHigherOrderComponent(function (BlockEdit) {
      return function (props) {
         if (props.name !== 'core/image') {
            return el(
               BlockEdit,
               props
            );
         }

         return el(
            wp.element.Fragment,
            {},
            el(
               BlockEdit,
               props
            ),
            el(
               wp.blockEditor.BlockControls,
               {},
               el(
                  wp.components.ToolbarGroup,
                  {},
                  el(
                     wp.components.ToolbarButton,
                     {
                        title: 'Edit image in Pixo Image Editor',
                        onClick: function () {
                           handleBlockEditorEdit( props );
                        },
                     },
                     'Edit'
                  )
               )
            )
         );
      };
   }, 'withBlockControls');

   wp.hooks.addFilter('editor.BlockEdit', 'pixo/with-inspector-controls', withBlockControls);
}

function handleBlockEditorEdit( props ) {
   const id = props.attributes.id;
   fetchAttachmentUrl(id, function (url) {
      let action = Pixo.ACTIONS.SAVE_OVERWRITE;
      const config = $.extend(true, getOptions(), {
         onSave: function (output) {
            function upload(target, remember) {
               Pixo.upload([{
                  id: id,
                  blob: output.toBlob(),
                  basefilename: Pixo.getBaseFilename(Pixo.parseURL(url).pathname),
               }], action, target, remember, function (error, files) {
                  if (error) {
                     return alert(error);
                  }
                  var url = files[0].url;
                  var id = files[0].id;
                  attachments_urls[id] = url;

                  const attachment = wp.media.attachment(id);
                  attachment.fetch().then(function () {
                     props.setAttributes({
                        id: id,
                        url: attachment.get('sizes')[props.attributes.sizeSlug].url,
                     });
                  });
               });
            }

            showImageSizeModalIfNeeded(upload);
         },
      });

      const bridge = new Pixo.Bridge(config);
      bridge.setAction = function (selected_action) {
         action = selected_action;
      };
      bridge.edit(url);
   });
}

})();
