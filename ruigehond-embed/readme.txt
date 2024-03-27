=== WP Rewrite debugger ===
Contributors: ruigehond
Tags: x-frame-options embed embedding iframe sameorigin
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=hallo@ruigehond.nl&lc=US&item_name=ruigehond-embed&no_note=0&cn=&currency_code=USD&bn=PP-DonationsBF:btn_donateCC_LG.gif:NonHosted
Requires at least: 6.0
Tested up to: 6.5
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv3

Prevent your site from being embedded. Select specific urls that may be embedded from specific origins.

== Description ==

Install this plugin on your site to prevent your pages from being embedded elsewhere. Some easy to mount attacks are based on this.

To make this plugin especially useful you can now allow (third party) websites to embed specific urls from your site.
Easily reuse forms or other content from your main site on satellite sites you own, without opening up any of them to attack.

Add a reference (e.g. `general-contact-form`) and a slug it should serve (e.g. `/contact-clean/`).
Add urls that may embed this (e.g. `https://my-satellite.site`).

Install the plugin on your satellite site. This has the added benefit of locking down that site as well.

Use the simple shortcode on that site to generate an iframe with the embedded content:
`[ruigehond-embed src="https://my-main.site/ruigehond_embed/general-contact-form"]`

Watch the form magically and safely be embedded. Other sites will continue to not be able to embed your content.

Enjoy the plugin! Let me know if you have any questions.

== Installation ==

Install the plugin by clicking ‘Install now’ below, or the ‘Download’ button, and put the `ruigehond-embed` folder in your `plugins` folder. Don’t forget to activate it.

== Screenshots ==
1. Setting screen.

== Changelog ==

1.0.0: release
