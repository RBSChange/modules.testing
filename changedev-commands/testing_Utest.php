<?php
/**
 * commands_testing_Utest
 * @package modules.testing.command
 */
class commands_testing_Utest extends commands_AbstractChangeCommand
{
	/**
	 * @return String
	 */
	public function getUsage()
	{
		return "<moduleName> [<particularTest.php>] [--verbose]";
	}

	/**
	 * @return String
	 */
	public function getDescription()
	{
		return "Lunch unit test or test suite with PHPUnit";
	}
	
	/**
	 * This method is used to handle auto-completion for this command.
	 * @param Integer $completeParamCount the parameters that are already complete in the command line
	 * @param String[] $params
	 * @param array<String, String> $options where the option array key is the option name, the potential option value or true
	 * @return String[] or null
	 */
	public function getParameters($completeParamCount, $params, $options, $current)
	{
		$components = array();
		
		if ($completeParamCount == 0)
		{
			foreach (glob("modules/*", GLOB_ONLYDIR) as $module)
			{
				$components[] = basename($module);
			}
			return $components;
		}
				
		return array_diff($components, $params);
	}
	
	/**
	 * @param String[] $params
	 * @param array<String, String> $options where the option array key is the option name, the potential option value or true
	 * @return boolean
	 */
	protected function validateArgs($params, $options)
	{
		return (count($params) > 0);
	}

	/**
	 * @return String[]
	 */
	public function getOptions()
	{
		return array(
			'--verbose'
			);
	}

	/**
	 * @param String[] $params
	 * @param array<String, String> $options where the option array key is the option name, the potential option value or true
	 * @see c_ChangescriptCommand::parseArgs($args)
	 */
	public function _execute($params, $options)
	{
		// Starts the framework
		require_once WEBEDIT_HOME . "/framework/Framework.php";
		
		//Location of the test from module name to the unit tests folder
		$testsDefaultLocation = DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR . 'unit' . 
			DIRECTORY_SEPARATOR;
		$phpunitLocation = PEAR_DIR . DIRECTORY_SEPARATOR . 'phpunit.php';
		
		$message = '';
		
		if (isset($params[1]))
		{
			$command = $phpunitLocation . ' ' . AG_MODULE_DIR . DIRECTORY_SEPARATOR . $params[0] .
				$testsDefaultLocation . $params[1];
			$message = 'Execute only the unit test: ' . $params[1];
		}
		else 
		{
			$command = $phpunitLocation . ' ' . AG_MODULE_DIR . DIRECTORY_SEPARATOR . $params[0] . 
				$testsDefaultLocation . $params[0] . '_testsUSuite.php';
			$message = 'Execute the unit test suite: ' . $params[0] . '_testsUSuite.php';
		}
		
		$this->message("== Utest ==");

		$this->message($message);
		
		$output = array();
		$execution = exec($command, $output);
		if (f_util_StringUtils::beginsWith($execution, 'OK'))
		{
			if (array_key_exists('verbose', $options))
			{
				$this->message(implode(PHP_EOL, $output));
			}
			$this->quitOk($execution);
		}
		else
		{
			$this->quitError(implode(PHP_EOL, $output));
		}
	}
}