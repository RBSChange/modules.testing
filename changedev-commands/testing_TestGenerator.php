<?php
/**
 * commands_testing_TestGenerator
 * @package modules.testing.command
 */
class commands_testing_TestGenerator extends commands_AbstractChangeCommand
{
	/**
	 *
	 * @return String
	 */
	public function getUsage()
	{
		return "<moduleName>";
	}
	
	/**
	 *
	 * @return String
	 */
	public function getDescription()
	{
		return "Generate a test file for each php class found in the module, or if file exist" . 
			" add test methods needed";
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
		return (count($params) > 0);
	}
	
	/**
	 *
	 * @return String[]
	 */
	public function getOptions()
	{
		return array('--verbose');
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
		$moduleName = $params[0];
		
		$this->message("== TestGenerator ==");
		$verbose = array_key_exists('verbose', $options);
		$this->loadFramework();
		$classFound = array();
		$pathsToAnalyse = $this->getPathsToAnalyse($moduleName);
		
		$files = $this->getFiles($pathsToAnalyse);
		
		$testInfos = array();
		foreach ($files as $file)
		{
			if ($verbose)
			{
				$this->message('file founded: ' . $file);
			}
			$tokenArray = token_get_all(file_get_contents($file));
			$definedClasses = array();
			
			foreach ($tokenArray as $index => $token)
			{
				if ($token[0] == T_CLASS)
				{
					$className = $tokenArray[$index + 2][1];
					
					$reflectionClass = new ReflectionClass($className);
					if (!$reflectionClass->isAbstract())
					{
						$definedClasses[] = $className;
					}
				}
			}
			$testInfos = array_merge($testInfos, $this->getTestInfos($definedClasses, $file, $verbose));
		}
		
		$this->generateTest($testInfos);

		$this->message('');
		$result = '';
		foreach ($testInfos as $class => $testInfo)
		{
			if (isset($testInfo['methods']))
			{
				$methods = $testInfo['methods'];
				$file = $testInfo['file'];
				$countMethods = is_array($methods) ? count($methods) : count($this->getPublicAndNoneHeritedMethods($class));
							
				$result .= $class . PHP_EOL . '	in file: ' . $file . PHP_EOL . '	has been ';
				$result .= is_array($methods) ? 'modified' : 'created';
				$result .= ' with ' . $countMethods . ' methods to edit';
				$result .= PHP_EOL;
			}
		}
		$this->message($result);
		$this->quitOk("Command successfully executed");
	}
	
	/**
	 * 
	 * @param string $moduleName
	 */
	private function getPathsToAnalyse($moduleName)
	{
		if (strtolower($moduleName) == 'framework')
		{
			return array(array('path' => FRAMEWORK_HOME, 'recursive' => true, 'exclude' => array('deprecated', 'doc', 'module', 'webapp', 'tests')));
		}
		else
		{
			return array(array('path' => AG_MODULE_DIR . DIRECTORY_SEPARATOR . $moduleName . DIRECTORY_SEPARATOR . 'actions'), 
				array('path' => AG_MODULE_DIR . DIRECTORY_SEPARATOR . $moduleName . DIRECTORY_SEPARATOR . 'change-commands', 'recursive' => true), 
				array('path' => AG_MODULE_DIR . DIRECTORY_SEPARATOR . $moduleName . DIRECTORY_SEPARATOR . 'changedev-commands', 'recursive' => true), 
				array('path' => AG_MODULE_DIR . DIRECTORY_SEPARATOR . $moduleName . DIRECTORY_SEPARATOR . 'lib', 'recursive' => true), 
				array('path' => AG_MODULE_DIR . DIRECTORY_SEPARATOR . $moduleName . DIRECTORY_SEPARATOR . 'views'), 
				array('path' => AG_MODULE_DIR . DIRECTORY_SEPARATOR . $moduleName . DIRECTORY_SEPARATOR . 'persistentdocument', 'recursive' => true));
		}
	}
	
	/**
	 * 
	 * @param string $pathsToAnalyse
	 */
	private function getFiles($pathsToAnalyse)
	{
		$files = array();
		foreach ($pathsToAnalyse as $pathToAnalyse)
		{
			// directory mapping
			$path = $pathToAnalyse['path'];
			if (is_dir($path))
			{
				$recursive = isset($pathToAnalyse['recursive']) ? $pathToAnalyse['recursive'] : false;
				$exludeDirs = isset($pathToAnalyse['exclude']) ? $pathToAnalyse['exclude'] : array();		
				$files = array_merge($files, $this->getPHPFiles($path, $recursive, $exludeDirs));
			}
		}
		return $files;
	}
	
	private function getPHPFiles($path, $recursive = true, $exludeDirs = array())
	{
		$result = array();
		$di = new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::KEY_AS_PATHNAME);
		f_PHPFileFilter::setFilters($recursive, $exludeDirs);
		$fi = new f_PHPFileFilter($di);
		$it = new RecursiveIteratorIterator($fi, RecursiveIteratorIterator::CHILD_FIRST);
	
		foreach ($it as $file => $info)
		{
			if ($info->isFile())
			{
				$result[] = $file;
			}
		}
		return $result;
	}
	
	/**
	 *
	 * @param string[] $definedClasses
	 * @param string $file
	 * @param boolean $verbose
	 * @return array< string $class, 'methods' => string $noMethods | string[] $methods, 'file' => string>
	 */
	private function getTestInfos($definedClasses, $file, $verbose = false)
	{
		$result = array();
		$defaultUnitTestFolder = $this->getUnitTestFolder($file);
		foreach ($definedClasses as $class)
		{
			if ($verbose)
			{
				$this->message('	Class: ' . $class);
			}
			
			$methods = $this->getPublicAndNoneHeritedMethods($class);
			$testFile = $class . 'Test.php';
			$testClass = $class . 'Test';
			$testFilePath = $defaultUnitTestFolder . DIRECTORY_SEPARATOR . $testFile;
			$result[$class]['file'] = $testFilePath;
			if (file_exists($testFilePath))
			{
				if ($verbose)
				{
					$this->okMessage('	test file exist for the class: ' . $class);
				}
				
				require_once $testFilePath;
				
				$testMethodsAvailable = get_class_methods($testClass);
				
				foreach ($methods as $method)
				{
					$isTestAvailable = false;
					foreach ($testMethodsAvailable as $testMethodAvailable)
					{
						if (f_util_StringUtils::endsWith($testMethodAvailable, $method, f_util_StringUtils::CASE_INSENSITIVE))
						{
							$isTestAvailable = true;
						}
					}
					if ($isTestAvailable)
					{
						if ($verbose)
						{
							$this->okMessage('		Method: ' . $method . ' is tested');
						}
					}
					else
					{
						if (!isset($result[$class]['methods']))
						{
							$result[$class]['methods'] = array();
						}
						$result[$class]['methods'][] = $method;
						if ($verbose)
						{
							$this->warnMessage('		Method: ' . $method . ' isn\'t tested');
						}
					}
				}
			}
			else
			{
				$this->errorMessage('	test file doesn\'t exist for the class: ' . $class);
				$result[$class]['methods'] = 'noMethods';
			}
		}
		return $result;
	}
	
	/**
	 * For example: $file = WEBEDIT_HOME/modules/catalog/lib/bin/testTest.php
	 * returns WEBEDIT_HOME/modules/testing/testunit/catalog/lib/bin
	 * @param string $folders
	 * @return string
	 */
	private function getUnitTestFolder($file)
	{
		$folders = dirname(substr($file, strlen(WEBEDIT_HOME) + 1));
		return f_util_FileUtils::buildAbsolutePath(AG_MODULE_DIR, 'testing', 'testunit', $folders);
	}
	
	/**
	 * 
	 * @param array<string $class, 'methods' => 'noMethods' | string[] $methods, 'file' => string>  $testInfos
	 */
	private function generateTest($testInfos)
	{
		foreach ($testInfos as $class => $testInfo)
		{
			$unitTestFolder = dirname($testInfo['file']);
			
			$testFilePath = $testInfo['file'];
			
			if (isset($testInfo['methods']) && is_array($testInfo['methods']))
			{
				$this->generateTestMethods($testInfo['methods'], $class, $testFilePath);
			}
			else if (file_exists($testFilePath))
			{
				$this->okMessage($class . ' exists and has no test methods to add');
			}
			else
			{
				try
				{
					if (!file_exists($unitTestFolder))
					{
						mkdir($unitTestFolder, 0777, true);
					}
						
					$classtest = $class . 'Test';
				
					$reflectionClass = new ReflectionClass($class);
					
					$isSingleton = $reflectionClass->hasMethod('getInstance');
					$hasNewInstanceMethod = $reflectionClass->hasMethod('getNewInstance');
					$hasCustomNewConstructor = $reflectionClass->getConstructor() != null ? true : false;
					$hasCustomGetInstance = false;
					if ($isSingleton)
					{
						$hasCustomGetInstance = ($reflectionClass->getMethod('getInstance')->getNumberOfRequiredParameters() > 0);
					}
					$hasCustomNewInstance = false;
					if ($hasNewInstanceMethod)
					{
						$hasCustomNewInstance = ($reflectionClass->getMethod('getNewInstance')->getNumberOfRequiredParameters() > 0);
					}
					
					$generator = new builder_Generator();
					$generator->setTemplateDir(f_util_FileUtils::buildWebeditPath('modules', 'testing', 'templates', 'builder'));
					$generator->assign_by_ref('isSingleton', $isSingleton);
					$generator->assign_by_ref('hasNewInstanceMethod', $hasNewInstanceMethod);
					$generator->assign_by_ref('hasCustomNewConstructor', $hasCustomNewConstructor);
					$generator->assign_by_ref('hasCustomGetInstance', $hasCustomGetInstance);
					$generator->assign_by_ref('hasCustomNewInstance', $hasCustomNewInstance);
					$generator->assign_by_ref('classbase', $class);
					$generator->assign_by_ref('classtest', $classtest);
					$result = $generator->fetch('testClass.tpl');
		
					f_util_FileUtils::write($testFilePath, $result);
					
					$this->okMessage('Test file: ' .  $class . 'Test.php was created at ' . $unitTestFolder);
					
					$methods = $this->getPublicAndNoneHeritedMethods($class);
					$this->generateTestMethods($methods, $class, $testFilePath);
				}
				catch (Exception $e)
				{
					Framework::error($e->getMessage());
				}
			}
		}
	}
	
	/**
	 * 
	 * @param string[] $methods
	 * @param string $class
	 * @param string $testFilePath
	 */
	private function generateTestMethods($methods, $class, $testFilePath)
	{
		$file = fopen($testFilePath, 'r+');
		
		$result = '';	
		foreach ($methods as $method)
		{
			$generator = new builder_Generator();
			$generator->setTemplateDir(f_util_FileUtils::buildWebeditPath('modules', 'testing', 'templates', 'builder'));
			$generator->assign_by_ref('classbase', $class);
			$generator->assign_by_ref('uMethod', f_util_StringUtils::ucfirst($method));
			$generator->assign_by_ref('method', $method);
			$result .= $generator->fetch('testFunction.tpl');
		}
		
		$fileContent = file_get_contents($testFilePath);
		$lastClosedBracePosition = strrpos($fileContent, '}');
		fseek($file, ($lastClosedBracePosition - 1), SEEK_SET);
		fputs($file, PHP_EOL . $result . PHP_EOL . '}');
		
		fclose($file);
	}
	
	private function getPublicAndNoneHeritedMethods($class)
	{
		$methods = array();
		$reflection = new ReflectionClass($class);
		$refelectionMethods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);
		foreach ($refelectionMethods as $reflectionMethod)
		{
			/* @var $reflectionMethod ReflectionMethod */
			if ($reflectionMethod->class == $class)
			{
				$methods[] = $reflectionMethod->getName();
			}
		}
		return $methods;
	}
}