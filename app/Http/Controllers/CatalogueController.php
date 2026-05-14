<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Models\AiSetting;
use App\Models\Business;
use App\Models\Product;
use App\Models\ProductCombo;
use App\Models\ProductVariation;
use App\Models\CatalogueCustomColumn;
use App\Models\CatalogueCustomValue;
use App\Models\UserNotification;
use App\Services\CatalogueAIService;
use App\Services\CatalogueColumnImportService;

class CatalogueController extends Controller
{
    /**
     * Common data for all client views (header, business, notifications).
     */
    private function getCommonData()
    {
        $business = Business::where('user_id', Auth::id())->where('is_default', 1)->first();
        if (!$business) {
            $business = Business::where('user_id', Auth::id())->first();
        }
        $notification_count = UserNotification::query();
        $user = Auth::user();
        if ($user && $user->last_notification_read_at) {
            $notification_count->where('created_at', '>', $user->last_notification_read_at);
        }
        $notification_count = $notification_count->count();
        return compact('business', 'notification_count');
    }

    // ─────────────────────────────────────────────────────────────
    //  SETUP WIZARD
    // ─────────────────────────────────────────────────────────────

    /**
     * Show the AI Setup Wizard page.
     */
    public function setupWizard()
    {
        $data = $this->getCommonData();
        $userId = Auth::id();

        // Load cached AI columns from previous analysis (if any)
        $cachedColumns = json_decode(
            AiSetting::get('setup_wizard_columns_' . $userId) ?? '[]', true
        );
        $cachedProducts = json_decode(
            AiSetting::get('setup_wizard_products_' . $userId) ?? '[]', true
        );

        // Check if columns already exist in DB
        $existingColumns = CatalogueCustomColumn::where('user_id', $userId)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        $isConfigured = (new CatalogueAIService())->isConfigured();

        return view('client.setup_wizard', array_merge($data, [
            'cachedColumns' => $cachedColumns,
            'cachedProducts' => $cachedProducts,
            'existingColumns' => $existingColumns,
            'isConfigured' => $isConfigured,
        ]));
    }

    /**
     * Analyze catalogue (Step 1) — Send PDF/URL to Vertex AI.
     */
    public function analyze(Request $request)
    {
        $request->validate([
            'source_type' => 'required|in:pdf,website',
            'catalogue_pdf' => 'required_if:source_type,pdf|file|mimes:pdf|max:61440',
            'website_url' => 'required_if:source_type,website|nullable|url',
        ]);

        $userId = Auth::id();
        $catalogueAI = new CatalogueAIService();

        if (!$catalogueAI->isConfigured()) {
            return response()->json([
                'success' => false,
                'message' => 'AI service not configured. Please contact admin.',
            ], 422);
        }

        ini_set('memory_limit', '1G');
        set_time_limit(3600);

        try {
            $sourceType = $request->source_type;
            $content = '';
            $pdfPath = null;

            if ($sourceType === 'pdf') {
                $uploadedFile = $request->file('catalogue_pdf');
                $originalName = $uploadedFile->getClientOriginalName();

                Log::info('SetupWizard: PDF upload received', [
                    'name' => $originalName,
                    'size_mb' => round($uploadedFile->getSize() / 1024 / 1024, 2),
                ]);

                $tempDir = storage_path('app/temp');
                if (!is_dir($tempDir)) mkdir($tempDir, 0755, true);
                $tempName = 'catalogue_' . $userId . '_' . time() . '.pdf';
                $uploadedFile->move($tempDir, $tempName);
                $pdfPath = $tempDir . DIRECTORY_SEPARATOR . $tempName;

                $fileSizeMB = round(filesize($pdfPath) / 1024 / 1024, 2);

                // Try text extraction first for smaller PDFs
                if ($fileSizeMB <= 10) {
                    $content = $catalogueAI->extractTextFromPDF($pdfPath);
                }

                // If text extraction failed or quality is poor → use multimodal vision
                if (empty(trim($content))) {
                    Log::info('SetupWizard: Using multimodal PDF analysis (image-based PDF)');

                    AiSetting::updateOrCreate(
                        ['key_name' => 'setup_wizard_pdf_path_' . $userId],
                        ['key_value' => $pdfPath]
                    );
                    AiSetting::updateOrCreate(
                        ['key_name' => 'setup_wizard_source_mode_' . $userId],
                        ['key_value' => 'pdf_multimodal']
                    );

                    $analysis = $catalogueAI->analyzeCatalogueFromPDF($pdfPath);

                    AiSetting::updateOrCreate(
                        ['key_name' => 'setup_wizard_columns_' . $userId],
                        ['key_value' => json_encode($analysis['columns'])]
                    );

                    return response()->json([
                        'success' => true,
                        'columns' => $analysis['columns'],
                        'confidence' => $analysis['confidence'],
                        'source_summary' => $analysis['source_summary'],
                        'business_details' => $analysis['business_details'],
                        'message' => 'Catalogue analyzed successfully! ' . count($analysis['columns']) . ' columns identified. (AI Vision)',
                    ]);
                }
            } else {
                $content = $catalogueAI->scrapeWebsite($request->website_url);
            }

            if (empty(trim($content))) {
                return response()->json([
                    'success' => false,
                    'message' => 'Could not extract any text content from the provided source.',
                ], 422);
            }

            // Save text and mode for product extraction step
            AiSetting::updateOrCreate(
                ['key_name' => 'setup_wizard_text_' . $userId],
                ['key_value' => substr($content, 0, 60000)]
            );
            AiSetting::updateOrCreate(
                ['key_name' => 'setup_wizard_source_mode_' . $userId],
                ['key_value' => 'text']
            );
            if ($pdfPath) {
                AiSetting::updateOrCreate(
                    ['key_name' => 'setup_wizard_pdf_path_' . $userId],
                    ['key_value' => $pdfPath]
                );
            }

            $analysis = $catalogueAI->analyzeCatalogueSource($content, $sourceType);

            AiSetting::updateOrCreate(
                ['key_name' => 'setup_wizard_columns_' . $userId],
                ['key_value' => json_encode($analysis['columns'])]
            );

            return response()->json([
                'success' => true,
                'columns' => $analysis['columns'],
                'confidence' => $analysis['confidence'],
                'source_summary' => $analysis['source_summary'],
                'business_details' => $analysis['business_details'],
                'message' => 'Catalogue analyzed successfully! ' . count($analysis['columns']) . ' columns identified.',
            ]);

        } catch (\RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (\Error $e) {
            Log::error('SetupWizard: Fatal error', ['message' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Server ran out of memory. Please try a smaller file.'], 500);
        } catch (\Exception $e) {
            Log::error('Catalogue analysis failed', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Analysis failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Import columns to database (Step 2).
     */
    public function importColumns(Request $request)
    {
        $userId = Auth::id();
        $columns = $request->input('columns', []);

        if (empty($columns)) {
            return response()->json(['success' => false, 'message' => 'No columns provided.'], 422);
        }

        $importService = new CatalogueColumnImportService($userId);
        $result = $importService->importFromArray($columns);

        return response()->json([
            'success' => true,
            'created' => $result['created'],
            'skipped' => $result['skipped'],
            'categories_created' => $result['categories_created'],
            'message' => "{$result['created']} columns created successfully!",
        ]);
    }

    /**
     * Extract products from catalogue (Step 3).
     */
    public function extractProducts(Request $request)
    {
        $userId = Auth::id();
        $catalogueAI = new CatalogueAIService();

        ini_set('memory_limit', '1G');
        set_time_limit(3600);

        // Get columns from database
        $columns = CatalogueCustomColumn::where('user_id', $userId)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->map(function ($col) {
                return [
                    'name' => $col->name,
                    'type' => $col->type,
                    'is_unique' => $col->is_unique,
                    'is_required' => $col->is_required,
                    'is_category' => $col->is_category,
                    'is_title' => $col->is_title,
                    'is_combo' => $col->is_combo,
                    'is_variation_field' => $col->is_variation_field,
                    'options' => $col->options,
                ];
            })
            ->toArray();

        if (empty($columns)) {
            return response()->json(['success' => false, 'message' => 'No columns defined. Complete Step 2 first.'], 422);
        }

        $sourceMode = AiSetting::get('setup_wizard_source_mode_' . $userId) ?? 'text';

        try {
            if ($sourceMode === 'pdf_multimodal') {
                // Use chunked PDF extraction (multimodal vision)
                $pdfPath = AiSetting::get('setup_wizard_pdf_path_' . $userId);
                if (!$pdfPath || !file_exists($pdfPath)) {
                    return response()->json(['success' => false, 'message' => 'PDF file not found. Please re-upload in Step 1.'], 404);
                }

                $result = $catalogueAI->extractProductDataFromPDF($pdfPath, $columns);
            } else {
                // Use text-based extraction
                $content = AiSetting::get('setup_wizard_text_' . $userId) ?? '';
                if (empty($content)) {
                    return response()->json(['success' => false, 'message' => 'No catalogue data found. Please re-upload.'], 422);
                }
                $result = $catalogueAI->extractProductData($content, $columns);
            }

            // Cache extracted products
            AiSetting::updateOrCreate(
                ['key_name' => 'setup_wizard_products_' . $userId],
                ['key_value' => json_encode($result['products'])]
            );

            return response()->json([
                'success' => true,
                'products' => $result['products'],
                'total' => $result['total'],
                'message' => "Extracted {$result['total']} products!",
            ]);
        } catch (\Exception $e) {
            Log::error('Product extraction failed', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Extraction failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Import extracted products to database (Step 3 action).
     */
    public function importProducts(Request $request)
    {
        $userId = Auth::id();
        $products = json_decode(
            AiSetting::get('setup_wizard_products_' . $userId) ?? '[]', true
        );

        if (empty($products)) {
            return response()->json(['success' => false, 'message' => 'No products to import.'], 422);
        }

        $columns = CatalogueCustomColumn::where('user_id', $userId)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        $categoryCol = $columns->firstWhere('is_category', true);
        $uniqueCol = $columns->firstWhere('is_unique', true);
        $titleCol = $columns->firstWhere('is_title', true);
        $comboCols = $columns->where('is_combo', true);

        $created = 0;
        $skipped = 0;

        foreach ($products as $productData) {
            try {
                // 1. Resolve category name
                $categoryName = null;
                if ($categoryCol) {
                    $categoryName = $productData[$categoryCol->name] ?? null;
                }

                // 2. Determine product name
                $name = 'Product ' . ($created + 1);
                if ($titleCol) {
                    $name = $productData[$titleCol->name] ?? $name;
                } elseif ($uniqueCol) {
                    $name = $productData[$uniqueCol->name] ?? $name;
                }

                // 3. Check for duplicate by unique column
                if ($uniqueCol) {
                    $uniqueVal = $productData[$uniqueCol->name] ?? '';
                    if (!empty($uniqueVal)) {
                        $existing = Product::where('user_id', $userId)
                            ->whereHas('customValues', function ($q) use ($uniqueCol, $uniqueVal) {
                                $q->where('column_id', $uniqueCol->id)->where('value', $uniqueVal);
                            })->first();
                        if ($existing) {
                            $skipped++;
                            continue;
                        }
                    }
                }

                // 4. Create product
                $product = Product::create([
                    'user_id' => $userId,
                    'title' => $name,
                    'category_name' => $categoryName,
                    'status' => 1,
                ]);

                // 5. Save custom values
                foreach ($columns as $col) {
                    if ($col->is_category) continue;
                    $value = $productData[$col->name] ?? '';
                    if (empty($value)) continue;

                    CatalogueCustomValue::create([
                        'product_id' => $product->id,
                        'column_id' => $col->id,
                        'value' => is_array($value) ? json_encode($value) : $value,
                    ]);
                }

                // 6. Save combos (pipe-separated values from AI)
                foreach ($comboCols as $combo) {
                    $comboValue = $productData[$combo->name] ?? '';
                    if (empty($comboValue)) continue;
                    $values = array_map('trim', explode('|', $comboValue));
                    $values = array_filter($values);
                    if (!empty($values)) {
                        ProductCombo::create([
                            'product_id' => $product->id,
                            'column_id' => $combo->id,
                            'selected_values' => array_values($values),
                        ]);
                    }
                }

                $created++;
            } catch (\Exception $e) {
                $skipped++;
                Log::warning('Product import error', ['error' => $e->getMessage()]);
            }
        }

        return response()->json([
            'success' => true,
            'created' => $created,
            'skipped' => $skipped,
            'message' => "{$created} products imported successfully!",
        ]);
    }

    /**
     * Complete setup wizard.
     */
    public function completeSetup(Request $request)
    {
        return response()->json(['success' => true, 'message' => 'Setup complete! 🎉']);
    }

    /**
     * Reset wizard — clear cached AI data.
     */
    public function resetSetup(Request $request)
    {
        $userId = Auth::id();
        AiSetting::where('key_name', 'setup_wizard_columns_' . $userId)->delete();
        AiSetting::where('key_name', 'setup_wizard_text_' . $userId)->delete();
        AiSetting::where('key_name', 'setup_wizard_products_' . $userId)->delete();

        return response()->json(['success' => true, 'message' => 'Wizard data reset.']);
    }

    // ─────────────────────────────────────────────────────────────
    //  CATALOGUE COLUMNS MANAGER
    // ─────────────────────────────────────────────────────────────

    /**
     * Show Catalogue Columns manager page.
     */
    public function columnsIndex()
    {
        $data = $this->getCommonData();
        $userId = Auth::id();

        $columns = CatalogueCustomColumn::where('user_id', $userId)
            ->orderBy('sort_order')
            ->get();

        return view('client.catalogue_columns', array_merge($data, [
            'columns' => $columns,
        ]));
    }

    /**
     * Create a new custom column.
     */
    public function columnStore(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'type' => 'required|in:text,textarea,number,select,multiselect,boolean',
        ]);

        $userId = Auth::id();
        $slug = Str::slug($request->name, '_');

        // Check duplicate
        $exists = CatalogueCustomColumn::where('user_id', $userId)->where('slug', $slug)->exists();
        if ($exists) {
            return response()->json(['success' => false, 'message' => 'A column with this name already exists.'], 422);
        }

        $maxSort = CatalogueCustomColumn::where('user_id', $userId)->max('sort_order') ?? 0;

        // Parse options
        $options = null;
        if (in_array($request->type, ['select', 'multiselect']) && !empty($request->options)) {
            $options = array_values(array_filter(array_map('trim', explode(',', $request->options))));
        }

        $column = CatalogueCustomColumn::create([
            'user_id' => $userId,
            'name' => $request->name,
            'slug' => $slug,
            'type' => $request->type,
            'options' => $options,
            'is_required' => (bool)$request->is_required,
            'is_unique' => (bool)$request->is_unique,
            'is_category' => (bool)$request->is_category,
            'is_title' => (bool)$request->is_title,
            'is_combo' => (bool)$request->is_combo,
            'is_variation_field' => (bool)$request->is_variation_field,
            'show_on_list' => (bool)$request->show_on_list,
            'show_in_ai' => $request->has('show_in_ai') ? (bool)$request->show_in_ai : true,
            'sort_order' => $maxSort + 1,
        ]);

        // Enforce exclusivity: if this is_category, unset others
        if ($column->is_category) {
            CatalogueCustomColumn::where('user_id', $userId)
                ->where('id', '!=', $column->id)
                ->where('is_category', true)
                ->update(['is_category' => false]);
        }
        if ($column->is_unique) {
            CatalogueCustomColumn::where('user_id', $userId)
                ->where('id', '!=', $column->id)
                ->where('is_unique', true)
                ->update(['is_unique' => false]);
        }
        if ($column->is_title) {
            CatalogueCustomColumn::where('user_id', $userId)
                ->where('id', '!=', $column->id)
                ->where('is_title', true)
                ->update(['is_title' => false]);
        }

        return response()->json([
            'success' => true,
            'column' => $column,
            'message' => 'Column created!',
        ]);
    }

    /**
     * Update a custom column.
     */
    public function columnUpdate(Request $request, $id)
    {
        $userId = Auth::id();
        $column = CatalogueCustomColumn::where('user_id', $userId)->findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:100',
            'type' => 'required|in:text,textarea,number,select,multiselect,boolean',
        ]);

        // Parse options
        $options = null;
        if (in_array($request->type, ['select', 'multiselect']) && !empty($request->options)) {
            if (is_array($request->options)) {
                $options = $request->options;
            } else {
                $options = array_values(array_filter(array_map('trim', explode(',', $request->options))));
            }
        }

        $updateData = [
            'name' => $request->name,
            'type' => $request->type,
            'options' => $options,
            'is_required' => (bool)$request->is_required,
            'show_on_list' => (bool)$request->show_on_list,
            'show_in_ai' => $request->has('show_in_ai') ? (bool)$request->show_in_ai : true,
        ];

        // System fields: only name/visibility editable
        if (!$column->is_system) {
            $updateData['is_unique'] = (bool)$request->is_unique;
            $updateData['is_category'] = (bool)$request->is_category;
            $updateData['is_title'] = (bool)$request->is_title;
            $updateData['is_combo'] = (bool)$request->is_combo;
            $updateData['is_variation_field'] = (bool)$request->is_variation_field;
        }

        $column->update($updateData);

        // Enforce exclusivity
        if ($column->is_category) {
            CatalogueCustomColumn::where('user_id', $userId)
                ->where('id', '!=', $column->id)
                ->where('is_category', true)
                ->update(['is_category' => false]);
        }
        if ($column->is_unique) {
            CatalogueCustomColumn::where('user_id', $userId)
                ->where('id', '!=', $column->id)
                ->where('is_unique', true)
                ->update(['is_unique' => false]);
        }
        if ($column->is_title) {
            CatalogueCustomColumn::where('user_id', $userId)
                ->where('id', '!=', $column->id)
                ->where('is_title', true)
                ->update(['is_title' => false]);
        }

        return response()->json([
            'success' => true,
            'column' => $column->fresh(),
            'message' => 'Column updated!',
        ]);
    }

    /**
     * Delete a custom column.
     */
    public function columnDestroy($id)
    {
        $userId = Auth::id();
        $column = CatalogueCustomColumn::where('user_id', $userId)->findOrFail($id);

        if ($column->is_system) {
            return response()->json(['success' => false, 'message' => 'System fields cannot be deleted.'], 422);
        }

        // Cascade: values and combos will be deleted via FK constraints
        $column->forceDelete();

        return response()->json(['success' => true, 'message' => 'Column deleted!']);
    }

    /**
     * Save drag-and-drop reorder.
     */
    public function columnReorder(Request $request)
    {
        $userId = Auth::id();
        $order = $request->input('order', []);

        foreach ($order as $index => $id) {
            CatalogueCustomColumn::where('user_id', $userId)
                ->where('id', $id)
                ->update(['sort_order' => $index + 1]);
        }

        return response()->json(['success' => true, 'message' => 'Order saved!']);
    }

    /**
     * Toggle column visibility.
     */
    public function columnToggle($id)
    {
        $userId = Auth::id();
        $column = CatalogueCustomColumn::where('user_id', $userId)->findOrFail($id);
        $column->update(['is_active' => !$column->is_active]);

        return response()->json([
            'success' => true,
            'is_active' => $column->is_active,
            'message' => $column->is_active ? 'Column visible' : 'Column hidden',
        ]);
    }

    // ─────────────────────────────────────────────────────────────
    //  PRODUCTS
    // ─────────────────────────────────────────────────────────────

    /**
     * Show Products listing page.
     */
    public function productsIndex(Request $request)
    {
        $data = $this->getCommonData();
        $userId = Auth::id();

        $customColumns = CatalogueCustomColumn::where('user_id', $userId)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        $query = Product::where('user_id', $userId)
            ->with(['customValues', 'combos.column', 'variations']);

        // Category filter
        if ($request->category) {
            $query->where('category_name', $request->category);
        }

        // Search
        if ($request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('category_name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%")
                  ->orWhereHas('customValues', function ($q2) use ($search) {
                      $q2->where('value', 'like', "%{$search}%");
                  });
            });
        }

        $products = $query->orderBy('id', 'desc')->paginate(25);

        // Get unique categories for filter pills
        $categories = Product::where('user_id', $userId)
            ->whereNotNull('category_name')
            ->distinct()
            ->pluck('category_name')
            ->sort()
            ->values();

        return view('client.products', array_merge($data, [
            'products' => $products,
            'categories' => $categories,
            'customColumns' => $customColumns,
        ]));
    }

    /**
     * Create a new product.
     */
    public function productStore(Request $request)
    {
        $userId = Auth::id();
        $columns = CatalogueCustomColumn::where('user_id', $userId)
            ->where('is_active', true)->get();

        $request->validate([
            'title' => 'required|string|max:255',
            'product_image' => 'nullable|image|max:4096', // 4MB max
        ]);

        // Handle image upload → compress to WebP
        $imagePath = null;
        if ($request->hasFile('product_image')) {
            $imagePath = $this->processProductImage($request->file('product_image'), $userId);
        }

        $product = Product::create([
            'user_id' => $userId,
            'title' => $request->title,
            'image' => $imagePath,
            'category_name' => $request->category_name,
            'sku' => $request->sku,
            'description' => $request->description,
            'unit' => $request->unit ?? 'pcs',
            'mrp' => ($request->mrp ?? 0) * 100,
            'sale_price' => ($request->sale_price ?? 0) * 100,
            'gst_percent' => $request->gst_percent ?? 0,
            'hsn_code' => $request->hsn_code,
            'status' => 1,
        ]);

        // Save custom values
        foreach ($request->input('custom_data', []) as $colId => $value) {
            if ($value === null || $value === '') continue;
            CatalogueCustomValue::create([
                'product_id' => $product->id,
                'column_id' => $colId,
                'value' => is_array($value) ? json_encode($value) : $value,
            ]);
        }

        // Save combo selections
        foreach ($request->input('combo_data', []) as $colId => $values) {
            if (empty($values)) continue;
            ProductCombo::create([
                'product_id' => $product->id,
                'column_id' => $colId,
                'selected_values' => is_array($values) ? $values : explode(',', $values),
            ]);
        }

        // Save variations
        foreach ($request->input('variations', []) as $variation) {
            if (empty($variation['combination'])) continue;
            ProductVariation::create([
                'product_id' => $product->id,
                'combination' => $variation['combination'],
                'combination_key' => ProductVariation::generateKey($variation['combination']),
                'price' => ($variation['price'] ?? 0) * 100,
                'discount' => $variation['discount'] ?? 0,
            ]);
        }

        return response()->json([
            'success' => true,
            'product' => $product->load('customValues', 'combos', 'variations'),
            'message' => 'Product created!',
        ]);
    }

    /**
     * Update a product.
     */
    public function productUpdate(Request $request, $id)
    {
        $userId = Auth::id();
        $product = Product::where('user_id', $userId)->findOrFail($id);

        // Handle image upload → compress to WebP
        $imagePath = $product->image;
        if ($request->hasFile('product_image')) {
            $request->validate([
                'product_image' => 'image|max:4096', // 4MB max
            ]);
            // Delete old image if exists
            if ($product->image && file_exists(public_path('uploads/' . $product->image))) {
                @unlink(public_path('uploads/' . $product->image));
            }
            $imagePath = $this->processProductImage($request->file('product_image'), $userId);
        }
        // Handle image removal
        if ($request->input('remove_image') == '1') {
            if ($product->image && file_exists(public_path('uploads/' . $product->image))) {
                @unlink(public_path('uploads/' . $product->image));
            }
            $imagePath = null;
        }

        $product->update([
            'title' => $request->title ?? $product->title,
            'image' => $imagePath,
            'category_name' => $request->category_name ?? $product->category_name,
            'sku' => $request->sku ?? $product->sku,
            'description' => $request->description ?? $product->description,
            'unit' => $request->unit ?? $product->unit,
            'mrp' => $request->has('mrp') ? ($request->mrp * 100) : $product->mrp,
            'sale_price' => $request->has('sale_price') ? ($request->sale_price * 100) : $product->sale_price,
            'gst_percent' => $request->gst_percent ?? $product->gst_percent,
            'hsn_code' => $request->hsn_code ?? $product->hsn_code,
        ]);

        // Update custom values
        foreach ($request->input('custom_data', []) as $colId => $value) {
            CatalogueCustomValue::updateOrCreate(
                ['product_id' => $product->id, 'column_id' => $colId],
                ['value' => is_array($value) ? json_encode($value) : $value]
            );
        }

        // Update combos
        if ($request->has('combo_data')) {
            ProductCombo::where('product_id', $product->id)->delete();
            foreach ($request->input('combo_data', []) as $colId => $values) {
                if (empty($values)) continue;
                ProductCombo::create([
                    'product_id' => $product->id,
                    'column_id' => $colId,
                    'selected_values' => is_array($values) ? $values : explode(',', $values),
                ]);
            }
        }

        // Update variations
        if ($request->has('variations')) {
            ProductVariation::where('product_id', $product->id)->delete();
            foreach ($request->input('variations', []) as $variation) {
                if (empty($variation['combination'])) continue;
                ProductVariation::create([
                    'product_id' => $product->id,
                    'combination' => $variation['combination'],
                    'combination_key' => ProductVariation::generateKey($variation['combination']),
                    'price' => ($variation['price'] ?? 0) * 100,
                    'discount' => $variation['discount'] ?? 0,
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'product' => $product->fresh()->load('customValues', 'combos', 'variations'),
            'message' => 'Product updated!',
        ]);
    }

    /**
     * Delete a product.
     */
    public function productDestroy($id)
    {
        $userId = Auth::id();
        $product = Product::where('user_id', $userId)->findOrFail($id);
        $product->delete(); // FK cascades will remove values, combos, variations

        return response()->json(['success' => true, 'message' => 'Product deleted!']);
    }

    /**
     * Process uploaded product image: compress and convert to WebP.
     * Returns the relative path (e.g., 'product_images/abc123.webp').
     */
    private function processProductImage($file, $userId)
    {
        $uploadDir = public_path('uploads/product_images');
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $filename = 'prod_' . $userId . '_' . time() . '_' . Str::random(6) . '.webp';
        $destination = $uploadDir . DIRECTORY_SEPARATOR . $filename;

        // Read image and convert to WebP with compression
        $sourceImage = null;
        $mime = $file->getMimeType();

        switch ($mime) {
            case 'image/jpeg':
            case 'image/jpg':
                $sourceImage = @imagecreatefromjpeg($file->getPathname());
                break;
            case 'image/png':
                $sourceImage = @imagecreatefrompng($file->getPathname());
                if ($sourceImage) {
                    imagealphablending($sourceImage, true);
                    imagesavealpha($sourceImage, true);
                }
                break;
            case 'image/webp':
                $sourceImage = @imagecreatefromwebp($file->getPathname());
                break;
            case 'image/gif':
                $sourceImage = @imagecreatefromgif($file->getPathname());
                break;
            default:
                // Fallback: try GD auto-detect
                $sourceImage = @imagecreatefromstring(file_get_contents($file->getPathname()));
                break;
        }

        if (!$sourceImage) {
            // Fallback: save original file as-is
            $fallbackName = 'prod_' . $userId . '_' . time() . '.' . $file->getClientOriginalExtension();
            $file->move($uploadDir, $fallbackName);
            return 'product_images/' . $fallbackName;
        }

        // Resize if too large (max 1200px on longest side for social media)
        $origW = imagesx($sourceImage);
        $origH = imagesy($sourceImage);
        $maxDimension = 1200;

        if ($origW > $maxDimension || $origH > $maxDimension) {
            $ratio = min($maxDimension / $origW, $maxDimension / $origH);
            $newW = (int) round($origW * $ratio);
            $newH = (int) round($origH * $ratio);

            $resized = imagecreatetruecolor($newW, $newH);
            imagealphablending($resized, false);
            imagesavealpha($resized, true);
            imagecopyresampled($resized, $sourceImage, 0, 0, 0, 0, $newW, $newH, $origW, $origH);
            imagedestroy($sourceImage);
            $sourceImage = $resized;
        }

        // Save as WebP with 80% quality (good balance of size vs quality)
        imagewebp($sourceImage, $destination, 80);
        imagedestroy($sourceImage);

        return 'product_images/' . $filename;
    }
}
