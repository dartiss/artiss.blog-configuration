<?php
/**
 * Artiss.blog Configuration
 *
 * @package           artiss-blog-configuration
 * @author            David Artiss
 * @license           GPL-2.0-or-later
 *
 * Plugin Name:       Artiss.blog Configuration
 * Plugin URI:        https://github.com/dartiss/artiss.blog-configuration
 * Description:       Configuration Settings for Artiss.blog.
 * Version:           4.8
 * Requires at least: 4.6
 * Requires PHP:      8.2
 * Author:            David Artiss
 * Author URI:        https://artiss.blog
 * License:           GPL v2 or later
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 *
 * This program is free software; you can redistribute it and/or modify it under the terms of the GNU
 * General Public License version 2, as published by the Free Software Foundation. You may NOT assume
 * that you can use any other version of the GPL.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without
 * even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 */

/**
 * Add shortcodes to RSS.
 */

add_filter( 'the_content_rss', 'do_shortcode' );

/**
 * Add new default Gravatar
 *
 * @param    array $avatar_defaults  Default avatars.
 * @return   array                   New array.
 */
function artiss_new_gravatar( $avatar_defaults ) {

	$new_avatar                     = content_url() . '/uploads/2016/04/Default-Avatar.png';
	$avatar_defaults[ $new_avatar ] = 'No Avatar';
	return $avatar_defaults;
}

add_filter( 'avatar_defaults', 'artiss_new_gravatar' );

/**
 * Add disclosure messages.
 *
 * @param    string $content  Post/page content.
 * @return   string           Updated content.
 */
function artiss_add_disclosure( $content ) {

	global $post;

	if ( is_single() ) {

		// Add a disclosure for gifted items.

		$custom_field = get_post_meta( $post->ID, 'disclosure', false );
		if ( isset( $custom_field[0] ) ) {
			$disclosure = $custom_field[0];
		} else {
			$disclosure = false; }

		if ( false !== $disclosure ) {
			$content .= '<p><strong>Disclosure of gift</strong> - I received this product at a discounted price in exchange for an honest and unbiased review.</p>';
		}

		// Add an automatic disclosure for Amazon links.

		if ( false !== strpos( $content, 'https://amzn.to/' ) || ( false !== strpos( $content, 'https://www.amazon.co.uk/' ) && false !== strpos( $content, '&tag=' ) ) ) {
			$content .= '<p class="has-luminous-vivid-amber-background-color has-background">ℹ️ <strong>As an Amazon Associate, links on this post may mean that I may from qualifying purchases that you make.</strong></p>';
		}
	}

	return $content;
}

add_filter( 'the_content', 'artiss_add_disclosure' );

/**
 * Shortcode for linking to Wikipedia
 *
 * @param    array  $paras    Shortcode paramaters.
 * @param    string $content  Any content between shortcodes.
 * @return   array            Output.
 */
function wikilinker_shortcode( $paras = '', $content = '' ) {

	// Extract the shortcode parameters.

	if ( isset( $paras['alt'] ) ) {
		$alt = $paras['alt'];
	}
	if ( isset( $paras['target'] ) ) {
		$target = $paras['target'];
	}

	// If an alternative link is specified use that rather than the linked text.

	if ( '' !== $alt ) {
		$lookup = $alt;
	} else {
		$lookup = $content;
	}

	// Now ensure that all spaces are replaced with underscores. It's what Wikipedia would want.

	$lookup = str_replace( ' ', '_', $lookup );

	// Build the title plus any additional, optional parameters.

	$title  = sprintf( '%s on Wikipedia', $content );
	$extras = '';
	if ( '' !== $target ) {
		$extras .= ' target="' . $target . '"';
	}

	// Generate the HTML code.

	$output = '<a href="https://en.wikipedia.org/wiki/' . $lookup . '" title="' . $title . '"' . $extras . '>' . $content . '</a>';
	return $output;
}

add_shortcode( 'wikilink', 'wikilinker_shortcode' );

/**
 * Add new headers
 *
 * @param    array $headers      Existing headers.
 * @return   array               Updated headers.
 */
function adb_add_headers( $headers ) {

	$headers['Permissions-Policy'] = 'interest-cohort=()';   // Disable Floc.
	$headers['Host-Header']        = 'Pressable';            // Add a host header.

	return $headers;
}

add_filter( 'wp_headers', 'adb_add_headers' );

/**
 * Add a menu for the block editor
 */
function add_menus() {

	if ( class_exists( 'Jetpack' ) ) {
		add_action( 'jetpack_admin_menu', 'add_jetpack_menu' );
	}

	add_menu_page(
		'Reusable Blocks',
		'Reusable Blocks',
		'manage_options',
		'edit.php?post_type=wp_block',
		'',
		'dashicons-editor-table',
		22
	);
}

add_action( 'admin_menu', 'add_menus', 5 );

/**
 * Add a sub-menu to Jetpack for the modules
 */
function add_jetpack_menu() {

	add_submenu_page(
		'jetpack',
		'Jetpack Modules',
		'Modules',
		'manage_options',
		'jetpack_modules',
		'jetpack'
	);
}

/**
 * Add support for a theme colour
 */
function add_theme_colour() {
	?>
	<meta name="theme-color" content="#BE702B" media="(prefers-color-scheme: light)">
	<meta name="theme-color" content="#265BA6" media="(prefers-color-scheme: dark)">
	<link rel="me" href="https://mastodon.social/@dartiss">
	<?php
}
add_action( 'wp_head', 'add_theme_colour' );

remove_filter( 'authenticate', 'wp_authenticate_email_password', 20 );

/**
 * Disable logging in via email
 *
 * Check for and serve an appropriate response to users attempting to sign in with email.
 *
 * @param  string $user     If the user is authenticated.
 * @param  string $username Username or email address.
 * @return string           Authentication details
 */
function artiss_blog_disable_email_login( $user, $username ) {

	if ( ! empty( $username ) && filter_var( $username, FILTER_VALIDATE_EMAIL ) ) {
		return new WP_Error( 'email_login_disabled', 'Logging in with email is disabled.' );
	}

	return $user;
}

add_filter( 'authenticate', 'artiss_blog_disable_email_login', 20, 3 );
