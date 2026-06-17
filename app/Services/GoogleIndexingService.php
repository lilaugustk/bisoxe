<?php

namespace App\Services;

use Google\Client;
use Google\Service\Indexing;
use Google\Service\Indexing\UrlNotification;
use Illuminate\Support\Facades\Log;

class GoogleIndexingService
{
    protected ?Client $client = null;

    public function __construct()
    {
        // Đường dẫn tới file JSON credentials được tải về từ Google Cloud Service Account
        $credentialsPath = storage_path('app/google-credentials.json');

        if (file_exists($credentialsPath)) {
            try {
                $this->client = new Client;
                $this->client->setAuthConfig($credentialsPath);
                $this->client->addScope('https://www.googleapis.com/auth/indexing');
            } catch (\Exception $e) {
                Log::error('Google Indexing Client initialization failed: '.$e->getMessage());
            }
        } else {
            Log::warning('Google credentials file not found at: '.$credentialsPath.'. Google Indexing API is disabled.');
        }
    }

    /**
     * Gửi yêu cầu lập chỉ mục hoặc cập nhật URL lên Google Indexing API.
     *
     * @param  string  $url  URL bài viết/biển số cần index
     * @param  string  $type  Loại thông báo (URL_UPDATED - cập nhật/thêm mới, URL_DELETED - xóa bỏ)
     */
    public function submitUrl(string $url, string $type = 'URL_UPDATED'): bool
    {
        if (! $this->client) {
            Log::warning('Cannot submit to Google Indexing: Google client is not configured.');

            return false;
        }

        try {
            $service = new Indexing($this->client);
            $notification = new UrlNotification;
            $notification->setUrl($url);
            $notification->setType($type);

            // Gửi request publish tới Google
            $result = $service->urlNotifications->publish($notification);

            Log::info('Successfully submitted URL to Google Indexing API', [
                'url' => $url,
                'type' => $type,
                'notification_entry' => $result->getNotificationPublishMetadata(),
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to submit URL to Google Indexing API', [
                'url' => $url,
                'message' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
