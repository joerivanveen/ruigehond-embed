=== Ruigehond embed ===
Contributors: ruigehond
Tags: x-frame-options embed embedding iframe sameorigin
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=hallo@ruigehond.nl&lc=US&item_name=ruigehond-embed&no_note=0&cn=&currency_code=USD&bn=PP-DonationsBF:btn_donateCC_LG.gif:NonHosted
Requires at least: 6.0
Tested up to: 6.6
Requires PHP: 7.4
Stable tag: 1.4.1
License: GPLv3

Prevent your site from being embedded. Select specific urls that may be embedded from specific origins.

== Description ==

Plugin to embed selected urls from your site elsewhere.

= Security =

Other embedding will be prohibited by default, with an `X-Frame-Options` header and, optionally, a `Content Security Policy` header.
This will secure your WordPress website from a number of fairly easy attacks.

To make this plugin especially useful you can now allow (third party) websites to embed specific urls from your site.
Easily reuse forms or other content from your main site on satellite sites you own, without opening up any of them to attack.

= Quick setup =

Activate the plugin and go to Settings -> Ruigehond embed.
Add a reference (e.g. `general-contact-form`) in the _title_ field and save the settings.
Add a slug it should serve (e.g. `/contact-clean/`) in the _embed_ field.
Add urls that may embed this, aka referrers, (e.g. `https://my-satellite.site`) in the textarea.

= Embedding =

Install the plugin on your satellite site. This has the added benefit of locking down that site as well.

Use the simple shortcode on that site to generate an iframe with the embedded content:
`[ruigehond-embed src="https://my-main.site/ruigehond_embed/general-contact-form"]`

Watch the form magically and safely be embedded. Other sites will continue to not be able to embed your content.

You can also embed using a regular iframe in html, as long as the referrer is whitelisted.
However, by using the plugin and shortcode, the height of the iframe will automatically be adjusted to fit the content.

= Use htaccess =

This plugin adds lines (clearly marked) at the beginning of your htaccess file.
They need not be at the beginning, but they need to be before the WordPress lines, or any other lines that corrupt the `THE_REQUEST` var.

This plugin needs `mod_headers`, `mod_rewrite` and `mod_setenvif` to be activated, but they probably already are.

= Without htaccess =

When the htaccess is not processed, the plugin itself works directly with the request in the php processor.
The CSP header is not supported in that case.
Also, other plugins (especially caching plugins) may already have decided on a different route and this plugin might not work.

= Content Security Policy =

You can switch on the `Content Security Policy` (or `CSP`) header in this plugin, which is the most modern way to tackle these issues.
However, other plugins may interfere, so be sure to check whether the CSP header is to your liking in practice.

This plugin will add a `CSP` header if none is present yet.
But if one is present, the `frame-ancestors` directive must be present in it for this plugin to work.
It will only set the `frame-ancestors` directive, none of the others (to not break your site).

== Installation ==

Install the plugin by clicking ‘Install now’ below, or the ‘Download’ button, and put the `ruigehond-embed` folder in your `plugins` folder. Don’t forget to activate it.

== Screenshots ==
1. Setting screen with one embed entry consisting of the title, the (local) url to embed and a list of authorized embedders (only 1).

== Changelog ==

1.4.1: restore query string processing in htaccess and short circuit unnecessary php processing

1.4.0: release into the wild
