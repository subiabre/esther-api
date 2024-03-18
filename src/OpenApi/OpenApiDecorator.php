<?php

namespace App\OpenApi;

use ApiPlatform\OpenApi\Factory\OpenApiFactoryInterface;
use ApiPlatform\OpenApi\Model;
use ApiPlatform\OpenApi\Model\SecurityScheme;
use ApiPlatform\OpenApi\OpenApi;
use Symfony\Component\DependencyInjection\Attribute\AsDecorator;

#[AsDecorator('api_platform.openapi.factory')]
class OpenApiDecorator implements OpenApiFactoryInterface
{

    public function __construct(private OpenApiFactoryInterface $decorated)
    {
    }

    public function __invoke(array $context = []): OpenApi
    {
        $openApi = $this->decorated->__invoke($context);

        $openApi = $openApi->withServers([
            new Model\Server('/'),
            new Model\Server('{{api}}/v1')
        ]);

        $securitySchemes = $openApi->getComponents()->getSecuritySchemes() ?: new \ArrayObject();
        $securitySchemes['access_token'] = new SecurityScheme(
            type: 'http',
            scheme: 'bearer',
        );

        return $openApi;
    }
}
