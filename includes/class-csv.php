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

/** Aliases. */
use \Exception;

/**
 * CSV class.
 */
class CSV {
	/**
	 * Default delimiter (field separator).
	 *
	 * @var string
	 */
	const DEFAULT_DELIMITER = 'comma';

	/**
	 * Default enclosure (string delimiter).
	 *
	 * @var string
	 */
	const DEFAULT_ENCLOSURE = 'double-quote';

	/**
	 * BOM constant.
	 *
	 * @var string
	 */
	const BOM = "\xEF\xBB\xBF";

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
	 * Output a BOM header.
	 *
	 * @var boolean
	 */
	public $bom_enabled = true;

	/**
	 * The column names.
	 *
	 * @var string[]
	 */
	public $columns = array();

	/**
	 * Deny list.
	 *
	 * @var string[]
	 */
	public $denylist = array();

	/**
	 * Allow list.
	 *
	 * @var string[]
	 */
	public $allowlist = array();

	/**
	 * CSV headers.
	 * Used to automatically output only once the BOM+CSV headers on the first `write()` call.
	 *
	 * @var boolean
	 */
	private $headers_sent = false;

	/**
	 * Initialize default settings:
	 *   filename, bom, enclosure, delimiter - use the setters to customize them.
	 *
	 * @return void
	 */
	public function __construct() {
		$this
			->set_filename( null )
			->set_enclosure()
			->set_delimiter();
	}

	/**
	 * Output header.
	 * Auto-called on the first `write( $data )`.
	 *
	 * Once the first `write()` is called, it is not possible to change
	 * the settings anymore (columns, filename, bom, enclosure, delimiter).
	 *
	 * @throws Exception If delimiter and enclosure are the same.
	 *
	 */
	private function output_header() {
		/** Check if delimiter and enclosure are the same. */
		if ( $this->delimiter_char === $this->enclosure_char ) {
			throw new Exception( 'Delimiter and Enclosure are the same.' );
		}

		/** Open file for writing. */
		$this->hnd = fopen( $this->filename, 'w' );

		/** UTF8 BOM. */
		if ( $this->bom_enabled ) {
			fwrite( $this->hnd, self::BOM );
		}

		/** Allowlist. */
		$this->columns = array_intersect( $this->columns, $this->allowlist );

		/** Denylist. */
		$this->columns = array_diff( $this->columns, $this->denylist );

		/** Sanitize column names - do not change original string values, so columns can still be found on `write()` . */
		$columns = array_map( array( $this, 'sanitize_value' ), $this->columns );

		/** Output header. */
		fputcsv( $this->hnd, $columns, $this->delimiter_char, $this->enclosure_char );
	}

	/**
	 * Set the output filename.
	 *
	 * @param string $filename [OPTIONAL] Default: 'php://output'. The output file name.
	 *                         Use 'php://memory' to save the CSV on memory (useful for testing), or a
	 *                         filename to save it locally as a file.
	 *
	 * @return Self Return self object, to be used on method chain.
	 */
	public function set_filename( $filename ) {
		/** The output file name. Default: stream to the screen ('php://output'). */
		$this->filename = null === $filename ? 'php://output' : $filename;
		return $this;
	}

	/**
	 * Enable UTF-8's BOM character.
	 *
	 * @return Self Return self object, to be used on method chain.
	 */
	public function enable_bom() {
		$this->bom_enabled = true;
		return $this;
	}

	/**
	 * Disable UTF-8's BOM character.
	 *
	 * @return Self Return self object, to be used on method chain.
	 */
	public function disable_bom() {
		$this->bom_enabled = false;
		return $this;
	}

	/**
	 * Set columns.
	 *   If a column is not explicily defined on allowlist, it won't be exported.
	 *   If a column is on denylist, it won't be exported even if is on allowlist.
	 *
	 * @param string[] $columns The columns to be exported.
	 *
	 * @return Self Return self object, to be used on method chain.
	 */
	public function set_columns( $columns ) {
		$this->columns = $columns;
		return $this;
	}

	/**
	 * Set enclosure (string delimiter).
	 *
	 * @param string $enclosure_char  [OPTIONAL] Custom enclosure.
	 *                                DEFAULT............: 'double-quote'.
	 *                                PRE-DEFINED OPTIONS: 'double-quote', 'quote'.
	 *                                CUSTOM.............: If value is other than the
	 *                                pre-defined ones, it will be used as the custom char.
	 *
	 * @throws Exception On danger custom values (=+-).
	 *
	 * @return Self Return self object, to be used on method chain.
	 */
	public function set_enclosure( $enclosure_char = null ) {
		/** Force string type. */
		$enclosure_char = '' . $enclosure_char;

		/** Default value */
		if ( empty( $enclosure_char ) ) {
			$enclosure_char = self::DEFAULT_ENCLOSURE;
		}

		/** Text Qualifier (enclosure). */
		$predefined_options = array(
			'double-quote' => '"',
			'quote'        => "'",
		);

		/** Using a predefined value. */
		if ( array_key_exists( $enclosure_char, $predefined_options ) ) {
			$this->enclosure_char = $predefined_options[ $enclosure_char ];
			return $this;
		}

		/** [SECURITY] Custom value: "=+-@" are not allowed! */
		$regex = '/^[\=\+\-\@](.*)$/';
		if ( 1 === preg_match( $regex, $enclosure_char ) ) {
			throw new Exception( 'These enclosures are not allowed: =+-@' );
		}

		/** Using a custom value. */
		$this->enclosure_char = $enclosure_char;
		return $this;
	}

	/**
	 * Set delimiter (field separator).
	 *
	 * @param string $delimiter_char [OPTIONAL] Custom delimiter.
	 *                               DEFAULT............: 'comma'.
	 *                               PRE-DEFINED OPTIONS: 'comma', 'semicolon', 'tab', 'space'.
	 *                               CUSTOM.............: If value is other than the pre-defined
	 *                               ones, it will be used as the custom char.
	 * @throws Exception On danger custom values (=+-).
	 *
	 * @return Self Return self object, to be used on method chain.
	 */
	public function set_delimiter( $delimiter_char = null ) {
		/** Force string type. */
		$delimiter_char = '' . $delimiter_char;

		/** Default value */
		if ( empty( $delimiter_char ) ) {
			$delimiter_char = self::DEFAULT_DELIMITER;
		}

		/** Predefined options. */
		$predefined_options = array(
			'comma'     => ',',
			'semicolon' => ';',
			'tab'       => "\t",
			'space'     => ' ',
		);

		/** Using a predefined value. */
		if ( array_key_exists( $delimiter_char, $predefined_options ) ) {
			$this->delimiter_char = $predefined_options[ $delimiter_char ];
			return $this;
		}

		/** [SECURITY] Custom value: "=+-@" are not allowed! */
		$regex = '/^[\=\+\-\@](.*)$/';
		if ( 1 === preg_match( $regex, $delimiter_char ) ) {
			throw new Exception( 'These delimiters are not allowed: =+-@' );
		}

		/** Using a custom value. */
		$this->delimiter_char = $delimiter_char;
		return $this;
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
	 *
	 * @return Self Return self object, to be used on method chain.
	 */
	public function write( $data ) {
		/** Output header. */
		if ( ! $this->headers_sent ) {
			$this->output_header();
			$this->headers_sent = true; /** Disallow calling again on next write. */
		}

		/** Output data. */
		foreach ( $data as $row ) {
			/** Add missing columns to the row. */
			$output_row = array();
			foreach ( $this->columns as $column ) {
				$column_name  = $column;
				$output_row[] = array_key_exists( $column_name, $row ) ? $row[ $column_name ] : '';
			}

			/** Sanitize values in the row. */
			$output_row = array_map( array( $this, 'sanitize_value' ), $output_row );

			/** Now that the user has the same columns as the header, outputs it. */
			fputcsv( $this->hnd, $output_row, $this->delimiter_char, $this->enclosure_char );
		}

		return $this;
	}

	/**
	 * Close stream.
	 *
	 */
	public function close() {
		fclose( $this->hnd );
	}

	/**
	 * Denylist for columns.
	 * These columns won't be displayed even if they are on the allowlist.
	 * Ex.: [ 'user_pass', 'session_tokens' ].
	 *
	 * @param string[] $list The deny list.
	 *
	 * @return Self Return self object, to be used on method chain.
	 */
	public function set_denylist( $list ) {
		$this->denylist = $list;
		return $this;
	}

	/**
	 * Allowlist for columns.
	 * Only columns on this list can be displayed.
	 * If a column is in both deny and allow lists, it won't be displayed.
	 *
	 * @param string[] $list The allow list.
	 *
	 * @return Self Return self object, to be used on method chain.
	 */
	public function set_allowlist( $list ) {
		$this->allowlist = $list;
		return $this;
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
