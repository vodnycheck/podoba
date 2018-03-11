=== WP Photo Album Plus ===
Contributors: opajaap
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=OpaJaap@OpaJaap.nl&item_name=WP-Photo-Album-Plus&item_number=Support-Open-Source&currency_code=USD&lc=US
Tags: photo, album, slideshow, video, audio, lightbox, iptc, exif, cloudinary, fotomoto, imagemagick, pdf
Version: 6.8.00
Stable tag: 6.7.12
Author: J.N. Breetvelt
Author URI: http://www.opajaap.nl/
Requires at least: 3.9
Tested up to: 4.9
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

This plugin is more than just a photo album plugin, it is a complete, highly customizable multimedia cms and display system.

== Description ==

This plugin is more than just a photo album plugin, it is a complete, highly customizable multimedia content management and display system.

Features:

* Any number of albums that contain any type of multimedia file as well as sub albums
* Full control over the display sizes, responsive as well as static
* Full control over links from any type of image
* Full control over metadata: exif, iptc can be used by keywords in item descriptions
* Up to 10 custom defined meta data fields, for albums and for media items
* Front-end uploads
* Bulk imports
* Built-in lightbox overlay system
* Built-in Google Maps to display maps based on the photo gpx exif data
* Built-in search functions on a.o. keywords and tags
* A customizable rating system
* Commenting system
* Moderate user uploads and comments
* Configurable email notification system
* 20 widgets a.o. upload, slideshow, photo of the day, top rated and commented items and many more
* Supports Cloudinary cloud storage service
* Supports Fotomoto print service
* Required maintenace is fully executed by background processes (cron jobs)
* Extended error/event logging system
* Extended documentation site: https://wppa.nl/

Plugin Admin Features:

You can find the plugin admin section under Menu Photo Albums on the admin screen.

* Albums: Create and manage Albums.
* Upload: To upload photos to an album you created.
* Import: To bulk import items to an album that are previously been ftp'd.
* Moderate: Change status of pending
* Export: To export albums
* Settings: To control the various settings to customize your needs.
* photo of the day widget settings
* Help & Info: Credits and link to the documentation site

Translations:

<ul>
<li>Dutch translation by OpaJaap himself (<a href="http://www.opajaap.nl">Opa Jaap's Weblog</a>)</li>
<li>Slovak translation by Branco Radenovich (<a href="http://webhostinggeeks.com/user-reviews/">WebHostingGeeks.com</a>)</li>
<li>Polish translation by Maciej Matysiak</li>
<li>Ukranian translation by Michael Yunat (<a href="http://getvoip.com/blog">http://getvoip.com</a>)</li>
<li>Italian translation by Giacomo Mazzullo (<a href="http://gidibao.net">http://gidibao.net</a> & <a href="http://charmingpress.com">http://charmingpress.com</a>)</li>
<li>German translation by Stefan Eggers</li>
</ul>

== Installation ==

* Standard from the plugins page

= Requirements =

* The plugin requires at least wp version 3.1.
* The server should run PHP version 5.5 or later.
* The theme should have a call to wp_head() in its header.php file and wp_footer() in its footer.php file.
* The theme should load enqueued scripts in the header if the scripts are enqueued without the $in_footer switch (like wppa.js and jQuery).
* The theme should not prevent this plugin from loading the jQuery library in its default wp manner, i.e. the library jQuery in safe mode (uses jQuery() and not $()).
* The theme should not use remove_action() or remove_all_actions() when it affects actions added by wppa+.
Most themes comply with these requirements.
However, check these requirements in case of problems with new installations with themes you never had used before with wppa+ or when you modified your theme.
* The server should have at least 64MB of memory.

== Frequently Asked Questions ==

= What do i have to do when converting to multisite? =

* See <a href="https://wppa.nl/changelog/installation-notes/#multisite" >the installation notes</a>

= Which other plugins do you recommend to use with WPPA+, and which not? =

* Recommended plugins: qTranslate, Comet Cache, Cube Points, Simple Cart & Buy Now.
* Plugins that break up WPPA+: My Live Signature.
* Google Analytics for WordPress will break the slideshow in most cases when *Track outbound clicks & downloads:* has been checked in its configuration.

= Which themes have problems with wppa+ ? =

* Photocrati has a problem with the wppa+ embedded lightbox when using page templates with sidebar.

= Are there special requirements for responsive (mobile) themes? =

* No, WPPA+ is responsive by default

= After update, many things seem to go wrong =

* After an update, always clear your browser cache (CTRL+F5) and clear your temp internetfiles, this will ensure the new versions of js files will be loaded.
* And - most important - if you use a server side caching program (like W3 Total Cache) clear its cache.
* Make sure any minifying plugin (like W3 Total Cache) is also reset to make sure the new version files are used.
* Visit the Photo Albums -> Settings page -> Table VIII-A1 and press Do it!
* When upload fails after an upgrade, one or more columns may be added to one of the db tables. In rare cases this may have been failed.
Unfortunately this is hard to determine.
If this happens, make sure (ask your hosting provider) that you have all the rights to modify db tables and run action Table VII-A1 again.

= How does the search widget work? =

* See the documentation on the WPPA+ Docs & Demos site: https://wppa.nl/docs-by-subject/search/regular-search/

= How can i translate the plugin into my language? =

* See the translators handbook: https://make.wordpress.org/polyglots/handbook/
* Here is the polyglot page for this plugin: https://translate.wordpress.org/projects/wp-plugins/wp-photo-album-plus

= How do i install a hotfix? =

* See the documentation on the WPPA+ Docs & Demos site: https://wppa.nl/docs-by-subject/development-version/

== Changelog ==

See for additional information: <a href="http://www.wppa.nl/changelog/" >The documentation website</a>

= 6.8.00 =

= Bug Fixes =

* Various minor fixes for PHP 7.2 compatibility.
* If the visitor does not have the rights to edit a photo at the front-end, there will not be a link or button to the edit page.
* On the Import and Upload Photos admin pages one can now select a target album even when there are more albums than the setting in Table IX-B6.3.
* The most recently uploaded photo had its viewcount bumped for every session when the [photo] shortcode was enabled. Fixed.
* If you use qr codes and cache them (See Table IX-K1.4), the cache will be cleared regularly to prvent the generation of too many files.

= New Features =

* On the Album Admin -> Edit screen: the photo information now also shows the EXIF data, if available.
* Shortcode [photo xxx] can now have 'random' as argument, e.g.: [photo random]. See Table IX-L for details.
* On the shortcode generator, one can optionally select one or more albums for the upload box.

= Other Changes =

* Supersearch. Selection boxes are now sorted, exif values are formatted.
Certain camera brand specific tags are now recognized and (partially) correct formatted.
* Further improved formatting of various exif tags.

= 6.7.12 =

= Bug Fixes =

* Various minor fixes for PHP 7.1 compatibility.
* Exif tags are now formatted when used as keywords.

= Other Changes =

* If Table IX-L5 is set to html, the html defaults to type sphoto.
* Improved formatting of various exif tags.

= 6.7.11 =

= Bug Fixes =

* Fixed a typo in bbpress compatibility code.
* Uploads on the [photo] shortcode generator dialog box now work as expected.
* Certain links did not work due to an internal counter bug. Fixed.

= 6.7.10 =

= Bug Fixes =

* Various minor fixes for PHP 7.1 compatibility.

= New Features =

* The shortcode generator for shortcode [photo xxx] is now also available for front-end tinymce editors.
See https://wppa.nl/docs-by-subject/advanced-topics/shortcode-photo/ for an explanation.

= 6.7.09 =

= Bug Fixes =

* Slideshow widget bug fixes:
--- all albums --- did not work, fixed.
On initial display of the activation screen, the default setting values were not shown. Fixed.
Album selectionbox was not sorted. Fixed.
Height could not be set to 0 (auto). Fixed.
* Shortcode generator bug fixes:
Fixed album enumeration delimiter; must be '.' rather than ','
Fixed colors in selectionboxes (red: required selection missing or invalid input; green: selection/input is ok).
Single image preview videos work correctly now.

= New Features =

* Slideshow widget new features:
Added checkbox 'Random' for random photo sequence. The sequence will change every pageload.
Added checkbox 'Include subalbums'.

= Other Changes =

* Sildeshow widget other changes:
You can set a maximum number of slides, to prevent heavy pageloads, especially when --- all albums --- is used.
The slideshow now always wraps around, regardless of the setting of Table IV-B8.

= 6.7.08 =

= Bug Fixes =

* Fixed a problem when using imagemagick and the upload file contains spaces in the name.

= New Features =

* Added shortcode generator for shortcode [photo]. Including upload new photo. Requires enabling the use of shortcode [photo] in Table IX-L1.

= Other Changes =

* Added 'Albums only' to Table IX-E12: Search results display.
* Changed defaults for max albums to 500 in Table IX-B6.3 and Table VII-B13. Note: The value in Table VII-B13 should be <= Table IX-B6.3.

= 6.7.07 =

= Bug Fixes =

* Fixed a spurious missing switch to flat/collapsible table button on the Album Admin page.
* Fixed a spurious error on local host systems.
* Fixed a problem displaying the widget admin page and customize screen when the system has many albums ( >> 1000 ).

= New Features =

* New shortcode attribute button for type="slide" only. Example: [wppa type="slide" album="13" button="Show me the slideshow"][/wppa]
This will hide the slideshow behind a button. Clicking the button will download the slideshow code to the browser. This is to reduce and speedup loading pages with slideshow(s).
* You can now restrict frontend uploads to one or more user roles. See Table II-H2.1

= Other Changes =

* Added a dummy index.php to all subfolders and to folders creted by wppa.

= 6.7.06 =

= Bug Fixes =

* Fixed hanging lightbox on old versions of Internet Explorer.
* Album widget frontend display on backend failed due to undefined function wppa_get_coverphoto_id(). Fixed.
* At frontend upload: selected photo tags lost accented characters. Fixed.
* Fixed an un-well formed numeric value error in php 7.

= New Features =

* Added activity widget on wp desktop
* Added link to lightbox single image on the BestOf widget.

= Other Changes =

* For clarity: The texts 'Awaiting moderation' and 'Scheduled for XXXX' are now also displayed for the owner of the photo and users with moderate rights where it applies.

= 6.7.05 =

= Bug Fixes =

* Album selectionboxes on the album admin page have alphabetically sorted content again.

= New Features =

* Negate option in search. Enter token1 !token2 to get all albums/photos that match token1 but do not match token2.

= 6.7.04 =

= Bug Fixes =

* Fixed a filesystem rights issue.
* Table IV-A18: cretae .htaccess file now works as expected.

= Other Changes =

* Fixed photo search form for mis-behaving themes like weaver ii.
* Logging of filesystem events. Table IX-A9.4.

= 6.7.03 =

= Bug Fixes =

* Album sequence in Upload page was odd since 6.7.01. Fixed.

= New Features =

* The category selection box from the widget is now also available in the search box, see Table IX-E19.
* Up to 3 selectionboxes can be configured with lists of pre-defined search tokens. See Table IX-E20.x and the widget activation screen.

= 6.7.02 =

= Bug Fixes =

* Various minor fixes that caused warnings but without any functional effect.
* Now passes PHP 7 compatibility check without any errors or warnings.
* Album sequence in Import page was odd since 6.7.01. Fixed.

= New Features =

* Table I-G5: Fullscreen button size, to set the size of the fullscreen and exit buttons on lightbox.
* You can secify the order number of the landing shortcode (occ) on the search landing page. This makes it possible to have the search box shortcode first, and the landing page shortcode second (occ=2). Table IX-E1.

= Other Changes =

* If your theme shows a magnifier glass on the search input, the Search Photos dialog will do the same.

= 6.7.01 =

= Bug Fixes =

* Under some circumstances the audiobar under slideshow was mis-aligned. Fixed.
* Many textual fixes (mainly typos).

= New Features =

* Table IX-A11: Minimum tags. These tags exist even when there are no photos that have one or more of these tags.
* Table IX-A12: Login link. Change this if you have a custom login page and you have ticked Table IV-F1: Commenting login or Table IV-E1: Rating login.

= Other Changes =

* Widgets have been revised to make the activation screens more consistent in usage and appearance.

= 6.7.00 =

= Bug Fixes =

* Many textual fixes, thanx to Stefan Eggers who has completed the german translations.
* Fixed a rounding issue in the calculation of wppa container width when the width in the shortcode was set to a fraction, causing intermittent layout issues.

= New Features =

* New shortcode attribute **timeout** for slideshows (type="slide", type="slideonly", type="slideonlyf"). Usage: [wppa type="slide" album="13" timeout="2000"][/wppa] for 2000ms. (2s.) timeout.
[wppa type="slide" album="13" timeout="random"][/wppa] for a random timeout between 2 and 7 times the animation speed.
* Completed translations for the German language, by Stefan Eggers

= 6.6.x =

* See <a href="https://wppa.nl/changelog/changelog-6-6-x/" >changelog-6-6-x/</a>

= 6.5.x =

* See <a href="https://wppa.nl/changelog/changelog-6-5-x/" >changelog-6-5-x/</a>

= 6.4.x =

* See <a href="https://wppa.nl/changelog/changelog-6-4-x/" >changelog-6-4-x/</a>

= 6.3.x =

* See <a href="https://wppa.nl/changelog/changelog-6-3-x/" >changelog-6-3-x/</a>

= 6.2.x =

* See <a href="https://wppa.nl/changelog/changelog-6-2-x/" >changelog-6-2-x/</a>

= 6.1.x =

* See <a href="https://wppa.nl/changelog/changelog-6-1-x/" >changelog-6-1-x/</a>

= 6.0.x =

* See <a href="https://wppa.nl/changelog/changelog-6-1-x/#6.0.0" >changelog-6-0-x/</a>

== About and Credits ==

* WP Photo Album Plus is extended with many new features and is maintained by J.N. Breetvelt, ( http://www.opajaap.nl/ ) a.k.a. OpaJaap
* Thanx to R.J. Kaplan for WP Photo Album 1.5.1, the basis of this plugin.

== Licence ==

WP Photo Album is released under the GNU GPL licence. ( http://www.gnu.org/copyleft/gpl.html ))