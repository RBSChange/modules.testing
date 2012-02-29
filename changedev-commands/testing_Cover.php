<?php
/**
 * commands_testing_Cover
 * @package modules.testing.command
 */
class commands_testing_Cover extends commands_AbstractChangeCommand
{
	/**
	 * @return Boolean default false
	 */
	function isHidden()
	{
		return true;
	}
	
	/**
	 * @return String
	 */
	public function getUsage()
	{
		return "";
	}

	/**
	 * @return String
	 */
	public function getDescription()
	{
		return "Lunch a code coverage of your project";
	}
	
	/**
	 * This method is used to handle auto-completion for this command.
	 * @param Integer $completeParamCount the parameters that are already complete in the command line
	 * @param String[] $params
	 * @param array<String, String> $options where the option array key is the option name, the potential option value or true
	 * @return String[] or null
	 */
// 	public function getParameters($completeParamCount, $params, $options, $current)
// 	{
// 		$components = array();
//		
// 		if ($completeParamCount == 0)
// 		{
// 			foreach (glob("modules/*", GLOB_ONLYDIR) as $module)
// 			{
// 				$components[] = basename($module);
// 			}
// 			return $components;
// 		}
//				
// 		return array_diff($components, $params);
// 	}
	
	/**
	 * @param String[] $params
	 * @param array<String, String> $options where the option array key is the option name, the potential option value or true
	 * @return boolean
	 */
// 	protected function validateArgs($params, $options)
// 	{
// 		return (count($params) > 0);
// 	}

	/**
	 * @return String[]
	 */
//	public function getOptions()
//	{
//	}

	/**
	 * @param String[] $params
	 * @param array<String, String> $options where the option array key is the option name, the potential option value or true
	 * @see c_ChangescriptCommand::parseArgs($args)
	 */
	public function _execute($params, $options)
	{
		$this->warnMessage('testing.cover is not implemented yet');
		
// 		// Starts the framework
// 		require_once WEBEDIT_HOME . "/framework/Framework.php";
		
// 		$this->message("== Cover ==");
// 		$this->warnMessage('That take a long time');
		
// 		$phpunitLocation = WEBEDIT_HOME . DIRECTORY_SEPARATOR . 'changePHPUnit.php';
// 		$coverageReportFolder = WEBEDIT_HOME . DIRECTORY_SEPARATOR . 'testing' . 
// 			DIRECTORY_SEPARATOR . 'report';
		
// 		$modules = glob("modules/*", GLOB_ONLYDIR);
// 		$modulesHaveTests = glob("modules/*/tests", GLOB_ONLYDIR);
// 		$modulesHaveNoTests = array_diff($modules, $modulesHaveTests);
// 		$i = 0;
// 		foreach ($modulesHaveNoTests as $moduleHasNoTests)
// 		{
// 			$this->warnMessage(basename($moduleHasNoTests) . ' has no tests folder!');
// 			$i++;
// 		}
// 		if ($i > 0)
// 		{
// 			$this->warnMessage('Modules who haven\'t tests folder and available tests inner are excluded from Code Coverage');
// 		}
		
// 		$command = 'php ' . $phpunitLocation . ' --coverage-html ' . $coverageReportFolder;
		
// 		$output = array();
// 		$execution = exec($command, $output);
// 		$this->message(implode(PHP_EOL, $output));
// 		$this->quitOk('HTML Report generated! You can find the index.html in this folder: ' .
// 			$coverageReportFolder . DIRECTORY_SEPARATOR . 'index.html');
	}
}