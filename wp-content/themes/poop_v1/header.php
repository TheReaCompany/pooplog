<?php
/**
 * @package WordPress
 * @subpackage Default_Theme
 */
?>
<?php $themeurl = get_bloginfo('template_url'); ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" <?php language_attributes(); ?>>

<head profile="http://gmpg.org/xfn/11">

<link rel="icon" href="http://s3.amazonaws.com/pooplog/images/favicon.ico" type="image/x-icon">
<link rel="shortcut icon" href="http://s3.amazonaws.com/pooplog/images/favicon.ico" type="image/x-icon">
<link rel="apple-touch-icon" href="http://s3.amazonaws.com/pooplog/images/pooplog_apple_icon.png"/>

<meta http-equiv="Content-Type" content="<?php bloginfo('html_type'); ?>; charset=<?php bloginfo('charset'); ?>" />
<meta name="description" content="" />
<link rel="image_src" href="http://s3.amazonaws.com/pooplog/images/pooplog_facebook_icon.jpg">


<title><?php wp_title('&laquo;', true, 'right'); ?> <?php bloginfo('name'); ?></title>

<link rel="stylesheet" href="<?php bloginfo('stylesheet_url'); ?>" type="text/css" media="screen" />
<!--[if lt IE 7]>
<link rel="stylesheet" href="<?php bloginfo('stylesheet_directory'); ?>/style_lt_ie7.css" type="text/css" media="screen" />
<![endif]-->
<link rel="pingback" href="<?php bloginfo('pingback_url'); ?>" />

<!--
Removed during Poop template construction v1
<style type="text/css" media="screen">

<?php
// Checks to see whether it needs a sidebar or not
if ( empty($withcomments) && !is_single() ) {
?>
	#page { background: url("<?php bloginfo('stylesheet_directory'); ?>/images/kubrickbg-<?php bloginfo('text_direction'); ?>.jpg") repeat-y top; border: none; }
<?php } else { // No sidebar ?>
	#page { background: url("<?php bloginfo('stylesheet_directory'); ?>/images/kubrickbgwide.jpg") repeat-y top; border: none; }
<?php } ?>
</style>
-->

<?php if ( is_singular() ) wp_enqueue_script( 'comment-reply' ); ?>
<?php wp_enqueue_script( 'scripts', $themeurl.'/scripts.js', array('jquery'), FALSE, TRUE ); ?>


<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>

<script src="http://static.ak.connect.facebook.com/js/api_lib/v0.4/FeatureLoader.js.php/en_US" type="text/javascript"></script><script type="text/javascript">FB.init("b086bc5b0c64215906e5d39b89912ca4");</script>
 
<div id="page">

<div id="header" role="banner">
	<div id="headerimg">
		<!-- <h1><a href="<?php echo get_option('home'); ?>/"><?php bloginfo('name'); ?></a></h1> -->
		<!-- <div class="description"><?php bloginfo('description'); ?> </div> -->
	<a href="http://www.pooplog.org/" id="pllogo" title="PoopLog">PoopLog</a>
	</div>
</div>
<hr />
