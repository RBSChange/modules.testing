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
	}
}