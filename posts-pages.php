<?php
$vortex_like_dislike = get_option("vortex_like_dislike");

	if($vortex_like_dislike['v-switch-posts']){

		function vortex_system_bb_check($post_id){
			global $wpdb;
			
			$name = $wpdb->prefix.'bp_activity';
			$check = $wpdb->get_var($wpdb->prepare("SELECT Count(id) FROM $name WHERE id=%d",$post_id));
			if($check == '1'){
				return true;
			}else{
				return false;
			}
		}
	
		function vortex_is_buddypress($post_id){
			if(function_exists('buddypress')){
				$buddypress = true;
			}else{
				$buddypress = false;
			}
			
			$check = vortex_system_bb_check($post_id);
			
			if($buddypress && $check){
				return true;
			}else{
				return false;
			}
		}
	
		function vortex_add_meta($post_id,$key,$value,$unique){
			$bbp = vortex_is_buddypress($post_id); 
				
			if($bbp){
				return bp_activity_add_meta($post_id, $key, $value,$unique);
			}else{
				return add_post_meta($post_id, $key, $value,$unique);
			}	
		}
		
		function vortex_get_meta($post_id,$key,$value){
			$bbp = vortex_is_buddypress($post_id); 
				
			if($bbp){
				return bp_activity_get_meta($post_id, $key, $value);
			}else{
				return get_post_meta($post_id, $key, $value);
			}	
		}
		
		function vortex_update_meta($post_id,$key,$value){
			$bbp = vortex_is_buddypress($post_id); 
				
			if($bbp){
				return bp_activity_update_meta($post_id, $key, $value);
			}else{
				return update_post_meta($post_id, $key, $value);
			}	
		}
		
	
		add_action( 'wp_ajax_nopriv_vortex_system_like_button', 'vortex_system_like_button' );
		add_action( 'wp_ajax_vortex_system_like_button', 'vortex_system_like_button' );
		function vortex_system_like_button() {
				
				$vortex_like_dislike = get_option("vortex_like_dislike");
				
				$nonce = $_POST['nonce'];
				if ( ! wp_verify_nonce( $nonce, 'ajax-nonce' ) ){
					wp_die();
				}
				
				if(!$vortex_like_dislike['v-switch-anon'] && !is_user_logged_in()){
					exit();
				}
				
				$singular = sanitize_text_field($vortex_like_dislike['v-singular-text']);
				$plural = sanitize_text_field($vortex_like_dislike['v-plural-text']);
				
				$post_id = absint($_POST['post_id']);
				vortex_ra_cookie('likepost',$post_id,'dislikepost');
				$likes = 'vortex_system_likes';
				$dislikes = 'vortex_system_dislikes';
				
				if(is_user_logged_in()){
				
				$current_user_id = get_current_user_id();
				$user_key = 'vortex_system_user_'.$current_user_id;
				
				}elseif($vortex_like_dislike['v-switch-anon']){
					$current_user_id = get_current_user_id();
					$user_ip = sanitize_text_field($_SERVER['REMOTE_ADDR']);
					
					$user_key = 'vortex_system_user_'.$user_ip;
					
						if(vortex_ra_read_cookie('likepost',$post_id) == 'found'){
									$response = array(
										'likes'	   => "exit"
									);
									
									echo json_encode($response);
									exit();
						}
					
						if(vortex_ra_read_cookie('dislikepost',$post_id) == 'found'){
									$response = array(
										'dislikes'	   => "exit"
									);
									
									echo json_encode($response);
									exit();
						}
					
				}else wp_die();
				
				//defaults for user when he first time likes a post
				$user_data = array(
					'liked'    => 'liked',
					'disliked' => 'disliked'
				);
				$shall_we = apply_filters("vortex_one_vote",false);
				//if this is the first time a user likes this post add the users data to the meta post
				if(vortex_get_meta ($post_id,$user_key,true) == ''){
					vortex_add_meta($post_id, $user_key, $user_data,true);
				}elseif($shall_we && !(vortex_get_meta ($post_id,$user_key,true) == '')){
					$response = array(
						'likes'	   => "exit",
					);
					
					echo json_encode($response);
					exit();
				}
				
				$user_data_new = array(
					'liked'    => 'noliked',
					'disliked' => 'disliked',
				);
				
				$current_user = vortex_get_meta($post_id,$user_key,true);
				$disliked_value = $current_user['disliked'];
				$current_user_liked = $current_user['liked'];

				$login_number = apply_filters('vortex_login_number',1);
				$logout_number = apply_filters('vortex_logout_number',1);
				
					if(!is_user_logged_in() && $vortex_like_dislike['v-switch-anon']){
						if($current_user_liked == 'noliked'){
								$response = array(
									'likes'	   => "exit",
							);
									
							echo json_encode($response);
							exit();
						}

						
						if($current_user_liked == 'nodisliked'){
								$response = array(
									'dislikes'	   => "exit",
							);
									
							echo json_encode($response);
							exit();
						}
					}
				
				//END custom
				if($current_user_liked == 'liked' && $disliked_value == 'nodisliked'){
					$current_likes = vortex_get_meta($post_id,$likes,true);
					if(!is_user_logged_in()){
					$current_likes += $logout_number;
					}else{
						$current_likes += $login_number;
					}
					vortex_update_meta($post_id,$likes,$current_likes);
					$current_dislikes = vortex_get_meta($post_id,$dislikes,true);
					if(!is_user_logged_in()){
					$current_dislikes -= $logout_number;
					}else{
						$current_dislikes -= $login_number;
					}
					vortex_update_meta($post_id,$dislikes,$current_dislikes);
					vortex_update_meta($post_id,$user_key,$user_data_new);
					do_action("vortex_post_dislike",'+likes','-dislikes',$current_user_id,$post_id);
					/*
					$vortex_like_dislike = get_option("vortex_like_dislike");
					if($vortex_like_dislike['v-switch-tooltip']){
						$response = array(
						'dislikes' => $current_dislikes,
						'likes'	   => $current_likes,
						'both'	   => 'yes',
						'singular' => $singular,
						'plural'   => $plural,
						'title'	   => esc_html(get_the_title($post_id)),
						'url'	   => esc_url(get_permalink($post_id)),
						'id'	   => $post_id,
						'like'     => $vortex_like_dislike['v-like-text'],
						'allreadylike' => $vortex_like_dislike['v-like-text-yes'],
						'dislike'	=> $vortex_like_dislike['v-dislike-text'],
						'allreadydislike' => $vortex_like_dislike['v-dislike-yes']
					}else{}*/
					if ($vortex_like_dislike['v_custom_text']){
						$current_likes = $vortex_like_dislike['v_custom_text_post_like'];
					};
						$response = array(
						'dislikes' => $current_dislikes,
						'likes'	   => $current_likes,
						'both'	   => 'yes',
						'singular' => $singular,
						'plural'   => $plural,
						'title'	   => esc_html(get_the_title($post_id)),
						'url'	   => esc_url(get_permalink($post_id)),
						'id'	   => $post_id
					);
					
					echo json_encode($response);
					exit();
					
				}elseif($current_user_liked == 'liked'){
					//he likes the post add +1 to likes
					//change the liked value so when he clicks again we can undo his vote
					$current_likes = vortex_get_meta($post_id,$likes,true);
					if(!is_user_logged_in()){
					$current_likes += $logout_number;
					}else{
						$current_likes += $login_number;
					}
					vortex_update_meta($post_id,$likes,$current_likes);
					vortex_update_meta($post_id,$user_key,$user_data_new);
					do_action("vortex_post_dislike",'+likes','nothing',$current_user_id,$post_id);
					
				}elseif($current_user_liked == 'noliked' ){
					//he doesn't like the post anymore let's undo his vote and change his meta so we can add his vote back 
					//if he changes his mind
					$current_likes = vortex_get_meta($post_id,$likes,true);
					if(!is_user_logged_in()){
					$current_likes -= $logout_number;
					}else{
						$current_likes -= $login_number;
					}
					vortex_update_meta($post_id,$likes,$current_likes);
					vortex_update_meta($post_id,$user_key,$user_data);
					do_action("vortex_post_dislike",'-likes','nothing',$current_user_id,$post_id);
					
						$response = array(
							'likes' => $current_likes,
							'both'   => 'no',
							'singular' => $singular,
							'plural'   => $plural,
							'title'	   => esc_html(get_the_title($post_id)),
							'url'	   => esc_url(get_permalink($post_id)),
							'id'	   => $post_id
						);
						echo json_encode($response);
					
						wp_die();
				}
				
				if ($vortex_like_dislike['v_custom_text']){
					$current_likes = $vortex_like_dislike['v_custom_text_post_like'];
				}
				$response = array(
					'likes' => $current_likes,
					'both'   => 'no',
					'singular' => $singular,
					'plural'   => $plural,
					'title'	   => esc_html(get_the_title($post_id)),
					'url'	   => esc_url(get_permalink($post_id)),
					'id'	   => $post_id
				);
				echo json_encode($response);
				
				wp_die();
			
		}


	if(!$vortex_like_dislike['v-switch-dislike']){
		add_action( 'wp_ajax_nopriv_vortex_system_dislike_button', 'vortex_system_dislike_button' );
		add_action( 'wp_ajax_vortex_system_dislike_button', 'vortex_system_dislike_button' );
		function vortex_system_dislike_button() {
			
				$nonce = $_POST['nonce'];
				if ( ! wp_verify_nonce( $nonce, 'ajax-nonce' ) ){
					wp_die();
				}
				
				
				$vortex_like_dislike = get_option("vortex_like_dislike");
				
				if(!$vortex_like_dislike['v-switch-anon'] && !is_user_logged_in()){
					exit();
				}
				
				$post_id = absint($_POST['post_id']);
				vortex_ra_cookie('dislikepost',$post_id,'likepost');
				$likes = 'vortex_system_likes';
				$dislikes = 'vortex_system_dislikes';
				$current_user_id = null;
				if(is_user_logged_in()){
				
				$current_user_id = get_current_user_id();
				$user_key = 'vortex_system_user_'.$current_user_id;
				
				}elseif($vortex_like_dislike['v-switch-anon']){
					$current_user_id = get_current_user_id();
					$user_ip = sanitize_text_field($_SERVER['REMOTE_ADDR']);
					
					$user_key = 'vortex_system_user_'.$user_ip;
					
					
						if(vortex_ra_read_cookie('dislikepost',$post_id) == 'found'){
									$response = array(
										'dislikes'	   => "exit"
									);
									
									echo json_encode($response);
									exit();
						}
						
							if(vortex_ra_read_cookie('likepost',$post_id) == 'found'){
									$response = array(
										'likes'	   => "exit"
									);
									
									echo json_encode($response);
									exit();
						}
					
					
				}else wp_die();		
				//defaults for user when he first time dislikes a post
				$user_data = array(
					'liked'    => 'liked',
					'disliked' => 'disliked'
				);
				
				$shall_we = apply_filters("vortex_one_vote",false);
				if(vortex_get_meta ($post_id,$user_key,true) == ''){
					vortex_add_meta($post_id, $user_key, $user_data,true);
				}elseif($shall_we && !(vortex_get_meta ($post_id,$user_key,true) == '')){
					$response = array(
						'dislikes'	   => "exit",
					);
					
					echo json_encode($response);
					exit();
				}

				$user_data_new = array(
					'liked'    => 'liked',
					'disliked' => 'nodisliked',
				);
				
				$singular = sanitize_text_field($vortex_like_dislike['v-singular-text']);
				$plural = sanitize_text_field($vortex_like_dislike['v-plural-text']);
				
				$current_user = vortex_get_meta($post_id,$user_key,true);
				$current_user_disliked = $current_user['disliked'];
				$liked_value = $current_user['liked'];
				//custom
				$login_number = apply_filters('vortex_login_number',1);
				$logout_number = apply_filters('vortex_logout_number',1);
				//END custom
				
				if(!is_user_logged_in() && $vortex_like_dislike['v-switch-anon']){
						if($current_user_liked == 'noliked'){
								$response = array(
									'likes'	   => "exit",
							);
									
							echo json_encode($response);
							exit();
						}
						
						if($current_user_liked == 'nodisliked'){
								$response = array(
									'dislikes'	   => "exit",
							);
									
							echo json_encode($response);
							exit();
						}
					
					}
					
				if($current_user_disliked == 'disliked' && $liked_value == 'noliked'){
					
					$current_likes = vortex_get_meta($post_id,$likes,true);
					if(!is_user_logged_in()){
					$current_likes -= $logout_number;
					}else{
						$current_likes -= $login_number;
					}
					vortex_update_meta($post_id,$likes,$current_likes);
					
					$current_dislikes = vortex_get_meta($post_id,$dislikes,true);
					if(!is_user_logged_in()){
					$current_dislikes += $logout_number;
					}else{
						$current_dislikes += $login_number;
					}
					vortex_update_meta($post_id,$dislikes,$current_dislikes);
					
					vortex_update_meta($post_id,$user_key,$user_data_new);
					do_action("vortex_post_dislike",'-likes','+dislikes',$current_user_id,$post_id);
					if ($vortex_like_dislike['v_custom_text']){
						$current_dislikes = $vortex_like_dislike['v_custom_text_post_dislike'];
					}
					
					if($vortex_like_dislike['v_enable_delete'] && ($current_dislikes >= $vortex_like_dislike['v_delete_number'])){
						wp_delete_post($post_id,true);
					}
					
					
					$response = array(
						'dislikes' => $current_dislikes,
						'likes'	   => $current_likes,
						'both'	   => 'yes',
						'singular' => $singular,
						'plural'   => $plural,
						'title'	   => esc_html(get_the_title($post_id)),
						'url'	   => esc_url(get_permalink($post_id)),
						'id'	   => $post_id
					);
					
					echo json_encode($response);
					exit();
					
				}elseif($current_user_disliked == 'disliked'){
					//he likes the post add +1 to likes
					//change the liked value so when he clicks again we can undo his vote
					$current_dislikes = vortex_get_meta($post_id,$dislikes,true);
					if(!is_user_logged_in()){
					$current_dislikes += $logout_number;
					}else{
						$current_dislikes += $login_number;
					}
					vortex_update_meta($post_id,$dislikes,$current_dislikes);
					
					vortex_update_meta($post_id,$user_key,$user_data_new);
					do_action("vortex_post_dislike",'nothing','+dislikes',$current_user_id,$post_id);
					if($vortex_like_dislike['v_enable_delete'] && ($current_dislikes >= $vortex_like_dislike['v_delete_number'])){
						wp_delete_post($post_id,true);
					}
					
				}elseif($current_user_disliked == 'nodisliked'){
					//he doesn't like the post anymore let's undo his vote and change his meta so we can add his vote back 
					//if he changes his mind
					$current_dislikes = vortex_get_meta($post_id,$dislikes,true);
					if(!is_user_logged_in()){
					$current_dislikes -= $logout_number;
					}else{
						$current_dislikes -= $login_number;
					}
					vortex_update_meta($post_id,$dislikes,$current_dislikes);
					do_action("vortex_post_dislike",'nothing','-dislikes',$current_user_id,$post_id);
					vortex_update_meta($post_id,$user_key,$user_data);

						$response = array(
							'dislikes' => $current_dislikes,
							'both'   => 'no',
							'singular' => $singular,
							'plural'   => $plural,
							'title'	   => esc_html(get_the_title($post_id)),
							'url'	   => esc_url(get_permalink($post_id)),
							'id'	   => $post_id
						);
						echo json_encode($response);
						
						wp_die();
				}
				
				if ($vortex_like_dislike['v_custom_text']){
					$current_dislikes = $vortex_like_dislike['v_custom_text_post_dislike'];
				}
				
				$response = array(
					'dislikes' => $current_dislikes,
					'both'   => 'no',
					'singular' => $singular,
					'plural'   => $plural,
					'title'	   => esc_html(get_the_title($post_id)),
					'url'	   => esc_url(get_permalink($post_id)),
					'id'	   => $post_id
				);
				echo json_encode($response);
				
				wp_die();
			
		}
	}

		function vortex_system_add_dislike_class(){
				$vortex_like_dislike = get_option("vortex_like_dislike");
			if(function_exists('bbp_get_reply_id')){
				if(bbp_get_reply_id() != null){
				$id = bbp_get_reply_id();
				}else {
					$id = get_the_ID();
				}
			}else {
				$id = get_the_ID();
			}
				if(is_user_logged_in()){
					$current_user_id = get_current_user_id();
					$user_key = 'vortex_system_user_'.$current_user_id;
				}elseif(!is_user_logged_in() && $vortex_like_dislike['v-switch-anon']){
					$user_ip = sanitize_text_field($_SERVER['REMOTE_ADDR']);
					$user_key = 'vortex_system_user_'.$user_ip;
				};
				if(is_user_logged_in() || (!is_user_logged_in() && $vortex_like_dislike['v-switch-anon'])){
					$current_user_disliked = '';
					if(!vortex_get_meta($id,$user_key,true) == ''){
						$current_user = vortex_get_meta($id,$user_key,true);
						$current_user_disliked = $current_user['disliked'];
					}		
					
						if($current_user_disliked == 'nodisliked'){
							return 'vortex-p-dislike-active';
						}elseif(vortex_ra_read_cookie('dislikepost',$id) == 'found' && $current_user_disliked !== 'disliked'){
							return 'vortex-p-dislike-active';
						}
				}
		}

		function vortex_system_add_like_class(){
				$vortex_like_dislike = get_option("vortex_like_dislike");
				if(function_exists('bbp_get_reply_id')){
				if(bbp_get_reply_id() != null){
				$id = bbp_get_reply_id();
				}else {
					$id = get_the_ID();
				}
			}else {
				$id = get_the_ID();
			}
				if(is_user_logged_in()){
					$current_user_id = get_current_user_id();
					$user_key = 'vortex_system_user_'.$current_user_id;
				}elseif(!is_user_logged_in() && $vortex_like_dislike['v-switch-anon']){
					$user_ip = sanitize_text_field($_SERVER['REMOTE_ADDR']);
					$user_key = 'vortex_system_user_'.$user_ip;
				};
				if(is_user_logged_in() || (!is_user_logged_in() && $vortex_like_dislike['v-switch-anon'])){
					$current_user_liked = '';
					if(!vortex_get_meta($id,$user_key,true) == ''){
						$current_user = vortex_get_meta($id,$user_key,true);
						$current_user_liked = $current_user['liked'];
					}
					if($current_user_liked == 'noliked'){
							return 'vortex-p-like-active';
					}elseif(vortex_ra_read_cookie('likepost',$id) == 'found' && $current_user_liked !== 'liked'){
							return 'vortex-p-like-active';
					}
					
						
				}
		}

		function vortex_system_get_total_likes(){
			if(function_exists('bbp_get_reply_id')){
				if(bbp_get_reply_id() != null){
				$id = bbp_get_reply_id();
				}else {
					$id = get_the_ID();
				}
			}else {
				$id = get_the_ID();
			}
				$likes = vortex_get_meta($id,'vortex_system_likes',true);
			
				if(empty($likes)){
					return 0;
				}elseif(!$likes == ''){
				 return $dislikes = vortex_get_meta($id,'vortex_system_likes',true);
				}
		}

		function vortex_system_get_total_dislikes(){
			if(function_exists('bbp_get_reply_id')){
				if(bbp_get_reply_id() != null){
				$id = bbp_get_reply_id();
				}else {
					$id = get_the_ID();
				}
			}else {
				$id = get_the_ID();
			}
				$dislikes = vortex_get_meta($id,'vortex_system_dislikes',true);
			
				if(empty($dislikes)){
					return 0;
				}elseif(!$dislikes == ''){
				 return $dislikes = vortex_get_meta($id,'vortex_system_dislikes',true);
				}
		}
		
		function vortex_system_get_like_icon(){
			$vortex_like_dislike = get_option("vortex_like_dislike");
			
			if($vortex_like_dislike['v_button_style'] == '1'){
				return 'icon-thumbs-up-1';
			}elseif($vortex_like_dislike['v_button_style'] == '2'){
				return 'icon-thumbs-up-alt';
			}elseif($vortex_like_dislike['v_button_style'] == '3'){
				return 'icon-thumbs-up';
			}elseif($vortex_like_dislike['v_button_style'] == '4'){
				return 'icon-thumbs-up-3';
			}elseif($vortex_like_dislike['v_button_style'] == '5'){
				return 'icon-thumbs-up-4';
			}elseif($vortex_like_dislike['v_button_style'] == '6'){
				return 'icon-thumbs-up-2';
			}elseif($vortex_like_dislike['v_button_style'] == '7'){
				return 'icon-plus-circled';
			}elseif($vortex_like_dislike['v_button_style'] == '8'){
				return 'icon-plus';
			}elseif($vortex_like_dislike['v_button_style'] == '9'){
				return 'icon-up';
			}elseif($vortex_like_dislike['v_button_style'] == '10'){
				return 'icon-up-big';
			}elseif($vortex_like_dislike['v_button_style'] == '11'){
				return 'icon-heart';
			}elseif($vortex_like_dislike['v_button_style'] == '12'){
				return 'icon-star';
			}elseif($vortex_like_dislike['v_button_style'] == '13'){
				return 'icon-ok-circle';
			}elseif($vortex_like_dislike['v_button_style'] == '14'){
				return 'icon-ok';
			}
			
			
		}
		
		function vortex_system_get_dislike_icon(){
			$vortex_like_dislike = get_option("vortex_like_dislike");
			
			if($vortex_like_dislike['v_button_style'] == '1'){
				return 'icon-thumbs-down-1';
			}elseif($vortex_like_dislike['v_button_style'] == '2'){
				return 'icon-thumbs-down-alt';
			}elseif($vortex_like_dislike['v_button_style'] == '3'){
				return 'icon-thumbs-down';
			}elseif($vortex_like_dislike['v_button_style'] == '4'){
				return 'icon-thumbs-down-3';
			}elseif($vortex_like_dislike['v_button_style'] == '5'){
				return 'icon-thumbs-down-4';
			}elseif($vortex_like_dislike['v_button_style'] == '6'){
				return 'icon-thumbs-down-2';
			}elseif($vortex_like_dislike['v_button_style'] == '7'){
				return 'icon-minus-circled';
			}elseif($vortex_like_dislike['v_button_style'] == '8'){
				return 'icon-minus';
			}elseif($vortex_like_dislike['v_button_style'] == '9'){
				return 'icon-down';
			}elseif($vortex_like_dislike['v_button_style'] == '10'){
				return 'icon-down-big';
			}elseif($vortex_like_dislike['v_button_style'] == '11'){
				return 'icon-heart-broken';
			}elseif($vortex_like_dislike['v_button_style'] == '12'){
				return 'icon-star-empty';
			}elseif($vortex_like_dislike['v_button_style'] == '13'){
				return 'icon-cancel-circle';
			}elseif($vortex_like_dislike['v_button_style'] == '14'){
				return 'icon-cancel';
			}
			
			
		}
		
		function vortex_button_align(){
			$vortex_like_dislike = get_option("vortex_like_dislike");

			if($vortex_like_dislike['vortex-button-align'] == '1'){
				return 'vortex-align-left';
			}elseif($vortex_like_dislike['vortex-button-align'] == '2'){
				return 'vortex-align-center';
			}else{
				return 'vortex-align-right';
			}
		}
		
		function vortex_system_dislike_counter(){
			if(function_exists('bbp_get_reply_id')){
				if(bbp_get_reply_id() != null){
				$id = bbp_get_reply_id();
				}else {
					$id = get_the_ID();
				}
			}else {
				$id = get_the_ID();
			}
			$vortex_like_dislike = get_option("vortex_like_dislike");
			if ($vortex_like_dislike['v_custom_text'] && $vortex_like_dislike['v_custom_text_post_keep'] && vortex_system_add_dislike_class() == 'vortex-p-dislike-active'){
				if(!$vortex_like_dislike['v-switch-anon-counter'] || is_user_logged_in()){
					return '<span class="vortex-p-dislike-counter '.$id. '">'.$vortex_like_dislike['v_custom_text_post_dislike'].'</span>';
				}
			}elseif(!$vortex_like_dislike['v-switch-anon-counter'] || is_user_logged_in()){
				return '<span class="vortex-p-dislike-counter '.$id. '">'. vortex_system_get_total_dislikes().'</span>';
			}
			
		}
		
		function vortex_system_render_dislike_button($counter){
			$dislike_counter = "";
			
			if($counter){
				$dislike_counter = vortex_system_dislike_counter();
			}
			
			if(function_exists('bbp_get_reply_id')){
				if(bbp_get_reply_id() != null){
				$id = bbp_get_reply_id();
				}else {
					$id = get_the_ID();
				}
			}else {
				$id = get_the_ID();
			}

			return	'<div class="vortex-container-dislike"><input type="hidden" value="'.$id.'" /><div class="vortex-p-dislike '.$id.' '. vortex_system_add_dislike_class() .' '.vortex_system_get_dislike_icon().'">'.$dislike_counter.'</div></div>';
			
		}
		
		function vortex_system_like_counter(){
			if(function_exists('bbp_get_reply_id')){
				if(bbp_get_reply_id() != null){
				$id = bbp_get_reply_id();
				}else {
					$id = get_the_ID();
				}
			}else {
				$id = get_the_ID();
			}
			
			$vortex_like_dislike = get_option("vortex_like_dislike");
			if ($vortex_like_dislike['v_custom_text'] && $vortex_like_dislike['v_custom_text_post_keep'] && vortex_system_add_like_class() == 'vortex-p-like-active'){
				if(!$vortex_like_dislike['v-switch-anon-counter'] || is_user_logged_in()){
					return 	'<span  class="vortex-p-like-counter '. $id.'">'.$vortex_like_dislike['v_custom_text_post_like'].'</span>';
				}
			}elseif(!$vortex_like_dislike['v-switch-anon-counter'] || is_user_logged_in()){
				return 	'<span  class="vortex-p-like-counter '. $id.'">'.vortex_system_get_total_likes().'</span>';
			}	

			
		}
		
		function vortex_render_for_posts($dislike = true , $counter = true){
			
			$vortex_like_dislike = get_option("vortex_like_dislike");
			$like_counter = "";
			if(function_exists('bbp_get_reply_id')){
				if(bbp_get_reply_id() != null){
				$id = bbp_get_reply_id();
				}else {
					$id = get_the_ID();
				}
			}else {
				$id = get_the_ID();
			}
			
			
			if($counter){
				$like_counter = vortex_system_like_counter();
			}
			
			if(!$vortex_like_dislike['v-switch-dislike'] && $dislike){
				

			$buttons = '<div class="vortex-container-vote '.vortex_button_align().'"><div class="vortex-container-like"><input type="hidden" value="'.$id.'" /><div class="vortex-p-like '.$id.' '.vortex_system_add_like_class().' '.vortex_system_get_like_icon().'">'.$like_counter.'</div></div>'.vortex_system_render_dislike_button($counter).'</div>';
				
				return $buttons;
			}else {

				$buttons = '<div class="vortex-container-vote '.vortex_button_align().'"><div class="vortex-container-like"><input type="hidden" value="'.$id.'" /><div class="vortex-p-like '.$id.' '.vortex_system_add_like_class().' '.vortex_system_get_like_icon().'">'.$like_counter.'</div></div></div>';
				
				return $buttons;
			}
			
		}

		if($vortex_like_dislike['v-switch-columns']){
			
			function vortex_system_columns( $columns ) {
				
				$vortex_like_dislike = get_option("vortex_like_dislike");
				$domain = 'vortex_system_ld';
				$columns['likes'] = '<span class="dashicons dashicons-thumbs-up"></span>';
				
				if(!$vortex_like_dislike['v-switch-dislike']){
					$columns['dislikes'] = '<span class="dashicons dashicons-thumbs-down"></span>';
				}

				return $columns;
			}

			function vortex_system_columns_value( $column, $post_id ) {
				global $post;
				
				switch( $column ) {

					case 'likes' :

						$likes = vortex_get_meta( $post_id, 'vortex_system_likes', true );

						if ( empty( $likes ) )
							echo '0';

						else
							echo $likes;

						break;
		
		
					case 'dislikes' :

						$dislikes = vortex_get_meta( $post_id, 'vortex_system_dislikes', true );

						if ( empty( $dislikes ) )
							echo '0';

						else
							echo $dislikes;

						break;
					
					
					/* Just break out of the switch statement for everything else. */
					default :
						break;
				}
			}

			function vortex_system_sortable_columns( $columns ) {
				$vortex_like_dislike = get_option("vortex_like_dislike");
				
				$columns['likes'] = 'likes';

				if(!$vortex_like_dislike['v-switch-dislike']){
					$columns['dislikes'] = 'dislikes';
				}

				return $columns;
			}
			
		function vortex_ra_custom_columns(){
			$args = array(
		   '_builtin' => false,
		   'public'		=> true
		);
		
		$output = 'names'; // names or objects, note names is the default
		$operator = 'and'; // 'and' or 'or'

		$post_types = get_post_types( $args, $output, $operator );

		$vortex_like_dislike = get_option("vortex_like_dislike");
		
		$post_types['post'] = 'post';
		$post_types['page'] = 'page';

		$array = array();
		if(!(empty($vortex_like_dislike['v_exclude_post_types-p']))){
			$array = $vortex_like_dislike['v_exclude_post_types-p'];
		}
	
			foreach ( $post_types  as $post_type ) {
				
					if(!in_array($post_type,$array)){
						add_filter( 'manage_edit-'.$post_type.'_sortable_columns', 'vortex_system_sortable_columns' );
						add_action( 'manage_'.$post_type.'_posts_custom_column', 'vortex_system_columns_value', 10, 2 );
						add_filter( 'manage_edit-'.$post_type.'_columns', 'vortex_system_columns' ) ;
					}	
			}
		}
		add_action('wp_loaded','vortex_ra_custom_columns');
		}
		
	//buddypress support
	if($vortex_like_dislike['v_enable_buddybpress']){
		include(plugin_dir_path( __FILE__ ).'buddypress.php');
	}
	//end buddypress support 
		
		
	function vortex_system_insert(){
		
		function vortex_system_before_post($content){
			$vortex_like_dislike = get_option("vortex_like_dislike");
			
			if(!empty($vortex_like_dislike['v_exclude_category'])){
				$array = $vortex_like_dislike['v_exclude_category'];
				if(has_category($array)){
					return $content;
				}
			}
			if(function_exists('bbp_get_reply_id')){
				if(bbp_get_reply_id() != null){
				$id = bbp_get_reply_id();
				}else {
					$id = get_the_ID();
				}
			}else {
				$id = get_the_ID();
			}
			if(!empty($vortex_like_dislike['v_exclude_post_types-p'])){
				$array = $vortex_like_dislike['v_exclude_post_types-p'];
				if(in_array(get_post_type($id),$array)){
					return $content;
				}
			}
			
			if($vortex_like_dislike['v_button_show'][1] && is_page() && is_main_query()){
				return vortex_render_for_posts().$content;
			}elseif($vortex_like_dislike['v_button_show'][2] && is_main_query() && !is_page() && !is_singular()){
				return vortex_render_for_posts().$content;
			}elseif($vortex_like_dislike['v_button_show'][3] && is_singular('post')){
				return vortex_render_for_posts().$content;
			}elseif($vortex_like_dislike['v_button_show'][4] && !is_main_query()){
				return vortex_render_for_posts().$content;
			}elseif($vortex_like_dislike['v_button_show'][5] && is_singular() && !is_singular('post')){
				return vortex_render_for_posts().$content;
			}else return $content;
			
		}
	
		function vortex_system_after_post($content){
			$vortex_like_dislike = get_option("vortex_like_dislike");
			if(function_exists('bbp_get_reply_id')){
				if(bbp_get_reply_id() != null){
				$id = bbp_get_reply_id();
				}else {
					$id = get_the_ID();
				}
			}else {
				$id = get_the_ID();
			}
			if(!empty($vortex_like_dislike['v_exclude_category'])){
				$array = $vortex_like_dislike['v_exclude_category'];
				if(has_category($array)){
					return $content;
				}
			}
			
			if(!empty($vortex_like_dislike['v_exclude_post_types-p'])){
				$array = $vortex_like_dislike['v_exclude_post_types-p'];
				if(in_array(get_post_type($id),$array)){
					return $content;
				}
			}
			
			if($vortex_like_dislike['v_button_show'][1] && is_page() && is_main_query()){
				return $content.vortex_render_for_posts();
			}elseif($vortex_like_dislike['v_button_show'][2] && is_main_query() && !is_page() && !is_singular()){
				return $content.vortex_render_for_posts();
			}elseif($vortex_like_dislike['v_button_show'][3] && is_singular('post')){
				return $content.vortex_render_for_posts();
			}elseif($vortex_like_dislike['v_button_show'][4] && !is_main_query()){
				return $content.vortex_render_for_posts();
			}elseif($vortex_like_dislike['v_button_show'][5] && is_singular() && !is_singular('post')){
				return $content.vortex_render_for_posts();
			}else return $content;
		}
		$vortex_like_dislike = get_option("vortex_like_dislike");
		if(!post_password_required()){
			if($vortex_like_dislike['v_button_visibility'][1] && $vortex_like_dislike['v_button_visibility'][2] ){
				add_filter('the_content','vortex_system_before_post');
				add_filter('the_content','vortex_system_after_post');
				if($vortex_like_dislike['v_enable_bbpress']){
					add_filter('bbp_get_reply_content','vortex_system_before_post');
					add_filter('bbp_get_reply_content','vortex_system_after_post');
				}
			}elseif($vortex_like_dislike['v_button_visibility'][1]){
				add_filter('the_content','vortex_system_before_post');
				if($vortex_like_dislike['v_enable_bbpress']){
					add_filter('bbp_get_reply_content','vortex_system_before_post');
				}
			}elseif($vortex_like_dislike['v_button_visibility'][2]){
				add_filter('the_content','vortex_system_after_post');
				if($vortex_like_dislike['v_enable_bbpress']){
					add_filter('bbp_get_reply_content','vortex_system_after_post');
				}
			}
		}
		
	}
	add_action('wp','vortex_system_insert');
	function vortex_system_styles_scripts(){
		$vortex_like_dislike = get_option("vortex_like_dislike");
			
			if(is_user_logged_in() || $vortex_like_dislike['v-switch-anon']){
				wp_enqueue_script( 'jquery' );
				wp_enqueue_style( 'vortex_like_or_dislike', plugin_dir_url( __FILE__ ).'assets/css/style.css' );
				if(!$vortex_like_dislike['v-switch-dislike']){
					wp_enqueue_script( 'vortex_touchevents', plugin_dir_url( __FILE__ ).'assets/js/toucheventsdetect.js', array(), '1.0',true);
					wp_enqueue_script( 'vortex_like_or_dislike_js', plugin_dir_url( __FILE__ ).'assets/js/like-or-dislike.js', array(), '1.0',true);
					wp_localize_script( 'vortex_like_or_dislike_js', 'vortex_ajax_var', array(
						'url' => admin_url( 'admin-ajax.php' ),
						'nonce' => wp_create_nonce( 'ajax-nonce' )
						)
					);
				}else{
					wp_enqueue_script( 'vortex_touchevents', plugin_dir_url( __FILE__ ).'assets/js/toucheventsdetect.js', array(), '1.0',true);
					wp_enqueue_script( 'vortex_no_dislike_js', plugin_dir_url( __FILE__ ).'assets/js/no-dislike.js', array(), '1.0',true);
					wp_localize_script( 'vortex_no_dislike_js', 'vortex_ajax_var', array(
						'url' => admin_url( 'admin-ajax.php' ),
						'nonce' => wp_create_nonce( 'ajax-nonce' ),
						'color' => esc_js($vortex_like_dislike['v_default_color'])
						)
					);
				}
			}elseif(!$vortex_like_dislike['v-switch-anon'] && !is_user_logged_in()){
				wp_enqueue_style( 'vortex_like_or_dislike', plugin_dir_url( __FILE__ ).'assets/css/style.css' );
				wp_enqueue_script( 'jquery' );
				wp_enqueue_script( 'vortex_login_js', plugin_dir_url( __FILE__ ).'assets/js/login.js', array(), '1.0',true);
				wp_localize_script( 'vortex_login_js', 'vortex_login', array(
					'text' => $vortex_like_dislike['v-login-text'],
					)
				);
				
			}
			
	}
	add_action('wp_enqueue_scripts','vortex_system_styles_scripts');
	
	function vortex_print_styles(){
		$vortex_like_dislike = get_option('vortex_like_dislike');
		
		?>
		<style type="text/css">
		.vortex-container-like,
		.vortex-container-dislike {
			font-size: <?php echo esc_html($vortex_like_dislike['vortex-buttons-size']);?>px !important;	
		}
		.vortex-p-like,
		.vortex-p-dislike{
			color:<?php echo esc_html($vortex_like_dislike['v_default_color']); ?>!important;
		}
		.vortex-p-like:hover{
			color:<?php echo esc_html($vortex_like_dislike['v_like_color']); ?>!important;
		}
		.vortex-p-like-active{
			color:<?php echo esc_html($vortex_like_dislike['v_like_active_color']); ?>!important;
		}
		.vortex-p-dislike:hover{
			color:<?php echo esc_html($vortex_like_dislike['v_dislike_color']); ?>!important;
		}
		.vortex-p-dislike-active{
			color:<?php echo esc_html($vortex_like_dislike['v_dislike_active_color']); ?>!important;
		}
		</style>
		
		<?php
	}
	add_action('wp_print_styles','vortex_print_styles');
}
