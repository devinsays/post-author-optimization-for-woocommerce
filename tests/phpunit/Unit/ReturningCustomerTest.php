<?php

namespace PostAuthorOptimization\Test\Unit;

use WP_UnitTestCase;
use WC_Helper_Order;
use PostAuthorOptimization;

class Post_Author_Optimization_Test extends WP_UnitTestCase {

	protected static $test_user_id;
	protected static $current_user_id;

	public function setUp() {
		// Not totally necessary, just ensures multiple customer accounts.
		self::$test_user_id = wp_create_user(
			'testuser',
			'password',
			'test@test.com'
		);

		// This user ID should be 3.
		self::$current_user_id = wp_create_user(
			'currentuser',
			'password1',
			'currentuser@test.com'
		);

		wp_set_current_user( self::$current_user_id );
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

	public function tearDown() {
		wp_delete_user( self::$test_user_id );
		wp_delete_user( self::$current_user_id );
		wp_set_current_user( 0 );
	}

}
