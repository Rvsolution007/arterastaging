<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\EditorFont;
use App\Models\EditorAsset;
use App\Models\StickerCategory;
use App\Models\Sticker;

class EditorDataController extends Controller
{
    public function getFonts()
    {
        $fonts = EditorFont::orderBy('name')->get();
        return response()->json([
            'success' => true,
            'data' => $fonts
        ]);
    }

    public function getAssets(Request $request)
    {
        $query = EditorAsset::query();
        
        if ($request->has('type')) {
            $query->where('type', $request->input('type'));
        }
        
        $assets = $query->orderBy('name')->get();
        return response()->json([
            'success' => true,
            'data' => $assets
        ]);
    }

    public function getStickers(Request $request)
    {
        $query = StickerCategory::where('status', 1);
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'LIKE', '%' . $search . '%')
                  ->orWhereHas('sticker', function($sq) use ($search) {
                      $sq->where('keywords', 'LIKE', '%' . $search . '%');
                  });
            });
        }
        
        $categories = $query->with(['sticker' => function($q) {
            $q->where('status', 1);
        }])->get();
        
        $data = [];
        $searchTerm = $request->has('search') ? strtolower($request->search) : '';

        foreach ($categories as $cat) {
            $stickersList = [];
            $categoryMatches = $searchTerm === '' || str_contains(strtolower($cat->name), $searchTerm);

            foreach ($cat->sticker as $s) {
                $stickerMatches = $searchTerm === '' || str_contains(strtolower((string)$s->keywords), $searchTerm);

                if ($categoryMatches || $stickerMatches) {
                    $stickersList[] = [
                        'id' => $s->id,
                        'url' => ($s->image) ? ((\App\Models\StorageSetting::getStorageSetting('storage') == 'DigitalOcean') ? \Illuminate\Support\Facades\Storage::disk('spaces')->url('uploads/'.$s->image) : asset('uploads/'.$s->image)) : ''
                    ];
                }
            }
            if (count($stickersList) > 0) {
                $data[] = [
                    'category_id' => $cat->id,
                    'category_name' => $cat->name,
                    'stickers' => $stickersList
                ];
            }
        }
        
        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }
}
