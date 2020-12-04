<?php
	namespace BlockCacher;
	
	use PHPUnit\Framework\TestCase;
	
	class BlockCacherTests extends TestCase
	{
		/** @var BlockCacher $cacher */
		private $cacher;
		
		const CachePrefix = 'test-';
		
		public function setUp(): void
		{
			parent::setUp();
			$cacheDirectory = __DIR__ . '/cache/';
			exec("rm -rf \"$cacheDirectory\"");
			$this->cacher = new BlockCacher($cacheDirectory . bin2hex(random_bytes(8)), self::CachePrefix);
			$this->cacher->clear();
		}
		
		public function tearDown(): void
		{
			$this->cacher->clear();
			$cacheDirectory = $this->cacher->directory();
			rmdir($cacheDirectory);
			parent::tearDown();
		}
		
		public function testGetAndStoreKey()
		{
			$cacher = $this->cacher;
			$this->assertNull($cacher->get('someKey'));
			$this->assertTrue($cacher->store('someKey', true));
			$this->assertTrue($cacher->get('someKey'));
			$this->assertNull($cacher->get('someKey', -1));
			
			$this->assertTrue($cacher->storeText('someKey', 'Hello, world!'));
			$this->assertEquals('Hello, world!', $cacher->getText('someKey'));
		}
		
		public function testKeyExists()
		{
			$cacher = $this->cacher;
			$this->assertFalse($cacher->exists('someKey'));
			$this->assertTrue($cacher->store('someKey', true));
			$this->assertTrue($cacher->exists('someKey'));
		}
		
		public function testStartAndEnd()
		{
			$cacher = $this->cacher;
			$value = 'Hello, world!';
			if ($cacher->start('buffer'))
				echo $value;
			$buffer = $cacher->end(false);
			
			$this->assertFalse($buffer->hit);
			$this->assertEquals($value, $buffer->contents);
			
			$hit = true;
			if ($cacher->start('buffer'))
				$hit = false;
			$buffer = $cacher->end(false);
			$this->assertTrue($hit);
			$this->assertEquals($value, $buffer->contents);
		}
		
		public function testClear()
		{
			$cacher = $this->cacher;
			$cacher->store('other', true);
			for($i = 0; $i < 10; $i++)
				$cacher->store("key-$i", true);
			$results = $cacher->clear('key-*');
			$this->assertEquals(10, $results->count());
			$this->assertEquals(10, $results->total());
			$results = $cacher->clear('*');
			$this->assertEquals(1, $results->count());
		}
		
		public function testGenerate()
		{
			$cacher = $this->cacher;
			$generated = false;
			$data = $cacher->generate($key = 'generated', function() use(&$generated)
			{
				$generated = true;
				return 'Data';
			});
			
			$this->assertEquals('Data', $data);
			$this->assertEquals(true, $generated);
			
			$generatedTwice = false;
			$data = $cacher->generate($key = 'generated', function() use(&$generatedTwice)
			{
				$generatedTwice = true;
				return 'Data1';
			});
			
			$this->assertEquals('Data', $data);
			$this->assertEquals(false, $generatedTwice);
		}
	}