<?php

namespace App;

use Carbon\Carbon;

class Url 
{
    private ?int $id = null;
    private ?string $name = null;
    private ?Carbon $createdAt = null;

    public static function fromArray(array $urlData): Url
    {
        ['name' => $name] = $urlData;
        $url = new Url;
        $url->setName($name);
        $url->setCreatedAt();
        
        return $url;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getCreatedAt(): ?string
    {
        return $this->createdAt;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function setCreatedAt(): void
    {
        $createdAt = Carbon::now();
        $this->createdAt = $createdAt;
    }

    public function exists(): bool
    {
        return !is_null($this->getId());
    }
}
