<?php

/**
 * Base test case.
 */
class testing_UnitTestBase extends PHPUnit_Framework_TestCase
{

	/**
	 * Prepares the environment before running a test.
	 */
	protected function setUp()
	{
		parent::setUp();
		if (!defined('PROJECT_HOME'))
		{
			define('PROJECT_HOME', '/home/renaud/sites/fix');
			define('WEBEDIT_HOME', PROJECT_HOME);
		}
		// Starts the framework
		require_once PROJECT_HOME . "/framework/Framework.php";

		$this->setRunTestInSeparateProcess(true);
	}

	/**
	 * Cleans up the environment after running a test.
	 */
	protected function tearDown()
	{
		parent::tearDown();
	}
}

