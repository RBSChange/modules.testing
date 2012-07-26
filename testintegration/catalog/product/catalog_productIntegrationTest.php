<?php
/**
 * catalog_productIntegrationTest test case.
 */
class catalog_productIntegrationTest extends testing_IntegrationTestBase
{
	/**
	 * @var catalog_persistentdocument_simpleproduct
	 */
	protected $simpleProduct;
	
	public static function setUpBeforeClass()
	{
		self::snapshotDB();
	}
	
	/**
	 * Prepares the environment before running a test.
	 */
	protected function setUp()
	{
		parent::setUp($this->getName());
		
		$this->getLogs();
		
		$this->simpleProduct = catalog_persistentdocument_simpleproduct::getNewInstance();
		$this->simpleProduct->setLabel('test');
		$this->simpleProduct->setCodeReference('TEST');
		$this->simpleProduct->save();
	}
	
	/**
	 * Cleans up the environment after running a test.
	 */
	protected function tearDown()
	{
		parent::tearDown();
	}
	
	/**
	 * Constructs the test case.
	 */
	public function __construct()
	{
	}
	
	public function testCreationOfSimpleProduct()
	{
		$productId = $this->simpleProduct->getId();
		$this->assertInternalType('integer', $productId);
		
		$testedProduct = catalog_persistentdocument_simpleproduct::getInstanceById($productId);
		$this->assertEquals('test', $testedProduct->getLabel());
		$this->compareLogs();
	}
	
	/**
	 * @depends testCreationOfSimpleProduct
	 * @expectedException			BaseException
	 * @expectedExceptionMessage	object-not-found
	 */
	public function testDeletingOfSimpleProduct()
	{
		$this->simpleProduct->delete();
		$this->simpleProduct->getId();
		$testedProduct = catalog_persistentdocument_simpleproduct::getInstanceById($this->simpleProduct->getId());
		$this->compareLogs();
	}
	
	/**
	 * @depends testCreationOfSimpleProduct
	 */
	public function testPutSimpleProductIntoShelf()
	{
		change_ConfigurationService::getInstance()->addVolatileProjectConfigurationNamedEntry('databases/webapp/embededTransaction', '10');
		//Check if database is not corrupt (SHELF-1 must not exist)
		$shelf1 = catalog_ShelfService::getInstance()->createQuery()
			->add(Restrictions::eq('codeReference', 'SHELF-1'))
			->findUnique();
		$this->assertNull($shelf1, 'If a shelf named SHELF-1 exists it means that your database is corrupted,' .
			'Please reset your database manually');

		$this->importSample('catalog/product.xml');

		$shelf1 = catalog_ShelfService::getInstance()->createQuery()
			->add(Restrictions::eq('codeReference', 'SHELF-1'))
			->findUnique();
		$this->assertInstanceOf('catalog_persistentdocument_shelf' ,$shelf1);
		$this->assertEquals('shelf1', $shelf1->getLabel());

		$this->simpleProduct->addShelf($shelf1);
		$this->simpleProduct->save();
		
		$this->assertEquals(1, $this->simpleProduct->getShelfCount());
		$this->assertEquals($shelf1->getLabel(), $this->simpleProduct->getShelf(0)->getLabel());
		
		//Place the product in another shelf
		$shelf2 = catalog_ShelfService::getInstance()->createQuery()
			->add(Restrictions::eq('codeReference', 'SHELF-2'))
			->findUnique();
		$this->assertInstanceOf('catalog_persistentdocument_shelf', $shelf2);
		$this->assertEquals('shelf2', $shelf2->getLabel());
		
		$this->simpleProduct->addShelf($shelf2);
		$this->simpleProduct->save();
		
		$this->assertEquals(2, $this->simpleProduct->getShelfCount());
		$this->assertEquals($shelf2->getLabel(), $this->simpleProduct->getShelf(1)->getLabel());
		
		//Delete simple product from the first shelf
		$this->simpleProduct->removeShelf($shelf1);
		$this->simpleProduct->save();
		
		$this->assertEquals(1, $this->simpleProduct->getShelfCount());
		$this->assertEquals($shelf2->getLabel(), $this->simpleProduct->getShelf(0)->getLabel());
		$this->compareLogs();
	}
	
}