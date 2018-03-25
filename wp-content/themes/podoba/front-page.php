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


		<nav class="sidenav" data-sidenav data-sidenav-toggle="#sidenav-toggle" id="toggle-menu">
			<div class="sidenav-header">
				<a href="#section1">ГЛАВНАЯ</a>
			</div>
			<div class="sidenav-header">
				<a href="#sector2">НАШИ РАБОТЫ</a>
			</div>
			<div class="sidenav-header">
				<a href="#sector3">ГАЛЕРЕЯ</a>
			</div>
			<div class="sidenav-header">
				<a href="#sector4">КОНТАКТЫ</a>
			</div>
		</nav>

		<header class="header-bg w-100 text-center d-flex flex-column
		justify-content-between">
			<div class="container navigation d-flex flex-column">
				<div class="nav-menu d-flex flex-column" id="scroll-menu">
					<a href="javascript:;" class="toggle" id="sidenav-toggle">
						<div class="sideBar"></div>
					</a>
					<ul>
						<li><a href="#section1">MAIN</a></li>
						<li>-</li>
						<li><a href="#sector2">OUR WORKS</a></li>
						<li>-</li>
						<li><a href="#sector3">GALERY</a></li>
						<li>-</li>
						<li><a href="#sector4">CONTACTS</a></li>
					</ul>
					<div class="logo"></div>
				</div>
			</div>	
			<div class="tagline" id="scroll">
				<div class="tag-text">
					<h3>Family constellation sets</h3>
				</div>
				<a href="#section1"><div class="arrow-down"></div></a>
			</div>
		</header>
		<main class="main text-center">
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
				<div class="sets container text-center">
					<h2 id="sector2">НАШИ РАБОТЫ</h2>
					<div class="row">
					<div class="col-12">
						<div class="row js-our-works-wrap">

						</div>
					</div>
					</div>
				</div>
			</section>

			<section class="slide-gallery" id="slideGallery">
				<div class="gallery-section container">
					<h2 id="sector3">ГАЛЕРЕЯ</h2>
					<div style="position: relative;     height: 660px;">
						<!-- Slider main container -->
						<div class="swiper-container">
							<!-- Additional required wrapper -->
							<div class="swiper-wrapper">
								<!-- Slides -->
								<?php
									//Get the images ids from the post_metadata
									$images = acf_photo_gallery('gallery', $post->ID);
									//Check if return array has anything in it
									if( count($images) ):
										//Cool, we got some data so now let's loop over it
										foreach($images as $image):
											$id = $image['id']; // The attachment id of the media
											$title = $image['title']; //The title
											$caption= $image['caption']; //The caption
											$full_image_url= $image['full_image_url']; //Full size image url
											//$full_image_url = acf_photo_gallery_resize_image($full_image_url, 262, 160); //Resized size to 262px width by 160px height image url
											$thumbnail_image_url= acf_photo_gallery_resize_image($full_image_url, 290, 200);
											$url= $image['url']; //Goto any link when clicked
											$target= $image['target']; //Open normal or new tab
											$alt = get_field('photo_gallery_alt', $id); //Get the alt which is a extra field (See below how to add extra fields)
											$class = get_field('photo_gallery_class', $id); //Get the class which is a extra field (See below how to add extra fields)
								?>
								<a class="swiper-slide" data-fancybox="gallery" href="<?php echo $full_image_url; ?>">
									<span class="swiper-slide-img-wrap">
										<img src="<?php echo $thumbnail_image_url; ?>" alt="<?php echo $title; ?>" title="<?php echo $title; ?>">
									</span>
								</a>
								<?php endforeach; endif; ?>
							</div>
							<!-- If we need navigation buttons -->
							<div class="swiper-button-prev"></div>
							<div class="swiper-button-next"></div>
						 
							<!-- If we need scrollbar -->
							<div class="swiper-scrollbar"></div>
						</div>
					</div>
				</div>
			</section>

			<section class="contacts-info" id="contactsInfo">
				<div class="container">
					<div class="row justify-content-sm-center justify-content-md-center justify-content-lg-around justify-content-xl-around">
						<div class="contacts-col contact-us col-12 col-lg-7">
							<h3 id="sector4">СВЯЗАТЬСЯ С НАМИ</h3>
							<div class="text-left">
								<?php echo do_shortcode( '[contact-form-7 id="71" title="Contact"]' ); ?>
							</div>
						</div>
						<div class="contacts-col our-contacts col-12 col-lg-5">
							<h3>КОНТАКТЫ</h3>
								<div class="our-mail our-info">
									<p>email: <a href="mailto:<?php echo get_field('email'); ?>"><?php echo get_field('email'); ?></a></p>
								</div>
								<div class="our-numbers our-info">
									<p>тел. <a href="tel:<?php echo get_field('tel_1'); ?>"><?php echo get_field('tel_1'); ?></a> <?php echo get_field('name_1'); ?></p>
									<p>тел. <a href="tel:<?php echo get_field('tel_2'); ?>"><?php echo get_field('tel_2'); ?></a> <?php echo get_field('name_2'); ?></p>
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
			</section>
		</main>
		<footer class="footer">
					<ul id="bottom-menu">
						<li><a href="#section1">ГЛАВНАЯ</a></li>
						<li class="dash">-</li>
						<li><a href="#sector2">НАШИ РАБОТЫ</a></li>
						<li class="dash">-</li>
						<li><a href="#sector3">ГАЛЕРЕЯ</a></li>
						<li class="dash">-</li>
						<li><a href="#sector4">КОНТАКТЫ</a></li>
					</ul>
		</footer>
	</main><!-- #main -->
</div><!-- #primary -->

<?php get_footer();
