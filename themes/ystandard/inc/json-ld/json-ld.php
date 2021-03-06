<?php
/**
 * Json-LD出力
 *
 * @package ystandard
 * @author yosiakatsuki
 * @license GPL-2.0+
 */

if ( ! function_exists( 'ys_the_json_ld' ) ) {
	/**
	 * Json-LD出力
	 */
	function ys_the_json_ld() {

		if ( is_home() || is_archive() ) {
			global $posts;
			$json = array();
			foreach ( $posts as $post ) {
				$json[] = ys_get_json_ld_article( $post );
			}
		} elseif ( is_singular() && ! is_front_page() ) {
			/**
			 * 個別ページ
			 */
			$json = ys_get_json_ld_article();
		} else {
			/**
			 * TOP・一覧ページなど
			 */
			$json = array(
				ys_get_json_ld_organization(),
				ys_get_json_ld_website(),
			);
		}
		$json = json_encode( $json, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT );
		if ( '' !== $json ) {
			echo '<script type="application/ld+json">' . $json . '</script>' . PHP_EOL;
		}
	}
}
add_action( 'wp_footer', 'ys_the_json_ld' );

/**
 * Json-LD Organization作成
 */
function ys_get_json_ld_organization() {
	$json             = array();
	$json['@context'] = 'http://schema.org';
	$json['@type']    = 'Organization';
	$json['url']      = home_url( '/' );
	if ( has_custom_logo() ) {
		$logo         = ys_get_custom_logo_image_object();
		$json['logo'] = array(
			'@type'  => 'ImageObject',
			'url'    => $logo[0],
			'width'  => $logo[1],
			'height' => $logo[2],
		);
	}
	return $json;
}
/**
 * Json-LD Website 作成
 */
function ys_get_json_ld_website() {
	$json                  = array();
	$json['@context']      = 'http://schema.org';
	$json['@type']         = 'Website';
	$json['url']           = home_url( '/' );
	$json['name']          = get_bloginfo( 'name' );
	$json['alternateName'] = get_bloginfo( 'name' );
	if ( ys_is_top_page() ) {
		$json['potentialAction'] = array(
			'@type'       => 'SearchAction',
			'target'      => home_url( '/?s={query}' ),
			'query-input' => 'required name=query',
		);
	}
	return $json;
}
/**
 * Json-LD Article 作成
 *
 * @param object $post_data post.
 */
function ys_get_json_ld_article( $post_data = null ) {
	global $post;
	if ( is_null( $post_data ) ) {
		$post_data = $post;
	}
	$json                     = array();
	$url                      = get_the_permalink( $post_data->ID );
	$name                     = get_the_title( $post_data->ID );
	$excerpt                  = esc_html( ys_get_the_custom_excerpt( '', 0, $post_data->ID ) );
	$content                  = esc_html( ys_get_plain_text( $post_data->post_content ) );
	$json['@context']         = 'http://schema.org';
	$json['@type']            = 'Article';
	$json['mainEntityOfPage'] = array(
		'@type' => 'WebPage',
		'@id'   => $url,
	);
	$json['name']             = $name;
	$json['headline']         = mb_substr( $name, 0, 110 );
	$json['description']      = $excerpt;
	$json['articleBody']      = $content;
	$json['author']           = array(
		'@type' => 'Person',
		'name'  => ys_get_author_display_name(),
	);
	$json['datePublished']    = get_the_date( DATE_ATOM, $post_data->ID );
	$json['dateModified']     = get_post_modified_time( DATE_ATOM, false, $post_data->ID );
	/**
	 * 投稿画像
	 */
	$image = ys_get_the_image_object( 'full', $post_data->ID );
	if ( $image ) {
		$json['image'] = array(
			'@type'  => 'ImageObject',
			'url'    => $image[0],
			'width'  => $image[1],
			'height' => $image[2],
		);
	}
	$category = get_the_category();
	if ( $category ) {
		if ( 1 < count( $category ) ) {
			$article_section = array();
			foreach ( $category as $item ) {
				$article_section[] = $item->name;
			}
			$json['articleSection'] = $article_section;
		} else {
			$json['articleSection'] = esc_html( $category[0]->name );
		}
	}
	$json['url'] = $url;
	/**
	 * パブリッシャー
	 */
	$json['publisher'] = array(
		'@type' => 'Organization',
		'name'  => ys_get_publisher_name(),
	);
	$publisher_img     = ys_get_publisher_image();
	if ( $publisher_img ) {
		$publisher_img             = ys_calc_publisher_image_size( $publisher_img );
		$json['publisher']['logo'] = array(
			'@type'  => 'ImageObject',
			'url'    => $publisher_img[0],
			'width'  => $publisher_img[1],
			'height' => $publisher_img[2],
		);
	}
	return $json;
}
/**
 * パブリッシャー名を取得
 */
function ys_get_publisher_name() {
	$name = ys_get_option( 'ys_option_structured_data_publisher_name' );
	if ( '' === $name ) {
		$name = get_bloginfo( 'name' );
	}
	return $name;
}
/**
 * パブリッシャー用画像のサイズ判断、相対サイズの計算
 *
 * @param array $image image data.
 */
function ys_calc_publisher_image_size( $image ) {
	if ( 60 < $image[2] ) {
		$height   = 60;
		$width    = $height * ( $image[1] / $image[2] );
		$image[1] = (int) $width;
		$image[2] = (int) $height;
	}
	if ( 600 < $image[1] ) {
		$width    = 600;
		$height   = $width * ( $image[2] / $image[1] );
		$image[1] = (int) $width;
		$image[2] = (int) $height;
	}
	return $image;
}
