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

        if (!empty($orm->getWhere('expense_category', ['ExpCategoryName' => $name]))) {
            ResponseHelper::error('Category name already exists', 400);
        }

        $categoryId = $orm->insert('expense_category', [
            'ExpCategoryName' => $name
        ])['id'];

        return ['status' => 'success', 'category_id' => $categoryId];
    }

    /**
     * Update an existing expense category
     */
    public static function update(int $categoryId, array $data): array
    {
        $orm = new ORM();

        $category = $orm->getWhere('expense_category', ['ExpCategoryID' => $categoryId]);
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
             WHERE ExpCategoryName = :name AND ExpCategoryID != :id",
            [':name' => $name, ':id' => $categoryId]
        );

        if (!empty($existing)) {
            ResponseHelper::error('Category name already exists', 400);
        }

        $orm->update('expense_category', ['ExpCategoryName' => $name], ['ExpCategoryID' => $categoryId]);
        return ['status' => 'success', 'category_id' => $categoryId];
    }

    /**
     * Delete an expense category (only if unused)
     */
    public static function delete(int $categoryId): array
    {
        $orm = new ORM();

        $category = $orm->getWhere('expense_category', ['ExpCategoryID' => $categoryId]);
        if (empty($category)) {
            ResponseHelper::error('Category not found', 404);
        }

        if (!empty($orm->getWhere('expense', ['ExpCategoryID' => $categoryId]))) {
            ResponseHelper::error('Cannot delete category used in expenses', 400);
        }

        $orm->delete('expense_category', ['ExpCategoryID' => $categoryId]);
        return ['status' => 'success'];
    }

    /**
     * Retrieve a single expense category
     */
    public static function get(int $categoryId): array
    {
        $orm = new ORM();

        $category = $orm->getWhere('expense_category', ['ExpCategoryID' => $categoryId]);
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
            "SELECT ExpCategoryID AS ExpenseCategoryID, ExpCategoryName AS CategoryName FROM expense_category ORDER BY ExpCategoryName ASC"
        );

        return ['data' => $categories];
    }
}
