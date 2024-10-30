=== Image Editor by Pixo ===

Contributors: ickata
Tags: image editor, photo editor, replace image, image optimization, image compression
Requires at least: 3.5
Tested up to: 6.6
Stable tag: 2.3.5
Requires PHP: 5.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Replaces the default image editor in wp-admin with more powerful one - Pixo. It can also be used in the front-end.


== Description ==

https://www.youtube.com/watch?v=CJN2zQezRls

[Pixo](https://pixoeditor.com) is cross-platform image editor. It can be integrated into any web app.

This plugin does exactly this – it fully replaces WordPress' default image editor with this more powerful one, and integrates it into the front-end.

Features:

   - Remove Background
   - Resize Image & Upscale with high quality
   - Instagram-like Filters
   - Stock and custom Stickers (from file or URL)
   - Rich Text editing
   - Drawing
   - Beautiful Photo Frames
   - Shapes
   - Image filesize optimization
   - Batch editing (supported only in Media list view)
   - Updates all posts where the image has been referenced
   - Can attach to every file input field in the front-end!
   - Crop, Flip, Rotate
   - Color corrections (RGB, HSV, brightness/contrast, and more)
   - Restore previous sessions and make changes to images (undo changes, update text, and more)
   - Image optimization via [TinyPNG](https://tinypng.com)
   - Ability to choose to which image size to apply changes to (all, thumbnail, all except thumbnail)
   - Supports Block Editor (Gutenberg)
   - Supports Multisite
   - Mobile-friendly

Pixo is external service that requires registration. This plugin only wraps the service into WordPress and does the registration automatically for you. The registration is with your WordPress user's email address and a randomly generated password. To change that password visit [the Control Panel](https://pixoeditor.com:8443/cp/#/forgotten-password).

[Pixo's Privacy Policy](https://pixoeditor.com/privacy-policy/)


== Installation ==

Plugin installation is the same as every other WordPress plugin:

1. Upload the plugin files to the `/wp-content/plugins/pixo` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. The plugin will auto-generate API key for you; in case you already have registration and API key, go to Settings–>Image Editor screen to configure the plugin.


== Frequently Asked Questions ==

= Can I use Pixo to edit my images in the wp-admin Media Library =

Yes, absolutely. Pixo replaces the default WordPress image editor, bringing to you all it's features.

= Can I integrate Pixo into the front-end =

Yes! Pixo can be attached to a file input field. When the user selects an image, Pixo opens it for editing. When the changes are saved, Pixo will update the image in the file input, so it can be submitted in a form. You can globally attach Pixo to every file input on every page, or by adding a shortcode to specific pages or posts.

= Why I cannot use this editor a couple of hours after installing the plugin =

The plugin automatically creates a registration for you on [pixoeditor.com](https://pixoeditor.com/) using your WordPress installation email as username and a strong randomly generated password. [The Service](https://pixoeditor.com/) then sends you a confirmation email, with a link to confirm your registration. If your registration is not confirmed within 24 hours, the service does not operate for you. If someone created your WordPress site for you, and you don't know the email used during WordPress installation – then you can always register with your own email on [pixoeditor.com](https://pixoeditor.com/), get an API key and save it in plugin settings. You have to confirm your registration in order to use the image editing service.

= Why the editor changed its style and behavior =

Most probably 30 days after plugin activation have passed. This is Pixo's trial period, after which, unless you purchase a subscription, you loose some premium functionality, including the WordPress theme styling.

= Do I have to pay for this plugin =

The plugin itself is free. The image editing service which this plugin wraps has also a free plan, but with limitations. For full premium features there are pre-paid packages, as well as pay-per-use option. More information on the [official website](https://pixoeditor.com/).

= I do not see option to batch edit my images =

This is probably because WordPress only allows extending Media's List view with custom bulk actions, and you are probably browsing your attachments via the Grid view. Switch to List view and you will see the bulk action in the drop-down.

= The options in the Save dropdown disappeared =

These options (save as new, save and update posts, etc.) are Premium features and are available only in the free trial (30 day) and in all paid subscriptions. More information on the [official website](https://pixoeditor.com/).


== Screenshots ==

1. You upload images to your WordPress site. You click the [Edit Image] button...
2. ...and this opens your image for editing!
3. Apply filters...
4. ...add stickers...
5. ...add rich text...
6. ...choose a nice photo frame...
7. ...or a shape!
8. You can also free draw...
9. ...and fine-tune colors, RGB, HSV...
10. ...and when you are happy with the result – just save!


== Changelog ==
= 2.3.5 =
* Added missing features in the front-end integration: Background and Resize

= 2.3.4 =
* Fixed a warning when WP_DEBUG mode is set (it was breaking the sign up form)

= 2.3.3 =
* Fixed inability to batch/multiple edit more than ~20 media files

= 2.3.2 =
* Fixed issue when editing older (uploaded long time ago) files

= 2.3.1 =
* Security fix, preventing possible phishing CSRF attack

= 2.3 =
* Supports WordPress Multisite

= 2.2.1 =
* Fixed broken multiple editing in Media

= 2.2 =
* Translate Pixo in the front-end respecting the current localization

= 2.1.1 =
* Fixed a bug: Cannot select custom stickers library if previously selected 1+ and then deleted them

= 2.1 =
* UI to select Custom Stickers Libraries for wp-admin area
* UI to select Custom Stickers Libraries for front-end area when Pixo is loaded globally

= 2.0 =
* Front-end integration, with great customization
* Custom stickers collection

= 1.5.3 =
* Fixed JS error for users with less capabilities
* Fixed non-working capability setting

= 1.5.2 =
* Fixed a regression - Media view did not refresh on Save

= 1.5.1 =
* Insert Media dialog now refreshes when edited image is saved as new attachment
* Ensure Block Editor integration affects only Image block (core/image)

= 1.5 =
* Added integration to Block Editor (Gutenberg)
* Ability to choose to which image size to apply changes to (all, thumbnail, all except thumbnail)
* Settings page visual improvements

= 1.4.2 =
* Fixed issue where older version of the image was loaded in the Editor when image was edited immediately after it was saved in the previous image editing session

= 1.4.1 =
* Fixed a bug where Save button was not working in FREE mode

= 1.4 =
* Ability to save edited image as new attachment (Pixo Premium feature)
* Ability to update all posts where edited image has been referenced (Pixo Premium feature)

= 1.3.1 =
* Support for batch editing in WP versions prior to 4.7

= 1.3 =
* Batch editing (available only in Media list view) (Pixo Premium feature)

= 1.2.3 =
* Load default WordPress image editor if Pixo API key is not properly configured
* Ability to manually add API key if the user already has a registration
* Fixed behavior bug where Pixo editor was opening forever after image has been saved

= 1.2.2 =
* Preserve original base filename of edited images

= 1.2.1 =
* Fixed bug in error reporting on registration

= 1.2 =
* Added ability to configure Pixo from Settings Page – output format, quality and optimization via [TinyPNG](https://tinypng.com/)
* Pixo loads in the same language (if available) as the WordPress is set to
* Improved look of admin notifications in WP 4.0 and below

= 1.1 =
* Reworked plugin installation so the user now can register to Pixo service with his own email and password
* Adding support for even older WP versions (3.5+)
* Image editing with Pixo is now possible even if GD or ImageMagic libraries are not available

= 1.0.3 =
* Fixed image preview not updating everywhere after edit
* Compatibility fix for WP < 4.5
* Fixed inability to edit image more than once in a row

= 1.0.2 =
* Pixo Editor is now loading when editing image from Insert Media dialog

= 1.0.1 =
* Integrated Control Panel in a right panel in the Settings page

= 1.0 =
* Initial version
