<?php

namespace App\Filter;

use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
use Doctrine\ORM\QueryBuilder;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;

final class PhotoAddressComponentsFilter extends AbstractFilter
{
    public const FILTER_NAME = 'components';

    protected function filterProperty(
        string $property,
        $values,
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        ?Operation $operation = null,
        array $context = []
    ): void {
        // otherwise filter is applied to order and page as well
        if (
            !is_array($values) ||
            !$this->isPropertyEnabled($property, $resourceClass)
        ) {
            return;
        }

        if (!isset($values[self::FILTER_NAME])) {
            return;
        }

        $values = explode(';', $values[self::FILTER_NAME]);
        foreach ($values as $value) {
            if (empty($value)) continue;

            $alias = $queryBuilder->getRootAliases()[0];
            $components = array_map('trim', explode(':', $value));
            $propertyName = sprintf("%s.components", $property);
            $parameterName = $queryNameGenerator->generateParameterName($propertyName);

            $queryBuilder
                ->andWhere(
                    sprintf(
                        "JSON_CONTAINS(%s.%s, :%s, '$.%s') = 1",
                        $alias,
                        $propertyName,
                        $parameterName,
                        $components[0]
                    )
                )
                ->setParameter($parameterName, sprintf('"%s"', $components[1]));
        }
    }

    public function getDescription(string $resourceClass): array
    {
        $description = [];
        foreach ($this->properties as $property => $strategy) {
            $description += [
                sprintf('%s[%s]', $property, self::FILTER_NAME) => [
                    'property' => $property,
                    'type' => 'array',
                    'required' => false,
                    'description' => 'Filter collection by address components key-value pairs.',
                    'openapi' => [
                        'allowReserved' => false,
                        'allowEmptyValue' => true,
                        'explode' => false,
                        'example' => 'foo: bar; country: Argentina;',
                    ],
                ]
            ];
        }

        return $description;
    }
}
