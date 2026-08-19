<?php

namespace App\Console\Commands;

use App\Models\PlanMasterKcs;
use App\Support\OffDays;
use Carbon\Carbon;
use Illuminate\Console\Command;

/**
 * Tính lại các cột dẫn xuất của bảng theo dõi hồ sơ KCS.
 *
 * Trang theo dõi chỉ tính lại một dòng khi người dùng lưu hoặc khi MMS đổi Ngày KCS,
 * nên khi công thức hoặc lịch nghỉ (off_days) thay đổi thì các dòng cũ vẫn giữ kết quả
 * tính theo quy tắc trước đó. Chạy lệnh này để đưa toàn bộ về cùng một công thức.
 *
 * Chỉ ghi lại các cột dẫn xuất (eligible_date, completion_days, bottleneck, kcs_pending,
 * kcs_year, kcs_month, result); không đụng vào dữ liệu người dùng nhập và không sinh
 * vết lịch sử chỉnh sửa.
 */
class RecomputeKcsTracking extends Command
{
    protected $signature = 'kcs:recompute {--dry-run : Chỉ đếm số dòng sẽ đổi, không ghi database}';

    protected $description = 'Tính lại Số Ngày HT / Kết Quả của bảng theo dõi hồ sơ KCS theo công thức hiện tại';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        OffDays::flush();
        $this->info('Lịch nghỉ công ty: ' . count(OffDays::all()) . ' ngày.');

        $scanned = 0;
        $changed = 0;
        $flipped = 0;

        PlanMasterKcs::query()->chunkById(500, function ($records) use (&$scanned, &$changed, &$flipped, $dryRun) {
            foreach ($records as $record) {
                $scanned++;

                $before = [
                    'completion_days' => $record->completion_days,
                    'result' => $record->result,
                ];

                $record->fill(PlanMasterKcs::derive($this->rawInput($record)));

                if (!$record->isDirty()) {
                    continue;
                }

                $changed++;

                if ($record->result !== $before['result']) {
                    $flipped++;
                }

                if ($dryRun) {
                    $record->discardChanges();
                    continue;
                }

                // Cố ý không đụng updated_by: đây là tính lại của hệ thống, không phải
                // ai đó sửa dữ liệu, để cột "người sửa" vẫn chỉ đúng người nhập gần nhất.
                $record->save();
            }
        });

        $this->info("Đã quét {$scanned} dòng, {$changed} dòng lệch, trong đó {$flipped} dòng đổi Kết Quả.");

        if ($dryRun) {
            $this->warn('Chế độ --dry-run: chưa ghi gì vào database.');
        }

        return self::SUCCESS;
    }

    /**
     * Dữ liệu gốc của một dòng, đưa về dạng chuỗi đúng như derive() nhận khi lưu.
     *
     * @return array<string, string|null>
     */
    private function rawInput(PlanMasterKcs $record): array
    {
        $input = [];

        foreach (PlanMasterKcs::trackedFields() as $field) {
            $value = $record->{$field};

            if ($value instanceof Carbon) {
                $value = $value->toDateString();
            }

            $input[$field] = ($value === null || $value === '') ? null : (string) $value;
        }

        return $input;
    }
}
