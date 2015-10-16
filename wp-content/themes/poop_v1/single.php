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
						<h2>Title: <a href="<?php the_permalink() ?>" rel="bookmark" title="Permanent Link to <?php the_title_attribute(); ?>"><?php the_title(); ?></a></h2>
						<?php /* <small><?php the_time('F jS, Y') ?> <!-- by <?php the_author() ?> --></small> */ ?>
						<?php the_tags('<p class="postmetadata"><strong>Tags:</strong><span class="weight_normal"> ', ', ', '</span></p>'); ?>
						<?php the_content('Read more...'); ?>
						<!-- Grab the image that was uploaded via TDO Mini Form -->				
					<!-- Create the anchor on the DIV using rel for Shadowbox plugin -->				
					<!-- Render the DIV with the background as the image defined above as $i -->
						<p><?php comments_number('No Comments','1 Commment','% Comments'); ?>.</p>
						<fb:share-button class="url" href="http://www.pooplog.org" type="button_count"></fb:share-button>
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
			<div id="comment_section">
					
				<p class="postmetadata alt">
					<small>
						This entry was posted
						<?php /* This is commented, because it requires a little adjusting sometimes.
							You'll need to download this plugin, and follow the instructions:
							http://binarybonsai.com/wordpress/time-since/ */
							/* $entry_datetime = abs(strtotime($post->post_date) - (60*120)); echo time_since($entry_datetime); echo ' ago'; */ ?>
						on <?php the_time('l, F jS, Y') ?> at <?php the_time() ?>
						and is filed under <?php the_category(', ') ?>.
						You can follow any responses to this entry through the <?php post_comments_feed_link('RSS 2.0'); ?> feed.

						<?php if ( comments_open() && pings_open() ) {
							// Both Comments and Pings are open ?>
							You can <a href="#respond">leave a response</a>, or <a href="<?php trackback_url(); ?>" rel="trackback">trackback</a> from your own site.

						<?php } elseif ( !comments_open() && pings_open() ) {
							// Only Pings are Open ?>
							Responses are currently closed, but you can <a href="<?php trackback_url(); ?> " rel="trackback">trackback</a> from your own site.

						<?php } elseif ( comments_open() && !pings_open() ) {
							// Comments are open, Pings are not ?>
							You can skip to the end and leave a response. Pinging is currently not allowed.

						<?php } elseif ( !comments_open() && !pings_open() ) {
							// Neither Comments, nor Pings are open ?>
							Both comments and pings are currently closed.

						<?php } edit_post_link('Edit this entry','','.'); ?>

					</small>
				</p>
				<?php comments_template(); ?>
			</div>
		<?php endwhile; ?>
		<div class="navigation">
			<div class="left"><?php previous_post_link('%link') ?></div>
			<div class="right"><?php next_post_link('%link') ?></div>
		</div>

<?php endif; ?>

	</div>

<?php include ('sidebar-2.php'); ?>



<?php get_footer(); ?>
