<?php declare(strict_types=1);


use Kekos\ParseRequestBodyMiddleware\UploadedFileCollectionFactory;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\UploadedFileInterface;

final class UploadedFileCollectionFactoryTest extends TestCase
{
    private static Psr17Factory $psr17_factory;
    private static UploadedFileCollectionFactory $file_collection_factory;

    public static function setUpBeforeClass(): void
    {
        self::$psr17_factory = new Psr17Factory();
        self::$file_collection_factory = new UploadedFileCollectionFactory(self::$psr17_factory, self::$psr17_factory);
    }

    public function testOneDimensionalFilesArray(): void
    {
		$file_struct = [
			'tmp_name' => __DIR__ . '/Fixtures/uploaded_file.txt',
			'size' => 26,
			'error' => UPLOAD_ERR_OK,
			'name' => 'uploaded_file.txt',
			'type' => 'text/plain',
		];
        $files = [
			'foo' => $file_struct,
		];

        $collection = self::$file_collection_factory->fromPhpFilesArray($files);

        $this->assertArrayHasKey('foo', $collection);
        $this->assertInstanceOf(UploadedFileInterface::class, $collection['foo']);
		$this->assertStringEqualsFile($file_struct['tmp_name'], (string) $collection['foo']->getStream());
		$this->assertEquals($file_struct['size'], $collection['foo']->getSize());
		$this->assertEquals($file_struct['error'], $collection['foo']->getError());
		$this->assertEquals($file_struct['name'], $collection['foo']->getClientFilename());
		$this->assertEquals($file_struct['type'], $collection['foo']->getClientMediaType());
    }

    public function testTwoDimensionalFilesArray(): void
    {
		$tmp_nameA = __DIR__ . '/Fixtures/uploaded_fileA.txt';
		$tmp_nameB = __DIR__ . '/Fixtures/uploaded_fileB.txt';
		$sizeA = 26;
		$sizeB = 27;
		$errorAB = UPLOAD_ERR_OK;
		$errorC = UPLOAD_ERR_NO_FILE;
		$nameA = 'uploaded_fileA.txt';
		$nameB = 'uploaded_fileB.txt';
		$nameC = 'uploaded_fileC.png';
		$typeAB = 'text/plain';
		$typeC = 'image/png';

        $files = [
			'multi' => [
				'tmp_name' => [
					'A' => $tmp_nameA,
					'B' => $tmp_nameB,
				],
				'size' => [
					'A' => $sizeA,
					'B' => $sizeB,
				],
				'error' => [
					'A' => $errorAB,
					'B' => $errorAB,
				],
				'name' => [
					'A' => $nameA,
					'B' => $nameB,
				],
				'type' => [
					'A' => $typeAB,
					'B' => $typeAB,
				],
			],
			'single' => [
				'tmp_name' => '',
				'size' => 0,
				'error' => $errorC,
				'name' => $nameC,
				'type' => $typeC,
			],
		];

        $collection = self::$file_collection_factory->fromPhpFilesArray($files);

        $this->assertArrayHasKey('multi', $collection);
        $this->assertIsArray($collection['multi']);
        $this->assertArrayHasKey('A', $collection['multi']);
        $this->assertInstanceOf(UploadedFileInterface::class, $collection['multi']['A']);
		$this->assertStringEqualsFile($tmp_nameA, (string) $collection['multi']['A']->getStream());
		$this->assertEquals($sizeA, $collection['multi']['A']->getSize());
		$this->assertEquals($errorAB, $collection['multi']['A']->getError());
		$this->assertEquals($nameA, $collection['multi']['A']->getClientFilename());
		$this->assertEquals($typeAB, $collection['multi']['A']->getClientMediaType());

        $this->assertArrayHasKey('B', $collection['multi']);
        $this->assertInstanceOf(UploadedFileInterface::class, $collection['multi']['B']);
		$this->assertStringEqualsFile($tmp_nameB, (string) $collection['multi']['B']->getStream());
		$this->assertEquals($sizeB, $collection['multi']['B']->getSize());
		$this->assertEquals($errorAB, $collection['multi']['B']->getError());
		$this->assertEquals($nameB, $collection['multi']['B']->getClientFilename());
		$this->assertEquals($typeAB, $collection['multi']['B']->getClientMediaType());

        $this->assertArrayHasKey('single', $collection);
        $this->assertInstanceOf(UploadedFileInterface::class, $collection['single']);
		$this->assertEquals(0, $collection['single']->getSize());
		$this->assertEquals($errorC, $collection['single']->getError());
		$this->assertEquals($nameC, $collection['single']->getClientFilename());
		$this->assertEquals($typeC, $collection['single']->getClientMediaType());
    }
}
