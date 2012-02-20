<?php
/**
 * @package modules.testing.lib.services
 */
class testing_ModuleService extends ModuleBaseService
{
	/**
	 * Singleton
	 * @var testing_ModuleService
	 */
	private static $instance = null;

	/**
	 * @return testing_ModuleService
	 */
	public static function getInstance()
	{
		if (is_null(self::$instance))
		{
			self::$instance = self::getServiceClassInstance(get_class());
		}
		return self::$instance;
	}
	
}