<?php
/**
 * Class WPUsers
 *
 * @package User_Export_With_Their_Meta
 */
use UserExportWithMeta\WPUsers;

/**
 * Test WPUsers class.
 */
class TestWPUsers extends WP_UnitTestCase {

	/**
	 * Associative array with all generated users + standard users.
	 *
	 * @var array
	 */
	public static $users_data;

	/**
	 * Init objects for each test.
	 *
	 * @return void
	 */
	public function setUp() {
		/** Call parent's code. */
		parent::setUp();

		/** Instantiate WPUsers class. */
		$this->users = new WPUsers();

		/** Clear cache */
		global $wp_roles;
		$wp_roles = null;
	}

	/**
	 * Init class.
	 */
	public static function setUpBeforeClass() {
		/** Call parent's code */
		parent::setUpBeforeClass();

		/** Generate dummy users. */
		self::generate_dummy_users( 10 );

		/** Get a list of all current users (default admin + dummies). */
		self::$users_data = self::get_users();
	}

	public function test_sql_injection_attack_via_role_field() {
		/** Add a new role. We will fetch it through sql injection hack. */
		add_role( 'hacker_marker', '~~~H4ck3rM4rk3r~~~', array( 'read' => true, 'level_0' => true ) );

		$roles = $this->users->get_user_ids_by_roles( array( "administrator' || (1=1) || '" ) );
		$this->assertCount( 0, $roles );
	}

	/**
	 * Test `get_user_ids_by_roles()` with default users and roles.
	 *
	 * @return void
	 */
	public function test_get_user_ids_by_roles_default() {
		$count_users = count_users(); /** get summary using WP core method. */

		/** Admins: 1. */
		$ids = $this->users->get_user_ids_by_roles( array( 'administrator' ) );
		$this->assertCount( $count_users['avail_roles']['administrator'], $ids );

		/** Subscribers: 10. */
		$ids = $this->users->get_user_ids_by_roles( array( 'subscriber' ) );
		$this->assertCount( $count_users['avail_roles']['subscriber'], $ids );

		/** Subscribers + Admins: 11. */
		$ids = $this->users->get_user_ids_by_roles( array( 'administrator', 'subscriber' ) );
		$sum = $count_users['avail_roles']['administrator'] + $count_users['avail_roles']['subscriber'];
		$this->assertCount( $sum, $ids );
	}

	/**
	 * Test `get_user_ids_by_roles()` with custom role.
	 *
	 * @return void
	 */
	public function test_get_user_ids_by_roles_with_custom_role() {
		/** Add new custom role. */
		add_role( 'custom_role', 'Custom Subscriber', array( 'read' => true, 'level_0' => true ) );

		/** Generate 3 users. */
		$data = self::generate_dummy_users( 3, array( 'role' => 'custom_role' ) );

		/** Get data and check. */
		$ids   = $this->users->get_user_ids_by_roles( array( 'custom_role' ) );
		$users = $this->users->get_users_data( $ids );
		$this->assertCount( 3, $ids );
	}

	/**
	 * Test `get_user_ids()` with default users.
	 *
	 * @return void
	 */
	public function test_get_user_ids_default() {
		$default_ids = array_map(
			function ( $item ) {
				return $item['id'];
			},
			self::$users_data
		);
		$current_ids = $this->users->get_user_ids();
		$equals      = $this->arrays_are_similar( $current_ids, $default_ids );
		$this->assertTrue( $equals );
	}

	/**
	 * Test `get_user_ids()` with extra users.
	 *
	 * @return void
	 */
	public function test_get_user_ids_with_extra_users() {
		/** Generate new dummy users. */
		self::generate_dummy_users( 2 );

		/** Get users' ID from db using default WP functions. */
		$users_data = self::get_users();
		$default_ids = array_map(
			function ( $item ) {
				return $item['id'];
			},
			$users_data
		);

		/** Get IDs using our WPUser class and compare them. */
		$current_ids = $this->users->get_user_ids();
		$equals      = $this->arrays_are_similar( $current_ids, $default_ids );
		$this->assertTrue( $equals );
	}

	/**
	 * Test `get_all_columns()` with default columns.
	 *
	 * @return void
	 */
	public function test_get_all_columns_default() {
		global $wpdb;
		$default_columns = array(
			'ID', 'user_login', 'user_pass', 'user_nicename', 'user_email',
			'user_url', 'user_registered', 'user_activation_key', 'user_status',
			'display_name', 'admin_color', 'comment_shortcuts', 'description',
			'dismissed_wp_pointers', 'first_name', 'last_name', 'locale',
			'nickname', 'rich_editing', 'show_admin_bar_front',
			'show_welcome_panel', 'syntax_highlighting', 'use_ssl',
			"{$wpdb->prefix}capabilities", "{$wpdb->prefix}user_level"
		);
		$columns         = array_keys( $this->users->get_all_columns() );
		$equals          = $this->arrays_are_similar( $columns, $default_columns );
		$this->assertTrue( $equals );
	}

	/**
	 * Test `get_all_columns()` with extra columns.
	 *
	 * @return void
	 */
	public function test_get_all_columns_with_custom_columns() {
		global $wpdb;
		$default_columns = array(
			'ID', 'user_login', 'user_pass', 'user_nicename', 'user_email',
			'user_url', 'user_registered', 'user_activation_key', 'user_status',
			'display_name', 'admin_color', 'comment_shortcuts', 'description',
			'dismissed_wp_pointers', 'first_name', 'last_name', 'locale',
			'nickname', 'rich_editing', 'show_admin_bar_front',
			'show_welcome_panel', 'syntax_highlighting', 'use_ssl',
			"{$wpdb->prefix}capabilities", "{$wpdb->prefix}user_level",
			'my_custom_col1', 'my_custom_col2'
		);

		/** update last user and add 2 new custom columns to it. */
		$user_ids = get_users( array( 'fields' => 'ID' ) );
		sort( $user_ids );
		$user_id  = array_pop( $user_ids );
		update_user_meta( $user_id, 'my_custom_col1', 'Lorem ipsum dolor sit amet.' );
		update_user_meta( $user_id, 'my_custom_col2', 'Λορεμ ιπσθμ δολορ σιτ αμετ προ.' );

		/** compare. */
		$columns         = array_keys( $this->users->get_all_columns() );
		$equals          = $this->arrays_are_similar( $columns, $default_columns );
		$this->assertTrue( $equals );
	}

	/**
	 * Test `get_all_roles()` with default roles.
	 *
	 * @return void
	 */
	public function test_get_all_roles_default() {
		$default_roles = array(
			'administrator' => 'Administrator',
			'editor'        => 'Editor',
			'author'        => 'Author',
			'contributor'   => 'Contributor',
			'subscriber'    => 'Subscriber',
		);
		$roles         = $this->users->get_all_roles();
		$equals        = $this->arrays_are_similar( $roles, $default_roles );
		$this->assertTrue( $equals );
	}

	/**
	 * Test `get_all_roles()` with extra default roles.
	 *
	 * @return void
	 */
	public function test_get_all_roles_with_custom_roles() {
		/** Add new custom roles, remove some default ones. */
		add_role( 'custom_role', 'Custom Subscriber', array( 'read' => true, 'level_0' => true ) );
		add_role( 'custom_role_utf8', 'اختبار Téççã%&" 테스트 测试' );
		remove_role( 'contributor' );

		$expected_roles = array(
			'administrator'    => 'Administrator',
			'editor'           => 'Editor',
			'author'           => 'Author',
			'subscriber'       => 'Subscriber',
			'custom_role'      => 'Custom Subscriber',
			'custom_role_utf8' => 'اختبار Téççã%&" 테스트 测试',
		);
		$roles         = $this->users->get_all_roles();
		$equals        = $this->arrays_are_similar( $roles, $expected_roles );
		$this->assertTrue( $equals );
	}

	/**
	 * Test `arrays_are_similar()` with different sizes.
	 *
	 * @return void
	 */
	public function test_arrays_are_similar_different_sizes() {
		$ary_a  = array(
			'one'   => 'saldj',
			'two'   => '34fdjks',
			'three' => 'ksdfj21',
		);
		$ary_b  = array(
			'one'   => 'saldj',
			'two'   => '34fdjks',
		);
		$equals = $this->arrays_are_similar( $ary_a, $ary_b );
		$this->assertFalse( $equals );
	}

	/**
	 * Test `arrays_are_similar()` with different values.
	 *
	 * @return void
	 */
	public function test_arrays_are_similar_different_values() {
		$ary_a  = array(
			'one'   => 'saldj',
			'two'   => '34fdjks',
			'three' => 'ksdfj21',
		);
		$ary_b  = array(
			'one'   => 'yyysaldj',
			'two'   => 'yyy34fdjks',
			'three' => 'yyyksdfj21',
		);
		$equals = $this->arrays_are_similar( $ary_a, $ary_b );
		$this->assertFalse( $equals );
	}

	/**
	 * Test `arrays_are_similar()` with different keys.
	 *
	 * @return void
	 */
	public function test_arrays_are_similar_different_keys() {
		$ary_a  = array(
			'one'   => 'saldj',
			'two'   => '34fdjks',
			'three' => 'ksdfj21',
		);
		$ary_b  = array(
			'one'   => 'saldj',
			'ytwo'  => '34fdjks',
			'three' => 'ksdfj21',
		);
		$equals = $this->arrays_are_similar( $ary_a, $ary_b );
		$this->assertFalse( $equals );
	}

	/**
	 * Test `arrays_are_similar()` with different order.
	 *
	 * @return void
	 */
	public function test_arrays_are_similar_different_order() {
		$ary_a  = array(
			'one'   => 'saldj',
			'three' => 'ksdfj21',
			'two'   => '34fdjks',
		);
		$ary_b  = array(
			'one'   => 'saldj',
			'two'   => '34fdjks',
			'three' => 'ksdfj21',
		);
		$equals = $this->arrays_are_similar( $ary_a, $ary_b );
		$this->assertTrue( $equals );
	}

	/**
	 * Test `arrays_are_similar()` with utf8 values.
	 *
	 * @return void
	 */
	public function test_arrays_are_similar_utf8() {
		$ary_a  = array(
			'one'   => 'saldj',
			'two'   => 'اختبار Téççã%&" 테스트 测试',
			'three' => 'ksdfj21',
		);
		$ary_b  = array(
			'one'   => 'saldj',
			'two'   => 'اختبار Téççã%&" 테스트 测试',
			'three' => 'ksdfj21',
		);
		$equals = $this->arrays_are_similar( $ary_a, $ary_b );
		$this->assertTrue( $equals );
	}

	/**
	 * Test `arrays_are_similar()` with no keys (different).
	 *
	 * @return void
	 */
	public function test_arrays_are_similar_no_keys_different() {
		$ary_a  = array( 'saldj', '34fdjks', 'ksdfj21' );
		$ary_b  = array( 'yysaldj', 'yy34fdjks', 'ksdfj21' );
		$equals = $this->arrays_are_similar( $ary_a, $ary_b );
		$this->assertFalse( $equals );
	}

	/**
	 * Test `arrays_are_similar()` with no keys (equal).
	 *
	 * @return void
	 */
	public function test_arrays_are_similar_no_keys_equal() {
		$ary_a  = array( 'saldj', '34fdjks', 'ksdfj21' );
		$ary_b  = array( 'saldj', '34fdjks', 'ksdfj21' );
		$equals = $this->arrays_are_similar( $ary_a, $ary_b );
		$this->assertTrue( $equals );
	}


	/**
	 * Test `arrays_are_similar()` with different order (equal).
	 *
	 * @return void
	 */
	public function test_arrays_are_similar_different_order_equal() {
		$ary_a  = array( 'saldj', '34fdjks', 'ksdfj21' );
		$ary_b  = array( '34fdjks', 'saldj', 'ksdfj21' );
		$equals = $this->arrays_are_similar( $ary_a, $ary_b );
		$this->assertTrue( $equals );
	}


	/**
	 * Determine if two associative arrays are similar
	 *
	 * Both arrays must have the same indexes with identical values
	 * without respect to key ordering
	 *
	 * @param array $ary_a First array.
	 * @param array $ary_b Second array.
	 *
	 * @return bool
	 */
	private function arrays_are_similar( $ary_a, $ary_b ) {
		/** Guard Clause: If count doesn't match, return immediately. */
		if ( count( $ary_a ) !== count( $ary_b ) ) {
			return false;
		}

		/** If not associative, sort before comparing values. */
		if ( ! self::is_associative( $ary_a ) ) {
			sort( $ary_a );
			sort( $ary_b );
		} else {
			/* Guard Clause: If the indexes don't match, return immediately. */
			if ( count( array_diff_assoc( $ary_a, $ary_b ) ) ) {
				return false;
			}
		}

		/**
		 * We know that the indexes, but maybe not values, match.
		 * compare the values between the two arrays .
		 *
		 * Uses MD5 to compare UTF8.
		 */
		foreach ( $ary_a as $k => $v ) {
			if ( md5( $v ) !== md5( $ary_b[ $k ] ) ) {
				return false;
			}
		}

		/* We have identical indexes, and no unequal values. */
		return true;
	}


	/**
	 * Helper method for `arrays_are_similar()`.
	 *
	 * @param array $ary Array to be checked.
	 *
	 * @return bool
	 */
	private static function is_associative( $ary ) {
		if ( array() === $ary ) {
			return false;
		}
		return array_keys( $ary ) !== range( 0, count( $ary ) - 1 );
	}

	/**
	 * Generate fake dummy users.
	 *
	 * @param int $amount How many users to create.
	 * @param array $custom_fields Force custom fields. Ex. ['role' => 'administrator'].
	 *
	 * @return array An array with the generated users.
	 */
	private static function generate_dummy_users( $amount, $custom_fields = null ) {
		$faker = Faker\Factory::create();

		/** default is empty array. */
		if ( $custom_fields == null ) {
			$custom_fields = array();
		}

		/** create n users. */
		$result = array();
		$i      = 0;
		while ( $i < $amount ) {
			$data  = array(
				'user_login'  => $faker->userName,
				'user_email'  => $faker->email,
				'user_pass'   => '102030',
				'first_name'  => $faker->firstName,
				'last_name'   => $faker->lastName,
				'user_url'    => $faker->url,
				'description' => $faker->paragraph( 3 ),
				'role'        => isset( $custom_fields['role'] ) ? $custom_fields['role'] : 'subscriber',
			);

			/** Add to db. */
			$id = wp_insert_user( $data );

			/** Error has happened (probable name/email already exists), try again. */
			if ( ! is_int( $id ) ) {
				continue;
			}

			$data['id'] = $id;
			unset( $data['user_pass'] ); /** remove password field, as it will be ignored. */
			$result[] = $data; /** add to our array. */

			/** next. */
			$i ++;
		}
		return $result;
	}

	/**
	 * Get current users on db (and their meta).
	 *
	 * @return array An array of users (data/meta).
	 */
	private static function get_users() {
		$users = get_users( array( 'fields' => 'all_with_meta' ) );
		$current_users = array();
		foreach ( $users as $user ) {
			$current_users[] = array(
				'id'          => $user->data->ID,
				'user_login'  => $user->data->user_login,
				'user_email'  => $user->data->user_email,
				'first_name'  => self::get_user_meta_first( $user->data->ID, 'first_name' ),
				'last_name'   => self::get_user_meta_first( $user->data->ID, 'last_name' ),
				'user_url'    => $user->data->user_url,
				'description' => self::get_user_meta_first( $user->data->ID, 'description' ),
				'role'        => array_shift( $user->roles ),
			);
		}
		return $current_users;
	}

	/**
	 * Like `get_user_meta_first()`, but returns its actual content.
	 *
	 * @param int    $id The user id.
	 * @param string $field The field name.
	 *
	 * @return string
	 */
	private static function get_user_meta_first( $id, $field ) {
		$meta = get_user_meta( $id, $field );
		return array_shift( $meta );
	}
}
