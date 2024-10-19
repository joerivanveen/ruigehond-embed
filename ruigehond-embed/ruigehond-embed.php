<?php
declare( strict_types=1 );
/*
Plugin Name: Ruigehond embed
Plugin URI: https://github.com/joerivanveen/ruigehond-embed
Description: Embed selected urls from your website elsewhere
Version: 1.4.1
Requires at least: 5.0
Tested up to: 6.6
Requires PHP: 7.4
Author: Joeri van Veen
Author URI: https://wp-developer.eu
License: GPLv3
Text Domain: ruigehond-embed
Domain Path: /languages/
*/

// TODO maybe add csp functionality to php as well
defined( 'ABSPATH' ) || die();
// This is plugin nr. 15 by Ruige hond. It identifies as: ruigehond015.
const RUIGEHOND015_VERSION = '1.4.1';
$ruigehond015_basename = plugin_basename( __FILE__ );
// Startup the plugin
add_action( 'init', 'ruigehond015_run' );
add_action( "activate_$ruigehond015_basename", 'ruigehond015_activate' );
add_action( "deactivate_$ruigehond015_basename", 'ruigehond015_deactivate' );
/* this is for the parent website: */
add_shortcode( 'ruigehond-embed', 'ruigehond015_shortcode' );
function ruigehond015_shortcode( $attributes = [], $content = null, $short_code = 'ruigehond-embed' ): string {
	if ( false === isset( $attributes['src'] ) ) {
		return 'Ruigehond embed: src attribute is missing.';
	}
	$src = $attributes['src'];
	$url = wp_parse_url( $src );
	if ( ! isset( $url['scheme'] ) || ! in_array( $url['scheme'], array( 'http', 'https' ) ) ) {
		return 'Ruigehond embed: src not recognized as a valid iframe src. Use a fully qualified url.';
	}
	wp_enqueue_script( 'ruigehond015_snuggle_javascript', plugin_dir_url( __FILE__ ) . 'snuggle.js', [], RUIGEHOND015_VERSION );

	return "<iframe style='width:100%;border:0;frame-border:0;height:100vh;overflow:auto;' loading='eager' src='$src'></iframe>";
}

//
function ruigehond015_run(): void {
	if ( is_admin() ) {
		load_plugin_textdomain( 'ruigehond-embed', '', dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
		add_action( 'admin_init', 'ruigehond015_settings' );
		add_action( 'admin_menu', 'ruigehond015_menuitem' );
		add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'ruigehond015_settingslink' ); // settings link on plugins page

		return;
	}

	wp_enqueue_script( 'ruigehond015_unframe_javascript', plugin_dir_url( __FILE__ ) . 'unframe.js', [], RUIGEHOND015_VERSION );

	if (getenv('RUIGEHOND015_REQUEST')) return; // processing already done in htaccess

	$vars = get_option( 'ruigehond015' );

	if ( isset( $vars['xframe'] ) && 'DENY' === $vars['xframe'] ) {
		header( 'X-Frame-Options: DENY' );
	} else {
		header( 'X-Frame-Options: SAMEORIGIN' );
	}

	if ( false === isset( $vars['titles'] ) ) {
		return;
	}

	if ( false === isset( $_SERVER['REQUEST_URI'] ) ) {
		return;
	}

	$url = trim( sanitize_url( $_SERVER['REQUEST_URI'], array( 'http', 'https' ) ), '/' );

	if ( 0 === strpos( $url, 'ruigehond_embed/' )
	     && ( $url = str_replace( 'ruigehond_embed/', '', $url ) )
	     && true === isset( $vars['titles'][ $url ] )
	) {
		$redirect = $vars['titles'][ $url ];
		if ( false === strpos( $redirect, '?' ) && false === strpos( $redirect, '#' ) ) {
			$redirect = "$redirect/"; // avoid prevent the extra 301 redirect from WordPress
		}
		if ( 0 !== strpos( $redirect, 'http://' )
		     && 0 !== strpos( $redirect, 'https://' )
		     && 0 !== strpos( $redirect, '//' )
		) {
			$redirect = "../$redirect"; // skip over the ruigehond_embed part in url
		}
		wp_redirect( $redirect, 307, 'Ruigehond-embed' );
		die(); // Necessary for otherwise sometimes a 404 is served. Also, wp_die does not work here.
	} elseif ( true === isset( $vars['embeds'][ $url ] )
	           && true === isset( $_SERVER['HTTP_REFERER'] )
	           && true === is_array( $allow = $vars['embeds'][ $url ] )
	) {
		$referrer = sanitize_url( $_SERVER['HTTP_REFERER'] );
		if ( false === filter_var( $referrer, FILTER_VALIDATE_URL ) ) {
			return;
		}
		$parts    = wp_parse_url( $referrer );
		$referrer = "{$parts['scheme']}://{$parts['host']}/";
		if ( true === in_array( $referrer, $allow )
		     // allow referrers with www. as well, when set that it should:
		     || ( true === $vars['wwwtoo'] && false !== strpos( $referrer, '://www.' )
		          && true === in_array( str_replace( '://www.', '://', $referrer ), $allow ) )
		) {
			// todo what about Content Security Policy frame ancestors here?
			add_action( 'send_headers', static function () use ( $referrer ) { // frontend
				header( "X-Ruigehond-Embed: Embed allowed from $referrer" );
				header_remove( 'X-Frame-Options' );
			}, 99 );
			add_action( 'admin_init', static function () use ( $referrer ) { // admin
				header( "X-Ruigehond-Embed: Embed allowed from $referrer" );
				header_remove( 'X-Frame-Options' );
			}, 99 );
		}
	}
}

function ruigehond015_get_safe_url( string $url ): string {
	$parts = explode( '/', trim( $url ) );
	foreach ( $parts as $index => $part ) {
		if ( 'http:' === $part || 'https:' === $part ) {
			continue;
		}
		$parts[ $index ] = str_replace(
			array( '%3F', '%3D', '%26' ),
			array( '?', '=', '&' ),
			rawurlencode( rawurldecode( $part ) )
		);
	}

	return implode( '/', $parts );
}

function ruigehond015_settingspage(): void {
	if ( ! current_user_can( 'administrator' ) ) {
		return;
	}
	echo '<div class="wrap ruigehond015"><h1>Ruigehond embed</h1><p>';
	echo esc_html__( 'This plugin sends an X-Frame-Options header for all requests, to protect your site.', 'ruigehond-embed' );
	echo '<br/>';
	echo esc_html__( 'Specify your exceptions below, to be able to have specific pages of your site embedded from specific other domains.', 'ruigehond-embed' );
	echo '<br/>';
	$str = __( 'On the site where you want to embed a page, you can use the shortcode %s to embed an url, if you have installed this plugin.', 'ruigehond-embed' );
	if ( 1 === substr_count( $str, '%s' ) ) {
		echo esc_html( sprintf( $str, '[ruigehond-embed src="&lt;Iframe src&gt;"]' ) );
		echo ' ', esc_html__( 'You do not need to specify an exception there.', 'ruigehond-embed' );
	}
	echo '</p><form action="options.php" method="post" id="ruigehond015-settings-form">';
	// output security fields for the registered setting
	settings_fields( 'ruigehond015' );
	// output setting sections and their fields
	do_settings_sections( 'ruigehond015' );
	// output save settings button
	submit_button( esc_html__( 'Save Settings', 'ruigehond-embed' ) );
	echo '</form></div>';
}

function ruigehond015_settings(): void {
	register_setting( 'ruigehond015', 'ruigehond015', 'ruigehond015_settings_validate' );
	// don’t bother with all this if we’re not even on the settings page
	if ( false === isset( $_GET['page'] ) || 'ruigehond-embed' !== $_GET['page'] ) {
		return;
	}
	// scripts used on the settings page
	wp_enqueue_style( 'ruigehond015_admin_stylesheet', plugin_dir_url( __FILE__ ) . 'admin.css', [], RUIGEHOND015_VERSION );
	wp_enqueue_script( 'ruigehond015_admin_javascript', plugin_dir_url( __FILE__ ) . 'admin.js', [], RUIGEHOND015_VERSION );
	// register a new section in the page
	add_settings_section(
		'ruigehond_embed_settings', // section id
		esc_html__( 'Set your options', 'ruigehond-embed' ), // title
		function () {
			echo '<p>';
			echo esc_html__( 'To add an entry, fill in the title at the bottom of the form.', 'ruigehond-embed' );
			echo '<br/>';
			echo esc_html__( 'To remove an entry, empty its title field.', 'ruigehond-embed' );
			echo '<br/>';
			$str = __( 'Remember to hit ‘%s’.', 'ruigehond-embed' );
			if ( 1 === substr_count( $str, '%s' ) ) {
				echo sprintf( esc_html( $str ), esc_html__( 'Save Settings', 'ruigehond-embed' ) );
			}
			echo '</p>';
		}, //callback
		'ruigehond015' // page
	);

	$vars = (array) get_option( 'ruigehond015' );

	//echo PHP_EOL, '<!-- RUIGEHOND015', PHP_EOL, var_export( $vars, true ), PHP_EOL, '-->', PHP_EOL;

	$titles = $vars['titles'] ?? array();
	$embeds = $vars['embeds'] ?? array();
	$index  = 1;
	ksort( $titles ); // alphabetical is probably nicer for the user

	$host         = site_url();
	$explanations = array(
		'title' => sprintf( __( 'Iframe src: %s/ruigehond_embed/%s', 'ruigehond-embed' ), $host, '%s' ),
		'embed' => __( 'Local or fully qualified uri that will be embedded.', 'ruigehond-embed' ),
		'allow' => __( 'Mandatory list of referrers that may embed this.', 'ruigehond-embed' ),
		'xfram' => sprintf( __( '%1$s header sent by default, possible values are %2$s and %3$s.', 'ruigehond-embed' ), 'X-Frame-Options', 'DENY', 'SAMEORIGIN' ),
		'csp_h' => __( 'Set CSP header. Be aware that other plugins could also mess with this header.', 'ruigehond-embed' ),
		'www_2' => __( 'As standard allow the www subdomain for each domain as well.', 'ruigehond-embed' ),
	);

	ruigehond015_add_settings_field( 'xfram', 0, $vars['xframe'] ?? '', $explanations );
	ruigehond015_add_settings_field( 'csp_h', 0, $vars['setcsp'] ?? false, $explanations );
	ruigehond015_add_settings_field( 'www_2', 0, $vars['wwwtoo'] ?? false, $explanations );

	foreach ( $titles as $title => $embed ) {
		ruigehond015_add_settings_field( 'title', $index, (string) $title, $explanations );
		ruigehond015_add_settings_field( 'embed', $index, (string) $embed, $explanations );
		$keyed = ruigehond015_get_key_for_embed( $embed );
		if ( true === isset( $embeds[ $keyed ] ) ) {
			if ( is_array( $embeds[ $keyed ] ) ) {
				ruigehond015_add_settings_field( 'allow', $index, $embeds[ $keyed ], $explanations );
				$embeds[ $keyed ] = false; // prevent a second textarea with the same values
			}
		} else {
			ruigehond015_add_settings_field( 'allow', $index, array(), $explanations );
		}
		++ $index;
	}
	ruigehond015_add_settings_field( 'title', 0, '', $explanations );
}

function ruigehond015_get_key_for_embed( string $embed ): string {
	if ( 0 === strpos( $embed, 'https://' )
	     || 0 === strpos( $embed, 'http://' )
	     || 0 === strpos( $embed, '//' )
	) {
		$parts = explode( '/', $embed );

		return implode( '/', array_slice( $parts, 3 ) );
	} else {
		return $embed;
	}
}

function ruigehond015_add_settings_field( $name, $index, $value, $explanations ): void {
	add_settings_field(
		"ruigehond015_{$name}_$index",
		$name,
		function ( $args ) {
			$value = $args['value'];
			$name  = $args['name'];
			if ( ! in_array( $name, array( 'title', 'embed', 'allow', 'xfram', 'csp_h', 'www_2' ) ) ) {
				return; // JOERI no valid $name
			}
			$index    = (int) $args['index'];
			$input_id = "ruigehond015[$name][$index]";
			if ( 'title' === $name ) {
				// add the space to be certain the link will be split by javascript
				$explanation = sprintf( $args['explanation'], ( $value ?: '{{title}}' ) . ' ' );
			} else {
				$explanation = $args['explanation'];
			}
			if ( is_array( $value ) ) {
				echo '<textarea name="', esc_html( $input_id ), '" id="', esc_html( $input_id ), '">';
				echo esc_html( implode( PHP_EOL, $value ) );
				echo '</textarea>';
			} elseif ( is_bool( $value ) ) {
				echo '<input type="checkbox" name="', esc_html( $input_id ), '" id="', esc_html( $input_id ), '"';
				if ( true === $value ) {
					echo ' checked="checked"';
				}
				echo '/>';
			} else {
				echo '<input type="text" name="', esc_html( $input_id ), '" id="', esc_html( $input_id ), '" value="';
				echo esc_html( $value );
				echo '" class="regular-text"/>';
			}
			if ( isset( $args['explanation'] ) ) {
				echo '<label for="', esc_html( $input_id ), '" class="ruigehond015 explanation ', esc_html( $name ), '"><em>';
				echo wp_kses_post( $explanation );
				echo '</em></label>';
			}
		},
		'ruigehond015',
		'ruigehond_embed_settings',
		array(
			'name'        => $name,
			'index'       => $index,
			'value'       => $value,
			'explanation' => $explanations[ $name ],
		) // args
	);
}

function ruigehond015_settings_validate( $input ): array {
	$vars = (array) get_option( 'ruigehond015' );

	$vars['titles'] = array();
	$vars['embeds'] = array();
	$vars['xframe'] = 'SAMEORIGIN';
	$vars['wwwtoo'] = 'on' === $input['www_2'][0];
	$set_csp_header = false;

	if ( 'DENY' === $input['xfram'][0] ) {
		$vars['xframe'] = 'DENY';
	}

	if ( 'on' === $input['csp_h'][0] ) {
		$set_csp_header = true;
	}

	$titles = $input['title'] ?? array();
	$embeds = $input['embed'] ?? array();
	$allows = $input['allow'] ?? array();

	$titles_had = array();
	$keyeds_had = array();

	foreach ( $titles as $index => $title ) {
		$embed = $embeds[ $index ] ?? null;
		$allow = $allows[ $index ] ?? null;

		if ( '' !== $title ) {
			$title = sanitize_title( $title );
			$embed = isset( $embed ) ? trim( $embed, '/' ) : '';
			// no hashtags allowed
			if ( false !== strpos( $embed, '#' ) ) {
				$embed = explode( '#', $embed )[0];
			}
			//
			$embed = ruigehond015_get_safe_url( $embed ); // joeri
			$keyed = ruigehond015_get_key_for_embed( $embed );

			if ( in_array( $title, $titles_had ) ) {
				$old_title = $title;
				$title     = random_int( 0, 9 ) . "_$title";
				while ( in_array( $title, $titles_had ) ) {
					$title = random_int( 0, 9 ) . $title;
				}
				add_settings_error(
					'ruigehond_embed',
					"ruigehond_embed_htaccess",
					sprintf( esc_html__( 'Duplicate title not allowed %s -> %s', 'ruigehond-embed' ), $old_title, $title )
				);
			}

			if ( in_array( $keyed, $keyeds_had ) ) {
				$embed = $keyed = md5( (string) random_int( 100000, 999999 ) );
				add_settings_error(
					'ruigehond_embed',
					'ruigehond_embed_htaccess',
					sprintf( esc_html__( 'Embed for %s not allowed, duplicate key will lead to trouble. Substituted %s', 'ruigehond-embed' ), $title, $embed )
				);
			}

			$vars['titles'][ $title ] = $embed;

			$titles_had[] = $title;
			$keyeds_had[] = $keyed;

			if ( '' === $keyed ) {
				add_settings_error(
					'ruigehond_embed',
					'ruigehond_embed_key_is_empty',
					sprintf( esc_html__( 'Warning for %s: allowing the root of your site to be embedded effectively allows everything to be embedded, this may not be what you want. Specify a distinct url in ‘embed’ then.', 'ruigehond-embed' ), $title ),
					'warning'
				);
			}

			if ( null === $allow ) {
				continue; // when there are duplicate keys / referrers
			}

			$allow = explode( PHP_EOL, $allow );
			$valid = $vars['embeds'][ $keyed ] ?? array();
			foreach ( $allow as $index => $referrer ) {
				$referrer = trim( $referrer ); // no whitespaces...
				if ( false === filter_var( $referrer, FILTER_VALIDATE_URL ) ) {
					continue;
				}
				$parts    = wp_parse_url( $referrer );
				$referrer = "{$parts['scheme']}://{$parts['host']}/";
				if ( false === in_array( $referrer, $valid ) ) {
					$valid[] = $referrer;
				}
			}

			sort( $valid ); // always alphabetically
			$vars['embeds'][ $keyed ] = $valid;
		}
	}
	$vars['setcsp'] = $set_csp_header;

	return ruigehond015_process_htaccess( $vars );
}

function ruigehond015_process_htaccess( array $vars ): array {
	if ( false === isset( $vars['titles'], $vars['embeds'], $vars['xframe'], $vars['wwwtoo'] ) ) {
		return $vars;
	}
	$set_csp_header = ( true === isset( $vars['setcsp'] ) && true === $vars['setcsp'] );
	if ( 'DENY' === $vars['xframe'] ) {
		$frame_ancestors = 'none';
		$x_frame_options = 'DENY';
	} else {
		$frame_ancestors = 'self';
		$x_frame_options = 'SAMEORIGIN';
	}
	ob_start();
	echo '# These directives are maintained by Ruigehond-embed, DO NOT EDIT', PHP_EOL;
	echo '# They must appear BEFORE WordPress\' own directives, or the embedding will not work because %{THE_REQUEST} will be null', PHP_EOL;
	echo '#', PHP_EOL;
	echo '<IfModule mod_setenvif.c>', PHP_EOL;
	echo '<IfModule mod_headers.c>', PHP_EOL;
	echo 'Header set X-Frame-Options "', $x_frame_options, '"', PHP_EOL;
	if ( true === $set_csp_header ) {
		// set the csp header before processing
		echo 'Header setifempty Content-Security-Policy "frame-ancestors \'', $frame_ancestors, '\';"', PHP_EOL;
	}
	echo '<IfModule mod_rewrite.c>', PHP_EOL;
	echo 'RewriteEngine On', PHP_EOL;
	echo '# work with the originally requested uri, because otherwise all bets are off', PHP_EOL;
	// apparently a # is allowed in the regex, without being interpreted as a middle of the line comment which is not allowed...
	echo 'RewriteCond %{THE_REQUEST} \s/+([^\s?]+)([^#\s]*)', PHP_EOL;
	echo 'RewriteRule ^ - [E=RUIGEHOND015_REQUEST:%1%2]', PHP_EOL; // store original request uri in env variable
	// spill the rules
	foreach ( $vars['titles'] as $title => $embed ) {
		$safe_title = esc_html( sanitize_title( $title ) );
		echo '# process key ', $safe_title, PHP_EOL;
		$redirect = $embed;
		if ( false === strpos( $redirect, '?' ) && false === strpos( $redirect, '#' ) ) {
			$redirect = "$redirect/"; // avoid prevent the extra 301 redirect from WordPress
		}
		// rewrite the tag to the proper url you want embedded
		// escaping % because they denote backreference in this context in the htaccess
		$safe_url = str_replace( '%', '\%', ruigehond015_get_safe_url( $redirect ) );
		// NE for no escaping (url is already escaped)
		echo 'RewriteRule ^ruigehond_embed/', $safe_title, '$ ', $safe_url, ' [NE,QSD,R=301,L]', PHP_EOL;
		// allow embedding from the following referrers:
		$keyed = ruigehond015_get_key_for_embed( $embed );
		if ( false === isset( $vars['embeds'][ $keyed ] ) || false === is_array( $vars['embeds'][ $keyed ] ) ) {
			continue; // not found
		}
		$highest = count( $vars['embeds'][ $keyed ] ) - 1;
		if ( - 1 === $highest ) {
			continue; // no allowed referrers apparently
		}
		$wwwtoo = true === $vars['wwwtoo'];
		foreach ( $vars['embeds'][ $keyed ] as $index => $referrer ) {
			$safe_url = ruigehond015_get_safe_url( $referrer );
			echo 'RewriteCond %{HTTP_REFERER} ^', $safe_url, '.*';
			if ( $wwwtoo ) { // add [OR] + www.-version if set that it should
				echo ' [OR]', PHP_EOL, 'RewriteCond %{HTTP_REFERER} ^', str_replace( '://', '://www.', $safe_url ), '.*';
			}
			if ( $index < $highest ) {
				echo ' [OR]'; // any of the referrers is ok, separate them by OR
			}
			echo PHP_EOL;
		}
		// allow specific page, for the whole hostname / site, this condition is not necessary
		if ( '' !== $keyed ) {
			$keyed = ruigehond015_get_safe_url( $keyed );
			if ( false !== strpos( $keyed, '?' ) ) {
				// escape question marks in htaccess, or it will not match
				$keyed = str_replace( '?', '\?', $keyed );
			} else {
				// url's end in forward slash normally
				$keyed = "$keyed/";
			}
			echo 'RewriteCond %{ENV:RUIGEHOND015_REQUEST} ', $keyed, PHP_EOL; // default AND will be used
		}
		echo 'RewriteRule (^.*$) - [E=RUIGEHOND015_REFERER:%{HTTP_REFERER}]', PHP_EOL; // store in env variable
	}
	// finish the file with correct headers from the rules when the REFERER env variable is set
	echo '</IfModule>', PHP_EOL;
	echo 'Header unset X-Frame-Options env=RUIGEHOND015_REFERER', PHP_EOL;
	echo 'Header set X-Ruigehond-Embed "%{RUIGEHOND015_REQUEST}e allowed from %{RUIGEHOND015_REFERER}e" env=RUIGEHOND015_REFERER', PHP_EOL;
	if ( true === $set_csp_header ) {
		// edit the csp header, other plugins can potentially break this
		echo 'Header edit Content-Security-Policy "frame-ancestors " "frame-ancestors %{RUIGEHOND015_REFERER}e " env=RUIGEHOND015_REFERER', PHP_EOL;
	}
	echo '</IfModule>', PHP_EOL;
	echo '</IfModule>'; // no PHP_EOL because inserting the lines in htaccess will take care of that
	if ( false === ruigehond015_write_to_htaccess( ob_get_clean(), 'Ruigehond015' ) ) {
		add_settings_error(
			'ruigehond_embed',
			'ruigehond_embed_htaccess',
			esc_html__( '.htaccess could not be updated!', 'ruigehond-embed' )
		);
	}

	return $vars;
}

function ruigehond015_write_to_htaccess( string $content, string $marker ): bool {
	$filename = get_home_path() . '.htaccess';
	if ( false === file_exists( $filename ) || false === is_writable( $filename ) ) {
		add_settings_error(
			'ruigehond_embed',
			'ruigehond_embed_htaccess',
			esc_html__( 'No .htaccess or file not writable.', 'ruigehond-embed' ),
			'warning'
		);

		return false;
	}
	$insertion = explode( PHP_EOL, $content );

	$start_marker = "# BEGIN {$marker}";
	$end_marker   = "# END {$marker}";

	$fp = fopen( $filename, 'r+' );

	if ( ! $fp ) {
		add_settings_error(
			'ruigehond_embed',
			'ruigehond_embed_htaccess',
			esc_html__( 'Could not get pointer to the file.', 'ruigehond-embed' ),
			'warning'
		);

		return false;
	}

	// Attempt to get a lock. If the filesystem supports locking, this will block until the lock is acquired.
	flock( $fp, LOCK_EX );

	$lines = array();

	while ( false !== ( $line = fgets( $fp ) ) ) {
		$lines[] = rtrim( $line, "\r\n" );
	}

	if ( ! feof( $fp ) ) {
		$fp = null;
		add_settings_error(
			'ruigehond_embed',
			'ruigehond_embed_htaccess',
			esc_html__( 'Did not read the whole file.', 'ruigehond-embed' ),
			'warning'
		);

		return false;
	}

	// Insert the insertion at the current marked location, or at the beginning (!) if not found
	$pre_lines        = array();
	$post_lines       = array();
	$existing_lines   = array();
	$found_marker     = false;
	$found_end_marker = false;

	foreach ( $lines as $line ) {
		if ( false === $found_marker && false !== strpos( $line, $start_marker ) ) {
			$found_marker = true;
			continue;
		} elseif ( false === $found_end_marker && false !== strpos( $line, $end_marker ) ) {
			$found_end_marker = true;
			continue;
		}

		if ( false === $found_marker ) {
			$pre_lines[] = $line;
		} elseif ( true === $found_end_marker ) {
			$post_lines[] = $line;
		} else {
			$existing_lines[] = $line;
		}
	}

	if ( $found_marker !== $found_end_marker ) {
		add_settings_error(
			'ruigehond_embed',
			"ruigehond_embed_htaccess",
			esc_html__( 'Start or end marker missing.', 'ruigehond-embed' ),
			'warning'
		);

		return false;
	}

	// Check to see if there was a change.
	if ( $existing_lines === $insertion ) {
		flock( $fp, LOCK_UN );
		fclose( $fp );

		return true;
	}

	// we want to insert at the beginning, for a new entry
	if ( false === $found_end_marker ) {
		$post_lines = $pre_lines;
		$pre_lines  = array();
	}

	// Generate the new file data.
	$new_file_data = implode(
		PHP_EOL,
		array_merge(
			$pre_lines,
			array( $start_marker ),
			$insertion,
			array( $end_marker ),
			$post_lines
		)
	);

	// Write to the start of the file, and truncate it to that length.
	fseek( $fp, 0 );
	$bytes = fwrite( $fp, $new_file_data );

	if ( $bytes ) {
		ftruncate( $fp, ftell( $fp ) );
	}

	fflush( $fp );
	flock( $fp, LOCK_UN );
	fclose( $fp );

	return (bool) $bytes;
}

function ruigehond015_settingslink( $links ): array {
	$url           = get_admin_url();
	$link_text     = esc_html__( 'Settings', 'ruigehond-embed' );
	$settings_link = "<a href=\"{$url}options-general.php?page=ruigehond-embed\">$link_text</a>";
	array_unshift( $links, $settings_link );

	return $links;
}

function ruigehond015_menuitem(): void {
	add_options_page(
		'Ruigehond embed',
		'Ruigehond embed',
		'administrator',
		'ruigehond-embed',
		'ruigehond015_settingspage'
	);
}

function ruigehond015_activate(): void {
	// re-add htaccess lines, if there is info stored in the option
	$vars = get_option( 'ruigehond015' );
	if ( true === is_array( $vars ) ) {
		$vars = ruigehond015_process_htaccess( $vars );
	}
}

function ruigehond015_deactivate(): void {
	// remove htaccess lines
	ruigehond015_write_to_htaccess( '# PLACEHOLDER, plugin Ruigehond-embed is deactivated', 'Ruigehond015' );
}
