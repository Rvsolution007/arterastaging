<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\BusinessCustomFrame;
use Illuminate\Support\Str;

class TemplateBuilderController extends Controller
{
    public function index(Request $request)
    {
        $globalFonts = \App\Models\Font::where('status', 1)->get();
        $customFrames = \App\Models\BusinessCustomFrame::orderBy('id', 'desc')->get();
        
        $mode = $request->query('mode', 'template');
        $frameCategories = [];
        $posterMakers = [];
        $editFrame = null;
        
        if ($mode === 'frame') {
            $frameCategories = \App\Models\PosterCategory::where('status', 1)->get();
            $posterMakers = \App\Models\PosterMaker::orderBy('zip_name', 'asc')->get();
            if ($request->has('frame_id')) {
                $editFrame = \App\Models\PosterMaker::find($request->query('frame_id'));
            }
        }
        
        return view('admin.template_builder.index', compact('globalFonts', 'customFrames', 'mode', 'frameCategories', 'posterMakers', 'editFrame'));
    }

    public function getStickers(Request $request)
    {
        return app(\App\Http\Controllers\Api\EditorDataController::class)->getStickers($request);
    }

    public function parseZip(Request $request)
    {
        $request->validate(['zip_file' => 'required|file|mimes:zip']);
        
        $zipFile = $request->file('zip_file');
        $zip = new \ZipArchive();
        if ($zip->open($zipFile->getRealPath()) === TRUE) {
            $jsonConfig = null;
            $images = [];
            $fonts = [];

            for ($i = 0; $i < $zip->numFiles; $i++) {
                $name = $zip->getNameIndex($i);
                $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                if ($ext === 'json') {
                    if (strpos($name, '__MACOSX') === false && strpos(basename($name), '._') !== 0) {
                        $decoded = json_decode($zip->getFromIndex($i), true);
                        if ($decoded) {
                            if (!$jsonConfig || isset($decoded['layers']) || isset($decoded['objects']) || isset($decoded['schema_version'])) {
                                $jsonConfig = $decoded;
                            }
                        }
                    }
                } elseif (in_array($ext, ['png', 'jpg', 'jpeg', 'webp'])) {
                    $images[basename($name)] = 'data:image/' . $ext . ';base64,' . base64_encode($zip->getFromIndex($i));
                } elseif (in_array($ext, ['ttf', 'otf', 'woff', 'woff2'])) {
                    $mime = 'font/' . $ext;
                    if ($ext === 'ttf') $mime = 'font/truetype';
                    if ($ext === 'otf') $mime = 'font/opentype';
                    $fonts[pathinfo($name, PATHINFO_FILENAME)] = 'data:' . $mime . ';base64,' . base64_encode($zip->getFromIndex($i));
                }
            }
            $zip->close();

            if ($jsonConfig) {
                $this->_injectSystemFonts($fonts, $jsonConfig);
                return response()->json(['success' => true, 'config' => $jsonConfig, 'images' => $images, 'fonts' => $fonts]);
            }
        }
        return response()->json(['success' => false, 'message' => 'Invalid ZIP or missing JSON']);
    }

    private function _injectSystemFonts(&$fonts, $jsonConfig)
    {
        if (!$jsonConfig) return;

        $requestedFonts = [];
        array_walk_recursive($jsonConfig, function($value, $key) use (&$requestedFonts) {
            if ($key === 'font' || $key === 'fontFamily') {
                $requestedFonts[] = $value;
            }
        });
        $requestedFonts = array_unique($requestedFonts);

        foreach ($requestedFonts as $fontName) {
            if (!isset($fonts[$fontName])) {
                $normalizedFontName = str_replace([' ', '-', '_'], '', strtolower($fontName));
                $fontDb = \App\Models\Font::whereRaw("LOWER(REPLACE(REPLACE(REPLACE(name, ' ', ''), '-', ''), '_', '')) = ?", [$normalizedFontName])->first();
                if (!$fontDb) {
                    $parts = preg_split('/[-_ ]/', $fontName);
                    if (count($parts) > 0) {
                        $normalizedPrefix = str_replace([' ', '-', '_'], '', strtolower($parts[0]));
                        $fontDb = \App\Models\Font::whereRaw("LOWER(REPLACE(REPLACE(REPLACE(name, ' ', ''), '-', ''), '_', '')) LIKE ?", [$normalizedPrefix . '%'])->first();
                    }
                }
                
                if ($fontDb) {
                    $fonts[$fontName] = asset($fontDb->file_path);
                }
            }
        }
    }

    public function loadZip($id)
    {
        $frame = \App\Models\BusinessCustomFrame::find($id);
        if (!$frame) {
            return response()->json(['success' => false, 'message' => 'Template not found']);
        }

        $zipPath = public_path('uploads/custom_frames_zips/' . $frame->zip_file_path);
        if (!file_exists($zipPath)) {
            return response()->json(['success' => false, 'message' => 'ZIP file not found']);
        }

        $zip = new \ZipArchive();
        if ($zip->open($zipPath) === TRUE) {
            $jsonConfig = null;
            $images = [];
            $fonts = [];

            for ($i = 0; $i < $zip->numFiles; $i++) {
                $name = $zip->getNameIndex($i);
                $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                
                // Read JSON files
                if ($ext === 'json') {
                    if (strpos($name, '__MACOSX') === false && strpos(basename($name), '._') !== 0) {
                        $decoded = json_decode($zip->getFromIndex($i), true);
                        if ($decoded) {
                            // Only overwrite if it looks like a valid template JSON, or if we haven't found one yet
                            if (!$jsonConfig || isset($decoded['layers']) || isset($decoded['objects']) || isset($decoded['schema_version'])) {
                                $jsonConfig = $decoded;
                            }
                        }
                    }
                } 
                // Read Images
                elseif (in_array($ext, ['png', 'jpg', 'jpeg', 'webp'])) {
                    $images[basename($name)] = 'data:image/' . $ext . ';base64,' . base64_encode($zip->getFromIndex($i));
                } 
                // Read Fonts
                elseif (in_array($ext, ['ttf', 'otf', 'woff', 'woff2'])) {
                    $mime = 'font/' . $ext;
                    if ($ext === 'ttf') $mime = 'font/truetype';
                    if ($ext === 'otf') $mime = 'font/opentype';
                    $fonts[pathinfo($name, PATHINFO_FILENAME)] = 'data:' . $mime . ';base64,' . base64_encode($zip->getFromIndex($i));
                }
            }
            $zip->close();

            if ($jsonConfig) {
                // Try to load full Artera schema if available for the web builder
                $uuid = str_replace(['Template_', '.zip'], '', $frame->zip_file_path);
                $editorTemplate = \App\Models\EditorTemplate::where('uuid', $uuid)->first();
                
                // Fallback: if zip_file_path doesn't follow "Template_UUID.zip" format,
                // try to find the EditorTemplate via PosterMaker's zip_name
                if (!$editorTemplate) {
                    $zipBaseName = str_replace('.zip', '', $frame->zip_file_path);
                    $posterMaker = \App\Models\PosterMaker::where('zip_name', $zipBaseName)->first();
                    if ($posterMaker && $posterMaker->zip_name && str_starts_with($posterMaker->zip_name, 'Template_')) {
                        $fallbackUuid = str_replace('Template_', '', $posterMaker->zip_name);
                        $editorTemplate = \App\Models\EditorTemplate::where('uuid', $fallbackUuid)->first();
                        if ($editorTemplate) $uuid = $fallbackUuid;
                    }
                    // Last resort: normalized fuzzy title matching
                    // Raw title has spaces ("Untitled design - 1254 x 1254") while
                    // zip_file_path has underscores ("Untitled_design_-_1254_x_1254.zip")
                    if (!$editorTemplate) {
                        $normalizedZipName = strtolower(preg_replace('/[^a-z0-9]/i', '', $zipBaseName));
                        $editorTemplate = \App\Models\EditorTemplate::whereRaw(
                            "LOWER(REPLACE(REPLACE(REPLACE(REPLACE(title, ' ', ''), '-', ''), '_', ''), '.', '')) = ?",
                            [$normalizedZipName]
                        )->orderByDesc('updated_at')->first();
                        if ($editorTemplate) $uuid = $editorTemplate->uuid;
                    }
                }
                if ($editorTemplate && is_array($editorTemplate->schema_json)) {
                    $jsonConfig = $editorTemplate->schema_json;
                    if (isset($jsonConfig['elements'])) {
                        foreach ($jsonConfig['elements'] as &$el) {
                            if (isset($el['src']) && strpos($el['src'], 'assets/') === 0) {
                                $el['src'] = asset('uploads/editor/templates/' . $uuid . '/' . $el['src']);
                            }
                        }
                    }
                    // Extract fonts from schema
                    $schemaFonts = [];
                    array_walk_recursive($jsonConfig, function($value, $key) use (&$schemaFonts) {
                        if ($key === 'font' || $key === 'fontFamily' || $key === 'family') {
                            $schemaFonts[] = $value;
                        }
                    });
                    $schemaFonts = array_unique($schemaFonts);
                    foreach ($schemaFonts as $fontName) {
                        // Load the exact font name if available
                        if (!isset($fonts[$fontName])) {
                            $normalizedFontName = str_replace([' ', '-', '_'], '', strtolower($fontName));
                            $fontDb = \App\Models\Font::whereRaw("LOWER(REPLACE(REPLACE(REPLACE(name, ' ', ''), '-', ''), '_', '')) = ?", [$normalizedFontName])->first();
                            if ($fontDb) {
                                $fonts[$fontName] = asset($fontDb->file_path);
                            }
                        }
                        // Also load ALL variants for this font family (Bold, Regular, Italic, etc.)
                        $parts = preg_split('/[-_ ]/', $fontName);
                        $baseFamilyName = count($parts) > 0 ? $parts[0] : $fontName;
                        $normalizedBaseFamily = str_replace([' ', '-', '_'], '', strtolower($baseFamilyName));
                        $allVariants = \App\Models\Font::whereRaw("LOWER(REPLACE(REPLACE(REPLACE(name, ' ', ''), '-', ''), '_', '')) LIKE ?", [$normalizedBaseFamily . '%'])->get();
                        foreach ($allVariants as $variantFont) {
                            if (!isset($fonts[$variantFont->name])) {
                                $fonts[$variantFont->name] = asset($variantFont->file_path);
                            }
                        }
                        // Also check for exact family name (e.g., "Montserrat" without suffix)
                        if (!isset($fonts[$baseFamilyName])) {
                            $exactFamily = \App\Models\Font::whereRaw("LOWER(REPLACE(REPLACE(REPLACE(name, ' ', ''), '-', ''), '_', '')) = ?", [$normalizedBaseFamily])->first();
                            if ($exactFamily) {
                                $fonts[$baseFamilyName] = asset($exactFamily->file_path);
                            }
                        }
                    }
                } else {
                    $this->_injectSystemFonts($fonts, $jsonConfig);
                }

                return response()->json([
                    'success' => true, 
                    'config' => $jsonConfig, 
                    'images' => $images, 
                    'fonts' => $fonts,
                    'frame_id' => $id,
                    'title' => $frame->original_zip_name ?? 'Template'
                ]);
            }
        }
        
        return response()->json(['success' => false, 'message' => 'Invalid ZIP or missing JSON']);
    }

    public function loadFrameZip($id)
    {
        $frame = \App\Models\PosterMaker::find($id);
        if (!$frame) {
            return response()->json(['success' => false, 'message' => 'Frame not found']);
        }

        $zipPath = public_path('uploads/custom_frames_zips/' . $frame->zip_name . '.zip');
        $extractedPath = public_path('uploads/template/' . $frame->zip_name);
        
        $jsonConfig = null;
        $images = [];
        $fonts = [];

        if (file_exists($zipPath)) {
            $zip = new \ZipArchive();
            if ($zip->open($zipPath) === TRUE) {
                for ($i = 0; $i < $zip->numFiles; $i++) {
                    $name = $zip->getNameIndex($i);
                    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                    
                    if ($ext === 'json') {
                        if (strpos($name, '__MACOSX') === false && strpos(basename($name), '._') !== 0) {
                            $decoded = json_decode($zip->getFromIndex($i), true);
                            if ($decoded) {
                                if (!$jsonConfig || isset($decoded['layers']) || isset($decoded['objects']) || isset($decoded['schema_version'])) {
                                    $jsonConfig = $decoded;
                                }
                            }
                        }
                    } elseif (in_array($ext, ['png', 'jpg', 'jpeg', 'webp'])) {
                        $images[basename($name)] = 'data:image/' . $ext . ';base64,' . base64_encode($zip->getFromIndex($i));
                    } elseif (in_array($ext, ['ttf', 'otf', 'woff', 'woff2'])) {
                        $mime = 'font/' . $ext;
                        if ($ext === 'ttf') $mime = 'font/truetype';
                        if ($ext === 'otf') $mime = 'font/opentype';
                        $fonts[pathinfo($name, PATHINFO_FILENAME)] = 'data:' . $mime . ';base64,' . base64_encode($zip->getFromIndex($i));
                    }
                }
                $zip->close();
            }
        } elseif (is_dir($extractedPath)) {
            // Load from extracted legacy folder structure
            if (is_dir($extractedPath . '/json')) {
                $jsonFiles = glob($extractedPath . '/json/*.json');
                if (!empty($jsonFiles)) {
                    $decoded = json_decode(file_get_contents($jsonFiles[0]), true);
                    if ($decoded) $jsonConfig = $decoded;
                }
            }
            if (is_dir($extractedPath . '/skins')) {
                foreach (\Illuminate\Support\Facades\File::allFiles($extractedPath . '/skins') as $imgFile) {
                    $ext = strtolower($imgFile->getExtension());
                    if (in_array($ext, ['png', 'jpg', 'jpeg', 'webp'])) {
                        $images[$imgFile->getFilename()] = 'data:image/' . $ext . ';base64,' . base64_encode(file_get_contents($imgFile->getPathname()));
                    }
                }
            }
            if (is_dir($extractedPath . '/fonts')) {
                foreach (\Illuminate\Support\Facades\File::allFiles($extractedPath . '/fonts') as $fontFile) {
                    $ext = strtolower($fontFile->getExtension());
                    if (in_array($ext, ['ttf', 'otf', 'woff', 'woff2'])) {
                        $mime = 'font/' . $ext;
                        if ($ext === 'ttf') $mime = 'font/truetype';
                        if ($ext === 'otf') $mime = 'font/opentype';
                        $fonts[$fontFile->getFilenameWithoutExtension()] = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($fontFile->getPathname()));
                    }
                }
            }
        } else {
            return response()->json(['success' => false, 'message' => 'ZIP file or template directory not found']);
        }
        if (!$jsonConfig && is_dir($extractedPath . '/skins')) {
            $jsonConfig = ['schema_version' => 1, 'layers' => []];
        }

        if ($jsonConfig) {
                // Try to load full Artera schema from EditorTemplate (preserves vector shapes)
                $editorTemplate = null;
                $editorUuid = null;

                // Method 1: Extract UUID from zip_name if it follows "Template_UUID" format
                if ($frame->zip_name && str_starts_with($frame->zip_name, 'Template_')) {
                    $editorUuid = str_replace('Template_', '', $frame->zip_name);
                    $editorTemplate = \App\Models\EditorTemplate::where('uuid', $editorUuid)->first();
                }

                // Method 2: Normalized fuzzy title matching
                if (!$editorTemplate && $frame->zip_name) {
                    $normalizedZipName = strtolower(preg_replace('/[^a-z0-9]/i', '', $frame->zip_name));
                    $editorTemplate = \App\Models\EditorTemplate::whereRaw(
                        "LOWER(REPLACE(REPLACE(REPLACE(REPLACE(title, ' ', ''), '-', ''), '_', ''), '.', '')) = ?",
                        [$normalizedZipName]
                    )->orderByDesc('updated_at')->first();
                    if ($editorTemplate) $editorUuid = $editorTemplate->uuid;
                }

                // If EditorTemplate found, use its schema_json (vector shapes preserved)
                if ($editorTemplate && is_array($editorTemplate->schema_json)) {
                    $jsonConfig = $editorTemplate->schema_json;
                    if (isset($jsonConfig['elements'])) {
                        foreach ($jsonConfig['elements'] as &$el) {
                            if (isset($el['src']) && strpos($el['src'], 'assets/') === 0) {
                                $el['src'] = asset('uploads/editor/templates/' . $editorUuid . '/' . $el['src']);
                            }
                        }
                    }
                    // Extract and load fonts from schema
                    $schemaFonts = [];
                    array_walk_recursive($jsonConfig, function($value, $key) use (&$schemaFonts) {
                        if ($key === 'font' || $key === 'fontFamily' || $key === 'family') {
                            $schemaFonts[] = $value;
                        }
                    });
                    $schemaFonts = array_unique($schemaFonts);
                    foreach ($schemaFonts as $fontName) {
                        if (!isset($fonts[$fontName])) {
                            $normalizedFontName = str_replace([' ', '-', '_'], '', strtolower($fontName));
                            $fontDb = \App\Models\Font::whereRaw("LOWER(REPLACE(REPLACE(REPLACE(name, ' ', ''), '-', ''), '_', '')) = ?", [$normalizedFontName])->first();
                            if ($fontDb) {
                                $fonts[$fontName] = asset($fontDb->file_path);
                            }
                        }
                    }
                }

                // Return frame settings alongside zip content
                $frameData = [
                    'poster_category_id' => $frame->poster_category_id,
                    'template_type' => $frame->template_type,
                    'theme' => $frame->theme,
                    'req_address' => $frame->req_address,
                    'req_email' => $frame->req_email,
                    'req_phone' => $frame->req_phone,
                    'req_website' => $frame->req_website,
                ];

                $this->_injectSystemFonts($fonts, $jsonConfig);
                return response()->json([
                    'success' => true, 
                    'config' => $jsonConfig, 
                    'images' => $images, 
                    'fonts' => $fonts,
                    'frame_id' => $id,
                    'title' => str_replace('.zip', '', $frame->zip_name),
                    'frameData' => $frameData
                ]);
            }
        
        return response()->json(['success' => false, 'message' => 'Invalid ZIP or missing JSON']);
    }

    public function save(Request $request)
    {
        $request->validate([
            'schema_json' => 'required',
            'title' => 'required|string',
            'thumbnail' => 'required', // base64 string
        ]);

        $schemaJson = json_decode($request->input('schema_json'), true);
        $legacyJson = json_decode($request->input('legacy_json', '[]'), true);

        // ── Determine if UPDATE or CREATE ──
        $existingFrame = null;
        $existingTemplate = null;
        $uuid = null;

        if ($request->has('frame_id') && $request->input('frame_id')) {
            $existingFrame = \App\Models\BusinessCustomFrame::find($request->input('frame_id'));
            if ($existingFrame && $existingFrame->zip_file_path) {
                // Extract UUID from the existing zip_file_path (e.g. "Template_UUID.zip" → "UUID")
                $oldUuid = str_replace(['Template_', '.zip'], '', $existingFrame->zip_file_path);
                $existingTemplate = \App\Models\EditorTemplate::where('uuid', $oldUuid)->first();
                if ($existingTemplate) {
                    $uuid = $oldUuid; // Reuse same UUID — same folder, same paths
                    \Log::info('Template UPDATE mode: reusing UUID ' . $uuid . ' for frame #' . $existingFrame->id);
                }
            }
        }

        // New template — generate fresh UUID
        if (!$uuid) {
            $uuid = Str::uuid()->toString();
            \Log::info('Template CREATE mode: new UUID ' . $uuid);
        }

        $templateDir = public_path('uploads/editor/templates/' . $uuid);
        $assetsDir = $templateDir . '/assets';

        if (!file_exists($assetsDir)) {
            mkdir($assetsDir, 0777, true);
        }

        // Process base64 images in schema_json
        if (isset($schemaJson['elements'])) {
            foreach ($schemaJson['elements'] as &$element) {
                if ($element['type'] === 'image' && isset($element['src']) && strpos($element['src'], 'data:image') === 0) {
                    $image_parts = explode(";base64,", $element['src']);
                    $image_type_aux = explode("image/", $image_parts[0]);
                    $image_type = $image_type_aux[1] === 'jpeg' ? 'jpg' : $image_type_aux[1];
                    $image_base64 = base64_decode($image_parts[1]);
                    $fileName = Str::random(10) . '.' . $image_type;
                    file_put_contents($assetsDir . '/' . $fileName, $image_base64);
                    
                    // Store relative to the assets folder for the schema
                    $element['src'] = 'assets/' . $fileName;

                    // Update legacy_json to use the same file
                    if (isset($legacyJson['layers'])) {
                        foreach ($legacyJson['layers'] as &$legacyLayer) {
                            if ($legacyLayer['name'] === $element['name'] && isset($legacyLayer['src'])) {
                                $legacyLayer['src'] = '../skins/' . $uuid . '/' . $fileName;
                            }
                        }
                    }
                }
                // If src is already a relative "assets/..." path, it's fine — same uuid folder
                // If src is an HTTP URL pointing to our server, copy to assets
                elseif ($element['type'] === 'image' && isset($element['src']) && strpos($element['src'], 'http') === 0 && strpos($element['src'], 'uploads/') !== false) {
                    $relativePath = 'uploads/' . explode('uploads/', $element['src'])[1];
                    $relativePath = strtok($relativePath, '?');
                    $localPath = public_path($relativePath);
                    if (file_exists($localPath)) {
                        $basename = basename($localPath);
                        // Only copy if not already in our assets dir
                        if (!file_exists($assetsDir . '/' . $basename)) {
                            copy($localPath, $assetsDir . '/' . $basename);
                        }
                        $element['src'] = 'assets/' . $basename;
                    }
                }
            }
        }

        // Process legacy_json for base64 shapes and old HTTP images
        if (isset($legacyJson['layers'])) {
            foreach ($legacyJson['layers'] as &$legacyLayer) {
                if ($legacyLayer['type'] === 'image' && isset($legacyLayer['src'])) {
                    $src = $legacyLayer['src'];
                    if (strpos($src, 'data:image') === 0) {
                        $image_parts = explode(";base64,", $src);
                        $image_type_aux = explode("image/", $image_parts[0]);
                        $image_type = $image_type_aux[1] === 'jpeg' ? 'jpg' : $image_type_aux[1];
                        if ($image_type === 'png' || $image_type === 'jpg' || $image_type === 'webp') {
                            $image_base64 = base64_decode($image_parts[1]);
                            $fileName = Str::random(10) . '.' . $image_type;
                            file_put_contents($assetsDir . '/' . $fileName, $image_base64);
                            $legacyLayer['src'] = '../skins/' . $uuid . '/' . $fileName;
                        }
                    } elseif (strpos($src, 'http') === 0 && strpos($src, 'uploads/') !== false) {
                        $relativePath = 'uploads/' . explode('uploads/', $src)[1];
                        $relativePath = strtok($relativePath, '?'); 
                        $localPath = public_path($relativePath);
                        if (file_exists($localPath)) {
                            $basename = basename($localPath);
                            if (!file_exists($assetsDir . '/' . $basename)) {
                                copy($localPath, $assetsDir . '/' . $basename);
                            }
                            $legacyLayer['src'] = '../skins/' . $uuid . '/' . $basename;
                        }
                    }
                    // If src is already "../skins/UUID/file.png" pointing to SAME uuid, it's fine
                    // If it points to a DIFFERENT old uuid, fix the path
                    elseif (strpos($src, '../skins/') === 0 && strpos($src, '../skins/' . $uuid . '/') !== 0) {
                        // Old uuid path — extract filename and repoint
                        $basename = basename($src);
                        // The file should already be in assetsDir (copied during initial import)
                        if (file_exists($assetsDir . '/' . $basename)) {
                            $legacyLayer['src'] = '../skins/' . $uuid . '/' . $basename;
                        }
                    }
                }
            }
        }

        // Handle thumbnail
        $thumbPath = null;
        if ($request->filled('thumbnail')) {
            $thumbParts = explode(";base64,", $request->input('thumbnail'));
            $thumbTypeAux = explode("image/", $thumbParts[0]);
            $thumbType = $thumbTypeAux[1] === 'jpeg' ? 'jpg' : $thumbTypeAux[1];
            $thumbBase64 = base64_decode($thumbParts[1]);
            $thumbName = 'thumbnail.' . $thumbType;
            file_put_contents($templateDir . '/' . $thumbName, $thumbBase64);
            $thumbPath = 'uploads/editor/templates/' . $uuid . '/' . $thumbName;
        }

        // ── UPDATE existing or CREATE new EditorTemplate ──
        if ($existingTemplate) {
            $existingTemplate->title = $request->input('title');
            $existingTemplate->canvas_width = $schemaJson['canvas']['width'] ?? 1080;
            $existingTemplate->canvas_height = $schemaJson['canvas']['height'] ?? 1080;
            $existingTemplate->schema_json = $schemaJson;
            $existingTemplate->legacy_json = $legacyJson;
            $existingTemplate->thumbnail_path = $thumbPath;
            $existingTemplate->status = 'published';
            $existingTemplate->save();
            $template = $existingTemplate;
            \Log::info('EditorTemplate UPDATED: id=' . $template->id . ' uuid=' . $uuid);
        } else {
            $template = new \App\Models\EditorTemplate();
            $template->uuid = $uuid;
            $template->title = $request->input('title');
            $template->canvas_width = $schemaJson['canvas']['width'] ?? 1080;
            $template->canvas_height = $schemaJson['canvas']['height'] ?? 1080;
            $template->schema_json = $schemaJson;
            $template->legacy_json = $legacyJson;
            $template->thumbnail_path = $thumbPath;
            $template->status = 'published';
            $template->author_id = auth()->id() ?? 1;
            $template->save();
            \Log::info('EditorTemplate CREATED: id=' . $template->id . ' uuid=' . $uuid);
        }

        // Generate the ZIP and create/update BusinessCustomFrame entry
        $this->generateLegacyZip($template, $request->input('purpose_id', 1), $request->input('image_type_id', 1), $existingFrame);

        // Clear mobile API cache for this template so app gets fresh data
        $templateName = "Template_" . $uuid;
        \Illuminate\Support\Facades\Cache::forget("template_json:{$templateName}");
        \Illuminate\Support\Facades\Cache::forget("template_json:{$templateName}.zip");
        \Log::info('Cache cleared for template_json:' . $templateName);

        return response()->json(['success' => true, 'message' => 'Template saved successfully!', 'uuid' => $uuid]);
    }

    private function generateLegacyZip(\App\Models\EditorTemplate $template, $purposeId, $imageTypeId, $existingFrame = null)
    {
        $uuid = $template->uuid;
        $templateName = "Template_" . $uuid;
        
        // Delete old zip to free up space since we are forcing a new filename for cache-busting
        if ($existingFrame && $existingFrame->zip_file_path) {
            $oldZipName = $existingFrame->zip_file_path;
            if ($oldZipName !== $templateName . '.zip') {
                $oldZip = public_path('uploads/custom_frames_zips/' . $oldZipName);
                if (file_exists($oldZip)) {
                    @unlink($oldZip);
                }
            }
        }
        
        $templateDir = public_path('uploads/editor/templates/' . $uuid);
        $assetsDir = $templateDir . '/assets';

        // Temporary directory for zip creation
        $tempDir = public_path('uploads/tmp_zip_' . $uuid);
        $skinsPath = $tempDir . '/skins/' . $templateName;
        $jsonDir = $tempDir . '/json';

        if (!file_exists($skinsPath)) mkdir($skinsPath, 0777, true);
        if (!file_exists($jsonDir)) mkdir($jsonDir, 0777, true);

        // Copy assets to skins folder
        if (file_exists($assetsDir)) {
            $files = array_diff(scandir($assetsDir), ['.', '..']);
            foreach ($files as $file) {
                copy($assetsDir . '/' . $file, $skinsPath . '/' . $file);
            }
        }

        // Prepare legacy JSON
        $legacyJson = $template->legacy_json ?? [];
        $legacyJson['name'] = $templateName;
        $legacyJson['path'] = $templateName . '/';
        
        // Ensure ALL relative paths in legacy JSON point to correct skins folder
        // Use regex to catch any old path prefix (../skins/old_name/ or ../skins/Template_old_uuid/)
        if (isset($legacyJson['layers'])) {
            foreach ($legacyJson['layers'] as &$layer) {
                if ($layer['type'] === 'image' && isset($layer['src'])) {
                    if (!str_starts_with($layer['src'], 'data:') && !str_starts_with($layer['src'], 'http')) {
                        $basename = basename($layer['src']);
                        // Always rewrite to the canonical path
                        $layer['src'] = '../skins/' . $templateName . '/' . $basename;
                    }
                }
            }
        }
        
        file_put_contents($jsonDir . '/' . $templateName . '.json', json_encode($legacyJson, JSON_PRETTY_PRINT));

        // Create Zip
        $zipDir = public_path('uploads/custom_frames_zips');
        if (!file_exists($zipDir)) mkdir($zipDir, 0777, true);
        
        $zipPath = $zipDir . '/' . $templateName . '.zip';
        $zip = new \ZipArchive();
        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) === TRUE) {
            $zip->addFile($jsonDir . '/' . $templateName . '.json', 'json/' . $templateName . '.json');
            
            $files = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($skinsPath),
                \RecursiveIteratorIterator::LEAVES_ONLY
            );

            foreach ($files as $name => $file) {
                if (!$file->isDir()) {
                    $filePath = $file->getRealPath();
                    $relativePath = 'skins/' . $templateName . '/' . basename($filePath);
                    $zip->addFile($filePath, $relativePath);
                }
            }
            $zip->close();
        }

        // Use File::copyDirectory to avoid Windows directory lock (Access Denied) issues after ZipArchive/Iterators
        $extractPath = public_path('uploads/template/' . $templateName);
        if (file_exists($extractPath)) {
            $this->deleteDirectory($extractPath);
        }
        \Illuminate\Support\Facades\File::copyDirectory($tempDir, $extractPath);
        $this->deleteDirectory($tempDir);

        // Copy thumbnail for legacy
        if ($template->thumbnail_path) {
            $thumbExt = pathinfo($template->thumbnail_path, PATHINFO_EXTENSION);
            copy(public_path($template->thumbnail_path), $zipDir . '/' . $templateName . '_thumb.' . $thumbExt);
            copy(public_path($template->thumbnail_path), $extractPath . '/preview.' . $thumbExt);
        }

        // Store in legacy database if not exists
        $frame = $existingFrame;
        if (!$frame) {
            $frame = \App\Models\BusinessCustomFrame::where('zip_file_path', $templateName . '.zip')->first();
            if (!$frame) {
                $frame = new \App\Models\BusinessCustomFrame();
                
                // Validate purposeId exists, else fallback to first available
                $validPurpose = \DB::table('custom_frame_purposes')->where('id', $purposeId)->exists();
                if (!$validPurpose) {
                    $firstPurpose = \DB::table('custom_frame_purposes')->first();
                    $purposeId = $firstPurpose ? $firstPurpose->id : 1;
                }
                
                $frame->custom_frame_purpose_id = $purposeId;
                $frame->custom_frame_image_type_id = $imageTypeId;
            }
        }
        
        $frame->zip_file_path = $templateName . '.zip';
        if (!$existingFrame) {
            $frame->original_zip_name = $templateName . '.zip';
        }
        // Map ai_roles and ai_fields from EditorTemplate->schema_json back to BusinessCustomFrame->json_rules
        // This ensures the mobile app's legacy AI generation can read the AI constraints
        $jsonRules = ['layers' => [], 'ai_global_rule' => ''];
        if (isset($template->schema_json['elements'])) {
            foreach ($template->schema_json['elements'] as $element) {
                if (($element['type'] === 'text' || $element['type'] === 'i-text' || $element['type'] === 'textbox') && !empty($element['ai_role'])) {
                    $jsonRules['layers'][] = [
                        'name' => $element['name'] ?? 'layer',
                        'type' => 'text',
                        'text' => $element['text'] ?? '',
                        'ai_role' => $element['ai_role'],
                        'ai_field' => $element['ai_field'] ?? '',
                        'ai_max_chars' => $element['ai_max_chars'] ?? 50,
                        'ai_priority' => $element['ai_priority'] ?? 5,
                    ];
                }
            }
        }

        $frame->json_rules = json_encode($jsonRules);
        $frame->status = 1;
        $frame->show_on_landing = 0;
        $frame->updated_at = now(); // Force update timestamp even if no column changed
        $frame->save();
        \Log::info('BusinessCustomFrame SAVED: id=' . $frame->id . ' zip_file_path=' . $frame->zip_file_path . ' updated_at=' . $frame->updated_at);
    }

    private function deleteDirectory($dir) {
        return \Illuminate\Support\Facades\File::deleteDirectory($dir);
    }

    public function saveFrame(Request $request)
    {
        $request->validate([
            'schema_json' => 'required',
            'title' => 'required|string',
            'thumbnail' => 'required',
            'poster_category_id' => 'required',
            'template_type' => 'required',
        ]);

        $schemaJson = is_string($request->input('schema_json')) ? json_decode($request->input('schema_json'), true) : $request->input('schema_json');
        $legacyJson = is_string($request->input('legacy_json')) ? json_decode($request->input('legacy_json', '[]'), true) : $request->input('legacy_json', []);

        $existingFrame = null;
        $existingTemplate = null;
        $uuid = null;

        if ($request->has('frame_id') && $request->input('frame_id')) {
            $existingFrame = \App\Models\PosterMaker::find($request->input('frame_id'));
            if ($existingFrame && $existingFrame->zip_name) {
                if (str_starts_with($existingFrame->zip_name, 'Template_')) {
                    $oldUuid = str_replace(['Template_', '.zip'], '', $existingFrame->zip_name);
                    $existingTemplate = \App\Models\EditorTemplate::where('uuid', $oldUuid)->first();
                    if ($existingTemplate) {
                        $uuid = $oldUuid;
                    }
                }
                // Fallback: try matching by title if UUID extraction failed
                if (!$existingTemplate) {
                    // First try exact match with the raw title from the form
                    $existingTemplate = \App\Models\EditorTemplate::where('title', $request->input('title'))
                        ->orderByDesc('updated_at')
                        ->first();
                    // Then try normalized fuzzy match against zip_name
                    if (!$existingTemplate) {
                        $normalizedZipName = strtolower(preg_replace('/[^a-z0-9]/i', '', $existingFrame->zip_name));
                        $existingTemplate = \App\Models\EditorTemplate::whereRaw(
                            "LOWER(REPLACE(REPLACE(REPLACE(REPLACE(title, ' ', ''), '-', ''), '_', ''), '.', '')) = ?",
                            [$normalizedZipName]
                        )->orderByDesc('updated_at')->first();
                    }
                    if ($existingTemplate) {
                        $uuid = $existingTemplate->uuid;
                    }
                }
            }
        }

        if (!$uuid) {
            $uuid = \Illuminate\Support\Str::uuid()->toString();
        }

        $templateDir = public_path('uploads/editor/templates/' . $uuid);
        $assetsDir = $templateDir . '/assets';

        if (!file_exists($assetsDir)) {
            mkdir($assetsDir, 0777, true);
        }

        if (isset($schemaJson['elements']) && is_array($schemaJson['elements'])) {
            foreach ($schemaJson['elements'] as &$el) {
                if ($el['type'] === 'image' && isset($el['src']) && str_starts_with($el['src'], 'data:image')) {
                    $parts = explode(';base64,', $el['src']);
                    if (count($parts) === 2) {
                        $typeAux = explode('image/', $parts[0]);
                        $ext = isset($typeAux[1]) ? ($typeAux[1] === 'jpeg' ? 'jpg' : $typeAux[1]) : 'png';
                        if (!in_array($ext, ['png', 'jpg', 'jpeg', 'webp'])) $ext = 'png';
                        $data = base64_decode($parts[1]);
                        if ($data !== false) {
                            $filename = uniqid('asset_') . '.' . $ext;
                            file_put_contents($assetsDir . '/' . $filename, $data);
                            $el['src'] = 'assets/' . $filename;
                        }
                    }
                }
            }
        }

        $thumbData = $request->input('thumbnail');
        $thumbPath = null;
        if (is_string($thumbData) && str_starts_with($thumbData, 'data:image')) {
            $parts = explode(';base64,', $thumbData);
            if (count($parts) === 2) {
                $data = base64_decode($parts[1]);
                if ($data !== false) {
                    $thumbName = 'thumb_' . time() . '.webp';
                    file_put_contents($templateDir . '/' . $thumbName, $data);
                    $thumbPath = 'uploads/editor/templates/' . $uuid . '/' . $thumbName;
                }
            }
        }

        if ($existingTemplate) {
            $existingTemplate->title = $request->input('title');
            $existingTemplate->canvas_width = $schemaJson['canvas']['width'] ?? 1080;
            $existingTemplate->canvas_height = $schemaJson['canvas']['height'] ?? 1080;
            $existingTemplate->schema_json = $schemaJson;
            $existingTemplate->legacy_json = $legacyJson;
            if ($thumbPath) $existingTemplate->thumbnail_path = $thumbPath;
            $existingTemplate->save();
            $template = $existingTemplate;
        } else {
            $template = new \App\Models\EditorTemplate();
            $template->uuid = $uuid;
            $template->title = $request->input('title');
            $template->canvas_width = $schemaJson['canvas']['width'] ?? 1080;
            $template->canvas_height = $schemaJson['canvas']['height'] ?? 1080;
            $template->schema_json = $schemaJson;
            $template->legacy_json = $legacyJson;
            $template->thumbnail_path = $thumbPath;
            $template->status = 'published';
            $template->author_id = auth()->id() ?? 1;
            $template->save();
        }

        // Generate Legacy Zip structure
        $titleRaw = $request->input('title');
        $titleSanitized = preg_replace('/[^A-Za-z0-9_-]/', '_', trim($titleRaw));
        
        if (!empty($titleSanitized) && strtolower($titleSanitized) !== 'my_custom_template') {
            $templateName = $titleSanitized;
            // Prevent exact collisions with other distinct frames
            $conflict = \App\Models\PosterMaker::where('zip_name', $templateName)
                ->where('id', '!=', $existingFrame ? $existingFrame->id : 0)
                ->exists();
            if ($conflict) {
                $templateName .= '_' . substr($uuid, 0, 6);
            }
        } else {
            $templateName = "Template_" . $uuid;
        }
        $tempDir = public_path('uploads/tmp_zip_' . $uuid);
        $skinsPath = $tempDir . '/skins/' . $templateName;
        $jsonDir = $tempDir . '/json';

        if (!file_exists($skinsPath)) mkdir($skinsPath, 0777, true);
        if (!file_exists($jsonDir)) mkdir($jsonDir, 0777, true);

        // Copy old frame images to the new skins directory if it's an existing frame
        if ($existingFrame && $existingFrame->zip_name) {
            $oldExtracted = public_path('uploads/template/' . $existingFrame->zip_name . '/skins');
            if (is_dir($oldExtracted)) {
                try {
                    $oldFiles = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($oldExtracted, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::LEAVES_ONLY);
                    foreach ($oldFiles as $name => $file) {
                        if ($file->isFile()) {
                            @copy($file->getRealPath(), $skinsPath . '/' . $file->getFilename());
                        }
                    }
                } catch (\Exception $e) {
                    \Log::warning('Could not copy old skins for frame: ' . $e->getMessage());
                }
            }
        }

        if (file_exists($assetsDir) && is_dir($assetsDir)) {
            $files = array_diff(scandir($assetsDir), ['.', '..']);
            foreach ($files as $file) {
                if (is_file($assetsDir . '/' . $file)) {
                    @copy($assetsDir . '/' . $file, $skinsPath . '/' . $file);
                }
            }
        }

        $legacyJson['name'] = $templateName;
        $legacyJson['path'] = $templateName . '/';
        
        if (isset($legacyJson['layers']) && is_array($legacyJson['layers'])) {
            foreach ($legacyJson['layers'] as &$layer) {
                if ($layer['type'] === 'image' && isset($layer['src'])) {
                    if (str_starts_with($layer['src'], 'data:image')) {
                        $parts = explode(';base64,', $layer['src']);
                        if (count($parts) === 2) {
                            $typeAux = explode('image/', $parts[0]);
                            $ext = isset($typeAux[1]) ? ($typeAux[1] === 'jpeg' ? 'jpg' : $typeAux[1]) : 'png';
                            if (!in_array($ext, ['png', 'jpg', 'jpeg', 'webp'])) $ext = 'png';
                            $data = base64_decode($parts[1]);
                            if ($data !== false) {
                                $filename = uniqid('shape_') . '.' . $ext;
                                file_put_contents($skinsPath . '/' . $filename, $data);
                                $layer['src'] = '../skins/' . $templateName . '/' . $filename;
                            }
                        }
                    } elseif (str_starts_with($layer['src'], 'http')) {
                        $copied = false;
                        if (strpos($layer['src'], 'uploads/') !== false) {
                            $parts = explode('uploads/', $layer['src']);
                            if (isset($parts[1])) {
                                $relPath = 'uploads/' . strtok($parts[1], '?');
                                $localPath = public_path($relPath);
                                if (file_exists($localPath) && is_file($localPath)) {
                                    $filename = uniqid('sticker_') . '_' . basename($localPath);
                                    @copy($localPath, $skinsPath . '/' . $filename);
                                    $layer['src'] = '../skins/' . $templateName . '/' . $filename;
                                    $copied = true;
                                }
                            }
                        }
                        if (!$copied) {
                            $filename = uniqid('sticker_') . '_' . basename(parse_url($layer['src'], PHP_URL_PATH));
                            try {
                                $imgData = @file_get_contents($layer['src']);
                                if ($imgData) {
                                    file_put_contents($skinsPath . '/' . $filename, $imgData);
                                    $layer['src'] = '../skins/' . $templateName . '/' . $filename;
                                }
                            } catch (\Exception $e) { }
                        }
                    } else {
                        $basename = basename($layer['src']);
                        $layer['src'] = '../skins/' . $templateName . '/' . $basename;
                    }
                }
            }
        }
        file_put_contents($jsonDir . '/' . $templateName . '.json', json_encode($legacyJson, JSON_PRETTY_PRINT));

        // Create Zip
        $zipDir = public_path('uploads/custom_frames_zips');
        if (!file_exists($zipDir)) mkdir($zipDir, 0777, true);
        
        $zipPath = $zipDir . '/' . $templateName . '.zip';
        $zip = new \ZipArchive();
        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) === TRUE) {
            $zip->addFile($jsonDir . '/' . $templateName . '.json', 'json/' . $templateName . '.json');
            try {
                $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($skinsPath, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::LEAVES_ONLY);
                foreach ($files as $name => $file) {
                    if ($file->isFile()) {
                        $filePath = $file->getRealPath();
                        $relativePath = 'skins/' . $templateName . '/' . basename($filePath);
                        $zip->addFile($filePath, $relativePath);
                    }
                }
            } catch (\Exception $e) {
                \Log::error('Error adding skins to zip in saveFrame: ' . $e->getMessage());
            }
            $zip->close();
        } else {
            \Log::error('Could not create ZipArchive at path: ' . $zipPath);
        }

        $extractPath = public_path('uploads/template/' . $templateName);
        if (file_exists($extractPath)) {
            \Illuminate\Support\Facades\File::deleteDirectory($extractPath);
        }
        \Illuminate\Support\Facades\File::copyDirectory($tempDir, $extractPath);
        \Illuminate\Support\Facades\File::deleteDirectory($tempDir);

        // Thumbnail for preview and custom_frames_zips
        if ($template->thumbnail_path && file_exists(public_path($template->thumbnail_path))) {
            $thumbExt = pathinfo($template->thumbnail_path, PATHINFO_EXTENSION);
            @copy(public_path($template->thumbnail_path), $extractPath . '/preview.' . $thumbExt);
            @copy(public_path($template->thumbnail_path), $zipDir . '/' . $templateName . '_thumb.' . $thumbExt);
        }
        
        // Digital Ocean upload if needed
        if (\App\Models\StorageSetting::getStorageSetting('storage') == 'DigitalOcean') {
            try {
                if (file_exists($zipPath)) {
                    \Illuminate\Support\Facades\Storage::disk('spaces')->putFileAs('uploads/custom_frames_zips', new \Illuminate\Http\File($zipPath), $templateName . '.zip', 'public');
                }
                if ($thumbPath && file_exists(public_path($thumbPath))) {
                    $thumbFile = new \Illuminate\Http\File(public_path($thumbPath));
                    \Illuminate\Support\Facades\Storage::disk('spaces')->putFileAs('uploads/editor/templates/' . $uuid, $thumbFile, basename($thumbPath), 'public');
                    \Illuminate\Support\Facades\Storage::disk('spaces')->putFileAs('uploads', $thumbFile, basename($thumbPath), 'public');
                }
            } catch (\Exception $e) {
                \Log::error('DigitalOcean upload failed: ' . $e->getMessage());
            }
        }

        if ($existingFrame) {
            $existingFrame->poster_category_id = $request->input('poster_category_id');
            $existingFrame->template_type = $request->input('template_type');
            $existingFrame->theme = 'all';
            $existingFrame->req_address = $request->input('req_address', 0);
            $existingFrame->req_email = $request->input('req_email', 0);
            $existingFrame->req_phone = $request->input('req_phone', 0);
            $existingFrame->req_website = $request->input('req_website', 0);
            
            if ($thumbPath) {
                $existingFrame->post_thumb = str_replace('uploads/', '', $thumbPath);
            }
            
            if ($templateName && $templateName !== $existingFrame->zip_name) {
                $existingFrame->zip_name = $templateName;
            }
            $existingFrame->save();
        } else {
            $existingFrame = \App\Models\PosterMaker::create([
                'poster_category_id' => $request->input('poster_category_id'),
                'template_type' => $request->input('template_type'),
                'zip_name' => $templateName,
                'theme' => 'all',
                'req_address' => $request->input('req_address', 0),
                'req_email' => $request->input('req_email', 0),
                'req_phone' => $request->input('req_phone', 0),
                'req_website' => $request->input('req_website', 0),
                'post_thumb' => $thumbPath ? str_replace('uploads/', '', $thumbPath) : null,
                'paid' => 1
            ]);
        }
        
        \Illuminate\Support\Facades\Cache::forget("template_json:{$templateName}");
        \Illuminate\Support\Facades\Cache::forget("template_json:{$templateName}.zip");

        return response()->json(['success' => true, 'message' => 'Frame saved successfully!', 'uuid' => $uuid, 'frame_id' => $existingFrame ? $existingFrame->id : null]);
    }
}
