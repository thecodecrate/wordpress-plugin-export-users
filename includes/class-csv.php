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
	 * Stream handler.
	 *
	 * @var int
	 */
	public $hnd;


	/**
	 * The delimiter (field separator).
	 *
	 * @var string
	 */
	public $delimiter_char;

	/**
	 * The enclosure (string delimiter).
	 *
	 * @var string
	 */
	public $enclosure_char;

	/**
	 * The column names.
	 *
	 * @var string[]
	 */
	public $columns;

	/**
	 * Outputs a CSV to the browser as an attachment.
	 *
	 * @param string[] $columns          The columns to be exported.
	 *                                   Ex. ['column_1', 'column_2', column_3'].
	 * @param string   $delimiter_char   [Optional] Custom delimiter (field separator).
	 *                                   NULL to use default (comma).
	 *                                   Pre-defined Options: 'comma', 'semicolon', 'tab', 'space'.
	 *                                   If value is other than the pre-defined ones, it will be used as
	 *                                   the custom char.
	 * @param string   $enclosure_char   [Optional] Custom enclosure (string delimiter).
	 *                                   NULL to use default (double-quote).
	 *                                   Pre-defined Options: 'double-quote', 'quote'.
	 *                                   If value is other than the pre-defined ones, it will be used as
	 *                                   the custom char.
	 * @param bool     $utf8_without_bom If true, output BOM to the beginning of the file.
	 *
	 * @return void
	 */
	public function __construct( $columns, $delimiter_char = null, $enclosure_char = null, $utf8_without_bom ) {
		/** Set HTTP headers to download mode. */
		header( 'Content-Encoding: UTF-8' );
		header( 'Content-Type: text/csv; charset=UTF-8' );
		header( 'Content-Disposition: attachment; filename=' . date( 'Y-m-d-H-i' ) . '-users.csv' );
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );

		/** Open file for writing. */
		$this->hnd = fopen( 'php://output', 'w' );

		/** UTF8 BOM. */
		if ( false === $utf8_without_bom ) {
			fwrite( $this->hnd, "\xEF\xBB\xBF" );
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
		fputcsv( $this->hnd, $columns, $delimiter_char, $enclosure_char );

		/** Store args. */
		$this->columns        = $columns;
		$this->delimiter_char = $delimiter_char;
		$this->enclosure_char = $enclosure_char;
	}

	/**
	 * Output data (a 2D matrix) to the CSV stream.
	 *
	 * @param array[] $data An array of associative arrays.
	 *    $data = [[
	 *       'column_1' => 'value1',
	 *       'column_2' => 'value2',
	 *       'column_3' => 'value3',
	 *    ],[
	 *       'column_1' => 'value1',
	 *       'column_2' => 'value2',
	 *       'column_3' => 'value3',
	 *    ]].
	 */
	public function write( $data ) {
		/** Output data. */
		foreach ( $data as $row ) {
			/** Add missing columns to the row. */
			$output_row = array();
			foreach ( $this->columns as $column ) {
				$output_row[ $column ] = array_key_exists( $column, $row ) ? $row[ $column ] : '';
			}

			/** Sanitize values in the row. */
			$output_row = array_map( array( $this, 'sanitize_value' ), $output_row );

			/** Now that the user has the same columns as the header, outputs it. */
			fputcsv( $this->hnd, $output_row, $this->delimiter_char, $this->enclosure_char );
		}
	}

	/**
	 * Close stream and QUIT the script.
	 *
	 */
	public function close() {
		/** Close file and exit. */
		fclose( $this->hnd );
		exit();
	}


	/**
	 * Sanitize value, removing vulnerabilities on values.
	 *
	 * @param string $value The string to be sanitized.
	 * @return string The `$value` after being sanitized.
	 */
	private function sanitize_value( $value ) {
		/**
		 * "Formula Injection" Mitigation:
		 *
		 * Ensure values doesn't start with: `=+-@`
		 * https://owasp.org/www-community/attacks/CSV_Injection
		 */
		$regex = '/^[\=\+\-\@](.*)$/';
		/** "Lastly, as a best security practice measure, consider stripping all trailling white spaces where possible,
		 * and limiting all client-supplied data to alpha-numeric characters." */
		$value = trim( $value ); /** Strip trailing white spaces - we can't restrict to alpha-numeric due to the multi-language support. */
		if ( 1 === preg_match( $regex, $value ) ) {
			/** Fix: "When generating spreadsheets, fields that begin with any of the above symbols
			 * should be prepended by a single quote or apostrophe (') character.
			 * Microsoft Excel will preserve data integrity by hiding this character when
			 * rendering the spreadsheet.." */
			$value = '`' . $value;
		}

		/** Return sanitized `$value`. */
		return $value;
	}
}
