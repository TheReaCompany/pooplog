<?php
/**
 * @package WordPress
 * @subpackage Default_Theme
 */

get_header(); ?>


<?php include ('sidebar-1.php'); ?>

	<div id="content" class="narrowcolumn" role="main">

			<div id="page_content_404">
		
				<div class="entry">
				<h2 class="center">Not Found</h2>
			<p class="center">Sorry, but you are looking for something that isn't here.</p>
			<?php get_search_form(); ?>

				</div>
			</div>			
			
	<?php edit_post_link('Edit this entry.', '<p>', '</p>'); ?>
		<div class="navigation">
		</div>
	
	<?php // comments_template(); ?>
	
	</div>

<?php include ('sidebar-2.php'); ?>

<?php get_footer(); ?>


