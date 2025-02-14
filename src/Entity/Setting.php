<?php

namespace App\Entity;

use App\Domain\SettingType;
use App\Repository\SettingRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SettingRepository::class)]
class Setting
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100, unique: true)]
    private string $code;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $value = null;

    #[ORM\Column(length: 15, enumType: SettingType::class)]
    private ?SettingType $type = null;

    public function __construct(string $code, mixed $value = null)
    {
        $this->code = $code;
        $this->setValue($value);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function setCode(string $code): static
    {
        $this->code = $code;

        return $this;
    }

    public function getValue(): mixed
    {
        return $this->type->castDbValue($this->value);
    }

    public function setValue(mixed $value): static
    {
        $this->value = (string) (is_bool($value) ? ($value ? 1 : 0) : $value);
        $this->type = SettingType::fromPhpValue($value);

        return $this;
    }
}
