<?php
/**
 * @package WordPress
 * @subpackage Default_Theme
 */

get_header(); ?>



<?php include ('sidebar-1.php'); ?>

	<div id="content" class="narrowcolumn" role="main">

	<?php if (have_posts()) : ?>

		<?php while (have_posts()) : the_post(); ?>

			<div <?php post_class() ?> id="post-<?php the_ID(); ?>">
				<div class="inner">
					<div class="postinfo">			
						<h2><a href="<?php the_permalink() ?>" rel="bookmark" title="Permanent Link to <?php the_title_attribute(); ?>"><?php the_title(); ?></a></h2>
						<?php /* <small><?php the_time('F jS, Y') ?> <!-- by <?php the_author() ?> --></small> */ ?>
						<?php the_tags('<p class="postmetadata">Tags:<span class="weight_normal"> ', ', ', '</span></p>'); ?>
						<?php the_content('Read more...'); ?>
						<!-- Grab the image that was uploaded via TDO Mini Form -->				
					<!-- Create the anchor on the DIV using rel for Shadowbox plugin -->				
					<!-- Render the DIV with the background as the image defined above as $i -->
						<p><?php comments_number('no comments','1 commment','% comments'); ?>.</p>
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
