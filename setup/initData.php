<?php
/**
 * @package modules.testing.setup
 */
class testing_Setup extends object_InitDataSetup
{
	public function install()
	{
		$this->copyToWebsiteRootFolder('phpunit.xml');
		$this->copyToWebsiteRootFolder('phpunitBootstrap.php');
		$phpunitFilename = PEAR_DIR . DIRECTORY_SEPARATOR . 'phpunit.php';
		if (!chmod($phpunitFilename, 0755))
		{
			$this->addError('Problem occured during the right modification (chmod) on phpunit.php');
		}
		else
		{
			$this->addInfo('right on pear/phpunit.php has been changed to 0755');
		}
		
		$this->changeTheDirectoryOfTestSuite();
	}

	/**
	 * @return String[]
	 */
	public function getRequiredPackages()
	{
		// Return an array of packages name if the data you are inserting in
		// this file depend on the data of other packages.
		// Example:
		// return array('modules_website', 'modules_users');
		return array();
	}
	
	/**
	 * @param string $fileName file name to copy to website root folder 
	 */
	private function copyToWebsiteRootFolder($fileName)
	{
		$ds = DIRECTORY_SEPARATOR;
		$source = AG_MODULE_DIR . $ds . 'testing' . $ds . 'setup' . $ds . $fileName;
		$dest = WEBEDIT_HOME . $ds . $fileName;
		if (!copy($source, $dest))
		{
			$this->addError('Problem occured during the copy of the file: ' . $fileName);
		}
		else
		{
			$this->addInfo($fileName . ' has been copied in your website root folder');
		}
	}
	
	private function changeTheDirectoryOfTestSuite()
	{
		$testsFoldersName = AG_MODULE_DIR . DIRECTORY_SEPARATOR . '*' .
			DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR . 'unit';
		
		$phpunitXMLPath = WEBEDIT_HOME . DIRECTORY_SEPARATOR . 'phpunit.xml';
		$phpunitXML = new DOMDocument('1.0', 'UTF-8');
		$phpunitXML->load($phpunitXMLPath);
		$testSuites = $phpunitXML->getElementsByTagName('testsuite');
		if ($testSuites->length > 0)
		{
			foreach ($testSuites->item(0)->childNodes as $node)
			{
				/* @var $node DOMNode */
				if ($node->localName == 'directory')
				{
					$node->appendChild($phpunitXML->createTextNode($testsFoldersName));
					break;
				}
			}
		}
		else
		{
			$this->addError('Problem occured during the manipulation of phpunit.xml');
		}
		$phpunitXML->save($phpunitXMLPath);
	}
}