<?php

namespace App\Validator;

use App\Entity\Image;
use App\Service\RoutesService;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class ImageFileValidator extends ConstraintValidator
{
    public function __construct(
        private RoutesService $routesService
    ) {}

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof ImageFile) {
            throw new UnexpectedTypeException($constraint, ImageFile::class);
        }

        if (null === $value || '' === $value) {
            return;
        }

        if (!is_string($value)) {
            throw new UnexpectedValueException($value, 'string');
        }

        $path = $this->routesService->getLocalUrlAsPath($value);
        $path = Image::encodeSrc($path);

        if (self::isImage($path)) {
            return;
        }

        $this->context->buildViolation($constraint->message)
            ->setParameter('{{ string }}', $value)
            ->addViolation();
    }

    public static function isImage(string $path): bool
    {
        try {
            /**
             * @disregard Imagick is loaded in the docker container
             * @see docker/php/Dockerfile
             */
            $image = new \Imagick($path);

            return $image->valid();
        } catch (\Exception $e) {
            return false;
        }
    }
}
