<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class ImageFileValidator extends ConstraintValidator
{
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

        if (self::isImage($value)) {
            return;
        }

        $this->context->buildViolation($constraint->message)
            ->setParameter('{{ string }}', $value)
            ->addViolation();
    }

    public static function isImage(string $file): bool
    {
        try {
            /**
             * @disregard Imagick is loaded in the docker container
             * @see docker/php/Dockerfile
             */
            $image = new \Imagick($file);

            return $image->valid();
        } catch (\Exception $e) {
            return false;
        }
    }
}
