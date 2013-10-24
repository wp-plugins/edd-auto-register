<?php
/*
Plugin Name: Easy Digital Downloads - Auto Register
Plugin URI: http://sumobi.com/shop/edd-auto-register/
Description: Auto registers customers as a user on your website without the need for the customer to fill in the EDD registration form
Version: 1.0
Author: Andrew Munro, Sumobi
Author URI: http://sumobi.com/
License: GPL-2.0+
License URI: http://www.opensource.org/licenses/gpl-license.php
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'EDD_Auto_Register' ) ) {

	class EDD_Auto_Register {

		private static $instance;

		/**
		 * Main Instance
		 *
		 * Ensures that only one instance exists in memory at any one
		 * time. Also prevents needing to define globals all over the place.
		 *
		 * @since 1.0
		 *
		 */
		public static function instance() {
			if ( ! isset ( self::$instance ) ) {
				self::$instance = new self;
			}

			return self::$instance;
		}


		/**
		 * Start your engines
		 *
		 * @since 1.0
		 *
		 * @return void
		 */
		public function __construct() {
			$this->setup_actions();
		}

		/**
		 * Setup the default hooks and actions
		 *
		 * @since 1.0
		 *
		 * @return void
		 */
		private function setup_actions() {
			
			global $edd_options;

			// text domain
			add_action( 'init', array( $this, 'textdomain' ) );

			// create user, email user, auto login
			add_action( 'edd_complete_purchase', array( $this, 'create_user' ) );

			// add settings
			// This will come in future version
			//add_filter( 'edd_settings_extensions', array( $this, 'settings' ) );

			// if user is not logged in
			if ( ! is_user_logged_in() ) {

				// set error if email already exists
				add_action( 'edd_checkout_error_checks', array( $this, 'set_error' ), 10, 2 );

				// if user's are required to login (Guest Checkout disabled)
				if ( edd_no_guest_checkout() && isset( $edd_options['show_register_form'] ) ) {

					// add login form
					add_action( 'edd_purchase_form_register_fields', array( $this, 'login_form' ) );

					// remove joint registration/purchase form
					remove_action( 'edd_purchase_form_register_fields', 'edd_get_register_fields' );

				}
				// if "Show Register / Login Form?" is enabled
				elseif ( isset( $edd_options['show_register_form'] ) ) {

					// remove standard registration form as it will interfer with plugin
					remove_action( 'edd_purchase_form_register_fields', 'edd_get_register_fields' );

					// add standard purchase fields
					add_action( 'edd_purchase_form_register_fields', 'edd_user_info_fields' );

				}
			}

			// can the customer checkout?
			add_filter( 'edd_can_checkout', array( $this, 'can_checkout' ) );

			// show error before purchase form
			add_action( 'edd_before_purchase_form', array( $this, 'edd_error_must_log_in' ) );

			do_action( 'edd_auto_register_setup_actions' );
		}

		/**
		 * Internationalization
		 *
		 * @since 1.0
		 */
		function textdomain() {
			load_plugin_textdomain( 'edd-auto-register', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
		}

		/**
		 * Create user from email address. Password is generated automatically and customer is emailed new login details
		 *
		 * @since 1.0
		*/
		function create_user( $purchase_id ) {
			
			// return if user is not logged in
			if ( is_user_logged_in() )
				return;

			global $edd_options;

			$purchase_session = edd_get_purchase_session();
			$user_info = $purchase_session['user_info'];

			$email_address = $user_info['email'];
			$first_name = $user_info['first_name'];
			$last_name = $user_info['last_name'];
			$user_id = username_exists( $email_address );

			if ( edd_no_guest_checkout() )
				return;

			// check to see if the username exists already and an email hasn't already been registered against username
			if ( ! $user_id && email_exists( $user_id ) == false ) {
				
				// Generate the password and create the user
				$password = wp_generate_password( 12, false );
				$user_id = wp_create_user( $email_address, $password, $email_address );

				// create user with information entered at checkout
				wp_update_user(
					array(
						'ID'			=> $user_id,
						'first_name'	=> $first_name,
						'last_name'		=> $last_name,
						'nickname'		=> $first_name, // set nick name to be the same as first name
						'display_name'	=> $first_name, // set display name to be the same as first name
					)
				);
				
				// Set role
				$user = new WP_User( $user_id );
				$user->set_role( apply_filters( 'edd_auto_register_role', 'subscriber' ) );

				// User details
				$user_details = get_user_by( 'email', $email_address );
				$username = $user_details->user_login;

				// purchase key
				$purchase_key = $purchase_session['purchase_key'];
				// payment_id
				$payment_id = edd_get_purchase_id_by_key( $purchase_key );
				// Update user ID
				update_post_meta( $payment_id, '_edd_payment_user_id', $user_id );

				// Auto login the new user
				edd_log_user_in( $user_id, $username, $password );

				// Subject line
				$subject = isset( $edd_options['edd_auto_register_email_subject'] ) && '' != $edd_options['edd_auto_register_email_subject'] ? esc_attr( $edd_options['edd_auto_register_email_subject'] ) : __( 'Your login details', 'edd-auto-register' );

				$subject = apply_filters( 'edd_auto_register_email_subject', __( 'Login details', 'edd-auto-register' ) );

				$message = edd_get_email_body_header();
				$message .= $this->get_email_body_content( $first_name, $username, $password );
				$message .= edd_get_email_body_footer();

				// get from name and email from EDD options
				$from_name = isset( $edd_options['from_name'] ) ? $edd_options['from_name'] : get_bloginfo( 'name' );
				$from_email = isset( $edd_options['from_email'] ) ? $edd_options['from_email'] : get_option( 'admin_email' );

				$headers = "From: " . stripslashes_deep( html_entity_decode( $from_name, ENT_COMPAT, 'UTF-8' ) ) . " <$from_email>\r\n";
				$headers .= "Reply-To: ". $from_email . "\r\n";
				$headers .= "Content-Type: text/html; charset=utf-8\r\n";
				$headers = apply_filters( 'edd_auto_register_headers', $headers );
				
				// Email the user
				$email = apply_filters( 'edd_auto_register_send_email', true );

				if ( $email )
					wp_mail( $email_address, $subject, $message, $headers );

			}
		}

		/**
		 * Email Template Body
		 *
		 * @since 1.0
		 * @return string $default_email_body Body of the email
		 */
		function get_email_body_content( $first_name, $username, $password ) {

			// Email body
			$default_email_body = __( "Dear", "edd-auto-register" ) . ' ' . $first_name . ",\n\n";
			$default_email_body .= __( "Below are your login details:", "edd-auto-register" ) . "\n\n";
			$default_email_body .= __( "Your Username:", "edd-auto-register" ) . ' ' . $username . "\n\n";
			$default_email_body .= __( "Your Password:", "edd-auto-register" ) . ' ' . $password . "\n\n";
			$default_email_body .= get_bloginfo( 'name' ) . "\n\n";
			$default_email_body .= site_url();

			$default_email_body = apply_filters( 'edd_auto_register_email_body', $default_email_body, $first_name, $username, $password );
			
			return apply_filters( 'edd_purchase_receipt', $default_email_body, null, null );
		}

		/**
		 * Set error message
		 * Prevents form from processing if username already exists, or the email address is already registered against another user account
		 *
		 * @since 1.0
		*/
		function set_error( $valid_data, $post_data ) {

			if ( edd_no_guest_checkout() )
				return;

			$email_address 	= $valid_data['guest_user_data']['user_email'];
			$email_address 	= sanitize_user( $email_address, true );
			$user_id 		= username_exists( $email_address );

			// check to see if the username exists already and an email hasn't already been registered against username
			if ( $user_id || email_exists( $valid_data['guest_user_data']['user_email'] ) ) {
				edd_set_error( 'edd_auto_register_error_email_exists', apply_filters( 'edd_auto_register_error_message', __( 'Email Address already in use', 'edd-auto-register' ) ) );
			}
			else {
				edd_unset_error( 'edd_auto_register_error_email_exists' );
			}
			
		}

		/**
		 * Error displayed when User must be logged in (Guest Checkout disabled) and there is no Register / Login Form enabled
		 *
		 * @since 1.0
		*/
		function edd_error_must_log_in() {
			global $edd_options;

			// return if user is already logged in
			if ( is_user_logged_in() )
				return;

			if ( edd_no_guest_checkout() && !isset( $edd_options['show_register_form'] ) ) {
				edd_set_error( 'edd_auto_register_error_must_login', apply_filters( 'edd_auto_register_error_message', __( 'You must login to complete your purchase', 'edd-auto-register' ) ) );
			}
			else {
				edd_unset_error( 'edd_auto_register_error_must_login' );
			}

			edd_print_errors();
			
		}

		/**
		 * Can checkout?
		 * Prevents the form from being displayed when User must be logged in (Guest Checkout disabled), but "Show Register / Login Form?" is not
		 *
		 * @since 1.0
		*/
		function can_checkout( $can_checkout ) {
			global $edd_options;
			
			if ( edd_no_guest_checkout() && !isset( $edd_options['show_register_form'] ) && ! is_user_logged_in() ) {
				return false;
			}

			return $can_checkout;
		}

		/**
		 * Gets the login fields for the login form on the checkout. This function hooks
		 * on the edd_purchase_form_login_fields to display the login form if a user already
		 * had an account.
		 *
		 * @since 1.0
		 * @return string
		 */
		function login_form() {
			ob_start(); ?>
				<fieldset id="edd_login_fields">
					<span><legend><?php echo apply_filters( 'edd_auto_register_checkout_login_text', __( 'Login To Purchase', 'edd-auto-register' ) ); ?></legend></span>

					<?php do_action( 'edd_checkout_login_fields_before' ); ?>

					<p id="edd-user-login-wrap">
						<label class="edd-label" for="edd-username"><?php _e( 'Username', 'edd-auto-register' ); ?></label>
						<input class="<?php if(edd_no_guest_checkout()) { echo 'required '; } ?>edd-input" type="text" name="edd_user_login" id="edd_user_login" value="" placeholder="<?php _e( 'Your username', 'edd-auto-register' ); ?>"/>
					</p>

					<p id="edd-user-pass-wrap" class="edd_login_password">
						<label class="edd-label" for="edd-password"><?php _e( 'Password', 'edd-auto-register' ); ?></label>
						<input class="<?php if(edd_no_guest_checkout()) { echo 'required '; } ?>edd-input" type="password" name="edd_user_pass" id="edd_user_pass" placeholder="<?php _e( 'Your password', 'edd-auto-register' ); ?>"/>
						<input type="hidden" name="edd-purchase-var" value="needs-to-login"/>
					</p>

					<?php do_action( 'edd_checkout_login_fields_after' ); ?>

				</fieldset>
			<?php

			echo apply_filters( 'edd_auto_register_login_form', ob_get_clean() );
		}

		/**
		 * Settings
		 *
		 * @since 1.0
		*/
		function settings( $settings ) {

		  $edd_ar_settings = array(
				array(
					'id' => 'edd_auto_register_header',
					'name' => '<strong>' . __( 'Auto Register', 'edd-auto-register' ) . '</strong>',
					'type' => 'header'
				),
				array(
					'id' => 'edd_auto_register_enable_email',
					'name' => __( 'Send Login Details To Customer', 'edd-auto-register' ),
					'desc' => __( 'Sends an email to the customer with their login and password', 'edd-auto-register' ),
					'type' => 'checkbox',
					'std' => ''
				),
			);

			return array_merge( $settings, $edd_ar_settings );
		}		
	}
}

/**
 * Get everything running
 *
 * @since 1.0
 *
 * @access private
 * @return void
 */
function edd_auto_register_load() {
	$edd_auto_register = new EDD_Auto_Register();
}
add_action( 'plugins_loaded', 'edd_auto_register_load' );