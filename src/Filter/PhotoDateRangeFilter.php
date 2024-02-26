<?php

namespace App\Filter;

use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
use Doctrine\ORM\QueryBuilder;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use Doctrine\ORM\Query\Expr\Join;
use Symfony\Component\PropertyInfo\Type;

final class PhotoDateRangeFilter extends AbstractFilter
{
    public const PARAMETER_MIN = 'range:min';
    public const PARAMETER_MAX = 'range:max';

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

        if (isset($values[self::PARAMETER_MIN])) {
            $value = $values[self::PARAMETER_MIN];

            $this->addWhere($property, '>=', $value, $queryBuilder, $queryNameGenerator, $resourceClass);
        }

        if (isset($values[self::PARAMETER_MAX])) {
            $value = $values[self::PARAMETER_MAX];

            $this->addWhere($property, '<=', $value, $queryBuilder, $queryNameGenerator, $resourceClass);
        }
    }

    public function addWhere(
        string $property,
        string $operator,
        string $value,
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass
    ) {
        $alias = $queryBuilder->getRootAliases()[0];
        $parameterName = $queryNameGenerator->generateParameterName($property);

        $queryBuilder
            ->andWhere($queryBuilder->expr()->orX(
                sprintf('%s.date.min %s :%s', $alias, $operator, $parameterName),
                sprintf('%s.date.max %s :%s', $alias, $operator, $parameterName)
            ))
            ->setParameter($parameterName, new \DateTime($value));
    }

    public function getDescription(string $resourceClass): array
    {
        $description = [];
        foreach ($this->properties as $property => $strategy) {
            $description += [
                sprintf('%s[%s]', $property, self::PARAMETER_MIN) => [
                    'property' => $property,
                    'type' => Type::BUILTIN_TYPE_STRING,
                    'required' => false,
                    'description' => 'Only display items after this date',
                    'openapi' => [
                        'allowReserved' => false,
                        'allowEmptyValue' => true,
                        'explode' => false,
                    ],
                ],
                sprintf('%s[%s]', $property, self::PARAMETER_MAX) => [
                    'property' => $property,
                    'type' => Type::BUILTIN_TYPE_STRING,
                    'required' => false,
                    'description' => 'Only display items before this date',
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
