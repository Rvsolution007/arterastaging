<?php

namespace App\Http\Controllers\Admin;

use Auth;
use App\Models\Language;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\GeneralPost;
use App\Models\ZipFileManager;
use App\Models\StorageSetting;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use ZipArchive;
use File;

class ZipFileManagerController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:BusinessFrame');
    }

    public function index()
    {
        $data['data'] = ZipFileManager::orderBy('id', 'DESC')->paginate(10);
        return view("zip_file_manager.index", $data);
    }

    public function create()
    {
        return view("zip_file_manager.create");
    }

    public function store(Request $request)
    {
        $validation = Validator::make($request->all(), [
            "zip_file" => "required|file|mimes:zip",
        ]);

        if ($validation->fails()) {
            return back()->withErrors($validation)->withInput();
        }

        if ($request->hasFile('zip_file')) {
            $zipFile = $request->file('zip_file');
            $originalName = $zipFile->getClientOriginalName();
            $fileName = Str::uuid() . '.zip';

            $validationResult = \App\Services\FontValidationService::validateZipFonts($zipFile->getRealPath());
            if ($validationResult !== true) {
                $errorMessage = 'Upload failed! The following fonts are missing in the central system: ' . implode(', ', $validationResult) . '. Please upload them to the Font Section first.';
                return back()->withErrors(['zip_file' => $errorMessage])->withInput();
            }

            if (StorageSetting::getStorageSetting("storage") == "DigitalOcean") {
                Storage::disk('spaces')->put('uploads/zips/' . $fileName, file_get_contents($zipFile), 'public');
            } else {
                $destinationPath = public_path('uploads/zips');
                if (!file_exists($destinationPath)) {
                    mkdir($destinationPath, 0777, true);
                }
                $zipFile->move($destinationPath, $fileName);
            }

            $zipManager = ZipFileManager::create([
                'file_name' => $originalName,
                'zip_file' => $fileName,
            ]);

            $zip = new ZipArchive;
            $res = $zip->open($zipFile->getPathname()); // Re-opening from temp for processing
            if ($res === TRUE) {
                $extractPath = public_path('temp_zip_' . time());
                if (!file_exists($extractPath)) {
                    mkdir($extractPath, 0777, true);
                }
                $zip->extractTo($extractPath);
                $zip->close();

                $files = File::allFiles($extractPath);
                foreach ($files as $file) {
                    if (in_array(strtolower($file->getExtension()), ['jpg', 'jpeg', 'png'])) {
                        // Skip images inside template structure folders (skins/, fonts/, json/)
                        $relativePath = str_replace($extractPath, '', $file->getPathname());
                        $relativePath = str_replace('\\', '/', $relativePath);
                        if (preg_match('#/(skins|fonts|json)/#i', $relativePath)) {
                            continue;
                        }
                        $this->createPost($file->getPathname(), $zipManager->id);
                    }
                }
                File::deleteDirectory($extractPath);
            }

            return redirect()->route("zip-file-manager.index")->with('success', 'ZIP uploaded and processed successfully.');
        }

        return back()->with('error', 'Please select a ZIP file.')->withInput();
    }

    private function createPost($file, $zipFileId)
    {
        $filePath = $file;
        $extension = pathinfo($file, PATHINFO_EXTENSION);

        $size = getimagesize($filePath);
        if (!$size)
            return;

        $type = "square";
        if ($size[0] > $size[1])
            $type = "landscape";
        elseif ($size[0] < $size[1])
            $type = "portrait";

        $post = GeneralPost::create([
            "user_id" => Auth::user()->id,
            "paid" => 1,
            "height" => $size[1],
            "width" => $size[0],
            "image_type" => $type,
            "aspect_ratio" => $this->getAspectRatio($size[0], $size[1]),
            "zip_file_id" => $zipFileId,
            "status" => 1
        ]);

        $fileName = Str::uuid() . '.' . $extension;

        if (StorageSetting::getStorageSetting("storage") == "DigitalOcean") {
            Storage::disk('spaces')->put('uploads/' . $fileName, file_get_contents($filePath), 'public');
            $post->frame_image = $fileName;
            $post->save();
        } else {
            $destinationPath = public_path('uploads');
            if (!file_exists($destinationPath)) {
                mkdir($destinationPath, 0777, true);
            }
            File::copy($filePath, $destinationPath . '/' . $fileName);
            $post->frame_image = $fileName;
            $post->save();
        }
    }

    public function show(Request $request, $id)
    {
        $zipManager = ZipFileManager::findOrFail($id);
        $currentPath = $request->get('path', '');
        $contents = ['folders' => [], 'files' => []];

        $localPath = null;
        if (StorageSetting::getStorageSetting("storage") == "DigitalOcean") {
            $tempZip = public_path('temp_' . $zipManager->zip_file);
            file_put_contents($tempZip, file_get_contents(Storage::disk('spaces')->url('uploads/zips/' . $zipManager->zip_file)));
            $localPath = $tempZip;
        } else {
            $localPath = public_path('uploads/zips/' . $zipManager->zip_file);
        }

        if (file_exists($localPath)) {
            $zip = new ZipArchive;
            if ($zip->open($localPath) === TRUE) {
                for ($i = 0; $i < $zip->numFiles; $i++) {
                    $name = $zip->getNameIndex($i);
                    $name = rtrim($name, '/');

                    // Skip if not in current path
                    if ($currentPath !== '') {
                        if (strpos($name, $currentPath . '/') !== 0)
                            continue;
                        $relativeName = substr($name, strlen($currentPath) + 1);
                    } else {
                        $relativeName = $name;
                    }

                    if ($relativeName === '')
                        continue;

                    $parts = explode('/', $relativeName);
                    $itemName = $parts[0];

                    if (count($parts) > 1) {
                        if (!in_array($itemName, $contents['folders'])) {
                            $contents['folders'][] = $itemName;
                        }
                    } else {
                        // Check if it's a directory entry in ZIP
                        $isDir = (substr($zip->getNameIndex($i), -1) == '/');
                        if ($isDir) {
                            if (!in_array($itemName, $contents['folders'])) {
                                $contents['folders'][] = $itemName;
                            }
                        } else {
                            $contents['files'][] = $itemName;
                        }
                    }
                }
                $zip->close();
            }
        }

        if (StorageSetting::getStorageSetting("storage") == "DigitalOcean" && isset($tempZip)) {
            @unlink($tempZip);
        }

        $data['zip'] = $zipManager;
        $data['currentPath'] = $currentPath;
        $data['folders'] = array_unique($contents['folders']);
        $data['files'] = array_unique($contents['files']);

        // Build breadcrumbs
        $breadcrumbs = [];
        if ($currentPath !== '') {
            $parts = explode('/', $currentPath);
            $cumulativePath = '';
            foreach ($parts as $part) {
                $cumulativePath = ($cumulativePath === '') ? $part : $cumulativePath . '/' . $part;
                $breadcrumbs[] = ['name' => $part, 'path' => $cumulativePath];
            }
        }
        $data['breadcrumbs'] = $breadcrumbs;

        return view("zip_file_manager.show", $data);
    }

    public function destroy($id)
    {
        $zipManager = ZipFileManager::findOrFail($id);

        // Delete zip file
        if (StorageSetting::getStorageSetting("storage") == "DigitalOcean") {
            Storage::disk('spaces')->delete('uploads/zips/' . $zipManager->zip_file);
        } else {
            if ($zipManager->zip_file && file_exists(public_path('uploads/zips/' . $zipManager->zip_file))) {
                unlink(public_path('uploads/zips/' . $zipManager->zip_file));
            }
        }

        $posts = GeneralPost::where('zip_file_id', $id)->get();
        foreach ($posts as $post) {
            if (StorageSetting::getStorageSetting("storage") == "DigitalOcean") {
                Storage::disk('spaces')->delete('uploads/' . $post->frame_image);
            } else {
                if ($post->frame_image && file_exists(public_path('uploads/' . $post->frame_image))) {
                    unlink(public_path('uploads/' . $post->frame_image));
                }
            }
            $post->delete();
        }

        $zipManager->delete();
        return redirect()->route('zip-file-manager.index')->with('success', 'ZIP record and associated files deleted.');
    }

    private function getAspectRatio(int $width, int $height)
    {
        $a = $width;
        $b = $height;
        while ($b != 0) {
            $t = $b;
            $b = $a % $b;
            $a = $t;
        }
        return $width / $a . ':' . $height / $a;
    }

    public function getZipLibrary()
    {
        $zips = ZipFileManager::orderBy('id', 'DESC')->get();
        return response()->json($zips);
    }

    public function ajaxStore(Request $request)
    {
        $validation = Validator::make($request->all(), [
            "zip_file" => "required|file|mimes:zip",
        ]);

        if ($validation->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation failed: ' . implode(', ', $validation->errors()->all())]);
        }

        if ($request->hasFile('zip_file')) {
            $zipFile = $request->file('zip_file');
            $originalName = $zipFile->getClientOriginalName();
            $fileName = Str::uuid() . '.zip';

            if (StorageSetting::getStorageSetting("storage") == "DigitalOcean") {
                Storage::disk('spaces')->put('uploads/zips/' . $fileName, file_get_contents($zipFile), 'public');
            } else {
                $destinationPath = public_path('uploads/zips');
                if (!file_exists($destinationPath)) {
                    mkdir($destinationPath, 0777, true);
                }
                $zipFile->move($destinationPath, $fileName);
            }

            $zipManager = ZipFileManager::create([
                'file_name' => $originalName,
                'zip_file' => $fileName,
            ]);

            $zip = new ZipArchive;
            $res = $zip->open(StorageSetting::getStorageSetting("storage") == "DigitalOcean" ? Storage::disk('spaces')->url('uploads/zips/' . $fileName) : public_path('uploads/zips/' . $fileName));

            // Re-opening from absolute path since temp file might be gone after move() in some envs
            // Actually let's use the temp path before move if possible, or just the new location.
            // Using the new location for consistency.

            $sourcePath = (StorageSetting::getStorageSetting("storage") == "DigitalOcean") ?
                Storage::disk('spaces')->url('uploads/zips/' . $fileName) :
                public_path('uploads/zips/' . $fileName);

            if (StorageSetting::getStorageSetting("storage") == "DigitalOcean") {
                $tempPath = public_path('temp_' . Str::uuid() . '.zip');
                file_put_contents($tempPath, file_get_contents($sourcePath));
                $zipRes = $zip->open($tempPath);
            } else {
                $zipRes = $zip->open($sourcePath);
            }

            if ($zipRes === TRUE) {
                $extractPath = public_path('temp_zip_' . time());
                if (!file_exists($extractPath)) {
                    mkdir($extractPath, 0777, true);
                }
                $zip->extractTo($extractPath);
                $zip->close();

                if (isset($tempPath) && file_exists($tempPath))
                    unlink($tempPath);

                $files = File::allFiles($extractPath);
                foreach ($files as $file) {
                    if (in_array(strtolower($file->getExtension()), ['jpg', 'jpeg', 'png'])) {
                        // Skip images inside template structure folders (skins/, fonts/, json/)
                        $relativePath = str_replace($extractPath, '', $file->getPathname());
                        $relativePath = str_replace('\\', '/', $relativePath);
                        if (preg_match('#/(skins|fonts|json)/#i', $relativePath)) {
                            continue;
                        }
                        $this->createPost($file->getPathname(), $zipManager->id);
                    }
                }
                File::deleteDirectory($extractPath);
            }

            return response()->json(['success' => true, 'message' => 'ZIP uploaded and processed.', 'zip' => $zipManager]);
        }

        return response()->json(['success' => false, 'message' => 'No file uploaded.']);
    }
}
