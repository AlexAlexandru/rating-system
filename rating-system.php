<?php
/**
* Plugin Name: Rating System
* Plugin URI: http://github.com/AlexAlexandru/rating-system
* Description: The simple way to add like or dislike buttons.
* Version: 2.7.6.1
* Author: AlexAlexandru
* Author URI: https://github.com/AlexAlexandru
* License: GPL2
* Text Domain: vortex_system_ld
* Domain Path: /languages
*/
if ( ! defined( 'ABSPATH' ) ) exit;//exit if accessed directly

$sse = plugin_dir_path( __FILE__).'admin/framework/autoload.php';
	if ( file_exists($sse) ) {
		include($sse);
	};

$vortex_like_dislike = null;

function vortex_ra_read_cookie($name,$postid){
	if(isset($_COOKIE[$name])){
		$decode = json_decode($_COOKIE[$name]);
		$found = array_search($postid, $decode);

		if ($found !== false) {
			return 'found';
		} else {
			return 'notfound';
		}
	}else{
		return 'notfound';
	}
}

function vortex_ra_cookie($name,$postid,$name2){
	if(!isset($_COOKIE[$name])){
		$array = json_encode(array($postid));
		setcookie($name,$array,time()+ 2419200,'/',COOKIE_DOMAIN,is_ssl(),true);
	}else{
		$decode = json_decode($_COOKIE[$name]);
		if(!in_array($postid,$decode)){
			array_push($decode,$postid);
			$encode = json_encode($decode);
			setcookie($name,$encode,time()+ 2419200,'/',COOKIE_DOMAIN,is_ssl(),true);
		}
	}
}

//require all usefull stuffs
function vortex_systen_main_function(){
	global $vortex_like_dislike;

	if(class_exists('myCRED_Hook')){
		include(plugin_dir_path( __FILE__ ).'mycredcomments.php');
		include(plugin_dir_path( __FILE__ ).'mycredposts.php');
		function vortex_mycred_references_filter($references){
			$references['vortex_like_posts_mycred_author_content'] = __( 'Receive like for posts(content author)', 'vortex_system_ld' );
			$references['vortex_like_posts_mycred_author'] = __( 'Receive like for posts(like author)', 'vortex_system_ld' );
			$references['vortex_like_coms_mycred_author_content'] = __( 'Receive like for comments(content author)', 'vortex_system_ld' );
			$references['vortex_like_coms_mycred_author'] = __( 'Receive like for comments(like author)', 'vortex_system_ld' );
			return $references;
		}
		add_filter('mycred_all_references','vortex_mycred_references_filter');
	}
	
		$vortex_like_dislike = get_option('vortex_like_dislike');
			load_plugin_textdomain( 'vortex_system_ld', FALSE, basename(plugin_dir_path( __FILE__ )). '/languages' );
			
			$options = plugin_dir_path( __FILE__).'admin/vortexlikedislike.php';

				if ( file_exists($options) ) {
					include($options);
				};

				//donation button
				function vortex_system_donation_button(){
					echo '<form style="width:260px;margin:0 auto;" action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_top">
						<input type="hidden" name="cmd" value="_s-xclick">
						<input type="hidden" name="hosted_button_id" value="VVGFFVJSFVZ7S">
						<input type="image" src="https://www.paypalobjects.com/en_US/i/btn/btn_donateCC_LG.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!">
						<img alt="" border="0" src="https://www.paypalobjects.com/en_US/i/scr/pixel.gif" width="1" height="1">
						</form>
						';
				}
				add_action('sse_footer_vortex_like_dislike','vortex_system_donation_button');
			
				if($vortex_like_dislike['v-switch-posts'] && isset($vortex_like_dislike['v-switch-posts'])){
					include(plugin_dir_path( __FILE__ ).'posts-pages.php');
					//load metabox
					include(plugin_dir_path( __FILE__).'metabox.php');
				}
				
				if($vortex_like_dislike['v-switch-comments'] && isset($vortex_like_dislike['v-switch-comments'])){
					include(plugin_dir_path( __FILE__ ).'comments.php');
				}	
}

add_action('plugins_loaded','vortex_systen_main_function');

function rating_system_load_widgets(){
	$widget = plugin_dir_path( __FILE__ ).'widget/widget.php';
	if(file_exists($widget)){
		include( $widget );
	}
}
add_action('plugins_loaded','rating_system_load_widgets');
//add shortcode
function vortex_rating_system_register_shortcodes(){

		function vortex_shortcode_render_posts($atts){
				extract( shortcode_atts(  array(
					'counter' => "yes",
				), $atts ) );
				
				if($counter == "yes"){
					return vortex_render_for_posts(true,true);
				}else{
					return vortex_render_for_posts(true,false);
				}
		}
		
		function vortex_shortcode_render_posts_disable_dislike($atts){
				extract( shortcode_atts(  array(
					'counter' => true,
				), $atts ) );
				
				
				if($counter == "yes"){
					return vortex_render_for_posts(false,true);
				}else{
					return vortex_render_for_posts(false,false);
				}
		}
		
		function vortex_shortcode_render_comments(){
				return vortex_render_for_comments();
		}
		
		function vortex_shortcode_render_comments_disable_dislike(){
				return vortex_render_for_comments(false);
		}
		
		function vortex_shortcode_render_top_posts($atts){
			
			extract( shortcode_atts(  array(
				'number' => '5',
				'display_counter' => 'yes',
				'display_content' => 'no',
				'link_to_post'	  => 'yes',
				'category_slugs'	=> '',
			), $atts ) );
			
			$args = array(
					'orderby'			=> 'meta_value_num',
					'meta_key'			=> 'vortex_system_likes',
					'post_type' 		=> 'post',
					'post_status'		=> 'publish',
					'posts_per_page'	=> $number,
					'category_name'		=> $category_slugs

			);	
			// The Query
			$query = new WP_Query( $args );
			// The Loop
			if ( $query->have_posts() ) {
				
				echo '<ul>';
				while ( $query->have_posts() ) {
					$query->the_post();
						$current_likes = get_post_meta(get_the_ID(),'vortex_system_likes',true);
						echo '<li class="top-posts '.get_the_ID().' ">';
						echo '<div class="top-posts-title '.get_the_ID().'">';
						if($link_to_post == "yes"){
								echo '<a class="top-posts-link '.get_the_ID().'" href="'.get_the_permalink().'">'.get_the_title().'</a>';
						} else {
								the_title();
						}
							
						if($display_counter == 'yes'){
							echo ' '.$current_likes.' likes';
						}
						echo '</div>';
						
						if($display_content == 'yes'){
							echo '<div class="top-posts-content '.get_the_ID().'">';
								echo get_the_content();
							echo '</div>';
						}
						
						echo '</li>';
				}
				echo '</ul>';
			} else {
				echo 'No posts found.';
			}
			// Restore original Post Data
			wp_reset_postdata();
			
		}
		
		add_shortcode('rating-system-top-posts', 'vortex_shortcode_render_top_posts');
		
		add_shortcode('rating-system-posts', 'vortex_shortcode_render_posts');
		add_shortcode('rating-system-posts-disable-dislike', 'vortex_shortcode_render_posts_disable_dislike');
		
		add_shortcode('rating-system-comments', 'vortex_shortcode_render_comments');
		add_shortcode('rating-system-comments-disable-dislike', 'vortex_shortcode_render_comments_disable_dislike');
}
add_action( 'init', 'vortex_rating_system_register_shortcodes');

