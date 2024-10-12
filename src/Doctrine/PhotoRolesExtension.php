<?php

namespace App\Doctrine;

use ApiPlatform\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use Doctrine\ORM\QueryBuilder;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use App\Entity\Image;
use App\Entity\Person;
use App\Entity\Photo;
use App\Entity\Portrait;
use Doctrine\ORM\Query\Expr\Join;
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
            !\in_array($resourceClass, [Photo::class, Person::class]) ||
            $this->security->isGranted('ROLE_ADMIN') ||
            null === $user = $this->security->getUser()
        ) {
            return;
        }

        $rootAlias = $queryBuilder->getRootAliases()[0];

        if ($resourceClass === Photo::class) {
            $photoAlias = $rootAlias;
        }

        if ($resourceClass === Person::class) {
            $personAlias = $rootAlias;

            $portraitAlias = \sprintf('%s_portrait', $personAlias);
            $queryBuilder->innerJoin(
                Portrait::class,
                $portraitAlias,
                Join::WITH,
                \sprintf('%s.person = %s.id', $portraitAlias, $personAlias)
            );

            $imageAlias = \sprintf('%s_image', $portraitAlias);
            $queryBuilder->innerJoin(
                Image::class,
                $imageAlias,
                Join::WITH,
                \sprintf('%s.id = %s.image', $imageAlias, $portraitAlias)
            );

            $photoAlias = \sprintf('%s_photo', $imageAlias);
            $queryBuilder->innerJoin(
                Photo::class,
                $photoAlias,
                Join::WITH,
                \sprintf('%s.id = %s.photo', $photoAlias, $imageAlias)
            );
        }

        foreach ($user->getRoles() as $key => $role) {
            $queryBuilder->orWhere(\sprintf('JSON_CONTAINS(%s.roles, :user_role%d) = 1', $photoAlias, $key));
            $queryBuilder->setParameter(\sprintf('user_role%d', $key), \json_encode($role));
        }
    }
}
