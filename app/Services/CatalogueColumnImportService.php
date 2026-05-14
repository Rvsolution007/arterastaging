<?php

namespace App\Services;

use App\Models\CatalogueCustomColumn;
use App\Models\ProductCategory;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use Illuminate\Support\Str;

class CatalogueColumnImportService
{
    protected int $userId;

    public function __construct(int $userId)
    {
        $this->userId = $userId;
    }

    public function importFromExcel(string $filePath): array
    {
        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getSheet(0);
        $highestRow = $sheet->getHighestRow();
        $highestColIdx = Coordinate::columnIndexFromString($sheet->getHighestColumn());

        $headers = [];
        for ($c = 1; $c <= $highestColIdx; $c++) {
            $headers[$c] = trim($sheet->getCell(Coordinate::stringFromColumnIndex($c) . '1')->getValue() ?? '');
        }

        $fieldMap = $this->mapHeaders($headers);

        if (!isset($fieldMap['name'])) {
            throw new \RuntimeException('Excel must have a "Name" column in the header row.');
        }

        $created = 0; $skipped = 0; $errors = []; $categoriesCreated = [];

        $existingSlugs = CatalogueCustomColumn::where('user_id', $this->userId)->pluck('slug')->toArray();
        $maxSort = CatalogueCustomColumn::where('user_id', $this->userId)->max('sort_order') ?? 0;

        for ($row = 2; $row <= $highestRow; $row++) {
            $rowData = []; $isEmpty = true;
            for ($c = 1; $c <= $highestColIdx; $c++) {
                $val = $sheet->getCell(Coordinate::stringFromColumnIndex($c) . $row)->getValue();
                if ($val !== null && $val !== '') $isEmpty = false;
                $rowData[$c] = trim($val ?? '');
            }
            if ($isEmpty) continue;

            $name = $rowData[$fieldMap['name']] ?? '';
            if (empty($name)) { $errors[] = ['row' => $row, 'error' => 'Name is empty']; $skipped++; continue; }

            $slug = Str::slug($name, '_');
            if (in_array($slug, $existingSlugs)) { $skipped++; continue; }

            $type = $this->normalizeType($rowData[$fieldMap['type'] ?? 0] ?? 'text');
            $optionsStr = $rowData[$fieldMap['options'] ?? 0] ?? '';
            $options = !empty($optionsStr) ? array_values(array_filter(array_map('trim', explode(',', $optionsStr)))) : null;

            $isRequired = $this->parseBoolean($rowData[$fieldMap['is_required'] ?? 0] ?? 'No');
            $isUnique = $this->parseBoolean($rowData[$fieldMap['is_unique'] ?? 0] ?? 'No');
            $isCategory = $this->parseBoolean($rowData[$fieldMap['is_category'] ?? 0] ?? 'No');
            $isTitle = $this->parseBoolean($rowData[$fieldMap['is_title'] ?? 0] ?? 'No');
            $isCombo = $this->parseBoolean($rowData[$fieldMap['is_combo'] ?? 0] ?? 'No');
            $isVariationField = $this->parseBoolean($rowData[$fieldMap['is_variation_field'] ?? 0] ?? 'No');
            $showInAI = $this->parseBoolean($rowData[$fieldMap['show_in_ai'] ?? 0] ?? 'Yes');
            $sortOrder = intval($rowData[$fieldMap['sort_order'] ?? 0] ?? 0);
            if ($sortOrder <= 0) $sortOrder = ++$maxSort;

            if ($isCategory && !empty($options)) {
                foreach ($options as $catName) {
                    $exists = ProductCategory::where('name', $catName)->exists();
                    if (!$exists) {
                        ProductCategory::create([
                            'name' => $catName,
                            'status' => '1',
                        ]);
                        $categoriesCreated[] = $catName;
                    }
                }
            }

            try {
                CatalogueCustomColumn::create([
                    'user_id' => $this->userId,
                    'name' => $name, 'slug' => $slug, 'type' => $type,
                    'options' => (in_array($type, ['select', 'multiselect']) && $options) ? $options : null,
                    'is_required' => $isRequired, 'is_unique' => $isUnique,
                    'is_category' => $isCategory, 'is_title' => $isTitle,
                    'is_combo' => $isCombo, 'is_variation_field' => $isVariationField,
                    'is_system' => false, 'is_active' => true,
                    'show_on_list' => true, 'show_in_ai' => $showInAI,
                    'sort_order' => $sortOrder,
                ]);
                $existingSlugs[] = $slug;
                $created++;
            } catch (\Exception $e) {
                $errors[] = ['row' => $row, 'error' => $e->getMessage()];
                $skipped++;
            }
        }

        return ['created' => $created, 'skipped' => $skipped, 'errors' => $errors, 'categories_created' => array_unique($categoriesCreated)];
    }

    public function importFromArray(array $columns): array
    {
        $created = 0; $skipped = 0; $errors = []; $categoriesCreated = [];

        $existingSlugs = CatalogueCustomColumn::where('user_id', $this->userId)->pluck('slug')->toArray();
        $maxSort = CatalogueCustomColumn::where('user_id', $this->userId)->max('sort_order') ?? 0;

        foreach ($columns as $col) {
            $name = $col['name'] ?? '';
            if (empty($name)) continue;

            $slug = Str::slug($name, '_');
            if (in_array($slug, $existingSlugs)) { $skipped++; continue; }

            $type = $col['type'] ?? 'text';
            $options = $col['options'] ?? null;
            $isCategory = $col['is_category'] ?? false;

            if ($isCategory && !empty($options)) {
                foreach ($options as $catName) {
                    $exists = ProductCategory::where('name', $catName)->exists();
                    if (!$exists) {
                        ProductCategory::create([
                            'name' => $catName,
                            'status' => '1',
                        ]);
                        $categoriesCreated[] = $catName;
                    }
                }
            }

            try {
                CatalogueCustomColumn::create([
                    'user_id' => $this->userId,
                    'name' => $name, 'slug' => $slug, 'type' => $type,
                    'options' => (in_array($type, ['select', 'multiselect']) || !empty($col['is_combo'])) ? ($options ?: null) : null,
                    'is_required' => $col['is_required'] ?? false,
                    'is_unique' => $col['is_unique'] ?? false,
                    'is_category' => $isCategory,
                    'is_title' => $col['is_title'] ?? false,
                    'is_combo' => $col['is_combo'] ?? false,
                    'is_variation_field' => $col['is_variation_field'] ?? false,
                    'is_system' => false, 'is_active' => true,
                    'show_on_list' => true,
                    'show_in_ai' => $col['show_in_ai'] ?? true,
                    'sort_order' => $col['sort_order'] ?? (++$maxSort),
                ]);
                $existingSlugs[] = $slug;
                $created++;
            } catch (\Exception $e) {
                $errors[] = ['column' => $name, 'error' => $e->getMessage()];
                $skipped++;
            }
        }

        return ['created' => $created, 'skipped' => $skipped, 'errors' => $errors, 'categories_created' => array_unique($categoriesCreated)];
    }

    private function mapHeaders(array $headers): array
    {
        $map = [];
        $aliases = [
            'name' => ['name', 'field name', 'column name', 'label', 'field label'],
            'type' => ['type', 'input type', 'field type', 'data type'],
            'options' => ['options', 'dropdown options', 'options (comma-separated)', 'values'],
            'is_required' => ['is required', 'required', 'is_required', 'mandatory'],
            'is_unique' => ['is unique', 'unique', 'is_unique', 'unique identifier'],
            'is_category' => ['is category', 'category', 'is_category', 'category linked'],
            'is_title' => ['is title', 'title', 'is_title', 'display title'],
            'is_combo' => ['is combo', 'combo', 'is_combo', 'variation', 'variation matrix'],
            'is_variation_field' => ['is variation field', 'variation field', 'is_variation_field', 'per-variation', 'per variation'],
            'show_in_ai' => ['show in ai', 'ai', 'show_in_ai', 'ai bot access'],
            'sort_order' => ['sort order', 'order', 'sort_order', 'position'],
        ];
        foreach ($headers as $colIdx => $header) {
            $normalized = mb_strtolower(trim($header));
            foreach ($aliases as $field => $alts) {
                foreach ($alts as $alt) {
                    if ($normalized === $alt) { $map[$field] = $colIdx; break 2; }
                }
            }
        }
        return $map;
    }

    private function normalizeType(string $type): string
    {
        $type = mb_strtolower(trim($type));
        $valid = ['text', 'textarea', 'number', 'select', 'multiselect', 'boolean'];
        if (in_array($type, $valid)) return $type;
        $aliases = [
            'string' => 'text', 'short text' => 'text', 'varchar' => 'text',
            'long text' => 'textarea', 'description' => 'textarea', 'memo' => 'textarea',
            'int' => 'number', 'integer' => 'number', 'decimal' => 'number', 'float' => 'number', 'numeric' => 'number',
            'dropdown' => 'select', 'list' => 'select', 'enum' => 'select',
            'multi-select' => 'multiselect', 'multi select' => 'multiselect', 'tags' => 'multiselect',
            'yes/no' => 'boolean', 'bool' => 'boolean', 'switch' => 'boolean', 'toggle' => 'boolean',
        ];
        return $aliases[$type] ?? 'text';
    }

    private function parseBoolean($value): bool
    {
        if (is_bool($value)) return $value;
        $str = mb_strtolower(trim($value));
        return in_array($str, ['yes', '1', 'true']);
    }
}
