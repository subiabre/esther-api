<?php

namespace App\Storage;

use League\Flysystem\FilesystemAdapter;
use League\Flysystem\Local\LocalFilesystemAdapter;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class LocalDriver implements DriverInterface
{
    private string $url;
    private string $urlPath;
    private string $fullPath;

    public function __construct(
        private string $rootDir,
        private UrlGeneratorInterface $urlGenerator
    ) {
        $this->url = $this->urlGenerator->generate('app_index', [], UrlGeneratorInterface::ABSOLUTE_URL);

        $urlPath = sprintf("bundles%slocalstorage", DIRECTORY_SEPARATOR);
        $fullPath = sprintf(
            "%s%spublic%s%s",
            rtrim($this->rootDir, '\/'),
            DIRECTORY_SEPARATOR,
            DIRECTORY_SEPARATOR,
            $urlPath
        );

        if (\file_exists($fullPath) && !\is_dir($fullPath)) {
            throw new \Exception();
        }

        if (!\file_exists($fullPath)) {
            mkdir($fullPath);
        }

        $this->urlPath = $urlPath;
        $this->fullPath = $fullPath;
    }

    public function getAdapter(): FilesystemAdapter
    {
        return new LocalFilesystemAdapter($this->fullPath);
    }

    public function getPublicUrl(): array
    {
        return [
            sprintf(
                "%s%s",
                $this->url,
                $this->urlPath
            )
        ];
    }
}
