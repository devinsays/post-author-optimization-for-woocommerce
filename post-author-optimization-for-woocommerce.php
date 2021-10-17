<?php
/**
 * Plugin Name: Post Author Optimization for WooCommerce
 * Plugin URI: http://wptheming.com
 * Description: Stores the customer_user for WooCommerce orders and subscriptions in the post_author column of posts table.
 * Version: 1.0.0
 * Author: Devin Price
 * Author URI: http://wptheming.com/
 * Developer: Devin Price
 * Developer URI: https://wptheming.com
 *
 * WC requires at least: 5.6.0
 * WC tested up to: 5.8.0
 *
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */


/**
 * By default all the subscription and order entries in wp_posts table have post_author=1.
 * Let's use filters to set the current user ID instead.
 *
 * Class PostAuthorOptimization
 * @package PostAuthorOptimization
 */
class PostAuthorOptimization {

	/**
	 * Store the latest pulled subscription's customer ID.
	 *
	 * @var bool
	 */
	protected static $subscription_customer_id = false;

	/**
	 * The single instance of the class.
	 *
	 * @var mixed $instance
	 */
	protected static $instance;

	/**
	 * Main PostAuthorOptimization Instance.
	 *
	 * Ensures only one instance of the PostAuthorOptimization is loaded or can be loaded.
	 *
	 * @return PostAuthorOptimization - Main instance.
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	public function __construct() {
		/**
		 * Filter called by create() for use in wp_insert_post().
		 *
		 * @param array Order data array
		 * @see wp-content\plugins\woocommerce\includes\data-stores\abstract-wc-order-data-store-cpt.php
		 */
		add_filter( 'woocommerce_new_order_data', [ $this, 'set_post_author_to_current_user' ] );

		/**
		 * Filter called by wcs_create_subscription() for use in wp_insert_post().
		 *
		 * @param array $subscription_data
		 * @see wp-content\plugins\woocommerce-subscriptons\wcs-functions.php
		 */
		add_filter( 'woocommerce_new_subscription_data', [ $this, 'set_post_author_to_current_user' ] );

		// Store the customer for a subscription.
		add_filter( 'wcs_get_subscription', [ $this, 'wcs_get_subscription' ] );

		// Stores the customer on an admin save.
		add_action( 'save_post_shop_subscription', [ $this, 'maybe_update_post_author' ] );
		add_action( 'save_post_shop_order', [ $this, 'maybe_update_post_author' ] );
	}

	/**
	 * Set current user ID as `post_author` column.
	 *
	 * @param $return
	 *
	 * @return mixed
	 */
	public function set_post_author_to_current_user( $return ) {
		if ( self::$subscription_customer_id ) {
			$customer_id = self::$subscription_customer_id;
		} else {
			$customer_id = get_current_user_id();
		}

		if ( $customer_id ) {
			$return['post_author'] = $customer_id;
		}

		return $return;
	}

	/**
	 * Save latest subscription customer ID. We need this for renewal orders. We don't
	 * have any other information about the related subscription at later point.
	 *
	 * @param \WC_Subscription $subscription
	 *
	 * @return mixed
	 */
	public function wcs_get_subscription( $subscription ) {
		if ( ! $subscription instanceof \WC_Subscription ) {
			return $subscription;
		}

		$customer_id = $subscription->get_customer_id();

		if ( $customer_id ) {
			self::$subscription_customer_id = $customer_id;
		}

		return $subscription;
	}

	/**
	 * When an order or subscription is saved in the admin, let's make
	 * sure that we set customer ID to post_author column.
	 *
	 * @param int $post_id
	 */
	public function maybe_update_post_author( $post_id ) {
		if ( ! isset( $_POST['customer_user'] ) || ! is_admin() ) {
			return;
		}

		if ( absint( $_POST['customer_user'] ) !== absint( $_POST['post_author'] ) ) {
			$this->set_post_author( $post_id, absint( $_POST['customer_user'] ) );
		}
	}

	/**
	 * Manually sets post_author to specified user.
	 *
	 * @param int $post_id
	 * @param int $user_id
	 */
	public static function set_post_author( $post_id, $user_id ) {
		if ( ! $post_id || ! $user_id ) {
			return;
		}

		global $wpdb;
		$wpdb->query(
			$wpdb->prepare( "UPDATE {$wpdb->posts} SET post_author = %d WHERE ID = %d", $user_id, $post_id )
		);
	}
}

PostAuthorOptimization::instance();
