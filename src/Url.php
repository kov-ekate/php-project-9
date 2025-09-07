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
        $createdAt = Carbon::now();
        $url->createdAt = $createdAt;
        
        return $url;
    }

    public static function fromDatabaseRow(array $row): Url
    {
        $name = $row['name'];
        $id = $row['id'];
        $createdAtString = $row['created_at'];
        $createdAt = new Carbon($createdAtString);
        $url = new Url();

        $url->setId($id);
        $url->setName($name);
        $url->setCreatedAt($createdAt);

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

    public function getCreatedAt(): ?Carbon
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

    public function setCreatedAt(Carbon $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    public function exists(): bool
    {
        return !is_null($this->getId());
    }
}
