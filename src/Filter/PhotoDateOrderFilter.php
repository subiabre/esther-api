<?php

namespace App\Filter;

use ApiPlatform\Doctrine\Common\Filter\OrderFilterInterface;
use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
use Doctrine\ORM\QueryBuilder;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;

final class PhotoDateOrderFilter extends AbstractFilter
{
    public const FILTER_NAME = 'order';

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

        $alias = $queryBuilder->getRootAliases()[0];
        $direction = strtoupper($values[self::FILTER_NAME]);

        switch ($direction) {
            case OrderFilterInterface::DIRECTION_ASC:
                $queryBuilder->addOrderBy(sprintf('%s.date.min', $alias), $direction);
                break;
            case OrderFilterInterface::DIRECTION_DESC:
                $queryBuilder->addOrderBy(sprintf('%s.date.max', $alias), $direction);
                break;
        }

        $queryBuilder->addOrderBy(sprintf('%s.id', $alias), $direction);
        $queryBuilder->addOrderBy(sprintf('%s.reel', $alias), $direction);
    }

    public function getDescription(string $resourceClass): array
    {
        $description = [];
        foreach ($this->properties as $property => $strategy) {
            $description += [
                sprintf('%s[%s]', $property, self::FILTER_NAME) => [
                    'property' => $property,
                    'type' => 'string',
                    'required' => false,
                    'schema' => [
                        'type' => 'string',
                        'enum' => [
                            strtolower(OrderFilterInterface::DIRECTION_ASC),
                            strtolower(OrderFilterInterface::DIRECTION_DESC),
                        ],
                    ],
                ]
            ];
        }

        return $description;
    }
}
