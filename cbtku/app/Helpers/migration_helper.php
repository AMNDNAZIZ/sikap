<?php

/**
 * Migration Helper Functions
 * 
 * Helper functions untuk membuat migration lebih aman dengan checking database state
 * sebelum melakukan perubahan. Ini mencegah error saat migration dijalankan ulang.
 */

if (!function_exists('safe_add_column')) {
    /**
     * Safely add column to table (check if not exists first)
     * 
     * @param object $forge Database Forge instance
     * @param string $table Table name
     * @param array $fields Column definition
     * @return bool True if column added or already exists
     */
    function safe_add_column($forge, $table, $fields)
    {
        $db = \Config\Database::connect();
        
        foreach ($fields as $columnName => $definition) {
            if (!column_exists($table, $columnName)) {
                try {
                    $forge->addColumn($table, [$columnName => $definition]);
                    log_message('info', "Column '{$columnName}' added to table '{$table}'");
                } catch (\Throwable $e) {
                    log_message('error', "Failed to add column '{$columnName}' to '{$table}': " . $e->getMessage());
                    return false;
                }
            } else {
                log_message('info', "Column '{$columnName}' already exists in table '{$table}', skipping");
            }
        }
        
        return true;
    }
}

if (!function_exists('safe_drop_column')) {
    /**
     * Safely drop column from table (check if exists first)
     * 
     * @param object $forge Database Forge instance
     * @param string $table Table name
     * @param string|array $columnName Column name(s) to drop
     * @return bool True if column dropped or doesn't exist
     */
    function safe_drop_column($forge, $table, $columnName)
    {
        $columns = is_array($columnName) ? $columnName : [$columnName];
        
        foreach ($columns as $col) {
            if (column_exists($table, $col)) {
                try {
                    $forge->dropColumn($table, $col);
                    log_message('info', "Column '{$col}' dropped from table '{$table}'");
                } catch (\Throwable $e) {
                    log_message('error', "Failed to drop column '{$col}' from '{$table}': " . $e->getMessage());
                    return false;
                }
            } else {
                log_message('info', "Column '{$col}' doesn't exist in table '{$table}', skipping");
            }
        }
        
        return true;
    }
}

if (!function_exists('safe_create_table')) {
    /**
     * Safely create table (check if not exists first)
     * 
     * @param object $forge Database Forge instance
     * @param string $table Table name
     * @param array $fields Table fields definition
     * @param bool $ifNotExists Use IF NOT EXISTS clause
     * @return bool True if table created or already exists
     */
    function safe_create_table($forge, $table, $fields, $ifNotExists = true)
    {
        if (!table_exists($table)) {
            try {
                $forge->addField($fields);
                $forge->createTable($table, $ifNotExists);
                log_message('info', "Table '{$table}' created successfully");
                return true;
            } catch (\Throwable $e) {
                log_message('error', "Failed to create table '{$table}': " . $e->getMessage());
                return false;
            }
        } else {
            log_message('info', "Table '{$table}' already exists, skipping");
            return true;
        }
    }
}

if (!function_exists('safe_drop_table')) {
    /**
     * Safely drop table (check if exists first)
     * 
     * @param object $forge Database Forge instance
     * @param string $table Table name
     * @param bool $ifExists Use IF EXISTS clause
     * @return bool True if table dropped or doesn't exist
     */
    function safe_drop_table($forge, $table, $ifExists = true)
    {
        if (table_exists($table)) {
            try {
                $forge->dropTable($table, $ifExists);
                log_message('info', "Table '{$table}' dropped successfully");
                return true;
            } catch (\Throwable $e) {
                log_message('error', "Failed to drop table '{$table}': " . $e->getMessage());
                return false;
            }
        } else {
            log_message('info', "Table '{$table}' doesn't exist, skipping");
            return true;
        }
    }
}

if (!function_exists('safe_modify_column')) {
    /**
     * Safely modify column (check if exists first)
     * 
     * @param object $forge Database Forge instance
     * @param string $table Table name
     * @param array $fields Column definition
     * @return bool True if column modified or doesn't exist
     */
    function safe_modify_column($forge, $table, $fields)
    {
        foreach ($fields as $columnName => $definition) {
            if (column_exists($table, $columnName)) {
                try {
                    $forge->modifyColumn($table, [$columnName => $definition]);
                    log_message('info', "Column '{$columnName}' modified in table '{$table}'");
                } catch (\Throwable $e) {
                    log_message('error', "Failed to modify column '{$columnName}' in '{$table}': " . $e->getMessage());
                    return false;
                }
            } else {
                log_message('warning', "Column '{$columnName}' doesn't exist in table '{$table}', cannot modify");
                return false;
            }
        }
        
        return true;
    }
}

if (!function_exists('safe_add_key')) {
    /**
     * Safely add index/key to table (check if not exists first)
     * 
     * @param object $forge Database Forge instance
     * @param string $table Table name
     * @param string|array $key Column name(s) for key
     * @param bool $primary Is primary key
     * @param bool $unique Is unique key
     * @param string $keyName Custom key name
     * @return bool True if key added or already exists
     */
    function safe_add_key($forge, $table, $key, $primary = false, $unique = false, $keyName = '')
    {
        $db = \Config\Database::connect();
        
        // Generate key name if not provided
        if (empty($keyName)) {
            $keyName = is_array($key) ? implode('_', $key) : $key;
            if ($primary) {
                $keyName = 'PRIMARY';
            } elseif ($unique) {
                $keyName = 'unique_' . $keyName;
            } else {
                $keyName = 'idx_' . $keyName;
            }
        }
        
        // Check if key exists
        if (!index_exists($table, $keyName)) {
            try {
                if ($primary) {
                    $forge->addPrimaryKey($key);
                } elseif ($unique) {
                    $forge->addUniqueKey($key, $keyName);
                } else {
                    $forge->addKey($key, false, false, $keyName);
                }
                
                // For non-primary keys, we need to process the table
                if (!$primary) {
                    $db->query("ALTER TABLE `{$table}` ADD " . 
                              ($unique ? "UNIQUE " : "") . 
                              "INDEX `{$keyName}` (" . 
                              (is_array($key) ? implode(',', array_map(function($k) { return "`{$k}`"; }, $key)) : "`{$key}`") . 
                              ")");
                }
                
                log_message('info', "Key '{$keyName}' added to table '{$table}'");
                return true;
            } catch (\Throwable $e) {
                log_message('error', "Failed to add key '{$keyName}' to '{$table}': " . $e->getMessage());
                return false;
            }
        } else {
            log_message('info', "Key '{$keyName}' already exists in table '{$table}', skipping");
            return true;
        }
    }
}

if (!function_exists('table_exists')) {
    /**
     * Check if table exists in database
     * 
     * @param string $table Table name
     * @return bool True if table exists
     */
    function table_exists($table)
    {
        $db = \Config\Database::connect();
        return $db->tableExists($table);
    }
}

if (!function_exists('column_exists')) {
    /**
     * Check if column exists in table
     * 
     * @param string $table Table name
     * @param string $column Column name
     * @return bool True if column exists
     */
    function column_exists($table, $column)
    {
        $db = \Config\Database::connect();
        
        if (!$db->tableExists($table)) {
            return false;
        }
        
        return $db->fieldExists($column, $table);
    }
}

if (!function_exists('index_exists')) {
    /**
     * Check if index/key exists in table
     * 
     * @param string $table Table name
     * @param string $keyName Key name
     * @return bool True if key exists
     */
    function index_exists($table, $keyName)
    {
        $db = \Config\Database::connect();
        
        if (!$db->tableExists($table)) {
            return false;
        }
        
        try {
            $query = $db->query("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$keyName]);
            return $query->getNumRows() > 0;
        } catch (\Throwable $e) {
            return false;
        }
    }
}

if (!function_exists('get_table_columns')) {
    /**
     * Get all columns in a table
     * 
     * @param string $table Table name
     * @return array Array of column names
     */
    function get_table_columns($table)
    {
        $db = \Config\Database::connect();
        
        if (!$db->tableExists($table)) {
            return [];
        }
        
        return $db->getFieldNames($table);
    }
}

if (!function_exists('get_column_type')) {
    /**
     * Get column data type
     * 
     * @param string $table Table name
     * @param string $column Column name
     * @return string|null Column type or null if not found
     */
    function get_column_type($table, $column)
    {
        $db = \Config\Database::connect();
        
        if (!column_exists($table, $column)) {
            return null;
        }
        
        try {
            $query = $db->query("SHOW COLUMNS FROM `{$table}` WHERE Field = ?", [$column]);
            $result = $query->getRow();
            return $result ? $result->Type : null;
        } catch (\Throwable $e) {
            return null;
        }
    }
}

if (!function_exists('safe_rename_column')) {
    /**
     * Safely rename column (check if old exists and new doesn't exist)
     * 
     * @param string $table Table name
     * @param string $oldName Old column name
     * @param string $newName New column name
     * @param string $definition Column definition (e.g., 'VARCHAR(255) NOT NULL')
     * @return bool True if column renamed successfully
     */
    function safe_rename_column($table, $oldName, $newName, $definition)
    {
        $db = \Config\Database::connect();
        
        if (!column_exists($table, $oldName)) {
            log_message('info', "Column '{$oldName}' doesn't exist in table '{$table}', skipping rename");
            return true;
        }
        
        if (column_exists($table, $newName)) {
            log_message('info', "Column '{$newName}' already exists in table '{$table}', skipping rename");
            return true;
        }
        
        try {
            $db->query("ALTER TABLE `{$table}` CHANGE `{$oldName}` `{$newName}` {$definition}");
            log_message('info', "Column '{$oldName}' renamed to '{$newName}' in table '{$table}'");
            return true;
        } catch (\Throwable $e) {
            log_message('error', "Failed to rename column '{$oldName}' to '{$newName}' in '{$table}': " . $e->getMessage());
            return false;
        }
    }
}

if (!function_exists('safe_execute_query')) {
    /**
     * Safely execute raw SQL query with error handling
     * 
     * @param string $sql SQL query
     * @param array $params Query parameters
     * @return bool True if query executed successfully
     */
    function safe_execute_query($sql, $params = [])
    {
        $db = \Config\Database::connect();
        
        try {
            if (empty($params)) {
                $db->query($sql);
            } else {
                $db->query($sql, $params);
            }
            log_message('info', "Query executed successfully: " . substr($sql, 0, 100));
            return true;
        } catch (\Throwable $e) {
            log_message('error', "Failed to execute query: " . $e->getMessage() . " | SQL: " . substr($sql, 0, 100));
            return false;
        }
    }
}
