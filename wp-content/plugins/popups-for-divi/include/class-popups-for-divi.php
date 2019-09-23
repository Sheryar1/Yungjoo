<?php
/**
 * Popups for Divi
 * Main plugin instance/controller. The main popup logic is done in javascript,
 * so we mainly need to make sure that our JS/CSS is loaded on the correctly.
 *
 * @package Popups_For_Divi
 */

defined( 'ABSPATH' ) || die();

/**
 * Set up our popup integration.
 */
class Popups_For_Divi {

	/**
	 * Hook up the module.
	 *
	 * @since  1.0.0
	 */
	public function __construct() {
		add_filter(
			'plugin_action_links_' . DIVI_POPUP_PLUGIN,
			array( $this, 'plugin_add_settings_link' )
		);

		add_filter(
			'plugin_row_meta',
			array( $this, 'plugin_row_meta' ),
			10, 4
		);

		// Do not load the JS library, when the Pro version is active.
		if ( defined( 'DIVI_AREAS_PLUGIN' ) ) {
			return;
		}

		add_action(
			'wp_enqueue_scripts',
			array( $this, 'enqueue_js_library' )
		);

		// Load the onboarding wizard.
		require_once __DIR__ . '/class-popups-for-divi-onboarding.php';
		$this->onboarding = new Popups_For_Divi_Onboarding();
	}

	/**
	 * Display a custom link in the plugins list
	 *
	 * @since  1.0.2
	 * @param  array $links List of plugin links.
	 * @return array New list of plugin links.
	 */
	public function plugin_add_settings_link( $links ) {
		$links[] = sprintf(
			'<a href="%s" target="_blank">%s</a>',
			'https://philippstracker.com/divi-popup/',
			__( 'How it works', 'divi-popup' )
		);
		return $links;
	}

	/**
	 * Display additional details in the right column of the "Plugins" page.
	 *
	 * @since 1.6.0
	 * @param string[] $plugin_meta An array of the plugin's metadata,
	 *                              including the version, author,
	 *                              author URI, and plugin URI.
	 * @param string   $plugin_file Path to the plugin file relative to the plugins directory.
	 * @param array    $plugin_data An array of plugin data.
	 * @param string   $status      Status of the plugin. Defaults are 'All', 'Active',
	 *                              'Inactive', 'Recently Activated', 'Upgrade', 'Must-Use',
	 *                              'Drop-ins', 'Search'.
	 */
	public function plugin_row_meta( $plugin_meta, $plugin_file, $plugin_data, $status ) {
		if ( DIVI_POPUP_PLUGIN !== $plugin_file ) {
			return $plugin_meta;
		}

		$plugin_meta[] = sprintf(
			'<a href="%s" target="_blank">%s</a>',
			'https://philippstracker.com/divi-areas-pro/',
			__( 'Divi Areas <strong>Pro</strong>', 'divi-popup' )
		);

		return $plugin_meta;
	}

	/**
	 * Add the CSS/JS support to the front-end to make the popups work.
	 *
	 * @since  1.0.0
	 */
	public function enqueue_js_library() {
		if ( defined( 'DOING_CRON' ) && DOING_CRON ) {
			return;
		}
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			return;
		}

		// phpcs:ignore
		if ( isset( $_GET['et_pb_preview'] ) && isset( $_GET['et_pb_preview_nonce'] ) ) {
			return;
		}
		$cache_version = DIVI_POPUP_VERSION;

		if ( function_exists( 'et_fb_is_enabled' ) ) {
			$is_divi_v3 = true;
			add_filter( 'divi_popup_build_mode', 'et_fb_is_enabled' );
		} else {
			$is_divi_v3 = false;
		}

		$config = array();

		// -- Modify UI of popups --

		/**
		 * The base z-index. This z-index is used for the overlay, every
		 * popup has a z-index increased by 1:
		 */
		$config['zIndex'] = 100000;

		/**
		 * Speed of the fade-in/out animations. Set this to 0 to disable fade-in/out.
		 */
		$config['animateSpeed'] = 400;

		// -- Modify triggers and behavior --

		/**
		 * A class-name prefix that can be used in *any* element to trigger
		 * the given popup. Default prefix is 'show-popup-', so we could
		 * add the class 'show-popup-demo' to an image. When this image is
		 * clicked, the popup "#demo" is opened.
		 * The prefix must have 3 characters or more.
		 *
		 * Example:
		 * <span class="show-popup-demo">Click here to show #demo</span>
		 *
		 * @since 1.3.0
		 */
		$config['triggerClassPrefix'] = 'show-popup-';

		/**
		 * Alternate popup trigger via data-popup attribute.
		 *
		 * Example:
		 * <span data-popup="demo">Click here to show #demo</span>
		 */
		$config['idAttrib'] = 'data-popup';

		/**
		 * Class that indicates a modal popup. A modal popup can only
		 * be closed via a close button, not by clicking on the overlay.
		 */
		$config['modalIndicatorClass'] = 'is-modal';

		/**
		 * This changes the default close-button state when a popup does
		 * not specify noCloseClass or withCloseClass
		 *
		 * @since  1.1.0
		 */
		$config['defaultShowCloseButton'] = true;

		/**
		 * Add this class to the popup section to show the close button
		 * in the top right corner.
		 *
		 * @since  1.1.0
		 */
		$config['withCloseClass'] = 'with-close';

		/**
		 * Add this class to the popup section to hide the close button
		 * in the top right corner.
		 *
		 * @since  1.1.0
		 */
		$config['noCloseClass'] = 'no-close';

		/**
		 * Name of the class that closes the currently open popup. By default
		 * this is "close".
		 *
		 * @since 1.3.0
		 */
		$config['triggerCloseClass'] = 'close';

		/**
		 * Name of the class that marks a popup as "singleton". A "singleton" popup
		 * will close all other popups when it is opened/focused. By default this
		 * is "single".
		 *
		 * @since 1.4.0
		 */
		$config['singletonClass'] = 'single';

		/**
		 * Name of the class that activates the dark mode (dark close button) of the
		 * popup.
		 *
		 * @since 1.6.0
		 */
		$config['darkModeClass'] = 'dark';

		/**
		 * Name of the class that removes the box-shadow from the popup.
		 *
		 * @since 1.6.0
		 */
		$config['noShadowClass'] = 'no-shadow';

		/**
		 * Name of the class that changes the popups close button layout.
		 *
		 * @since 1.6.0
		 */
		$config['altCloseClass'] = 'close-alt';

		/**
		 * CSS selector used to identify popups.
		 * Each popup must also have a unique ID attribute that
		 * identifies the individual popups.
		 */
		$config['popupSelector'] = '.et_pb_section.popup';

		/**
		 * Whether to wait for an JS event-trigger before initializing
		 * the popup module in front end. This is automatically set
		 * for the Divi theme.
		 *
		 * If set to false, the popups will be initialized instantly when the JS
		 * library is loaded.
		 *
		 * @since 1.2.0
		 */
		$config['initializeOnEvent'] = (
			$is_divi_v3
				? 'et_pb_after_init_modules' // Divi 3.0+ detected.
				: false // Older Divi or other themes.
		);

		// -- CSS classes that control layout --

		/**
		 * All popups are wrapped in a new div element. This is the
		 * class name of this wrapper div.
		 *
		 * @since  1.2.0
		 */
		$config['popupWrapperClass'] = 'popup_outer_wrap';

		/**
		 * CSS class that is added to the popup when it enters
		 * full-width mode (i.e. on small screens)
		 */
		$config['fullWidthClass'] = 'popup_full_width';

		/**
		 * CSS class that is added to the popup when it enters
		 * full-height mode (i.e. on small screens)
		 */
		$config['fullHeightClass'] = 'popup_full_height';

		/**
		 * CSS class that is added to the website body when at least
		 * one popup is visible.
		 */
		$config['openPopupClass'] = 'evr_popup_open';

		/**
		 * CSS class that is added to the modal overlay that is
		 * displayed while at least one popup is visible.
		 */
		$config['overlayClass'] = 'evr_fb_popup_modal';

		/**
		 * Class that adds an exit-intent trigger to the popup.
		 * The exit intent popup is additionally triggered, when the
		 * mouse pointer leaves the screen towards the top.
		 * It's only triggered once.
		 */
		$config['exitIndicatorClass'] = 'on-exit';

		/**
		 * Defines the delay for reacting to exit-intents.
		 * Default is 2000, which means that an exit intent during the first two
		 * seconds after page load is ignored.
		 *
		 * @since 1.5.1
		 */
		$config['onExitDelay'] = 2000;

		/**
		 * Class to hide a popup on mobile devices.
		 * Used for non-Divi themes or when creating popups via DiviPopup.register().
		 *
		 * @since 1.5.0
		 */
		$config['notMobileClass'] = 'not-mobile';

		/**
		 * Class to hide a popup on tablet devices.
		 * Used for non-Divi themes or when creating popups via DiviPopup.register().
		 *
		 * @since 1.5.0
		 */
		$config['notTabletClass'] = 'not-tablet';

		/**
		 * Class to hide a popup on desktop devices.
		 * Used for non-Divi themes or when creating popups via DiviPopup.register().
		 *
		 * @since 1.5.0
		 */
		$config['notDesktopClass'] = 'not-desktop';

		/**
		 * The parent container which holds all popups. For most Divi sites
		 * this could be "#page-container", but some child themes do not
		 * adhere to this convention.
		 * When a valid Divi theme is detected by the JS library, it will switch from
		 * 'body' to '#page-container'. To avoid this, simply use
		 *
		 * @since 1.3.0
		 */
		$config['baseContext'] = 'body';

		/**
		 * This class is added to the foremost popup; this is useful to
		 * hide/fade popups in the background.
		 *
		 * @since  1.1.0
		 */
		$config['activePopupClass'] = 'is-open';

		/**
		 * This is the class-name of the close button that is
		 * automatically added to the popup. Only change this, if you
		 * want to use existing CSS or when the default class causes a
		 * conflict with your existing code.
		 *
		 * Note: The button is wrapped in a span which gets the class-
		 *       name `closeButtonClass + "_wrap"` e.g. "evr-close_wrap"
		 *
		 * @since  1.1.0
		 */
		$config['closeButtonClass'] = 'evr-close';

		/**
		 * Display debug output in the JS console.
		 *
		 * @since 1.3.0 Default value is WP_DEBUG. Before it, default was false.
		 */
		$config['debug'] = defined( WP_DEBUG ) ? WP_DEBUG : false;

		/* -- End of default configuration -- */

		// Compatibility with older Popups for Divi version.
		$js_data = apply_filters( 'evr_divi_popup-js_data', $config ); // phpcs:ignore

		// Divi Areas Pro filter.
		$js_data = apply_filters( 'divi_areas_js_data', $config );

		if ( apply_filters( 'divi_popup_build_mode', false ) ) {
			$base_name  = 'builder';
			$inline_css = '';
		} else {
			$base_name  = 'front';
			$inline_css = sprintf(
				'%s{display:none}',
				$js_data['popupSelector']
			);
		}

		if ( $js_data['debug'] || ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ) {
			$cache_version .= '-' . time();
		}

		wp_register_script(
			'js-divi-popup',
			plugins_url( 'js/' . $base_name . '.js', dirname( __FILE__ ) ),
			array( 'jquery' ),
			$cache_version,
			true
		);

		wp_register_style(
			'css-divi-popup',
			plugins_url( 'css/' . $base_name . '.css', dirname( __FILE__ ) ),
			array(),
			$cache_version,
			'all'
		);

		wp_localize_script( 'js-divi-popup', 'DiviPopupData', $js_data );

		wp_enqueue_script( 'js-divi-popup' );
		wp_enqueue_style( 'css-divi-popup' );

		if ( $inline_css ) {
			wp_add_inline_style( 'css-divi-popup', $inline_css );
		}
	}
}
