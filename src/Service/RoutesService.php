<?php

namespace App\Service;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class RoutesService
{
    public function __construct(
        private string $projectDir,
        private UrlGeneratorInterface $urlGenerator
    ) {
    }

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
            $path .= sprintf("%s%s", \DIRECTORY_SEPARATOR, $piece);
        }

        return $path;
    }
}
