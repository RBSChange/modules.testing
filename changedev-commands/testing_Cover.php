<?php
/**
 * commands_testing_Cover
 * @package modules.testing.command
 */
class commands_testing_Cover extends commands_AbstractChangeCommand
{
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
//	public function getParameters($completeParamCount, $params, $options, $current)
//	{
//		$components = array();
//		
//		// Generate options in $components.		
//		
//		return $components;
//	}
	
	/**
	 * @param String[] $params
	 * @param array<String, String> $options where the option array key is the option name, the potential option value or true
	 * @return boolean
	 */
//	protected function validateArgs($params, $options)
//	{
//	}

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
		// Starts the framework
		require_once WEBEDIT_HOME . "/framework/Framework.php";
		
		$this->message("== Cover ==");
		$this->warnMessage('That take a long time');
		
		$phpunitLocation = PEAR_DIR . DIRECTORY_SEPARATOR . 'phpunit.php';
		$coverageReportFolder = WEBEDIT_HOME . DIRECTORY_SEPARATOR . 'testing' . 
			DIRECTORY_SEPARATOR . 'report';
		
		$command = $phpunitLocation . ' --coverage-html ' . $coverageReportFolder . ' ' . 
			AG_MODULE_DIR;
		
		$output = array();
		$execution = exec($command, $output);
		
		$this->quitOk(implode(PHP_EOL, $output) . PHP_EOL . 
			'HTML Report generated! You can find the index.html in this folder: ' . 
			$coverageReportFolder . DIRECTORY_SEPARATOR . 'index.html');
	}
}