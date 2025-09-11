<?php

namespace App;

use App\Url;
use Carbon\Carbon;

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
            $url = Url::fromDatabaseRow($row);
            $url->setId($row['id']);
            $urls[] = $url;
        }

        return $urls;
    }

    public function getLastChecks(): array
    {
        $lastChecks = [];
        $sql = "SELECT
                    urls.id,
                    urls.name,
                    MAX(url_checks.created_at) AS last_check,
                    (SELECT status_code FROM url_checks WHERE url_id = urls.id ORDER BY created_at DESC LIMIT 1) AS last_status_code
                FROM
                    urls
                LEFT JOIN
                    url_checks ON urls.id = url_checks.url_id
                GROUP BY
                    urls.id, urls.name
                ORDER BY
                    urls.id DESC
            ";
        $stmt = $this->conn->query($sql);

        while ($row = $stmt->fetch()) {
            $lastChecks[$row['id']] = [
                'last_check' => $row['last_check'] ? new Carbon($row['last_check']) : null,
                'last_status_code' => $row['last_status_code'] ? (int)$row['last_status_code'] : null
            ];
        }

        return $lastChecks;
    }

    public function find(int $id): ?Url
    {
        $sql = "SELECT * FROM urls WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$id]);
        if ($row = $stmt->fetch())  {
            $url = Url::fromDatabaseRow($row);
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
}
