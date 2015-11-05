<?php

if ( ! defined( 'ABSPATH' ) ) exit();

/**
 * Parse shortcodes in a string removing extra <p> and <br> tags.
 *
 * @param string $content
 * @return string
 */
function educator_do_shortcodes( $content ) {
	return do_shortcode( shortcode_unautop( preg_replace( '#(^<\/p>|^<br\s?\/?>|<p>$)#', '', $content ) ) );
}

if ( ! function_exists( 'educator_title' ) ) :
/**
 * Shortcode: educator_title.
 *
 * @param array $atts
 * @param string $content
 * @return string
 */
function educator_title( $atts, $content = null ) {
	$atts = shortcode_atts( array(
		'title' => '',
	), $atts, 'section_title' );

	$output = '<div class="title1 clearfix"><div class="text"><h2>' . esc_html( $atts['title'] ) . '</h2></div> ';

	if ( ! empty( $content ) ) {
		$output .= '<div class="sub-title">' . $content . '</div>';
	}

	$output .= '</div>';

	return $output;
}
endif;
add_shortcode( 'section_title', 'educator_title' );

if ( ! function_exists( 'educator_courses_shortcode' ) ) :
/**
 * Shortcode: educator_courses.
 *
 * @param array $atts
 * @param string $content
 * @return string
 */
function educator_courses_shortcode( $atts, $content = null ) {
	$atts = shortcode_atts( array(
		'show_price' => 1,
		'ids'        => '',
		'number'     => 15,
		'categories' => null,
	), $atts );

	$output = '<div class="courses-carousel owl-carousel">';

	$params = array(
		'post_type'      => 'ib_educator_course',
		'orderby'        => 'menu_order',
		'posts_per_page' => intval( $atts['number'] ),
	);

	if ( $atts['ids'] ) {
		$ids = explode( ' ', $atts['ids'] );
		$params['post__in'] = array();

		foreach ( $ids as $id ) {
			$params['post__in'][] = intval( $id );
		}

		$params['posts_per_page'] = -1;
		$params['orderby'] = 'post__in';
	}

	if ( $atts['categories'] ) {
		$categories = explode( ' ', $atts['categories'] );

		foreach ( $categories as $key => $term_id ) {
			$categories[ $key ] = intval( $term_id );
		}

		$params['tax_query'] = array(
			array( 'taxonomy' => 'ib_educator_category', 'field' => 'term_id', 'terms' => $categories ),
		);
	}
	
	$query = new WP_Query( $params );

	if ( $query->have_posts() ) {
		$course_id = 0;

		while ( $query->have_posts() ) {
			$query->the_post();
			$course_id = get_the_ID();
			$output .= '<article class="' . esc_attr( implode( ' ', get_post_class( 'post-grid' ) ) ) . '">';

			if ( has_post_thumbnail() ) {
				$output .= '<div class="post-thumb"><a href="' . esc_url( get_permalink() ) . '">' . get_the_post_thumbnail( $course_id, 'ib-educator-grid' ) . '</a></div>';
			}

			$output .= '<div class="post-body">';
			$output .= '<h2 class="entry-title"><a href="' . esc_url( get_permalink() ) . '">' . the_title( '', '', false ) . '</a></h2>';

			if ( 1 == $atts['show_price'] && 'closed' != ib_edu_registration( $course_id ) ) {
				$output .= '<div class="price">' . ib_edu_format_course_price( ib_edu_get_course_price( $course_id ) ) . '</div>';
			}

			ob_start();
			the_excerpt();
			$output .= '<div class="post-excerpt">' . ob_get_clean() . '</div>';
			$output .= '</div>';

			if ( function_exists( 'educator_course_meta' ) ) {
				$output .= '<footer class="post-meta">' . educator_course_meta( $course_id, array( 'num_lessons', 'difficulty' ) ) . educator_share( 'menu' ) . '</footer>';
			}

			$output .= '</article>';
		}

		wp_reset_postdata();
	}

	$output .= '</div>';

	return $output;
}
endif;
add_shortcode( 'educator_courses', 'educator_courses_shortcode' );

if ( ! function_exists( 'educator_posts_shortcode' ) ) :
function educator_posts_shortcode( $atts, $content = null ) {
	$atts = shortcode_atts( array(
		'ids'        => '',
		'number'     => 6,
		'categories' => null,
	), $atts );

	$params = array(
		'post_type'      => 'post',
		'posts_per_page' => intval( $atts['number'] ),
	);

	// Get posts by IDs.
	if ( $atts['ids'] ) {
		$ids = explode( ' ', $atts['ids'] );
		$params['post__in'] = array();

		foreach ( $ids as $id ) {
			if ( is_numeric( $id ) ) {
				$params['post__in'][] = $id;
			}
		}

		$params['posts_per_page'] = -1;
		$params['orderby'] = 'post__in';
		$params['ignore_sticky_posts'] = true;
	}

	// Categories.
	if ( $atts['categories'] ) {
		$categories = explode( ' ', $atts['categories'] );

		foreach ( $categories as $key => $term_id ) {
			$categories[ $key ] = intval( $term_id );
		}

		$params['tax_query'] = array(
			array( 'taxonomy' => 'category', 'field' => 'term_id', 'terms' => $categories ),
		);
	}

	$query = new WP_Query( $params );
	$output = '';

	if ( $query->have_posts() ) {
		$output .= '<div class="posts-carousel owl-carousel">';

		while ( $query->have_posts() ) {
			$query->the_post();
			$post_id = get_the_ID();
			$output .= '<article class="post-grid">';

			if ( has_post_thumbnail() ) {
				$output .= '<div class="post-thumb"><a href="' . esc_url( get_permalink() ) . '">' . get_the_post_thumbnail( $post_id, 'ib-educator-grid' ) . '</a></div>';
			}

			$output .= '<div class="post-body">';
			$output .= '<h2 class="entry-title"><a href="' . esc_url( get_permalink() ) . '">' . the_title( '', '', false ) . '</a></h2>';

			ob_start();
			the_excerpt();
			$output .= '<div class="post-excerpt">' . ob_get_clean() . '</div>';
			$output .= '</div>';

			if ( function_exists( 'educator_post_meta' ) ) {
				$output .= '<footer class="post-meta">' . educator_post_meta( array( 'date', 'comments' ) ) . educator_share( 'menu' ) . '</footer>';
			}

			$output .= '</article>';
		}

		wp_reset_postdata();

		$output .= '</div>';
	}

	return $output;
}
endif;
add_shortcode( 'educator_posts', 'educator_posts_shortcode' );

if ( ! function_exists( 'educator_lecturers_shortcode' ) ) :
/**
 * Shortcode: educator_lecturers.
 *
 * @param array $atts
 * @param string $content
 * @return string
 */
function educator_lecturers_shortcode( $atts, $content = null ) {
	$atts = shortcode_atts( array(
		'ids'    => '',
		'layout' => 'carousel',
	), $atts );
	$params = array(
		'role'    => 'lecturer',
		'orderby' => 'include',
	);

	if ( $atts['ids'] ) {
		$ids = explode( ' ', $atts['ids'] );
		$params['include'] = array();

		foreach ( $ids as $id ) {
			$params['include'][] = intval( $id );
		}
	}

	$user_query = new WP_User_Query( $params );
	$output = '';

	if ( ! empty( $user_query->results ) ) {
		if ( ! empty( $params['include'] ) ) {
			$users = array();

			foreach ( $user_query->results as $user ) {
				$order = array_search( $user->ID, $params['include'] );
				$users[ $order ] = $user;
			}

			ksort( $users );
		} else {
			$users = $user_query->results;
		}

		$pretty_permalinks = get_option( 'permalink_structure' );

		if ( 'carousel' == $atts['layout'] ) {
			$output .= '<div class="lecturers-carousel owl-carousel">';

			foreach ( $users as $user ) {
				$output .= '<div>';
				$user_photo = educator_get_user_profile_photo( $user->ID );
				$author_posts_url = function_exists( 'educator_theme_lecturer_link' ) ? educator_theme_lecturer_link( $user->ID ) : get_author_posts_url( $user->ID );

				if ( $user_photo ) {
					$output .= '<div class="author-photo"><a href="' . esc_url( $author_posts_url ) . '">' . $user_photo . '</a></div>';
				}
				
				$output .= '<h3>' . esc_html( $user->display_name ) . '</h3>';
				$output .= '<div class="author-description">' . esc_html( get_user_meta( $user->ID, '_educator_short_bio', true ) ) . '</div>';
				$output .= '<div class="author-links"><a href="' . esc_url( $author_posts_url ) . '">' . __( 'View Profile', 'ib-educator-theme' ) . ' <span class="fa fa-angle-double-right"></span></a></div>';
				$output .= '</div>';
			}

			$output .= '</div>';
		} else {
			$output .= '<div class="lecturers-grid clearfix">';
			$i = 0;
			$num_users = count( $users );
			$is_even = ( $num_users % 2 == 0 );

			foreach ( $users as $user ) {
				$class = ( $i % 2 ) ? 'column-2' : 'column-1';
				
				if ( $i < 2 ) $class .= ' first-row';
				if ( ( $is_even && $i > $num_users - 3 ) || ( ! $is_even && $i > $num_users - 2 ) ) $class .= ' last-row';

				$output .= '<div class="lecturer ' . $class . '">';
				$user_photo = educator_get_user_profile_photo( $user->ID );
				$author_posts_url = function_exists( 'educator_theme_lecturer_link' ) ? educator_theme_lecturer_link( $user->ID ) : get_author_posts_url( $user->ID );

				if ( $user_photo ) {
					$output .= '<div class="author-photo"><a href="' . esc_url( $author_posts_url ) . '">' . $user_photo . '</a></div>';
				}
				
				$output .= '<div class="summary"><h3>' . esc_html( $user->display_name ) . '</h3>';
				$output .= '<div class="author-description">' . esc_html( get_user_meta( $user->ID, '_educator_short_bio', true ) ) . '</div>';
				$output .= '<div class="author-links"><a href="' . esc_url( $author_posts_url ) . '">' . __( 'View Profile', 'ib-educator-theme' ) . ' <span class="fa fa-angle-double-right"></span></a></div>';
				$output .= '</div></div>';
				++$i;
			}

			$output .= '</div>';
		}
	}

	return $output;
}
endif;
add_shortcode( 'educator_lecturers', 'educator_lecturers_shortcode' );

if ( ! function_exists( 'educator_page_section' ) ) :
/**
 * Shortcode: page_section.
 *
 * @param array $atts
 * @param string $content
 * @return string
 */
function educator_page_section( $atts, $content = null ) {
	$atts = shortcode_atts( array(
		'bg_color' => '',
		'class'    => '',
	), $atts );

	$style = '';
	$class = 'section-content';

	if ( ! empty( $atts['bg_color'] ) ) {
		$style = ' style="background-color:' . esc_attr( $atts['bg_color'] ) . ';"';
		$class .= ' section-bg';
	}

	if ( '' != $atts['class'] ) {
		$class .= ' ' . $atts['class'];
	}

	return '<section class="' . esc_attr( $class ) . '"' . $style . '><div class="container clearfix"><div class="entry-content">' . educator_do_shortcodes( $content ) . '</div></div></section>';
}
endif;
add_shortcode( 'page_section', 'educator_page_section' );

/**
 * Output main slideshow.
 *
 * @param array $slides
 * @param array $settings
 * @return string
 */
function educator_fw_slideshow( $slides, $settings, $type = '' ) {
	$settings = wp_parse_args( $settings, array(
		'autoscroll' => 0,
	) );

	$class = 'flexslider';

	if ( 'fw' == $type ) $class .=  ' fw-slider';
	else $class .= ' post-slider';
	
	$output = '<div class="' . esc_attr( $class ) . '" data-autoscroll="' . intval( $settings['autoscroll'] ) . '"><ul class="slides">';
	$i = 0;

	foreach ( $slides as $slide ) {
		$has_caption = ( ! empty( $slide['title'] ) || ! empty( $slide['description'] ) );
		$slide_classes = array( 'slide' );

		if ( ! empty( $slide['overlay'] ) ) $slide_classes[] = 'overlay';

		if ( $i == 0 ) $slide_classes[] = 'active';

		$output .= '<li class="' . esc_attr( implode( ' ', $slide_classes ) ) . '">';

		// Slide image.
		$image_full = wp_get_attachment_image_src( $slide['attachment_id'], 'full' );
		$image_large = wp_get_attachment_image_src( $slide['attachment_id'], 'large' );

		if ( $image_full && $image_large ) {
			$output .= '<div class="slide-image"><img src="' . esc_url( $image_large[0] ) . '" srcset="'
					. esc_url( $image_large[0] ) . ' ' . intval( $image_large[1] ) . 'w, ' . esc_url( $image_full[0] ) . ' ' . intval( $image_full[1] ) . 'w'
					. '" sizes="100vw" alt="' . esc_attr( $slide['title'] ) . '"></div>';
		}
		
		if ( $has_caption ) {
			$classes = array();

			// Caption style.
			if ( ! empty( $slide['caption_style'] ) ) $classes[] = sanitize_html_class( $slide['caption_style'] );

			// Caption position.
			if ( ! empty( $slide['caption_pos'] ) ) $classes[] = sanitize_html_class( $slide['caption_pos'] );

			$output .= '<div class="slide-caption' . ( ! empty( $classes ) ? ' ' . implode( ' ', $classes ) : '' ) . '">';
			$output .= '<div class="container"><div class="caption-inner">';

			// Slide title.
			if ( ! empty( $slide['title'] ) ) {
				$output .= '<h2 class="slide-title">' . esc_html( $slide['title'] ) . '</h2>';
			}

			// Slide description.
			if ( ! empty( $slide['description'] ) ) {
				$output .= '<div class="slide-description">' . esc_html( $slide['description'] ) . '</div>';
			}

			// Button text.
			if ( ! empty( $slide['button_text'] ) && ! empty( $slide['url'] ) ) {
				$target = ( empty( $slide['target'] ) ) ? '' : ' target="' . esc_attr( $slide['target'] ) . '"';
				$output .= '<div class="buttons"><a class="button button-white" href="'
						. esc_url( $slide['url'] ) . '"' . $target . '>' . esc_html( $slide['button_text'] ) . '</a></div>';
			}

			$output .= '</div></div>';
			$output .= '</div>';
		}

		$output .= '</li>';

		++$i;
	}

	$output .= '</ul></div>';

	return $output;
}

/**
 * Output post slideshow.
 *
 * @param array $slides
 * @param array $settings
 * @return string
 */
function educator_post_slideshow( $slides, $settings ) {
	$settings = wp_parse_args( $settings, array(
		'autoscroll' => 0,
	) );

	$output = '<div class="flexslider post-slider" data-autoscroll="' . intval( $settings['autoscroll'] ) . '"><ul class="slides">';
	$i = 0;

	foreach ( $slides as $slide ) {
		$has_caption = ( ! empty( $slide['title'] ) || ! empty( $slide['description'] ) );
		$slide_classes = array( 'slide' );

		if ( ! empty( $slide['overlay'] ) ) $slide_classes[] = 'overlay';

		if ( $i == 0 ) $slide_classes[] = 'active';

		$output .= '<li class="' . esc_attr( implode( ' ', $slide_classes ) ) . '">';

		// Slide image.
		$image = wp_get_attachment_image_src( $slide['attachment_id'], 'large' );
		$img = '';

		if ( $image ) {
			$img = '<div class="slide-image"><img src="' . esc_url( $image[0] ) . '" alt="' . esc_attr( $slide['title'] ) . '"></div>';

			if ( ! empty( $slide['url'] ) ) {
				$output .= '<a href="' . esc_url( $slide['url'] ) . '" target="' . ( empty( $slide['target'] ) ? '_self' : esc_attr( $slide['target'] ) ) . '">' . $img . '</a>';
			} else {
				$output .= $img;
			}
		}
		
		if ( $has_caption ) {
			$classes = array();

			// Caption position.
			if ( ! empty( $slide['caption_pos'] ) ) $classes[] = sanitize_html_class( $slide['caption_pos'] );

			$output .= '<div class="slide-caption' . ( ! empty( $classes ) ? ' ' . implode( ' ', $classes ) : '' ) . '">';
			$output .= '<div class="caption-inner">';

			// Slide title.
			if ( ! empty( $slide['title'] ) ) {
				$output .= '<h2 class="slide-title">' . esc_html( $slide['title'] ) . '</h2>';
			}

			// Slide description.
			if ( ! empty( $slide['description'] ) ) {
				$output .= '<div class="slide-description">' . esc_html( $slide['description'] ) . '</div>';
			}

			$output .= '</div>'; // .caption-inner
			$output .= '</div>'; // .slide-caption
		}

		$output .= '</li>';

		++$i;
	}

	$output .= '</ul></div>';

	return $output;
}

/**
 * Output slideshow given its alias.
 *
 * @param string $slug
 * @return string
 */
function educator_slideshow( $slug, $type = '' ) {
	if ( ! $slug || ! class_exists( 'IB_Slideshow' ) ) {
		return '';
	}
	
	$ibs = IB_Slideshow::get_instance();
	$slideshow = $ibs->get_slideshow_by( 'slug', $slug );

	if ( ! $slideshow ) {
		return '';
	}
	
	$slides = $ibs->get_slides( $slideshow->ID );

	if ( ! $slides ) {
		return '';
	}

	$settings = $ibs->get_slideshow_settings( $slideshow->ID );
	$slideshow_type = isset( $settings['type'] ) ? $settings['type'] : 'default';

	switch ( $slideshow_type ) {
		case 'default':
			return educator_fw_slideshow( $slides, $settings, $type );

		case 'post':
			return educator_post_slideshow( $slides, $settings );
	}
}

if ( ! function_exists( 'educator_slideshow_shortcode' ) ) :
/**
 * Output slideshow section.
 *
 * @param array $atts
 * @param string $content
 * @return string
 */
function educator_slideshow_shortcode( $atts, $content = null ) {
	if ( ! function_exists( 'educator_slideshow' ) ) {
		return '';
	}

	$atts = shortcode_atts( array(
		'id' => '',
	), $atts );

	if ( empty( $atts['id'] ) ) {
		return '';
	}

	return '<section class="section-slider">' . educator_slideshow( $atts['id'], 'fw' ) . '</section>';
}
endif;
add_shortcode( 'section_slideshow', 'educator_slideshow_shortcode' );

function educator_post_slideshow_shortcode( $output, $atts, $content ) {
	return educator_slideshow( $atts['slug'] );
}
add_filter( 'alter_ib_slideshow_shortcode', 'educator_post_slideshow_shortcode', 10, 3 );
