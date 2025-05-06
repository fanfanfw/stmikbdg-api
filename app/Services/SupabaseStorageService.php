<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class SupabaseStorageService
{
    private $baseUrl;
    private $apiKey;
    private $bucket;

    public function __construct()
    {
        $this->baseUrl = rtrim(env('SUPABASE_URL'), '/');
        $this->apiKey = env('SUPABASE_KEY');
        $this->bucket = 'images'; // adjust as needed
    }

    public function put($file)
    {
        $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
        $path = "{$this->bucket}/{$filename}";

        $response = Http::withHeaders([
            'apikey' => $this->apiKey,
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type' => 'application/octet-stream',
        ])->put("{$this->baseUrl}/storage/v1/object/{$path}", file_get_contents($file));

        if (!$response->successful()) {
            throw new \Exception('Failed to upload file to Supabase');
        }

        return $filename;
    }

    public function url($filename)
    {
        return "{$this->baseUrl}/storage/v1/object/public/{$this->bucket}/{$filename}";
    }
}
