<?php

/**
 * Base test case.
 */
class testing_IntegrationTestBase extends testing_UnitTestBase
{
	/**
	 * @var array<string, string>
	 */
	protected static $dbInfos;
	
	/**
	 * @var boolean
	 */
	protected $dbToBeCleaned = false;
	
	/**
	 * @var string
	 */
	protected static $dumpPath;
	
	/**
	 * @var string
	 */
	protected $phperrorLog;
	
	/**
	 * @var string
	 */
	protected $applicationLog;
	
	/**
	 * Prepares the environment before running a test.
	 */
	protected function setUp()
	{
		f_persistentdocument_TransactionManager::getInstance()->beginTransaction();
	}
	
	/**
	 * Cleans up the environment after running a test.
	 */
	protected function tearDown()
	{	
		f_persistentdocument_TransactionManager::getInstance()->rollBack();
		if ($this->dbToBeCleaned)
		{
			$this->cleanDB();
			$this->dbToBeCleaned = false;
		}
	}
	
	/**
	 * Allow to import XML setup data from a filepath
	 * Place your XML files in setup of testing module
	 * @param string $sampleFile
	 */
	protected function importSample($sampleFile)
	{
		$scriptReader = import_ScriptReader::getInstance();
		try 
		{
			$scriptReader->executeModuleScript('testing', $sampleFile);
		} 
		catch (Exception $e)
		{
			echo $e->getMessage();
		}
	}
	
	/**
	 * Made a dump of database
	 * Place this function at the top of SetUp method of your 
	 * test if you want to reset the database after it.
	 * Don't forget to call cleanDB at the end of tearDown method 
	 * WARNING: you don't have to call parent::setUp!
	 */
	protected static function snapshotDB()
	{
		self::$dbInfos = array();
		self::$dbInfos['name'] = Framework::getConfigurationValue('databases/webapp/database');
		self::$dbInfos['user'] = Framework::getConfigurationValue('databases/webapp/user');
		self::$dbInfos['password'] = Framework::getConfigurationValue('databases/webapp/password');
		
		self::$dumpPath = f_util_FileUtils::getTmpFile('changeTestIntegration', true);
		
		$tablesToIgnore = array('f_locale', 'f_cache', 'f_user_action_entry');
		$tablesIgnoringOptions = '';
		foreach ($tablesToIgnore as $tableToIgnore)
		{
			$tablesIgnoringOptions .= '--ignore-table=' . self::$dbInfos['name'] . '.' . $tableToIgnore . ' ';
		}
		$mysqldump = 'mysqldump -u ' . self::$dbInfos['user'] . ' -p' . self::$dbInfos['password'] . ' ' . self::$dbInfos['name'] . ' ' . $tablesIgnoringOptions . '--opt > ' . self::$dumpPath;
		
		system($mysqldump);
	}
	
	/**
	 * Clean the database
	 * Don't call this method directly in your test, use
	 * the boolean $this->dbToBeClean = true to clean the DB
	 * Don't forget to create the snapshot in your 
	 * setUpBeforeClass method by calling self::snapshotDB
	 * @see testing_IntegrationTestBase::snapshotDB
	 * @throws Exception
	 */
	protected function cleanDB()
	{
		if (!f_util_StringUtils::isEmpty(self::$dumpPath))
		{	
			system('mysql -u ' . self::$dbInfos['user'] . ' -p' . self::$dbInfos['password'] . ' ' . self::$dbInfos['name'] . ' < ' . self::$dumpPath);
		}
		else
		{
			throw new Exception('Database was not cleaned. ' . __METHOD__ . ' need a snapshot of Database. Please made a snapshot before begin of the test by calling ' . __CLASS__ . '::snapshotDB method in setUp.');
		}
	}
	
	/**
	 * Place this function in your setUp if you want
	 * to check up your logs.
	 * Don't forget to call compareLogs at the end of your test
	 * @see testing_IntegrationTestBase::compareLogs
	 */
	protected function getLogs()
	{
		//Log level must be WARN !
		if (AG_LOGGING_LEVEL != 'WARN')
		{
			throw new Exception('Log level must be WARN, change your config before calling: ' . __METHOD__);
		}
		
		$this->phperrorLog = f_util_FileUtils::getTmpFile('phperrorLog');
		$this->applicationLog = f_util_FileUtils::getTmpFile('applicationLog');
		f_util_FileUtils::cp(f_util_FileUtils::buildAbsolutePath(CHANGE_LOG_DIR, 'phperror.log'), $this->phperrorLog, f_util_FileUtils::OVERRIDE);
		f_util_FileUtils::cp(f_util_FileUtils::buildAbsolutePath(CHANGE_LOG_DIR, 'application.log'), $this->applicationLog, f_util_FileUtils::OVERRIDE);
	}
	
	/**
	 * Place this function at the end of your test if you want
	 * to check up your logs.
	 * Don't forget to call getLogs in your setUp
	 * @see testing_IntegrationTestBase::getLogs
	 */
	protected function compareLogs()
	{
		if ($this->phperrorLog != '' && $this->applicationLog != '')
		{
			$newPhpErrorLog = f_util_FileUtils::buildAbsolutePath(CHANGE_LOG_DIR, 'phperror.log');
			if (file_exists($newPhpErrorLog))
			{
				$diffPhpErrorLog = array();
				$cmd = 'diff ' . $newPhpErrorLog . ' ' . $this->phperrorLog;
				exec($cmd, $diffPhpErrorLog);
				$this->assertEmpty($diffPhpErrorLog, 'phperror.log was not empty: ' . implode(PHP_EOL, $diffPhpErrorLog));
			}
			
			$newApplicationLog = f_util_FileUtils::buildAbsolutePath(CHANGE_LOG_DIR, 'application.log');
			if (file_exists($newApplicationLog))
			{
				$diffApplicationLog = array();
				$cmd = 'diff ' . $newApplicationLog . ' ' . $this->applicationLog;
				exec($cmd, $diffApplicationLog);
				$this->assertEmpty($diffApplicationLog, 'application.log was not empty: ' . implode(PHP_EOL, $diffApplicationLog));
			}
		}
		else
		{
			$this->fail('Did you call: ' . __METHOD__ . ' in your test without calling getLogs in your setUp?');
		}
	}
	
	/**
	 * Usefull when a document need to be refreshed
	 */
	protected function refreshCache()
	{
		f_persistentdocument_PersistentProvider::getInstance()->setDocumentCache(false);
		f_persistentdocument_PersistentProvider::getInstance()->setDocumentCache(true);
	}
}

