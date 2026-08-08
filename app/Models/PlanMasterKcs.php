<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Một dòng theo dõi hồ sơ KCS của một lô sản xuất (plan_master).
 *
 * Toàn bộ công thức của file theo dõi KCS được gom về derive() để trang danh sách,
 * bảng tổng kết tỉ lệ và phần tính lại khi lưu luôn cho ra cùng một kết quả.
 */
class PlanMasterKcs extends Model
{
    use HasFactory;

    protected $table = 'plan_master_KCS';

    /** Đạt yêu cầu khi KCS xong trong vòng 3 ngày kể từ ngày đủ điều kiện */
    public const ON_TIME_DAYS = 3;

    public const RESULT_MET = 'Đáp Ứng';
    public const RESULT_NOT_MET = 'Không Đáp Ứng';

    /** Mục tiêu / ngưỡng cảnh báo của tỉ lệ lô KCS đúng hạn (%) */
    public const TARGET_RATE = 98;
    public const CRITICAL_RATE = 80;

    /**
     * Các mốc có thể quyết định "ngày đủ điều kiện", theo đúng thứ tự ưu tiên
     * của công thức gốc: mốc nào trùng với ngày lớn nhất thì được ghi nhận.
     */
    public const MILESTONES = [
        'record_done_date' => 'Đọc hồ sơ',
        'coatp_received_date' => 'Nhận COA thành phẩm',
        'dr_ir_approval_date' => 'DR/IR',
        'oos_approval_date' => 'OOS',
        'dr_ir_kcq_approval_date' => 'DR/IR KCQ',
        'opv_pvr_approval_date' => 'OPV/PVR',
    ];

    /**
     * Các cột người dùng được nhập trên lưới theo dõi, kèm nhãn tiếng Việt.
     * Dùng chung cho việc lưu, tính lại và hiển thị lịch sử chỉnh sửa.
     */
    public const INPUT_LABELS = [
        'record_received_date' => 'Ngày nhận hồ sơ',
        'reader' => 'Người đọc',
        'record_done_date' => 'Ngày đọc xong hồ sơ',
        'stock_in_qty' => 'Số lượng nhập kho',
        'coatp_number' => 'Số phiếu COATP',
        'coatp_received_date' => 'Ngày nhận COATP',
        'dr_ir' => 'DR/IR',
        'dr_ir_approval_date' => 'Ngày Approval DR/IR',
        'oos' => 'OOS',
        'oos_approval_date' => 'Ngày Approval OOS',
        'dr_ir_kcq_approval_date' => 'Ngày Approval DR/IR KCQ',
        'opv_pvr_approval_date' => 'Ngày Approval OPV/PVR',
        'kcs_date' => 'Ngày KCS',
        'note' => 'Ghi chú',
    ];

    /** Tên các cột người dùng được nhập (INPUT_LABELS là nguồn duy nhất) */
    public static function inputFields(): array
    {
        return array_keys(self::INPUT_LABELS);
    }

    /**
     * Các cột đã bỏ khỏi lưới nhập nhưng vẫn còn trong lịch sử chỉnh sửa cũ,
     * giữ nhãn để vết lịch sử không hiện ra tên cột thô.
     */
    private const LEGACY_LABELS = [
        'edition' => 'Ấn bản (đã bỏ)',
    ];

    /** Nhãn tiếng Việt của một cột nhập, dùng khi hiển thị lịch sử chỉnh sửa */
    public static function inputLabel(string $field): string
    {
        return self::INPUT_LABELS[$field] ?? self::LEGACY_LABELS[$field] ?? $field;
    }

    protected $fillable = [
        'plan_master_id',
        'record_received_date',
        'reader',
        'record_done_date',
        'stock_in_qty',
        'kcs_date',
        'coatp_number',
        'coatp_received_date',
        'dr_ir',
        'dr_ir_approval_date',
        'oos',
        'oos_approval_date',
        'dr_ir_kcq_approval_date',
        'opv_pvr_approval_date',
        'note',
        'eligible_date',
        'completion_days',
        'bottleneck',
        'kcs_pending',
        'kcs_year',
        'kcs_month',
        'result',
        'updated_by',
    ];

    protected $casts = [
        'record_received_date' => 'date',
        'record_done_date' => 'date',
        'kcs_date' => 'date',
        'coatp_received_date' => 'date',
        'dr_ir_approval_date' => 'date',
        'oos_approval_date' => 'date',
        'dr_ir_kcq_approval_date' => 'date',
        'opv_pvr_approval_date' => 'date',
        'eligible_date' => 'date',
    ];

    public function planMaster()
    {
        return $this->belongsTo(PlanMaster::class, 'plan_master_id');
    }

    /**
     * Tính các cột dẫn xuất từ dữ liệu người dùng nhập.
     *
     * @param  array<string, mixed>  $input  Các cột trong INPUT_FIELDS
     * @return array<string, mixed>          eligible_date, completion_days, bottleneck,
     *                                       kcs_pending, kcs_year, kcs_month, result
     */
    public static function derive(array $input): array
    {
        $derived = [
            'eligible_date' => null,
            'completion_days' => null,
            'bottleneck' => null,
            'kcs_pending' => null,
            'kcs_year' => null,
            'kcs_month' => null,
            'result' => null,
        ];

        // Ngày đủ điều kiện = mốc hoàn tất muộn nhất trong các mốc đã có
        $eligible = null;
        foreach (array_keys(self::MILESTONES) as $field) {
            $date = self::toDate($input[$field] ?? null);

            if ($date && ($eligible === null || $date->gt($eligible))) {
                $eligible = $date;
            }
        }

        if ($eligible) {
            $derived['eligible_date'] = $eligible->toDateString();

            // Mốc đầu tiên (theo thứ tự ưu tiên) rơi đúng vào ngày đủ điều kiện
            foreach (self::MILESTONES as $field => $label) {
                $date = self::toDate($input[$field] ?? null);

                if ($date && $date->eq($eligible)) {
                    $derived['bottleneck'] = $label;
                    break;
                }
            }
        }

        $kcsDate = self::toDate($input['kcs_date'] ?? null);

        if (!$kcsDate) {
            // Chưa KCS thì chưa có kết quả để đánh giá
            return $derived;
        }

        $derived['kcs_year'] = (int) $kcsDate->year;
        $derived['kcs_month'] = (int) $kcsDate->month;

        // Số ngày chờ KCS: từ ngày nhận hồ sơ đến ngày KCS, tính theo ngày làm việc
        // (chỉ nghỉ Chủ nhật) và trừ chính ngày bắt đầu.
        //
        // Chỉ tính từ ngày nhận hồ sơ, không lấy MAX với ngày nhận COATP như công thức
        // Excel gốc. Cột này để tham khảo, không ảnh hưởng kết quả Đáp Ứng / Không Đáp Ứng
        // và bảng tổng kết tỉ lệ.
        $receivedDate = self::toDate($input['record_received_date'] ?? null);

        if ($receivedDate) {
            $derived['kcs_pending'] = self::workingDays($receivedDate, $kcsDate) - 1;
        }

        if ($eligible) {
            $completionDays = (int) $eligible->diffInDays($kcsDate, false);
            $derived['completion_days'] = $completionDays;
            $derived['result'] = $completionDays <= self::ON_TIME_DAYS
                ? self::RESULT_MET
                : self::RESULT_NOT_MET;
        }

        return $derived;
    }

    /**
     * Số ngày làm việc từ $start đến $end (tính cả hai đầu), nghỉ Chủ nhật.
     * Tương đương NETWORKDAYS.INTL(...; 11) của Excel.
     */
    public static function workingDays(Carbon $start, Carbon $end): int
    {
        if ($end->lt($start)) {
            return -self::workingDays($end, $start);
        }

        // Chặn vòng lặp chạy quá xa khi dữ liệu nhập sai (VD gõ nhầm năm)
        if ($start->diffInDays($end) > 3650) {
            return 0;
        }

        $days = 0;

        for ($cursor = $start->copy(); $cursor->lte($end); $cursor->addDay()) {
            if ($cursor->dayOfWeek !== Carbon::SUNDAY) {
                $days++;
            }
        }

        return $days;
    }

    /** Chuẩn hoá giá trị ngày về Carbon, bỏ qua chuỗi rỗng và giá trị không đọc được */
    private static function toDate($value): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof Carbon) {
            return $value->copy()->startOfDay();
        }

        try {
            return Carbon::parse($value)->startOfDay();
        } catch (\Exception) {
            return null;
        }
    }

}
