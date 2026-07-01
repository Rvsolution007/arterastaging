<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\EditorFont;

class EditorFontsSeeder extends Seeder
{
    public function run()
    {
        $fonts = [
            ['name' => 'Roboto', 'family' => 'Roboto', 'file_path' => 'https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap'],
            ['name' => 'Open Sans', 'family' => 'Open Sans', 'file_path' => 'https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;700&display=swap'],
            ['name' => 'Lato', 'family' => 'Lato', 'file_path' => 'https://fonts.googleapis.com/css2?family=Lato:wght@400;700&display=swap'],
            ['name' => 'Montserrat', 'family' => 'Montserrat', 'file_path' => 'https://fonts.googleapis.com/css2?family=Montserrat:wght@400;700&display=swap'],
            ['name' => 'Oswald', 'family' => 'Oswald', 'file_path' => 'https://fonts.googleapis.com/css2?family=Oswald:wght@400;700&display=swap'],
            ['name' => 'Raleway', 'family' => 'Raleway', 'file_path' => 'https://fonts.googleapis.com/css2?family=Raleway:wght@400;700&display=swap'],
            ['name' => 'Poppins', 'family' => 'Poppins', 'file_path' => 'https://fonts.googleapis.com/css2?family=Poppins:wght@400;700&display=swap'],
            ['name' => 'Inter', 'family' => 'Inter', 'file_path' => 'https://fonts.googleapis.com/css2?family=Inter:wght@400;700&display=swap'],
        ];

        foreach ($fonts as $font) {
            EditorFont::updateOrCreate(['name' => $font['name']], $font);
        }
    }
}
