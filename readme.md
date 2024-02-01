# ruigehond embed

WordPress plugin to embed selected urls from your site elsewhere.

## Security

Other embedding will be prohibited by default, with an `X-Frame-Options` header and, optionally, a `Content Security Policy` header.
This will secure your WordPress website from a number of fairly easy attacks.

## Use htaccess

This plugin adds lines (clearly marked) at the beginning of your htaccess file.
They need not be at the beginning, but they need to be before the WordPress lines, or any other lines that corrupt the `THE_REQUEST` var.

This plugin needs `mod_headers`, `mod_rewrite` and `mod_setenvif` to be activated, but they probably already are.

## Without htaccess

When the htaccess is not processed, the plugin itself works directly with the request in the php processor.
The CSP header is not supported in that case.
Also, other plugins (especially caching plugins) may already have decided on a different route and this plugin might not work.


## Content Security Policy

You can switch on the `Content Security Policy` (or `CSP`) header in this plugin, which is the most modern way to tackle these issues.
However, other plugins may interfere, so be sure to check whether the CSP header is to your liking in practice.

This plugin will add a `CSP` header if none is present yet.
But if one is present, the `frame-ancestors` directive must be present in it for this plugin to work.
It will only set the `frame-ancestors` directive, none of the others (to not break your site).
