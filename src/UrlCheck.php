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
        $createdAt = Carbon::now();
        $url->createdAt = $createdAt;
        
        return $url;
    }

    public static function fromDatabaseRow(array $row): UrlCheck
    {
        $urlId = $row['url_id'];
        $id = $row['id'];
        $createdAtString = $row['created_at'];
        $createdAt = new Carbon($createdAtString);
        $urlCheck = new UrlCheck();

        $urlCheck->setId($id);
        $urlCheck->setUrlId($urlId);
        $urlCheck->setCreatedAt($createdAt);

        return $urlCheck;
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

    public function setCreatedAt(Carbon $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    public function exists(): bool
    {
        return !is_null($this->getId());
    }
}
