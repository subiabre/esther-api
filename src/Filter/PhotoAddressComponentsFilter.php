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
        if (
            !is_array($values) ||
            !$this->isPropertyEnabled($property, $resourceClass)
        ) {
            return;
        }

        if (!isset($values[self::FILTER_NAME])) {
            return;
        }

        $strategy = 'and';
        $values = $values[self::FILTER_NAME];
        if (\is_array($values) && count($values) > 1) {
            $strategy = 'or';
        }

        foreach ($values as $key => $value) {
            $this->addWhere($value, $property, $strategy, $queryBuilder, $queryNameGenerator);
        }
    }

    public function addWhere(
        string $value,
        string $property,
        string $strategy,
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
    ) {
        $values = explode(';', $value);
        foreach ($values as $value) {
            if (empty($value)) continue;

            $alias = $queryBuilder->getRootAliases()[0];
            $components = array_map('trim', explode(':', $value));
            $propertyName = sprintf("%s.components", $property);
            $parameterName = $queryNameGenerator->generateParameterName($propertyName);

            $where = sprintf(
                "JSON_CONTAINS(%s.%s, :%s, '$.%s') = 1",
                $alias,
                $propertyName,
                $parameterName,
                $components[0]
            );

            switch ($strategy) {
                case 'or':
                    $queryBuilder
                        ->orWhere($where)
                        ->setParameter($parameterName, json_encode($components[1]));
                    break;
                case 'and':
                    $queryBuilder
                        ->andWhere($where)
                        ->setParameter($parameterName, json_encode($components[1]));
                    break;
            }
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
                    'is_collection' => true,
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
