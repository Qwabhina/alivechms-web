<?php

/**
 * Expense Category Management
 *
 * Full CRUD operations for expense categories with uniqueness,
 * usage protection, and audit trail.
 *
 * @package  AliveChMS\Core
 * @version  1.1.0
 * @author   Benjamin Ebo Yankson
 * @since    2025-November
 */

declare(strict_types=1);

class ExpenseCategory
{
    /**
     * Create a new expense category
     */
    public static function create(array $data): array
    {
        $orm = new ORM();

        Helpers::validateInput($data, [
            'name' => 'required|max:50'
        ]);

        $name = trim($data['name']);

        if (!empty($orm->getWhere('expense_category', ['CategoryName' => $name, 'IsActive' => 1]))) {
            ResponseHelper::error('Category name already exists', 400);
        }

        $categoryId = $orm->insert('expense_category', [
            'CategoryName' => $name,
            'CategoryDescription' => $data['description'] ?? null,
            'IsActive' => 1
        ])['id'];

        return ['status' => 'success', 'category_id' => $categoryId];
    }

    /**
     * Update an existing expense category
     */
    public static function update(int $categoryId, array $data): array
    {
        $orm = new ORM();

        $category = $orm->getWhere('expense_category', ['ExpCategoryID' => $categoryId, 'IsActive' => 1]);
        if (empty($category)) {
            ResponseHelper::error('Category not found', 404);
        }

        if (empty($data['name'])) {
            return ['status' => 'success', 'category_id' => $categoryId];
        }

        $name = trim($data['name']);
        Helpers::validateInput(['name' => $name], ['name' => 'required|max:50']);

        $existing = $orm->runQuery(
            "SELECT ExpCategoryID FROM expense_category 
             WHERE CategoryName = :name AND ExpCategoryID != :id AND IsActive = 1",
            [':name' => $name, ':id' => $categoryId]
        );

        if (!empty($existing)) {
            ResponseHelper::error('Category name already exists', 400);
        }

        $update = ['CategoryName' => $name];
        if (isset($data['description'])) {
            $update['CategoryDescription'] = $data['description'];
        }

        $orm->update('expense_category', $update, ['ExpCategoryID' => $categoryId]);
        return ['status' => 'success', 'category_id' => $categoryId];
    }

    /**
     * Delete an expense category (only if unused)
     */
    public static function delete(int $categoryId): array
    {
        $orm = new ORM();

        $category = $orm->getWhere('expense_category', ['ExpCategoryID' => $categoryId, 'IsActive' => 1]);
        if (empty($category)) {
            ResponseHelper::error('Category not found', 404);
        }

        if (!empty($orm->getWhere('expense', ['ExpCategoryID' => $categoryId]))) {
            ResponseHelper::error('Cannot delete category used in expenses', 400);
        }

        // Soft delete
        $orm->update('expense_category', ['IsActive' => 0], ['ExpCategoryID' => $categoryId]);
        return ['status' => 'success'];
    }

    /**
     * Retrieve a single expense category
     */
    public static function get(int $categoryId): array
    {
        $orm = new ORM();

        $category = $orm->getWhere('expense_category', ['ExpCategoryID' => $categoryId, 'IsActive' => 1]);
        if (empty($category)) {
            ResponseHelper::error('Category not found', 404);
        }

        return $category[0];
    }

    /**
     * Retrieve all expense categories
     */
    public static function getAll(): array
    {
        $orm = new ORM();
        $categories = $orm->runQuery(
            "SELECT ExpCategoryID, CategoryName, CategoryDescription, IsActive 
             FROM expense_category 
             WHERE IsActive = 1
             ORDER BY CategoryName ASC"
        );

        return ['data' => $categories];
    }
}
