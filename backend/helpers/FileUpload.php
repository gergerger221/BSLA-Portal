<?php
// backend/helpers/FileUpload.php
namespace App\Helpers;

use App\Config\Response;

class FileUpload {
    private static string $uploadDir = __DIR__ . '/../uploads/';

    public static function init(): void {
        if (!is_dir(self::$uploadDir)) {
            mkdir(self::$uploadDir, 0777, true);
        }
    }

    public static function upload(array $file, array $allowedExtensions = ['jpg', 'jpeg', 'png', 'pdf', 'webp', 'doc', 'docx'], int $maxSizeBytes = 10485760): array {
        self::init();

        if (!isset($file['error']) || is_array($file['error'])) {
            Response::error('Invalid file upload parameters.');
        }

        if ($file['error'] !== UPLOAD_ERR_OK) {
            Response::error('File upload failed with error code: ' . $file['error']);
        }

        if ($file['size'] > $maxSizeBytes) {
            Response::error('File size exceeds the 10MB limit.');
        }

        $originalName = basename($file['name']);
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

        if (!in_array($extension, $allowedExtensions)) {
            Response::error('Invalid file extension. Allowed: ' . implode(', ', $allowedExtensions));
        }

        // VULN-11 fix: Validate actual MIME type to prevent disguised malicious uploads
        $allowedMimeTypes = [
            'jpg'  => ['image/jpeg'],
            'jpeg' => ['image/jpeg'],
            'png'  => ['image/png'],
            'webp' => ['image/webp'],
            'pdf'  => ['application/pdf'],
            'doc'  => [
                'application/msword',
                'application/vnd.ms-word',
                'application/octet-stream',
                'application/x-msword'
            ],
            'docx' => [
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/zip',
                'application/x-zip',
                'application/x-zip-compressed',
                'application/octet-stream'
            ],
        ];
        if (function_exists('finfo_open') && isset($allowedMimeTypes[$extension])) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $detectedMime = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);
            if (!in_array($detectedMime, $allowedMimeTypes[$extension], true)) {
                Response::error("File content does not match its extension. Detected: {$detectedMime}, Expected: " . implode('/', $allowedMimeTypes[$extension]));
            }
        }

        $newFileName = 'doc_' . date('Ymd_His') . '_' . bin2hex(random_bytes(6)) . '.' . $extension;
        $targetPath = self::$uploadDir . $newFileName;

        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            Response::error('Failed to save uploaded document to server storage.');
        }

        return [
            'file_name'     => $newFileName,
            'original_name' => $originalName,
            'file_path'     => 'uploads/' . $newFileName,
            'file_size'     => $file['size'],
            'extension'     => $extension
        ];
    }
}
