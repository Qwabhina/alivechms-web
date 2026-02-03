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
}
