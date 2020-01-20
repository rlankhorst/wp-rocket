<?php
namespace WP_Rocket\Subscriber\CDN\RocketCDN;

use WP_Rocket\Abstract_Render;
use WP_Rocket\Event_Management\Subscriber_Interface;
use WP_Rocket\CDN\RocketCDN\APIClient;

/**
 * Subscriber for the RocketCDN notices on WP Rocket settings page
 *
 * @since 3.5
 * @author Remy Perona
 */
class NoticesSubscriber extends Abstract_Render implements Subscriber_Interface {
	/**
	 * RocketCDN API Client instance.
	 *
	 * @var APIClient
	 */
	private $api_client;

	/**
	 * Constructor
	 *
	 * @param APIClient $api_client RocketCDN API Client instance.
	 * @param string    $template_path Path to the templates.
	 */
	public function __construct( APIClient $api_client, $template_path ) {
		parent::__construct( $template_path );

		$this->api_client = $api_client;
	}

	/**
	 * @inheritDoc
	 */
	public static function get_subscribed_events() {
		return [
			'admin_notices'                    => [
				[ 'promote_rocketcdn_notice' ],
				[ 'purge_cache_notice' ],
			],
			'rocket_before_cdn_sections'       => 'display_rocketcdn_cta',
			'wp_ajax_toggle_rocketcdn_cta'     => 'toggle_cta',
			'wp_ajax_rocketcdn_dismiss_notice' => 'dismiss_notice',
			'admin_footer'                     => 'add_dismiss_script',
		];
	}

	/**
	 * Adds notice to promote Rocket CDN on settings page
	 *
	 * @since 3.5
	 * @author Remy Perona
	 *
	 * @return void
	 */
	public function promote_rocketcdn_notice() {
		if ( ! $this->should_display_notice() ) {
			return;
		}

		echo $this->generate( 'promote-notice' );
	}

	/**
	 * Adds inline script to permanently dismissing the RocketCDN promotion notice
	 *
	 * @since 3.5
	 * @author Remy Perona
	 *
	 * @return void
	 */
	public function add_dismiss_script() {
		if ( ! $this->should_display_notice() ) {
			return;
		}

		$nonce = wp_create_nonce( 'rocketcdn_dismiss_notice' );
		?>
		<script>
		window.addEventListener( 'load', function() {
			var dismissBtn  = document.querySelectorAll( '#rocketcdn-promote-notice .notice-dismiss, #rocketcdn-promote-notice #rocketcdn-learn-more-dismiss' );

			dismissBtn.forEach(function(element) {
				element.addEventListener( 'click', function( event ) {
					var httpRequest = new XMLHttpRequest(),
						postData    = '';

					postData += 'action=rocketcdn_dismiss_notice';
					postData += '&nonce=<?php echo esc_attr( $nonce ); ?>';
					httpRequest.open( 'POST', '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>' );
					httpRequest.setRequestHeader( 'Content-Type', 'application/x-www-form-urlencoded' )
					httpRequest.send( postData );
				});
			});
		});
		</script>
		<?php
	}

	/**
	 * Checks if the promotion notice should be displayed
	 *
	 * @since 3.5
	 * @author Remy Perona
	 *
	 * @return boolean
	 */
	private function should_display_notice() {
		if ( ! current_user_can( 'rocket_manage_options' ) ) {
			return false;
		}

		if ( 'settings_page_wprocket' !== get_current_screen()->id ) {
			return false;
		}

		if ( get_user_meta( get_current_user_id(), 'rocketcdn_dismiss_notice', true ) ) {
			return false;
		}

		$subscription_data = $this->api_client->get_subscription_data();

		return ! $subscription_data['is_active'];
	}

	/**
	 * Ajax callback to save the dismiss as a user meta
	 *
	 * @since 3.5
	 * @author Remy Perona
	 *
	 * @return void
	 */
	public function dismiss_notice() {
		check_ajax_referer( 'rocketcdn_dismiss_notice', 'nonce', true );

		if ( ! isset( $_POST['action'] ) || 'rocketcdn_dismiss_notice' !== $_POST['action'] ) {
			return;
		}

		update_user_meta( get_current_user_id(), 'rocketcdn_dismiss_notice', true );
	}

	/**
	 * Displays the Rocket CDN Call to Action on the CDN tab of WP Rocket settings page
	 *
	 * @since 3.5
	 * @author Remy Perona
	 *
	 * @return void
	 */
	public function display_rocketcdn_cta() {
		$subscription_data = $this->api_client->get_subscription_data();

		if ( $subscription_data['is_active'] ) {
			return;
		}

		$pricing = $this->api_client->get_pricing_data();

		$regular_price   = '';
		$nopromo_variant = '--no-promo';
		$cta_small_class = 'wpr-isHidden';
		$cta_big_class   = '';

		if ( get_user_meta( get_current_user_id(), 'rocket_rocketcdn_cta_hidden', true ) ) {
			$cta_small_class = '';
			$cta_big_class   = 'wpr-isHidden';
		}

		$small_cta_data = [
			'container_class' => $cta_small_class,
		];

		if ( is_wp_error( $pricing ) ) {
			$big_cta_data = [
				'container_class' => $cta_big_class,
				'nopromo_variant' => $nopromo_variant,
				'error'           => true,
				'message'         => $pricing->get_error_message(),
			];
		} else {
			$current_price      = number_format_i18n( $pricing['monthly_price'], 2 );
			$promotion_campaign = $pricing['discount_campaign_name'];
			$promotion_end_date = date_i18n( get_option( 'date_format' ), strtotime( $pricing['end_date'] ) );

			if ( $pricing['is_discount_active'] ) {
				$regular_price   = $current_price;
				$current_price   = number_format_i18n( $pricing['discounted_price_monthly'], 2 ) . '*';
				$nopromo_variant = '';
			}

			$big_cta_data = [
				'container_class'    => $cta_big_class,
				'promotion_campaign' => $promotion_campaign,
				'promotion_end_date' => $promotion_end_date,
				'nopromo_variant'    => $nopromo_variant,
				'regular_price'      => $regular_price,
				'current_price'      => $current_price,
			];
		}

		echo $this->generate( 'cta-small', $small_cta_data );
		echo $this->generate( 'cta-big', $big_cta_data );
	}

	/**
	 * Toggles display of the Rocket CDN CTAs on the settings page
	 *
	 * @since 3.5
	 * @author Remy Perona
	 *
	 * @return void
	 */
	public function toggle_cta() {
		check_ajax_referer( 'rocket-ajax', 'nonce', true );

		if ( ! isset( $_POST['action'], $_POST['status'] ) || 'toggle_rocketcdn_cta' !== $_POST['action'] ) {
			return;
		}

		if ( 'big' === $_POST['status'] ) {
			delete_user_meta( get_current_user_id(), 'rocket_rocketcdn_cta_hidden' );
		} elseif ( 'small' === $_POST['status'] ) {
			update_user_meta( get_current_user_id(), 'rocket_rocketcdn_cta_hidden', true );
		}
	}

	/**
	 * Displays a notice after purging the RocketCDN cache.
	 *
	 * @since 3.5
	 * @author Remy Perona
	 *
	 * @return void
	 */
	public function purge_cache_notice() {
		if ( ! current_user_can( 'rocket_manage_options' ) ) {
			return;
		}

		if ( 'settings_page_wprocket' !== get_current_screen()->id ) {
			return;
		}

		$purge_response = get_transient( 'rocketcdn_purge_cache_response' );

		if ( false === $purge_response ) {
			return;
		}

		delete_transient( 'rocketcdn_purge_cache_response' );

		\rocket_notice_html(
			[
				'status'  => $purge_response['status'],
				'message' => $purge_response['message'],
			]
		);
	}
}