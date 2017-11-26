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
						<li><a href="#section1">ГЛАВНАЯ</a></li>
						<li>-</li>
						<li><a href="#sector2">НАШИ РАБОТЫ</a></li>
						<li>-</li>
						<li><a href="#sector3">ГАЛЕРЕЯ</a></li>
						<li>-</li>
						<li><a href="#sector4">КОНТАКТЫ</a></li>
					</ul>
					<div class="logo"></div>
				</div>
			</div>	
			<div class="tagline" id="scroll">
				<div class="tag-text">
					<h3>фигурки для системно-семейных расстановок</h3>
				</div>
				<a href="#section1"><div class="arrow-down"></div></a>
			</div>
		</header>
		<main class="main text-center">
			<section class="info-bg our-info" id="ourInfo">
				<div class="container container-info">
					<div class="row justify-content-center">
						<div class="info col-sm-12 col-md-12 col-lg-12 col-xl-10" id="section1">
							<p>Расстановка на фигурках,  которые символизируют  конкретных людей,  позволяет войти в резонанс с системой семьи клиента, увидеть пространственную проекцию взаимоотношений всех членов расставленной системы. Использование данного метода помогает посмотреть на ситуацию со стороны, рассмотреть ее объективно при этом расстановщик и клиент получают возможность ощутить связи, чувства и эмоции которые существуют между членами системы клиента. Мы хотим создать материал, с помощью которого Вам будет легко работать.  Расстановка на фигурках,  которые символизируют  конкретных людей,  позволяет войти в резонанс с системой семьи клиента, увидеть пространственную проекцию взаимоотношений всех членов расставленной системы. Использование данного метода помогает посмотреть на ситуацию со стороны, рассмотреть ее объективно при этом расстановщик и клиент получают возможность ощутить связи, чувства и эмоции.</p>
							<p>Мы хотим создать материал, с помощью которого Вам будет легко работать.  Расстановка на фигурках,  которые символизируют  конкретных людей,  позволяет войти в резонанс с системой семьи клиента, увидеть пространственную проекцию взаимоотношений всех членов расставленной системы. Использование данного метода помогает посмотреть на ситуацию со стороны, рассмотреть ее объективно при этом расстановщик и клиент получают возможность ощутить связи, чувства и эмоции которые существуют между членами системы клиента. Мы хотим создать материал, с помощью которого Вам будет легко работать.</p>
						</div>
					</div>
				</div>
			</section>

			<section class="our-works" id="ourWorks">
				<div class="sets container text-center">
					<h2 id="sector2">НАШИ РАБОТЫ</h2>
					<div class="row justify-content-center">
					<div class="col-lg-12 col-xl-10">


						<div class="row justify-content-between align-items-baseline">
							<div class="col-md-12 col-lg-6 set row justify-content-center">
								<div class="work-examples work-examples-big col-12" id="bigYellow" style="background-image: url(<?php echo get_field('image_1'); ?>);"></div>
								<div class="work-examples-text col-11"><p><strong><?php echo get_field('header_1'); ?></strong><?php echo get_field('description_1'); ?></p></div>
							</div>
							<div class="col-md-12 col-lg-6 set row justify-content-center">
								<div class="work-examples col-12" id="smallYellow" style="background-image: url(<?php echo get_field('image_2'); ?>);"></div>
								<div class="work-examples-text col-11"><p><strong><?php echo get_field('header_2'); ?></strong><?php echo get_field('description_2'); ?></p></div>
							</div>
							<div class="col-md-12 col-lg-6 set row justify-content-center">
								<div class="work-examples col-12" id="smallColor" style="background-image: url(<?php echo get_field('image_3'); ?>);"></div>
								<div class="work-examples-text col-11"><p><strong><?php echo get_field('header_3'); ?></strong><?php echo get_field('description_3'); ?></p></div>
							</div>
							<div class="col-md-12 col-lg-6 set row justify-content-center">
								<div class="work-examples work-examples-big col-12" id="bigColor" style="background-image: url(<?php echo get_field('image_4'); ?>);"></div>
								<div class="work-examples-text col-11"><p><strong><?php echo get_field('header_4'); ?></strong><?php echo get_field('description_4'); ?></p></div>
							</div>
						</div>
					
					</div>
					</div>
				</div>
			</section>

			<section class="slide-gallery" id="slideGallery">
				<div class="gallery-section container">
					<h2 id="sector3">ГАЛЕРЕЯ</h2>
					<div class="row d-flex justify-content-center align-items-center">
						<div class="slider-buttons col-1 slideGallery">
							<a name="-1" class="slide-change"></a>
						</div>
						<div class="container my-slides fad col-10">
							<a data-fancybox="gallery" href="<?php echo $imageDir;?>big/810.jpg">
	    						<img src="<?php echo $imageDir;?>small/810.jpg">
							</a>
							<a data-fancybox="gallery" href="<?php echo $imageDir;?>big/798.jpg">
	    						<img src="<?php echo $imageDir;?>small/798.jpg">
							</a>
							<a data-fancybox="gallery" href="<?php echo $imageDir;?>big/829.jpg">
	    						<img src="<?php echo $imageDir;?>small/829.jpg">
							</a>
							<a data-fancybox="gallery"  href="<?php echo $imageDir;?>big/856.jpg">
	    						<img src="<?php echo $imageDir;?>small/856.jpg">
							</a>
							<a data-fancybox="gallery" href="<?php echo $imageDir;?>big/789.jpg">
	    						<img src="<?php echo $imageDir;?>small/789.jpg">
							</a>
							<a data-fancybox="gallery" href="<?php echo $imageDir;?>big/771.jpg">
	    						<img src="<?php echo $imageDir;?>small/771.jpg">
							</a>
							<a data-fancybox="gallery" href="<?php echo $imageDir;?>big/282.jpg">
	    						<img src="<?php echo $imageDir;?>small/282.jpg">
							</a>
							<a data-fancybox="gallery" href="<?php echo $imageDir;?>big/910.jpg">
	    						<img src="<?php echo $imageDir;?>small/910.jpg">
							</a>
							<a  data-fancybox="gallery" href="<?php echo $imageDir;?>big/290.jpg">
	    						<img src="<?php echo $imageDir;?>small/290.jpg">
							</a>
								
							<!--  -->
							<a data-fancybox="gallery" href="<?php echo $imageDir;?>big/856.jpg">
	    						<img src="<?php echo $imageDir;?>small/856.jpg">
							</a>
							<a data-fancybox="gallery" href="<?php echo $imageDir;?>big/789.jpg">
	    						<img src="<?php echo $imageDir;?>small/789.jpg">
							</a>
							<a data-fancybox="gallery" href="<?php echo $imageDir;?>big/771.jpg">
	    						<img src="<?php echo $imageDir;?>small/771.jpg">
							</a>
							<a data-fancybox="gallery" href="<?php echo $imageDir;?>big/282.jpg">
	    						<img src="<?php echo $imageDir;?>small/282.jpg">
							</a>
							<a data-fancybox="gallery" href="<?php echo $imageDir;?>big/910.jpg">
	    						<img src="<?php echo $imageDir;?>small/910.jpg">
							</a>
							<a data-fancybox="gallery" href="<?php echo $imageDir;?>big/290.jpg">
	    						<img src="<?php echo $imageDir;?>small/290.jpg">
							</a>
							<a data-fancybox="gallery" href="<?php echo $imageDir;?>big/810.jpg">
	    						<img src="<?php echo $imageDir;?>small/810.jpg">
							</a>
							<a data-fancybox="gallery" href="<?php echo $imageDir;?>big/789.jpg">
	    						<img src="<?php echo $imageDir;?>small/789.jpg">
							</a>
							<a data-fancybox="gallery" href="<?php echo $imageDir;?>big/829.jpg">
	    						<img src="<?php echo $imageDir;?>small/829.jpg">
							</a>
							<!--  -->
						</div>
						<div class="slider-buttons col-1 slideGallery">
							<a name="1" class="slide-change"></a>
						</div>
					</div>
				</div>
			</section>

			<section class="contacts-info" id="contactsInfo">
				<div class="container">
					<div class="row justify-content-sm-center justify-content-md-center justify-content-lg-around justify-content-xl-around">
						<div class="contacts-col contact-us col-sm-12 col-md-10 col-lg-7 col-xl-6">
							<h3 id="sector4">СВЯЗАТЬСЯ С НАМИ</h3>
							<div class="text-left">
								<div class="user-information user-info">
									<input type="text" placeholder="ВАШЕ ИМЯ">
									<input type="text" placeholder="ВАШ ЭЛЕКТРОННЫЙ АДРЕС">
								</div>
								<div class="user-message user-info">
									<textarea name="" id="" placeholder="СООБЩЕНИЕ"></textarea>
								</div>
								<div class="user-send user-info">
									<input type="button" class="button" value="ОТПРАВИТЬ">
								</div>
							</div>
						</div>
						<div class="contacts-col our-contacts col-xs-7 col-sm-7 col-md-7 col-lg-4 col-xl-4">
							<h3>КОНТАКТЫ</h3>
								<div class="our-mail our-info">
									<p>email: <a href="mailto:podoba.ua@gmail.com">podoba.ua@gmail.com</a></p>
								</div>
								<div class="our-numbers our-info">
									<p>тел. <a href="">+380 96 107 16 62</a> Ирина</p>
									<p>тел. <a href="">+380 95 528 96 30</a> Сергей</p>
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
