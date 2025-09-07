<?php

namespace App;

use Carbon\Carbon;

class UrlCheck
{
    private ?int $id = null;
    private ?int $urlId = null;
    private ?Carbon $createdAt = null;

    public static function fromArray(array $urlData): UrlCheck
    {
        ['url_id' => $urlId] = $urlData;
        $url = new UrlCheck;
        $url->setUrlId($urlId);
        $url->setCreatedAt();
        
        return $url;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUrlId(): ?int
    {
        return $this->urlId;
    }

    public function getCreatedAt(): ?Carbon
    {
        return $this->createdAt;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function setUrlId(int $urlId): void
    {
        $this->urlId = $urlId;
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
