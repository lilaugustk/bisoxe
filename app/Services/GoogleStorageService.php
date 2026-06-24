<?php

namespace App\Services;

use Google\Client;
use Google\Service\Storage;
use Google\Service\Storage\StorageObject;
use Illuminate\Support\Facades\Log;
use Exception;

class GoogleStorageService
{
    protected ?Client $client = null;
    protected ?Storage $storageService = null;
    protected ?string $bucketName = null;

    public function __construct()
    {
        $credentialsPath = storage_path('app/google-credentials.json');
        $this->bucketName = config('services.google.storage_bucket');

        if (file_exists($credentialsPath) && !empty($this->bucketName)) {
            try {
                $this->client = new Client();
                $this->client->setAuthConfig($credentialsPath);
                $this->client->addScope(Storage::DEVSTORAGE_READ_WRITE);
                $this->storageService = new Storage($this->client);
            } catch (Exception $e) {
                Log::error('Google Storage Service Client initialization failed: ' . $e->getMessage());
            }
        }
    }

    /**
     * Kiểm tra xem dịch vụ đã cấu hình thành công chưa
     */
    public function isConfigured(): bool
    {
        return $this->client !== null && $this->storageService !== null && !empty($this->bucketName);
    }

    /**
     * Lấy tên bucket đang cấu hình
     */
    public function getBucketName(): ?string
    {
        return $this->bucketName;
    }

    /**
     * Tải một tệp tin cục bộ lên Google Cloud Storage Bucket
     */
    public function uploadFile(string $localFilePath, string $objectName): bool
    {
        if (!$this->isConfigured()) {
            Log::warning('Google Cloud Storage is not configured correctly. Upload aborted.');
            return false;
        }

        if (!file_exists($localFilePath)) {
            Log::error("Local file does not exist for upload: {$localFilePath}");
            return false;
        }

        try {
            $fileMetadata = new StorageObject([
                'name' => $objectName
            ]);

            $fileContent = file_get_contents($localFilePath);

            $this->storageService->objects->insert(
                $this->bucketName,
                $fileMetadata,
                [
                    'data' => $fileContent,
                    'mimeType' => 'application/zip',
                    'uploadType' => 'multipart'
                ]
            );

            Log::info("Successfully uploaded file to GCS: {$objectName} (Bucket: {$this->bucketName})");
            return true;
        } catch (Exception $e) {
            Log::error('Failed to upload file to Google Cloud Storage: ' . $e->getMessage(), [
                'file' => $localFilePath,
                'object' => $objectName,
                'bucket' => $this->bucketName
            ]);
            return false;
        }
    }

    /**
     * Tải một đối tượng từ Google Cloud Storage Bucket về máy cục bộ
     */
    public function downloadFile(string $objectName, string $localSavePath): bool
    {
        if (!$this->isConfigured()) {
            Log::warning('Google Cloud Storage is not configured correctly. Download aborted.');
            return false;
        }

        try {
            $response = $this->storageService->objects->get(
                $this->bucketName,
                $objectName,
                ['alt' => 'media']
            );

            // Ghi nội dung tải về vào file
            $body = $response->getBody();
            file_put_contents($localSavePath, $body);

            Log::info("Successfully downloaded file from GCS: {$objectName} -> {$localSavePath}");
            return true;
        } catch (Exception $e) {
            Log::error('Failed to download file from Google Cloud Storage: ' . $e->getMessage(), [
                'object' => $objectName,
                'bucket' => $this->bucketName,
                'save_path' => $localSavePath
            ]);
            return false;
        }
    }

    /**
     * Liệt kê danh sách các bản sao lưu trong GCS Bucket
     *
     * @return array<int, array{name: string, size: int, mtime: int}>
     */
    public function listFiles(string $prefix = 'backup-'): array
    {
        if (!$this->isConfigured()) {
            return [];
        }

        try {
            $options = [];
            if ($prefix) {
                $options['prefix'] = $prefix;
            }

            $objects = $this->storageService->objects->listObjects($this->bucketName, $options);
            $fileList = [];

            // Kiểm tra xem có kết quả trả về không
            $items = $objects->getItems();
            if ($items) {
                foreach ($items as $object) {
                    // Chuyển mtime định dạng ISO-8601 sang timestamp
                    $mtime = strtotime($object->getUpdated());
                    $fileList[] = [
                        'name' => (string) $object->getName(),
                        'size' => (int) $object->getSize(),
                        'mtime' => $mtime !== false ? $mtime : time(),
                    ];
                }
            }

            return $fileList;
        } catch (Exception $e) {
            Log::error('Failed to list objects from Google Cloud Storage: ' . $e->getMessage(), [
                'bucket' => $this->bucketName
            ]);
            return [];
        }
    }
}
