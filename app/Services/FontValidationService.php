<?php

namespace App\Services;

use ZipArchive;
use App\Models\Font;
use App\Models\EditorFont;

class FontValidationService
{
    /**
     * Validate if all fonts used in the JSON files inside a ZIP archive exist in the central database.
     * 
     * @param string $zipFilePath Absolute path to the ZIP file.
     * @return array|bool Returns true if all fonts exist, or an array of missing font names if validation fails.
     */
    public static function validateZipFonts($zipFilePath)
    {
        $zip = new ZipArchive;
        if ($zip->open($zipFilePath) === TRUE) {
            $requiredFonts = [];

            // Scan all files in the ZIP for .json files
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $filename = $zip->getNameIndex($i);
                
                // If it's a JSON file (in root or in json/ folder)
                if (pathinfo($filename, PATHINFO_EXTENSION) === 'json' && !str_starts_with($filename, '__MACOSX')) {
                    $jsonContent = $zip->getFromIndex($i);
                    $data = json_decode($jsonContent, true);

                    if ($data && is_array($data)) {
                        $fontsInFile = self::extractFontsFromJson($data);
                        $requiredFonts = array_merge($requiredFonts, $fontsInFile);
                    }
                }
            }
            $zip->close();

            $requiredFonts = array_unique($requiredFonts);

            if (empty($requiredFonts)) {
                return true; // No fonts required
            }

            $missingFonts = [];
            foreach ($requiredFonts as $fontName) {
                // Check if font exists in either Font or EditorFont tables
                $existsInFont = Font::where('name', $fontName)->exists();
                $existsInEditorFont = EditorFont::where('name', $fontName)->exists();

                if (!$existsInFont && !$existsInEditorFont) {
                    $missingFonts[] = $fontName;
                }
            }

            if (!empty($missingFonts)) {
                return $missingFonts;
            }

            return true;
        }

        return ['Unable to open ZIP file for font validation.'];
    }

    /**
     * Recursively extract all "font" keys from a JSON array.
     */
    private static function extractFontsFromJson($data)
    {
        $fonts = [];

        foreach ($data as $key => $value) {
            if ($key === 'font' && !empty($value)) {
                $fonts[] = $value;
            }

            if (is_array($value)) {
                $fonts = array_merge($fonts, self::extractFontsFromJson($value));
            }
        }

        return $fonts;
    }
}
