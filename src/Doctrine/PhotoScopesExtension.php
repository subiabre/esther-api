<?php

namespace App\Doctrine;

use ApiPlatform\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use Doctrine\ORM\QueryBuilder;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use App\Entity\Photo;
use App\Entity\PhotoScope;
use Doctrine\ORM\Query\Expr\Join;
use Symfony\Bundle\SecurityBundle\Security;

class PhotoScopesExtension implements QueryCollectionExtensionInterface
{
    public function __construct(
        private Security $security
    ) {
    }

    public function applyToCollection(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, ?Operation $operation = null, array $context = []): void
    {
        if (
            $resourceClass !== Photo::class ||
            $this->security->isGranted('ROLE_ADMIN') ||
            null === $user = $this->security->getUser()
        ) {
            return;
        }

        $rootAlias = $queryBuilder->getRootAliases()[0];

        $scopeAlias = sprintf('%s_scope', $rootAlias);
        $queryBuilder->innerJoin(PhotoScope::class, $scopeAlias, Join::WITH, sprintf('%s.photo = %s.id', $scopeAlias, $rootAlias));

        $queryBuilder->andWhere(sprintf('%s.role IN (:user_roles)', $scopeAlias));
        $queryBuilder->setParameter('user_roles', $user->getRoles());
    }
}
