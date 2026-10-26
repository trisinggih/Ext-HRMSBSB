<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * FaceMatchHelper
 *
 * Membandingkan dua foto wajah menggunakan Face++ (Megvii) API.
 * Tingkat akurasi sangat tinggi dan cepat.
 */
class FaceMatchHelper
{
    /** Threshold similarity default (0.0 - 1.0) */
    public const DEFAULT_THRESHOLD = 0.70;

    /**
     * Bandingkan foto referensi dengan foto incoming via Face++ API.
     *
     * @param string $referencePath  Path absolut ke foto referensi (di storage)
     * @param string $incomingBase64 Data base64 foto incoming dari mobile
     * @return float Similarity antara 0.0 (beda total) dan 1.0 (identik)
     */
    public static function compare(string $referencePath, string $incomingBase64): float
    {
        $apiKey = config('services.faceplusplus.key');
        $apiSecret = config('services.faceplusplus.secret');

        // Jika API Key belum diset, return 0 atau bisa kita bypass untuk mode dev
        if (empty($apiKey) || empty($apiSecret)) {
            Log::warning('Face++ API Key is missing. Returning 0 similarity.');
            return 0.0;
        }

        // 1. Siapkan Reference Image (dari Storage Path)
        if (!file_exists($referencePath)) {
            Log::error('Face++: Reference image not found at ' . $referencePath);
            return 0.0;
        }
        $refData = file_get_contents($referencePath);
        $refBase64 = base64_encode($refData);

        // 2. Siapkan Incoming Image
        if (str_contains($incomingBase64, ',')) {
            $incomingBase64 = explode(',', $incomingBase64, 2)[1];
        }

        // 3. Panggil API Face++
        try {
            $response = Http::asForm()->post('https://api-us.faceplusplus.com/facepp/v3/compare', [
                'api_key' => $apiKey,
                'api_secret' => $apiSecret,
                'image_base64_1' => $refBase64,
                'image_base64_2' => $incomingBase64,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                // Jika request sukses dan ada nilai confidence
                if (isset($data['confidence'])) {
                    // Face++ mengembalikan confidence 0 - 100
                    return (float) $data['confidence'] / 100.0;
                } else {
                    Log::error('Face++ API Error: ' . $response->body());
                }
            } else {
                Log::error('Face++ HTTP Error: ' . $response->body());
            }
        } catch (\Exception $e) {
            Log::error('Face++ Exception: ' . $e->getMessage());
        }

        return 0.0;
    }

    /**
     * Apakah similarity di atas threshold (wajah cocok)?
     */
    public static function isMatch(string $referencePath, string $incomingBase64, float $threshold = self::DEFAULT_THRESHOLD): bool
    {
        return self::compare($referencePath, $incomingBase64) >= $threshold;
    }
}
