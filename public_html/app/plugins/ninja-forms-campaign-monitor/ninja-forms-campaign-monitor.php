<?php
/*
Plugin Name: Ninja Forms - Campaign Monitor
Plugin URL: http://wpninjas.com/downloads/campaign-monitor
Description: Sign users up for your Campaign Monitor newsletter when submitting Ninja Forms
Version: 1.0.2
Author: Pippin Williamson
Author URI: http://pippinsplugins.com
Contributors: Pippin Williamson
*/


/**
 * Plugin text domain
 *
 * @since       1.0
 * @return      void
 */
function ninja_forms_cm_textdomain() {

	// Set filter for plugin's languages directory
	$edd_lang_dir = dirname( plugin_basename( __FILE__ ) ) . '/languages/';
	$edd_lang_dir = apply_filters( 'ninja_forms_cm_languages_directory', $edd_lang_dir );

	// Load the translations
	load_plugin_textdomain( 'ninja-forms-cm', false, $edd_lang_dir );
}
add_action( 'init', 'ninja_forms_cm_textdomain' );


/**
 * Add the Campaign Monitor tab to the Plugin Settings screen
 *
 * @since       1.0
 * @return      void
 */

function ninja_forms_cm_add_tab() {

	if ( ! function_exists( 'ninja_forms_register_tab_metabox_options' ) )
		return;

	$tab_args              = array(
		'name'             => 'Campaign Monitor',
		'page'             => 'ninja-forms-settings',
		'display_function' => '',
		'save_function'    => 'ninja_forms_save_license_settings',
	);
	ninja_forms_register_tab( 'campaign_monitor', $tab_args );

}
add_action( 'admin_init', 'ninja_forms_cm_add_tab' );


/**
 * PRegister the settings in the Campaign Monitor Tab
 *
 * @since       1.0
 * @return      void
 */
function ninja_forms_cm_add_plugin_settings() {

	if ( ! function_exists( 'ninja_forms_register_tab_metabox_options' ) )
		return;

	$mc_args = array(
		'page'     => 'ninja-forms-settings',
		'tab'      => 'campaign_monitor',
		'slug'     => 'campaign_monitor',
		'title'    => __( ' Campaign Monitor', 'ninja-forms-cm' ),
		'settings' => array(
			array(
				'name' => 'ninja_forms_cm_api',
				'label' => __( 'Campaign Monitor API Key', 'ninja-forms-cm' ),
				'desc' => __( 'Enter your Campaign Monitor API key. This can be found under your Account Settings.', 'ninja-forms-cm' ),
				'type' => 'text',
				'size' => 'regular'
			),
			array(
				'name' => 'ninja_forms_cm_client',
				'label' => __( 'Campaign Monitor Client ID', 'ninja-forms-cm' ),
				'desc' => __( 'Enter your Campaign Monitor Client ID. The ID can be found in the Client Settings page of the client.', 'ninja-forms-cm' ),
				'type' => 'text',
				'size' => 'regular'
			)
		)
	);
	ninja_forms_register_tab_metabox( $mc_args );
}
add_action( 'admin_init', 'ninja_forms_cm_add_plugin_settings', 100 );


/**
 * Register the form-specific settings
 *
 * @since       1.0
 * @return      void
 */
function ninja_forms_cm_add_form_settings() {

	if ( ! function_exists( 'ninja_forms_register_tab_metabox_options' ) )
		return;

	$args = array();
	$args['page'] = 'ninja-forms';
	$args['tab']  = 'form_settings';
	$args['slug'] = 'basic_settings';
	$args['settings'] = array(
		array(
			'name'      => 'campaign_monitor_signup_form',
			'type'      => 'checkbox',
			'label'     => __( 'Campaign Monitor', 'ninja-forms-cm' ),
			'desc'      => __( 'Enable Campaign Monitor signup for this form?', 'ninja-forms-cm' ),
			'help_text' => __( 'This will cause all email fields in this form to be sent to Campaign Monitor', 'ninja-forms-cm' ),
		),
		array(
			'name'    => 'ninja_forms_cm_list',
			'label'   => __( 'Choose a list', 'ninja-forms-cm' ),
			'desc'    => __( 'Select the list you wish to subscribe users to', 'ninja-forms-cm' ),
			'type'    => 'select',
			'options' => ninja_forms_cm_get_campaign_monitor_lists()
		)
	);
	ninja_forms_register_tab_metabox_options( $args );

}
add_action( 'admin_init', 'ninja_forms_cm_add_form_settings', 100 );


/**
 * Retrieve an array of Campaign Monitor lists
 *
 * @since       1.0
 * @return      array
 */
function ninja_forms_cm_get_campaign_monitor_lists() {

	global $pagenow, $edd_settings_page;

	if ( ! isset( $_GET['page'] ) || ! isset( $_GET['tab'] ) || $_GET['page'] != 'ninja-forms' || $_GET['tab'] != 'form_settings' )
		return;
	$options = get_option( "ninja_forms_settings" );

	if ( ! empty( $options['ninja_forms_cm_api'] ) && ! empty( $options['ninja_forms_cm_client'] ) ) {

		$lists = array();

		if( ! class_exists( 'CS_REST_Clients' ) )
			require_once( dirname( __FILE__ ) . '/vendor/csrest_clients.php');

		$api    = new CS_REST_Clients( $options['ninja_forms_cm_client'], $options['ninja_forms_cm_api'] );
		$result = $api->get_lists();

		if( $result->was_successful() ) {
			foreach( $result->response as $list ) {
				$lists[] = array(
					'value' => $list->ListID,
					'name'  => $list->Name
				);
			}
			return $lists;
		}

	}

	return array();
}


/**
 * Subscribe an email address to a Campaign Monitor list
 *
 * @since       1.0
 * @return      bool
 */
function ninja_forms_cm_subscribe_email( $subscriber = array(), $list_id = '' ) {

	$options = get_option( "ninja_forms_settings" );

	if ( empty( $list_id ) || empty( $subscriber ) )
		return false;

	if( ! class_exists( 'CS_REST_Clients' ) )
			require_once( dirname( __FILE__ ) . '/vendor/csrest_subscribers.php');

	$api  = new CS_REST_Subscribers( $list_id, $options['ninja_forms_cm_api'] );
	$vars = array();
	$name = '';

	if ( ! empty( $subscriber['first_name'] ) )
		$name .= $subscriber['first_name'];

	if ( ! empty( $subscriber['last_name'] ) )
		$name .= ' ' . $subscriber['last_name'];

	$subscribe = $api->add( array(
		'EmailAddress' => $subscriber['email'],
		'Name'         => $name,
		'Resubscribe'  => true
	) );

	if( $subscribe->was_successful() ) {
		return true;
	}

	return false;
}


/**
 * Check for newsletter signups on form submission
 *
 * @since       1.0
 * @return      void
 */
function ninja_forms_cm_check_for_email_signup() {

	if ( ! function_exists( 'ninja_forms_register_tab_metabox_options' ) )
		return;

	global $ninja_forms_processing;

	$form = $ninja_forms_processing->get_all_form_settings();

	// Check if Campaign Monitor is enabled for this form
	if ( empty( $form['campaign_monitor_signup_form'] ) )
		return;

	//Get all the user submitted values
	$all_fields = $ninja_forms_processing->get_all_fields();

	if ( is_array( $all_fields ) ) { //Make sure $all_fields is an array.
		//Loop through each of our submitted values.
		$subscriber = array();
		foreach ( $all_fields as $field_id => $value ) {

			$field = $ninja_forms_processing->get_field_settings( $field_id );
			//echo '<pre>'; print_R( $field ); echo '</pre>'; exit;
			if ( ! empty( $field['data']['email'] ) && is_email( $value ) ) {
				$subscriber['email'] = $value;
			}

			if ( ! empty( $field['data']['first_name'] ) ) {
				$subscriber['first_name'] = $value;
			}

			if ( ! empty( $field['data']['last_name'] ) ) {
				$subscriber['last_name'] = $value;
			}

		}
		if ( ! empty( $subscriber ) ) {
			ninja_forms_cm_subscribe_email( $subscriber, $form['ninja_forms_cm_list'] );
		}
	}
}


/**
 * Connect our signup check to form processing
 *
 * @since       1.0
 * @return      void
 */
function ninja_forms_cm_hook_into_processing() {
	add_action( 'ninja_forms_process', 'ninja_forms_cm_check_for_email_signup' );
}
add_action( 'init', 'ninja_forms_cm_hook_into_processing' );


/**
 * Plugin Updater / licensing
 *
 * @since       1.0.1
 * @return      void
 */

function ninja_forms_cm_extension_setup_license() {
    if ( class_exists( 'NF_Extension_Updater' ) ) {
        $NF_Extension_Updater = new NF_Extension_Updater( 'Campaign Monitor', '1.0.2', 'Pippin Williamson', __FILE__, 'campaign_monitor' );
    }
}
add_action( 'admin_init', 'ninja_forms_cm_extension_setup_license' );