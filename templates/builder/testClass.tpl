<?php
/**
 * <{$classbase}> test case.
 */
class <{$classtest}> extends testing_UnitTestBase
{
	/**
	 * @var <{$classbase}>
	 */
	private $<{$classbase}>;

	/**
	 * Prepares the environment before running a test.
	 */
	protected function setUp()
	{
		parent::setUp($this->getName());
<{if $hasCustomNewInstance}>
		//Custom getNewInstance() to edit
		//$this-><{$classbase}> = <{$classbase}>::getNewInstance(/*parameters*/);
<{elseif $hasNewInstanceMethod}>
		$this-><{$classbase}> = <{$classbase}>::getNewInstance();
<{elseif $hasCustomGetInstance}>
		//Custom getInstance() to edit
		//$this-><{$classbase}> = <{$classbase}>::getInstance(/*parameters*/);
<{elseif $isSingleton}>
		$this-><{$classbase}> = <{$classbase}>::getInstance();
<{elseif $hasCustomNewConstructor}>
		//Custom constructor to edit
		//$this-><{$classbase}> = new <{$classbase}>(/*parameters*/);
<{else}>
		$this-><{$classbase}> = new <{$classbase}>();
<{/if}>
	}

	/**
	 * Cleans up the environment after running a test.
	 */
	protected function tearDown()
	{
		$this-><{$classbase}> = null;
		
		parent::tearDown();
	}

	/**
	 * Constructs the test case.
	 */
	public function __construct()
	{
	}
}