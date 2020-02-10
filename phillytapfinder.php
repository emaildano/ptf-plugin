<?php
/*
Plugin Name: PhillyTapFinder
Plugin URI: http://phillytapfinder.com
Description: A plugin that adds unique functionality for PhillyTapFinder.com.
Version: 1.0
Author: New Philosophy
Author URI: http://newphilosophy.com
License: All rights reserved. Not for resale, reuse, or distribution.
*/

spl_autoload_register('class_autoload');
function class_autoload($classname) {
	$filename = $_SERVER['DOCUMENT_ROOT'] . "/wp-content/plugins/phillytapfinder/classes/$classname.class.php";

	if (file_exists($filename)) {
		require_once "$filename";
	}
}

function createTaxonomies() {
	register_taxonomy('ptf_hoods', 'ptf_bars', array('hierarchical' => true, 'label' => 'Neighborhoods', 'rewrite' => array('slug' => 'hood')));
	register_taxonomy('ptf_beer_style', 'ptf_beer', array('hierarchical' => true, 'label' => 'Styles', 'rewrite' => array('slug' => 'style')));
	register_taxonomy('ptf_breweries', 'ptf_beer', array('hierarchical' => true, 'label' => 'Breweries',  'rewrite' => array('slug' => 'brewery')));

	register_taxonomy('ptf_beer_featured', 'ptf_beer', array('hierarchical' => true, 'label' => 'Featured Beer'));
	register_taxonomy('ptf_bar_featured', 'ptf_bar', array('hierarchical' => true, 'label' => 'Featured Bar'));
	register_taxonomy('ptf_brewery_featured', 'ptf_breweries', array('hierarchical' => true, 'label' => 'Featured Brewery'));
	register_taxonomy('ptf_ad_overrides', 'ptf_ads', array('hierarchical' => true, 'label' => 'Advertisement Placement Overrides'));
}

function createContentTypes() {
	register_post_type('ptf_bars', array(
		'labels' => array(
			'name' => __('Bars'),
			'singular_name' => __('Bar'),
			'add_new' => __('Add Bar'),
			'add_new_item' => __('Add New Bar'),
			'edit_item' => __('Edit Bar'),
			'update_item' => __('Update Bar'),
		),
		'rewrite' => array('slug'=>'bar'),
		'taxonomies' => array('ptf_hoods', 'ptf_beer_tax', 'ptf_bar_featured'),
		'supports' => array('title', 'editor', 'author', 'thumbnail', 'excerpt', 'trackbacks', 'custom-fields', 'comments', 'page-attributes' ),
		'public' => true,
		'menu_position' => 40,
		'show_in_rest' => true
		)
	);

	register_post_type('ptf_events', array(
		'labels' => array(
			'name' => __('Events'),
			'singular_name' => __('Event'),
			'add_new' => __('Add Event'),
			'add_new_item' => __('Add New Event'),
			'edit_item' => __('Edit Event'),
			'update_item' => __('Update Event'),
		),
		'rewrite' => array('slug'=>'event'),
		'supports' => array('title', 'editor', 'author', 'thumbnail', 'excerpt', 'trackbacks', 'custom-fields', 'comments', 'page-attributes' ),
		'public' => true,
		'menu_position' => 40,
		'show_in_rest' => true
		)
	);


	register_post_type('ptf_beers', array(
		'labels' => array(
			'name' => __('Beers'),
			'singular_name' => __('Beer'),
			'add_new' => __('Add Beer'),
			'add_new_item' => __('Add New Beer'),
			'edit_item' => __('Edit Beer'),
			'update_item' => __('Update Beer'),
		),
		'rewrite' => array('slug'=>'beer'),
		'taxonomies' => array('ptf_beer_style', 'ptf_breweries', 'ptf_beer_featured'),
		'supports' => array('title', 'editor', 'author', 'thumbnail', 'excerpt', 'trackbacks', 'custom-fields', 'comments', 'page-attributes', 'revisions' ),
		'public' => true,
		'menu_position' => 40,
		'show_in_rest' => true
		)
	);

	register_post_type('ptf_breweries_meta', array(
		'labels' => array(
			'name' => __('Brewery Info'),
			'singular_name' => __('Brewery Info'),
			'add_new' => __('Add Brewery Info'),
			'add_new_item' => __('Add New Brewery Info'),
			'edit_item' => __('Edit Brewery Info'),
			'update_item' => __('Update Brewery Info'),
		),
		'rewrite' => array('slug'=>'ptf-breweries-meta'),
		'taxonomies' => array('ptf_breweries', 'ptf_brewery_featured'),
		'supports' => array('title', 'editor', 'author', 'thumbnail', 'excerpt', 'trackbacks', 'custom-fields', 'comments', 'page-attributes' ),
		'public' => true,
		'show_ui' => true,
		'rewrite' => array('slug' => 'brewery'),
		'publicly_queryable' => 'false',
		'menu_position' => 40,
		'show_in_rest' => true
		)
	);



	register_post_type('ptf_ads', array(
		'labels' => array(
			'name' => __('Advertisements'),
			'singular_name' => __('Advertisement'),
			'add_new' => __('Add an Advertisement'),
			'add_new_item' => __('Add New Advertisement'),
			'edit_item' => __('Edit Advertisement'),
			'update_item' => __('Update Advertisement'),
		),
		'rewrite' => array('slug'=>'ads'),
		'supports' => array('title', 'editor', 'author', 'thumbnail', 'excerpt', 'trackbacks', 'custom-fields', 'comments', 'page-attributes' ),
		'public' => true,
		'menu_position' => 40,
		'show_in_rest' => true
		)
	);
}

function addScripts() {
	wp_enqueue_script("ss-plugins", plugins_url() ."/phillytapfinder/js/plugins.js", array(), "3", true);
	wp_enqueue_script("ss-ajax", plugins_url() ."/phillytapfinder/js/phillytapfinder.js", array(), "3", true);
}

add_action('init', 'createTaxonomies', 0);
add_action('init', 'createContentTypes', 0);
add_action('wp_enqueue_scripts', 'addScripts', 0);

function np_pageTitle() {
	$titleOutput = '';
	$siteName = get_bloginfo('name');
	$siteDescription = get_bloginfo('description');

	if ( is_tag() ) {
		$titleOutput = 'Content tagged with the word &quot;'.single_tag_title( '', false ).'&quot; at '.$siteName;
	} elseif ( is_category() ) {
		$titleOutput = 'Content categorized as &quot;'.single_cat_title( '', false ).'&quot; at '.$siteName;
	} elseif ( is_author() ) {
		$author = get_userdata( get_query_var('author') );
		$titleOutput = 'Posts written by '.$author->display_name.' at '.$siteName;
	} elseif ( is_month() ) {
		$titleOutput = 'Posts from'.single_month_title(' ', false).' at '.$siteName;
	} elseif ( is_date() ) {
		$titleOutput = 'Posts from '.wp_title('', false).' at '.$siteName;
	} elseif ( is_search() ) {
		$titleOutput = 'Search results for &quot;'.get_search_query().'&quot; at '.$siteName;
	} elseif ( is_404() ) {
		$titleOutput = 'Sorry, we couldn\'t find what you were looking for at '.$siteName;
	} elseif ( is_home() || is_front_page() ) {
		$titleOutput = $siteName.' - '.$siteDescription;
	} elseif ( is_single() || is_page() ) {
		$titleOutput = wp_title('',false).' - '.$siteName;
	} else {
		$titleOutput = $siteName.' - '.$siteDescription;
	}

	echo $titleOutput;
}

function theAlphabet() {
	$alphabetR = range('a', 'z');
	return $alphabetR;
}

function np_backendStyles() {

	wp_register_style(
		$handle = 'np_alt_style',
		$src = plugins_url('css/np_alt_style.css', __FILE__),
		$deps = array(),
		$ver = '2.0.0',
		$media = 'all'
	);

	wp_enqueue_style('np_alt_style');
}

add_action('admin_print_styles', 'np_backendStyles');

function curPageURL() {
	$pageURL = 'http';

	if ($_SERVER["HTTPS"] == "on") {$pageURL .= "s";}
		$pageURL .= "://";
	if ($_SERVER["SERVER_PORT"] != "80") {
		$pageURL .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];
	} else {
		$pageURL .= $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
	}

	return $pageURL;
}

function split_url( $url, $decode=TRUE )
{
    $xunressub     = 'a-zA-Z\d\-._~\!$&\'()*+,;=';
    $xpchar        = $xunressub . ':@%';

    $xscheme       = '([a-zA-Z][a-zA-Z\d+-.]*)';

    $xuserinfo     = '((['  . $xunressub . '%]*)' .
                     '(:([' . $xunressub . ':%]*))?)';

    $xipv4         = '(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})';

    $xipv6         = '(\[([a-fA-F\d.:]+)\])';

    $xhost_name    = '([a-zA-Z\d-.%]+)';

    $xhost         = '(' . $xhost_name . '|' . $xipv4 . '|' . $xipv6 . ')';
    $xport         = '(\d*)';
    $xauthority    = '((' . $xuserinfo . '@)?' . $xhost .
                     '?(:' . $xport . ')?)';

    $xslash_seg    = '(/[' . $xpchar . ']*)';
    $xpath_authabs = '((//' . $xauthority . ')((/[' . $xpchar . ']*)*))';
    $xpath_rel     = '([' . $xpchar . ']+' . $xslash_seg . '*)';
    $xpath_abs     = '(/(' . $xpath_rel . ')?)';
    $xapath        = '(' . $xpath_authabs . '|' . $xpath_abs .
                     '|' . $xpath_rel . ')';

    $xqueryfrag    = '([' . $xpchar . '/?' . ']*)';

    $xurl          = '^(' . $xscheme . ':)?' .  $xapath . '?' .
                     '(\?' . $xqueryfrag . ')?(#' . $xqueryfrag . ')?$';


    // Split the URL into components.
    if ( !preg_match( '!' . $xurl . '!', $url, $m ) )
        return FALSE;

    if ( !empty($m[2]) )        $parts['scheme']  = strtolower($m[2]);

    if ( !empty($m[7]) ) {
        if ( isset( $m[9] ) )   $parts['user']    = $m[9];
        else            $parts['user']    = '';
    }
    if ( !empty($m[10]) )       $parts['pass']    = $m[11];

    if ( !empty($m[13]) )       $h=$parts['host'] = $m[13];
    else if ( !empty($m[14]) )  $parts['host']    = $m[14];
    else if ( !empty($m[16]) )  $parts['host']    = $m[16];
    else if ( !empty( $m[5] ) ) $parts['host']    = '';
    if ( !empty($m[17]) )       $parts['port']    = $m[18];

    if ( !empty($m[19]) )       $parts['path']    = $m[19];
    else if ( !empty($m[21]) )  $parts['path']    = $m[21];
    else if ( !empty($m[25]) )  $parts['path']    = $m[25];

    if ( !empty($m[27]) )       $parts['query']   = $m[28];
    if ( !empty($m[29]) )       $parts['fragment']= $m[30];

    if ( !$decode )
        return $parts;
    if ( !empty($parts['user']) )
        $parts['user']     = rawurldecode( $parts['user'] );
    if ( !empty($parts['pass']) )
        $parts['pass']     = rawurldecode( $parts['pass'] );
    if ( !empty($parts['path']) )
        $parts['path']     = rawurldecode( $parts['path'] );
    if ( isset($h) )
        $parts['host']     = rawurldecode( $parts['host'] );
    if ( !empty($parts['query']) )
        $parts['query']    = rawurldecode( $parts['query'] );
    if ( !empty($parts['fragment']) )
        $parts['fragment'] = rawurldecode( $parts['fragment'] );
    return $parts;
}

function join_url( $parts, $encode=TRUE )
{
    if ( $encode )
    {
        if ( isset( $parts['user'] ) )
            $parts['user']     = rawurlencode( $parts['user'] );
        if ( isset( $parts['pass'] ) )
            $parts['pass']     = rawurlencode( $parts['pass'] );
        if ( isset( $parts['host'] ) &&
            !preg_match( '!^(\[[\da-f.:]+\]])|([\da-f.:]+)$!ui', $parts['host'] ) )
            $parts['host']     = rawurlencode( $parts['host'] );
        if ( !empty( $parts['path'] ) )
            $parts['path']     = preg_replace( '!%2F!ui', '/',
                rawurlencode( $parts['path'] ) );
        if ( isset( $parts['query'] ) )
            $parts['query']    = rawurlencode( $parts['query'] );
        if ( isset( $parts['fragment'] ) )
            $parts['fragment'] = rawurlencode( $parts['fragment'] );
    }

    $url = '';
    if ( !empty( $parts['scheme'] ) )
        $url .= $parts['scheme'] . ':';
    if ( isset( $parts['host'] ) )
    {
        $url .= '//';
        if ( isset( $parts['user'] ) )
        {
            $url .= $parts['user'];
            if ( isset( $parts['pass'] ) )
                $url .= ':' . $parts['pass'];
            $url .= '@';
        }
        if ( preg_match( '!^[\da-f]*:[\da-f.:]+$!ui', $parts['host'] ) )
            $url .= '[' . $parts['host'] . ']'; // IPv6
        else
            $url .= $parts['host'];             // IPv4 or name
        if ( isset( $parts['port'] ) )
            $url .= ':' . $parts['port'];
        if ( !empty( $parts['path'] ) && $parts['path'][0] != '/' )
            $url .= '/';
    }
    if ( !empty( $parts['path'] ) )
        $url .= $parts['path'];
    if ( isset( $parts['query'] ) )
        $url .= '?' . $parts['query'];
    if ( isset( $parts['fragment'] ) )
        $url .= '#' . $parts['fragment'];
    return $url;
}

?>
