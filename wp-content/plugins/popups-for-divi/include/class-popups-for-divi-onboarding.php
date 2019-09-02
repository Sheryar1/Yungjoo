<?php
/**
 * Onboarding signup.
 *
 * Offers an onboarding signup form so new users for new users.
 *
 * @package Popups_For_Divi
 */

defined( 'ABSPATH' ) || die();

/**
 * Set up our popup integration.
 */
class Popups_For_Divi_Onboarding {

	/**
	 * Hook up the module.
	 *
	 * @since  1.6.0
	 * @return void
	 */
	public function __construct() {
		if ( ! is_admin() ) {
			return;
		}

		// This action only fires on the main wp-admin Dashboard page.
		add_action(
			'load-index.php',
			array( $this, 'init_onboarding' )
		);

		add_action(
			'wp_ajax_pfd_hide_onboarding',
			array( $this, 'ajax_hide_onboarding' )
		);

		add_action(
			'wp_ajax_pfd_start_course',
			array( $this, 'ajax_start_course' )
		);
	}

	/**
	 * Initialize the onboarding process.
	 *
	 * @since 1.6.0
	 * @return void
	 */
	public function init_onboarding() {
		if ( defined( 'DISABLE_NAG_NOTICES' ) && DISABLE_NAG_NOTICES ) {
			return;
		}

		if ( ! defined( 'DIVI_POPUP_ONBOARDING_CAP' ) ) {
			// By default display the onboarding notice to all users who can
			// activate plugins (i.e. administrators).
			define( 'DIVI_POPUP_ONBOARDING_CAP', 'activate_plugins' );
		}

		$user = wp_get_current_user();

		if ( ! $user->has_cap( DIVI_POPUP_ONBOARDING_CAP ) ) {
			return;
		}

		if ( 'done' === $user->get( '_pfd_onboarding' ) ) {
			return;
		}

		add_action(
			'admin_notices',
			array( $this, 'onboarding_notice' ),
			1
		);
	}

	/**
	 * Ajax handler: Permanently close the onboarding notice.
	 *
	 * @since 1.6.0
	 * @return void
	 */
	public function ajax_hide_onboarding() {
		// phpcs:ignore
		update_user_meta( get_current_user_id(), '_pfd_onboarding', 'done' );

		wp_send_json_success();
	}

	/**
	 * Ajax handler: Subscribe the email address to our onboarding course.
	 *
	 * @since 1.6.0
	 * @return void
	 */
	public function ajax_start_course() {
		// phpcs:ignore
		$form = wp_unslash( $_POST );

		$email = sanitize_email( trim( $form['email'] ) );
		$name  = sanitize_text_field( trim( $form['name'] ) );

		// Send the subscription details to our website.
		$resp = wp_remote_post(
			'https://philippstracker.com/wp-admin/admin-post.php',
			array(
				'headers' => array(
					'Content-Type' => 'application/x-www-form-urlencoded; charset=utf-8',
				),
				'body'    => array(
					'action' => 'pfd_start_onboarding',
					'fname'  => $name,
					'email'  => $email,
				),
			)
		);

		if ( is_wp_error( $resp ) ) {
			wp_send_json_success( 'ERROR' );
		}

		$result = wp_remote_retrieve_body( $resp );
		wp_send_json_success( $result );
	}

	/**
	 * Output the onboarding notice on th wp-admin Dashboard.
	 *
	 * This function intentionally outputs inline CSS and JS - since it's only
	 * displayed once, it does not make sense to store the CSS/JS in the browsers
	 * cache.
	 *
	 * @since 1.6.0
	 * @return void
	 */
	public function onboarding_notice() {
		$user = wp_get_current_user();

		?>
		<div class="pfd-onboarding notice">
			<p class="title"><?php _e( 'Thanks for using Popups&nbsp;for&nbsp;Divi', 'divi-popup' ); ?> 😊</p>
			<div class="pfd-layout">
				<p class="msg"><?php _e( 'We have created a short email course that helps you get the most out of <a href="https://wordpress.org/plugins/popups-for-divi/" target="_blank">Popups for Divi</a>. You will receive six short emails that help you to avoid common pitfalls and teach you some advanced use cases.', 'divi-popup' ); ?></p>
				<div class="form">
					<input
						type="name"
						class="name"
						placeholder="Your first name"
					/>
					<input
						type="email"
						class="email"
						placeholder="Your email address"
						value="<?php echo esc_attr( $user->user_email ); ?>"
					/>
					<button class="button-primary submit">
					<?php _e( 'Start The Course!', 'divi-popup' ); ?>
					</button>
				</div>
			</div>
			<p class="privacy"><?php _e( 'Only your name and email is sent to our website. We use the information to deliver the onboarding mails. Nothing else. <a href="https://philippstracker.com/privacy/" target="_blank">Privacy&nbsp;Policy</a>', 'divi-popup' ); ?></p>
			<div class="loader"><span class="spinner is-active"></span></div>
			<span class="notice-dismiss"><?php _e( 'Close forever', 'divi-popup' ); ?></span>
		</div>
		<style>
			.wrap .notice.pfd-onboarding{position:relative;margin-bottom:4em;padding-bottom:0;border-left-color:#660099}
			.pfd-onboarding .title{font-weight:600;color:#000;border-bottom:1px solid #eee;padding-bottom:.5em;padding-right:100px;margin-bottom:0}
			.pfd-onboarding .form{text-align:center;position:relative;padding:.5em}
			.pfd-onboarding .privacy{font-size:.9em;text-align:center;opacity:.6;position:absolute;left:0;right:0}
			.pfd-onboarding .pfd-layout{display:flex;flex-wrap:wrap;position:relative}
			.pfd-onboarding .form:before{content:'';position:absolute;right:-9px;left:-9px;top:0;bottom:1px;background:#9944cc linear-gradient(-45deg,#660099 0%,#9944cc 100%)!important;box-shadow:0 0 0 1px #0004 inset}
			.pfd-onboarding .pfd-layout>*{flex:1 1 100%;align-self:center;z-index:10}
			.pfd-onboarding input:focus,
			.pfd-onboarding input,
			.pfd-onboarding button.button-primary,
			.pfd-onboarding button.button-primary:focus{display:block;width:80%;margin:12px auto;text-align:center;border-radius:0;height:30px;vertical-align:top;box-shadow:0 0 0 5px #fff3;outline:none;position:relative;z-index:10}
			.pfd-onboarding input:focus,
			.pfd-onboarding input{border:1px solid #0002;padding:5px 3px}
			.pfd-onboarding .notice-dismiss:before{display:none}
			.pfd-onboarding .msg{position:relative;z-index:20}
			.pfd-onboarding .msg .dismiss{float:right}
			.pfd-onboarding .msg strong{white-space:nowrap}
			.pfd-onboarding .msg .emoji{width:3em!important;height:3em!important;vertical-align:middle!important;margin-right:1em!important;float:left}
			.pfd-onboarding .loader{display:none;position:absolute;background:#fffc;z-index:50;left:0;top:0;right:0;bottom:0}
			.pfd-onboarding.loading .loader{display:block}
			.pfd-onboarding .loader .spinner{position:absolute;left:50%;top:50%;margin:0;transform:translate(-50%,-50%)}

			@media (min-width: 783px) and (max-width: 1023px) {
				.pfd-onboarding .form:before{right:-11px;left:-11px}
			}
			@media (min-width:1024px) {
				.wrap .notice.pfd-onboarding{margin-bottom:2em;padding-right:0}
				.pfd-onboarding .pfd-layout{flex-wrap:nowrap;overflow:hidden;padding:.5em 0}
				.pfd-onboarding .pfd-layout>*{flex:0 0 50%}
				.pfd-onboarding input:focus,
				.pfd-onboarding input,
				.pfd-onboarding button.button-primary,
				.pfd-onboarding button.button-primary:focus{display:inline-block;width:auto;margin:5px}
				.pfd-onboarding input:focus,
				.pfd-onboarding input{width:32%}
				.pfd-onboarding .form{position:static}
				.pfd-onboarding .form:before{width:50%;right:0;left:auto;bottom:0}
				.pfd-onboarding .form:after{content:'';position:absolute;right:50%;width:50px;height:50px;top:50%;background:#fff;transform:translate(50%,-50%) rotate(45deg) skew(20deg,20deg)}
			}
		</style>
		<script>jQuery(function() {
			var notice = jQuery('.pfd-onboarding.notice');
			var msg = notice.find('.msg');
			notice.on('click', '.notice-dismiss, .dismiss', dismissForever);
			notice.find('.submit').on('click', startCourse);
			function dismissForever() {
				notice.addClass('loading');
				jQuery.post(ajaxurl, {
					action: 'pfd_hide_onboarding'
				}, function() {
					notice.removeClass('loading');
					notice.fadeOut(400, function() {
						notice.remove();
					});
				});
			}
			function startCourse() {
				var email = notice.find('input.email').val().trim();
				var name = notice.find('input.name').val().trim();

				if (name.length<2) {
					notice.find('input.name').focus();
					return false;
				}
				if (email.length<5) {
					notice.find('input.email').focus();
					return false;
				}
				notice.addClass('loading');
				jQuery.post(ajaxurl, {
					action: 'pfd_start_course',
					name: name,
					email: email,
				}, function(res) {
					notice.removeClass('loading');
					state = res && res.data ? res.data : '';
					if ('OK'===state) {
						msg.html("🎉 <?php echo _e( 'Congratulations! Please check your inbox and look for an email with the subject &quot;<strong>Your Popups for Divi course is one click away!</strong>&quot; to confirm your registration.', 'divi-popup' ); ?>");
						msg.append("<br><a href='#' class='dismiss'><?php esc_html_e( 'Close this message', 'divi-popup' ); ?></a>");
					}
					else if ('DUPLICATE'===state) {
						msg.html("<?php esc_html_e( 'It looks like you already signed up for this course... Please check your inbox or use a different email address', 'divi-popup' ); ?>");
					}
					else if ('INVALID_NAME'===state) {
						msg.html("<?php esc_html_e( 'Our system says, your name is invalid. Please check your input', 'divi-popup' ); ?>");
					}
					else if ('INVALID_EMAIL'===state) {
						msg.html("<?php esc_html_e( 'Our system rejected the email address. Please check your input', 'divi-popup' ); ?>");
					}
					else {
						msg.html("<?php esc_html_e( 'Something went wrong, but we\'re not sure what. Please try again in a moment or contact us via the wp.org support forum', 'divi-popup' ); ?>");
					}
				});
			}
		})</script>
		<?php
	}
}
