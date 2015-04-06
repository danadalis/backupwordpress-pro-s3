<?php
// Because main plugin checks is_admin before loading
require_once dirname( __DIR__ ) . '/admin/admin.php';

class Test_Admin_Functions extends WP_UnitTestCase {

	protected $admin;

	function setUp() {

		parent::setUp();

		$this->admin = \HM\BackUpWordPressS3\Check_License::get_instance();

	}

	function test_license_is_invalid() {

		$this->assertTrue( $this->admin->is_license_invalid( 'invalid' ) );
	}

	function test_license_is_expired() {
		$this->assertTrue( $this->admin->is_license_expired( 'expired' ) );
	}

	function test_fetch_license_data_invalid() {

		$api_params = array(
			'edd_action' => 'check_license',
			'license'    => 'invalidkey',
			'item_name'  => urlencode( \HM\BackUpWordPressS3\Plugin::EDD_STORE_URL )
		);

		add_filter( 'pre_http_request', $this->get_http_request_overide( $this->admin->get_api_url( $api_params ),file_get_contents( __DIR__ . '/data/invalid_license.json' )
			), 10, 3 );

		$license_data = $this->admin->fetch_license_data( 'invalidkey' );

		$this->assertInstanceOf( 'stdClass', $license_data );

		$this->assertTrue( $this->admin->is_license_invalid( $license_data->license ) );

	}

	function test_fetch_license_data_valid() {

		$api_params = array(
			'edd_action' => 'check_license',
			'license'    => 'e804e13c0099a7275b0019d544232212',
			'item_name'  => urlencode( \HM\BackUpWordPressS3\Plugin::EDD_STORE_URL )
		);

		add_filter( 'pre_http_request', $this->get_http_request_overide( $this->admin->get_api_url( $api_params ),file_get_contents( __DIR__ . '/data/valid_license.json' )
		), 10, 3 );

		$license_data = $this->admin->fetch_license_data( 'e804e13c0099a7275b0019d544232212' );

		$this->assertInstanceOf( 'stdClass', $license_data );

		$this->assertTrue( ! $this->admin->is_license_invalid( $license_data->license ) );

	}

	private function get_http_request_overide( $matched_url, $response_body ) {

		$func = null;

		return $func = function ( $return, $request, $url ) use ( $matched_url, $response_body, &$func ) {

			remove_filter( 'pre_http_request', $func );

			if ( $url !== $matched_url ) {
				return $return;
			}

			$response = array(
				'headers'  => array(),
				'body'     => $response_body,
				'response' => 200,
			);

			return $response;
		};

	}

}

