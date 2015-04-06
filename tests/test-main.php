<?php

class Test_Main_Functions extends WP_UnitTestCase {

	protected $plugin;

	function setUp() {
		parent::setUp();
		$this->plugin = \HM\BackUpWordPressS3\Plugin::get_instance();
	}

	function test_instantiation() {

		$this->assertInstanceOf( '\\HM\\BackUpWordPressS3\\Plugin', $this->plugin );

		$this->assertEquals( 10, has_action( 'admin_init', array( $this->plugin, 'maybe_self_deactivate' ) ) );

		do_action( 'backupwordpress_loaded' );

		$this->assertEquals( 10, has_action( 'backupwordpress_loaded', array( $this->plugin, 'init' ) ) );
	}
}
