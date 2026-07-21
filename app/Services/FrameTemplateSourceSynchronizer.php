<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use ZipArchive;

/**
 * Keeps every persisted representation of a frame on the same JSON payload.
 *
 * The database layers_json is the canonical runtime payload because it is also
 * the payload returned to the native app API.  ZIP files are distribution and
 * editor assets only; they must never become an alternate source of truth.
 */
class FrameTemplateSourceSynchronizer
{
    public function canonicalZipEntry(string $zipName): string
    {
        return 'json/' . $zipName . '.json';
    }

    public function canonicalJsonPath(string $zipName): string
    {
        return public_path('uploads/template/' . $zipName . '/json/' . $zipName . '.json');
    }

    public function decodeTemplateJson($json): ?array
    {
        if (is_array($json)) {
            return $this->isTemplateJson($json) ? $json : null;
        }

        if (!is_string($json) || trim($json) === '') {
            return null;
        }

        $decoded = json_decode($json, true);

        return is_array($decoded) && $this->isTemplateJson($decoded) ? $decoded : null;
    }

    public function canonicalJson(string $zipName, $layersJson = null): ?array
    {
        $databaseJson = $this->decodeTemplateJson($layersJson);
        if ($databaseJson !== null) {
            return $databaseJson;
        }

        $path = $this->canonicalJsonPath($zipName);
        if (!File::exists($path)) {
            return null;
        }

        return $this->decodeTemplateJson(File::get($path));
    }

    /**
     * Writes the canonical JSON file and updates every existing archive that
     * can later be opened by the web editor.  Frame-config JSON entries are
     * removed first so an archive never has competing template definitions.
     */
    public function synchronize(string $zipName, array $json): string
    {
        $jsonString = json_encode($json, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);

        if ($jsonString === false) {
            throw new \RuntimeException("Unable to encode canonical JSON for frame {$zipName}.");
        }

        $jsonPath = $this->canonicalJsonPath($zipName);
        File::ensureDirectoryExists(dirname($jsonPath));
        File::put($jsonPath, $jsonString);

        foreach ([
            public_path('uploads/custom_frames_zips/' . $zipName . '.zip'),
            public_path('uploads/template/' . $zipName . '.zip'),
        ] as $archivePath) {
            if (File::exists($archivePath)) {
                $this->synchronizeArchive($archivePath, $zipName, $jsonString);
            }
        }

        return $jsonString;
    }

    private function synchronizeArchive(string $archivePath, string $zipName, string $jsonString): void
    {
        $zip = new ZipArchive();
        if ($zip->open($archivePath) !== true) {
            throw new \RuntimeException("Unable to open frame archive {$archivePath}.");
        }

        // Delete in reverse order: ZipArchive indexes move after each deletion.
        for ($index = $zip->numFiles - 1; $index >= 0; $index--) {
            $entryName = $zip->getNameIndex($index);
            if (strtolower(pathinfo($entryName, PATHINFO_EXTENSION)) !== 'json') {
                continue;
            }

            $entryJson = $this->decodeTemplateJson($zip->getFromIndex($index));
            if ($entryJson !== null) {
                $zip->deleteIndex($index);
            }
        }

        if (!$zip->addFromString($this->canonicalZipEntry($zipName), $jsonString)) {
            $zip->close();
            throw new \RuntimeException("Unable to write canonical JSON to frame archive {$archivePath}.");
        }

        $zip->close();
    }

    private function isTemplateJson(array $json): bool
    {
        return isset($json['layers']) || isset($json['objects']) || isset($json['elements']);
    }
}
