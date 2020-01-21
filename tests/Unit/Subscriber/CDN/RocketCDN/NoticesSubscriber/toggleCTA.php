<?php
namespace WP_Rocket\Tests\Unit\Subscriber\CDN\RocketCDN;

use WP_Rocket\Tests\Unit\TestCase;
use WP_Rocket\Subscriber\CDN\RocketCDN\NoticesSubscriber;
use Brain\Monkey\Functions;

/**
 * @covers \WP_Rocket\Subscriber\CDN\RocketCDN\NoticesSubscriber::toggle_cta
 * @group RocketCDN
 */
class Test_ToggleCTA extends TestCase {
	private $notices;

	public function setUp() {
		parent::setUp();

		$this->notices = new NoticesSubscriber(
			$this->createMock( 'WP_Rocket\CDN\RocketCDN\APIClient' ),
			'views/settings/rocketcdn'
		);
	}

	/**
	 * Test should return null when the $_POST values are not set
	 */
	public function testShouldReturnNullWhenPOSTNotSet() {
		Functions\when('check_ajax_referer')->justReturn(true);

		$this->assertNull( $this->notices->toggle_cta() );
	}

	/**
	 * Test should call delete_user_meta once when status value is big
	 */
	public function testShouldDeleteUserMetaWhenStatusIsBig() {
		Functions\when('check_ajax_referer')->justReturn(true);
		Functions\when('get_current_user_id')->justReturn(1);
		Functions\expect('delete_user_meta')
			->once()
			->with( 1, 'rocket_rocketcdn_cta_hidden' );

		$_POST['status'] = 'big';
		$_POST['action'] = 'toggle_rocketcdn_cta';

		$this->notices->toggle_cta();
	}

	/**
	 * Test should call update_user_meta once when status value is small
	 */
	public function testShouldUpdateUserMetaWhenStatusIsSmall() {
		Functions\when('check_ajax_referer')->justReturn(true);
		Functions\when('get_current_user_id')->justReturn(1);
		Functions\expect('update_user_meta')
			->once()
			->with( 1, 'rocket_rocketcdn_cta_hidden', true );

		$_POST['status'] = 'small';
		$_POST['action'] = 'toggle_rocketcdn_cta';

		$this->notices->toggle_cta();
	}
}
