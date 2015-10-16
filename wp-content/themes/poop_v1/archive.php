<?php
/**
 * @package WordPress
 * @subpackage Default_Theme
 */

get_header(); ?>



<?php include ('sidebar-1.php'); ?>

	<div id="content" class="narrowcolumn" role="main">

<!-- Header for results -->
<?php $post = $posts[0]; // Hack. Set $post so that the_date() works. ?>
<?php /* If this is a category archive */ if (is_category()) { ?>
<h2 class="pagetitle">Archive for the &#8216;<?php single_cat_title(); ?>&#8217; Category</h2>
<?php /* If this is a tag archive */ } elseif( is_tag() ) { ?>
<h2 class="pagetitle">Posts Tagged &#8216;<?php single_tag_title(); ?>&#8217;</h2>
<?php /* If this is a daily archive */ } elseif (is_day()) { ?>
<h2 class="pagetitle">Archive for <?php the_time('F jS, Y'); ?></h2>
<?php /* If this is a monthly archive */ } elseif (is_month()) { ?>
<h2 class="pagetitle">Archive for <?php the_time('F, Y'); ?></h2>
<?php /* If this is a yearly archive */ } elseif (is_year()) { ?>
<h2 class="pagetitle">Archive for <?php the_time('Y'); ?></h2>
<?php /* If this is an author archive */ } elseif (is_author()) { ?>
<h2 class="pagetitle">Author Archive</h2>
<?php /* If this is a paged archive */ } elseif (isset($_GET['paged']) && !empty($_GET['paged'])) { ?>
<h2 class="pagetitle">Blog Archives</h2>
<?php } ?>

	<?php if (have_posts()) : ?>

		<?php while (have_posts()) : the_post(); ?>

			<div <?php post_class() ?> id="post-<?php the_ID(); ?>">
				<div class="inner">
					<div class="postinfo">			
						<h2>Title: <a href="<?php the_permalink() ?>" rel="bookmark" title="Permanent Link to <?php the_title_attribute(); ?>"><?php the_title(); ?></a></h2>
						<?php /* <small><?php the_time('F jS, Y') ?> <!-- by <?php the_author() ?> --></small> */ ?>
						<?php the_tags('<p class="postmetadata"><strong>Tags:</strong><span class="weight_normal"> ', ', ', '</span></p>'); ?>
						<?php the_content('Read more...'); ?>
						<!-- Grab the image that was uploaded via TDO Mini Form -->				
					<!-- Create the anchor on the DIV using rel for Shadowbox plugin -->				
					<!-- Render the DIV with the background as the image defined above as $i -->
						<p><?php comments_number('No Comments','1 Commment','% Comments');†?>. <a href="<?php the_permalink() ?>" rel="bookmark" title="Permanent Link to <?php the_title_attribute(); ?>">Post a Comment and Share</a></p>
					</div>				
					<?php
							$args = array(
						'post_type' => 'attachment',
						'post_mime_type' => 'image',
						'post_parent' => $post->ID
						);
						$images = get_posts( $args );
						foreach($images as $image):
						$i = wp_get_attachment_url($image->ID);
						endforeach;
						if($i):
					?>
					<div class="postpoop">
						<img class="poopimg" src="<?php echo $i; ?>" alt="PoopLog" />
						<a class="enlarge" href="<?php echo $i; ?>" rel="shadowbox;player=img;"><span></span></a>			
					</div>

					<?php endif; ?>
				</div>
			</div>
		<?php endwhile; ?>

		<div class="navigation">
			<div class="left"><?php next_posts_link('Previous') ?></div>
			<div class="right"><?php previous_posts_link('Next') ?></div>
		</div>

	<?php else : ?>

		<h2 class="center">Not Found</h2>
		<p class="center">Sorry, but you are looking for something that isn't here.</p>
		<?php get_search_form(); ?>

	<?php endif; ?>

	</div>

<?php include ('sidebar-2.php'); ?>



<?php get_footer(); ?>
