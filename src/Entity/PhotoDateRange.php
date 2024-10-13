<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Embeddable]
#[Gedmo\Loggable()]
class PhotoDateRange
{
    #[Assert\NotBlank()]
    #[Assert\Type('datetime')]
    #[Assert\LessThanOrEqual('now')]
    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Gedmo\Versioned()]
    private ?\DateTimeInterface $min = null;

    #[Assert\Type('datetime')]
    #[Assert\LessThanOrEqual('now')]
    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Gedmo\Versioned()]
    private ?\DateTimeInterface $max = null;

    public function __construct(
        ?\DateTimeInterface $min = null,
        ?\DateTimeInterface $max = null
    )
    {
        $this->min = $min;
        $this->max = $max ?? new \DateTime();
    }

    public function getMin(): ?\DateTimeInterface
    {
        return $this->min;
    }

    public function setMin(\DateTimeInterface $min): static
    {
        $this->min = $min;

        return $this;
    }

    public function getMax(): ?\DateTimeInterface
    {
        return $this->max;
    }

    public function setMax(\DateTimeInterface $max): static
    {
        $this->max = $max;

        return $this;
    }
}
