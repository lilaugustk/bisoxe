<?php

namespace App\Console\Commands;

use App\Services\GoogleStorageService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;
use ZipArchive;
use Exception;

#[Signature('project:restore')]
#[Description('Khôi phục cơ sở dữ liệu và các tệp tải lên từ file sao lưu ZIP')]
class ProjectRestore extends Command
{
    public function handle(GoogleStorageService $storageService): int
    {
        $backupDir = storage_path('backups');

        if (!File::exists($backupDir)) {
            File::makeDirectory($backupDir, 0755, true);
        }

        $backups = [];

        // 1. Tìm các file ZIP sao lưu cục bộ
        $files = File::files($backupDir);
        foreach ($files as $file) {
            if ($file->getExtension() === 'zip' && str_starts_with($file->getFilename(), 'backup-')) {
                $backups[] = [
                    'source' => 'local',
                    'name' => $file->getFilename(),
                    'pathname' => $file->getPathname(),
                    'size' => File::size($file->getPathname()),
                    'mtime' => $file->getMTime(),
                ];
            }
        }

        // 2. Tìm các file ZIP sao lưu trên Google Cloud Storage
        if ($storageService->isConfigured()) {
            $this->info("Đang tải danh sách bản sao lưu từ Google Cloud Storage...");
            $gcsFiles = $storageService->listFiles();
            foreach ($gcsFiles as $gcsFile) {
                $backups[] = [
                    'source' => 'gcs',
                    'name' => $gcsFile['name'],
                    'pathname' => $gcsFile['name'],
                    'size' => $gcsFile['size'],
                    'mtime' => $gcsFile['mtime'],
                ];
            }
        }

        if (empty($backups)) {
            $this->error("Không tìm thấy bản sao lưu nào cục bộ hoặc trên Google Cloud Storage.");
            return self::FAILURE;
        }

        // Sắp xếp bản mới nhất lên đầu
        usort($backups, function ($a, $b) {
            return $b['mtime'] - $a['mtime'];
        });

        // Tạo danh sách cho người dùng chọn
        $options = [];
        foreach ($backups as $b) {
            $sizeFormatted = $this->formatBytes($b['size']);
            $date = date('Y-m-d H:i:s', $b['mtime']);
            $sourceTag = $b['source'] === 'gcs' ? '[ĐÁM MÂY]' : '[CỤC BỘ]';
            $options[$b['name']] = "{$sourceTag} {$b['name']} (Ngày tạo: {$date} | Dung lượng: {$sizeFormatted})";
        }

        $selectedName = $this->choice(
            'Chọn bản sao lưu bạn muốn khôi phục',
            $options,
            array_key_first($options)
        );

        if (!is_string($selectedName)) {
            $this->error("Lựa chọn không hợp lệ.");
            return self::FAILURE;
        }

        $selectedBackup = collect($backups)->first(fn($b) => $b['name'] === $selectedName);
        if (!$selectedBackup) {
            $this->error("Không tìm thấy thông tin bản sao lưu đã chọn.");
            return self::FAILURE;
        }

        $this->warn("CẢNH BÁO: Quá trình khôi phục sẽ ghi đè lên Cơ sở dữ liệu và các Tệp tin tải lên hiện tại!");
        if (!$this->confirm("Bạn có chắc chắn muốn tiến hành khôi phục từ bản sao lưu [{$selectedName}] không?", false)) {
            $this->info("Đã hủy bỏ quá trình khôi phục.");
            return self::SUCCESS;
        }

        $zipFilePath = '';
        $deleteLocalAfterRestore = false;

        // Nếu là file trên Google Cloud, tiến hành tải về cục bộ trước
        if ($selectedBackup['source'] === 'gcs') {
            $zipFilePath = "{$backupDir}/{$selectedName}";
            $this->info("Đang tải tệp sao lưu từ Google Cloud Storage về máy cục bộ...");
            if (!$storageService->downloadFile($selectedName, $zipFilePath)) {
                $this->error("Tải bản sao lưu từ đám mây thất bại.");
                return self::FAILURE;
            }
            $this->info("Tải bản sao lưu về thành công.");
            $deleteLocalAfterRestore = true;
        } else {
            $zipFilePath = $selectedBackup['pathname'];
        }

        $tempPath = "{$backupDir}/temp_restore_" . time();
        File::makeDirectory($tempPath);

        // Giải nén file ZIP
        $this->info("Đang giải nén file sao lưu...");
        $zip = new ZipArchive();
        if ($zip->open($zipFilePath) !== true) {
            if ($deleteLocalAfterRestore && File::exists($zipFilePath)) {
                File::delete($zipFilePath);
            }
            File::deleteDirectory($tempPath);
            $this->error("Không thể giải nén file ZIP.");
            return self::FAILURE;
        }

        $zip->extractTo($tempPath);
        $zip->close();

        try {
            // 1. Khôi phục Database
            $tempSqlFile = "{$tempPath}/database.sql";
            if (File::exists($tempSqlFile)) {
                if ($this->confirm("Tìm thấy tệp tin Cơ sở dữ liệu. Bạn có muốn khôi phục không?", true)) {
                    $this->info("Đang khôi phục cơ sở dữ liệu...");
                    if ($this->importDatabase($tempSqlFile)) {
                        $this->info("Khôi phục cơ sở dữ liệu thành công!");
                    } else {
                        throw new Exception("Lỗi khi import cơ sở dữ liệu.");
                    }
                }
            }

            // 2. Khôi phục cấu hình .env
            $tempEnvFile = "{$tempPath}/.env.backup";
            if (File::exists($tempEnvFile)) {
                if ($this->confirm("Tìm thấy tệp tin cấu hình .env. Bạn có muốn khôi phục cấu hình hệ thống không?", false)) {
                    $this->info("Đang khôi phục tệp .env...");
                    File::copy($tempEnvFile, base_path('.env'));
                    $this->info("Khôi phục tệp .env thành công!");
                }
            }

            // 3. Khôi phục Files
            $tempFilesPath = "{$tempPath}/files";
            if (File::exists($tempFilesPath)) {
                if ($this->confirm("Tìm thấy tệp tải lên công khai. Bạn có muốn khôi phục không?", true)) {
                    $this->info("Đang khôi phục các tệp tải lên...");
                    $publicStoragePath = storage_path('app/public');

                    if (File::exists($publicStoragePath)) {
                        File::cleanDirectory($publicStoragePath);
                    } else {
                        File::makeDirectory($publicStoragePath, 0755, true);
                    }

                    File::copyDirectory($tempFilesPath, $publicStoragePath);
                    $this->info("Khôi phục các tệp tải lên thành công!");
                }
            }

            $this->newLine();
            $this->info("=== KHÔI PHỤC THÀNH CÔNG ===");
            $this->info("Hệ thống đã phục hồi trạng thái từ: {$selectedName}");

            // Xóa cache cấu hình để cập nhật
            $this->info("Đang dọn dẹp bộ nhớ đệm cache hệ thống...");
            Artisan::call('config:clear');
            Artisan::call('cache:clear');
            $this->info("Dọn dẹp cache hoàn tất.");
            $this->newLine();

        } catch (Exception $e) {
            $this->error("Quá trình khôi phục thất bại: " . $e->getMessage());
            return self::FAILURE;
        } finally {
            // Luôn dọn dẹp thư mục tạm
            File::deleteDirectory($tempPath);

            // Xóa file ZIP tải từ GCS về để tránh rác dung lượng cục bộ
            if ($deleteLocalAfterRestore && $zipFilePath !== '' && File::exists($zipFilePath)) {
                File::delete($zipFilePath);
            }
        }

        return self::SUCCESS;
    }

    /**
     * Import cơ sở dữ liệu từ file SQL
     */
    private function importDatabase(string $sqlFilePath): bool
    {
        $connection = config('database.default');
        $dbConfig = config("database.connections.{$connection}");

        if (!$dbConfig) {
            $this->error("Không tìm thấy cấu hình cơ sở dữ liệu.");
            return false;
        }

        if ($connection === 'sqlite') {
            $sqlitePath = $dbConfig['database'];
            // Sao lưu ngược file sqlite hiện tại đề phòng
            if (File::exists($sqlitePath)) {
                File::copy($sqlitePath, $sqlitePath . '.backup');
            }
            return File::copy($sqlFilePath, $sqlitePath);
        }

        if ($connection === 'mysql') {
            $host = $dbConfig['host'] ?? '127.0.0.1';
            $port = $dbConfig['port'] ?? '3306';
            $database = $dbConfig['database'];
            $username = $dbConfig['username'];
            $password = $dbConfig['password'];

            $importCommand = sprintf(
                'mysql --user="%s" --host="%s" --port="%s" "%s"',
                addcslashes($username, '"\\$'),
                addcslashes($host, '"\\$'),
                addcslashes($port, '"\\$'),
                addcslashes($database, '"\\$')
            );

            $this->info("Đang import cơ sở dữ liệu qua lệnh mysql...");
            $process = Process::fromShellCommandline($importCommand . ' < ' . escapeshellarg($sqlFilePath), null, [
                'MYSQL_PWD' => $password
            ]);

            $process->run();

            if ($process->isSuccessful()) {
                return true;
            }

            $this->warn("mysql import CLI thất bại hoặc không có quyền thực thi. Đang chuyển sang phương thức dự phòng PHP PDO...");
            return $this->fallbackImportMysql($sqlFilePath);
        }

        return false;
    }

    /**
     * Phương thức dự phòng khôi phục database bằng PHP PDO khi không dùng được lệnh mysql CLI
     */
    private function fallbackImportMysql(string $sqlFilePath): bool
    {
        /** @var \PDO|null $pdo */
        $pdo = null;
        try {
            ini_set('memory_limit', '512M');
            $pdo = DB::connection()->getPdo();
            $pdo->exec('SET FOREIGN_KEY_CHECKS=0;');

            $handle = fopen($sqlFilePath, 'r');
            if (!$handle) {
                $this->error("Không thể mở file SQL để đọc: {$sqlFilePath}");
                return false;
            }

            $this->info("Đang đọc và import dữ liệu (vui lòng đợi)...");
            
            $currentQuery = '';
            $queryCount = 0;

            if (!$pdo->inTransaction()) {
                $pdo->beginTransaction();
            }

            while (($line = fgets($handle)) !== false) {
                $trimmedLine = trim($line);

                // Bỏ qua chú thích và dòng trống
                if ($trimmedLine === '' || str_starts_with($trimmedLine, '--') || str_starts_with($trimmedLine, '#') || str_starts_with($trimmedLine, '/*')) {
                    continue;
                }

                $currentQuery .= $line;

                // Nếu dòng kết thúc bằng dấu chấm phẩy, tiến hành thực thi
                if (str_ends_with($trimmedLine, ';')) {
                    try {
                        $pdo->exec($currentQuery);
                        $queryCount++;
                        if ($queryCount % 1000 === 0) {
                            $this->output->write('.');
                        }
                        if ($queryCount % 5000 === 0) {
                            if ($pdo->inTransaction()) {
                                $pdo->commit();
                            }
                            $pdo->beginTransaction();
                        }
                    } catch (Exception $e) {
                        // Tiếp tục chạy để khôi phục tối đa dữ liệu, log cảnh báo
                        $this->newLine();
                        $this->warn("Cảnh báo câu lệnh lỗi: " . substr(trim($currentQuery), 0, 100) . "... | Lỗi: " . $e->getMessage());
                    }
                    $currentQuery = '';
                }
            }

            if ($pdo->inTransaction()) {
                $pdo->commit();
            }

            fclose($handle);
            $this->newLine();
            $pdo->exec('SET FOREIGN_KEY_CHECKS=1;');

            return true;
        } catch (Exception $e) {
            try {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
            } catch (\Throwable $ex) {}
            $this->newLine();
            $this->error("Lỗi phương thức dự phòng PDO: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Định dạng kích thước byte
     */
    private function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = (int) min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);

        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}
