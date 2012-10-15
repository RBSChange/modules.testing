<?php
/**
 * catalog_priceIntegrationTest test case.
 */
class catalog_priceIntegrationTest extends testing_IntegrationTestBase
{
	/**
	 * @var catalog_persistentdocument_simpleproduct
	 */
	protected $simpleProduct1;
	
	/**
	 * @var catalog_persistentdocument_declinedproduct
	 */
	protected $declinedProductSynchronized;
	
	/**
	 * @var catalog_persistentdocument_declinedproduct
	 */
	protected $declinedProductUnsynchronized;
	
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
		
		$this->importSample('catalog/price.xml');
		$this->simpleProduct1 = $this->getProductByCodeReference('SIMPLEPRODUCT-1');
		$this->assertInstanceOf('catalog_persistentdocument_simpleproduct', $this->simpleProduct1);
		
		$this->declinedProductSynchronized = catalog_DeclinedproductService::getInstance()->createQuery()
			->add(Restrictions::eq('codeReference', 'DECLINED-SYNCHRONIZED'))
			->findUnique();
		$this->assertInstanceOf('catalog_persistentdocument_declinedproduct', $this->declinedProductSynchronized);
		
		$this->declinedProductUnsynchronized = catalog_DeclinedproductService::getInstance()->createQuery()
			->add(Restrictions::eq('codeReference', 'DECLINED-UNSYNCHRONIZED'))
			->findUnique();
		$this->assertInstanceOf('catalog_persistentdocument_declinedproduct', $this->declinedProductUnsynchronized);
		
		
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
	
	/**
	 * @throws Exception
	 */
	public function testCreatePriceForProduct()
	{
		$price = catalog_persistentdocument_price::getNewInstance();
		$shop = catalog_ShopService::getInstance()->getDefaultShop();
		$price->setShopId($shop->getId());
		$price->setBillingAreaId($shop->getDefaultBillingArea()->getId());
		$price->setValueWithoutTax(50.0);
		$price->setProductId($this->simpleProduct1->getId());
		$price->save();
			
		$simpleProduct = $this->getProductByCodeReference('SIMPLEPRODUCT-1');
		$this->assertInstanceOf('catalog_persistentdocument_simpleproduct', $simpleProduct);
		$testedPrice = $simpleProduct->getPriceForCurrentShopAndCustomer();
		$this->assertEquals(50.0, $testedPrice->getValueWithoutTax());
		$this->compareLogs();
	}
	
	/**
	 * @depends testCreatePriceForProduct
	 */
	public function testGetPriceByZone()
	{
		$price = catalog_persistentdocument_price::getNewInstance();
		$shop = catalog_ShopService::getInstance()->getDefaultShop();
		$price->setShopId($shop->getId());
		$price->setBillingAreaId($shop->getDefaultBillingArea()->getId());
		$price->setValueWithoutTax(50.0);
		$price->setProductId($this->simpleProduct1->getId());
		$price->save();
		
		$price = catalog_persistentdocument_price::getNewInstance();
		$shop = catalog_ShopService::getInstance()->getDefaultShop();
		$price->setShopId($shop->getId());
		$gbArea = catalog_BillingareaService::getInstance()->createQuery()
			->add(Restrictions::eq('codeReference', 'GB'))
			->findUnique();
		$this->assertNotNull($gbArea);
		$price->setBillingAreaId($gbArea->getId());
		$price->setValueWithoutTax(39.04);
		$price->setProductId($this->simpleProduct1->getId());
		$price->save();
		
		$simpleProduct = $this->getProductByCodeReference('SIMPLEPRODUCT-1');
		$this->assertInstanceOf('catalog_persistentdocument_simpleproduct', $simpleProduct); 
		$testedPrice = $simpleProduct->getPriceForCurrentShopAndCustomer();
		$this->assertEquals(50.0, $testedPrice->getValueWithoutTax());
		
		$testedPriceGB = $simpleProduct->getPrice($shop, $gbArea, null);
		$this->assertEquals(39.04, $testedPriceGB->getValueWithoutTax());
		
		$this->compareLogs();
	}
	
	/**
	 * @depends testCreatePriceForProduct
	 */
	public function testGetPriceByNumberOfProduct()
	{
		$price = catalog_persistentdocument_price::getNewInstance();
		$shop = catalog_ShopService::getInstance()->getDefaultShop();
		$price->setShopId($shop->getId());
		$price->setBillingAreaId($shop->getDefaultBillingArea()->getId());
		$price->setValueWithoutTax(50.0);
		$price->setProductId($this->simpleProduct1->getId());
		$price->save();
		
		$price = catalog_persistentdocument_price::getNewInstance();
		$shop = catalog_ShopService::getInstance()->getDefaultShop();
		$price->setShopId($shop->getId());
		$price->setBillingAreaId($shop->getDefaultBillingArea()->getId());
		$price->setThresholdMin(5);
		$price->setValueWithoutTax(40.0);
		$price->setProductId($this->simpleProduct1->getId());
		$price->save();
		
		$price = catalog_persistentdocument_price::getNewInstance();
		$shop = catalog_ShopService::getInstance()->getDefaultShop();
		$price->setShopId($shop->getId());
		$price->setBillingAreaId($shop->getDefaultBillingArea()->getId());
		$price->setThresholdMin(10);
		$price->setValueWithoutTax(30.0);
		$price->setProductId($this->simpleProduct1->getId());
		$price->save();
		
		$simpleProduct = $this->getProductByCodeReference('SIMPLEPRODUCT-1');
		$this->assertInstanceOf('catalog_persistentdocument_simpleproduct', $simpleProduct);
		$testedPrice = $simpleProduct->getPriceForCurrentShopAndCustomer();
		$this->assertEquals(50.0, $testedPrice->getValueWithoutTax());
	
		$testedPrice = $simpleProduct->getPriceForCurrentShopAndCustomer(4.0);
		$this->assertEquals(50.0, $testedPrice->getValueWithoutTax());
		
		$testedPrice = $simpleProduct->getPriceForCurrentShopAndCustomer(4.9);
		$this->assertEquals(50.0, $testedPrice->getValueWithoutTax());
		
		$testedPrice = $simpleProduct->getPriceForCurrentShopAndCustomer(4.9999);
		$this->assertEquals(50.0, $testedPrice->getValueWithoutTax());
		
		$testedPrice = $simpleProduct->getPriceForCurrentShopAndCustomer(5.0);
		$this->assertEquals(40.0, $testedPrice->getValueWithoutTax());
		
		$testedPrice = $simpleProduct->getPriceForCurrentShopAndCustomer(9.0);
		$this->assertEquals(40.0, $testedPrice->getValueWithoutTax());
		
		$testedPrice = $simpleProduct->getPriceForCurrentShopAndCustomer(10.0);
		$this->assertEquals(30.0, $testedPrice->getValueWithoutTax());
		
		$testedPrice = $simpleProduct->getPriceForCurrentShopAndCustomer(11.0);
		$this->assertEquals(30.0, $testedPrice->getValueWithoutTax());
		
		$this->compareLogs();
	}
	
	/**
	 * @depends testCreatePriceForProduct
	 */
	public function testGetPriceDiscounted()
	{
		$price = catalog_persistentdocument_price::getNewInstance();
		$shop = catalog_ShopService::getInstance()->getDefaultShop();
		$price->setShopId($shop->getId());
		$price->setBillingAreaId($shop->getDefaultBillingArea()->getId());
		$price->setValueWithoutTax(50.0);
		$price->setProductId($this->simpleProduct1->getId());
		$price->save();
		
		$simpleProduct = $this->getProductByCodeReference('SIMPLEPRODUCT-1');
		$this->assertInstanceOf('catalog_persistentdocument_simpleproduct', $simpleProduct);
		$testedPrice = $simpleProduct->getPriceForCurrentShopAndCustomer();
		$this->assertEquals(50.0, $testedPrice->getValueWithoutTax());
		
		$price->setDiscountValue(45.0);
		$price->save();
		
		$testedPrice = $simpleProduct->getPriceForCurrentShopAndCustomer();
		$this->assertEquals(45.0, $testedPrice->getValueWithoutTax());
		$this->assertEquals(50.0, $testedPrice->getOldValueWithoutTax());
		$this->compareLogs();
	}
	
	/**
	 * @depends testCreatePriceForProduct
	 */
	public function testPriceTaxes()
	{
		$price = catalog_persistentdocument_price::getNewInstance();
		$shop = catalog_ShopService::getInstance()->getDefaultShop();
		$price->setShopId($shop->getId());
		$price->setBillingAreaId($shop->getDefaultBillingArea()->getId());
		$price->setValueWithoutTax(50.0);
		$price->setProductId($this->simpleProduct1->getId());
		$price->save();
		
		$simpleProduct = $this->getProductByCodeReference('SIMPLEPRODUCT-1');
		$this->assertInstanceOf('catalog_persistentdocument_simpleproduct', $simpleProduct);
		$testedPrice = $simpleProduct->getPriceForCurrentShopAndCustomer();
		$this->assertEquals(50.0, $testedPrice->getValueWithoutTax());
		$this->assertEquals(50.0, $testedPrice->getValueWithTax());
		
		$price->setTaxCategory('1');
		$price->save();

		$this->refreshCache();
		
		$simpleProduct = $this->getProductByCodeReference('SIMPLEPRODUCT-1');
		$this->assertInstanceOf('catalog_persistentdocument_simpleproduct', $simpleProduct);
		$testedPrice = $simpleProduct->getPriceForCurrentShopAndCustomer();
		$this->assertEquals(50.0, $testedPrice->getValueWithoutTax());
		$this->assertEquals(59.80, $testedPrice->getValueWithTax());
		$this->compareLogs();
	}
	
	/**
	 * @depends testCreatePriceForProduct
	 */
	public function testDatedDiscountPrices()
	{
		$price = catalog_persistentdocument_price::getNewInstance();
		$shop = catalog_ShopService::getInstance()->getDefaultShop();
		$price->setShopId($shop->getId());
		$price->setBillingAreaId($shop->getDefaultBillingArea()->getId());
		$price->setValueWithoutTax(50.0);
		$price->setProductId($this->simpleProduct1->getId());
		$price->save();
		
		$simpleProduct = $this->getProductByCodeReference('SIMPLEPRODUCT-1');
		$this->assertInstanceOf('catalog_persistentdocument_simpleproduct', $simpleProduct);
		$testedPrice = $simpleProduct->getPriceForCurrentShopAndCustomer();
		$this->assertEquals(50.0, $testedPrice->getValueWithoutTax());
		
		$price = catalog_persistentdocument_price::getNewInstance();
		$shop = catalog_ShopService::getInstance()->getDefaultShop();
		$price->setShopId($shop->getId());
		$price->setBillingAreaId($shop->getDefaultBillingArea()->getId());
		$price->setValueWithoutTax(50.0);
		$price->setDiscountValue(40.0);
		$startDate = date_Calendar::now()->add(date_Calendar::DAY, 1)->toString();
		$endDate = date_Calendar::now()->add(date_Calendar::DAY, 8)->toString();
		$price->setStartpublicationdate($startDate);
		$price->setEndpublicationdate($endDate);
		$price->setProductId($this->simpleProduct1->getId());
		$ps = catalog_PriceService::getInstance();
		$ps->insertPrice($price);
		
		$simpleProduct = $this->getProductByCodeReference('SIMPLEPRODUCT-1');
		$this->assertInstanceOf('catalog_persistentdocument_simpleproduct', $simpleProduct);
		$testedPrice = $simpleProduct->getPriceForCurrentShopAndCustomer();
		$this->assertEquals(50.0, $testedPrice->getValueWithoutTax());
		
		$allPrices = catalog_PriceService::getInstance()->createQuery()
			->add(Restrictions::eq('productId', $simpleProduct->getId()))
			->add(Restrictions::eq('shopId', $shop->getId()))
			->add(Restrictions::eq('billingAreaId', $shop->getDefaultBillingArea()->getId()))
			->addOrder(Order::asc('startpublicationdate'))->addOrder(Order::asc('priority'))
			->find();
		
		$oldPrice = $allPrices[0];
		$this->assertEquals(50.0, $oldPrice->getValueWithoutTax());
		$this->assertNull($oldPrice->getStartpublicationdate());
		$this->assertEquals($startDate, $oldPrice->getEndpublicationdate());
		
		$discountPrice = $allPrices[1];
		$this->assertEquals(40.0, $discountPrice->getValueWithoutTax());
		$this->assertEquals($startDate, $discountPrice->getStartpublicationdate());
		$this->assertEquals($endDate, $discountPrice->getEndpublicationdate());
		
		$afterDiscountDatePrice = $allPrices[2];
		$this->assertEquals(50.0, $afterDiscountDatePrice->getValueWithoutTax());
		$this->assertEquals($endDate, $afterDiscountDatePrice->getStartpublicationdate());
		$this->assertNull($afterDiscountDatePrice->getEndpublicationdate());
		$this->compareLogs();
	}
	
	public function testSynchronizedOnDeclinedProduct()
	{
		$price = catalog_persistentdocument_price::getNewInstance();
		$shop = catalog_ShopService::getInstance()->getDefaultShop();
		$price->setShopId($shop->getId());
		$price->setBillingAreaId($shop->getDefaultBillingArea()->getId());
		$price->setValueWithoutTax(50.0);
		$price->setProductId($this->declinedProductSynchronized->getId());
		$price->save();
		
		$declinedProductSynchronized = catalog_DeclinedproductService::getInstance()->createQuery()
			->add(Restrictions::eq('codeReference', 'DECLINED-SYNCHRONIZED'))
			->findUnique();
		$this->assertNotNull($declinedProductSynchronized);
		$this->assertInstanceOf('catalog_persistentdocument_declinedproduct', $declinedProductSynchronized);
		$testedProducts = $declinedProductSynchronized->getProductdeclinationArrayInverse();
		foreach ($testedProducts as $productDeclination)
		{
			/* @var $productDeclination catalog_persistentdocument_productdeclination */
			$this->assertEquals(50.0, $productDeclination->getPriceForCurrentShopAndCustomer()->getValueWithoutTax());
		}
		
		//desynchronize price
		$declinedProductSynchronized->setSynchronizePrices(false);
		$declinedProductSynchronized->save();
		
		$declinedProductSynchronized = catalog_DeclinedproductService::getInstance()->createQuery()
			->add(Restrictions::eq('codeReference', 'DECLINED-SYNCHRONIZED'))
			->findUnique();
		$this->assertNotNull($declinedProductSynchronized);
		$this->assertInstanceOf('catalog_persistentdocument_declinedproduct', $declinedProductSynchronized);
		$testedProducts = $declinedProductSynchronized->getProductdeclinationArrayInverse();
		foreach ($testedProducts as $productDeclination)
		{
			/* @var $productDeclination catalog_persistentdocument_productdeclination */
			$this->assertEquals(50.0, $productDeclination->getPriceForCurrentShopAndCustomer()->getValueWithoutTax());
			
			$price = $productDeclination->getPriceForCurrentShopAndCustomer();
			$randomPrice = 50.0 - mt_rand(5, 30);
			$price->setValueWithoutTax($randomPrice);
			$price->save();
			
			$this->assertEquals($randomPrice, $productDeclination->getPriceForCurrentShopAndCustomer()->getValueWithoutTax());
		}
	}
	
	public function testUnsynchronizedOnDeclinedProduct()
	{
		$shop = catalog_ShopService::getInstance()->getDefaultShop();
		$declinedProductUnsynchronized = catalog_DeclinedproductService::getInstance()->createQuery()
			->add(Restrictions::eq('codeReference', 'DECLINED-UNSYNCHRONIZED'))
			->findUnique();
		$this->assertNotNull($declinedProductUnsynchronized);
		$this->assertInstanceOf('catalog_persistentdocument_declinedproduct', $declinedProductUnsynchronized);
		$testedProducts = $declinedProductUnsynchronized->getProductdeclinationArrayInverse();
		foreach ($testedProducts as $productDeclination)
		{
			/* @var $productDeclination catalog_persistentdocument_productdeclination */
			$price = catalog_persistentdocument_price::getNewInstance();
			$price->setShopId($shop->getId());
			$price->setBillingAreaId($shop->getDefaultBillingArea()->getId());
			$randomPrice = 50.0 - mt_rand(5, 30);
			$price->setValueWithoutTax($randomPrice);
			$price->setProductId($productDeclination->getId());
			$price->save();
				
			$this->assertEquals($randomPrice, $productDeclination->getPriceForCurrentShopAndCustomer()->getValueWithoutTax());
		}
		$this->assertFalse($declinedProductUnsynchronized->getSynchronizePrices());
		
		//synchronize price from the first declination
		$firstProductDeclination = $testedProducts[0];
		$firstDeclinationPriceValue = $firstProductDeclination->getPriceForCurrentShopAndCustomer()->getValueWithoutTax();
		$declinedProductUnsynchronized->setSynchronizePricesFrom($firstProductDeclination->getId());
		$declinedProductUnsynchronized->save();
		
		/* @var $declinedProductUnsynchronized catalog_persistentdocument_declinedproduct */
		$this->assertTrue($declinedProductUnsynchronized->getSynchronizePrices());
		
		//Refresh the cached prices
		$this->refreshCache();
		$declinedProductUnsynchronized = catalog_DeclinedproductService::getInstance()->createQuery()
			->add(Restrictions::eq('codeReference', 'DECLINED-UNSYNCHRONIZED'))
			->findUnique();
		$this->assertNotNull($declinedProductUnsynchronized);
		$this->assertInstanceOf('catalog_persistentdocument_declinedproduct', $declinedProductUnsynchronized);
		$testedProducts = $declinedProductUnsynchronized->getProductdeclinationArrayInverse();
		
		foreach ($testedProducts as $productDeclination)
		{
			/* @var $productDeclination catalog_persistentdocument_productdeclination */
			$this->assertEquals($firstDeclinationPriceValue, $productDeclination->getPriceForCurrentShopAndCustomer()->getValueWithoutTax());
		}
	}
	
	public function testKit()
	{
		$shop = catalog_ShopService::getInstance()->getDefaultShop();
		
		$simpleProduct1 = $this->getProductByCodeReference('SIMPLEPRODUCT-1');
		$this->assertInstanceOf('catalog_persistentdocument_simpleproduct', $simpleProduct1);
		$price = catalog_persistentdocument_price::getNewInstance();
		$price->setShopId($shop->getId());
		$price->setBillingAreaId($shop->getDefaultBillingArea()->getId());
		$price->setValueWithoutTax(50.0);
		$price->setProductId($simpleProduct1->getId());
		$price->save();
		
		$simpleProduct2 = $this->getProductByCodeReference('SIMPLEPRODUCT-2');
		$this->assertInstanceOf('catalog_persistentdocument_simpleproduct', $simpleProduct2);
		$price = catalog_persistentdocument_price::getNewInstance();
		$price->setShopId($shop->getId());
		$price->setBillingAreaId($shop->getDefaultBillingArea()->getId());
		$price->setValueWithoutTax(45.0);
		$price->setProductId($simpleProduct2->getId());
		$price->save();
		
		$simpleProduct3 = $this->getProductByCodeReference('SIMPLEPRODUCT-3');
		$this->assertInstanceOf('catalog_persistentdocument_simpleproduct', $simpleProduct3);
		$price = catalog_persistentdocument_price::getNewInstance();
		$price->setShopId($shop->getId());
		$price->setBillingAreaId($shop->getDefaultBillingArea()->getId());
		$price->setValueWithoutTax(40.0);
		$price->setProductId($simpleProduct3->getId());
		$price->save();
		
		$declinedProduct1 = catalog_DeclinedproductService::getInstance()->createQuery()
			->add(Restrictions::eq('codeReference', 'DECLINED-SYNCHRONIZED'))
			->findUnique();
		$this->assertInstanceOf('catalog_persistentdocument_declinedproduct', $declinedProduct1);
		$price = catalog_persistentdocument_price::getNewInstance();
		$price->setShopId($shop->getId());
		$price->setBillingAreaId($shop->getDefaultBillingArea()->getId());
		$price->setValueWithoutTax(55.55);
		$price->setProductId($declinedProduct1->getId());
		$price->save();
		
		$productDeclination1 = $this->getProductByCodeReference('DECLINATION-1-UNSYNCHRONIZED');
		$this->assertInstanceOf('catalog_persistentdocument_productdeclination', $productDeclination1);
		$price = catalog_persistentdocument_price::getNewInstance();
		$price->setShopId($shop->getId());
		$price->setBillingAreaId($shop->getDefaultBillingArea()->getId());
		$price->setValueWithoutTax(61.00);
		$price->setProductId($productDeclination1->getId());
		$price->save();
		
		$productDeclination2 = $this->getProductByCodeReference('DECLINATION-2-UNSYNCHRONIZED');
		$this->assertInstanceOf('catalog_persistentdocument_productdeclination', $productDeclination2);
		$price = catalog_persistentdocument_price::getNewInstance();
		$price->setShopId($shop->getId());
		$price->setBillingAreaId($shop->getDefaultBillingArea()->getId());
		$price->setValueWithoutTax(62.00);
		$price->setProductId($productDeclination2->getId());
		$price->save();
		
		$productDeclination3 = $this->getProductByCodeReference('DECLINATION-3-UNSYNCHRONIZED');
		$this->assertInstanceOf('catalog_persistentdocument_productdeclination', $productDeclination2);
		$price = catalog_persistentdocument_price::getNewInstance();
		$price->setShopId($shop->getId());
		$price->setBillingAreaId($shop->getDefaultBillingArea()->getId());
		$price->setValueWithoutTax(63.00);
		$price->setProductId($productDeclination3->getId());
		$price->save();
		
		$productDeclination4 = $this->getProductByCodeReference('DECLINATION-4-UNSYNCHRONIZED');
		$this->assertInstanceOf('catalog_persistentdocument_productdeclination', $productDeclination4);
		$price = catalog_persistentdocument_price::getNewInstance();
		$price->setShopId($shop->getId());
		$price->setBillingAreaId($shop->getDefaultBillingArea()->getId());
		$price->setValueWithoutTax(64.00);
		$price->setProductId($productDeclination4->getId());
		$price->save();
		
		$kit = catalog_KitService::getInstance()->createQuery()
			->add(Restrictions::eq('codeReference', 'KIT-1'))
			->findUnique();
		$this->assertNotNull($kit);
		$this->markTestIncomplete('Kit needs more tests!');
	}
	
	/**
	 * 
	 * @param string $codeReference
	 * @return catalog_persistentdocument_product
	 */
	private function getProductByCodeReference($codeReference)
	{
		$product = catalog_ProductService::getInstance()->createQuery()
			->add(Restrictions::eq('codeReference', $codeReference))
			->findUnique();
		$this->assertNotNull($product, 'You code reference: ' . $codeReference . ' doesn\'t match any product');
		return $product;
	}

}