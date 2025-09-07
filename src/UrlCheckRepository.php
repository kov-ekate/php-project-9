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

    public function getEntities(): array
    {
        $urls = [];
        $sql = "SELECT * FROM url_checks ORDER BY id DESC";
        $stmt = $this->conn->query($sql);

        while ($row = $stmt->fetch()) {
            $url = UrlCheck::fromDatabaseRow($row);
            $url->setId($row['id']);
            $urls[] = $url;
        }

        return $urls;
    }

     public function urlExists(string $urlId): bool
    {
        $sql = "SELECT COUNT(*) FROM url_checks WHERE url_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$urlId]);
        $count = (int) $stmt->fetchColumn();
        return $count > 0;
    }

    public function find(int $id): ?array
    {
        $sql = "SELECT * FROM url_checks WHERE url_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$id]);
        $urlChecks = [];
        while ($row = $stmt->fetch()) {
            $urlChecks[] = UrlCheck::fromDatabaseRow($row);
        }

        return $urlChecks;
    }

    public function save(UrlCheck $urlCheck): bool
    {
        $sql = "INSERT INTO url_checks (url_id, created_at) VALUES (:url_id, :created_at)";
        $stmt = $this->conn->prepare($sql);
        $urlId = $urlCheck->getUrlId();
        $createdAt = $urlCheck->getCreatedAt();
        $stmt->bindParam(':url_id', $urlId);
        $stmt->bindParam(':created_at', $createdAt);
        $result = $stmt->execute();
        $id = (int) $this->conn->lastInsertId();
        $urlCheck->setId($id);
        
        return $result;
    }
}
