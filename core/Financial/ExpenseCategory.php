<?php

/**
 * Expense Category Management
 *
 * Orchestrates expense categories and delegates to LookupRepository.
 *
 * @package  AliveChMS\Core
 * @version  2.0.0
 */

declare(strict_types=1);

namespace AliveChMS\Core\Financial;

use AliveChMS\Core\System\LookupRepository;
use AliveChMS\Core\System\Helpers;

class ExpenseCategory
{
    public static function getAll(): array
    {
        $repo = new LookupRepository('expense_category', 'ExpCategoryID');
        return ['data' => $repo->getAll()];
    }

    public static function create(array $data): array
    {
        $repo = new LookupRepository('expense_category', 'ExpCategoryID');
        Helpers::validateInput($data, ['name' => 'required|max:50']);

        $id = $repo->create([
            'CategoryName' => trim($data['name']),
            'CategoryDescription' => $data['description'] ?? null,
            'IsActive' => 1
        ]);

        return ['status' => 'success', 'category_id' => $id];
    }
    public static function update(int $id, array $data): array
    {
        $repo = new LookupRepository('expense_category', 'ExpCategoryID');
        Helpers::validateInput($data, ['name' => 'required|max:50']);

        $repo->update($id, [
            'CategoryName' => trim($data['name']),
            'CategoryDescription' => $data['description'] ?? null,
            'IsActive' => $data['is_active'] ?? 1
        ]);

        return ['status' => 'success'];
    }
    public static function delete(int $id): array
    {
        $repo = new LookupRepository('expense_category', 'ExpCategoryID');
        $repo->delete($id);
        return ['status' => 'success'];
    }
    public static function get(int $id): array
    {
        $repo = new LookupRepository('expense_category', 'ExpCategoryID');
        return $repo->findById($id);
    }
}
