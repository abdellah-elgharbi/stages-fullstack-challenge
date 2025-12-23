<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImageUploadController extends Controller
{
    /**
     * Handle image upload with optimization (single WebP file).
     */
    public function upload(Request $request)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:20480',
        ]);

        if (!$request->hasFile('image')) {
            return response()->json(['error' => 'No image provided'], 400);
        }

        $uploadedFile = $request->file('image');
        $extension = $uploadedFile->getClientOriginalExtension();
        $filename = Str::random(20);
        
        try {
            // Create image resource
            $sourceImage = $this->createImageResource($uploadedFile->getRealPath(), $extension);
            
            if (!$sourceImage) {
                return response()->json(['error' => 'Failed to process image'], 500);
            }

            // Get dimensions
            $originalWidth = imagesx($sourceImage);
            $originalHeight = imagesy($sourceImage);
            
            // Calculate new dimensions (max 1200px)
            $maxWidth = 1200;
            if ($originalWidth > $maxWidth) {
                $ratio = $maxWidth / $originalWidth;
                $newWidth = $maxWidth;
                $newHeight = (int)($originalHeight * $ratio);
            } else {
                $newWidth = $originalWidth;
                $newHeight = $originalHeight;
            }
            
            // Create optimized image
            $optimizedImage = imagecreatetruecolor($newWidth, $newHeight);
            
            // Preserve transparency
            if (in_array($extension, ['png', 'gif'])) {
                imagealphablending($optimizedImage, false);
                imagesavealpha($optimizedImage, true);
                $transparent = imagecolorallocatealpha($optimizedImage, 255, 255, 255, 127);
                imagefilledrectangle($optimizedImage, 0, 0, $newWidth, $newHeight, $transparent);
            }
            
            // Resize
            imagecopyresampled(
                $optimizedImage, $sourceImage,
                0, 0, 0, 0,
                $newWidth, $newHeight,
                $originalWidth, $originalHeight
            );
            
            // Save only WebP version
            $storagePath = storage_path('app/public/images');
            if (!file_exists($storagePath)) {
                mkdir($storagePath, 0755, true);
            }
            
            $webpPath = $storagePath . '/' . $filename . '.webp';
            imagewebp($optimizedImage, $webpPath, 80);
            
            $webpSize = filesize($webpPath);
            $originalSize = $uploadedFile->getSize();
            
            // Clean up
            imagedestroy($sourceImage);
            imagedestroy($optimizedImage);
            
            return response()->json([
                'message' => 'Image uploaded and optimized successfully',
                'path' => 'images/' . $filename . '.webp',
                'url' => '/storage/images/' . $filename . '.webp',
                'size' => $webpSize,
                'optimization' => [
                    'original_size' => $originalSize,
                    'optimized_size' => $webpSize,
                    'savings_percent' => round((1 - $webpSize / $originalSize) * 100, 1),
                ],
                'dimensions' => [
                    'width' => $newWidth,
                    'height' => $newHeight,
                    'original_width' => $originalWidth,
                    'original_height' => $originalHeight,
                ],
            ], 201);
            
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    private function createImageResource($path, $extension)
    {
        switch (strtolower($extension)) {
            case 'jpg':
            case 'jpeg':
                return imagecreatefromjpeg($path);
            case 'png':
                return imagecreatefrompng($path);
            case 'gif':
                return imagecreatefromgif($path);
            default:
                return false;
        }
    }

    /**
     * Delete an uploaded image.
     */
    public function delete(Request $request)
    {
        $request->validate([
            'path' => 'required|string',
        ]);

        $path = $request->input('path');

        if (Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
            return response()->json(['message' => 'Image deleted successfully']);
        }

        return response()->json(['error' => 'Image not found'], 404);
    }
}
