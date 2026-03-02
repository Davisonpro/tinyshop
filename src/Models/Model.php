<?php

declare(strict_types=1);

namespace TinyShop\Models;

use PDO;
use TinyShop\Enums\FieldType;
use TinyShop\Exceptions\ValidationException;

/**
 * Active Record base model.
 *
 * @since 1.0.0
 *
 * @implements \ArrayAccess<string, mixed>
 */
abstract class Model implements \ArrayAccess, \JsonSerializable
{
    private static ?PDO $pdo = null;

    /** @var array<string, mixed> */
    protected array $data = [];

    /** @var array<string, mixed> */
    private array $original = [];

    private bool $isNew = true;

    /** @var array{table: string, primary: string, fields: array<string, array>} Table schema definition. */
    protected static array $definition = [
        'table'   => '',
        'primary' => 'id',
        'fields'  => [],
    ];

    // ── Bootstrap ──────────────────────────────────────────────

    /**
     * Set the shared PDO connection for all models.
     *
     * @since 1.0.0
     *
     * @param PDO $pdo Database connection.
     */
    public static function boot(PDO $pdo): void
    {
        self::$pdo = $pdo;
    }

    /**
     * Get the shared PDO connection.
     *
     * @since 1.0.0
     *
     * @return PDO
     * @throws \RuntimeException If boot() has not been called.
     */
    protected static function db(): PDO
    {
        if (self::$pdo === null) {
            throw new \RuntimeException('Model::boot() has not been called.');
        }
        return self::$pdo;
    }

    // ── Magic accessors ────────────────────────────────────────

    /** @return mixed */
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

    /** Hydrate a single row into a model instance. */
    protected static function hydrate(array $row): static
    {
        $instance = new static();
        $instance->data = $instance->castOnHydrate($row);
        $instance->original = $instance->data;
        $instance->isNew = false;
        return $instance;
    }

    /**
     * Hydrate multiple rows.
     *
     * @since 1.0.0
     *
     * @param  array[]  $rows Database rows.
     * @return static[]
     */
    protected static function hydrateAll(array $rows): array
    {
        return array_map(static fn(array $row) => static::hydrate($row), $rows);
    }

    // ── Static finders ─────────────────────────────────────────

    /**
     * Find a record by primary key.
     *
     * @since 1.0.0
     *
     * @param  int $id Primary key value.
     * @return static|null
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
     *
     * @since 1.0.0
     *
     * @param  string $column Column name.
     * @param  mixed  $value  Value to match.
     * @return static|null
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
     * @since 1.0.0
     *
     * @param  string  $column  Column name.
     * @param  mixed   $value   Value to match.
     * @param  ?int    $limit   Max rows.
     * @param  int     $offset  Starting offset.
     * @param  string  $orderBy ORDER BY clause.
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
     * @since 1.0.0
     *
     * @param  ?int   $limit   Max rows.
     * @param  int    $offset  Starting offset.
     * @param  string $orderBy ORDER BY clause.
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
     *
     * @since 1.0.0
     *
     * @param  ?string $column Column to filter by.
     * @param  mixed   $value  Value to match.
     * @return int
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
     * Check if a record exists matching a column value.
     *
     * @since 1.0.0
     *
     * @param  string $column    Column to check.
     * @param  mixed  $value     Value to match.
     * @param  ?int   $excludeId ID to exclude from the check.
     * @return bool
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
     * Find first record matching multiple conditions.
     *
     * @since 1.0.0
     *
     * @param  array<string, mixed> $conditions Column => value pairs.
     * @return static|null
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
     * Find all records matching multiple conditions.
     *
     * @since 1.0.0
     *
     * @param  array<string, mixed> $conditions Column => value pairs.
     * @param  ?int                 $limit      Max rows.
     * @param  int                  $offset     Starting offset.
     * @param  string               $orderBy    ORDER BY clause.
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
     * Run a raw SELECT and return associative arrays.
     *
     * @since 1.0.0
     *
     * @param  string $sql    SQL query.
     * @param  array  $params Bound parameters.
     * @return array[]
     */
    public static function rawQuery(string $sql, array $params = []): array
    {
        $stmt = static::db()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Run a raw query and return a single scalar.
     *
     * @since 1.0.0
     *
     * @param  string $sql    SQL query.
     * @param  array  $params Bound parameters.
     * @return mixed
     */
    public static function rawScalar(string $sql, array $params = []): mixed
    {
        $stmt = static::db()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn();
    }

    /**
     * Run a raw INSERT/UPDATE/DELETE and return affected rows.
     *
     * @since 1.0.0
     *
     * @param  string $sql    SQL statement.
     * @param  array  $params Bound parameters.
     * @return int
     */
    public static function rawExecute(string $sql, array $params = []): int
    {
        $stmt = static::db()->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }

    // ── Instance CRUD ──────────────────────────────────────────

    /**
     * Save the model (insert or update).
     *
     * @since 1.0.0
     *
     * @return bool
     * @throws ValidationException If validation fails.
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
     * Delete a record by ID or the current instance.
     *
     * @since 1.0.0
     *
     * @param  ?int $id Primary key, or null to delete current instance.
     * @return bool
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
     * Reload from the database.
     *
     * @since 1.0.0
     *
     * @return static
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
     * Mass-assign data from an array (whitelisted fields only).
     *
     * @since 1.0.0
     *
     * @param  array $data Column => value pairs.
     * @return static
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
     * Check if a field (or any field) has changed since load.
     *
     * @since 1.0.0
     *
     * @param  ?string $field Specific field, or null for any.
     * @return bool
     */
    public function isDirty(?string $field = null): bool
    {
        if ($field !== null) {
            return ($this->data[$field] ?? null) !== ($this->original[$field] ?? null);
        }

        return !empty($this->getDirty());
    }

    /**
     * Get all changed fields since load.
     *
     * @since 1.0.0
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
     * Get the original value of a field before changes.
     *
     * @since 1.0.0
     *
     * @param  string $field Field name.
     * @return mixed
     */
    public function getOriginal(string $field): mixed
    {
        return $this->original[$field] ?? null;
    }

    // ── Serialization ──────────────────────────────────────────

    /**
     * Convert to associative array.
     *
     * @since 1.0.0
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->data;
    }

    // ── ArrayAccess ─────────────────────────────────────────────

    /** @inheritDoc */
    public function offsetExists(mixed $offset): bool
    {
        return array_key_exists($offset, $this->data);
    }

    /** @inheritDoc */
    public function offsetGet(mixed $offset): mixed
    {
        return $this->data[$offset] ?? null;
    }

    /** @inheritDoc */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->data[$offset] = $value;
    }

    /** @inheritDoc */
    public function offsetUnset(mixed $offset): void
    {
        unset($this->data[$offset]);
    }

    // ── JsonSerializable ────────────────────────────────────────

    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        return $this->data;
    }

    // ── Lifecycle hooks ────────────────────────────────────────

    /** Called before insert or update. Override for pre-save logic. */
    protected function beforeSave(): void {}

    /** Called after a successful insert or update. */
    protected function afterSave(): void {}

    /** Called before a delete is executed. */
    protected function beforeDelete(): void {}

    /** Called after a successful delete. */
    protected function afterDelete(): void {}

    // ── Transaction helper ──────────────────────────────────

    /**
     * Run a callback inside a database transaction.
     *
     * @since 1.0.0
     *
     * @param  callable $fn Receives the PDO instance.
     * @return mixed    The callback's return value.
     * @throws \Throwable Re-thrown after rollback.
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
     * Atomically increment a column.
     *
     * @since 1.0.0
     *
     * @param  int    $id     Record ID.
     * @param  string $column Column name.
     * @param  int    $amount Increment amount.
     * @return bool
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
     * Atomically decrement a column (floors at 0).
     *
     * @since 1.0.0
     *
     * @param  int    $id     Record ID.
     * @param  string $column Column name.
     * @param  int    $amount Decrement amount.
     * @return bool
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
     * @since 1.0.0
     *
     * @param  array[] $rows Rows to insert (all must share the same keys).
     * @return bool
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
     *
     * @since 1.0.0
     *
     * @param  int ...$ids Primary keys to delete.
     * @return int         Number of deleted rows.
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

    /** Whether this instance is new (not yet saved). */
    public function isNew(): bool
    {
        return $this->isNew;
    }

    /** Get the primary key value. */
    public function getId(): mixed
    {
        $pk = static::$definition['primary'];
        return $this->data[$pk] ?? null;
    }

    // ── Validation ─────────────────────────────────────────────

    /**
     * Validate data against field definitions.
     *
     * @since 1.0.0
     *
     * @return array<string, string> Field => error message (empty = valid).
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

    /** Cast DB values to PHP types on hydrate. */
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

    /** Cast a PHP value for DB storage. */
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

    /** Perform an INSERT with defaults and timestamps. */
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

    /** UPDATE only dirty fields. */
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
