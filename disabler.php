<?php
/*
Plugin Name: Disabler
Plugin URI: http://halfelf.org/plugins/disabler/
Description: Instead of installing a million plugins to turn off features you don't want, why not use ONE plugin?
Version: 2.2
Author: Mika Epstein
Author URI: http://ipstenu.org/

Copyright 2010-11 Mika Epstein (email: ipstenu@ipstenu.org)

    This file is part of Disabler, a plugin for WordPress.

    Disabler is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 2 of the License, or
    (at your option) any later version.

    Disabler is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with WordPress.  If not, see <http://www.gnu.org/licenses/>.

*/

global $wp_version;
$exit_msg_ver = 'Sorry, but this plugin is no longer supported on pre-3.0 WordPress installs.';
if (version_compare($wp_version,"2.9","<")) { exit($exit_msg_ver); }

// Internationalization
add_action( 'init', 'ippy_dis_internationalization' );
function ippy_dis_internationalization() {
	load_plugin_textdomain('ippy_dis', false, 'disabler/languages' );
}

// Hooks
register_activation_hook( __FILE__, 'disabler_activate' );

/* FRONT END SETTINGS */
/* Texturization */
if (get_option('disabler_smartquotes') != '0' ) {
	remove_filter('comment_text', 'wptexturize');
	remove_filter('the_content', 'wptexturize');
	remove_filter('the_excerpt', 'wptexturize');
	remove_filter('the_title', 'wptexturize');
	remove_filter('the_content_feed', 'wptexturize');
	}
/* Disable Capital P in WordPress auto-correct */
if (get_option('disabler_capitalp') != '0' ) {
	remove_filter('the_content','capital_P_dangit');
	remove_filter('the_title','capital_P_dangit');
	remove_filter('comment_text','capital_P_dangit');
	}
/* Remove the <p> from being automagically added in posts */
if (get_option('disabler_autop') != '0' ) {
	remove_filter('the_content', 'wpautop');
	}
	
/* BACK END SETTINGS */
/* Disable Self Pings */
if (get_option('disabler_selfping') != '0' ) {
	function no_self_ping( &$links ) {
		$home = get_option( 'home' );
		foreach ( $links as $l => $link )
			if ( 0 === strpos( $link, $home ) )
               unset($links[$l]);
		}
	add_action( 'pre_ping', 'no_self_ping' );
	}
/* No RSS */
if (get_option('disabler_norss') != '0' ) {
	function disabler_kill_rss() {
		wp_die( _e("No feeds available.", 'ippy_dis') );
	}
 
	add_action('do_feed', 'disabler_kill_rss', 1);
	add_action('do_feed_rdf', 'disabler_kill_rss', 1);
	add_action('do_feed_rss', 'disabler_kill_rss', 1);
	add_action('do_feed_rss2', 'disabler_kill_rss', 1);
	add_action('do_feed_atom', 'disabler_kill_rss', 1);
	}
/* Post Auto Saves */
if (get_option('disabler_autosave') != '0' ) {
	
	function disabler_kill_autosave(){
		wp_deregister_script('autosave');
		}
	add_action( 'wp_print_scripts', 'disabler_kill_autosave' );
	}
/* Post Revisions */
if (get_option('disabler_revisions') != '0' ) {
	remove_action ( 'pre_post_update', 'wp_save_post_revision' );
	}

/* PRIVACY SETTINGS */	
/* Remove WordPress version from header */
if (get_option('disabler_version') != '0' ) {
	remove_action('wp_head', 'wp_generator');
	}
/* Hide blog URL from Wordpress 'phone home' */
if (get_option('disabler_nourl') != '0' ) {
	function disabler_remove_url($default)
		{
  		global $wp_version;
  		return 'WordPress/'.$wp_version;
		}
	add_filter('http_headers_useragent', 'disabler_remove_url');
	}
// Load the options page
add_action('admin_menu', 'ippy_disabler_admin_page');

function ippy_disabler_admin_page() {
	add_options_page('Disabler Options', 'Disabler', 'manage_options', 'disabler', 'ippy_disabler_options');
}

// Register and define the settings
add_action('admin_init', 'ippy_disabler_admin_init');

function ippy_disabler_admin_init(){
	register_setting(
		'disabler',               // settings page
		'ippy_disabler_options'   // option name
	);
}

// donate link on manage plugin page
add_filter('plugin_row_meta', 'disabler_donate_link', 10, 2);
function disabler_donate_link($links, $file) {
        if ($file == plugin_basename(__FILE__)) {
                $donate_link = '<a href="https://www.wepay.com/donations/halfelf-wp">Donate</a>';
                $links[] = $donate_link;
        }
        return $links;
}

// display the admin options page
function ippy_disabler_options() {

	$options     = get_option( 'ippy_disabler_options' );
	$smartquotes = $options['smartquotes'];
	$capitalp    = $options['capitalp'];
	$autop       = $options['autop'];
	$selfping    = $options['selfping'];
	$norss       = $options['norss'];
	$autosave    = $options['autosave'];
	$revisions   = $options['revisions'];
	$capitalp    = $options['capitalp'];
	$version     = $options['version'];
	$nourl       = $options['nourl'];
?>
<div class="wrap">
<h2><?php _e("Disabler", 'ippy_dis'); ?></h2>

Options relating to the Custom Plugin.
<form action="options.php" method="post">
<form method="post" width='1'>

<h3><?php _e("Front End Settings", 'ippy_dis'); ?></h3>

<p><?php _e("These are settings are changes on the front end. These are the things that affect what your site looks like when other people visit. What THEY see.  While these are actually things that annoy <strong>you</strong>, it all comes back to being things on the forward facing part of your site.", 'ippy_dis'); ?></p>

<fieldset class="disabler-frontend">
<p> <input id='smartquotes' name='ippy_disabler_options[smartquotes]' type='checkbox' value='<?php echo $smartquotes; ?>' <?php if ( ( $smartquotes != '0') && !is_null($smartquotes) ) { echo ' checked="checked"'; } ?> /><?php _e("Disable Texturization -- smart quotes (a.k.a. curly quotes), em dash, en dash and ellipsis.", 'ippy_dis'); ?></p>

<p> <input id='capitalp' name='ippy_disabler_options[capitalp]' type='checkbox' value='<?php echo $capitalp; ?>' <?php if ( ( $capitalp != '0') && !is_null($capitalp) ) { echo ' checked="checked"'; } ?> /> <?php _e("Disable auto-correction of WordPress capitalization.", 'ippy_dis'); ?></p>

<p> <input id='autop' name='ippy_disabler_options[autop]' type='checkbox' value='<?php echo $autop; ?>' <?php if ( ( $autop != '0') && !is_null($autop) ) { echo ' checked="checked"'; } ?> /> <?php _e("Disable paragraphs (i.e. &lt;p&gt;  tags) from being automatically inserted in your posts.", 'ippy_dis'); ?></p>
</fieldset>

<h3><?php _e("Back End Settings", 'ippy_dis'); ?></h3>

<p><?php _e("Back End settings affect how WordPress runs. Nothing here will <em>break</em> your install, but some turn off 'desired' functions.", 'ippy_dis'); ?></p>

<fieldset class="disabler-backend">
<p> <input id='selfping' name='ippy_disabler_options[selfping]' type='checkbox' value='<?php echo $selfping; ?>' <?php if ( ( $selfping != '0') && !is_null($selfping) ) { echo ' checked="checked"'; } ?> /> <?php _e("Disable self pings (i.e. trackbacks/pings from your own domain).", 'ippy_dis'); ?></p>

<p> <input id='norss' name='ippy_disabler_options[norss]' type='checkbox' value='<?php echo $norss; ?>' <?php if ( ( $norss != '0') && !is_null($norss) ) { echo ' checked="checked"'; } ?> /> <?php _e("Disable all RSS feeds.", 'ippy_dis'); ?></p>

<p> <input id='autosave' name='ippy_disabler_options[autosave]' type='checkbox' value='<?php echo $autosave; ?>' <?php if ( ( $autosave != '0') && !is_null($autosave) ) { echo ' checked="checked"'; } ?> /> <?php _e("Disable auto-saving of posts.", 'ippy_dis'); ?></p>

<p> <input id='revisions' name='ippy_disabler_options[revisions]' type='checkbox' value='<?php echo $revisions; ?>' <?php if ( ( $revisions != '0') && !is_null($revisions) ) { echo ' checked="checked"'; } ?> /> <?php _e("Disable post revisions.", 'ippy_dis'); ?></p>
</fieldset>

<h3><?php _e("Privacy Settings", 'ippy_dis'); ?></h3>

<p><?php _e("These settings help obfuscate information about your blog to the world (inclyding to Wordpress.org). While they don't protect you from anything, they do make it a little harder for people to get information about you and your site.", 'ippy_dis'); ?></p>

<fieldset class="disabler-privacy">
<p> <input id='version' name='ippy_disabler_options[version]' type='checkbox' value='<?php echo $version; ?>' <?php if ( ( $version != '0') && !is_null($version) ) { echo ' checked="checked"'; } ?> /> <?php _e("Disable WordPress from printing it's version in your headers (only seen via View Source).", 'ippy_dis'); ?></p>

<p> <input id='nourl' name='ippy_disabler_options[nourl]' type='checkbox' value='<?php echo $nourl; ?>' <?php if ( ( $nourl != '0') && !is_null($nourl) ) { echo ' checked="checked"'; } ?> /> <?php _e("Disable WordPress from sending your URL information when checking for updates.", 'ippy_dis'); ?></p>
</fieldset>

<p class="submit"><input type="submit" name="update" value="<?php _e("Update Options", 'ippy_dis'); ?>" /></p>

<input name="Submit" type="submit" value="<?php esc_attr_e('Save Changes'); ?>" />
</form></div>

<?php
}