<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\CatalogueCustomColumn;
use App\Services\CatalogueAIService;
use App\Services\CatalogueColumnImportService;
use App\Services\ProductExcelService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class SetupWizardApiController extends Controller
{
    public function status(Request $request)
    {
        $userId = $request->input('userId');

        if (!$userId) {
            return response()->json(['success' => false, 'message' => 'User ID required'], 400);
        }

        $isCompleted = Setting::getValue('setup_tour', 'completed', false, $userId);
        $existingColumns = CatalogueCustomColumn::where('user_id', $userId)->count();

        $cachedColumns = Setting::getValue('setup_tour', 'ai_columns_json', null, $userId);
        if (is_string($cachedColumns))
            $cachedColumns = json_decode($cachedColumns, true);

        $tourConfig = [
            'welcome_title' => Setting::getGlobalValue('setup_tour', 'welcome_title', 'Welcome to VyaparCRM! 🚀'),
            'welcome_subtitle' => Setting::getGlobalValue('setup_tour', 'welcome_subtitle', 'Let\'s set up your product catalogue in minutes using AI'),
            'intro_message' => Setting::getGlobalValue('setup_tour', 'intro_message', 'Upload your product catalogue PDF or share your website URL — our AI will automatically analyze your products and create the perfect database structure for you.'),
        ];

        $isConfigured = (new \App\Services\VertexAIService($userId))->isConfigured();

        $business = \App\Models\Business::where('user_id', $userId)->where('is_default', 1)->first();
        if (!$business) {
            $business = \App\Models\Business::where('user_id', $userId)->first();
        }

        return response()->json([
            'success' => true,
            'isCompleted' => $isCompleted,
            'existingColumns' => $existingColumns,
            'cachedColumns' => $cachedColumns,
            'tourConfig' => $tourConfig,
            'isConfigured' => $isConfigured,
            'business_id' => $business ? $business->id : null,
        ]);
    }

    public function analyze(Request $request)
    {
        $request->validate([
            'source_type' => 'required|in:pdf,website',
            'catalogue_pdf' => 'required_if:source_type,pdf|file|mimes:pdf|max:51200',
            'website_url' => 'required_if:source_type,website|nullable|url',
        ]);

        $userId = $request->input('userId');
        $service = new CatalogueAIService($userId);

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
                if (!is_dir($tempDir))
                    mkdir($tempDir, 0755, true);
                $tempName = 'catalogue_' . $userId . '_' . time() . '.pdf';
                $uploadedFile->move($tempDir, $tempName);
                $pdfPath = $tempDir . DIRECTORY_SEPARATOR . $tempName;

                $fakeUpload = new \Illuminate\Http\UploadedFile($pdfPath, $originalName, 'application/pdf', null, true);
                $fileSizeMB = round(filesize($pdfPath) / 1024 / 1024, 2);
                $content = '';

                // Extract text directly from the PDF if possible (this is super fast)
                // We no longer skip text extraction for large PDFs because we only need the schema.
                // The AI service internally chunks the PDF to 3 pages anyway for text extraction.
                $content = $service->extractTextFromPDF($fakeUpload);

                if (empty(trim($content))) {
                    Log::info('SetupWizard: Using multimodal PDF analysis');

                    Setting::setValue('setup_tour', 'last_pdf_path', $pdfPath, $userId);
                    Setting::setValue('setup_tour', 'last_source_text', '', $userId);
                    Setting::setValue('setup_tour', 'source_mode', 'pdf_multimodal', $userId);

                    $analysis = $service->analyzeCatalogueFromPDF($pdfPath);

                    Setting::setValue('setup_tour', 'ai_columns_json', json_encode($analysis['columns']), $userId);

                    $businessDetails = $analysis['business_details'] ?? null;
                    if (!empty($businessDetails)) {
                        Setting::setValue('ai_bot', 'business_prompt', $businessDetails, $userId);
                    }

                    return response()->json([
                        'success' => true,
                        'columns' => $analysis['columns'],
                        'source_summary' => $analysis['source_summary'],
                        'confidence' => $analysis['confidence'],
                        'ai_tokens' => $analysis['ai_tokens'],
                        'business_details' => $businessDetails,
                        'message' => 'Catalogue analyzed successfully! ' . count($analysis['columns']) . ' columns identified. (Used AI vision for image-based PDF)',
                    ]);
                }
            } else {
                $content = $service->scrapeWebsite($request->website_url);
            }

            if (empty(trim($content))) {
                return response()->json([
                    'success' => false,
                    'message' => 'Could not extract any text content from the provided source. Please try a different file or URL.'
                ], 422);
            }

            Setting::setValue('setup_tour', 'last_source_text', $content, $userId);
            Setting::setValue('setup_tour', 'source_mode', 'text', $userId);
            if ($pdfPath) {
                Setting::setValue('setup_tour', 'last_pdf_path', $pdfPath, $userId);
            }

            $analysis = $service->analyzeCatalogueSource($content, $sourceType);

            Setting::setValue('setup_tour', 'ai_columns_json', json_encode($analysis['columns']), $userId);

            $businessDetails = $analysis['business_details'] ?? null;
            if (!empty($businessDetails)) {
                Setting::setValue('ai_bot', 'business_prompt', $businessDetails, $userId);
            }

            return response()->json([
                'success' => true,
                'columns' => $analysis['columns'],
                'source_summary' => $analysis['source_summary'],
                'confidence' => $analysis['confidence'],
                'ai_tokens' => $analysis['ai_tokens'],
                'business_details' => $businessDetails,
                'message' => 'Catalogue analyzed successfully! ' . count($analysis['columns']) . ' columns identified.',
            ]);

        } catch (\RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (\Error $e) {
            Log::error('SetupWizard: Fatal error', ['message' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Server ran out of memory. Please try a smaller file.'], 500);
        } catch (\Exception $e) {
            \Log::error('DIAGNOSIS: Image extraction failed Exception', [
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ]);
            return response()->json([
                'success' => false, 
                'message' => 'Extraction failed: ' . $e->getMessage(),
                'diagnosis' => 'Exception at ' . basename($e->getFile()) . ':' . $e->getLine()
            ], 500);
        }
    }

    public function downloadColumnsExcel(Request $request)
    {
        $userId = $request->input('userId');
        $columnsJson = Setting::getValue('setup_tour', 'ai_columns_json', null, $userId);

        if (!$columnsJson) {
            return response()->json(['error' => 'No analysis found. Please run Step 1 first.'], 404);
        }

        $columns = is_string($columnsJson) ? json_decode($columnsJson, true) : $columnsJson;

        $service = new CatalogueAIService($userId);
        $spreadsheet = $service->generateColumnsExcel($columns);

        $writer = new Xlsx($spreadsheet);
        $fileName = 'catalogue_columns_' . date('Y-m-d_His') . '.xlsx';

        $tempDir = storage_path('app/temp');
        if (!is_dir($tempDir))
            mkdir($tempDir, 0755, true);
        $tempPath = $tempDir . '/' . $fileName;
        $writer->save($tempPath);

        return response()->download($tempPath, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    public function importColumns(Request $request)
    {
        $userId = $request->input('userId');
        if (!$userId) {
            return response()->json(['success' => false, 'message' => 'User ID is required.'], 400);
        }

        $importService = new CatalogueColumnImportService((int) $userId);

        $importType = $request->input('import_type', 'direct');

        try {
            if ($importType === 'excel') {
                $request->validate(['file' => 'required|file|mimes:xlsx,xls|max:10240']);

                $file = $request->file('file');
                $tempName = 'col_import_' . $request->input('userId') . '_' . time() . '.' . $file->getClientOriginalExtension();
                $tempDir = storage_path('app/temp');
                if (!is_dir($tempDir))
                    mkdir($tempDir, 0755, true);
                $file->move($tempDir, $tempName);
                $filePath = $tempDir . '/' . $tempName;

                $result = $importService->importFromExcel($filePath);
                @unlink($filePath);

            } else {
                $columns = $request->input('columns');
                if (!empty($columns) && is_array($columns)) {
                    Setting::setValue('setup_tour', 'ai_columns_json', json_encode($columns), $userId);
                } else {
                    $columnsJson = Setting::getValue('setup_tour', 'ai_columns_json', null, $userId);
                    if (!$columnsJson) {
                        return response()->json(['success' => false, 'message' => 'No column analysis cached. Please run Step 1 first.'], 404);
                    }
                    $columns = is_string($columnsJson) ? json_decode($columnsJson, true) : $columnsJson;
                }
                $result = $importService->importFromArray($columns);
            }

            if (class_exists('\\App\\Services\\AIChatbotService')) {
                \App\Services\AIChatbotService::clearProductGroupCache($userId);
            }

            return response()->json([
                'success' => true,
                'created' => $result['created'],
                'skipped' => $result['skipped'],
                'errors' => $result['errors'],
                'categories_created' => $result['categories_created'],
                'message' => "{$result['created']} columns created" .
                    (count($result['categories_created']) > 0 ? ", " . count($result['categories_created']) . " categories auto-created" : '') .
                    ($result['skipped'] > 0 ? ", {$result['skipped']} skipped (already exist)" : '') . '.',
            ]);

        } catch (\Exception $e) {
            Log::error('SetupWizard: Column import failed', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function extractProducts(Request $request)
    {
        $userId = $request->input('userId');
        if (!$userId) {
            return response()->json(['success' => false, 'message' => 'User ID is required.'], 400);
        }

        ini_set('memory_limit', '1G');
        set_time_limit(3600);

        $sourceMode = Setting::getValue('setup_tour', 'source_mode', 'text', $userId);
        $columnsJson = Setting::getValue('setup_tour', 'ai_columns_json', null, $userId);
        $columns = is_string($columnsJson) ? json_decode($columnsJson, true) : ($columnsJson ?? []);

        if (empty($columns)) {
            return response()->json(['success' => false, 'message' => 'No columns defined. Please complete Step 2 first.'], 422);
        }

        try {
            $service = new CatalogueAIService($userId);

            if ($sourceMode === 'pdf_multimodal') {
                $pdfPath = Setting::getValue('setup_tour', 'last_pdf_path', null, $userId);

                if (!$pdfPath || !file_exists($pdfPath)) {
                    return response()->json(['success' => false, 'message' => 'PDF file not found. Please re-upload in Step 1.'], 404);
                }

                $result = $service->extractProductDataFromPDF($pdfPath, $columns);
            } else {
                $content = Setting::getValue('setup_tour', 'last_source_text', null, $userId);
                if (!$content) {
                    return response()->json(['success' => false, 'message' => 'No catalogue source found. Please re-run Step 1.'], 404);
                }
                $result = $service->extractProductData($content, $columns);
            }

            Setting::setValue('setup_tour', 'ai_products_json', json_encode($result['products']), $userId);

            return response()->json([
                'success' => true,
                'products' => $result['products'],
                'total' => $result['total'],
                'ai_tokens' => $result['ai_tokens'],
                'message' => "{$result['total']} products extracted from your catalogue!",
            ]);

        } catch (\Exception $e) {
            Log::error('SetupWizard: Product extraction failed', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function importProducts(Request $request)
    {
        $userId = $request->input('userId');
        if (!$userId) {
            return response()->json(['success' => false, 'message' => 'User ID is required.'], 400);
        }

        $productsJson = Setting::getValue('setup_tour', 'ai_products_json', null, $userId);
        $columnsJson = Setting::getValue('setup_tour', 'ai_columns_json', null, $userId);

        if (!$productsJson || !$columnsJson) {
            return response()->json(['success' => false, 'message' => 'No product data found. Please run Step 3 extraction first.'], 404);
        }

        $products = is_string($productsJson) ? json_decode($productsJson, true) : $productsJson;
        $aiColumns = is_string($columnsJson) ? json_decode($columnsJson, true) : $columnsJson;

        if (empty($products)) {
            return response()->json(['success' => false, 'message' => 'No products to import.'], 422);
        }

        $dbColumns = CatalogueCustomColumn::where('user_id', $userId)
            ->where('is_active', true)->orderBy('sort_order')->get();

        $colMap = [];
        foreach ($dbColumns as $dbCol) {
            $colMap[mb_strtolower(trim($dbCol->name))] = $dbCol;
        }

        $categories = \App\Models\ProductCategory::all();
        $catMap = [];
        foreach ($categories as $cat) {
            $catMap[mb_strtolower(trim($cat->name))] = $cat;
        }

        $created = 0;
        $skipped = 0;
        $errors = [];
        $createdCategories = [];

        try {
            foreach ($products as $idx => $product) {
                $systemData = [
                    'user_id' => $userId,
                    'status' => 1,
                ];
                $customData = [];
                $comboData = [];
                $categoryId = null;
                $productName = 'Product ' . ($idx + 1);

                foreach ($product as $colName => $value) {
                    $value = is_string($value) ? trim($value) : $value;
                    if ($value === '' || $value === null)
                        continue;

                    $key = mb_strtolower(trim($colName));
                    $dbCol = $colMap[$key] ?? null;
                    if (!$dbCol)
                        continue;

                    if ($dbCol->is_category) {
                        $catKey = mb_strtolower(trim($value));
                        if (isset($catMap[$catKey])) {
                            $categoryId = $catMap[$catKey]->id;
                        } else {
                            $newCat = \App\Models\ProductCategory::create([
                                'name' => trim($value),
                                'status' => '1',
                            ]);
                            $catMap[$catKey] = $newCat;
                            $categoryId = $newCat->id;
                            $createdCategories[] = trim($value);
                        }
                    }

                    if ($dbCol->is_title) {
                        $productName = $value;
                    }

                    if ($dbCol->is_combo) {
                        $comboValues = array_filter(array_map('trim', explode('|', $value)), fn($v) => $v !== '');
                        if (count($comboValues) > 0) {
                            $comboData[$dbCol->id] = $comboValues;
                        }
                        continue;
                    }

                    if ($dbCol->is_system) {
                        $slug = $dbCol->slug;
                        if (in_array($slug, ['sale_price', 'mrp'])) {
                            $systemData[$slug] = round((is_numeric($value) ? (float) $value : 0) * 100);
                        } elseif ($slug === 'gst_percent') {
                            $systemData[$slug] = is_numeric($value) ? (int) $value : 0;
                        } else {
                            $systemData[$slug] = $value;
                        }
                    } else {
                        $customData[$dbCol->id] = $value;
                    }
                }

                if ($categoryId)
                    $systemData['category_id'] = $categoryId;
                if (!isset($systemData['title']))
                    $systemData['title'] = $productName;
                if (!isset($systemData['sale_price']))
                    $systemData['sale_price'] = 0;
                if (!isset($systemData['mrp']))
                    $systemData['mrp'] = 0;
                if (!isset($systemData['gst_percent']))
                    $systemData['gst_percent'] = 0;
                if (!isset($systemData['sku']))
                    $systemData['sku'] = 'AUTO-' . strtoupper(uniqid());
                if (!isset($systemData['description']))
                    $systemData['description'] = '';

                try {
                    $uniqueCol = $dbColumns->where('is_unique', true)->first();
                    $existingProduct = null;

                    if ($uniqueCol) {
                        $uniqueValue = $uniqueCol->is_system
                            ? ($systemData[$uniqueCol->slug] ?? null)
                            : ($customData[$uniqueCol->id] ?? null);

                        if ($uniqueValue) {
                            if ($uniqueCol->is_system) {
                                $existingProduct = \App\Models\Product::where('user_id', $userId)
                                    ->where($uniqueCol->slug, $uniqueValue)->first();
                            } else {
                                $ev = \App\Models\CatalogueCustomValue::where('column_id', $uniqueCol->id)
                                    ->where('value', $uniqueValue)
                                    ->whereHas('product', fn($q) => $q->where('user_id', $userId))
                                    ->first();
                                if ($ev)
                                    $existingProduct = $ev->product;
                            }
                        }
                    }

                    if ($existingProduct) {
                        $existingProduct->update($systemData);
                        $product = $existingProduct;
                    } else {
                        $product = \App\Models\Product::create($systemData);
                        $created++;
                    }

                    foreach ($customData as $colId => $val) {
                        \App\Models\CatalogueCustomValue::updateOrCreate(
                            ['product_id' => $product->id, 'column_id' => $colId],
                            ['value' => is_array($val) ? json_encode($val) : $val]
                        );
                    }

                    foreach ($comboData as $colId => $vals) {
                        \App\Models\CatalogueCustomValue::updateOrCreate(
                            ['product_id' => $product->id, 'column_id' => $colId],
                            ['value' => json_encode(array_values($vals))]
                        );
                        \App\Models\ProductCombo::updateOrCreate(
                            ['product_id' => $product->id, 'column_id' => $colId],
                            ['selected_values' => array_values($vals)]
                        );
                    }
                } catch (\Exception $e) {
                    $errors[] = "Product " . ($idx + 1) . ": " . $e->getMessage();
                    $skipped++;
                }
            }

            if (class_exists('\\App\\Services\\AIChatbotService')) {
                \App\Services\AIChatbotService::clearProductGroupCache($userId);
            }

            return response()->json([
                'success' => true,
                'created' => $created,
                'skipped' => $skipped,
                'errors' => $errors,
                'categories_created' => array_unique($createdCategories),
                'message' => "{$created} products imported successfully!" .
                    ($skipped > 0 ? " ({$skipped} skipped)" : '') .
                    (count($createdCategories) > 0 ? " " . count($createdCategories) . " categories auto-created." : ''),
            ]);

        } catch (\Exception $e) {
            Log::error('SetupWizard: Product import failed', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function downloadProductsExcel(Request $request)
    {
        $userId = $request->input('userId');

        $productsJson = Setting::getValue('setup_tour', 'ai_products_json', null, $userId);
        $columnsJson = Setting::getValue('setup_tour', 'ai_columns_json', null, $userId);

        if (!$productsJson || !$columnsJson) {
            return response()->json(['error' => 'No product data found. Please run Step 3 first.'], 404);
        }

        $products = is_string($productsJson) ? json_decode($productsJson, true) : $productsJson;
        $columns = is_string($columnsJson) ? json_decode($columnsJson, true) : $columnsJson;

        $service = new CatalogueAIService($userId);
        $spreadsheet = $service->generateProductsExcel($products, $columns);

        $writer = new Xlsx($spreadsheet);
        $fileName = 'products_import_' . date('Y-m-d_His') . '.xlsx';

        $tempDir = storage_path('app/temp');
        if (!is_dir($tempDir))
            mkdir($tempDir, 0755, true);
        $tempPath = $tempDir . '/' . $fileName;
        $writer->save($tempPath);

        return response()->download($tempPath, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    public function complete(Request $request)
    {
        $userId = $request->input('userId');
        if (!$userId) {
            return response()->json(['success' => false, 'message' => 'User ID is required.'], 400);
        }

        Setting::setValue('setup_tour', 'completed', true, $userId);
        Setting::setValue('setup_tour', 'completed_at', now()->toISOString(), $userId);
        Setting::setValue('setup_tour', 'completed_by', $userId, $userId);

        return response()->json([
            'success' => true,
            'message' => 'Setup wizard completed! Your catalogue is ready.',
        ]);
    }

    public function reset(Request $request)
    {
        $userId = $request->input('userId');
        if (!$userId) {
            return response()->json(['success' => false, 'message' => 'User ID is required.'], 400);
        }

        $pdfPath = Setting::getValue('setup_tour', 'last_pdf_path', null, $userId);
        if ($pdfPath && file_exists($pdfPath))
            @unlink($pdfPath);

        Setting::setValue('setup_tour', 'completed', false, $userId);
        Setting::setValue('setup_tour', 'ai_columns_json', null, $userId);
        Setting::setValue('setup_tour', 'ai_products_json', null, $userId);
        Setting::setValue('setup_tour', 'last_source_text', null, $userId);
        Setting::setValue('setup_tour', 'last_pdf_path', null, $userId);
        Setting::setValue('setup_tour', 'source_mode', null, $userId);

        return response()->json([
            'success' => true,
            'message' => 'Setup wizard reset. You can run it again.',
        ]);
    }

    public function getProducts(Request $request)
    {
        $userId = $request->input('userId');

        if (!$userId) {
            return response()->json(['success' => false, 'message' => 'Missing userId'], 400);
        }

        $customColumns = \App\Models\CatalogueCustomColumn::where('user_id', $userId)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        $products = \App\Models\Product::where('user_id', $userId)
            ->with(['customValues', 'combos.column', 'variations'])
            ->orderBy('updated_at', 'desc')
            ->paginate(50);

        return response()->json([
            'success' => true,
            'products' => $products,
            'customColumns' => $customColumns
        ]);
    }

    public function updateProduct(Request $request, $id)
    {
        $request->validate([
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'title' => ['nullable', 'string', 'max:255'],
            'sku' => ['nullable', 'string', 'max:255'],
        ]);

        $userId = $request->input('userId');
        if (!$userId) {
            return response()->json(['success' => false, 'message' => 'Missing userId'], 400);
        }

        $product = \App\Models\Product::where('user_id', $userId)->find($id);
        if (!$product) {
            return response()->json(['success' => false, 'message' => 'Product not found'], 404);
        }

        $product->update([
            'title' => $request->title ?? $product->title,
            'category_name' => $request->category_name ?? $product->category_name,
            'sku' => $request->sku ?? $product->sku,
            'mrp' => $request->has('mrp') ? (floatval($request->mrp) * 100) : $product->mrp,
            'sale_price' => $request->has('sale_price') ? (floatval($request->sale_price) * 100) : $product->sale_price,
        ]);

        // Handle Image Upload
        Log::info('ProductUpdate: hasFile(image)=' . ($request->hasFile('image') ? 'YES' : 'NO') . ', allFiles=' . json_encode(array_keys($request->allFiles())));
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            Log::info('ProductUpdate: file received', [
                'originalName' => $file->getClientOriginalName(),
                'mimeType' => $file->getMimeType(),
                'size' => $file->getSize(),
            ]);
            
            $fileName = \Illuminate\Support\Str::uuid() . '.' . $file->extension();
            
            // Save directly to public/uploads/products to avoid symlink issues
            $destinationPath = public_path('uploads/products');
            if (!file_exists($destinationPath)) {
                mkdir($destinationPath, 0755, true);
            }
            
            $file->move($destinationPath, $fileName);
            $dbPath = 'products/' . $fileName; // Saved in DB as products/filename.ext
            
            $product->update(['image' => $dbPath]);
            Log::info('ProductUpdate: Image saved', ['path' => $dbPath, 'product_id' => $product->id]);
        }

        // Update custom values
        $customData = $request->input('custom_data', []);
        if (is_string($customData)) {
            $customData = json_decode($customData, true) ?? [];
        }

        foreach ($customData as $colId => $value) {
            \App\Models\CatalogueCustomValue::updateOrCreate(
                ['product_id' => $product->id, 'column_id' => $colId],
                ['value' => is_array($value) ? json_encode($value) : $value]
            );
        }

        // Update combos
        if ($request->has('combo_data')) {
            \App\Models\ProductCombo::where('product_id', $product->id)->delete();
            foreach ($request->input('combo_data', []) as $colId => $values) {
                if (empty($values))
                    continue;
                \App\Models\ProductCombo::create([
                    'product_id' => $product->id,
                    'column_id' => $colId,
                    'selected_values' => is_array($values) ? $values : explode(',', $values),
                ]);
            }
        }

        // Invalidate AI content cache for this product (stale data prevention)
        \App\Models\UserCustomFrameContent::where('user_id', $userId)
            ->where('product_id', $product->id)
            ->delete();

        return response()->json([
            'success' => true,
            'product' => $product->fresh()->load('customValues', 'combos', 'variations'),
            'message' => 'Product updated successfully!'
        ]);
    }

    public function deleteProduct(Request $request, $id)
    {
        $userId = $request->input('userId');
        if (!$userId) {
            return response()->json(['success' => false, 'message' => 'Missing userId'], 400);
        }

        $product = \App\Models\Product::where('user_id', $userId)->find($id);
        if (!$product) {
            return response()->json(['success' => false, 'message' => 'Product not found'], 404);
        }

        $product->delete();

        return response()->json([
            'success' => true,
            'message' => 'Product deleted successfully!'
        ]);
    }

    public function createProduct(Request $request)
    {
        $request->validate([
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'title' => ['nullable', 'string', 'max:255'],
            'sku' => ['nullable', 'string', 'max:255'],
        ]);

        $userId = $request->input('userId');
        if (!$userId) {
            return response()->json(['success' => false, 'message' => 'Missing userId'], 400);
        }

        // Handle Image Upload
        $dbPath = null;
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $fileName = \Illuminate\Support\Str::uuid() . '.' . $file->extension();
            $destinationPath = public_path('uploads/products');
            if (!file_exists($destinationPath)) {
                mkdir($destinationPath, 0755, true);
            }
            $file->move($destinationPath, $fileName);
            $dbPath = 'products/' . $fileName;
        }

        $product = \App\Models\Product::create([
            'user_id' => $userId,
            'title' => $request->title ?? 'New Product',
            'category_name' => $request->category_name ?? '',
            'sku' => $request->sku ?? 'AUTO-' . strtoupper(uniqid()),
            'mrp' => $request->has('mrp') ? (floatval($request->mrp) * 100) : 0,
            'sale_price' => $request->has('sale_price') ? (floatval($request->sale_price) * 100) : 0,
            'image' => $dbPath,
            'status' => 1,
            'description' => '',
            'gst_percent' => 0,
        ]);

        // Custom values
        $customData = $request->input('custom_data', []);
        if (is_string($customData)) {
            $customData = json_decode($customData, true) ?? [];
        }
        foreach ($customData as $colId => $value) {
            \App\Models\CatalogueCustomValue::create([
                'product_id' => $product->id,
                'column_id' => $colId,
                'value' => is_array($value) ? json_encode($value) : $value
            ]);
        }

        // Combos
        if ($request->has('combo_data')) {
            foreach ($request->input('combo_data', []) as $colId => $values) {
                if (empty($values)) continue;
                \App\Models\ProductCombo::create([
                    'product_id' => $product->id,
                    'column_id' => $colId,
                    'selected_values' => is_array($values) ? $values : explode(',', $values),
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'product' => $product->fresh()->load('customValues', 'combos', 'variations'),
            'message' => 'Product created successfully!'
        ]);
    }

    public function extractFromImage(Request $request)
    {
        $request->validate([
            'image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $userId = $request->input('userId');
        if (!$userId) {
            return response()->json(['success' => false, 'message' => 'Missing userId'], 400);
        }

        if (!$request->hasFile('image')) {
            return response()->json(['success' => false, 'message' => 'Image file is required.'], 400);
        }

        ini_set('memory_limit', '256M');
        set_time_limit(120);
        
        \Log::info("DIAGNOSIS: Image extraction API called for userId: {$userId}");

        try {
            $file = $request->file('image');
            $mimeType = $file->getMimeType();
            $base64Image = base64_encode(file_get_contents($file->getRealPath()));

            $source = $request->input('source');
            if ($source === 'mobile') {
                $columns = \App\Models\CatalogueCustomColumn::where('user_id', $userId)
                    ->where('is_active', true)->orderBy('sort_order')->get()->toArray();
            } else {
                $columnsJson = Setting::getValue('setup_tour', 'ai_columns_json', null, $userId);
                $columns = is_string($columnsJson) ? json_decode($columnsJson, true) : ($columnsJson ?? []);
            }

            if (empty($columns)) {
                \Log::warning("DIAGNOSIS: No custom columns defined for userId: {$userId}");
                return response()->json(['success' => false, 'message' => 'No custom columns defined. Please complete the setup wizard first.'], 422);
            }

            \Log::info("DIAGNOSIS: Image encoded. Size: " . strlen($base64Image) . " bytes. Calling Gemini AI Vision API...");
            $startTime = microtime(true);

            $service = new \App\Services\CatalogueAIService($userId);
            $result = $service->extractProductDataFromImage($base64Image, $mimeType, $columns);

            $duration = round(microtime(true) - $startTime, 2);
            \Log::info("DIAGNOSIS: Gemini AI Vision API returned successfully in {$duration}s. Extracted {$result['total']} products.");

            return response()->json([
                'success' => true,
                'products' => $result['products'],
                'total' => $result['total'],
                'ai_tokens' => $result['ai_tokens'],
                'message' => "{$result['total']} products extracted from the image!"
            ]);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('SetupWizard: Image extraction failed', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Image extraction could not be completed. Please try again.'], 500);
        }
    }

    public function bulkCreateProducts(Request $request)
    {
        $userId = $request->input('userId');
        if (!$userId) {
            return response()->json(['success' => false, 'message' => 'Missing userId'], 400);
        }

        $products = $request->input('products', []);
        if (is_string($products)) {
            $products = json_decode($products, true) ?? [];
        }

        if (empty($products)) {
            return response()->json(['success' => false, 'message' => 'No products provided.'], 422);
        }

        $dbColumns = \App\Models\CatalogueCustomColumn::where('user_id', $userId)
            ->where('is_active', true)->orderBy('sort_order')->get();

        $colMap = [];
        foreach ($dbColumns as $dbCol) {
            $colMap[mb_strtolower(trim($dbCol->name))] = $dbCol;
        }

        $categories = \App\Models\ProductCategory::all();
        $catMap = [];
        foreach ($categories as $cat) {
            $catMap[mb_strtolower(trim($cat->name))] = $cat;
        }

        $created = 0;
        $skipped = 0;
        $errors = [];
        $createdCategories = [];

        try {
            foreach ($products as $idx => $product) {
                $systemData = [
                    'user_id' => $userId,
                    'status' => 1,
                ];
                $customData = [];
                $comboData = [];
                $categoryId = null;
                $productName = 'Product ' . ($idx + 1);

                foreach ($product as $colName => $value) {
                    $value = is_string($value) ? trim($value) : $value;
                    if ($value === '' || $value === null) continue;

                    $key = mb_strtolower(trim($colName));
                    $dbCol = $colMap[$key] ?? null;
                    if (!$dbCol) continue;

                    if ($dbCol->is_category) {
                        $catKey = mb_strtolower(trim($value));
                        if (isset($catMap[$catKey])) {
                            $categoryId = $catMap[$catKey]->id;
                        } else {
                            $newCat = \App\Models\ProductCategory::create([
                                'name' => trim($value),
                                'status' => '1',
                            ]);
                            $catMap[$catKey] = $newCat;
                            $categoryId = $newCat->id;
                            $createdCategories[] = trim($value);
                        }
                        $systemData['category_name'] = trim($value);
                    }

                    if ($dbCol->is_title) {
                        $productName = $value;
                    }

                    if ($dbCol->is_combo) {
                        $comboValues = array_filter(array_map('trim', explode('|', $value)), fn($v) => $v !== '');
                        if (count($comboValues) > 0) {
                            $comboData[$dbCol->id] = $comboValues;
                        }
                        continue;
                    }

                    if ($dbCol->is_system) {
                        $slug = $dbCol->slug;
                        if (in_array($slug, ['sale_price', 'mrp'])) {
                            $systemData[$slug] = round((is_numeric($value) ? (float) $value : 0) * 100);
                        } elseif ($slug === 'gst_percent') {
                            $systemData[$slug] = is_numeric($value) ? (int) $value : 0;
                        } else {
                            $systemData[$slug] = $value;
                        }
                    } else {
                        $customData[$dbCol->id] = $value;
                    }
                }

                if ($categoryId) $systemData['category_id'] = $categoryId;
                if (!isset($systemData['title'])) $systemData['title'] = $productName;
                if (!isset($systemData['sale_price'])) $systemData['sale_price'] = 0;
                if (!isset($systemData['mrp'])) $systemData['mrp'] = 0;
                if (!isset($systemData['gst_percent'])) $systemData['gst_percent'] = 0;
                if (!isset($systemData['sku'])) $systemData['sku'] = 'AUTO-' . strtoupper(uniqid());
                if (!isset($systemData['description'])) $systemData['description'] = '';

                try {
                    $uniqueCol = $dbColumns->where('is_unique', true)->first();
                    $existingProduct = null;

                    if ($uniqueCol) {
                        $uniqueValue = $uniqueCol->is_system
                            ? ($systemData[$uniqueCol->slug] ?? null)
                            : ($customData[$uniqueCol->id] ?? null);

                        if ($uniqueValue) {
                            if ($uniqueCol->is_system) {
                                $existingProduct = \App\Models\Product::where('user_id', $userId)
                                    ->where($uniqueCol->slug, $uniqueValue)->first();
                            } else {
                                $ev = \App\Models\CatalogueCustomValue::where('column_id', $uniqueCol->id)
                                    ->where('value', $uniqueValue)
                                    ->whereHas('product', fn($q) => $q->where('user_id', $userId))
                                    ->first();
                                if ($ev) $existingProduct = $ev->product;
                            }
                        }
                    }

                    if ($existingProduct) {
                        $existingProduct->update($systemData);
                        $p = $existingProduct;
                    } else {
                        $p = \App\Models\Product::create($systemData);
                        $created++;
                    }

                    foreach ($customData as $colId => $val) {
                        \App\Models\CatalogueCustomValue::updateOrCreate(
                            ['product_id' => $p->id, 'column_id' => $colId],
                            ['value' => is_array($val) ? json_encode($val) : $val]
                        );
                    }

                    foreach ($comboData as $colId => $vals) {
                        \App\Models\CatalogueCustomValue::updateOrCreate(
                            ['product_id' => $p->id, 'column_id' => $colId],
                            ['value' => json_encode(array_values($vals))]
                        );
                        \App\Models\ProductCombo::updateOrCreate(
                            ['product_id' => $p->id, 'column_id' => $colId],
                            ['selected_values' => array_values($vals)]
                        );
                    }
                } catch (\Exception $e) {
                    $errors[] = "Product " . ($idx + 1) . ": " . $e->getMessage();
                    $skipped++;
                }
            }

            if (class_exists('\\App\\Services\\AIChatbotService')) {
                \App\Services\AIChatbotService::clearProductGroupCache($userId);
            }

            return response()->json([
                'success' => true,
                'created' => $created,
                'skipped' => $skipped,
                'errors' => $errors,
                'categories_created' => array_unique($createdCategories),
                'message' => "{$created} products imported successfully!" .
                    ($skipped > 0 ? " ({$skipped} skipped)" : '') .
                    (count($createdCategories) > 0 ? " " . count($createdCategories) . " categories auto-created." : ''),
            ]);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('SetupWizard: Bulk create failed', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // --- CATALOGUE COLUMNS MANAGEMENT ---

    public function getColumns(Request $request)
    {
        $userId = $request->input('userId');
        if (!$userId) {
            return response()->json(['success' => false, 'message' => 'Missing userId'], 400);
        }

        $columns = \App\Models\CatalogueCustomColumn::where('user_id', $userId)
            ->orderBy('sort_order')
            ->get();

        return response()->json([
            'success' => true,
            'columns' => $columns
        ]);
    }

    public function updateColumn(Request $request)
    {
        $userId = $request->input('userId');
        if (!$userId) {
            return response()->json(['success' => false, 'message' => 'Missing userId'], 400);
        }

        $id = $request->input('id');
        $data = [
            'name' => $request->input('name'),
            'type' => $request->input('type'),
            'options' => $request->input('options'),
            'is_required' => $request->input('is_required', false),
            'show_on_list' => $request->input('show_on_list', false),
            'is_category' => $request->input('is_category', false),
            'is_unique' => $request->input('is_unique', false),
            'is_combo' => $request->input('is_combo', false),
            'is_title' => $request->input('is_title', false),
            'is_active' => $request->input('is_active', true),
        ];

        if ($id && is_numeric($id)) {
            // Update
            $column = \App\Models\CatalogueCustomColumn::where('user_id', $userId)->find($id);
            if (!$column)
                return response()->json(['success' => false, 'message' => 'Column not found'], 404);
            $column->update($data);
        } else {
            // Create
            $data['user_id'] = $userId;
            $data['slug'] = \Illuminate\Support\Str::slug($data['name'], '_');
            $data['is_system'] = false;
            $data['show_in_ai'] = true;
            $data['sort_order'] = \App\Models\CatalogueCustomColumn::where('user_id', $userId)->max('sort_order') + 1;
            $column = \App\Models\CatalogueCustomColumn::create($data);
        }

        // Exclusivity
        if ($column->is_category)
            \App\Models\CatalogueCustomColumn::where('user_id', $userId)->where('id', '!=', $column->id)->where('is_category', true)->update(['is_category' => false]);
        if ($column->is_unique)
            \App\Models\CatalogueCustomColumn::where('user_id', $userId)->where('id', '!=', $column->id)->where('is_unique', true)->update(['is_unique' => false]);
        if ($column->is_title)
            \App\Models\CatalogueCustomColumn::where('user_id', $userId)->where('id', '!=', $column->id)->where('is_title', true)->update(['is_title' => false]);

        return response()->json(['success' => true, 'column' => $column]);
    }

    public function deleteColumn(Request $request, $id)
    {
        $userId = $request->input('userId');
        $column = \App\Models\CatalogueCustomColumn::where('user_id', $userId)->find($id);
        if (!$column)
            return response()->json(['success' => false, 'message' => 'Not found'], 404);
        if ($column->is_system)
            return response()->json(['success' => false, 'message' => 'System fields cannot be deleted.'], 422);

        $column->delete();
        return response()->json(['success' => true]);
    }

    public function toggleColumn(Request $request, $id)
    {
        $userId = $request->input('userId');
        $column = \App\Models\CatalogueCustomColumn::where('user_id', $userId)->find($id);
        if (!$column)
            return response()->json(['success' => false, 'message' => 'Not found'], 404);

        $column->update(['is_active' => !$column->is_active]);
        return response()->json(['success' => true]);
    }

    public function reorderColumns(Request $request)
    {
        $userId = $request->input('userId');
        $order = $request->input('order', []);

        foreach ($order as $index => $id) {
            \App\Models\CatalogueCustomColumn::where('user_id', $userId)->where('id', $id)->update(['sort_order' => $index + 1]);
        }

        return response()->json(['success' => true]);
    }

    // --- CATEGORY MANAGEMENT ---

    public function getCategoryList(Request $request)
    {
        $userId = $request->input('userId');
        $columnId = $request->input('columnId');
        if (!$userId) return response()->json(['success' => false, 'message' => 'Missing userId'], 400);

        $query = \App\Models\CatalogueCustomColumn::where('user_id', $userId);
        if ($columnId) {
            $query->where('id', $columnId);
        } else {
            $query->where('is_category', true);
        }
        $categoryColumn = $query->first();

        if (!$categoryColumn) {
            return response()->json(['success' => true, 'categories' => []]);
        }

        $options = $categoryColumn->options ?? [];
        if (is_string($options)) {
            $options = json_decode($options, true) ?? [];
        }

        // Count connected products
        $customValues = \App\Models\CatalogueCustomValue::where('column_id', $categoryColumn->id)
            ->whereNotNull('value')
            ->where('value', '!=', '')
            ->whereHas('product', function($q) use ($userId) {
                $q->where('user_id', $userId);
            })
            ->get();
            
        $productCounts = [];
        foreach ($customValues as $cv) {
            $vals = array_map('trim', explode(',', $cv->value));
            foreach ($vals as $v) {
                if (!isset($productCounts[$v])) $productCounts[$v] = [];
                $productCounts[$v][$cv->product_id] = true; // Use array to emulate DISTINCT product_id
            }
        }

        $categories = [];
        foreach ($options as $opt) {
            $categories[] = [
                'name' => $opt,
                'count' => isset($productCounts[$opt]) ? count($productCounts[$opt]) : 0
            ];
        }

        return response()->json(['success' => true, 'categories' => $categories]);
    }

    public function addCategory(Request $request)
    {
        $userId = $request->input('userId');
        $name = trim($request->input('name'));
        $columnId = $request->input('columnId');
        if (!$userId || !$name) return response()->json(['success' => false, 'message' => 'Missing required fields'], 400);

        $query = \App\Models\CatalogueCustomColumn::where('user_id', $userId);
        if ($columnId) {
            $query->where('id', $columnId);
        } else {
            $query->where('is_category', true);
        }
        $categoryColumn = $query->first();

        if (!$categoryColumn) return response()->json(['success' => false, 'message' => 'No category column defined'], 404);

        $options = $categoryColumn->options ?? [];
        if (is_string($options)) $options = json_decode($options, true) ?? [];

        if (in_array($name, $options)) {
            return response()->json(['success' => false, 'message' => 'Category already exists'], 422);
        }

        $options[] = $name;
        $categoryColumn->update(['options' => $options]);

        return response()->json(['success' => true]);
    }

    public function updateCategory(Request $request)
    {
        $userId = $request->input('userId');
        $oldName = trim($request->input('old_name'));
        $newName = trim($request->input('new_name'));
        $columnId = $request->input('columnId');

        if (!$userId || !$oldName || !$newName) return response()->json(['success' => false, 'message' => 'Missing required fields'], 400);

        $query = \App\Models\CatalogueCustomColumn::where('user_id', $userId);
        if ($columnId) {
            $query->where('id', $columnId);
        } else {
            $query->where('is_category', true);
        }
        $categoryColumn = $query->first();

        if (!$categoryColumn) return response()->json(['success' => false, 'message' => 'No category column defined'], 404);

        $options = $categoryColumn->options ?? [];
        if (is_string($options)) $options = json_decode($options, true) ?? [];

        $index = array_search($oldName, $options);
        if ($index !== false) {
            if (in_array($newName, $options)) {
                return response()->json(['success' => false, 'message' => 'A category with the new name already exists'], 422);
            }
            $options[$index] = $newName;
            $categoryColumn->update(['options' => $options]);
        } else {
            $options[] = $newName;
            $categoryColumn->update(['options' => $options]);
        }

        // Update in products table only if it's the category column
        if ($categoryColumn->is_category) {
            \App\Models\Product::where('user_id', $userId)
                ->where('category_name', $oldName)
                ->update(['category_name' => $newName]);
        }

        // Update custom values (handling comma-separated combo fields)
        $customValues = \App\Models\CatalogueCustomValue::where('column_id', $categoryColumn->id)
            ->where('value', 'LIKE', '%' . $oldName . '%')
            ->whereHas('product', function($q) use ($userId) {
                $q->where('user_id', $userId);
            })->get();
            
        foreach ($customValues as $cv) {
            $vals = array_map('trim', explode(',', $cv->value));
            $idx = array_search($oldName, $vals);
            if ($idx !== false) {
                $vals[$idx] = $newName;
                $cv->update(['value' => implode(', ', $vals)]);
            }
        }

        return response()->json(['success' => true]);
    }

    public function deleteCategory(Request $request)
    {
        $userId = $request->input('userId');
        $name = trim($request->input('name'));
        $columnId = $request->input('columnId');
        if (!$userId || !$name) return response()->json(['success' => false, 'message' => 'Missing required fields'], 400);

        $query = \App\Models\CatalogueCustomColumn::where('user_id', $userId);
        if ($columnId) {
            $query->where('id', $columnId);
        } else {
            $query->where('is_category', true);
        }
        $categoryColumn = $query->first();

        if (!$categoryColumn) return response()->json(['success' => false, 'message' => 'No category column defined'], 404);

        $options = $categoryColumn->options ?? [];
        if (is_string($options)) $options = json_decode($options, true) ?? [];

        $index = array_search($name, $options);
        if ($index !== false) {
            array_splice($options, $index, 1);
            $categoryColumn->update(['options' => $options]);
        }

        // Detach category from products (set to null) in products table only if is_category
        if ($categoryColumn->is_category) {
            \App\Models\Product::where('user_id', $userId)
                ->where('category_name', $name)
                ->update(['category_name' => null]);
        }

        // Delete from custom values
        $customValues = \App\Models\CatalogueCustomValue::where('column_id', $categoryColumn->id)
            ->where('value', 'LIKE', '%' . $name . '%')
            ->whereHas('product', function($q) use ($userId) {
                $q->where('user_id', $userId);
            })->get();
            
        foreach ($customValues as $cv) {
            $vals = array_map('trim', explode(',', $cv->value));
            $idx = array_search($name, $vals);
            if ($idx !== false) {
                array_splice($vals, $idx, 1);
                $cv->update(['value' => implode(', ', $vals)]);
            }
        }

        return response()->json(['success' => true]);
    }
}
