<?php

namespace App\Filter;

use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
use Doctrine\ORM\QueryBuilder;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;

final class PhotoAddressKnownFilter extends AbstractFilter
{
    public const FILTER_NAME = 'known';

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
            !$this->isPropertyEnabled($property, $resourceClass)
        ) {
            return;
        }

        if (!isset($values[self::FILTER_NAME])) {
            return;
        }


        $alias = $queryBuilder->getRootAliases()[0];
        $propertyName = sprintf("%s.components", $property);
        $value = $this->normalizeValue($values[self::FILTER_NAME], $property);

        if ($value === true) {
            $queryBuilder->andWhere(sprintf("%s.%s IS NOT NULL", $alias, $propertyName));
        }

        if ($value === false) {
            $queryBuilder->andWhere(sprintf("%s.%s IS NULL", $alias, $propertyName));
        }
    }

    private function normalizeValue($value, string $property): ?bool
    {
        if (\in_array($value, [true, 'true', '1'], true)) {
            return true;
        }

        if (\in_array($value, [false, 'false', '0'], true)) {
            return false;
        }

        return null;
    }

    public function getDescription(string $resourceClass): array
    {
        $description = [];
        foreach ($this->properties as $property => $strategy) {
            $description += [
                sprintf('%s[%s]', $property, self::FILTER_NAME) => [
                    'property' => $property,
                    'type' => 'bool',
                    'required' => false,
                    'description' => 'Filter collection by known/unknown addresses.',
                    'openapi' => [
                        'allowReserved' => false,
                        'allowEmptyValue' => true,
                        'explode' => false,
                    ],
                ]
            ];
        }

        return $description;
    }
}
