<?php
namespace PostAuthorOptimization\UnitTests;

use PostAuthorOptimization;

class Test_Post_Author_Optimization extends \WC_Unit_Test_Case {

	protected static $current_user_id;

	/**
	 * Setup once before running tests.
	 *
	 * @param object $factory Factory object.
	 */
	public static function wpSetUpBeforeClass( $factory ) {
		self::$current_user_id = wp_create_user(
			'testuser1',
			'password1',
			'test@test1.com'
		);

		wp_set_current_user( self::$current_user_id );
	}

	public static function tearDownAfterClass() {
		wp_delete_user( self::$current_user_id );
		wp_set_current_user( 0 );

		parent::tearDownAfterClass();
	}

	/**
	 * Test that a newly created order will get post_author from current user ID.
	 */
	public function test_order_author() {
		$order = WC_Helper_Order::create_order( self::$current_user_id );

		// Manually update post_author field to match our test user instance.
		PostAuthorOptimization::set_post_author( $order->get_id(), self::$current_user_id );

		global $wpdb;

		$result = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT post_author FROM {$wpdb->prefix}posts WHERE ID = %s",
				$order->get_id()
			),
			ARRAY_A
		);

		$this->assertEquals( (int) $result[0]['post_author'], self::$current_user_id );
	}
}
