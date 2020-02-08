<?php
/**
 * Class Main.
 *   This is the entrypoint for our plugin.
 *
 * TODO:
 * * [ ] Fix PHPMD/PHPCS issues on files;
 * * [ ] Create unit tests for files in the "includes" folder;
 * * [ ] Use custom SQL to get all columns;
 * * [ ] UTF8 (BOM) issue (see WordPress plugin's issues);
 *
 * @package UserExportWithMeta
 */

/** Our namespace. */
namespace UserExportWithMeta;

/** Aliases. */
use WPPluginFramework\Singleton;
use WPPluginFramework\WPPluginFramework;

/** Can't access this file directly. */
defined( 'ABSPATH' ) || exit;

/**
 * Main class for the plugin.
 */
class Main extends Singleton {

	/**
	 * Plugin's slug.
	 *
	 * @var string
	 */
	const SLUG = 'uewm';

	/**
	 * Class Composition: methods are on separated files, for code clarity.
	 */
	protected $service_users;
	protected $service_csv;
	protected $service_wp;


	/**
	 * Entry-point for the plugin.
	 *
	 * @return void
	 */
	public function __construct() {
		/** Class Composition: load modules. */
		$this->service_users = new WPUsers();
		$this->service_csv   = new CSV();
		$this->service_wp    = new WPPluginFramework( self::SLUG );

		/** Set menu. */
		$this->service_wp->set_menu(
			'users',                    // $root_menu
			'Export to CSV',            // $menu_title
			'Export Users to CSV',      // $page_title
			'list_users',               // $menu_capability
			$this->get_settings(),      // $settings_fields
			[ $this, 'save_callback' ], // $save_callback
			'Save and Export'           // $save_button_title
		);
	}

	/**
	 * Settings page fields definition.
	 *
	 * @return array. An associative array with field definition.
	 */
	public function get_settings() {
		$settings = [
			[
				'id'     => 'filters_section',
				'title'  => '',
				'fields' => [
					[
						'type'             => 'multiselect',
						'id'               => 'roles',
						'title'            => 'Roles',
						'class'            => 'select2',
						'options_callback' => function () {
							/** Fill "Roles" field. */
							$roles        = [];
							$roles_object = get_editable_roles();
							foreach ( $roles_object as $role_id => $role_object ) {
								$roles[ $role_id ] = $role_object['name'];
							}
							return $roles;
						},
					],
					[
						'type'             => 'multiselect',
						'id'               => 'columns',
						'title'            => 'Columns',
						'class'            => 'select2',
						'options_callback' => function () {
							/** Fill "Columns" field. */
							$columns = $this->service_users->get_all_user_field_names();
							$columns = array_combine( $columns, $columns ); /** Keys and values have the same content. */
							return $columns;
						},
					],
					[
						'type'  => 'checkbox',
						'id'    => 'use_custom_csv_settings',
						'title' => 'Use a custom field separator (,) and text qualifier (")',
					],
				],
			],
			[
				'id'     => 'custom_csv_settings',
				'title'  => 'Field Separator and Text Qualifier',
				'fields' => [
					[
						'type'       => 'select_with_text',
						'id'         => 'field_separator',
						'title'      => 'Field Separator',
						'class'      => 'width_150',
						'options'    => [
							'comma'     => 'Comma (,)',
							'semicolon' => 'Semicolon (;)',
							'tab'       => 'Tab (\t)',
							'space'     => 'Space ( )',
							'custom'    => 'Other',
						],
						'text_name'  => 'custom_field_separator',
						'text_title' => 'Define it here: ',
					],
					[
						'type'       => 'select_with_text',
						'id'         => 'text_qualifier',
						'title'      => 'Text Qualifier',
						'class'      => 'width_150',
						'options'    => [
							'double-quote' => 'Double-quote (")',
							'quote'        => 'Quote (\')',
							'custom'       => 'Other',
						],
						'text_name'  => 'custom_text_qualifier',
						'text_title' => 'Define it here: ',
					],
				],
			],
			// [
			// 	'id'     => 'advanced',
			// 	'title'  => 'Advanced Options',
			// 	'fields' => [
			// 		[
			// 			'type'  => 'checkbox',
			// 			'id'    => 'utf8_without_bom',
			// 			'title' => 'UTF-8 without BOM. <br><i>Check this if you are experiencing issues with weird characters at the beginning of the file.</i>',
			// 		],
			// 	],
			// ],
		];
		return $settings;
	}

	/**
	 * Called on form submit.
	 *
	 */
	public function save_callback() {
		/** Save fields. */
		$this->service_wp->update_options( $this->get_settings() );

		/** Generate CSV. */
		$roles               = get_option( self::SLUG . '_roles' );
		$roles               = $roles ? $roles : []; /** Empty = all. */
		$users               = get_users( [ 'role__in' => $roles ] );
		$columns             = get_option( self::SLUG . '_columns' );
		$has_custom_settings = get_option( self::SLUG . '_use_custom_csv_settings' );
		$delimiter           = get_option( self::SLUG . '_field_separator' );
		$custom_delimiter    = get_option( self::SLUG . '_custom_field_separator' );
		$enclosure           = get_option( self::SLUG . '_text_qualifier' );
		$custom_enclosure    = get_option( self::SLUG . '_custom_text_qualifier' );
		// $utf8_without_bom    = get_option( self::SLUG . '_utf8_without_bom' );

		/** Convert "yes"/"no" to boolean. Compatibility: data saved with version 0.1.9 and below is "1"/"0". */
		$has_custom_settings = 'yes' === $has_custom_settings || '1' === $has_custom_settings;
		// $utf8_without_bom    = 'yes' === $utf8_without_bom || '1' === $utf8_without_bom;

		/** Get (1) an assoc array with users data, (2) all column names. */
		$all_column_names = [];
		$all_user_rows    = [];
		foreach ( $users as $user ) {
			$fields = $this->service_users->get_user_fields( $user );
			foreach ( array_keys( $fields ) as $key ) {
				$all_column_names[ $key ] = '';
			}
			$all_user_rows[] = $fields;
		}
		$all_column_names = array_keys( $all_column_names );

		/** Selected columns (Empty = all). */
		$columns = $columns ? $columns : $all_column_names;

		/** Do not export these. */
		$columns = array_diff(
			$columns,
			[
				'user_pass',
				'user_activation_key',
				'session_tokens',
				'wp_user-settings',
				'wp_user-settings-time',
				'wp_capabilities',
				'community-events-location',
			]
		);

		/**
		 * Delimiter / Enclosure.
		 */
		$delimiter_char = null;
		$enclosure_char = null;
		if ( $has_custom_settings ) {
			$delimiter_char = 'custom' === $delimiter ? $custom_delimiter : $delimiter;
			$enclosure_char = 'custom' === $enclosure ? $custom_enclosure : $enclosure;
		}

		/**
		 * Output to browser and quit.
		 */
		$this->service_csv->output_csv( $columns, $all_user_rows, $delimiter_char, $enclosure_char, true );
	}

}
