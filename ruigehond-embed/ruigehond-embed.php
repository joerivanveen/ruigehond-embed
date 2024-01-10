<?php
declare( strict_types=1 );
/*
Plugin Name: Ruigehond embed
Plugin URI: https://github.com/joerivanveen/ruigehond-embed
Description: Embed selected urls from your website elsewhere
Author: Joeri van Veen
Author URI: https://wp-developer.eu
Version: 0.0.1
*/
defined( 'ABSPATH' ) || die();
// This is plugin nr. 15 by Ruige hond. It identifies as: ruigehond015.
const RUIGEHOND015_VERSION = '0.0.1';
// Startup the plugin
add_action( 'init', 'ruigehond015_run' );
//
function ruigehond015_run() {
	if ( is_admin() ) {
		load_plugin_textdomain( 'ruigehond-embed', null, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
		add_action( 'admin_init', 'ruigehond015_settings' );
		add_action( 'admin_menu', 'ruigehond015_menuitem' );
		add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'ruigehond015_settingslink' ); // settings link on plugins page

		return;
	}
	$vars = get_option( 'ruigehond015' );
//	$vars = array(
//		// redirect to keep changing slugs central
//		'titles' => array(
//			'goochel' => 'wp-admin/options-reading.php',
//			'giechel' => 'prut/'
//		),
//		// x-frame-options...
//		'embeds' => array(
//			'wp-admin/admin-ajax.php' => array(),
//			'prut'                    => array(),
//		),
//	);

	if ( ! isset( $_SERVER['REQUEST_URI'] ) ) {
		return;
	}

	$url = trim( $_SERVER['REQUEST_URI'], '/' );

	if ( 0 === strpos( $url, 'ruigehond_embed/' ) ) {
		$url = str_replace( 'ruigehond_embed/', '', $url );
		if ( true === isset( $vars['titles'][ $url ] ) ) {
			wp_redirect( $vars['titles'][ $url ], 307, 'Ruigehond-embed' );
			die(); // Necessary for otherwise sometimes a 404 is served. Also, wp_die does not work here.
		}
	} elseif ( true === isset( $vars['embeds'][ $url ] ) ) {
		$allow   = $vars['embeds'][ $url ];
		$referer = $_SERVER['HTTP_REFERER'];
		// todo what about Content Security Policy frame ancestors?
		// todo if array, check if request url is in array, if not, allow
		add_action( 'send_headers', function () { // frontend
			//header( 'X-Frame-Options: ALLOW-FROM obsolete' );
			header( 'X-Ruigehond-embed: Yo!' );
			header_remove( 'X-Frame-Options' );
		}, 99 );
		add_action( 'admin_init', function () { // admin
			//header( 'X-Frame-Options: ALLOW-FROM obsolete' );
			header( 'X-Ruigehond-embed: Yo!' );
			header_remove( 'X-Frame-Options' );
		}, 99 );
	}
}

function ruigehond015_settingspage() {
	if ( ! current_user_can( 'administrator' ) ) {
		return;
	}
	echo '<div class="wrap"><h1>';
	echo esc_html( get_admin_page_title() );
	echo '</h1><form action="options.php" method="post">';
	// output security fields for the registered setting
	settings_fields( 'ruigehond015' );
	// output setting sections and their fields
	do_settings_sections( 'ruigehond015' );
	// output save settings button
	submit_button( __( 'Save Settings', 'wp-reading-progress' ) );
	echo '</form></div>';
}

function ruigehond015_settings() {
	/**
	 * register a new setting, call this function for each setting
	 * Arguments: (Array)
	 * - group, the same as in settings_fields, for security / nonce etc.
	 * - the name of the options
	 * - the function that will validate the options
	 */
	register_setting( 'ruigehond015', 'ruigehond015', 'ruigehond015_settings_validate' );
	// register a new section in the page
	add_settings_section(
		'ruigehond_embed_settings', // section id
		__( 'Set your options', 'ruigehond-embed' ), // title
		function () {
			echo '<p>';
			echo __( 'To add an entry, fill in the title at the bottom of the form.', 'ruigehond-embed' );
			echo '<br/>';
			echo __( 'To remove an entry, empty its title field and hit save.', 'ruigehond-embed' );
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
		'title' => sprintf( __( 'Summon by title: %s/ruigehond_embed/%s', 'ruigehond-embed' ), $host, '%s' ),
		'embed' => __( 'Local or fully qualified uri that will be embedded.', 'ruigehond-embed' ),
		'allow' => __( 'Embedding only allowed from these referers. Leave empty to allow from all.', 'ruigehond-embed' ) . ' <strong>NOT WORKING YET</strong>',
	);

	foreach ( $titles as $title => $embed ) {
		ruigehond015_add_settings_field( 'title', $index, (string) $title, $explanations );
		ruigehond015_add_settings_field( 'embed', $index, (string) $embed, $explanations );
		if ( true === isset( $embeds[ $embed ] ) ) {
			ruigehond015_add_settings_field( 'allow', $index, (array) $embeds[ $embed ], $explanations );
		} else {
			ruigehond015_add_settings_field( 'allow', $index, array(), $explanations );
		}
		++ $index;
	}
	ruigehond015_add_settings_field( 'title', 0, '', $explanations );
}

function ruigehond015_add_settings_field( $name, $index, $value, $explanations ) {
	add_settings_field(
		"ruigehond015_{$name}_$index",
		$name,
		function ( $args ) {
			$value = $args['value'];
			if ( 'title' === $args['name'] ) {
				$explanation = sprintf( $args['explanation'], $value ?: '{{title}}' );
				echo '<hr/>';
			} else {
				$explanation = $args['explanation'];
			}
			if ( is_array( $value ) ) {
				echo '<textarea name="ruigehond015[', $args['name'], '][', $args['index'], ']">';
				echo implode( PHP_EOL, array_map( static function ( $value ) {
					return htmlentities( $value );
				}, $value ) );
				echo '</textarea>';
			} else {
				echo '<input type="text" name="ruigehond015[', $args['name'], '][', $args['index'], ']" value="';
				echo htmlentities( $value );
				echo '" class="regular-text"/>';
			}
			if ( isset( $args['explanation'] ) ) {
				echo '<div class="ruigehond015 explanation"><em>';
				echo $explanation;
				echo '</em></div>';
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

function ruigehond015_settings_validate( $input ) {
	$vars           = $old_vars = (array) get_option( 'ruigehond015' );
	$vars['titles'] = array();
	$vars['embeds'] = array();

	$titles = $input['title'] ?? array();
	$embeds = $input['embed'] ?? array();
	$allows = $input['allow'] ?? array();

	foreach ( $titles as $index => $title ) {
		$embed = $embeds[ $index ] ?? null;
		$allow = $allows[ $index ] ?? null;

		if ( isset( $vars['titles'][ $title ] ) ) {
			add_settings_error(
				'ruigehond_embed',
				"ruigehond_embed_$index",
				sprintf( __( 'Duplicate titles not allowed: %s', 'ruigehond-embed' ), $title )
			);

			return $old_vars;
		}

		if ( '' !== $title ) {
			$embed = $findr = trim( $embed, '/' );
			if ( 0 === strpos( $embed, 'https://' )
			     || 0 === strpos( $embed, 'http://' )
			     || 0 === strpos( $embed, '//' )
			) {
				$parts = explode( '/', $embed );
				$findr = implode( '/', array_slice( $parts, 3 ) );
			} else {
				$embed = "/$embed";
			}
			$vars['titles'][ $title ] = $embed;
			$vars['embeds'][ $findr ] = $allow;
		}
	}

	return $vars;
}

function ruigehond015_settingslink( $links ) {
	$url           = get_admin_url();
	$link_text     = __( 'Settings', 'ruigehond-embed' );
	$settings_link = "<a href=\"{$url}options-general.php?page=ruigehond-embed\">$link_text</a>";
	array_unshift( $links, $settings_link );

	return $links;
}

function ruigehond015_menuitem() {
	add_options_page(
		'Ruigehond embed',
		'Ruigehond embed',
		'administrator',
		'ruigehond-embed',
		'ruigehond015_settingspage'
	);
}
