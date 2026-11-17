<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\MediaLibraryUploader;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Image upload for the GrapesJS page builder's Asset Manager. Files land in the
 * media library like any other upload, so they show up in Admin → Media too.
 * Answers in the shape GrapesJS expects: {data: [{src, name}]}.
 */
class BuilderAssetController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'files' => ['required', 'array', 'max:10'],
            'files.*' => ['file', 'image', 'mimes:jpg,jpeg,png,gif,webp,svg', 'max:8192'],
        ]);

        $assets = [];

        foreach ($request->file('files') as $file) {
            $path = $file->store('media', 'public');

            $media = MediaLibraryUploader::store(
                $path,
                $file->getClientOriginalName(),
                $file->getMimeType() ?: 'image/jpeg',
                $file->getSize(),
                $request->user()?->id,
            );

            $assets[] = ['src' => $media->url, 'name' => $media->original_filename, 'type' => 'image'];
        }

        return response()->json(['data' => $assets]);
    }
}
