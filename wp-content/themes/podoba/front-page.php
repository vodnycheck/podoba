<?php
/**
 * The front page template file
 *
 * If the user has selected a static page for their homepage, this is what will
 * appear.
 * Learn more: https://codex.wordpress.org/Template_Hierarchy
 *
 * @package WordPress
 * @subpackage Twenty_Seventeen
 * @since 1.0
 * @version 1.0
 */
$imageDir = '/wp-content/themes/podoba/assets/images/';
get_header(); ?>

<div id="primary" class="content-area">
	<main id="main" class="site-main" role="main">

		<?php // Show the selected frontpage content.
		if ( have_posts() ) :
			while ( have_posts() ) : the_post();
				get_template_part( 'template-parts/page/content', 'front-page' );
			endwhile;
		else : // I'm not sure it's possible to have no posts when this page is shown, but WTH.
			get_template_part( 'template-parts/post/content', 'none' );
		endif; ?>

		<?php
		// Get each of our panels and show the post data.
		if ( 0 !== twentyseventeen_panel_count() || is_customize_preview() ) : // If we have pages to show.

			/**
			 * Filter number of front page sections in Twenty Seventeen.
			 *
			 * @since Twenty Seventeen 1.0
			 *
			 * @param int $num_sections Number of front page sections.
			 */
			$num_sections = apply_filters( 'twentyseventeen_front_page_sections', 4 );
			global $twentyseventeencounter;

			// Create a setting and control for each of the sections available in the theme.
			for ( $i = 1; $i < ( 1 + $num_sections ); $i++ ) {
				$twentyseventeencounter = $i;
				twentyseventeen_front_page_section( null, $i );
			}

	endif; // The if ( 0 !== twentyseventeen_panel_count() ) ends here. ?>
	<?php if ( has_post_thumbnail() ) :
		$thumbnail = wp_get_attachment_image_src( get_post_thumbnail_id( $post->ID ), 'twentyseventeen-featured-image' );

		// Calculate aspect ratio: h / w * 100%.
		$ratio = $thumbnail[2] / $thumbnail[1] * 100;
		?>
	<?php endif; ?>
	<header class="header-bg w-100 d-flex flex-column justify-content-between" style="background-image:url(<?php echo esc_url( $thumbnail[0] ); ?>)">
		<div class="container navigation d-flex">
			<div class="nav-menu d-flex" id="scroll-menu">
				<div class="logo"></div>
			</div>
		</div>
		<div class="container">
			<div class="tag-text">
				<h3>Family constellation sets</h3>
			</div>
		</div>
	</header>

		<main class="main">
			<section class="info-bg our-info" id="ourInfo">
				<div class="container container-info">
					<div class="row justify-content-center">
						<div class="info col-12" id="section1">
							<?php echo get_field('description'); ?>
						</div>
					</div>
				</div>
			</section>

			<div class="albums">
				<div class="albums__all">
					<?php echo do_shortcode( '[wppa type="covers"][/wppa]' ); ?>
				</div>
				<div class="albums__list">
					<?php for ($i = 1; $i <= 10; $i++) {
						echo do_shortcode( '[wppa type="thumbs" album="' . $i . '"][/wppa]' );
					}; ?>
				</div>
			</div>

			<section class="our-works" id="ourWorks">
				<div class="sets container">
					<h2 id="sector2" class="header-h2">Our Works</h2>
					<div class="row">
					<div class="col-12">
						<div class="row js-our-works-wrap">

						</div>
					</div>
					</div>
				</div>
			</section>

			<div class="container tagline">
				<div class="tag-text">
					<h2 class="header-h2">
					Contact us<span class="our-mail__type">email: <a href="mailto:<?php echo get_field('email'); ?>"><?php echo get_field('email'); ?></a></span>
					</h2>
				</div>
				<div class="social-networks justify-content-around">
					<div id="facebook">
						<a href="https://www.facebook.com/WePodoba"><img src="<?php echo $imageDir;?>socialNetworks/if_facebook_395306.png" alt=""></a>
					</div>
					<div id="pinterest">
						<a href="https://www.pinterest.ru/PODOBAshop/"><img src="<?php echo $imageDir;?>socialNetworks/if_pinterest_395377.png" alt=""></a>
					</div>
					<div id="instagram">
						<a href="https://www.instagram.com/podobashop"><img src="<?php echo $imageDir;?>socialNetworks/if_instagram_395340.png" alt=""></a>
					</div>
					<div id="etsy">
						<a href="https://www.etsy.com/shop/PODOBAshop"><img src="<?php echo $imageDir;?>socialNetworks/etsy.png" alt=""></a>
					</div>
				</div>
			</div>

			<!-- <section class="contacts-info" id="contactsInfo">
				<div class="container">
					<div class="row justify-content-sm-center justify-content-md-center justify-content-lg-around justify-content-xl-around">
						<div class="contacts-col contact-us col-12 col-lg-7">
							<h3 id="sector4">Send message</h3>
							<div class="text-left">
								<?php echo do_shortcode( '[contact-form-7 id="71" title="Contact"]' ); ?>
							</div>
						</div>
						<div class="contacts-col col-12 col-lg-5">
								<div class="our-contacts">
									<h3>Contacts</h3>
									<div class="our-mail our-info">
										<p><span class="our-mail__type">email: </span><a href="mailto:<?php echo get_field('email'); ?>"><?php echo get_field('email'); ?></a></p>
									</div>
									<div class="our-numbers our-info">
										<p><span class="our-mail__type">tel: </span><a href="tel:<?php echo get_field('tel_1'); ?>"><?php echo get_field('tel_1'); ?></a> <?php echo get_field('name_1'); ?></p>
										<p><span class="our-mail__type">tel: </span><a href="tel:<?php echo get_field('tel_2'); ?>"><?php echo get_field('tel_2'); ?></a> <?php echo get_field('name_2'); ?></p>
									</div>
								</div>
							<div class="social-networks row justify-content-around">
								<div id="facebook">
									<a href=""><img src="<?php echo $imageDir;?>socialNetworks/if_facebook_395306.png" alt=""></a>
								</div>
								<div id="pinterest">
									<a href=""><img src="<?php echo $imageDir;?>socialNetworks/if_pinterest_395377.png" alt=""></a>
								</div>
								<div id="instagram">
									<a href=""><img src="<?php echo $imageDir;?>socialNetworks/if_instagram_395340.png" alt=""></a>
								</div>
								<div id="vk">
									<a href=""><img src="<?php echo $imageDir;?>socialNetworks/if_vkontakte_vk_395425.png" alt=""></a>
								</div>
							</div>
						</div>
					</div>
				</div>
			</section> -->
		</main>
	</main><!-- #main -->
</div><!-- #primary -->

<?php get_footer();
