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
				<a class="js-action--smooth-scroll" href="#section1">HOME</a>
			</div>
			<div class="sidenav-header">
				<a class="js-action--smooth-scroll" href="#sector2">OUR WORKS</a>
			</div>
			<div class="sidenav-header">
				<a class="js-action--smooth-scroll" href="#sector4">CONTACTS</a>
			</div>
		</nav>

		<header class="header-bg w-100 text-center d-flex flex-column
		justify-content-between">
			<div class="container navigation d-flex">
				<div class="nav-menu d-flex" id="scroll-menu">
					<a href="javascript:;" class="toggle" id="sidenav-toggle">
						<div class="sideBar"></div>
					</a>
					<ul>
						<li><a class="js-action--smooth-scroll" href="#section1">HOME</a></li>
						<li><a class="js-action--smooth-scroll" href="#sector2">OUR WORKS</a></li>
						<li><a class="js-action--smooth-scroll" href="#sector4">CONTACTS</a></li>
					</ul>
					<div class="logo"></div>
				</div>
			</div>
			<div class="tagline" id="scroll">
				<div class="tag-text">
					<h3>Family constellation sets</h3>
				</div>
				<a class="js-action--smooth-scroll" href="#section1"><div class="arrow-down"></div></a>
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
					<h2 id="sector2">Our Works</h2>
					<div class="row">
					<div class="col-12">
						<div class="row js-our-works-wrap">

						</div>
					</div>
					</div>
				</div>
			</section>

			<section class="contacts-info" id="contactsInfo">
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
			</section>
		</main>
		<footer class="footer">
					<ul id="bottom-menu">
						<li><a class="js-action--smooth-scroll" href="#section1">HOME</a></li>
						<li><a class="js-action--smooth-scroll" href="#sector2">OUR WORKS</a></li>
						<li><a class="js-action--smooth-scroll" href="#sector4">CONTACTS</a></li>
					</ul>
		</footer>
	</main><!-- #main -->
</div><!-- #primary -->

<?php get_footer();
