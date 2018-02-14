<?php
/*
Plugin Name: Artiss.blog Configuration
Plugin URI: https://artiss.blog
Description: Configuration Settings for Artiss.blog
Version: 4.1
Author: David Artiss
Author URI: https://artiss.blog
*/

/*

TWEAK WORDPRESS

*/

// Remove WordPress version from meta

function ac_version_removal() { return ''; }

add_filter( 'the_generator', 'ac_version_removal' );

// Add shortcodes to RSS

add_filter( 'the_content_rss', 'do_shortcode' );

// Add new default Gravatar

function newgravatar( $avatar_defaults ) {

    $new_avatar = content_url() . '/uploads/2016/04/Default-Avatar.png';
    $avatar_defaults[ $new_avatar ] = 'No Avatar';
    return $avatar_defaults;
}

add_filter( 'avatar_defaults', 'newgravatar' );

// Add a default image to Jetpack related posts

function jeherve_custom_image( $media, $post_id, $args ) {
    if ( $media ) {
        return $media;
    } else {
        $permalink = get_permalink( $post_id );
        $url = apply_filters( 'jetpack_photon_url', 'https://artiss.blog/wp-content/uploads/2017/02/Image-not-available.png' );

        return array( array(
            'type'  => 'image',
            'from'  => 'custom_fallback',
            'src'   => esc_url( $url ),
            'href'  => $permalink,
        ) );
    }
}

add_filter( 'jetpack_images_get_images', 'jeherve_custom_image', 10, 3 );

// If just one post in result just show it
// https://trepmal.com/2011/04/22/redirect-when-search-query-only-returns-one-match/

function ac_single_result() {

	if ( is_search() ) {
		global $wp_query;
		if ( $wp_query -> post_count == 1 && $wp_query -> max_num_pages == 1 ) {
			wp_redirect( get_permalink( $wp_query -> posts[ 0 ] -> ID ) );
			exit;
		}
	}
}

add_action( 'template_redirect', 'ac_single_result' );

/*

NEW POST CONTENTS

*/

// Add disclosure message

function add_disclosure( $content ) {

	global $post;

    if ( is_single() ) {
        $custom_field = get_post_meta( $post -> ID, 'disclosure', false );
        if ( isset( $custom_field[ 0 ] ) ) { $disclosure = $custom_field[ 0 ]; } else { $disclosure = false; }

        if ( $disclosure !== false ) {
            $content .= '<p><strong>Disclosure of gift</strong> - I received this product at a discounted price in exchange for an honest and unbiased review.</p>';
        }
    }

    return $content;

}

add_filter( 'the_content', 'add_disclosure' );

// Add message at top of posts if beyond a certain age

function ac_post_message( $content ) {

    if ( is_single() && !is_sticky() && get_the_time( 'U' ) < strtotime( '1st August 2012' ) ) {

		$content = '<div class="alert alert-danger alert-dismissible"><button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>Due to updates, over time, that have been made to the site and the age of this article, this post may not display correctly. In particular images may be missing or product reviews display incorrectly.</br></br>If this is the case and you\'d particularly like me to fix it, then please reach out to me on <a href="https://twitter.com/DavidArtiss">Twitter</a>.</div>' . $content;
	}

	return $content;
}

add_filter( 'the_content', 'ac_post_message' );

// Add BTQ messages to the bottom of posts

function ac_btq_message( $content ) {

    if ( is_single() && !is_sticky() && has_category( array( 'Hacks', 'Customer Service', 'Gaming', 'Product Review' ) ) ) {

        $content .= '<div class="alert alert-info alert-dismissible"><button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button><a href="https://bigtechquestion.com/"><img src="https://artiss.blog/wp-content/uploads/2017/11/The-Big-Tech-Question-Logo.png" style="float: left;"></a>If you liked this, you should try <a href="https://bigtechquestion.com/">The Big Tech Question</a>, which includes articles written by myself.</br></br>The Big Tech Question delivers straight answers to the biggest questions in tech. And some questions nobody really wanted the answers to…</div>';
    }

    return $content;
}

add_filter( 'the_content', 'ac_btq_message' );

// Shortcode for bookmarks

function add_bookmark( $paras = '', $content = '' ) {

    extract( shortcode_atts( array( 'id' => '' ), $paras ) );

    return '<a name="' . $id . '" id="' . $id . '"></a>';
}

add_shortcode( 'link', 'add_bookmark' );

// Add Time to Read to top of posts

function time_to_read( $content = false ) {

    $format = get_post_format() ? : 'standard';

    if ( is_single () && ( $format == 'standard' or $format == 'aside' ) ) {

        // If content has not been passed in (via the filter), fetch it

        if ( !$content ) { $content = get_the_content(); $add = false; } else { $add = true; }

        // Get the cache, if the content was not provided and this is not a preview

        $cache = false;
        if ( !$add && !is_preview() ) { $cache = get_transient( 'time_to_read_' . get_the_id() ); }

        // If cache was found, see if the post has updated. If so, trash it

        if ( $cache ) { if ( isset( $cache[ 'updated' ] ) && $cache[ 'updated'] != get_the_modified_date() ) { $cache = false; } }

        // If there is a cache, use it!

        if ( $cache ) {

            $content = $cache[ 'content'];

        } else {

            // Generate output

        	$time = str_word_count( strip_tags( $content ) ) / 300;
			if ( $time == 0 ) { $time = 0.1; } // If there is no content, report < 1 minute
        	$rounded = ceil( $time );
            $output = 'Time to read: ' . ( $time<1?'<':'' ) . $rounded . ' minute' . ( $rounded>1?'s':'' );
            $generated = 'Code generated';

            if ( $add ) { $content = $output . $content; } else { $content = $output; }

            // Save cache, if content was not provided and this is not a preview

            if ( !$add && !is_preview() ) {
                $cache[ 'content' ] = $content;
                $cache[ 'updated '] = get_the_modified_date();
                set_transient( 'time_to_read_' . get_the_id(), $cache );;
            }

        }

    }

    return $content;
}

/*

ADVERTISING

*/

// Add Skimlinks to Footer

function add_skimlinks_script() {

    if ( ac_add_ads() ) { echo "<script type=\"text/javascript\" src=\"//s.skimresources.com/js/15437X1558574.skimlinks.js\"></script>\n"; }

}

add_action( 'wp_footer', 'add_skimlinks_script' );

// Add advert at top and bottom of post and 'after the fold'

function ac_add_post_ad( $content ) {

	global $post;

	if ( ac_add_ads() ) {

		$content = ac_add_ad() . '</br>' . $content . '</br>' . ac_add_ad();

		$spanpos = strpos( $content, '<span id="more-' );

		if ( $spanpos !== false ) {
			$divpos = strpos( $content, '</p>', $spanpos );
			$content = substr( $content, 0, $divpos + 4 ) . '<p style="text-align: center; color: grey; font-size: 8pt;">ADVERTISEMENT</br><script async src="//pagead2.googlesyndication.com/pagead/js/adsbygoogle.js"></script><ins class="adsbygoogle" style="display:block; text-align:center;" data-ad-format="fluid" data-ad-layout="in-article" data-ad-client="ca-pub-0743991857092062" data-ad-slot="1797643890"></ins><script>(adsbygoogle = window.adsbygoogle || []).push({});</script></p>' . substr( $content, $divpos + 5 );
		}
	}

	return $content;
}

add_filter( 'the_content', 'ac_add_post_ad', 15 );

// Validate if post is valid for adverts
// Needs to be in a specific category, be a single page and not have a non-standard post format

function ac_add_ads() {

    if ( has_category( array( 'Hacks', 'Customer Service', 'Gaming', 'Product Review' ) ) && is_single() && !get_post_format() ) {
        return true;
    } else {
        return false;
    }
}

// Return AdSense advert

function ac_add_ad() {

    return '<script async src="//pagead2.googlesyndication.com/pagead/js/adsbygoogle.js"></script><ins class="adsbygoogle" style="display:block" data-ad-client="ca-pub-0743991857092062" data-ad-slot="9500874098" data-ad-format="auto"></ins><script>(adsbygoogle = window.adsbygoogle || []).push({});</script>';

}

// Filter my feed so I don't get a full output for those carrying adverts

function filter_my_feed( $content ){

    global $post;

    if ( has_category( array( 'Hacks', 'Customer Service', 'Gaming', 'Product Review' ), $post->ID ) ) {

        return $content;

    } else {

        return get_the_excerpt( $post->ID );
    }
}

add_filter( "the_content_feed", "filter_my_feed" );
?>