<?php

namespace App\Service;

use App\Storage\LocalDriver;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class RoutesService
{
    public const PUBLIC_DIR = 'public';

    public function __construct(
        private string $projectDir,
        private UrlGeneratorInterface $urlGenerator,
    ) {}

    public function getAbsoluteUrl(string $name, array $parameters = []): string
    {
        return $this->urlGenerator->generate(
            $name,
            $parameters,
            UrlGeneratorInterface::ABSOLUTE_URL
        );
    }

    public function buildAbsolutePath(...$pieces): string
    {
        $path = \rtrim($this->projectDir, '\/');

        foreach ($pieces as $piece) {
            $path .= sprintf("%s%s", \DIRECTORY_SEPARATOR, \ltrim($piece, '\/'));
        }

        return $path;
    }

    public function getLocalStorageUrl(): string
    {
        return \sprintf('%s%s', $this->getAbsoluteUrl('app_index'), LocalDriver::STORAGE_DIR);
    }

    public function getLocalStoragePath(): string
    {
        return $this->buildAbsolutePath(self::PUBLIC_DIR, LocalDriver::STORAGE_DIR);
    }

    public function getLocalUrlAsPath(string $url): string
    {
        $local = $this->getLocalStorageUrl();

        if (\preg_match(\sprintf('/^%s/', \preg_quote($local, '/')), $url)) {
            return $this->buildAbsolutePath(
                self::PUBLIC_DIR,
                LocalDriver::STORAGE_DIR,
                \str_replace($local, '', \urldecode($url))
            );
        }

        return \urldecode($url);
    }
}
