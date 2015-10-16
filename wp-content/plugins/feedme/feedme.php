<?php
/*
 * Plugin Name: Feedme
 * Plugin URI: http://www.tierra-innovation.com
 * Description: Add additional control over your WordPress Feeds.  Assign a global image, images per post, set display in feed delay, exclude categories of content and disable all feeds.
 * Version: 1.0
 * Author: Tierra Innovation
 * Author URI: http://www.tierra-innovation.com/
 */

/*
 * This plugin is currently available for use in all personal
 * or commercial projects under both MIT and GPL licenses. This
 * means that you can choose the license that best suits your
 * project, and use it accordingly.
 *
 * MIT License: http://www.tierra-innovation.com/license/MIT-LICENSE.txt
 * GPL2 License: http://www.tierra-innovation.com/license/GPL-LICENSE.txt
 */

// set admin screen
function modify_feedme_menu() {
	add_options_page(
		'Feedme', // page title
		'Feedme', // sub-menu title
		'manage_options', // access/capa
		'feedme.php', // file
		'admin_feedme_options' // function
	);
}

add_action('admin_menu', 'modify_feedme_menu');



// set options
function set_feedme_options() {

	// global image options
	$feedme_global_image_allow = get_option('feedme_global_image_allow');
	$feedme_global_image_path = get_option('feedme_global_image_path');
	$feedme_global_image_width = get_option('feedme_global_image_width');
	$feedme_global_image_height = get_option('feedme_global_image_height');
	$feedme_global_feedme_image_link = get_option('feedme_global_feedme_image_link');

	// post image options
	$feedme_image_allow = get_option('feedme_image_allow');
	$feedme_image_thumbnail_size = get_option('feedme_image_thumbnail_size');

	// publish date options
	$feedme_future_publish_value = get_option('feedme_future_publish_value');

	// category exclude options
	$feedme_exclude_category = get_option('feedme_exclude_category');
	$feedme_exclude_category = explode(",", $feedme_exclude_category);

	// disable feed options
	$disable_feed = get_option('disable_feed');
	$disable_feed_rdf = get_option('disable_feed_rdf');
	$disable_feed_rss = get_option('disable_feed_rss');
	$disable_feed_rss2 = get_option('disable_feed_rss2');
	$disable_feed_atom = get_option('disable_feed_atom');

}



// unset options upon deactivation
function unset_feedme_options() {

	// global image options
	delete_option('feedme_global_image_allow');
	delete_option('feedme_global_image_path');
	delete_option('feedme_global_image_width');
	delete_option('feedme_global_image_height');
	delete_option('feedme_global_feedme_image_link');

	// post image options
	delete_option('feedme_image_allow');
	delete_option('feedme_image_thumbnail_size');

	// publish date options
	delete_option('feedme_future_publish_value');

	// category exclude options
	delete_option('feedme_exclude_category');

	// disable feed options
	delete_option('disable_feed');
	delete_option('disable_feed_rdf');
	delete_option('disable_feed_rss');
	delete_option('disable_feed_rss2');
	delete_option('disable_feed_atom');

}



// form post
function admin_feedme_options() {

	if ($_REQUEST['submit'])
		update_feedme_options();

	print_feedme_form();

}



// updating settings
function update_feedme_options() {

		if (is_array($_REQUEST['feedme_exclude_category']))
		{
			$_REQUEST['feedme_exclude_category'] = implode(",", $_REQUEST['feedme_exclude_category']);
		}

		foreach (array (
			"feedme_global_image_allow",
			"feedme_global_image_path",
			"feedme_global_image_width",
			"feedme_global_image_height",
			"feedme_global_feedme_image_link",
			"feedme_image_allow",
			"feedme_image_thumbnail_size",
			"feedme_future_publish_value",
			"feedme_exclude_category",
			"disable_feed",
			"disable_feed_rdf",
			"disable_feed_rss",
			"disable_feed_rss2",
			"disable_feed_atom"
		) as $option)

		{
			if (isset($_REQUEST[$option]))
            {
				update_option($option,$_REQUEST[$option]);
			}
			else
			{
				delete_option($option);
			}
        }

    echo "
        <div id='message' class='updated fade'>

            <p>Options saved.</p>

        </div>
    ";

}



// exclude checked categories in feed
function feedme_future_publish($feedme_future_where) {

	global $wpdb;

	$feedme_future_publish_minute_value = '';

	if ( is_feed() ) {

		$feedme_now_time = gmdate('Y-m-d H:i:s');
		$feedme_future_publish_minute_value = get_option('feedme_future_publish_value');
		$feedme_future_where .= " AND TIMESTAMPDIFF(MINUTE, $wpdb->posts.post_date_gmt, '$feedme_now_time') > $feedme_future_publish_minute_value ";

	}

	return $feedme_future_where;

}

add_filter('posts_where', 'feedme_future_publish');



// set image for global feed
if (get_option('feedme_global_image_allow') == "yes") {

	function feedme_global_feed_rss_image()
	{

		$feedme_global_rss_name = get_bloginfo('name');
		$feedme_global_rss_style_dir = get_option('feedme_global_image_path');

		if ($feedme_global_rss_style_dir == "") {
			$feedme_global_rss_style_dir = get_option('siteurl') . '/wp-content/plugins/feedme/images/wordpress.jpg';
		}

		if (get_option('feedme_global_feedme_image_link') == "")
			$feedme_global_rss_url = get_bloginfo('url');

		else
			$feedme_global_rss_url = get_option('feedme_global_feedme_image_link');

		$feedme_global_rss_desc = get_bloginfo('description');

		$xml = <<<RSS

	<image>
		<title>$feedme_global_rss_name</title>
		<url>$feedme_global_rss_style_dir</url>
		<link>$feedme_global_rss_url</link>
		<width>75</width>
		<height>75</height>
		<description>$feedme_global_rss_desc</description>
	</image>
	
	<enclosure url="$feedme_global_rss_style_dir" type="image/jpg" />

RSS;
    
	    print $xml;
    
	}

	// specify site icon for rss header
	add_action('rss2_head','feedme_global_feed_rss_image');
	add_action('rss_head','feedme_global_feed_rss_image');
	add_action('commentsrss2_head','feedme_global_feed_rss_image');
	add_action('rdf_head','feedme_global_feed_rss_image');
	add_action('atom_head','feedme_global_feed_rss_image');

}



// add post specific image

if (get_option('feedme_image_allow') == "yes") {

	$feedme_img_size = get_option('feedme_image_thumbnail_size');
	$feedme_default_img_size = get_option('feedme_image_thumbnail_size');
	
	function feedme_single_post_include (){

		global $post;

		if (get_option('feedme_image_thumbnail_size') == 'thumbnail')
			$feedme_img_url = feedme_single_post_img_url('thumbnail');
		else
			$feedme_img_url = feedme_single_post_img_url('medium');

		if ($feedme_img_url != '') :

		print "
			<description><![CDATA[$post->post_excerpt<br /><br /><a href='" . $feedme_img_url . "'><img src='" . $feedme_img_url . "' alt='' title='' /></a>]]></description>
			<enclosure url='".$feedme_img_url."' type='image/jpg' />
		";

		endif;

	}

	function feedme_single_post_img_url($feedme_default_img_size) {	

		global $post;

		$attachments = get_children( array('post_parent' => $post->ID, 'post_type' => 'attachment', 'post_mime_type' => 'image', 'order' => 'ASC', 'numberposts' => 1) );

		if($attachments == true) :
			foreach($attachments as $id => $attachment) :
				$feemdme_img = wp_get_attachment_image_src($id, $feedme_default_img_size);			
			endforeach;		
		endif;

		return $feemdme_img[0];

	}

	// specify image per post in rss
	add_action('rss_item','feedme_single_post_include');
	add_action('rss2_item','feedme_single_post_include');
	add_action('rdf_item','feedme_single_post_include');
	add_action('atom_item','feedme_single_post_include');

}


// print form to admin
function print_feedme_form() {

	// set blank options for global feed images
	$feedme_global_image_allow_yes = '';
	$feedme_global_image_allow_no = '';

	// set blank options for post feed images
	$feedme_image_allow_yes = '';
	$feedme_image_allow_no = '';

	// set blank options for cms disable feed options
	$disable_feed_selected = '';
	$disable_feed_rdf_selected = '';
	$disable_feed_rss_selected = '';
	$disable_feed_rss2_selected = '';
	$disable_feed_atom_selected = '';

	// check options for global feed images
	if (get_option('feedme_global_image_allow') == "yes")
		$feedme_global_image_allow_yes = ' selected="selected"';
	else
		$feedme_global_image_allow_no = ' selected="selected"';

	// check options for post feed images
	if (get_option('feedme_image_allow') == "yes")
		$feedme_image_allow_yes = ' selected="selected"';
	else
		$feedme_image_allow_no = ' selected="selected"';

	// check sizing options for post feed images
	if (get_option('feedme_image_thumbnail_size') == "thumbnail")
		$feedme_image_thumbnail_size_thumbnail = ' selected="selected"';
	else
		$feedme_image_thumbnail_size_medium = ' selected="selected"';

	// check options for form population
	if ( get_option('disable_feed') == "yes" )
		$disable_feed_selected = 'checked';
	else
		$disable_feed_selected = '';

	if ( get_option('disable_feed_rdf') == "yes" )
		$disable_feed_rdf_selected = 'checked';
	else
		$disable_feed_rdf_selected = '';

	if ( get_option('disable_feed_rss') == "yes" )
		$disable_feed_rss_selected = 'checked';
	else
		$disable_feed_rss_selected = '';

	if ( get_option('disable_feed_rss2') == "yes" )
		$disable_feed_rss2_selected = 'checked';
	else
		$disable_feed_rss2_selected = '';

	if ( get_option('disable_feed_atom') == "yes" )
		$disable_feed_atom_selected = 'checked';
	else
		$disable_feed_atom_selected = '';

	$feedme_wp_url = get_bloginfo("url");

	$feedme_global_image_path_value = get_option('feedme_global_image_path');
	$feedme_global_image_height_value = get_option('feedme_global_image_height');
	$feedme_global_image_width_value = get_option('feedme_global_image_width');
	$feedme_global_feedme_image_link_value = get_option('feedme_global_feedme_image_link');
	$feedme_future_publish_minute_value = get_option('feedme_future_publish_value');

	if ($feedme_global_image_path_value == "") {
		$feedme_global_image_path_value = get_option('siteurl') . '/wp-content/plugins/feedme/images/wordpress.jpg';
	}

	if ($feedme_global_image_width_value == "") {
		$feedme_global_image_width_value = "75";
	}

	if ($feedme_global_image_height_value == "") {
		$feedme_global_image_height_value = "75";
	}

	if ($feedme_global_feedme_image_link_value == "") {
		$feedme_global_feedme_image_link_value = get_bloginfo('url');
	}

	if ($feedme_future_publish_minute_value == "") {
		$feedme_future_publish_minute_value = "0";
	}

	// execute the form
	print "

	<div class='wrap'>

		<div id='icon-options-general' class='icon32'><img src='http://tierra-innovation.com/wordpress-cms/logos/src/feedme/1.0/default.gif' alt='' title='' /><br /></div>

		<h2>Feedme Options</h2>

			<form method='post'>

				<h3>Global Feed Image</h3>

				<ul>
					<li>
					
						<select name='feedme_global_image_allow' style='width: 100px;'>
							<option value='yes' $feedme_global_image_allow_yes>Yes</option>
							<option value='no' $feedme_global_image_allow_no>No</option>
						</select>
					
						<label for='feedme_global_image_allow'>Show Single Image On Global Feed</label>

					</li>
					<li><input type='text' name='feedme_global_image_path' value='$feedme_global_image_path_value' style='width: 300px;' /> Full URL To Custom Image (if blank, loads plugin default image in images/wordpress.jpg)</li>
					<li><input type='text' name='feedme_global_image_width' value='$feedme_global_image_width_value' style='width: 60px;' /> x <input type='text' name='feedme_global_image_height' value='$feedme_global_image_height_value' style='width: 60px;' /> Width x Height</li>
					<li><input type='text' name='feedme_global_feedme_image_link' value='$feedme_global_feedme_image_link_value' style='width: 300px;' /> Image Link</li>
				</ul>

				<h3><br />Post Feed Images</h3>

				<p>Select the default image assigned to each post.  Specify the default size to show in the feed.</p>

				<ul>
					<li>
					
						<select name='feedme_image_allow' style='width: 100px;'>
							<option value='yes' $feedme_image_allow_yes>Yes</option>
							<option value='no' $feedme_image_allow_no>No</option>
						</select>
					
						<label for='feedme_image_allow'>Show Images Per Post In Feed</label>

					</li>
					<li>
					
						<select name='feedme_image_thumbnail_size' style='width: 100px;'>
							<option value='thumbnail' $feedme_image_thumbnail_size_thumbnail>Thumbnail</option>
							<option value='medium' $feedme_image_thumbnail_size_medium>Medium</option>
						</select>
					
						<label for='feedme_image_thumbnail_size'>Set Default Image Size</label>

					</li>
				</ul>

				<h3><br />Feed Delay</h3>

				<p>Frequently updating your latest posts?  You can set the delay time it will show in the feed so you're changes aren't published out until you're ready.</p>

				<input type='text' name='feedme_future_publish_value' value='$feedme_future_publish_minute_value' style='width: 40px;' /> minutes

				<h3><br />Exclude Categories</h3>

				<p>By checking the box next to each category, you exclude the content from that category from displaying in your WordPress Feeds.</p>

				<ul>

				";

					$args = array(
						'orderby' => 'name',
						'order' => 'ASC'
					);

					$categories=get_categories('hide_empty=0');

					$feedme_exclude_category = get_option('feedme_exclude_category');
					$feedme_exclude_category = explode(",", $feedme_exclude_category);

					foreach($categories as $category) { 

						$checked = '';

						if (in_array('-' . $category->term_id, $feedme_exclude_category))
							$checked = "checked=\"checked\" ";

						echo '<li><input type="checkbox" name="feedme_exclude_category[]" value="-' . $category->term_id . '" '.$checked.' /> ' . $category->name . '</li>';

					}

				print "

				</ul>

				<h3><br />CMS Function: Disable Feeds</h3>

				<p>Using WordPress as a CMS?  Perhaps you don't want to display RSS on your site.  Check the boxes below to disable each feed type.</p>

				<ul>
					<li><input type='checkbox' name='disable_feed' value='yes' $disable_feed_selected /> Disable Feed (<a href='$feedme_wp_url/feed'>/feed</a>)</li>
					<li><input type='checkbox' name='disable_feed_rdf' value='yes' $disable_feed_rdf_selected /> Disable RTF Feed (<a href='$feedme_wp_url/feed/rtf'>/feed/rtf</a>)</li>
					<li><input type='checkbox' name='disable_feed_rss' value='yes' $disable_feed_rss_selected /> Disable RSS Feed (<a href='$feedme_wp_url/feed/rss'>/feed/rss</a>)</li>
					<li><input type='checkbox' name='disable_feed_rss2' value='yes' $disable_feed_rss2_selected /> Disable RSS2 Feed (<a href='$feedme_wp_url/feed/rss2'>/feed/rss2</a>)</li>
					<li><input type='checkbox' name='disable_feed_atom' value='yes' $disable_feed_atom_selected /> Disable ATOM Feed (<a href='$feedme_wp_url/feed/atom'>/feed/atom</a>)</li>
				</ul>

				<br />
				<input type='submit' name='submit' class='button-primary' value='Save Options' /><br /><br />

			</form>

		</div>

	";

}




// exclude checked categories in feed
function feedme_exclude_category_from_feed($query) {

	//var_dump(get_option('feedme_exclude_category'));

    if ($query->is_feed) {
        //$query->set('cat',get_option('feedme_exclude_category'));
        $query->query_vars['cat'] = get_option('feedme_exclude_category');    
    }

	return $query;

}

add_filter('pre_get_posts','feedme_exclude_category_from_feed');



// disable feeds if CMS
function feedme_disable_feeds() {

	$feedme_wp_url = get_bloginfo("url");

	// javascript redirect to home page
	print "<script type='text/javascript'>window.location.href = '$feedme_wp_url';</script>";

	// added if js is disabled or delayed
	wp_die( __('This page has moved to <a href="$feedme_wp_url">$feedme_wp_url</a>!') );

}

// feed/ url
if ( get_option('disable_feed') == "yes" )
	add_action('do_feed', 'feedme_disable_feeds', 1);

// feed/rdf/ url
if ( get_option('disable_feed_rdf') == "yes" )
	add_action('do_feed_rdf', 'feedme_disable_feeds', 1);

// feed/rss/ url
if ( get_option('disable_feed_rss') == "yes" )
	add_action('do_feed_rss', 'feedme_disable_feeds', 1);

// feed/rss2/ url
if ( get_option('disable_feed_rss2') == "yes" )
	add_action('do_feed_rss2', 'feedme_disable_feeds', 1);

// feed/atom/ url
if ( get_option('disable_feed_atom') == "yes" )
	add_action('do_feed_atom', 'feedme_disable_feeds', 1);



register_activation_hook(__FILE__,'set_feedme_options');
register_deactivation_hook(__FILE__,'unset_feedme_options');

?>