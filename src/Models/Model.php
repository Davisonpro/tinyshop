<?php

declare(strict_types=1);

namespace TinyShop\Models;

use PDO;
use TinyShop\Enums\FieldType;
use TinyShop\Exceptions\ValidationException;

abstract class Model implements \ArrayAccess, \JsonSerializable
{
    private static ?PDO $pdo = null;

    /** @var array<string, mixed> */
    protected array $data = [];

    /** @var array<string, mixed> */
    private array $original = [];

    private bool $isNew = true;

    /**
     * Definition array — must be overridden by each concrete model.
     *
     * Structure:
     *   'table'   => string,
     *   'primary' => string (default 'id'),
     *   'fields'  => [
     *       'column_name' => [
     *           'type'      => FieldType,
     *           'required'  => bool,       // enforced on INSERT (default false)
     *           'maxLength' => int,        // for String/Text
     *           'values'    => string[],   // for Enum
     *           'default'   => mixed,      // applied on INSERT when null
     *       ],
     *   ],
     *
     * @var array{table: string, primary: string, fields: array<string, array>}
     */
    protected static array $definition = [
        'table'   => '',
        'primary' => 'id',
        'fields'  => [],
    ];

    // ── Bootstrap ──────────────────────────────────────────────

    public static function boot(PDO $pdo): void
    {
        self::$pdo = $pdo;
    }

    protected static function db(): PDO
    {
        if (self::$pdo === null) {
            throw new \RuntimeException('Model::boot() has not been called.');
        }
        return self::$pdo;
    }

    // ── Magic accessors ────────────────────────────────────────

    public function __get(string $name): mixed
    {
        return $this->data[$name] ?? null;
    }

    public function __set(string $name, mixed $value): void
    {
        $this->data[$name] = $value;
    }

    public function __isset(string $name): bool
    {
        return array_key_exists($name, $this->data);
    }

    // ── Hydration ──────────────────────────────────────────────

    /**
     * Create a model instance from a database row.
     */
    protected static function hydrate(array $row): static
    {
        $instance = new static();
        $instance->data = $instance->castOnHydrate($row);
        $instance->original = $instance->data;
        $instance->isNew = false;
        return $instance;
    }

    /**
     * Hydrate an array of rows into model instances.
     *
     * @param array[] $rows
     * @return static[]
     */
    protected static function hydrateAll(array $rows): array
    {
        return array_map(static fn(array $row) => static::hydrate($row), $rows);
    }

    // ── Static finders ─────────────────────────────────────────

    /**
     * Find a record by primary key.
     */
    public static function find(int $id): ?static
    {
        $table = static::$definition['table'];
        $pk = static::$definition['primary'];
        $stmt = static::db()->prepare("SELECT * FROM `{$table}` WHERE `{$pk}` = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? static::hydrate($row) : null;
    }

    /**
     * Find first record matching a column value.
     */
    public static function findBy(string $column, mixed $value): ?static
    {
        $table = static::$definition['table'];
        $stmt = static::db()->prepare("SELECT * FROM `{$table}` WHERE `{$column}` = ? LIMIT 1");
        $stmt->execute([$value]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? static::hydrate($row) : null;
    }

    /**
     * Find all records matching a column value.
     *
     * @return static[]
     */
    public static function where(
        string $column,
        mixed $value,
        ?int $limit = null,
        int $offset = 0,
        string $orderBy = ''
    ): array {
        $table = static::$definition['table'];
        $sql = "SELECT * FROM `{$table}` WHERE `{$column}` = ?";

        if ($orderBy !== '') {
            $sql .= " ORDER BY {$orderBy}";
        }
        if ($limit !== null) {
            $sql .= " LIMIT {$limit} OFFSET {$offset}";
        }

        $stmt = static::db()->prepare($sql);
        $stmt->execute([$value]);
        return static::hydrateAll($stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    /**
     * Get all records.
     *
     * @return static[]
     */
    public static function all(?int $limit = null, int $offset = 0, string $orderBy = ''): array
    {
        $table = static::$definition['table'];
        $sql = "SELECT * FROM `{$table}`";

        if ($orderBy !== '') {
            $sql .= " ORDER BY {$orderBy}";
        }
        if ($limit !== null) {
            $sql .= " LIMIT {$limit} OFFSET {$offset}";
        }

        $stmt = static::db()->query($sql);
        return static::hydrateAll($stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    /**
     * Count records, optionally filtered by a column.
     */
    public static function count(?string $column = null, mixed $value = null): int
    {
        $table = static::$definition['table'];

        if ($column !== null) {
            $stmt = static::db()->prepare("SELECT COUNT(*) FROM `{$table}` WHERE `{$column}` = ?");
            $stmt->execute([$value]);
        } else {
            $stmt = static::db()->query("SELECT COUNT(*) FROM `{$table}`");
        }

        return (int) $stmt->fetchColumn();
    }

    /**
     * Check if a record exists with the given column value, optionally excluding an ID.
     */
    public static function exists(string $column, mixed $value, ?int $excludeId = null): bool
    {
        $table = static::$definition['table'];
        $pk = static::$definition['primary'];

        if ($excludeId !== null) {
            $stmt = static::db()->prepare(
                "SELECT 1 FROM `{$table}` WHERE `{$column}` = ? AND `{$pk}` != ? LIMIT 1"
            );
            $stmt->execute([$value, $excludeId]);
        } else {
            $stmt = static::db()->prepare(
                "SELECT 1 FROM `{$table}` WHERE `{$column}` = ? LIMIT 1"
            );
            $stmt->execute([$value]);
        }

        return $stmt->fetchColumn() !== false;
    }

    // ── Multi-condition finders ────────────────────────────────

    /**
     * Find first record matching multiple column conditions.
     *
     * @param array<string, mixed> $conditions  column => value pairs (null becomes IS NULL)
     */
    public static function findWhere(array $conditions): ?static
    {
        $table = static::$definition['table'];
        $clauses = [];
        $values = [];

        foreach ($conditions as $col => $val) {
            if ($val === null) {
                $clauses[] = "`{$col}` IS NULL";
            } else {
                $clauses[] = "`{$col}` = ?";
                $values[] = $val;
            }
        }

        $sql = "SELECT * FROM `{$table}` WHERE " . implode(' AND ', $clauses) . " LIMIT 1";
        $stmt = static::db()->prepare($sql);
        $stmt->execute($values);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? static::hydrate($row) : null;
    }

    /**
     * Find all records matching multiple column conditions.
     *
     * @param array<string, mixed> $conditions  column => value pairs
     * @return static[]
     */
    public static function whereAll(
        array $conditions,
        ?int $limit = null,
        int $offset = 0,
        string $orderBy = ''
    ): array {
        $table = static::$definition['table'];
        $clauses = [];
        $values = [];

        foreach ($conditions as $col => $val) {
            if ($val === null) {
                $clauses[] = "`{$col}` IS NULL";
            } else {
                $clauses[] = "`{$col}` = ?";
                $values[] = $val;
            }
        }

        $sql = "SELECT * FROM `{$table}` WHERE " . implode(' AND ', $clauses);

        if ($orderBy !== '') {
            $sql .= " ORDER BY {$orderBy}";
        }
        if ($limit !== null) {
            $sql .= " LIMIT {$limit} OFFSET {$offset}";
        }

        $stmt = static::db()->prepare($sql);
        $stmt->execute($values);
        return static::hydrateAll($stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    // ── Raw queries (escape hatch for JOINs, aggregates) ──────

    /**
     * Execute a raw SELECT query and return rows as associative arrays.
     *
     * @return array[]
     */
    public static function rawQuery(string $sql, array $params = []): array
    {
        $stmt = static::db()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Execute a raw query and return a single scalar value.
     */
    public static function rawScalar(string $sql, array $params = []): mixed
    {
        $stmt = static::db()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn();
    }

    /**
     * Execute a raw statement (INSERT/UPDATE/DELETE) and return affected row count.
     */
    public static function rawExecute(string $sql, array $params = []): int
    {
        $stmt = static::db()->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }

    // ── Instance CRUD ──────────────────────────────────────────

    /**
     * Save the model — INSERT if new, UPDATE dirty fields if existing.
     *
     * @throws ValidationException
     */
    public function save(): bool
    {
        $errors = $this->validate();
        if (!empty($errors)) {
            throw new ValidationException($errors);
        }

        $this->beforeSave();

        $result = $this->isNew ? $this->performInsert() : $this->performUpdate();

        $this->afterSave();

        return $result;
    }

    /**
     * Delete a record from the database.
     *
     * Called with an ID:   $model->delete(5)   — finds and deletes by primary key
     * Called on instance:  $model->delete()     — deletes current record
     */
    public function delete(?int $id = null): bool
    {
        $table = static::$definition['table'];
        $pk = static::$definition['primary'];

        $pkVal = $id ?? ($this->data[$pk] ?? null);

        if ($pkVal === null) {
            return false;
        }

        $this->beforeDelete();

        $stmt = static::db()->prepare("DELETE FROM `{$table}` WHERE `{$pk}` = ?");
        $stmt->execute([$pkVal]);
        $deleted = $stmt->rowCount() > 0;

        if ($deleted) {
            $this->afterDelete();
        }

        return $deleted;
    }

    /**
     * Reload data from the database.
     */
    public function refresh(): static
    {
        $pk = static::$definition['primary'];
        $id = $this->data[$pk] ?? null;

        if ($id !== null) {
            $fresh = static::find((int) $id);
            if ($fresh !== null) {
                $this->data = $fresh->data;
                $this->original = $fresh->original;
                $this->isNew = false;
            }
        }

        return $this;
    }

    // ── Mass assignment ────────────────────────────────────────

    /**
     * Fill model data from an associative array.
     * Only keys that exist in $definition['fields'] or match the primary key are accepted.
     */
    public function fill(array $data): static
    {
        $fields = static::$definition['fields'];
        $pk = static::$definition['primary'];

        foreach ($data as $key => $value) {
            if ($key === $pk || array_key_exists($key, $fields)) {
                $this->data[$key] = $value;
            }
        }

        return $this;
    }

    // ── Dirty tracking ─────────────────────────────────────────

    /**
     * Check if a specific field (or any field) has been modified since load.
     */
    public function isDirty(?string $field = null): bool
    {
        if ($field !== null) {
            return ($this->data[$field] ?? null) !== ($this->original[$field] ?? null);
        }

        return !empty($this->getDirty());
    }

    /**
     * Get all fields that have changed since load.
     *
     * @return array<string, mixed>
     */
    public function getDirty(): array
    {
        $dirty = [];
        foreach ($this->data as $key => $value) {
            if (!array_key_exists($key, $this->original) || $this->original[$key] !== $value) {
                $dirty[$key] = $value;
            }
        }
        return $dirty;
    }

    /**
     * Get the original value of a field (at load time).
     */
    public function getOriginal(string $field): mixed
    {
        return $this->original[$field] ?? null;
    }

    // ── Serialization ──────────────────────────────────────────

    /**
     * Convert to associative array — compatible with Smarty templates.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->data;
    }

    // ── ArrayAccess ─────────────────────────────────────────────

    public function offsetExists(mixed $offset): bool
    {
        return array_key_exists($offset, $this->data);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->data[$offset] ?? null;
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->data[$offset] = $value;
    }

    public function offsetUnset(mixed $offset): void
    {
        unset($this->data[$offset]);
    }

    // ── JsonSerializable ────────────────────────────────────────

    public function jsonSerialize(): array
    {
        return $this->data;
    }

    // ── Lifecycle hooks ────────────────────────────────────────

    protected function beforeSave(): void {}
    protected function afterSave(): void {}
    protected function beforeDelete(): void {}
    protected function afterDelete(): void {}

    // ── Transaction helper ──────────────────────────────────

    /**
     * Execute a callback inside a database transaction.
     */
    public static function transaction(callable $fn): mixed
    {
        $db = static::db();
        $db->beginTransaction();
        try {
            $result = $fn($db);
            $db->commit();
            return $result;
        } catch (\Throwable $e) {
            $db->rollBack();
            throw $e;
        }
    }

    // ── Atomic column operations ────────────────────────────

    /**
     * Atomically increment a column value.
     */
    public static function increment(int $id, string $column, int $amount = 1): bool
    {
        $table = static::$definition['table'];
        $pk = static::$definition['primary'];
        return static::rawExecute(
            "UPDATE `{$table}` SET `{$column}` = `{$column}` + ? WHERE `{$pk}` = ?",
            [$amount, $id]
        ) > 0;
    }

    /**
     * Atomically decrement a column value (floors at 0).
     */
    public static function decrement(int $id, string $column, int $amount = 1): bool
    {
        $table = static::$definition['table'];
        $pk = static::$definition['primary'];
        return static::rawExecute(
            "UPDATE `{$table}` SET `{$column}` = GREATEST(0, `{$column}` - ?) WHERE `{$pk}` = ?",
            [$amount, $id]
        ) > 0;
    }

    // ── Batch operations ────────────────────────────────────

    /**
     * Insert multiple rows in a single query.
     *
     * @param array[] $rows  Array of associative arrays — all must have the same keys
     */
    public static function batchInsert(array $rows): bool
    {
        if (empty($rows)) {
            return false;
        }

        $table = static::$definition['table'];
        $columns = array_keys($rows[0]);
        $placeholder = '(' . implode(', ', array_fill(0, count($columns), '?')) . ')';

        $sql = sprintf(
            'INSERT INTO `%s` (%s) VALUES %s',
            $table,
            implode(', ', array_map(fn(string $c) => "`{$c}`", $columns)),
            implode(', ', array_fill(0, count($rows), $placeholder))
        );

        $values = [];
        foreach ($rows as $row) {
            foreach ($columns as $col) {
                $values[] = $row[$col] ?? null;
            }
        }

        $stmt = static::db()->prepare($sql);
        return $stmt->execute($values);
    }

    /**
     * Delete one or more records by primary key.
     */
    public static function destroy(int ...$ids): int
    {
        if (empty($ids)) {
            return 0;
        }

        $table = static::$definition['table'];
        $pk = static::$definition['primary'];
        $placeholders = implode(',', array_fill(0, count($ids), '?'));

        return static::rawExecute(
            "DELETE FROM `{$table}` WHERE `{$pk}` IN ({$placeholders})",
            $ids
        );
    }

    // ── State checks ───────────────────────────────────────────

    public function isNew(): bool
    {
        return $this->isNew;
    }

    /**
     * Get the primary key value.
     */
    public function getId(): mixed
    {
        $pk = static::$definition['primary'];
        return $this->data[$pk] ?? null;
    }

    // ── Validation ─────────────────────────────────────────────

    /**
     * Validate data against field definitions.
     *
     * @return array<string, string>  field => error message (empty = valid)
     */
    public function validate(): array
    {
        $errors = [];
        $fields = static::$definition['fields'];

        foreach ($fields as $name => $def) {
            $value = $this->data[$name] ?? null;
            $type = $def['type'] ?? null;

            // Required check — only on INSERT
            if ($this->isNew && !empty($def['required']) && ($value === null || $value === '')) {
                $errors[$name] = ucfirst(str_replace('_', ' ', $name)) . ' is required.';
                continue;
            }

            // Skip further checks if value is null/empty
            if ($value === null || $value === '') {
                continue;
            }

            // Max length
            if (isset($def['maxLength']) && is_string($value) && mb_strlen($value) > $def['maxLength']) {
                $errors[$name] = ucfirst(str_replace('_', ' ', $name)) . " must not exceed {$def['maxLength']} characters.";
            }

            // Enum values
            if ($type === FieldType::Enum && !empty($def['values']) && !in_array($value, $def['values'], true)) {
                $errors[$name] = ucfirst(str_replace('_', ' ', $name)) . ' has an invalid value.';
            }
        }

        return $errors;
    }

    // ── Type casting ───────────────────────────────────────────

    /**
     * Cast database values to PHP types based on field definitions.
     */
    private function castOnHydrate(array $row): array
    {
        $fields = static::$definition['fields'];

        foreach ($row as $key => $value) {
            if ($value === null || !isset($fields[$key]['type'])) {
                continue;
            }

            $row[$key] = match ($fields[$key]['type']) {
                FieldType::Int   => (int) $value,
                FieldType::Bool  => (int) $value,
                FieldType::Decimal => (float) $value,
                FieldType::Json  => is_string($value) ? json_decode($value, true) : $value,
                default          => $value,
            };
        }

        return $row;
    }

    /**
     * Cast a value for database storage based on its field type.
     */
    private function castForStorage(string $key, mixed $value): mixed
    {
        $fields = static::$definition['fields'];

        if ($value === null || !isset($fields[$key]['type'])) {
            return $value;
        }

        return match ($fields[$key]['type']) {
            FieldType::Json => is_array($value) || $value instanceof \stdClass
                ? json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                : $value,
            FieldType::Bool => (int) $value,
            default         => $value,
        };
    }

    // ── Internal persistence ───────────────────────────────────

    private function performInsert(): bool
    {
        $table = static::$definition['table'];
        $pk = static::$definition['primary'];
        $fields = static::$definition['fields'];

        // Apply defaults for missing fields
        foreach ($fields as $name => $def) {
            if (!array_key_exists($name, $this->data) && array_key_exists('default', $def)) {
                $this->data[$name] = $def['default'];
            }
        }

        // Auto-set timestamps
        $now = date('Y-m-d H:i:s');
        if (isset($fields['created_at']) && !isset($this->data['created_at'])) {
            $this->data['created_at'] = $now;
        }
        if (isset($fields['updated_at']) && !isset($this->data['updated_at'])) {
            $this->data['updated_at'] = $now;
        }

        // Build column/value lists — skip primary key (auto-increment) and null values
        $columns = [];
        $placeholders = [];
        $values = [];

        foreach ($this->data as $key => $value) {
            if ($key === $pk && $value === null) {
                continue;
            }
            if ($value === null && $key !== $pk) {
                continue;
            }
            $columns[] = "`{$key}`";
            $placeholders[] = '?';
            $values[] = $this->castForStorage($key, $value);
        }

        if (empty($columns)) {
            return false;
        }

        $sql = sprintf(
            'INSERT INTO `%s` (%s) VALUES (%s)',
            $table,
            implode(', ', $columns),
            implode(', ', $placeholders)
        );

        $stmt = static::db()->prepare($sql);
        $stmt->execute($values);

        $insertId = static::db()->lastInsertId();
        if ($insertId) {
            $this->data[$pk] = (int) $insertId;
        }

        $this->original = $this->data;
        $this->isNew = false;

        return true;
    }

    private function performUpdate(): bool
    {
        $table = static::$definition['table'];
        $pk = static::$definition['primary'];
        $fields = static::$definition['fields'];
        $id = $this->data[$pk] ?? null;

        if ($id === null) {
            return false;
        }

        // Auto-set updated_at timestamp
        if (isset($fields['updated_at'])) {
            $this->data['updated_at'] = date('Y-m-d H:i:s');
        }

        $dirty = $this->getDirty();
        unset($dirty[$pk]); // Never update the primary key

        if (empty($dirty)) {
            return true; // Nothing to update
        }

        $sets = [];
        $values = [];

        foreach ($dirty as $key => $value) {
            $sets[] = "`{$key}` = ?";
            $values[] = $this->castForStorage($key, $value);
        }

        $values[] = $id;

        $sql = sprintf(
            'UPDATE `%s` SET %s WHERE `%s` = ?',
            $table,
            implode(', ', $sets),
            $pk
        );

        $stmt = static::db()->prepare($sql);
        $stmt->execute($values);

        $this->original = $this->data;

        return true;
    }
}
