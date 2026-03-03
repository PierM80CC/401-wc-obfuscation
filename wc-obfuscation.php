<?php
/**
 * Plugin Name: WC Attribute Obfuscation
 * Description: Obfusque les liens d'attributs WooCommerce sur les fiches produit pour economiser le budget de crawl.
 * Version:     1.0.0
 * Author:      Pierre M. / 401.fr
 * Text Domain: wc-obfuscation
 * Requires at least: 6.0
 * Requires PHP: 8.0
 * WC requires at least: 8.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'WCO_VERSION', '1.0.0' );

add_action( 'before_woocommerce_init', function () {
	if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
	}
} );

/**
 * Whether obfuscation assets should load on the current page.
 *
 * @return bool
 */
function wco_should_load(): bool {
	static $should_load = null;
	if ( null === $should_load ) {
		$should_load = function_exists( 'is_product' ) && (bool) apply_filters( 'wco_should_load', is_product() );
	}
	return $should_load;
}

/**
 * Obfuscate attribute links on single product pages.
 *
 * @param string $html      Formatted attribute HTML.
 * @param WC_Product_Attribute $attribute Product attribute object.
 * @param array  $values    Attribute term values.
 * @return string Modified HTML.
 */
function wco_obfuscate_attribute_link( string $html, $attribute, $values ): string {
	if ( ! wco_should_load() ) {
		return $html;
	}

	if ( strpos( $html, '<a' ) === false ) {
		return $html;
	}

	$result = preg_replace_callback(
		'/(<a\s[^>]*)href=["\']([^"\']*)["\']/',
		function ( $matches ) {
			$before = $matches[1];
			$href   = $matches[2];
			return $before . 'data-url="' . esc_url( $href ) . '" role="link" tabindex="0"';
		},
		$html
	);

	return $result ?? $html;
}

add_filter( 'woocommerce_attribute', 'wco_obfuscate_attribute_link', 20, 3 );

/**
 * Inline JS for obfuscated link navigation.
 *
 * @return void
 */
function wco_enqueue_script(): void {
	if ( ! wco_should_load() ) {
		return;
	}

	wp_register_script( 'wco-obfuscation', false, array(), WCO_VERSION, true );
	wp_enqueue_script( 'wco-obfuscation' );

	$js = <<<'JS'
(function() {
	function isSafeUrl( url ) {
		if ( ! url || url.startsWith( '//' ) ) return false;
		return url.startsWith( '/' ) || url.startsWith( location.origin );
	}

	function getObfuscatedLink( event ) {
		var el = event.target.closest( '[data-url]' );
		if ( ! el ) return null;
		var url = el.getAttribute( 'data-url' );
		if ( ! isSafeUrl( url ) ) return null;
		return { el: el, url: url };
	}

	document.addEventListener( 'click', function( e ) {
		var link = getObfuscatedLink( e );
		if ( ! link ) return;
		e.preventDefault();
		if ( e.ctrlKey || e.metaKey || e.shiftKey ) {
			window.open( link.url, '_blank', 'noopener,noreferrer' );
		} else {
			location.href = link.url;
		}
	});

	document.addEventListener( 'auxclick', function( e ) {
		if ( e.button !== 1 ) return;
		var link = getObfuscatedLink( e );
		if ( ! link ) return;
		e.preventDefault();
		window.open( link.url, '_blank', 'noopener,noreferrer' );
	});

	document.addEventListener( 'keydown', function( e ) {
		if ( e.key !== 'Enter' ) return;
		var link = getObfuscatedLink( e );
		if ( ! link ) return;
		e.preventDefault();
		location.href = link.url;
	});
})();
JS;

	wp_add_inline_script( 'wco-obfuscation', $js );
}

add_action( 'wp_enqueue_scripts', 'wco_enqueue_script' );

/**
 * Inline CSS for obfuscated links.
 *
 * @return void
 */
function wco_inline_css(): void {
	if ( ! wco_should_load() ) {
		return;
	}
	echo '<style>a[data-url]{cursor:pointer}</style>';
}

add_action( 'wp_head', 'wco_inline_css' );
