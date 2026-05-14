<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\CatalogueCustomColumn;
use App\Services\CatalogueAIService;
use App\Services\CatalogueColumnImportService;
use App\Services\ProductExcelService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class SetupWizardController extends Controller
{
    public function index()
    {
        $userId = auth()->id();

        $isCompleted = Setting::getValue('setup_tour', 'completed', false, $userId);
        $existingColumns = CatalogueCustomColumn::where('user_id', $userId)->count();

        $cachedColumns = Setting::getValue('setup_tour', 'ai_columns_json', null, $userId);
        if (is_string($cachedColumns))
            $cachedColumns = json_decode($cachedColumns, true);

        $tourConfig = [
            'welcome_title' => Setting::getGlobalValue('setup_tour', 'welcome_title', 'Welcome to VyaparCRM! ðŸš€'),
            'welcome_subtitle' => Setting::getGlobalValue('setup_tour', 'welcome_subtitle', 'Let\'s set up your product catalogue in minutes using AI'),
            'intro_message' => Setting::getGlobalValue('setup_tour', 'intro_message', 'Upload your product catalogue PDF or share your website URL â€” our AI will automatically analyze your products and create the perfect database structure for you.'),
        ];

        $isConfigured = (new \App\Services\VertexAIService($userId))->isConfigured();

        $business = \App\Models\Business::where('user_id', $userId)->where('is_default', 1)->first();
        if (!$business) {
            $business = \App\Models\Business::where('user_id', $userId)->first();
        }

        $notification_count = \App\Models\UserNotification::query();
        $user = auth()->user();
        if ($user && $user->last_notification_read_at) {
            $notification_count->where('created_at', '>', $user->last_notification_read_at);
        }
        $notification_count = $notification_count->count();

        return view('client.setup_wizard', compact(
            'isCompleted',
            'existingColumns',
            'cachedColumns',
            'tourConfig',
            'isConfigured',
            'business',
            'notification_count'
        ));
    }

    public function analyze(Request $request)
    {
        $request->validate([
            'source_type' => 'required|in:pdf,website',
            'catalogue_pdf' => 'required_if:source_type,pdf|file|mimes:pdf|max:51200',
            'website_url' => 'required_if:source_type,website|nullable|url',
        ]);

        $userId = auth()->id();
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

                if ($fileSizeMB <= 10) {
                    $content = $service->extractTextFromPDF($fakeUpload);
                } else {
                    Log::info("SetupWizard: Skipping text extraction for large PDF ({$fileSizeMB}MB), using multimodal directly");
                }

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
            Log::error('SetupWizard: Analysis failed', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'An unexpected error occurred: ' . $e->getMessage()], 500);
        }
    }

    public function downloadColumnsExcel()
    {
        $userId = auth()->id();
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
        $userId = auth()->id();
        $importService = new CatalogueColumnImportService($userId);

        $importType = $request->input('import_type', 'direct');

        try {
            if ($importType === 'excel') {
                $request->validate(['file' => 'required|file|mimes:xlsx,xls|max:10240']);

                $file = $request->file('file');
                $tempName = 'col_import_' . auth()->id() . '_' . time() . '.' . $file->getClientOriginalExtension();
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

    public function extractProducts()
    {
        $userId = auth()->id();

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

    public function importProducts()
    {
        $userId = auth()->id();
        $userId = auth()->id();

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
                        $comboValues = array_filter(array_map('trim', explode('|', $value)));
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

    public function downloadProductsExcel()
    {
        $userId = auth()->id();

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

    public function complete()
    {
        $userId = auth()->id();
        Setting::setValue('setup_tour', 'completed', true, $userId);
        Setting::setValue('setup_tour', 'completed_at', now()->toISOString(), $userId);
        Setting::setValue('setup_tour', 'completed_by', auth()->id(), $userId);

        return response()->json([
            'success' => true,
            'message' => 'Setup wizard completed! Your catalogue is ready.',
        ]);
    }

    public function reset()
    {
        $userId = auth()->id();

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
}
