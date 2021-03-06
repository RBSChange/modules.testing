<?php
/**
 * commands_testing_Utest
 * @package modules.testing.command
 */
class commands_testing_Utest extends commands_AbstractChangeCommand
{
	const ALL_TEST = 0;
	const GENERIC_TEST = 1;
	const SPECIFIC_TEST = 2;
	
	/**
	 *
	 * @return String
	 */
	public function getUsage()
	{
		return "[--scope=(all|generic|specific)|<moduleName>|framework [<path/to/your/particularTest.php> [<testName>]]] [--report [/path/to/report/]]" . PHP_EOL . "Parameters" . PHP_EOL . " * --scope=all: launch all tests, generic: all generic tests (framework + modules), --scope=specific: all specialized modules" . PHP_EOL . " * moduleName|framework [fileName.php [testName]] : launch module or framework tests, precise a path to file for one tests file, precise test name for one test" . PHP_EOL . "Optionals" . PHP_EOL . " * --report [/path/to/report/folder/] : generate a Junit report and a PHP report" . PHP_EOL . " * --reportJunit [/path/to/report/folder/] : generate only the Junit report";
	}
	
	/**
	 *
	 * @return String
	 */
	public function getDescription()
	{
		return "Launch unit test with PHPUnit";
	}
	
	/**
	 * This method is used to handle auto-completion for this command.
	 *
	 * @param $completeParamCount Integer
	 *        the parameters that are already complete in the command line
	 * @param $params String[]       	
	 * @param array<String, String> $options where the option array key is
	 *        the option name, the potential option value or true
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
	 *
	 * @param $params String[]       	
	 * @param array<String, String> $options where the option array key is
	 *        the option name, the potential option value or true
	 * @return boolean
	 */
	protected function validateArgs($params, $options)
	{
		return (count($params) > 0) || count($options > 0);
	}
	
	/**
	 *
	 * @return String[]
	 */
	public function getOptions()
	{
		return array('--report', '--reportJunit', '--scope', '--verbose');
	}
	
	/**
	 *
	 * @param $params String[]       	
	 * @param array<String, String> $options where the option array key is
	 *        the option name, the potential option value or true
	 * @see c_ChangescriptCommand::parseArgs($args)
	 */
	public function _execute($params, $options)
	{
		
		// Starts the framework
		$this->loadFramework();
		
		// Location of the test from module name to the unit tests folder
		$testsDefaultLocation = f_util_FileUtils::buildAbsolutePath(AG_MODULE_DIR, 'testing', 'testunit');
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
				$configReportFolder = Framework::getConfigurationValue('modules/testing/uTestReportFolder');
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
				$this->quitError('Config value for unit test report folder doesn\'t exist so give a correct absolute path after --report or --reportJunit');
				exit();
			}
			$reportFolder = substr($reportFolder, -1) == DIRECTORY_SEPARATOR ? $reportFolder : $reportFolder . DIRECTORY_SEPARATOR;
			$junitReport = $reportFolder . 'junitReport_' . date('Y-m-d_H-i-s') . '.xml';
			$switches .= '--log-junit ' . $junitReport . ' ';
		}
		
		if (isset($params[0]))
		{
			if (strtolower($params[0]) == 'framework')
			{
				$searchDirectory = $testsDefaultLocation . DIRECTORY_SEPARATOR . 'framework';
			}
			else
			{
				$searchDirectory = $testsDefaultLocation . DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR . $params[0];
			}

			if (isset($params[1]))
			{
				if (isset($params[2]))
				{
					$switches .= '--filter ' . $params[2] . ' ';
					$message = 'Execute only the unit test: ' . $params[2] . ' of file: ' . $params[1] . ' in ' . $params[0];
				}
				else
				{
					$message = 'Execute only the unit tests of file: ' . $params[1] . ' in ' . $params[0];
				}
				$searchDirectory .= DIRECTORY_SEPARATOR . $params[1];
			}
			else
			{
				$message = 'Execute all unit tests included in the folder of ' . $params[0];
			}
		}
		else
		{
			if (isset($options['scope']))
			{
				switch ($options['scope'])
				{
					case 'generic':
						$message = 'Execute all unit tests included in the testunit folder of generic modules.' . PHP_EOL . 'That can be very long';
						$testsuiteFilePath = $this->generateTestSuite($testsDefaultLocation, self::GENERIC_TEST);
						$switches .= '-c ' . $testsuiteFilePath;
						break;
					case 'all':
						$message = 'Execute all unit tests included in the testunit folder of modules and framework. That can be very long';
						$testsuiteFilePath = $this->generateTestSuite($testsDefaultLocation, self::ALL_TEST);
						$switches .= '-c ' . $testsuiteFilePath;
						break;
					case 'specific':
						$message = 'Execute all unit tests included in the testunit folder of your specialized modules. That can be very long';
						$testsuiteFilePath = $this->generateTestSuite($testsDefaultLocation, self::SPECIFIC_TEST);
						$switches .= '-c ' . $testsuiteFilePath;
						break;
					default:
						$this->errorMessage('your scope choice is wrong');
						$this->message($this->getUsage());
						exit();
				}
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
		
		$this->message("== Utest ==");
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
	
	/**
	 *
	 * @param $path string       	
	 */
	protected function generateReport($pathOfJUnit)
	{
		$this->message('Report generation in progress...');
		$phpFormatedReportFilename = 'phpReport_' . date('Y-m-d_H-i-s') . '.php';
		$reportFolder = dirname($pathOfJUnit);
		$filename = $reportFolder . DIRECTORY_SEPARATOR . $phpFormatedReportFilename;
		$junitReport = new DOMDocument('1.0', 'UTF-8');
		if ($junitReport->load($pathOfJUnit) && $phpFormatedReport = fopen($filename, 'a'))
		{
			$content = '<?php ' . PHP_EOL . '/**' . PHP_EOL . ' * Auto generated PHPUnit test report by Change' . PHP_EOL . ' */' . PHP_EOL . PHP_EOL;
			
			//Get resume infos
			$testsuiteResume = $junitReport->getElementsByTagName('testsuite')->item(0);
			
			$testsNb = $testsuiteResume->attributes->getNamedItem('tests')->nodeValue;
			$assertionsNb = $testsuiteResume->attributes->getNamedItem('assertions')->nodeValue;
			$failuresNb = $testsuiteResume->attributes->getNamedItem('failures')->nodeValue;
			$errorsNb = $testsuiteResume->attributes->getNamedItem('errors')->nodeValue;
			$time = $testsuiteResume->attributes->getNamedItem('time')->nodeValue;
			
			$testcases = $junitReport->getElementsByTagName('testcase');
			
			$testcasesArray = array();
			
			$autogeneratedNb = 0;
			
			foreach ($testcases as $testcase)
			{
				/* @var $testcase DOMNode */
				if ($testcase->hasChildNodes())
				{
					$caseName = $testcase->attributes->getNamedItem('class')->nodeValue . '::' . $testcase->attributes->getNamedItem('name')->nodeValue;
					$testcaseNodes = $testcase->childNodes;
					foreach ($testcaseNodes as $testcaseNode)
					{
						/* @var $testcaseNode DOMText */
						if ($testcaseNode->attributes)
						{
							$isAutogenerated = $testcaseNode->attributes->getNamedItem('type')->nodeValue == 'testing_AutoGeneratedException';
							
							$status = $testcaseNode->attributes->getNamedItem('type')->nodeValue;
							$testcasesArray[$status][$caseName][] = $testcaseNode->attributes->getNamedItem('type')->nodeValue;
							$testcasesArray[$status][$caseName]['autogenerated'] = $isAutogenerated;
							$testcasesArray[$status][$caseName]['message'] = 'Message: ' . $testcase->textContent;
							
							if ($isAutogenerated)
							{
								$autogeneratedNb++;
							}
						}
					}
				}
			}
			
			$content .= '/**' . PHP_EOL . ' * tests: ' . $testsNb . '  assertions: ' . $assertionsNb . '  time: ' . $time . PHP_EOL . ' * errors: ' . $errorsNb . PHP_EOL . ' * total failures: ' . $failuresNb . PHP_EOL . ' *   real failures: ' . ($failuresNb - $autogeneratedNb) . PHP_EOL . ' *   autogenerated: ' . $autogeneratedNb . PHP_EOL . ' */' . PHP_EOL . PHP_EOL;
			
			uksort($testcasesArray, "self::compareType");
			
			$commentSeparator = '	##	##	##	##	##	##	##	##	##	##	##';
			
			foreach ($testcasesArray as $level => $case)
			{
				$content .= '/**' . PHP_EOL;
				$content .= ' * failure type: ' . $level . PHP_EOL;
				$content .= ' * ' . PHP_EOL;
				$content .= ' * ' . PHP_EOL;
				
				foreach ($case as $test => $infos)
				{
					$content .= ' * @see ' . $test . PHP_EOL;
					if ($infos['autogenerated'])
					{
						$content .= ' *//* Auto genereted test, edit it' . '*//**' . PHP_EOL;
					}
					else
					{
						$content .= ' *//* ' . $infos['message'] . PHP_EOL;
						$content .= $commentSeparator . PHP_EOL . ' *//**' . PHP_EOL;
					}
					
					$content .= ' * ' . PHP_EOL;
				}
				$content .= ' */' . PHP_EOL;
				$content .= PHP_EOL;
				$content .= PHP_EOL;
			}
			
			fputs($phpFormatedReport, $content);
			fclose($phpFormatedReport);
			$this->message('check generated report at: ' . $filename);
		}
	}
	
	/**
	 * compare function to sort tests by failure type
	 * @param string $a
	 * @param string $b
	 * @return integer
	 */
	protected static function compareType($a, $b)
	{
		if ($a == 'testing_AutoGeneratedException')
		{
			return 1;
		}
		else if ($a == 'Exception')
		{
			return -1;
		}
		else
		{
			return 0;
		}
	}
	
	/**
	 *
	 * @param string $testsDefaultLocation
	 * @param integer $typeOfTests of commands_testing_Utest const
	 * @return string
	 */
	protected function generateTestSuite($testsDefaultLocation, $typeOfTests = self::ALL_TEST)
	{
		$filename = f_util_FileUtils::getTmpFile('testSuite');
		
		$domDoc = new DOMDocument('1.0', 'UTF-8');
		
		$root = $domDoc->createElement('phpunit');
		$root = $domDoc->appendChild($root);
		
		$testsuites = $domDoc->createElement('testsuites');
		$testsuites = $root->appendChild($testsuites);
		
		$testsuite = $domDoc->createElement('testsuite');
		$testsuite = $testsuites->appendChild($testsuite);
		$nameAttribute = $domDoc->createAttribute('name');
		$nameAttribute->value = 'allTests';
		$testsuite->appendChild($nameAttribute);
		
		switch ($typeOfTests)
		{
			case self::ALL_TEST :
				$frameworkDirectory = $domDoc->createElement('directory');
				$frameworkDirectory = $testsuite->appendChild($frameworkDirectory);
				$frameworkDirectoryTextNode = $domDoc->createTextNode($testsDefaultLocation . DIRECTORY_SEPARATOR . '*');
				$frameworkDirectoryTextNode = $frameworkDirectory->appendChild($frameworkDirectoryTextNode);
				
				break;
			case self::GENERIC_TEST :
				$frameworkDirectory = $domDoc->createElement('directory');
				$frameworkDirectory = $testsuite->appendChild($frameworkDirectory);
				$frameworkDirectoryTextNode = $domDoc->createTextNode($testsDefaultLocation . DIRECTORY_SEPARATOR . 'framework' . DIRECTORY_SEPARATOR . '*');
				$frameworkDirectoryTextNode = $frameworkDirectory->appendChild($frameworkDirectoryTextNode);
				
				foreach ($this->getGenericModules() as $genericModuleNames)
				{
					$moduleDirectory = $domDoc->createElement('directory');
					$moduleDirectory = $testsuite->appendChild($moduleDirectory);
					$moduleDirectoryTextNode = $domDoc->createTextNode($testsDefaultLocation . DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR . $genericModuleNames . DIRECTORY_SEPARATOR . '*');
					$moduleDirectoryTextNode = $moduleDirectory->appendChild($moduleDirectoryTextNode);
				}
				break;
			case self::SPECIFIC_TEST :
				$specializedModules = $this->getSpecializedModules();
				if (count($specializedModules) > 0)
				{
					foreach ($specializedModules as $specializedModuleNames)
					{
						$moduleDirectory = $domDoc->createElement('directory');
						$moduleDirectory = $testsuite->appendChild($moduleDirectory);
						$moduleDirectoryTextNode = $domDoc->createTextNode($testsDefaultLocation . DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR . $specializedModuleNames . DIRECTORY_SEPARATOR . '*');
						$moduleDirectoryTextNode = $moduleDirectory->appendChild($moduleDirectoryTextNode);
					}
				}
				else
				{
					$this->errorMessage('There is any specific module in your modules folder');
					exit();
				}
				break;
		}
		
		$domDoc->save($filename);
		
		return $filename;
	}
	
	/**
	 * @return array<string, string>
	 */
	private function getSpecializedModules()
	{
		$genericModules = $this->getGenericModules();
		$specializedModules = array();
		foreach (ModuleService::getInstance()->getPackageNames() as $package)
		{
			$moduleName = ModuleService::getInstance()->getShortModuleName($package);
			if (!isset($genericModules[$moduleName]))
			{
				$specializedModules[$moduleName] = $moduleName;
			}
		}
		
		return $specializedModules;
	}
	
	/**
	 * @return array<string, string>
	 */
	private function getGenericModules()
	{
		$bootstrap = $this->getParent()->getBootStrap();
		$dependencies = $bootstrap->loadDependencies();
		$genericModules = array();
		foreach ($dependencies as $debType => $debs)
		{
			if ($debType != 'modules')
			{
				continue;
			}
			
			foreach ($debs as $debName => $infos)
			{
				if ($infos['linked'])
				{
					$genericModules[$debName] = $debName;
				}
			}
		}
		
		return $genericModules;
	}
}