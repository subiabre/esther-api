<?php

namespace App\Doctrine;

use ApiPlatform\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use Doctrine\ORM\QueryBuilder;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use App\Entity\Photo;
use Symfony\Bundle\SecurityBundle\Security;

class PhotoRolesExtension implements QueryCollectionExtensionInterface
{
    public function __construct(
        private Security $security
    ) {}

    public function applyToCollection(
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        ?Operation $operation = null,
        array $context = []
    ): void {
        if (
            !\in_array($resourceClass, [Photo::class]) ||
            $this->security->isGranted('ROLE_ADMIN') ||
            null === $user = $this->security->getUser()
        ) {
            return;
        }

        $rootAlias = $queryBuilder->getRootAliases()[0];
        $photoAlias = $rootAlias;

        foreach ($user->getRoles() as $key => $role) {
            $queryBuilder->orWhere(\sprintf('JSON_CONTAINS(%s.roles, :user_role%d) = 1', $photoAlias, $key));
            $queryBuilder->setParameter(\sprintf('user_role%d', $key), \json_encode($role));
        }
    }
}
