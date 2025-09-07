<?php

namespace App;

use App\Url;

class UrlRepository
{
    private \PDO $conn;

    public function __construct(\PDO $conn)
    {
        $this->conn = $conn;
    }

     public function urlExists(string $url): bool
    {
        $sql = "SELECT COUNT(*) FROM urls WHERE name = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$url]);
        $count = (int) $stmt->fetchColumn();
        return $count > 0;
    }

    public function getEntities(): array
    {
        $urls = [];
        $sql = "SELECT * FROM urls ORDER BY id DESC";
        $stmt = $this->conn->query($sql);

        while ($row = $stmt->fetch()) {
            $url = Url::fromArray($row);
            $url->setId($row['id']);
            $urls[] = $url;
        }

        return $urls;
    }

    public function find(int $id): ?Url
    {
        $sql = "SELECT * FROM urls WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$id]);
        if ($row = $stmt->fetch())  {
            $url = Url::fromArray($row);
            $url->setId($row['id']);
            return $url;
        }
        return null;
    }

    public function save(Url $url): bool
    {
        if ($this->urlExists($url->getName())) {
            return false;
        }

        $sql = "INSERT INTO urls (name, created_at) VALUES (:name, :created_at)";
        $stmt = $this->conn->prepare($sql);
        $name = $url->getName();
        $createdAt = $url->getCreatedAt();
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':created_at', $createdAt);
        $stmt->execute();
        $id = (int) $this->conn->lastInsertId();
        $url->setId($id);
        
        return true;
    }

    public function delete(int $id): void
    {
        $sql = "DELETE FROM urls WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$id]);

        // Получаем имя последовательности
        $sqlGetSequence = "SELECT pg_get_serial_sequence('urls', 'id');";
        $stmtGetSequence = $this->conn->prepare($sqlGetSequence);
        $stmtGetSequence->execute();
        $sequenceName = $stmtGetSequence->fetchColumn();

        // Сброс последовательности
        if ($sequenceName) {
            $sqlReset = "ALTER SEQUENCE {$sequenceName} RESTART WITH 1;";
            $stmtReset = $this->conn->prepare($sqlReset);
            $stmtReset->execute();
        }
    }
}