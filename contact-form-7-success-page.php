<?php
/**
 * Plugin Name: Contact Form 7 - Success page
 * Description: Allows to display to the user that just send a contact form. A couple informations about it's request.
 * Author: Eoxia
 * Author URI: http://www.eoxia.com/
 * Version: 1.0.0.0
 *
 * @package Eoxia WPCF7
 */

/**
 * Check if there is a session already started.
 *
 * If not start a new session. If already exists read data from contact form and create shortcode for data display anywhere we want
 */
function eo_wpcf7_check_session() {
	/** Load translation for the plugin */
	load_plugin_textdomain( 'eo_wpcf7_redirect_success_page', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

	/** Checik if there is a session already started. If not start a new one. If already started read stored datas */
	if ( ! session_id() ) {
		session_start();
	} elseif ( ! empty( $_SESSION ) && ! empty( $_SESSION['eo_wpcf7_last_sended_email'] ) ) {
		add_shortcode( 'eo_wpcf7_message', function ( $attrs ) {
			return $_SESSION['eo_wpcf7_last_sended_email'][ $attrs['field'] ];
		} );
	}
}
add_action( 'init', 'eo_wpcf7_check_session', 1 );

/**
 * Function allowing to save posted data into a Session var and
 *
 * @param  Object $current_form Current sended form.
 */
function eo_wpcf7_mail_sent( $current_form ) {
	/** Get the page to redirect the user to from database regarding the form settings */
	$success_page = get_post_meta( $current_form->id(), '_eo_wpcf7_after_save_contact_form_page', true );
	$submission = WPCF7_Submission::get_instance();
	if ( $submission ) {
		$_SESSION['eo_wpcf7_last_sended_email'] = $submission->get_posted_data();
	}
	if ( ! empty( $success_page ) ) {
		wp_redirect( get_permalink( $success_page ) );
		die();
	}
}
add_action( 'wpcf7_mail_sent', 'eo_wpcf7_mail_sent' );

/**
 * Disable contact form 7 js functions. This will disable direct checking on fields that are required.
 */
add_filter( 'wpcf7_load_js', '__return_false' );

/**
 * Add a tab to configure the redirect page
 *
 * @param array $panels The list of available tabs for contact form settings.
 */
function _eo_wpcf7_add_setting_tab( $panels ) {
	$panels['redirect-panel'] = array( 'title' => __( 'Redirect Settings', 'eo_wpcf7_redirect_success_page' ), 'callback' => '_eo_wpcf7_setting_tab_content' );

	return $panels;
}
add_action( 'wpcf7_editor_panels', '_eo_wpcf7_add_setting_tab' );

/**
 * Define the html output for the setting tab allowing to chose the page where the customer will be redirect to
 *
 * @param  Object $current_form The contact form currently being edited.
 */
function _eo_wpcf7_setting_tab_content( $current_form ) {
	wp_nonce_field( '_eo_wpcf7_redirect_settings', '_eo_wpcf7_redirect_settings_nonce' );

	$dropdown_options = array(
		'echo'							=> 0,
		'name'							=> '_eo_wpcf7_redirect_page_id',
		'show_option_none'	=> '--',
		'option_none_value'	=> '0',
		'selected'					=> get_post_meta( $current_form->id(), '_eo_wpcf7_after_save_contact_form_page', true ),
	);

	echo '<fieldset><legend>' . esc_html_e( 'Select a page to redirect to on successful form submission.', 'eo_wpcf7_redirect_success_page' ) . '</legend>' . wp_dropdown_pages( $dropdown_options ) . '</fieldset>'; // WPCS: XSS ok.
}

/**
 * Save the page where to redirect the user after a contact form successfully sended
 *
 * @param  Object $contact_form Current form in edition mode to associate the page where to redirect.
 */
function _eo_wpcf7_save_redirect_settings( $contact_form ) {
	/** Check if the user came from the right page for saving by checking nonce value */
	if ( ! wp_verify_nonce( $_POST['_eo_wpcf7_redirect_settings_nonce'], '_eo_wpcf7_redirect_settings' ) ) {
		return;
	}

	if ( ! isset( $_POST ) || empty( $_POST ) || ! isset( $_POST['_eo_wpcf7_redirect_page_id'] ) || ! is_int( (int) $_POST['_eo_wpcf7_redirect_page_id'] ) ) {
		return;
	} else {
		/** Save the page id where the user will be redirect after a form successfyly send */
		update_post_meta( $contact_form->id(), '_eo_wpcf7_after_save_contact_form_page', $_POST['_eo_wpcf7_redirect_page_id'] );
	}
}
add_action( 'wpcf7_after_save', '_eo_wpcf7_save_redirect_settings' );
