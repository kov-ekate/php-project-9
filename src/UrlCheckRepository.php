<?php

namespace App;

use App\UrlCheck;

class UrlCheckRepository
{
    private \PDO $conn;

    public function __construct(\PDO $conn)
    {
        $this->conn = $conn;
    }

     public function urlExists(string $urlId): bool
    {
        $sql = "SELECT COUNT(*) FROM url_checks WHERE url_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$urlId]);
        $count = (int) $stmt->fetchColumn();
        return $count > 0;
    }

    public function find(int $id): ?UrlCheck
    {
        $sql = "SELECT * FROM url_checks WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$id]);
        if ($row = $stmt->fetch())  {
            $url = UrlCheck::fromArray($row);
            $url->setId($row['id']);
            return $url;
        }
        return null;
    }

    public function save(UrlCheck $urlCheck): bool
    {
        $sql = "INSERT INTO url_checks (url_id, created_at)
                VALUES (:url_id, :created_at)";
        $stmt = $this->conn->prepare($sql);

        $urlId = $urlCheck->getUrlId();
        $createdAt = $urlCheck->getCreatedAt();
        $stmt->bindValue(':url_id', $urlId);
        $stmt->bindValue(':created_at', $createdAt);

        $result = $stmt->execute();

        if ($result) {
            $urlCheck->setId($this->conn->lastInsertId());
        }

        return $result;
    }

    public function findByUrlId(int $urlId): array
    {
        $sql = "SELECT * FROM url_checks WHERE url_id = :url_id ORDER BY created_at DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':url_id', $urlId);
        $stmt->execute();

        $urlChecks = [];
        while ($row = $stmt->fetch()) {
            $urlChecks[] = UrlCheck::fromArray($row);
        }

        return $urlChecks;
    }
}