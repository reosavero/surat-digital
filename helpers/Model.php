<?php

/**
 * Base Model - Surat Digital
 */

class Model
{
    protected string $table;
    protected string $primaryKey = 'id';

    public function find(int $id): array|false
    {
        return Database::fetch("SELECT * FROM {$this->table} WHERE {$this->primaryKey} = ?", [$id]);
    }

    public function all(): array
    {
        return Database::fetchAll("SELECT * FROM {$this->table} ORDER BY {$this->primaryKey} DESC");
    }

    public function create(array $data): string
    {
        $keys = array_keys($data);
        $fields = implode(', ', $keys);
        $placeholders = implode(', ', array_fill(0, count($keys), '?'));
        
        $sql = "INSERT INTO {$this->table} ({$fields}) VALUES ({$placeholders})";
        Database::query($sql, array_values($data));
        return Database::lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $sets = [];
        foreach (array_keys($data) as $key) {
            $sets[] = "{$key} = ?";
        }
        $setString = implode(', ', $sets);
        
        $sql = "UPDATE {$this->table} SET {$setString} WHERE {$this->primaryKey} = ?";
        $params = array_values($data);
        $params[] = $id;
        
        return Database::execute($sql, $params);
    }

    public function delete(int $id): bool
    {
        return Database::execute("DELETE FROM {$this->table} WHERE {$this->primaryKey} = ?", [$id]);
    }
}
