<?php

namespace App;

use Carbon\Carbon;

class UrlCheck
{
    private ?int $id = null;
    private ?int $urlId = null;
    private ?int $statusCode = null;
    private ?string $h1 = null;
    private ?string $title = null;
    private ?string $description = null;
    private ?Carbon $createdAt = null;

    public static function fromArray(array $urlData): UrlCheck
    {
        [
            'url_id' => $urlId,
            'status_code' => $statusCode,
            'h1' => $h1,
            'title' => $title,
            'description' => $description
        ] = $urlData;
        $url = new UrlCheck();
        $url->setUrlId($urlId);
        $url->setStatusCode($statusCode);
        $url->setH1($h1);
        $url->setTitle($title);
        $url->setDescription($description);
        $createdAt = Carbon::now();
        $url->createdAt = $createdAt;

        return $url;
    }

    public static function fromDatabaseRow(array $row): UrlCheck
    {
        $urlId = $row['url_id'];
        $id = $row['id'];
        $statusCode = $row['status_code'];
        $h1 = $row['h1'];
        $title = $row['title'];
        $description = $row['description'];
        $createdAtString = $row['created_at'];
        $createdAt = new Carbon($createdAtString);
        $urlCheck = new UrlCheck();

        $urlCheck->setId($id);
        $urlCheck->setUrlId($urlId);
        $urlCheck->setStatusCode($statusCode);
        $urlCheck->setH1($h1);
        $urlCheck->setTitle($title);
        $urlCheck->setDescription($description);
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

    public function getStatusCode(): ?int
    {
        return $this->statusCode;
    }

    public function getH1(): ?string
    {
        return $this->h1;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function getDescription(): ?string
    {
        return $this->description;
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

    public function setStatusCode(int $statusCode): void
    {
        $this->statusCode = $statusCode;
    }

    public function setH1(string $h1): void
    {
        $this->h1 = $h1;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
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
