<?php

namespace App\Doctrine;

use ApiPlatform\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use Doctrine\ORM\QueryBuilder;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use App\Entity\Image;
use App\Entity\Person;
use App\Entity\Photo;
use App\Entity\PhotoScope;
use App\Entity\Portrait;
use Doctrine\ORM\Query\Expr\Join;
use Symfony\Bundle\SecurityBundle\Security;

class PhotoScopesExtension implements QueryCollectionExtensionInterface
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
            !\in_array($resourceClass, [Photo::class, Portrait::class, Person::class,]) ||
            // $this->security->isGranted('ROLE_ADMIN') ||
            null === $user = $this->security->getUser()
        ) {
            return;
        }

        $rootAlias = $queryBuilder->getRootAliases()[0];
        $photoAlias = $rootAlias;

        if ($resourceClass === Portrait::class) {
            $imageAlias = \sprintf('%s_image', $rootAlias);
            $queryBuilder->innerJoin(
                Image::class,
                $imageAlias,
                Join::WITH,
                \sprintf('%s.id = %s.image', $imageAlias, $rootAlias)
            );

            $photoAlias = \sprintf('%s_photo', $imageAlias);
            $queryBuilder->innerJoin(
                Photo::class,
                $photoAlias,
                Join::WITH,
                \sprintf('%s.id = %s.photo', $photoAlias, $imageAlias)
            );
        }

        if ($resourceClass === Person::class) {
            $portraitAlias = \sprintf('%s_portrait', $rootAlias);
            $queryBuilder->innerJoin(
                Portrait::class,
                $portraitAlias,
                Join::WITH,
                \sprintf('%s.person = %s.id', $portraitAlias, $rootAlias)
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

        $scopeAlias = \sprintf('%s_scope', $photoAlias);
        $queryBuilder->innerJoin(
            PhotoScope::class,
            $scopeAlias,
            Join::WITH,
            \sprintf('%s.photo = %s.id', $scopeAlias, $photoAlias)
        );

        $queryBuilder->andWhere(\sprintf('%s.role IN (:user_roles)', $scopeAlias));
        $queryBuilder->setParameter('user_roles', $user->getRoles());
    }
}
