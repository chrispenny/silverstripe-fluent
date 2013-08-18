<?php

/**
 * Tests fluent
 * 
 * @see SiteTree
 * @package fluent
 * @author Damian Mooyman <damian.mooyman@gmail.com>
 */
class FluentTest extends SapphireTest {
	
	protected static $fixture_file = 'FluentTest.yml';
	
	protected $extraDataObjects = array(
		'FluentTest_TranslatedObject',
		'FluentTest_FilteredObject'
	);

	protected $illegalExtensions = array(
		'SiteTree' => array(
			'SiteTreeSubsites',
			'Translatable'
		)
	);
	
	protected $_original_config_locales = null;
	protected $_original_config_default = null;
	protected $_original_config_aliases = null;
	
	public function setUpOnce() {
		
		// Ensure that Fluent doesn't interfere with scaffolding records from FluentTest.yml
		FluentExtension::set_enable_write_augmentation(false);
		
		$this->_original_config_locales = Fluent::config()->locales;
		$this->_original_config_default = Fluent::config()->default_locale;
		$this->_original_config_aliases = Fluent::config()->aliases;
		
		// Tweak configuration
		Config::inst()->remove('Fluent', 'locales');
		Config::inst()->update('Fluent', 'locales', array('fr_CA', 'en_NZ', 'en_US', 'es_ES'));
		Config::inst()->remove('Fluent', 'default_locale');
		Config::inst()->update('Fluent', 'default_locale', 'fr_CA');
		Config::inst()->remove('Fluent', 'aliases');
		Config::inst()->update('Fluent', 'aliases', array(
			'en_US' => 'usa'
		));
		Fluent::set_persist_locale('fr_CA');
		
		// Force db regeneration using the above values
		self::kill_temp_db();
		self::create_temp_db();
		$this->resetDBSchema(true);
		
		parent::setUpOnce();
		
		FluentExtension::set_enable_write_augmentation(true);
	}
	
	public function setUp() {
		
		// Ensure that Fluent doesn't interfere with scaffolding records from FluentTest.yml
		FluentExtension::set_enable_write_augmentation(false);
		parent::setUp();
		FluentExtension::set_enable_write_augmentation(true);
		
		// Reset fluent locale
		Fluent::set_persist_locale('fr_CA');
	}
	
	public function tearDownOnce() {
		
		parent::tearDownOnce();
		
		Config::inst()->update('Fluent', 'locales', $this->_original_config_locales);
		Config::inst()->update('Fluent', 'default_locale', $this->_original_config_default);
		Config::inst()->update('Fluent', 'aliases', $this->_original_config_aliases);
		Fluent::set_persist_locale(null);
		
		self::kill_temp_db();
		self::create_temp_db();
		$this->resetDBSchema(true);
	}
	
	/**
	 * Test that URLS for pages are generated correctly
	 */
	public function testFluentURLs() {
		$home = $this->objFromFixture('Page', 'home');
		$about = $this->objFromFixture('Page', 'about');
		$staff = $this->objFromFixture('Page', 'staff');
		
		$this->assertEquals('/fr_CA/', $home->Link());
		$this->assertEquals('/fr_CA/about-us/', $about->Link());
		$this->assertEquals('/fr_CA/about-us/my-staff/', $staff->Link());
	}
	
	/**
	 * Test that alternate urls for a page work
	 */
	public function testAlternateURLs() {
		$home = $this->objFromFixture('Page', 'home');
		$about = $this->objFromFixture('Page', 'about');
		$staff = $this->objFromFixture('Page', 'staff');
		
		$this->assertEquals('/en_NZ/', $home->LocaleLink('en_NZ'));
		$this->assertEquals('/es_ES/', $home->LocaleLink('es_ES'));
		$this->assertEquals('/en_NZ/about-us/', $about->LocaleLink('en_NZ'));
		$this->assertEquals('/es_ES/about-us/', $about->LocaleLink('es_ES'));
		$this->assertEquals('/en_NZ/about-us/my-staff/', $staff->LocaleLink('en_NZ'));
		$this->assertEquals('/es_ES/about-us/my-staff/', $staff->LocaleLink('es_ES'));
	}
	
	/**
	 * Test that alternate baseurls work
	 */
	public function testAlternateBaseURLS() {
		$oldURL = Director::baseURL();
		Config::inst()->update('Director', 'alternate_base_url', '/mysite/mvc1');
		
		$home = $this->objFromFixture('Page', 'home');
		$about = $this->objFromFixture('Page', 'about');
		$staff = $this->objFromFixture('Page', 'staff');
		
		// Test Link
		$this->assertEquals('/mysite/mvc1/fr_CA/', $home->Link());
		$this->assertEquals('/mysite/mvc1/fr_CA/about-us/', $about->Link());
		$this->assertEquals('/mysite/mvc1/fr_CA/about-us/my-staff/', $staff->Link());
		
		// Test LocaleLink
		$this->assertEquals('/mysite/mvc1/en_NZ/', $home->LocaleLink('en_NZ'));
		$this->assertEquals('/mysite/mvc1/es_ES/', $home->LocaleLink('es_ES'));
		$this->assertEquals('/mysite/mvc1/en_NZ/about-us/', $about->LocaleLink('en_NZ'));
		$this->assertEquals('/mysite/mvc1/es_ES/about-us/', $about->LocaleLink('es_ES'));
		$this->assertEquals('/mysite/mvc1/en_NZ/about-us/my-staff/', $staff->LocaleLink('en_NZ'));
		$this->assertEquals('/mysite/mvc1/es_ES/about-us/my-staff/', $staff->LocaleLink('es_ES'));
		
		if($oldURL) {
			Config::inst()->update('Director', 'alternate_base_url', $oldURL);
		} else {
			Config::inst()->remove('Director', 'alternate_base_url');
		}
	}
	
	/**
	 * Test that aliases for a URL work
	 */
	public function testAliases() {
		$home = $this->objFromFixture('Page', 'home');
		$about = $this->objFromFixture('Page', 'about');
		$staff = $this->objFromFixture('Page', 'staff');
		
		$this->assertEquals('/usa/', $home->LocaleLink('en_US'));
		$this->assertEquals('/usa/about-us/', $about->LocaleLink('en_US'));
		$this->assertEquals('/usa/about-us/my-staff/', $staff->LocaleLink('en_US'));
	}
	
	/**
	 * Test that db fields for a translated objects is correctly extended
	 */
	public function testTranslateFields() {
		$db = DataObject::custom_database_fields('FluentTest_TranslatedObject');
		ksort($db);
		
		$this->assertEquals(array(
			'Description' => 'Text',
			'Description_en_NZ' => 'Text',
			'Description_en_US' => 'Text',
			'Description_es_ES' => 'Text',
			'Description_fr_CA' => 'Text',
			'ImageID' => 'ForeignKey',
			'ImageID_en_NZ' => 'Int',
			'ImageID_en_US' => 'Int',
			'ImageID_es_ES' => 'Int',
			'ImageID_fr_CA' => 'Int',
			'Title' => 'Varchar(255)',
			'Title_en_NZ' => 'Varchar(255)',
			'Title_en_US' => 'Varchar(255)',
			'Title_es_ES' => 'Varchar(255)',
			'Title_fr_CA' => 'Varchar(255)',
			'URLKey' => 'Text'
		), $db);
	}
	
	/**
	 * Test that filtered objects are queried correctly
	 */
	public function testFilteredObjects() {
		
		// Check locale that should have some items
		Fluent::set_persist_locale('en_NZ');
		$ids = DataObject::get('FluentTest_FilteredObject')->sort('Title')->column('Title');
		$this->assertEquals(array('filtered 1', 'filtered 2'), $ids);
		
		// Check locale that has some items
		Fluent::set_persist_locale('fr_CA');
		$ids = DataObject::get('FluentTest_FilteredObject')->sort('Title')->column('Title');
		$this->assertEquals(array('filtered 2'), $ids);
		
		// Put default locale back
		Fluent::set_persist_locale('fr_CA');
	}
	
	/*
	 * Test that locale filter can be augmented properly
	 */
	public function testUpdateFilteredObject() {
		
		// Test basic filter
		$ids = DataObject::get('FluentTest_FilteredObject')->sort('Title')->column('Title');
		$this->assertEquals(array('filtered 2'), $ids);
		
		// Test that item can have filter changed
		$item = $this->objFromFixture('FluentTest_FilteredObject', 'filtered2');
		$item->setFilteredLocales('fr_CA');
		$this->assertTrue($item->LocaleFilter_fr_CA);
		$this->assertEquals(array('fr_CA'), $item->getFilteredLocales());
		
		// Test exclusion
		$this->assertEquals(array('en_NZ', 'en_US', 'es_ES'), $item->getFilteredLocales(true));
		
		// Test item set to foreign locale limits this item
		$item->setFilteredLocales('en_NZ', 'en_US');
		$item->write();
		$ids = DataObject::get('FluentTest_FilteredObject')->sort('Title')->column('Title');
		$this->assertEquals(array(), $ids);
	}
	
	/**
	 * Test auto-scaffolding of CMS fields
	 */
	public function testCMSFields() {
		$filtered1 = $this->objFromFixture('FluentTest_TranslatedObject', 'translated1');
		$fields = $filtered1->getCMSFields()->dataFields();
		
		$this->assertEquals(array('Title', 'Description', 'URLKey', 'Image'), array_keys($fields));
	}
	
	/**
	 * Tests that DB fields are properly named
	 */
	public function testDBFieldNaming() {
		
		$this->assertEquals('Title_en_NZ', Fluent::db_field_for_locale('Title', 'en_NZ'));
		$this->assertEquals('ParentID_en_NZ', Fluent::db_field_for_locale('ParentID', 'en_NZ'));
	}
	/**
	 * Test that different filters against 
	 */
	public function testFilterConditions() {
		
		// In en_NZ locale
		Fluent::set_persist_locale('en_NZ');
		
		// Test basic search for english string
		$urls = DataObject::get('FluentTest_TranslatedObject')
			->filter('Title', 'this is an object')
			->column('URLKey');
		$this->assertEquals(array('my-translated'), $urls);
		
		// Test basic search where language specific field is blank
		$urls = DataObject::get('FluentTest_TranslatedObject')
			->filter('Title', 'This colour is blue')
			->column('URLKey');
		$this->assertEquals(array('my-translated-2'), $urls);
		
		// In en_US locale
		Fluent::set_persist_locale('en_US');
		
		// Test basic search for english string
		$urls = DataObject::get('FluentTest_TranslatedObject')
			->filter('Title', 'this is an object')
			->column('URLKey');
		$this->assertEquals(array('my-translated'), $urls);
		
		// Default value differs from locale specific value
		$urls = DataObject::get('FluentTest_TranslatedObject')
			->filter('Title', 'This colour is blue')
			->column('URLKey');
		$this->assertEquals(array(), $urls);
		
		$urls = DataObject::get('FluentTest_TranslatedObject')
			->filter('Title', 'This color is blue')
			->column('URLKey');
		$this->assertEquals(array('my-translated-2'), $urls);
		
		// In fr_CA locales
		Fluent::set_persist_locale('fr_CA');
		
		// Default value differs from locale specific value
		$urls = DataObject::get('FluentTest_TranslatedObject')
			->filter('Title', "il s'agit d'un objet")
			->column('URLKey');
		$this->assertEquals(array('my-translated'), $urls);
		
		$urls = DataObject::get('FluentTest_TranslatedObject')
			->filter('Title', 'this is an object')
			->column('URLKey');
		$this->assertEquals(array(), $urls);
		
		// Put default locale back
		Fluent::set_persist_locale('fr_CA');
	}
	
	/**
	 * Mock a browser HTTP locale (Accept-Language header) for the purpose of a test
	 * 
	 * @param string $lang Accept-Language header value
	 * @param callable $callback Callback
	 */
	public function withBrowserHTTPLanguage($lang, $callback) {
		$old = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
		$_SERVER['HTTP_ACCEPT_LANGUAGE'] = $lang;
		try { // Ensure failed test don't break state
			$callback($this);
		} catch(Exception $ex) {}
		$_SERVER['HTTP_ACCEPT_LANGUAGE'] = $old;
		if(!empty($ex)) throw $ex;
	}
	
	/**
	 * Mock a request URL for the purpose of a test
	 * 
	 * @param string $baseURL BaseURL to use
	 * @param string $url Request URL relative to BaseURL
	 * @param callable $callback Callback
	 */
	public function withURL($baseURL, $url, $callback) {
		
		// Set base URL
		$oldBaseURL = Config::inst()->get('Director', 'alternate_base_url');
		Config::inst()->update('Director', 'alternate_base_url', $baseURL);
		
		// Set URL
		$oldURL = $_SERVER['REQUEST_URI'];
		$_SERVER['REQUEST_URI'] = $url;
		
		try { // Ensure failed test don't break state
			$callback($this);
		} catch(Exception $ex) {}
		
		// Revert URL
		$_SERVER['REQUEST_URI'] = $oldURL;
		
		// Revert baseURL
		if($oldBaseURL) {
			Config::inst()->update('Director', 'alternate_base_url', $oldBaseURL);
		} else {
			Config::inst()->remove('Director', 'alternate_base_url');
		}
		if(!empty($ex)) throw $ex;
	}
	
	/**
	 * Push a controller onto the stack to mock a particular request
	 * 
	 * @param Controller $controller
	 * @param callback $callback
	 */
	public function withController(Controller $controller, $callback) {
		$controller->pushCurrent();
		try { // Ensure failed test don't break state
			$callback($this);
		} catch(Exception $ex) {}
		$controller->popCurrent();
		if(!empty($ex)) throw $ex;
	}
	
	/**
	 * Test browser detection of locale
	 */
	public function testDetectBrowserLocale() {
		
		$this->withBrowserHTTPLanguage('en-us;q=1,en;q=0.50', function($test) {
			$test->assertEquals('en_US', Fluent::detect_browser_locale());
		});
		
		$this->withBrowserHTTPLanguage('fr,en', function($test) {
			$test->assertEquals('fr_CA', Fluent::detect_browser_locale());
		});
		
		$this->withBrowserHTTPLanguage('en,fr-ca', function($test) {
			$test->assertEquals('en_NZ', Fluent::detect_browser_locale());
		});
		
		$this->withBrowserHTTPLanguage('en-nz,fr,en', function($test) {
			$test->assertEquals('en_NZ', Fluent::detect_browser_locale());
		});
		
		$this->withBrowserHTTPLanguage('fr-fr,en,fr', function($test) {
			$test->assertEquals('en_NZ', Fluent::detect_browser_locale());
		});
		
		$this->withBrowserHTTPLanguage('fr-fr,en-uk,ms', function($test) {
			$test->assertEmpty(Fluent::detect_browser_locale());
		});
	}
	
	/**
	 * Tests overriding locale values
	 */
	public function testWithLocale() {
				
		// Default value differs from locale specific value
		$names = DataObject::get('FluentTest_TranslatedObject')->sort('ID')->column('Title');
		self::assertEquals(array("il s'agit d'un objet", "Cette couleur est le bleu"), $names);
				
		// In en_NZ locale
		$names = Fluent::with_locale('en_NZ', function() {
			return DataObject::get('FluentTest_TranslatedObject')->sort('ID')->column('Title');
		});
		$this->assertEquals(array("this is an object", "This colour is blue"), $names);
		
		// Use default locale again
		$names = DataObject::get('FluentTest_TranslatedObject')->sort('ID')->column('Title');
		$this->assertEquals(array("il s'agit d'un objet", "Cette couleur est le bleu"), $names);
		
	}
	
	/**
	 * Test write operations with overriding locale values
	 */
	public function testWriteWithLocale() {
		
		// Test creation in default locale
		$item = new FluentTest_TranslatedObject();
		$item->Title = 'Test Title';
		$item->write();
		$itemID = $item->ID;
		
		// Test basic detail
		$item = FluentTest_TranslatedObject::get()->byId($itemID);
		$this->assertEquals('Test Title', $item->Title);
		
		// Test update in alternate locale
		Fluent::with_locale('es_ES', function() use ($itemID) {
			$item = FluentTest_TranslatedObject::get()->byId($itemID);
			$item->Title = 'Spanish Title';
			$item->write();
		});
		
		// Default title is unchanged
		$item = FluentTest_TranslatedObject::get()->byId($itemID);
		$this->assertEquals('Test Title', $item->Title);
		
		// Test previously set alternate locale title change persists
		$esTitle = Fluent::with_locale('es_ES', function() use ($itemID) {
			$item = FluentTest_TranslatedObject::get()->byId($itemID);
			return $item->Title;
		});
		$this->assertEquals('Spanish Title', $esTitle);
		
		// Test object created in alternate locale
		$item2ID = Fluent::with_locale('es_ES', function() {
			$item2 = new FluentTest_TranslatedObject();
			$item2->Title = 'Spanish 2';
			$item2->write();
			return $item2->ID;
		});
		
		// Default title should be set 
		$item2 = FluentTest_TranslatedObject::get()->byId($item2ID);
		$this->assertEquals('Spanish 2', $item2->Title);
		
		// Change title
		$item2->Title = 'English 2';
		$item2->write();
		
		// check alternate locale title unchanged
		$es2Title = Fluent::with_locale('es_ES', function() use ($item2ID) {
			$item2 = FluentTest_TranslatedObject::get()->byId($item2ID);
			return $item2->Title;
		});
		$this->assertEquals($es2Title, 'Spanish 2');
		
		// Test that object selected in default locale has the recently changed title
		$item2 = FluentTest_TranslatedObject::get()->byId($item2ID);
		$this->assertEquals('English 2', $item2->Title);
		
	}
	
	public function testFrontendDetection() {
		
		// Check that test controller counts as frontend
		$this->assertTrue(Fluent::is_frontend());
		$this->assertTrue(Fluent::is_frontend(true));
		
		// Check detection based on URL - frontend
		$this->withURL('/mybase/', '/mybase/about/us', function($test) {
			$test->assertTrue(Fluent::is_frontend(true));
		});
		$this->withURL('/mybase/', 'mybase/about/us', function($test) {
			$test->assertTrue(Fluent::is_frontend(true));
		});
		$this->withURL('/', '/about/us', function($test) {
			$test->assertTrue(Fluent::is_frontend(true));
		});
		$this->withURL('/', 'about/us', function($test) {
			$test->assertTrue(Fluent::is_frontend(true));
		});
		
		// Check detection based on URL - admin
		$this->withURL('/mybase/', '/mybase/admin/pages', function($test) {
			$test->assertFalse(Fluent::is_frontend(true));
		});
		$this->withURL('/mybase/', 'mybase/admin/pages', function($test) {
			$test->assertFalse(Fluent::is_frontend(true));
		});
		$this->withURL('/', '/admin/pages', function($test) {
			$test->assertFalse(Fluent::is_frontend(true));
		});
		$this->withURL('/', 'admin/pages', function($test) {
			$test->assertFalse(Fluent::is_frontend(true));
		});
		
		// Test detection based on controller
		$this->withController(new ModelAsController(), function($test) {
			$test->assertTrue(Fluent::is_frontend());
		});
		$this->withController(new LeftAndMain(), function($test) {
			$test->assertFalse(Fluent::is_frontend());
		});
	}
}

/**
 * Test class for fluent translated objects
 */
class FluentTest_TranslatedObject extends DataObject implements TestOnly {
	
	private static $extensions = array(
		'FluentExtension'
	);
	
	private static $db = array(
		'Title' => 'Varchar(255)',
		'Description' => 'Text',
		'URLKey' => 'Text'
	);
	
	private static $has_one = array(
		'Image' => 'Image'
	);
	
	private static $translate = array(
		'Title',
		'Description',
		'ImageID'
	);
}

class FluentTest_FilteredObject extends DataObject implements TestOnly {
	
	private static $extensions = array(
		'FluentFilteredExtension'
	);
	
	private static $db = array(
		'Title' => 'Varchar(255)'
	);
	
}