<?php

namespace App\Console\Commands;

use App\Services\GoogleStorageService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;
use ZipArchive;
use Exception;

#[Signature('project:backup {--database-only : Chỉ sao lưu cơ sở dữ liệu} {--files-only : Chỉ sao lưu các tệp tin public và cấu hình}')]
#[Description('Sao lưu cơ sở dữ liệu và các tệp tải lên của dự án thành file ZIP')]
class ProjectBackup extends Command
{
    public function handle(GoogleStorageService $storageService): int
    {
        $databaseOnly = $this->option('database-only');
        $filesOnly = $this->option('files-only');

        // Mặc định sao lưu cả hai nếu không chọn cụ thể
        $backupDb = !$filesOnly;
        $backupFiles = !$databaseOnly;

        $backupDir = storage_path('backups');
        if (!File::exists($backupDir)) {
            File::makeDirectory($backupDir, 0755, true);
        }

        $timestamp = now()->format('Y-m-d_H-i-s');
        $zipFileName = "backup-{$timestamp}.zip";
        $zipFilePath = "{$backupDir}/{$zipFileName}";

        $this->info("Bắt đầu quá trình sao lưu...");

        $zip = new ZipArchive();
        if ($zip->open($zipFilePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            $this->error("Không thể tạo file ZIP: {$zipFilePath}");
            return self::FAILURE;
        }

        // 1. Sao lưu Database
        if ($backupDb) {
            $this->info("Đang sao lưu cơ sở dữ liệu...");
            $tempSqlFile = "{$backupDir}/temp_database_{$timestamp}.sql";

            $success = $this->exportDatabase($tempSqlFile);

            if ($success && File::exists($tempSqlFile)) {
                $zip->addFile($tempSqlFile, 'database.sql');
                $this->info("Đã thêm cơ sở dữ liệu vào file nén.");
            } else {
                $zip->close();
                File::delete($zipFilePath);
                $this->error("Sao lưu cơ sở dữ liệu thất bại. Đã hủy bỏ sao lưu.");
                return self::FAILURE;
            }
        }

        // 2. Sao lưu Files
        if ($backupFiles) {
            $this->info("Đang sao lưu tệp tin cấu hình và tệp tải lên công khai...");

            // Sao lưu .env
            $envPath = base_path('.env');
            if (File::exists($envPath)) {
                $zip->addFile($envPath, '.env.backup');
                $this->info("Đã thêm cấu hình .env vào file nén.");
            } else {
                $this->warn("Không tìm thấy file .env để sao lưu cấu hình.");
            }

            // Sao lưu storage/app/public
            $publicStoragePath = storage_path('app/public');
            if (File::exists($publicStoragePath)) {
                $this->info("Đang quét thư mục storage/app/public...");
                $this->addDirectoryToZip($publicStoragePath, $zip);
                $this->info("Đã thêm tệp tải lên công khai vào file nén.");
            } else {
                $this->warn("Thư mục storage/app/public không tồn tại.");
            }
        }

        $zip->close();

        // Xóa file SQL tạm thời sau khi đóng ZIP
        if (isset($tempSqlFile) && File::exists($tempSqlFile)) {
            File::delete($tempSqlFile);
        }

        if (File::exists($zipFilePath)) {
            $size = File::size($zipFilePath);
            $sizeFormatted = $this->formatBytes($size);
            $this->newLine();
            $this->info("=== SAO LƯU CỤC BỘ THÀNH CÔNG ===");
            $this->info("Vị trí lưu: {$zipFilePath}");
            $this->info("Dung lượng: {$sizeFormatted}");

            // Tải lên Google Cloud Storage nếu cấu hình
            if ($storageService->isConfigured()) {
                $this->info("Đang tải bản sao lưu lên Google Cloud Storage (Bucket: {$storageService->getBucketName()})...");
                if ($storageService->uploadFile($zipFilePath, $zipFileName)) {
                    $this->info("Tải lên Google Cloud Storage THÀNH CÔNG!");
                } else {
                    $this->error("Tải lên Google Cloud Storage THẤT BẠI. Vui lòng kiểm tra log.");
                }
            }

            $this->newLine();
            return self::SUCCESS;
        }

        $this->error("Không thể hoàn tất file sao lưu.");
        return self::FAILURE;
    }

    /**
     * Xuất cơ sở dữ liệu ra file SQL
     */
    private function exportDatabase(string $outputPath): bool
    {
        $connection = config('database.default');
        $dbConfig = config("database.connections.{$connection}");

        if (!$dbConfig) {
            $this->error("Không tìm thấy cấu hình cơ sở dữ liệu cho kết nối: {$connection}");
            return false;
        }

        if ($connection === 'sqlite') {
            $sqlitePath = $dbConfig['database'];
            if (File::exists($sqlitePath)) {
                return File::copy($sqlitePath, $outputPath);
            }
            $this->error("Không tìm thấy tệp SQLite: {$sqlitePath}");
            return false;
        }

        if ($connection === 'mysql') {
            $host = $dbConfig['host'] ?? '127.0.0.1';
            $port = $dbConfig['port'] ?? '3306';
            $database = $dbConfig['database'];
            $username = $dbConfig['username'];
            $password = $dbConfig['password'];

            $dumpCommand = sprintf(
                'mysqldump --user="%s" --host="%s" --port="%s" "%s"',
                addcslashes($username, '"\\$'),
                addcslashes($host, '"\\$'),
                addcslashes($port, '"\\$'),
                addcslashes($database, '"\\$')
            );

            $this->info("Đang chạy mysqldump...");
            // Chạy mysqldump với mật khẩu truyền qua biến môi trường để an toàn
            $process = Process::fromShellCommandline($dumpCommand . ' > ' . escapeshellarg($outputPath), null, [
                'MYSQL_PWD' => $password
            ]);

            $process->run();

            if ($process->isSuccessful() && File::exists($outputPath) && File::size($outputPath) > 0) {
                return true;
            }

            $this->warn("mysqldump thất bại hoặc không được cài đặt trên máy chủ. Đang chuyển sang phương thức dự phòng PHP PDO...");
            return $this->fallbackExportMysql($outputPath);
        }

        $this->error("Không hỗ trợ tự động sao lưu cho loại cơ sở dữ liệu: {$connection}");
        return false;
    }

    /**
     * Phương thức dự phòng xuất database bằng PHP PDO khi không có mysqldump
     */
    private function fallbackExportMysql(string $outputPath): bool
    {
        try {
            ini_set('memory_limit', '512M');
            $pdo = DB::connection()->getPdo();
            $handle = fopen($outputPath, 'w');
            if (!$handle) {
                return false;
            }

            fwrite($handle, "-- Backup fallback generated by Laravel (PHP PDO)\n");
            fwrite($handle, "-- Date: " . date('Y-m-d H:i:s') . "\n\n");
            fwrite($handle, "SET FOREIGN_KEY_CHECKS=0;\n\n");

            // Lấy danh sách bảng
            $tables = [];
            $stmt = $pdo->query('SHOW TABLES');
            while ($row = $stmt->fetch(\PDO::FETCH_NUM)) {
                $tables[] = $row[0];
            }
            $stmt->closeCursor();

            foreach ($tables as $table) {
                $this->info("Đang sao lưu bảng: {$table}");
                // Xuất câu lệnh tạo bảng
                $createStmt = $pdo->query("SHOW CREATE TABLE `{$table}`");
                $createRow = $createStmt->fetch(\PDO::FETCH_NUM);
                $createStmt->closeCursor();

                fwrite($handle, "DROP TABLE IF EXISTS `{$table}`;\n");
                fwrite($handle, $createRow[1] . ";\n\n");

                // Thiết lập PDO MySQL không buffer để tránh tràn bộ nhớ đối với bảng lớn
                $pdo->setAttribute(\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);

                // Xuất dữ liệu bảng
                $rowsStmt = $pdo->query("SELECT * FROM `{$table}`");
                while ($row = $rowsStmt->fetch(\PDO::FETCH_ASSOC)) {
                    $keys = array_map(fn($key) => "`{$key}`", array_keys($row));
                    $values = array_map(function ($val) use ($pdo) {
                        if ($val === null) {
                            return 'NULL';
                        }
                        return $pdo->quote($val);
                    }, array_values($row));

                    fwrite($handle, "INSERT INTO `{$table}` (" . implode(', ', $keys) . ") VALUES (" . implode(', ', $values) . ");\n");
                }
                $rowsStmt->closeCursor();

                // Khôi phục lại chế độ có buffer để thực hiện các lệnh ở vòng lặp sau
                $pdo->setAttribute(\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
                fwrite($handle, "\n");
            }

            fwrite($handle, "SET FOREIGN_KEY_CHECKS=1;\n");
            fclose($handle);
            return true;
        } catch (Exception $e) {
            // Đảm bảo khôi phục lại chế độ buffered query nếu có lỗi xảy ra
            try {
                $pdo->setAttribute(\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
            } catch (Exception $ex) {}
            $this->error("Lỗi phương thức dự phòng PDO: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Thêm thư mục vào file nén ZIP đệ quy
     */
    private function addDirectoryToZip(string $dir, ZipArchive $zip): void
    {
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($files as $file) {
            if (!$file->isDir()) {
                $filePath = $file->getRealPath();
                $relativePath = substr($filePath, strlen($dir) + 1);
                // Tạo đường dẫn trong zip với tiền tố 'files/'
                $zip->addFile($filePath, 'files/' . str_replace('\\', '/', $relativePath));
            }
        }
    }

    /**
     * Định dạng kích thước byte thành định dạng dễ đọc (KB, MB...)
     */
    private function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);

        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}
