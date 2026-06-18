<?php

namespace App\Console\Commands;

use App\Models\PlateKind;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

#[Signature('app:fix-plate-kinds')]
#[Description('Gán lại toàn bộ kinds cho biển số dựa trên regex thực tế, sửa dữ liệu sai từ VPA')]
class FixPlateKinds extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        ini_set('memory_limit', '2048M');
        set_time_limit(0);
        DB::disableQueryLog();

        $kinds = PlateKind::whereNotNull('regex')->get();
        $this->info("Tìm thấy {$kinds->count()} loại biển có regex.");

        // Đếm trước khi sửa
        $beforeCount = DB::table('license_plate_kinds')->count();
        $this->info("Tổng liên kết kinds hiện tại: {$beforeCount}");

        // Xóa toàn bộ liên kết cũ
        $this->info('Đang xóa toàn bộ liên kết kinds cũ...');
        DB::table('license_plate_kinds')->truncate();

        $totalPlates = DB::table('license_plates')->count();
        $this->info("Đang quét lại {$totalPlates} biển số với regex...");

        $bar = $this->output->createProgressBar($totalPlates);
        $bar->start();

        $totalInserted = 0;
        $chunkSize = 5000;

        DB::table('license_plates')
            ->select('id', 'serial_number')
            ->orderBy('id')
            ->chunk($chunkSize, function ($plates) use ($kinds, $bar, &$totalInserted) {
                $pivotRows = [];
                $now = now()->toDateTimeString();

                foreach ($plates as $plate) {
                    if (empty($plate->serial_number)) {
                        continue;
                    }

                    foreach ($kinds as $kind) {
                        try {
                            if (preg_match('#'.str_replace('#', '\#', $kind->regex).'#', $plate->serial_number)) {
                                $pivotRows[] = [
                                    'plate_id' => $plate->id,
                                    'kind_id' => $kind->id,
                                    'created_at' => $now,
                                ];
                            }
                        } catch (\Exception $e) {
                            // Bỏ qua regex lỗi
                        }
                    }
                }

                if (! empty($pivotRows)) {
                    // Insert theo sub-chunk để tránh vượt quá kích thước gói MySQL
                    foreach (array_chunk($pivotRows, 2000) as $subChunk) {
                        DB::table('license_plate_kinds')->insert($subChunk);
                        $totalInserted += count($subChunk);
                    }
                }

                $bar->advance(count($plates));
            });

        $bar->finish();
        $this->line('');
        $this->line('');

        // Thống kê sau khi sửa
        $afterCount = DB::table('license_plate_kinds')->count();
        $this->info('============================================================');
        $this->info('HOÀN TẤT! Kết quả:');
        $this->info("  Trước: {$beforeCount} liên kết");
        $this->info("  Sau:   {$afterCount} liên kết");
        $diff = $afterCount - $beforeCount;
        $this->info('  Chênh lệch: '.($diff >= 0 ? '+' : '').$diff);

        // Hiển thị thống kê theo từng kind
        $this->line('');
        $this->info('Thống kê theo loại biển:');
        $stats = DB::table('license_plate_kinds')
            ->join('plate_kinds', 'plate_kinds.id', '=', 'license_plate_kinds.kind_id')
            ->select('plate_kinds.name', DB::raw('COUNT(*) as count'))
            ->groupBy('plate_kinds.name')
            ->orderBy('count', 'desc')
            ->get();

        foreach ($stats as $stat) {
            $this->line("  {$stat->name}: {$stat->count} biển");
        }

        return self::SUCCESS;
    }
}
