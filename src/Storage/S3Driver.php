<?php

namespace App\Storage;

use Aws\S3\S3Client;
use League\Flysystem\AwsS3V3\AwsS3V3Adapter;
use League\Flysystem\FilesystemAdapter;

class S3Driver implements DriverInterface
{
    private array $config;

    public function __construct(
        private StorageManager $storageManager
    ) {
        $storage = $storageManager->get(self::getName());

        $this->config = $storage ? $storage->getConfig() : self::getConfiguration();
    }

    public static function getName(): string
    {
        return 's3';
    }

    public static function getConfiguration(): array
    {
        return [
            'bucket' => null,
            'region' => null,
            'credentials' => [
                'key' => null,
                'secret' => null
            ]
        ];
    }

    /**
     * @return AwsS3V3Adapter
     */
    public function getAdapter(): FilesystemAdapter
    {
        return new AwsS3V3Adapter(
            new S3Client($this->config),
            $this->config['bucket']
        );
    }

    public function getPublicUrl(): array
    {
        return [];
    }
}
