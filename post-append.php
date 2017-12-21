<?php
/*
Plugin Name: Post Append
Plugin URI:  http://www.builtbyburton.com/post-append/
Description: Easily add custom content to your posts and feeds.
Author: David Burton
Author URI: http://www.builtbyburton.com/
Donate link: http://www.builtbyburton.com/please-dont-hesitate-to-donate/
Version: 1.0.0
License: GPLv2 or later
Usage: Visit the plugin's settings page to add some custom conent.

Post Append created by David Burton
Copyright (C) 2014  David Burton

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
*/

if (!defined('ABSPATH')) die();

$post_append_version = '1.0.0';
$options = get_option('post_append_options');

// i18n
function post_append_i18n_init() {
	load_plugin_textdomain('post_append', false, dirname(plugin_basename(__FILE__)) . '/languages/');
}
add_action('plugins_loaded', 'post_append_i18n_init');

// require minimum version of WordPress
function require_wp_version() {
	global $wp_version;
	$plugin = plugin_basename(__FILE__);
	$plugin_data = get_plugin_data(__FILE__, false);

	if (version_compare($wp_version, "3.5.1", "<")) {
		if (is_plugin_active($plugin)) {
			deactivate_plugins($plugin);
			$msg =  '<p><strong>' . $plugin_data['Name'] . '</strong> requires WordPress 3.5.1 or higher, and has been deactivated!</p>';
			$msg .= '<p>Please upgrade WordPress and try again.</p><p>Return to the <a href="' .admin_url() . '">WordPress Admin area</a>.</p>';
			wp_die($msg);
		}
	}
}
if (isset($_GET['activate']) && $_GET['activate'] == 'true') {
	add_action('admin_init', 'require_wp_version');
}

// custom content in all posts
if ($options['post_append_enable'] == 1) {
	add_filter('the_content', 'custom_post_append');
}

function custom_post_append($content){
	global $post;
	$options = get_option('post_append_options');
	$append_content_to_post = '';

	if (isset($options['post_append_page']) && $options['post_append_page'] != '' && $options['post_append_enable'] == 1) {
		$post_append_page = get_page_by_title( $options['post_append_page'] );
		$post_append_page_id = $post_append_page->ID;
		$append_content = get_post($post_append_page_id);
		$append_content_to_post = $append_content->post_content;
	} 

	else if(isset($options['post_append_rich_text']) && $options['post_append_rich_text'] != '' && $options['post_append_enable'] == 1) {
    	$append_content_to_post = $options['post_append_rich_text'];
	}
	
	$content =  $content . $append_content_to_post;

	return $content;
}

// display settings link on plugin page
add_filter ('plugin_action_links', 'post_append_plugin_action_links', 10, 2);
function post_append_plugin_action_links($links, $file) {
	if ($file == plugin_basename(__FILE__)) {
		$post_append_links = '<a href="'. get_admin_url() .'options-general.php?page=post-append/post-append.php">'. __('Settings', 'post_append') .'</a>';
		array_unshift($links, $post_append_links);
	}
	return $links;
}

// rate plugin link
function add_post_append_links($links, $file) {
	if ($file == plugin_basename(__FILE__)) {
		$rate_url = 'http://wordpress.org/support/view/plugin-reviews/' . basename(dirname(__FILE__)) . '?rate=5#postform';
		$links[] = '<a href="' . $rate_url . '" target="_blank" title="Click here to rate and review this plugin on WordPress.org">Rate this plugin</a>';
	}
	return $links;
}
add_filter('plugin_row_meta', 'add_post_append_links', 10, 2);

// delete plugin settings
function post_append_delete_plugin_options() {
	delete_option('post_append_options');
}
if ($options['default_options'] == 1) {
	register_uninstall_hook (__FILE__, 'post_append_delete_plugin_options');
}

// define default settings
register_activation_hook (__FILE__, 'post_append_add_defaults');
function post_append_add_defaults() {
	$tmp = get_option('post_append_options');
	if(($tmp['default_options'] == '1') || (!is_array($tmp))) {
		$arr = array(
			'post_append_page'    => '',
			'post_append_rich_text' => '<em>- Post Append WP plugin.</em>',
			'post_append_enable' => 0,
			'default_options'     => 0,
		);
		update_option('post_append_options', $arr);
	}
}

// whitelist settings
add_action ('admin_init', 'post_append_init');
function post_append_init() {
	register_setting('post_append_plugin_options', 'post_append_options', 'post_append_validate_options');
}

// sanitize and validate input
function post_append_validate_options($input) {
	//global $post_append_page, $post_append_rich_text;

	$input['post_append_page'] = wp_kses_post($input['post_append_page']);
	$input['post_append_rich_text'] = wp_kses_post($input['post_append_rich_text']);
	$input['post_append_enable'] = wp_kses_post($input['post_append_enable']);

	if (!isset($input['post_append_page'])) $input['post_append_page'] = null;

	if (!isset($input['post_append_rich_text'])) $input['post_append_rich_text'] = null;

	if (!isset($input['post_append_enable'])) $input['post_append_enable'] = null;

	if (!isset($input['default_options'])) $input['default_options'] = null;
	$input['default_options'] = ($input['default_options'] == 1 ? 1 : 0);

	return $input;
}

// add the options page
add_action ('admin_menu', 'post_append_add_options_page');
function post_append_add_options_page() {
	add_options_page('Post Append', 'POST APPEND', 'manage_options', __FILE__, 'post_append_render_form');
}

// create the options page
function post_append_render_form() {
	global $post_append_version, $post_append_page, $post_append_rich_text; $post_append_enable; ?>

	<style type="text/css">
		#post_append-admin h2 small { font-size: 60%; }
		#post_append-admin h3, .post_append-overview h3 { font-size:18px; border-bottom: none; margin: 10px;}
		#post_append-admin h3.no-toggle { cursor:default; }
		#post_append-admin h4, 
		#post_append-admin p { margin: 15px; line-height: 18px; }
		#post_append-admin .button-primary { margin: 0 0 15px 15px; }

		#post_append-overview span.hidden, .hidden { display: none; }
		#post_append-overview span.warn { color:#ff0000; }
		.post_append-overview { padding-left: 77px; background: url(<?php echo plugins_url(); ?>/post-append/post-append-logo.png) no-repeat; height:113px; width:400px;margin:10px 0px 10px 10px;}
		.post_append-content { border-top: 1px solid #dfdfdf; }
		.post_append-content p { margin-left:10px; font-size:18px; }
		.post_append-content p.small { margin-left:10px; font-size:14px; }
		.post_append_editor { margin: 10px; }
		.label { font-size: 14px; }
	</style>

	<div id="post_append-admin" class="wrap">
		<?php screen_icon(); ?>
		<h2><?php _e('Post Append', 'post_append'); ?> <small><?php echo 'v' . $post_append_version; ?></small></h2>

			<form method="post" action="options.php">
			<?php $options = get_option('post_append_options'); settings_fields('post_append_plugin_options'); ?>
			<div class="metabox-holder">	

				<div id="post_append-overview" class="postbox">
					<h3 class="no-toggle"><?php _e('Overview', 'post_append'); ?></h3>
						<div class="post_append-content">
						<div class="post_append-overview"></div>
							<p>
								<?php _e('<strong>Post Append</strong> makes it easy to add custom content to the bottom of all posts.', 'post_append'); ?>
							</p>
							<p class="small">
								<strong>OPTION 1: </strong><br>By selecting a published page from Option 1, any changes made to the selected page will also update the Post Append content on all posts.<br>This option overrides any custom content entered under Option 2.
							</p>
							<p class="small">
								<strong>OPTION 2: </strong><br>To use the custom content, select 'None' in Option 1. The custom content will be appened to all posts.
							</p>
							<p class="small">
								<strong>CKECK AND SAVE: </strong><br>Checking the 'Enable Post Append' option, then the 'Save Settings' button will update add the Post Append content to all posts.<br>Unchecking the 'Enable Post Append' option, then the 'Save Settings' button will update add remove the Post Append content on all posts.
							</p>
						</div>
				</div>

			</div>

			<div class="metabox-holder">	
				<div id="post_append-overview" class="postbox">
					<h3 class="no-toggle"><?php _e('OPTION 1', 'post_append'); ?></h3>
					<div class="post_append-content option1">

						<p>
							<?php _e('Select a Published Page to append to all posts.', 'post_append'); ?>

							<select class="dropdown" name="post_append_options[post_append_page]"> 
								<option value="">
								<?php echo esc_attr( __( 'None' ) ); ?></option> 
								<?php 
								$pages = get_pages(); 
								foreach ( $pages as $page ) {
								if (esc_attr( __($options['post_append_page'])) == $page->post_title) {
									$selected = "selected";
								} else {
									$selected = "";
								}
								$option = '<option value="' . $page->post_title . '" ' . $selected . '>';
								$option .= $page->post_title;
								$option .= '</option>';
								echo $option;
								}
								?>
							</select>
						</p>

					</div>
				</div>
			</div>

			<div class="metabox-holder">	
				<div id="post_append-overview" class="postbox">
					<h3 class="no-toggle"><?php _e('OPTION 2 ', 'post_append'); ?><span class="warn hidden">To use this option, select 'None' from Option 1.</span></h3>
					<div class="post_append-content option2 hidden">

						<p>
							<?php _e('Add custom content below.', 'post_append'); ?>
						</p>

						<div class="post_append_editor">
							<?php
							$args = array("textarea_rows" => 10, "textarea_name" => "post_append_options[post_append_rich_text]", "editor_class" => "post_append_editor_custom");
							wp_editor($options['post_append_rich_text'], "post_append_editor_1", $args);
							?>
						</div>

					</div>
				</div>
			</div>

			<p>
				<?php $post_append_enabled_checked = (isset($options['post_append_enable']) && $options['post_append_enable'] == 1 ? " checked" : ""); ?>
				<input name="post_append_options[post_append_enable]" type="checkbox" value="1" <?php echo $post_append_enabled_checked; ?> /> 
				<label class="label" for="post_append_options[post_append_enable]"><?php _e('Enable Post Append', 'post_append'); ?></label>
			</p>

			<input type="submit" class="button-primary" value="<?php _e('Save Settings', 'post_append'); ?>" />

			</form>
	</div>

	<script type="text/javascript">
		// prevent accidents
		if(!jQuery("#scs_restore_defaults").is(":checked")){
			jQuery('#scs_restore_defaults').click(function(event){
				var r = confirm("<?php _e('(This Option has been overridden because a Page has been selected in Option 1. Set Option 1 to None to use this option.)', 'post_append'); ?>");
				if (r == true){  
					jQuery("#scs_restore_defaults").attr('checked', true);
				} else {
					jQuery("#scs_restore_defaults").attr('checked', false);
				}
			});
		}
		// selector change
		jQuery(document).ready(function(){
			var selection = jQuery( "select option:selected" ).val();
			if (selection != "") { 
				jQuery('.option2').hide();
				jQuery('#post_append-overview span.hidden').css('display','inline-block');
			} else {
				jQuery('.option2').show();
				jQuery('#post_append-overview span.hidden').css('display','none');
			}
			jQuery( '.dropdown' ).change(function() {
			selection = jQuery( "select option:selected" ).val();	
				if (selection != "") { 
					jQuery('.option2').hide();
					jQuery('#post_append-overview span.hidden').css('display','inline-block');
				} else {
					jQuery('.option2').show();
					jQuery('#post_append-overview span.hidden').css('display','none');
				}
			});
		});
	</script>
<?php }