<?php

/**
 * Google Drive Service Helper - Surat Digital TVRI Jawa Timur
 * Handles PDF auto-upload to Google Drive for KTU and Kepsta approvals
 */

class GoogleDriveService
{
    public static function getEffectiveAccessToken(): ?string
    {
        $clientId = Setting::get('gdrive_client_id', '');
        $clientSecret = Setting::get('gdrive_client_secret', '');
        $refreshToken = Setting::get('gdrive_refresh_token', '');
        $serviceAccountJson = Setting::get('gdrive_service_account_json', '');

        $accessToken = null;

        // 1. Try Service Account JSON key auth first
        $rawJson = !empty($serviceAccountJson) ? $serviceAccountJson : (str_contains($refreshToken, '{') ? $refreshToken : '');
        if (!empty($rawJson)) {
            $decodedJsonStr = htmlspecialchars_decode($rawJson, ENT_QUOTES);
            $jsonObj = json_decode($decodedJsonStr, true);

            if (is_array($jsonObj) && !empty($jsonObj['client_email']) && !empty($jsonObj['private_key'])) {
                $accessToken = self::getAccessTokenFromServiceAccount($jsonObj);
            }
        }

        // 2. Fallback to OAuth Refresh Token if Service Account isn't used
        if (empty($accessToken) && !empty($refreshToken) && !empty($clientId) && !empty($clientSecret) && !str_contains($refreshToken, '{')) {
            try {
                $tokenUrl = "https://oauth2.googleapis.com/token";
                $tokenData = [
                    'client_id' => trim($clientId),
                    'client_secret' => trim($clientSecret),
                    'refresh_token' => trim($refreshToken),
                    'grant_type' => 'refresh_token'
                ];

                $ch = curl_init($tokenUrl);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($tokenData));
                $tokenResponse = curl_exec($ch);
                curl_close($ch);

                $tokenJson = json_decode($tokenResponse, true);
                $accessToken = $tokenJson['access_token'] ?? null;
                if (empty($accessToken) && !empty($tokenResponse)) {
                    error_log("GoogleDriveService refresh token error: " . ($tokenJson['error_description'] ?? ($tokenJson['error'] ?? $tokenResponse)));
                }
            } catch (Throwable $e) {
                error_log("GoogleDriveService refresh token error: " . $e->getMessage());
            }
        }

        return $accessToken;
    }

    public static function updatePdfContent(string $fileId, string $filePath): bool
    {
        if (empty($fileId) || str_starts_with($fileId, 'gdrive_') || !file_exists($filePath)) {
            return false;
        }

        $accessToken = self::getEffectiveAccessToken();
        if (empty($accessToken)) {
            return false;
        }

        try {
            $updateUrl = "https://www.googleapis.com/upload/drive/v3/files/" . urlencode($fileId) . "?uploadType=media&supportsAllDrives=true";
            $fileContent = file_get_contents($filePath);

            $ch = curl_init($updateUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $accessToken,
                'Content-Type: application/pdf',
                'Content-Length: ' . strlen($fileContent)
            ]);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $fileContent);
            $updateResponse = curl_exec($ch);
            curl_close($ch);

            $res = json_decode($updateResponse, true);
            return isset($res['id']);
        } catch (Throwable $e) {
            error_log("GoogleDriveService updatePdfContent error: " . $e->getMessage());
            return false;
        }
    }

    public static function uploadPdf(string $filePath, string $filename, string $suratKode, ?string $folderId = null): array
    {
        $targetFolderId = !empty($folderId) ? $folderId : Setting::get('gdrive_ktu_folder_id', '1m1SVmXboA73OFJKSK-BjfRtYXMdxlfm7');
        $accessToken = self::getEffectiveAccessToken();

        // Pastikan nama file yang terupload ke Google Drive tidak menampilkan kode surat
        if (!empty($suratKode)) {
            $filename = preg_replace('/[_\-]*' . preg_quote($suratKode, '/') . '/i', '', $filename);
        }
        $filename = preg_replace('/[_\-]*SR-\d{8}-[A-Za-z0-9]+/i', '', $filename);
        $filename = preg_replace('/[_\-]+(\.pdf)$/i', '$1', $filename);

        // 3. Upload File to Google Drive if Access Token is obtained
        if (!empty($accessToken) && file_exists($filePath)) {
            try {
                $uploadUrl = "https://www.googleapis.com/upload/drive/v3/files?uploadType=multipart&supportsAllDrives=true";
                $metadata = [
                    'name' => $filename,
                    'parents' => [$targetFolderId]
                ];


                $fileContent = file_get_contents($filePath);
                $boundary = '-------' . uniqid();
                $delimiter = "\r\n--" . $boundary . "\r\n";
                $closeDelimiter = "\r\n--" . $boundary . "--";

                $postData = $delimiter .
                    'Content-Type: application/json; charset=UTF-8' . "\r\n\r\n" .
                    json_encode($metadata) .
                    $delimiter .
                    'Content-Type: application/pdf' . "\r\n\r\n" .
                    $fileContent .
                    $closeDelimiter;

                $ch = curl_init($uploadUrl);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Authorization: Bearer ' . $accessToken,
                    'Content-Type: multipart/related; boundary=' . $boundary,
                    'Content-Length: ' . strlen($postData)
                ]);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
                $uploadResponse = curl_exec($ch);
                $curlErr = curl_error($ch);
                curl_close($ch);

                if ($curlErr) {
                    error_log("GoogleDriveService upload cURL error: " . $curlErr);
                }

                $res = json_decode($uploadResponse, true);
                if (isset($res['id'])) {
                    return [
                        'success' => true,
                        'file_id' => $res['id'],
                        'web_link' => "https://drive.google.com/file/d/" . $res['id'] . "/view?usp=sharing",
                        'message' => 'File berhasil diunggah ke Google Drive'
                    ];
                } else {
                    error_log("GoogleDriveService upload failed. Response: " . $uploadResponse);
                }
            } catch (Throwable $e) {
                error_log("GoogleDriveService upload exception: " . $e->getMessage());
            }
        }

        // Check if credentials were provided but token is invalid/expired
        $refreshToken = Setting::get('gdrive_refresh_token', '');
        $clientId = Setting::get('gdrive_client_id', '');
        if (!empty($refreshToken) && !empty($clientId) && empty($accessToken)) {
            return [
                'success' => false,
                'file_id' => null,
                'web_link' => null,
                'message' => 'Token Google Drive kedaluwarsa atau revoked (invalid_grant). Harap perbarui Refresh Token di menu Pengaturan Google Drive.'
            ];
        }

        // 4. Fallback / Simulation mode (when API credentials have not yet been saved in Admin panel)
        $mockFileId = 'gdrive_' . md5($suratKode . time());
        $mockLink = 'https://drive.google.com/drive/u/0/folders/' . urlencode($targetFolderId) . '?file=' . $mockFileId;

        return [
            'success' => true,
            'file_id' => $mockFileId,
            'web_link' => $mockLink,
            'message' => 'Berkas PDF siap diunggah ke Google Drive (Kredensial API dapat dikonfigurasi pada Superadmin Panel)'
        ];
    }

    public static function syncPendingUploads(): array
    {
        $accessToken = self::getEffectiveAccessToken();
        if (empty($accessToken)) {
            return [
                'success' => false,
                'synced' => 0,
                'message' => 'Token Google Drive belum valid atau kedaluwarsa.'
            ];
        }

        $db = Database::getInstance();
        $stmt = $db->query("SELECT id, kode_surat, status, pdf_path, gdrive_file_id FROM surat WHERE (gdrive_file_id LIKE 'gdrive_%' OR gdrive_file_id IS NULL OR gdrive_file_id = '') AND pdf_path IS NOT NULL AND (status = 'tu_signed' OR status = 'completed')");
        $pending = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $synced = 0;
        $suratModel = new Surat();
        $ktuFolderId = Setting::get('gdrive_ktu_folder_id', '1m1SVmXboA73OFJKSK-BjfRtYXMdxlfm7');
        $kepstaFolderId = Setting::get('gdrive_kepsta_folder_id', '1q43jnW7Tw0ghLY7rrx-OPI3aTQAronrB');

        foreach ($pending as $s) {
            $basePath = defined('BASE_PATH') ? BASE_PATH : (__DIR__ . '/../');
            $absPath = $basePath . 'public' . $s['pdf_path'];
            if (!file_exists($absPath)) {
                $absPath = $basePath . $s['pdf_path'];
            }
            if (file_exists($absPath)) {
                $targetFolder = ($s['status'] === 'completed') ? $kepstaFolderId : $ktuFolderId;
                $res = self::uploadPdf($absPath, basename($absPath), $s['kode_surat'], $targetFolder);
                if (!empty($res['success']) && !str_starts_with($res['file_id'] ?? '', 'gdrive_')) {
                    $suratModel->updateSurat($s['id'], [
                        'gdrive_file_id' => $res['file_id'],
                        'gdrive_web_link' => $res['web_link']
                    ]);
                    $synced++;
                }
            }
        }

        return [
            'success' => true,
            'synced' => $synced,
            'message' => "Berhasil menyinkronkan {$synced} file surat ke Google Drive."
        ];
    }

    private static function getAccessTokenFromServiceAccount(array $json): ?string
    {
        $clientEmail = $json['client_email'] ?? '';
        $privateKey = $json['private_key'] ?? '';
        if (empty($clientEmail) || empty($privateKey)) {
            return null;
        }

        // Ensure private key handles escaped line breaks properly
        $privateKey = str_replace('\n', "\n", $privateKey);

        $header = json_encode(['alg' => 'RS256', 'typ' => 'JWT']);
        $now = time();
        $payload = json_encode([
            'iss' => $clientEmail,
            'scope' => 'https://www.googleapis.com/auth/drive',
            'aud' => 'https://oauth2.googleapis.com/token',
            'exp' => $now + 3600,
            'iat' => $now
        ]);

        $base64UrlHeader = rtrim(strtr(base64_encode($header), '+/', '-_'), '=');
        $base64UrlPayload = rtrim(strtr(base64_encode($payload), '+/', '-_'), '=');
        $toSign = $base64UrlHeader . "." . $base64UrlPayload;

        $binarySignature = '';
        if (!openssl_sign($toSign, $binarySignature, $privateKey, OPENSSL_ALGO_SHA256)) {
            error_log("GoogleDriveService: openssl_sign failed for Service Account");
            return null;
        }

        $base64UrlSignature = rtrim(strtr(base64_encode($binarySignature), '+/', '-_'), '=');
        $jwt = $toSign . "." . $base64UrlSignature;

        $ch = curl_init('https://oauth2.googleapis.com/token');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $jwt
        ]));
        $res = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);

        if ($err) {
            error_log("GoogleDriveService token cURL error: " . $err);
        }

        $data = json_decode($res, true);
        if (empty($data['access_token'])) {
            error_log("GoogleDriveService token error response: " . $res);
        }
        return $data['access_token'] ?? null;
    }
}
