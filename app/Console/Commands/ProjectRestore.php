<?php

namespace App\Console\Commands;

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
    public function handle(): int
    {
        $backupDir = storage_path('backups');

        if (!File::exists($backupDir)) {
            $this->error("Thư mục sao lưu không tồn tại: {$backupDir}");
            return self::FAILURE;
        }

        // Tìm các file ZIP sao lưu
        $files = File::files($backupDir);
        $backups = [];

        foreach ($files as $file) {
            if ($file->getExtension() === 'zip' && str_starts_with($file->getFilename(), 'backup-')) {
                $backups[] = $file;
            }
        }

        if (empty($backups)) {
            $this->error("Không tìm thấy bản sao lưu nào trong thư mục storage/backups/.");
            return self::FAILURE;
        }

        // Sắp xếp bản mới nhất lên đầu
        usort($backups, function ($a, $b) {
            return $b->getMTime() - $a->getMTime();
        });

        // Tạo danh sách cho người dùng chọn
        $options = [];
        foreach ($backups as $file) {
            $size = File::size($file->getPathname());
            $sizeFormatted = $this->formatBytes($size);
            $date = date('Y-m-d H:i:s', $file->getMTime());
            $options[$file->getFilename()] = "{$file->getFilename()} (Ngày tạo: {$date} | Dung lượng: {$sizeFormatted})";
        }

        $selectedName = $this->choice(
            'Chọn bản sao lưu bạn muốn khôi phục',
            $options,
            array_key_first($options)
        );

        $selectedFile = collect($backups)->first(fn($file) => $file->getFilename() === $selectedName);

        $this->warn("CẢNH BÁO: Quá trình khôi phục sẽ ghi đè lên Cơ sở dữ liệu và các Tệp tin tải lên hiện tại!");
        if (!$this->confirm("Bạn có chắc chắn muốn tiến hành khôi phục từ bản sao lưu [{$selectedName}] không?", false)) {
            $this->info("Đã hủy bỏ quá trình khôi phục.");
            return self::SUCCESS;
        }

        $tempPath = "{$backupDir}/temp_restore_" . time();
        File::makeDirectory($tempPath);

        // Giải nén file ZIP
        $this->info("Đang giải nén file sao lưu...");
        $zip = new ZipArchive();
        if ($zip->open($selectedFile->getPathname()) !== true) {
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
                    } catch (Exception $e) {
                        // Tiếp tục chạy để khôi phục tối đa dữ liệu, log cảnh báo
                        $this->newLine();
                        $this->warn("Cảnh báo câu lệnh lỗi: " . substr(trim($currentQuery), 0, 100) . "... | Lỗi: " . $e->getMessage());
                    }
                    $currentQuery = '';
                }
            }

            fclose($handle);
            $this->newLine();
            $pdo->exec('SET FOREIGN_KEY_CHECKS=1;');

            return true;
        } catch (Exception $e) {
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
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);

        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}
