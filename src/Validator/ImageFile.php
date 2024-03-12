<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
class ImageFile extends Constraint
{
    public string $message = 'The resource at "{{ string }}" is not a valid image: it can only be a JPEG, GIF or PNG file.';

    public function __construct(string $message = null, array $groups = null, $payload = null)
    {
        parent::__construct([], $groups, $payload);

        $this->message = $message ?? $this->message;
    }
}
