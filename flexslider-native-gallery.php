<?php
/*
Plugin Name: FlexSlider Native Gallery
Plugin URI:
Description: Provides a new shortcode to use to alter the WordPress gallery core feature. Just insert a gallery into the text editor and replace [gallery (parameters)] with [flexslider (parameters)].
Author: Caspar Hübinger
Version: 0.2
Author URI: http://glueckpress.com/

 This program is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with this program; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307, USA
*/

/**
 * Avoid direct calls to this file where wp core files not present
 */
if ( ! function_exists ( 'add_action' ) || ! defined( 'ABSPATH' ) ) {
		header( 'Status: 403 Forbidden' );
		header( 'HTTP/1.1 403 Forbidden' );
		exit();
}

/**
 * Print jQuery at the bottom of the page.
 */
add_action( 'wp_default_scripts', apply_filters( 'flexslider_print_jquery_in_footer', 'gpfs_print_jquery_in_footer' ) );
function gpfs_print_jquery_in_footer( &$scripts) {
	if ( ! is_admin() )
		$scripts->add_data( 'jquery', 'group', 1 );
}

/**
 * Plugin init function
 *
 * Pass your own javascript via add_filter( 'flexslider_init', 'your_custom_register_script_function' ).
 * Pass your own stylesheet via add_filter( 'flexslider_style', 'your_custom_register_style_function' ).
 *
 */
add_action( 'init', 'my_scritps' );
function my_scritps(){
	wp_register_script(	'flexslider-core', plugins_url( '/js/jquery.flexslider.min.js', __FILE__ ), array( 'jquery' ), '2.1', true );
	wp_register_script(	'flexslider-init', apply_filters( 'flexslider_init', plugins_url( '/js/flexslider.init.min.js', __FILE__ ) ), array( 'flexslider-core' ), '2.1', true );
	wp_register_style(	'flexslider-style', apply_filters( 'flexslider_style', plugins_url( '/css/flexslider.css', __FILE__ ) ), false, '2.1', 'screen' );
}


/**
 * Custom gallery markup and shortcode
 *
 */
remove_shortcode( 'gallery', 'gallery_shortcode' );
add_shortcode( 'gallery', 'gpfs_gallery_shortcode' );
function gpfs_gallery_shortcode( $attr ) {
	global $post, $wp_scripts;

	static $instance = 0;
	$instance++;

	// Allow plugins/themes to override the default gallery template?
	// Nope, not here!
	/*
	$output = apply_filters( 'post_gallery', '', $attr );
	if ( $output != '' )
		return $output;
	*/

	// We're trusting author input, so let's at least make sure it looks like a valid orderby statement
	if ( isset( $attr['orderby'] ) ) {
		$attr['orderby'] = sanitize_sql_orderby( $attr['orderby'] );
		if ( !$attr['orderby'] )
			unset( $attr['orderby'] );
	}

	extract(shortcode_atts(array(
		 'flexslider'			=> false
		,'order'				=> 'ASC'
		,'orderby'				=> 'menu_order ID'
		,'id'					=> $post->ID
 		,'size'					=> apply_filters( 'gallery_default_size', 'large' )
		,'ids'					=> ''
		,'include'				=> ''
		,'exclude'				=> ''
		,'kenburns'				=> false
		,'carousel'				=> false
		,'scale'				=> '1.5'
		,'translate'			=> '0px'
		,'transform_duration'	=> '30s'
		,'transform_origin'		=> '' // bl,tr,tl,br
	), $attr ) );

/* TO DO:
	implement add_filter( 'post_gallery', 'gpfs_flexslider' );
	if( true !== $flexslider)
		return;
*/

	$transform_origin_list = explode(',', $transform_origin);

	$localized_data = array( ( $instance - 1 ) => array( 'kenburns' => $kenburns, 'scale' => $scale ) );

	$id = intval( $id );
	if ( 'RAND' == $order )
		$orderby = 'none';

	$ids = ( ! empty( $include ) ) ? $include : $ids;

	if ( ! empty( $ids ) ) {
		$ids = preg_replace( '/[^0-9,]+/', '', $ids );
		$_attachments = get_posts( array('include' => $ids, 'post_status' => 'inherit', 'post_type' => 'attachment', 'post_mime_type' => 'image', 'order' => $order, 'orderby' => $orderby) );

		$attachments = array();
		foreach ( $_attachments as $key => $val ) {
			$attachments[$val->ID] = $_attachments[$key];
		}
	} elseif ( !empty( $exclude ) ) {
		$exclude = preg_replace( '/[^0-9,]+/', '', $exclude );
		$attachments = get_children( array('post_parent' => $id, 'exclude' => $exclude, 'post_status' => 'inherit', 'post_type' => 'attachment', 'post_mime_type' => 'image', 'order' => $order, 'orderby' => $orderby) );
	} else {
		$attachments = get_children( array('post_parent' => $id, 'post_status' => 'inherit', 'post_type' => 'attachment', 'post_mime_type' => 'image', 'order' => $order, 'orderby' => $orderby) );
	}

	if ( empty( $attachments ) )
		return '';

	if ( is_feed() ) {
		$output = "\n";
		foreach ( $attachments as $att_id => $attachment )
			$output .= wp_get_attachment_link( $att_id, $size, true ) . "\n";
		return $output;
	}

	$selector = "gallery-{$instance}";

	$output  = '<section id="' . $selector . '" class="flex-container">' . "\n";
	$output .= '<div class="flexslider fsloading">' . "\n";

	$sliderlist  = '<ul class="slides" ';
	$sliderlist .= ' data-animation="';
					$sliderlist .= ( $kenburns ) ? 'kb' : 'none';
					$sliderlist .= '"';
	$sliderlist .= ( $scale ) ? ' data-scale="' . $scale . '"' : '';
	$sliderlist .= ( $translate ) ? ' data-translate="' . $translate . '"' : '';
	$sliderlist .= ' data-transform-duration="' . $transform_duration . '"';
	$sliderlist .= '>' . "\n";

	$i = 0;
	$transform_origin_iterator = 0;
	foreach ( $attachments as $id => $attachment ) {
		$flexslider_img = wp_get_attachment_image( $id, $size, false, array(
			'class'	=> "flex-attachment-image " . $transform_origin_list[ $transform_origin_iterator++ % count( $transform_origin_list ) ],
			'alt'	=> trim( strip_tags( get_post_meta( $id, '_wp_attachment_image_alt', true ) ) ),
			'title'	=> trim( strip_tags( $attachment->post_title ) )
		));
		$flexslider_img_src = wp_get_attachment_image_src( $id, $size );
		$flexslider_img_url = $flexslider_img_src[0];

		$sliderlist .= '<li>';
		$sliderlist .= '<figure>';
		$sliderlist .= ( $kenburns ) ? '<div class="focus-shift">' : '';
		$sliderlist .= $flexslider_img;
		$sliderlist .= ( $kenburns ) ? '</div>' : '';
		$sliderlist .= ( trim( $attachment->post_excerpt ) ) ? '<figcaption class="flex-caption wp-caption-text">' . wptexturize( $attachment->post_excerpt ) . '</figcaption>' : '';
		$sliderlist .= '</figure>';
		$sliderlist .= '</li>' . "\n";
	}

	$output .= $sliderlist . '</div><!-- .slider -->' . "\n";

	if( false !== $carousel )
	 	$output .= '<div id="carousel-' . $selector . '" class="flexslider carousel carousel-' . $selector . '">' . $sliderlist . '</div><!-- .nav-carousel -->' . "\n";

	$output .= '</section><!-- #' . $selector . '.flex-container -->' . "\n";

	/* By now, we pretty much have a gallery, so let's enqueue scripts */
	if( false == wp_script_is( 'jquery' ) )
		wp_enqueue_script( 'jquery' );

	wp_enqueue_script( 'flexslider-core' );
	wp_enqueue_script( 'flexslider-init' );
	wp_enqueue_style( 'flexslider-style' );

	/* And action */
	return $output;
}