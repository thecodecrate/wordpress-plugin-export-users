<?php
/**
 * CSV class.
 *   Generates a CSV, given some columns and rows.
 *
 * @package UserExportWithMeta
 */

/** Our namespace. */
namespace UserExportWithMeta;

/** Can't access it directly. */
defined( 'ABSPATH' ) || exit;

/**
 * CSV class.
 */
class CSV {
	/**
	 * Outputs a CSV to the browser as an attachment.
	 *
	 * @param string[] $columns The columns to be exported.
	 *                          Ex. ['column_1', 'column_2', column_3'].
	 * @param array[]  $data An array of associative arrays.
	 *    $data = [[
	 *       'column_1' => 'value1',
	 *       'column_2' => 'value2',
	 *       'column_3' => 'value3',
	 *    ],[
	 *       'column_1' => 'value1',
	 *       'column_2' => 'value2',
	 *       'column_3' => 'value3',
	 *    ]].
	 * @param string   $delimiter_char [Optional] Custom delimiter (field separator).
	 *                                 NULL to use default (comma).
	 *                                 Pre-defined Options: 'comma', 'semicolon', 'tab', 'space'.
	 *                                 If value is other than the pre-defined ones, it will be used as
	 *                                 the custom char.
	 * @param string   $enclosure_char [Optional] Custom enclosure (string delimiter).
	 *                                 NULL to use default (double-quote).
	 *                                 Pre-defined Options: 'double-quote', 'quote'.
	 *                                 If value is other than the pre-defined ones, it will be used as
	 *                                 the custom char.
	 *
	 * @return void
	 */
	public function output_csv( $columns, $data, $delimiter_char = null, $enclosure_char = null, $utf8_without_bom ) {
		/** Set HTTP headers to download mode. */
		header( 'Content-Encoding: UTF-8' );
		header( 'Content-Type: text/csv; charset=UTF-8' );
		header( 'Content-Disposition: attachment; filename=' . date( 'Y-m-d-H-i' ) . '-users.csv' );
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );

		/** Open file for writing. */
		$hnd = fopen( 'php://output', 'w' );

		/** UTF8 BOM. */
		if ( false === $utf8_without_bom ) {
			fwrite( $hnd, "\xEF\xBB\xBF" );
		}

		/** Default delimiter / enclosure */
		if ( null === $delimiter_char ) {
			$delimiter_char = 'comma';
		}
		if ( null === $enclosure_char ) {
			$enclosure_char = 'double-quote';
		}

		/** Field Separator (delimiter). */
		$delimiter_options = [
			'comma'     => ',',
			'semicolon' => ';',
			'tab'       => "\t",
			'space'     => ' ',
		];
		/** Use a predefined value. If is not a predefined value, use it as the actual value.  */
		if ( array_key_exists( $delimiter_char, $delimiter_options ) ) {
			$delimiter_char = $delimiter_options[ $delimiter_char ];
		}

		/** Text Qualifier (enclosure). */
		$enclosure_options = [
			'double-quote' => '"',
			'quote'        => "'",
		];
		/** Use a predefined value. If is not a predefined value, use it as the actual value.  */
		if ( array_key_exists( $enclosure_char, $enclosure_options ) ) {
			$enclosure_char = $enclosure_options[ $enclosure_char ];
		}


		/** Output header. */
		fputcsv( $hnd, $columns, $delimiter_char, $enclosure_char );

		/** Output data. */
		foreach ( $data as $row ) {
			/** Add missing columns to the row. */
			$output_row = [];
			foreach ( $columns as $column ) {
				$output_row[ $column ] = array_key_exists( $column, $row ) ? $row[ $column ] : '';
			}

			/** Now that the user has the same columns as the header, outputs it. */
			fputcsv( $hnd, $output_row, $delimiter_char, $enclosure_char );
		}

		/** Close file and exit. */
		fclose( $hnd );
		exit();
	}
}
