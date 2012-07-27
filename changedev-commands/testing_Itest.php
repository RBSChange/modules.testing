<?php
/**
 * commands_testing_Itest
 * @package modules.testing.command
 */
class commands_testing_Itest extends commands_testing_Utest
{
	/**
	 * @return String
	 */
	public function getUsage()
	{
		return "iTestFolder [<path/to/your/particularTest.php> [<testName>]] [--report [/path/to/report/]]" . PHP_EOL . 
			" * --all execute all integration test" . PHP_EOL .
			" * --report [/path/to/report/folder/] generate a Junit report and a PHP report" . PHP_EOL .
 			" * --reportJunit [/path/to/report/folder/] generate only the Junit report" . PHP_EOL .
			" * --rdb reset the database and import minimal samples to doing Integrations tests";
	}

	/**
	 * @return String
	 */
	public function getDescription()
	{
		return "Launch integration test with PHPUnit";
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
			foreach (glob("modules/testing/testintegration/*", GLOB_ONLYDIR) as $folder)
			{
				$components[] = basename($folder);
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
		return (count($params) > 0) || count($options > 0);
	}

	/**
	 * @return String[]
	 */
	public function getOptions()
	{
		return array('--report', '--reportJunit', '--verbose', '--all', '--rdb');
	}

	/**
	 * @param String[] $params
	 * @param array<String, String> $options where the option array key is the option name, the potential option value or true
	 * @see c_ChangescriptCommand::parseArgs($args)
	 */
	public function _execute($params, $options)
	{
		// Starts the framework
		$this->loadFramework();

		//contraints
		//Any cache must be activate
		if (CHANGE4_CACHE_SERVICE_CLASS != 'f_persistentdocument_NoopCacheService')
		{
			$this->quitError(('Any cache must be activate, change your config before executing integration tests.' . 
				' Change the value of CHANGE4_CACHE_SERVICE_CLASS to "f_persistentdocument_NoopCacheService"'));
			return;
		}
		
		if (array_key_exists('rdb', $options))
		{
			if (ModuleService::getInstance()->isInstalled('sample'))
			{
				$this->getParent()->executeCommand('reset-database');
				$this->getParent()->executeCommand('sample.import', array('website/sample.xml'));
				$this->getParent()->executeCommand('sample.import', array('catalog/default.xml'));
			}
			else
			{
				$this->errorMessage('module sample is not installed, please install it before executing --rdb option');
			}
		}
		
		// Location of the test from module name to the unit tests folder
		$testsDefaultLocation = f_util_FileUtils::buildAbsolutePath(AG_MODULE_DIR, 'testing', 'testintegration');
		$phpunitLocation = f_util_FileUtils::buildWebeditPath('changePHPUnit.php');

		$message = '';
		
		$command = 'php ' . $phpunitLocation . ' ';
		$switches = '--verbose ';
		$particularTest = '';
		$searchDirectory = '';
		
		if (array_key_exists('report', $options) || array_key_exists('reportJunit', $options))
		{
			//last params value is a valid absolute path
			if (preg_match('/^\//', $params[count($params) - 1]) && strpos($params[count($params) - 1], '/') !== false)
			{
				$reportFolder = array_pop($params);
				if (!is_dir($reportFolder))
				{
					$this->quitError($reportFolder . 'is not a valid absolute path, give a correct after --report or --reportJunit');
					exit();
				}
			}
			else if (Framework::hasConfiguration('modules/testing/reportFolder'))
			{
				$configReportFolder = Framework::getConfigurationValue('modules/testing/iTestReportFolder');
				if (is_dir($configReportFolder))
				{
					$reportFolder = $configReportFolder;
					$message .= 'Config value for report folder exist, the test will be genereted in: ' . $configReportFolder . PHP_EOL;
				}
				else
				{
					if (mkdir($configReportFolder, 0777, true))
					{
						$message .= 'Config value for report folder exist, the folder have been created at: ' . $configReportFolder . PHP_EOL;
						$reportFolder = $configReportFolder;
					}
					else
					{
						$this->quitError('cannot create folder: ' . $configReportFolder . ' are you sure you\'ve the right to do that?');
						exit();
					}
				}
			}
			else
			{
				$this->quitError('Config value for integration test report folder doesn\'t exist so give a correct absolute path after --report or --reportJunit');
				exit();
			}
			$reportFolder = substr($reportFolder, -1) == DIRECTORY_SEPARATOR ? $reportFolder : $reportFolder . DIRECTORY_SEPARATOR;
			$junitReport = $reportFolder . 'junitReport_' . date('Y-m-d_H-i-s') . '.xml';
			$switches .= '--log-junit ' . $junitReport . ' ';
		}
		
		$searchDirectory = $testsDefaultLocation;
		
		if (isset($params[0]))
		{
			$searchDirectory .= DIRECTORY_SEPARATOR . $params[0];
			if (isset($params[1]))
			{
				if (isset($params[2]))
				{
					$switches .= '--filter ' . $params[2] . ' ';
					$message = 'Execute only the integration test: ' . $params[2] . ' of file: ' . $params[1] . ' in ' . $params[0];
				}
				else
				{
					$message = 'Execute only the integration tests of file: ' . $params[1] . ' in ' . $params[0];
				}
				$searchDirectory .= DIRECTORY_SEPARATOR . $params[1];
			}
			else
			{
				$message = 'Execute all integration tests included in the folder of ' . $params[0];
			}
			if (isset($options['all']))
			{
				$this->warnMessage('You give --all option, it was not considered because you give a folder before');
			}
		}
		else
		{
			if (isset($options['all']))
			{
				$message = 'Execute all integration tests included in the testintegration folder of testing. That can be very long';
			}
			else
			{
				$this->message($this->getUsage());
				exit();
			}
		}
		
		if (isset($options['verbose']))
		{
			$switches .= '--debug ';
		}
		
		$executionCommand = $command . $switches . $searchDirectory;
		
		$this->message("== Itest ==");
		$this->getParent()->executeCommand('clearLog');
		if (isset($options['verbose']))
		{
			$this->message('command: ' . $executionCommand);
		}
		$this->message($message);
		$output = array();
		$execution = system($executionCommand, $output);

		if (array_key_exists('report', $options))
		{
			$this->generateReport($junitReport);
		}
		else if (array_key_exists('reportJunit', $options))
		{
			$this->message('check generated Junit report at: ' . $junitReport);
		}
		f_util_FileUtils::cleanTmpFiles();
		
		if (f_util_StringUtils::beginsWith($execution, 'OK'))
		{
			$this->quitOk($execution);
		}
		else if (preg_match('/Incomplete:/', $execution) && !preg_match('/Failures:/', $execution))
		{
			$this->quitWarn($execution);
		}
		else
		{
			$this->quitError($execution);
		}
	}
}