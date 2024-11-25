<?php declare(strict_types=1);

namespace Kekos\ParseRequestBodyMiddleware;

use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UploadedFileFactoryInterface;
use Psr\Http\Message\UploadedFileInterface;

use function array_map;
use function is_array;

use const UPLOAD_ERR_OK;

/**
 * @phpstan-type PhpSingleFileStruct array{
 *     tmp_name: string,
 *     error: int,
 *     size: int,
 *     name: string,
 *     type: string,
 * }
 * @phpstan-type PhpMultiFileStruct array{
 *     tmp_name: array<array-key, string>,
 *     error: array<array-key, int>,
 *     size: array<array-key, int>,
 *     name: array<array-key, string>,
 *     type: array<array-key, string>,
 * }
 * @phpstan-type UploadedFileArray array<array-key, UploadedFileInterface>
 * @phpstan-type RecursiveUploadedFileArray array<array-key, UploadedFileInterface|UploadedFileArray>
 */
final class UploadedFileCollectionFactory
{
    public function __construct(
        private UploadedFileFactoryInterface $uploaded_file_factory,
        private StreamFactoryInterface $stream_factory,
    ) {
    }

    /**
     * @param array<array-key, mixed> $php_files
     * @return RecursiveUploadedFileArray
     */
    public function fromPhpFilesArray(array $php_files): array
    {
        $collection = [];

        foreach ($php_files as $key => $file) {
            if (!is_array($file)) {
                continue;
            }

            if (isset($file['tmp_name'])) {
                /** @var PhpSingleFileStruct $file */
                $collection[$key] = $this->createPsrUploadedFileFromStruct($file);
            } else {
                $collection[$key] = $this->fromPhpFilesArray($file);
            }
        }

        return $collection;
    }

    /**
     * @param PhpSingleFileStruct|PhpMultiFileStruct $php_file
     * @return RecursiveUploadedFileArray|UploadedFileInterface
     */
    private function createPsrUploadedFileFromStruct(array $php_file): array|UploadedFileInterface
    {
        if (is_array($php_file['tmp_name'])) {
            return $this->transposeNestedStructToFlat($php_file);
        }

        if ($php_file['error'] !== UPLOAD_ERR_OK) {
            $stream = $this->stream_factory->createStream();
        } else {
            $stream = $this->stream_factory->createStreamFromFile($php_file['tmp_name']);
        }

        return $this->uploaded_file_factory->createUploadedFile(
            $stream,
            (int) $php_file['size'],
            (int) $php_file['error'],
            $php_file['name'],
            $php_file['type']
        );
    }

    /**
     * @param PhpMultiFileStruct $nested_php_file
     * @return RecursiveUploadedFileArray
     */
    private function transposeNestedStructToFlat(array $nested_php_file): array
    {
        $collection = [];

        foreach ($nested_php_file as $struct_key => $values) {
            if (!is_array($values)) {
                continue;
            }

            foreach ($values as $key => $value) {
                $collection[$key][$struct_key] = $value;
            }
        }

        return array_map([$this, 'createPsrUploadedFileFromStruct'], $collection);
    }
}
