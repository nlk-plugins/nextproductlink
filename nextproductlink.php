<?php
/*
Plugin Name: Next Product Link
Description: Helper Plugin to provide Next/Previous Product links to navigate between WP E-Commerce Products via something like next_product_link( '%link', 'Next Product "%title" &gt;' )
Version: 1.0
Author: Alex Chousmith
Author URI: http://www.ninthlink.com/author/alex/
*/

if ( !function_exists( 'next_product_link' ) ):
/*
 * Display Next Product link, adjacent to current Product.
 *
 * use something like:
 * <?php if(function_exists('next_product_link')) next_product_link( '%link', 'Next Product "%title" &gt;' ); ?>
 *
 * @since 1.5.0
 *
 * @param string $format Optional. Link anchor format.
 * @param string $link Optional. Link permalink format.
 * @param bool $in_same_cat Optional. Whether link should be in same category.
 * @param string $excluded_categories Optional. Excluded categories IDs.
 */
function next_product_link($format='%link &raquo;', $link='%title', $in_same_cat = true, $excluded_categories = '') {
	nextproductlink_adjacent($format, $link, $in_same_cat, $excluded_categories, true);
}
endif;

if ( !function_exists( 'previous_product_link' ) ):
/*
 * Display previous post link, adjacent to current Product.
 *
 * use something like:
 * <?php if(function_exists('previous_product_link')) previous_product_link( '%link', 'Previous Product "%title" &gt;' ); ?>
 *
 * @since 1.0.0
 *
 * @param string $format Optional. Link anchor format.
 * @param string $link Optional. Link permalink format.
 * @param bool $in_same_cat Optional. Whether link should be in same category.
 * @param string $excluded_categories Optional. Excluded categories IDs.
 */
function previous_product_link($format='&laquo; %link', $link='%title', $in_same_cat = true, $excluded_categories = '') {
	nextproductlink_adjacent($format, $link, $in_same_cat, $excluded_categories, false);
}
endif;

if ( !function_exists( 'nextproductlink_adjacent' ) ):
/*
 * Display adjacent Product (wpsc-product) link.
 *
 * Can be either next or previous product link
 *
 * @since 1.0
 *
 * @param string $format Link anchor format.
 * @param string $link Link permalink format.
 * @param bool $in_same_cat Optional. Whether link should be in same category.
 * @param string $excluded_categories Optional. Excluded categories IDs.
 * @param bool $next Optional, default is true. Whether to link to Next (true) or Previous (false) Product.
 */
function nextproductlink_adjacent($format, $link, $in_same_cat = false, $excluded_categories = '', $next = true) {
	if ( $previous && is_attachment() )
		$post = & get_post($GLOBALS['post']->post_parent);
	else
		$post = nextproductlink_get($in_same_cat, $excluded_categories, $next);

	if ( !$post )
		return;

	$title = $post->post_title;

	if ( empty($post->post_title) )
		$title = $previous ? __('Previous Post') : __('Next Post');

	$title = apply_filters('the_title', $title, $post->ID);
	$date = mysql2date(get_option('date_format'), $post->post_date);
	$rel = $previous ? 'prev' : 'next';

	$string = '<a href="'.get_permalink($post).'" rel="'.$rel.'" class="adjproductlink'. ($next ? ' next' : ' prev' ) .'">';
	$link = str_replace('%title', $title, $link);
	$link = str_replace('%date', $date, $link);
	$link = $string . $link . '</a>';

	$format = str_replace('%link', $link, $format);

	$adjacent = $previous ? 'previous' : 'next';
	echo apply_filters( "{$adjacent}_wpsc-product_link", $format, $link );
}
endif;

if ( !function_exists( 'nextproductlink_get' ) ):
/**
 * Retrieve adjacent Product.
 *
 * Can either be next or previous Product.
 *
 * @since 1.0.0
 *
 * @param bool $in_same_cat Optional. Whether post should be in same category.
 * @param string $excluded_categories Optional. Excluded categories IDs.
 * @param bool $next Optional. Whether to retrieve next (true) or previous (false) product.
 * @return mixed Post object if successful. Null if global $post is not set. Empty string if no corresponding post exists.
 */
function nextproductlink_get($in_same_cat = true, $excluded_categories = '', $next = true) {
	global $post;

	if ( empty( $post ) )
		return null;
	
	$args = array(
		'post_type' => 'wpsc-product',
		'posts_per_page' => -1
	);
	if ( $in_same_cat == true ) {
		$cats = wp_get_object_terms( $post->ID, 'wpsc_product_category' );
		$slugs = array();
		foreach($cats as $cat) {
			$slugs[] = $cat->slug;
		}
		$cats = implode(',', $slugs);
		$args['wpsc_product_category'] = $cats;
	}
	/*
	
	if ( $excluded_categories != '' ) {
		$badcats = '-'. str_replace(',', ',-', $excluded_categories);
		$args['wpsc_product_category'] = $badcats;
	}
	*/
	$prod_query = new WP_Query($args);
	//wp_die('<pre>'.print_r($prod_query,true) .'</pre>');
	$currentindex = 0;
	foreach ( $prod_query->posts as $k => $p ) {
		if ( $p->ID == $post->ID ) {
			$currentindex = $k;
		}
	}
	
	if ( $currentindex == 0 )
		return null;
		
	$adjindex = $next ? $currentindex - 1 : $currentindex + 1;
	
	if ( ( $next && ($adjindex < 0) ) || ( $next==false && ($adjindex >= $prod_query->post_count) ) )
		return null;
	
	$nextpost = $prod_query->posts[$adjindex];
	return $nextpost;
}
endif;