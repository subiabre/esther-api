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

        $address = [];
        foreach ($values as $value) {
            if (empty($value)) continue;

            $components = array_map('trim', explode(':', $value));
            $address[$components[0]] = $components[1];
        }

        $alias = $queryBuilder->getRootAliases()[0];
        $propertyName = sprintf("%s.components", $property);
        $parameterName = $queryNameGenerator->generateParameterName($propertyName);

        $where = sprintf(
            "JSON_CONTAINS(%s.%s, :%s) = 1",
            $alias,
            $propertyName,
            $parameterName,
        );

        switch ($strategy) {
            case 'or':
                $queryBuilder
                    ->orWhere($where)
                    ->setParameter($parameterName, json_encode($address));
                break;
            case 'and':
                $queryBuilder
                    ->andWhere($where)
                    ->setParameter($parameterName, json_encode($address));
                break;
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
