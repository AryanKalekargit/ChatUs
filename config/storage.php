<?php
// config/storage.php
// Supabase Storage helper – uploads a file to a Supabase Storage bucket
// and returns the public URL, or false on failure.

function uploadToSupabase(string $localFilePath, string $bucket, string $destPath): string|false {
    // Load env if not already loaded
    $supabaseUrl   = getenv('SUPABASE_URL');
    $supabaseKey   = getenv('SUPABASE_SERVICE_KEY');

    if (!$supabaseUrl || !$supabaseKey) {
        error_log("Supabase Storage: SUPABASE_URL or SUPABASE_SERVICE_KEY not set.");
        return false;
    }

    $fileContent = file_get_contents($localFilePath);
    if ($fileContent === false) return false;

    $finfo    = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($localFilePath);

    $uploadUrl = rtrim($supabaseUrl, '/') . "/storage/v1/object/{$bucket}/{$destPath}";

    $ch = curl_init($uploadUrl);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST  => 'PUT',
        CURLOPT_POSTFIELDS     => $fileContent,
        CURLOPT_HTTPHEADER     => [
            "Authorization: Bearer {$supabaseKey}",
            "Content-Type: {$mimeType}",
            "x-upsert: true",
        ],
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 200 || $httpCode === 201) {
        // Build public URL
        return rtrim($supabaseUrl, '/') . "/storage/v1/object/public/{$bucket}/{$destPath}";
    }

    error_log("Supabase Storage upload failed ({$httpCode}): {$response}");
    return false;
}
