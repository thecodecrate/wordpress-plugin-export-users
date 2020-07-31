<?php
/**
 * Class CSV
 *
 * @package User_Export_With_Their_Meta
 */
use UserExportWithMeta\CSV;

/**
 * Test CSV class.
 */
class TestCSV extends WP_UnitTestCase {

	/**
	 * The CSV instance.
	 *
	 * @var CSV
	 */
	public $csv;

	/**
	 * BOM constant.
	 *
	 * @var string
	 */
	public $bom = "\xEF\xBB\xBF";

	/**
	 * Init objects for each test.
	 *
	 * @return void
	 */
	public function setUp() {
		/** Call parent's code. */
		parent::setUp();

		/** Instantiate class. */
		$columns   = array( 'first_name', 'last_name', 'memo' );
		$this->csv = ( new CSV() )
			->set_filename( 'php://memory' )
			->set_columns( $columns )
			->set_allowlist( $columns );

		/** Clear cache */
		global $wp_roles;
		$wp_roles = null;
	}

	/**
	 * Test if we can generate a simple normal CSV.
	 */
	public function test_csv() {
		/** Data. */
		$data1 = array(
			'first_name' => 'John',
			'last_name'  => 'Smith',
			'memo'       => 'Some text.',
		);
		$data2 = array(
			'first_name' => 'Sam',
			'last_name'  => 'Lloyd',
			'memo'       => 'Another person.',
		);

		/** Expected. */
		$expected  = CSV::BOM;
		$expected .= "first_name,last_name,memo\n";
		$expected .= "{$data1['first_name']},{$data1['last_name']},\"{$data1['memo']}\"\n";
		$expected .= "{$data2['first_name']},{$data2['last_name']},\"{$data2['memo']}\"\n";

		/** Generate CSV */
		$this->csv->write( array( $data1, $data2 ) );

		/** Test. */
		$result = $this->get_csv_from_memory();
		$this->assertEquals( $expected, $result );
	}

	/**
	 * Test missing columns on some records.
	 */
	public function test_missing_columns() {
		/** Data. */
		$data1 = array(
			'first_name' => 'John',
			'last_name'  => 'Smith',
			'memo'       => 'Some text.',
		);
		$data2 = array(
			'first_name' => 'Sam',
			'memo'       => 'Another person.',
		);

		/** Expected. */
		$expected  = CSV::BOM;
		$expected .= "first_name,last_name,memo\n";
		$expected .= "{$data1['first_name']},{$data1['last_name']},\"{$data1['memo']}\"\n";
		$expected .= "{$data2['first_name']},,\"{$data2['memo']}\"\n";

		/** Generate CSV */
		$this->csv->write( array( $data1, $data2 ) );

		/** Test. */
		$result = $this->get_csv_from_memory();
		$this->assertEquals( $expected, $result );
	}

	/**
	 * Test additional columns on some records.
	 */
	public function test_additional_columns() {
		/** Data. */
		$data1 = array(
			'first_name' => 'John',
			'last_name'  => 'Smith',
			'memo'       => 'Some text.',
		);
		$data2 = array(
			'first_name'               => 'Sam',
			'last_name'                => 'Lloyd',
			'memo'                     => 'Another person.',
			'column_that_doesnt_exist' => 'Something.',
		);

		/** Expected. */
		$expected  = CSV::BOM;
		$expected .= "first_name,last_name,memo\n";
		$expected .= "{$data1['first_name']},{$data1['last_name']},\"{$data1['memo']}\"\n";
		$expected .= "{$data2['first_name']},{$data2['last_name']},\"{$data2['memo']}\"\n";

		/** Generate CSV */
		$this->csv->write( array( $data1, $data2 ) );

		/** Test. */
		$result = $this->get_csv_from_memory();
		$this->assertEquals( $expected, $result );
	}

	/**
	 * Test usorted values.
	 */
	public function test_unsorted() {
		/** Data. */
		$data1 = array(
			'first_name' => 'John',
			'last_name'  => 'Smith',
			'memo'       => 'Some text.',
		);
		$data2 = array(
			'memo'       => 'Another person.',
			'first_name' => 'Sam',
			'last_name'  => 'Lloyd',
		);

		/** Expected. */
		$expected  = CSV::BOM;
		$expected .= "first_name,last_name,memo\n";
		$expected .= "{$data1['first_name']},{$data1['last_name']},\"{$data1['memo']}\"\n";
		$expected .= "{$data2['first_name']},{$data2['last_name']},\"{$data2['memo']}\"\n";

		/** Generate CSV */
		$this->csv->write( array( $data1, $data2 ) );

		/** Test. */
		$result = $this->get_csv_from_memory();
		$this->assertEquals( $expected, $result );
	}

	/**
	 * Test empty values.
	 */
	public function test_empty_values() {
		/** Data. */
		$data1 = array(
			'first_name' => 'John',
			'last_name'  => '',
			'memo'       => 'Some text.',
		);
		$data2 = array(
			'first_name' => '',
			'last_name'  => 'Lloyd',
			'memo'       => 'Another person.',
		);

		/** Expected. */
		$expected  = CSV::BOM;
		$expected .= "first_name,last_name,memo\n";
		$expected .= "{$data1['first_name']},,\"{$data1['memo']}\"\n";
		$expected .= ",{$data2['last_name']},\"{$data2['memo']}\"\n";

		/** Generate CSV */
		$this->csv->write( array( $data1, $data2 ) );

		/** Test. */
		$result = $this->get_csv_from_memory();
		$this->assertEquals( $expected, $result );
	}

	/**
	 * Test custom column sorting.
	 */
	public function test_custom_sorting() {
		/** Set column sorting different than the value sorting. */
		$columns = array( 'memo', 'first_name', 'last_name' );
		$this->csv->set_columns( $columns );

		/** Data. */
		$data1 = array(
			'first_name' => 'John',
			'last_name'  => 'Smith',
			'memo'       => 'Some text.',
		);
		$data2 = array(
			'memo'       => 'Another person.',
			'first_name' => 'Sam',
			'last_name'  => 'Lloyd',
		);

		/** Expected. */
		$expected  = CSV::BOM;
		$expected .= "memo,first_name,last_name\n";
		$expected .= "\"{$data1['memo']}\",{$data1['first_name']},{$data1['last_name']}\n";
		$expected .= "\"{$data2['memo']}\",{$data2['first_name']},{$data2['last_name']}\n";

		/** Generate CSV */
		$this->csv->write( array( $data1, $data2 ) );

		/** Test. */
		$result = $this->get_csv_from_memory();
		$this->assertEquals( $expected, $result );
	}

	/**
	 * Test BOM disabled.
	 */
	public function test_bom_disabled() {
		/** Disable BOM. */
		$this->csv->disable_bom();

		/** Data. */
		$data1 = array(
			'first_name' => 'John',
			'last_name'  => 'Smith',
			'memo'       => 'Some text.',
		);
		$data2 = array(
			'first_name' => 'Sam',
			'last_name'  => 'Lloyd',
			'memo'       => 'Another person.',
		);

		/** Expected. */
		$expected  = '';
		$expected .= "first_name,last_name,memo\n";
		$expected .= "{$data1['first_name']},{$data1['last_name']},\"{$data1['memo']}\"\n";
		$expected .= "{$data2['first_name']},{$data2['last_name']},\"{$data2['memo']}\"\n";

		/** Generate CSV */
		$this->csv->write( array( $data1, $data2 ) );

		/** Test. */
		$result = $this->get_csv_from_memory();
		$this->assertEquals( $expected, $result );
	}

	/**
	 * Test UTF-8 on values.
	 */
	public function test_utf_values() {
		/** Data. */
		$data1 = array(
			'first_name' => 'John',
			'last_name'  => 'اختبار Téççã%& 테스트 测试',
			'memo'       => 'Some text.',
		);
		$data2 = array(
			'first_name' => 'Sam',
			'last_name'  => 'Lloyd',
			'memo'       => 'Another person.',
		);

		/** Expected. */
		$expected  = $this->bom;
		$expected .= "first_name,last_name,memo\n";
		$expected .= "{$data1['first_name']},\"{$data1['last_name']}\",\"{$data1['memo']}\"\n";
		$expected .= "{$data2['first_name']},{$data2['last_name']},\"{$data2['memo']}\"\n";

		/** Generate CSV */
		$this->csv->write( array( $data1, $data2 ) );

		/** Test. */
		$result = $this->get_csv_from_memory();
		$this->assertEquals( $expected, $result );
	}

	/**
	 * Test UTF-8 on columns.
	 */
	public function test_utf_columns() {
		/** Columns with an utf8 item. */
		$utf_str = 'اختبار Téççã%& 테스트 测试';
		$columns = array( 'first_name', $utf_str, 'memo' );
		$this->csv->set_columns( $columns )->set_allowlist( $columns );

		/** Data. */
		$data1 = array(
			'first_name' => 'John',
			$utf_str     => 'Smith',
			'memo'       => 'Some text.',
		);
		$data2 = array(
			'first_name' => 'Sam',
			$utf_str     => 'Lloyd',
			'memo'       => 'Another person.',
		);

		/** Expected. */
		$expected  = $this->bom;
		$expected .= "first_name,\"{$utf_str}\",memo\n";
		$expected .= "{$data1['first_name']},{$data1[$utf_str]},\"{$data1['memo']}\"\n";
		$expected .= "{$data2['first_name']},{$data2[$utf_str]},\"{$data2['memo']}\"\n";

		/** Generate CSV */
		$this->csv->write( array( $data1, $data2 ) );

		/** Test. */
		$result = $this->get_csv_from_memory();
		$this->assertEquals( $expected, $result );
	}

	/**
	 * Test predefined delimiter options.
	 */
	public function test_predefined_delimiters() {
		/** Set of tests. */
		$delimiter_tests = array(
			'comma'     => ',',
			'semicolon' => ';',
			'tab'       => "\t",
			'space'     => ' ',
			'|'         => '|', /** custom ascii. */
		);

		/** Data. */
		$columns = array( 'first_name', 'last_name', 'memo' );
		$data1   = array(
			'first_name' => 'John',
			'last_name'  => 'Smith',
			'memo'       => 'Some text.',
		);
		$data2   = array(
			'first_name' => 'Sam',
			'last_name'  => 'Lloyd',
			'memo'       => 'Another person.',
		);

		foreach ( $delimiter_tests as $delimiter_key => $delimiter_value ) {
			/** Create new CSV. */
			$csv = ( new CSV() )
				->set_filename( 'php://memory' )
				->set_columns( $columns )
				->set_allowlist( $columns );

			/** Set delimiter. */
			$csv->set_delimiter( $delimiter_key );

			/** Expected. */
			$expected  = CSV::BOM;
			$expected .= "first_name{$delimiter_value}last_name{$delimiter_value}memo\n";
			$expected .= "{$data1['first_name']}{$delimiter_value}{$data1['last_name']}{$delimiter_value}\"{$data1['memo']}\"\n";
			$expected .= "{$data2['first_name']}{$delimiter_value}{$data2['last_name']}{$delimiter_value}\"{$data2['memo']}\"\n";

			/** Generate CSV */
			$csv->write( array( $data1, $data2 ) );

			/** Test. */
			$result = $this->get_csv_from_memory( $csv );
			$this->assertEquals( $expected, $result );
		}
	}

	/**
	 * Test predefined enclosure options.
	 */
	public function test_predefined_enclosures() {
		/** Set of tests. */
		$enclosure_tests = array(
			'double-quote' => '"',
			'quote'        => "'",
			'|'            => '|',
		);

		/** Data. */
		$columns = array( 'first_name', 'last_name', 'memo' );
		$data1   = array(
			'first_name' => 'John',
			'last_name'  => 'Smith',
			'memo'       => 'Some text.',
		);
		$data2   = array(
			'first_name' => 'Sam',
			'last_name'  => 'Lloyd',
			'memo'       => 'Another person.',
		);

		foreach ( $enclosure_tests as $enclosure_key => $enclosure_value ) {
			/** Create new CSV. */
			$csv = ( new CSV() )
				->set_filename( 'php://memory' )
				->set_columns( $columns )
				->set_allowlist( $columns );

			/** Set enclosure. */
			$csv->set_enclosure( $enclosure_key );

			/** Expected. */
			$expected  = CSV::BOM;
			$expected .= "first_name,last_name,memo\n";
			$expected .= "{$data1['first_name']},{$data1['last_name']},{$enclosure_value}{$data1['memo']}{$enclosure_value}\n";
			$expected .= "{$data2['first_name']},{$data2['last_name']},{$enclosure_value}{$data2['memo']}{$enclosure_value}\n";

			/** Generate CSV */
			$csv->write( array( $data1, $data2 ) );

			/** Test. */
			$result = $this->get_csv_from_memory( $csv );
			$this->assertEquals( $expected, $result );
		}
	}

	/**
	 * Test if value contains the delimiter character.
	 * Example: delimiter is `;` and value is `I like;to;write;with;semicolons;`.
	 *
	 * Solution: Add "\" to these characters. PHP's core csv functions already do
	 * this by default, but it's good to double check it.
	 */
	public function test_value_has_delimiter_character() {
		/** Set delimiter to `,`. */
		$this->csv->set_delimiter( ',' );

		/** Data. */
		$data1 = array(
			'first_name' => 'John',
			'last_name'  => 'Sm,ith',
			'memo'       => 'Some, text.',
		);

		/** Expected. */
		$expected  = CSV::BOM;
		$expected .= "first_name,last_name,memo\n";
		$expected .= 'John,"Sm,ith","Some, text."' . "\n";

		/** Generate CSV */
		$this->csv->write( array( $data1 ) );

		/** Test. */
		$result = $this->get_csv_from_memory();
		$this->assertEquals( $expected, $result );
	}

	/**
	 * Test if value contains the enclosure character.
	 * Example: enclosure is `;` and value is `I like;to;write;with;semicolons;`.
	 *
	 * Solution: Add "\" to these characters. PHP's core csv functions already do
	 * this by default, but it's good to double check it.
	 */
	public function test_value_has_enclosure_character() {
		/** Set enclosure to `|`. */
		$this->csv->set_enclosure( '|' );

		/** Data. */
		$data1 = array(
			'first_name' => 'John|',
			'last_name'  => 'Smi|th',
			'memo'       => 'Som|e text.',
		);

		/** Expected. */
		$expected  = CSV::BOM;
		$expected .= "first_name,last_name,memo\n";
		$expected .= '|John|||,|Smi||th|,|Som||e text.|' . "\n";

		/** Generate CSV */
		$this->csv->write( array( $data1 ) );

		/** Test. */
		$result = $this->get_csv_from_memory();
		$this->assertEquals( $expected, $result );
	}

	/**
	 * Test if enclosure and delimiter is the same character.
	 * Example: enclosure is ";" and delimiter is also ";".
	 *
	 * Solution: Do not allow it. It should throw an error that will
	 * be displayed to the user.
	 */
	public function test_same_character_to_delimiter_and_enclosure() {
		/** Set delimiter and enclosure to `|`. */
		$this->csv->set_delimiter( '|' );
		$this->csv->set_enclosure( '|' );

		/** Data. */
		$data1 = array(
			'first_name' => 'John',
			'last_name'  => 'Smith',
			'memo'       => 'Some text.',
		);

		/** Test for exception. */
		$this->expectException( Exception::class );

		/** Generate CSV */
		$this->csv->write( array( $data1 ) );
	}

	/**
	 * Test the denylist.
	 *
	 * On this attack, we try to fetch passwords and other sensitive information.
	 *
	 * Solution: Use the denylist. Even if the column is in the allowlist the
	 * column will be blocked because the denylist has priority over the allowlist.
	 */
	public function test_block_sensitive_information() {
		/** Block 'memo' and an invalid column. */
		$denylist = array( 'memo', 'column_that_doesnt_exist' );
		$this->csv->set_denylist( $denylist );

		/** Data. */
		$data1 = array(
			'first_name' => 'John',
			'last_name'  => 'Smith',
			'memo'       => 'Some text.',
		);
		$data2 = array(
			'first_name' => 'Sam',
			'last_name'  => 'Lloyd',
			'memo'       => 'Another person.',
		);

		/** Expected. */
		$expected  = CSV::BOM;
		$expected .= "first_name,last_name\n";
		$expected .= "{$data1['first_name']},{$data1['last_name']}\n";
		$expected .= "{$data2['first_name']},{$data2['last_name']}\n";

		/** Generate CSV */
		$this->csv->write( array( $data1, $data2 ) );

		/** Test. */
		$result = $this->get_csv_from_memory();
		$this->assertEquals( $expected, $result );
	}

	/**
	 * Test denylist over allowlist.
	 *
	 * If a column is in both deny and allow lists, the deny list has priority.
	 *
	 * The reason why denylist prevails over allowlist is to protect sensitive
	 * data (eg. passwords) at all costs. If an attacker is able to manipulate
	 * the allowlist, the denylist will work as a last line of defense.
	 */
	public function test_denylist_over_allowlist() {
		/** Block 'memo'. */
		$allowlist = array( 'first_name', 'memo', 'column_that_doesnt_exist' );
		$denylist  = array( 'memo' );
		$this->csv
			->set_allowlist( $allowlist )
			->set_denylist( $denylist );

		/** Data. */
		$data1 = array(
			'first_name' => 'John',
			'last_name'  => 'Smith',
			'memo'       => 'Some text.',
		);
		$data2 = array(
			'first_name' => 'Sam',
			'last_name'  => 'Lloyd',
			'memo'       => 'Another person.',
		);

		/** Expected. */
		$expected  = CSV::BOM;
		$expected .= "first_name\n";
		$expected .= "{$data1['first_name']}\n";
		$expected .= "{$data2['first_name']}\n";

		/** Generate CSV */
		$this->csv->write( array( $data1, $data2 ) );

		/** Test. */
		$result = $this->get_csv_from_memory();
		$this->assertEquals( $expected, $result );
	}

	/**
	 * On this attack, a value contains a formula.
	 * Example: user saves their name as `=1+1`.
	 *
	 * Solution: Sanitize values with `sanitize_value( $value )`.
	 */
	public function test_attack_formula_injection_value() {
		/** Data. */
		$data1 = array(
			'first_name' => 'John',
			'last_name'  => '+1+1',
			'memo'       => '-1+1 long string',
		);
		$data2 = array(
			'first_name' => 'Sam',
			'last_name'  => '=1+1',
			'memo'       => '@1+1 long string',
		);

		/** Expected. */
		$expected  = CSV::BOM;
		$expected .= "first_name,last_name,memo\n";
		$expected .= "{$data1['first_name']},`{$data1['last_name']},\"`{$data1['memo']}\"\n";
		$expected .= "{$data2['first_name']},`{$data2['last_name']},\"`{$data2['memo']}\"\n";

		/** Generate CSV */
		$this->csv->write( array( $data1, $data2 ) );

		/** Test. */
		$result = $this->get_csv_from_memory();
		$this->assertEquals( $expected, $result );
	}

	/**
	 * On this attack, a column that contains a formula exists on the database.
	 * Example: a column named `=1+1`.
	 * This can happen if a plugin allows the end-user create custom fields.
	 *
	 * Solution: Sanitize columns with `sanitize_value( $column )`.
	 */
	public function test_attack_formula_injection_column() {
		/** Set columns. */
		$columns = array( 'first_name', '+1+1', '-1+1 long string', '=1+1', '@1+1 long string', 'last_name' );
		$this->csv
			->set_columns( $columns )
			->set_allowlist( $columns );

		/** Data. */
		$data1 = array(
			'first_name'       => 'John',
			'+1+1'             => 'Smith',
			'-1+1 long string' => 'Some text.',
			'=1+1'             => 'Lloyd',
			'@1+1 long string' => 'Another person.',
		);
		$data2 = array(
			'last_name' => 'Sam',
		);

		/** Expected. */
		$expected  = CSV::BOM;
		$expected .= 'first_name,`+1+1,"`-1+1 long string",`=1+1,"`@1+1 long string",last_name' . "\n";
		$expected .= "{$data1['first_name']},{$data1['+1+1']},\"{$data1['-1+1 long string']}\",{$data1['=1+1']},\"{$data1['@1+1 long string']}\",\n";
		$expected .= ",,,,,{$data2['last_name']}\n";

		/** Generate CSV */
		$this->csv->write( array( $data1, $data2 ) );

		/** Test. */
		$result = $this->get_csv_from_memory();
		$this->assertEquals( $expected, $result );
	}

	/**
	 * Test the allowlist.
	 *
	 * On this attack, it is requested a column that doesn't exist on database and that has
	 * a formula. Example: `":";-3+3+cmd|\' /C calc\'!D2`.
	 * Once the victim opens it on Calc/Excel using a semicolon as the delimiter, the
	 * formula `-3+3+cmd|\' /C calc\'!D2` is executed.
	 *
	 * Solution: Use allowlist to filter real column names.
	 */
	public function test_attack_formula_injection_fake_column() {
		/** Injected formula column. */
		$injection_code = '":";-3+3+cmd|\' /C calc\'!D2';
		$columns        = array( 'first_name', 'last_name', 'memo', $injection_code );
		$this->csv->set_columns( $columns );

		/** Allowlist. */
		$allowlist = array( 'first_name', 'last_name', 'memo' );
		$this->csv->set_allowlist( $allowlist );

		/** Data. */
		$data1 = array(
			'first_name' => 'John',
			'last_name'  => 'Smith',
			'memo'       => 'Some text.',
		);

		/** Expected. */
		$expected  = $this->bom;
		$expected .= "first_name,last_name,memo\n";
		$expected .= "{$data1['first_name']},{$data1['last_name']},\"{$data1['memo']}\"\n";

		/** Generate CSV */
		$this->csv->write( array( $data1 ) );

		/** Test. */
		$result = $this->get_csv_from_memory();
		$this->assertEquals( $expected, $result );
	}

	/**
	 * On this attack, we use a `=` as a delimiter.
	 * Any value or column name that contains a formula will be executed.
	 * Example: '1+1'.
	 *
	 * Solution: Do not allow `=` as delimiter.
	 */
	public function test_attack_formula_injection_delimiter_equal() {
		/** Test for exception. */
		$this->expectException( Exception::class );

		/** Set delimiter to `=`. */
		$this->csv->set_delimiter( '=' );
	}

	/**
	 * On this attack, we use a `-` as a delimiter.
	 * Any value or column name that contains a formula will be executed.
	 * Example: '1+1'.
	 *
	 * Solution: Do not allow `-` as delimiter.
	 */
	public function test_attack_formula_injection_delimiter_minus() {
		/** Test for exception. */
		$this->expectException( Exception::class );

		/** Set delimiter to `-`. */
		$this->csv->set_delimiter( '-' );
	}

	/**
	 * On this attack, we use a `+` as a delimiter.
	 * Any value or column name that contains a formula will be executed.
	 * Example: '1+1'.
	 *
	 * Solution: Do not allow `+` as delimiter.
	 */
	public function test_attack_formula_injection_delimiter_plus() {
		/** Test for exception. */
		$this->expectException( Exception::class );

		/** Set delimiter to `+`. */
		$this->csv->set_delimiter( '+' );
	}

	/**
	 * On this attack, we use a `@` as a delimiter.
	 * Any value or column name that contains a formula will be executed.
	 * Example: '1+1'.
	 *
	 * Solution: Do not allow `@` as delimiter.
	 */
	public function test_attack_formula_injection_delimiter_at() {
		/** Test for exception. */
		$this->expectException( Exception::class );

		/** Set delimiter to `@`. */
		$this->csv->set_delimiter( '@' );
	}

	/**
	 * On this attack, we use a `=` as a enclosure.
	 * Any value or column name that contains a formula will be executed.
	 * Example: '1+1'.
	 *
	 * Solution: Do not allow `=` as enclosure.
	 */
	public function test_attack_formula_injection_enclosure_equal() {
		/** Test for exception. */
		$this->expectException( Exception::class );

		/** Set delimiter to `=`. */
		$this->csv->set_enclosure( '=' );
	}

	/**
	 * On this attack, we use a `+` as a enclosure.
	 * Any value or column name that contains a formula will be executed.
	 * Example: '1+1'.
	 *
	 * Solution: Do not allow `+` as enclosure.
	 */
	public function test_attack_formula_injection_enclosure_plus() {
		/** Test for exception. */
		$this->expectException( Exception::class );

		/** Set delimiter to `+`. */
		$this->csv->set_enclosure( '+' );
	}

	/**
	 * On this attack, we use a `-` as a enclosure.
	 * Any value or column name that contains a formula will be executed.
	 * Example: '1+1'.
	 *
	 * Solution: Do not allow `-` as enclosure.
	 */
	public function test_attack_formula_injection_enclosure_minus() {
		/** Test for exception. */
		$this->expectException( Exception::class );

		/** Set delimiter to `-`. */
		$this->csv->set_enclosure( '-' );
	}

	/**
	 * On this attack, we use a `@` as a enclosure.
	 * Any value or column name that contains a formula will be executed.
	 * Example: '1+1'.
	 *
	 * Solution: Do not allow `@` as enclosure.
	 */
	public function test_attack_formula_injection_enclosure_at() {
		/** Test for exception. */
		$this->expectException( Exception::class );

		/** Set delimiter to `@`. */
		$this->csv->set_enclosure( '@' );
	}

	/**
	 * Get CSV from memory.
	 *
	 * @param CSV $csv The CSV object. Default: `$this->csv`.
	 *
	 * @return string
	 */
	public function get_csv_from_memory( $csv = null ) {
		if ( null === $csv ) {
			$csv = $this->csv;
		}

		rewind( $csv->hnd );
		$str = stream_get_contents( $csv->hnd );
		$csv->close();
		return $str;
	}

}
