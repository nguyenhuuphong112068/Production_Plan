<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Thông báo đóng gói của một lô sản xuất (plan_master).
 *
 * Dòng được sinh tự động khi gửi kế hoạch tháng, ban đầu chỉ có plan_master_id và
 * plan_list_id; người dùng bổ sung số PO và thông tin lấy mẫu ở trang Thông Báo Đóng Gói.
 * Lô nằm ngoài quy tắc tự động vẫn thêm tay được bằng nút "Tạo Thông Báo Khác" - những
 * dòng đó có is_manual = 1.
 */
class PlanMasterInforParkaging extends Model
{
    use HasFactory;

    protected $table = 'plan_master_infor_parkaging';

    /**
     * Các cột người dùng được nhập trên lưới, kèm nhãn tiếng Việt.
     * Dùng chung cho việc lưu và hiển thị nên thêm cột mới chỉ cần sửa ở đây.
     *
     * Mỗi loại đóng gói (sơ cấp / thứ cấp) là MỘT ô văn bản tự do, không tách riêng
     * số lượng / đơn vị / thời điểm: phiếu lấy mẫu thật có nhiều điểm lấy mẫu trong lô
     * (đầu lô, giữa lô, cuối lô) với giá trị gộp kiểu "5 hộp + 7 hộp SC" mà một ô số
     * không gõ được, nên để người dùng gõ nguyên văn như phiếu giấy gốc.
     */
    public const INPUT_LABELS = [
        'PO_no' => 'Số PO',
        'primary_sample' => 'Mẫu Đóng Gói Sơ Cấp',
        'secondary_sample' => 'Mẫu Đóng Gói Thứ Cấp',
        'Reason' => 'Lý Do',
        'sampled_confirmed' => 'Xác Nhận Đã Lấy Mẫu',
    ];

    /**
     * Các cột đã bỏ khỏi lưới nhập (gộp vào primary_sample / secondary_sample, hoặc bỏ
     * hẳn như sampled_by/sampled_date) nhưng vẫn còn trong lịch sử nhập liệu cũ, giữ nhãn
     * để vết lịch sử không hiện tên cột thô.
     */
    private const LEGACY_LABELS = [
        'Sampling_specifications' => 'Quy Cách Lấy Mẫu (đã gộp vào Mẫu Sơ/Thứ Cấp)',
        'Sampling_times' => 'Số Lần Lấy Mẫu (đã gộp vào Mẫu Sơ/Thứ Cấp)',
        'Sampling_amount' => 'Số Lượng Lấy Mẫu (đã gộp vào Mẫu Sơ/Thứ Cấp)',
        'sampling_uint' => 'Đơn Vị (đã gộp vào Mẫu Sơ/Thứ Cấp)',
        'sampled_by' => 'Người Lấy (đã bỏ)',
        'sampled_date' => 'Ngày Lấy (đã bỏ)',
    ];

    /** Quyền nhập cột Số PO - do bộ phận đơn hàng nhập */
    public const PERMISSION_PO = 'packaging_notification_update_po';

    /** Quyền nhập các cột lấy mẫu và lý do */
    public const PERMISSION_SAMPLING = 'packaging_notification_update_sampling';

    /**
     * Cột nào thuộc quyền nào. Mỗi cột chỉ nằm ở đúng một quyền, cột không liệt kê ở đây
     * coi như thuộc nhóm lấy mẫu (xem fieldPermission).
     */
    public const PO_FIELDS = ['PO_no'];

    protected $fillable = [
        'plan_master_id',
        'plan_list_id',
        'is_manual',
        'PO_no',
        'primary_sample',
        'secondary_sample',
        'Reason',
        'sampled_confirmed',
        'updated_by',
        'created_by',
    ];

    protected $casts = [
        'is_manual' => 'boolean',
        'sampled_confirmed' => 'boolean',
    ];

    /** Tên các cột người dùng được nhập (INPUT_LABELS là nguồn duy nhất) */
    public static function inputFields(): array
    {
        return array_keys(self::INPUT_LABELS);
    }

    /** Nhãn tiếng Việt của một cột, dùng khi hiển thị lịch sử nhập liệu */
    public static function inputLabel(string $field): string
    {
        return self::INPUT_LABELS[$field] ?? self::LEGACY_LABELS[$field] ?? $field;
    }

    /** Quyền cần có để nhập một cột */
    public static function fieldPermission(string $field): string
    {
        return in_array($field, self::PO_FIELDS, true)
            ? self::PERMISSION_PO
            : self::PERMISSION_SAMPLING;
    }

    /** Các cột người dùng được phép nhập, theo hai quyền họ đang có */
    public static function editableFields(bool $canEditPo, bool $canEditSampling): array
    {
        return array_values(array_filter(
            self::inputFields(),
            fn($field) => self::fieldPermission($field) === self::PERMISSION_PO
                ? $canEditPo
                : $canEditSampling
        ));
    }

    public function planMaster()
    {
        return $this->belongsTo(PlanMaster::class, 'plan_master_id');
    }

    /** Thị trường nội địa */
    public const DOMESTIC_MARKET_CODE = 'VN';

    /** Công đoạn đóng gói trong stage_plan */
    public const PACKAGING_STAGE_CODE = 7;

    /** Bảo trì - hiệu chuẩn, không phải công đoạn sản xuất nên loại khi tính trạng thái */
    public const MAINTENANCE_STAGE_CODE = 8;

    /** Nhãn trạng thái theo công đoạn cuối đã hoàn tất, khớp với lưới Kế Hoạch Sản Xuất */
    public const STAGE_STATUS_LABELS = [
        1 => 'Đã Cân',
        3 => 'Đã Pha chế',
        4 => 'Đã THT',
        5 => 'Đã định hình',
        6 => 'Đã Bao phim',
        7 => 'Hoàn Tất ĐG',
    ];

    /**
     * Khung truy vấn chung: lô còn hiệu lực của một kế hoạch tháng, đã join sẵn
     * finished_product_category (fpc), market (mk) và nhóm chia lô (sg).
     *
     * sg là leftJoin nên sg.main_parkaging_id khác NULL nghĩa là lô có chia lô: nhóm
     * cùng main_parkaging_id có từ 2 lô còn hiệu lực trở lên. Nhóm chỉ 1 dòng nghĩa là
     * cả lô đóng gói thành một quy cách duy nhất.
     */
    private static function baseQuery(int $planListId)
    {
        $splitGroups = DB::table('plan_master as g')
            ->where('g.plan_list_id', $planListId)
            ->where('g.active', 1)
            ->where('g.cancel', 0)
            ->whereNotNull('g.main_parkaging_id')
            ->groupBy('g.main_parkaging_id')
            ->havingRaw('COUNT(*) > 1')
            ->select('g.main_parkaging_id');

        return DB::table('plan_master as pm')
            ->leftJoin('finished_product_category as fpc', 'pm.product_caterogy_id', '=', 'fpc.id')
            ->leftJoin('market as mk', 'fpc.market_id', '=', 'mk.id')
            ->leftJoinSub($splitGroups, 'sg', 'sg.main_parkaging_id', '=', 'pm.main_parkaging_id')
            ->where('pm.plan_list_id', $planListId)
            ->where('pm.active', 1)
            ->where('pm.cancel', 0);
    }

    /**
     * Các lô của một kế hoạch tháng cần có thông báo đóng gói.
     *
     * Quy tắc nghiệp vụ (dùng chung cho cả lúc gửi kế hoạch lẫn lúc hiển thị trang,
     * để hai nơi không bao giờ lệch nhau) - lô còn hiệu lực và thoả MỘT TRONG HAI:
     *   - có chia lô: nhóm main_parkaging_id có từ 2 lô trở lên, kể cả lô thị trường VN
     *   - không phải thị trường nội địa VN, kể cả lô không chia
     * Lô chưa gán thị trường vẫn giữ lại: chưa xác định thì chưa thể coi là nội địa.
     *
     * Phần còn lại (không chia lô VÀ thị trường VN) không sinh tự động, nhưng người dùng
     * vẫn thêm tay được - xem manualCandidateQuery.
     *
     * @return \Illuminate\Database\Query\Builder Query trên plan_master (alias 'pm')
     */
    public static function eligibleQuery(int $planListId)
    {
        return self::applyRule(self::baseQuery($planListId));
    }

    /** Ràng buộc quy tắc tự động lên một query đã dựng từ baseQuery */
    private static function applyRule($query)
    {
        return $query->where(function ($q) {
            $q->whereNotNull('sg.main_parkaging_id')
                ->orWhereNull('mk.code')
                ->orWhere('mk.code', '<>', self::DOMESTIC_MARKET_CODE);
        });
    }

    /**
     * Các lô hiển thị trên lưới: lô theo quy tắc tự động, cộng thêm lô người dùng đã
     * chủ động tạo thông báo (is_manual = 1).
     */
    public static function visibleQuery(int $planListId)
    {
        $manual = DB::table('plan_master_infor_parkaging')
            ->where('plan_list_id', $planListId)
            ->where('is_manual', 1)
            ->select('plan_master_id');

        return self::baseQuery($planListId)->where(function ($q) use ($manual) {
            self::applyRule($q)->orWhereIn('pm.id', $manual);
        });
    }

    /**
     * Các lô được phép thêm tay: phần bù của quy tắc tự động (không chia lô VÀ thị trường
     * VN) và chưa có dòng thông báo nào.
     */
    public static function manualCandidateQuery(int $planListId)
    {
        $existing = DB::table('plan_master_infor_parkaging')
            ->where('plan_list_id', $planListId)
            ->select('plan_master_id');

        return self::baseQuery($planListId)
            ->whereNull('sg.main_parkaging_id')
            ->where('mk.code', self::DOMESTIC_MARKET_CODE)
            ->whereNotIn('pm.id', $existing);
    }

    /**
     * Sinh dòng thông báo đóng gói cho toàn bộ lô đủ điều kiện của một kế hoạch tháng.
     *
     * Gọi khi người dùng bấm "Gửi Kế Hoạch Tháng". Dùng insertOrIgnore dựa trên ràng buộc
     * unique(plan_master_id) nên gửi lại kế hoạch không sinh dòng trùng và không đè mất
     * dữ liệu người dùng đã nhập, cũng không đụng tới dòng thêm tay.
     *
     * @return int Số dòng vừa được tạo
     */
    public static function createForPlanList(int $planListId): int
    {
        $planMasters = self::eligibleQuery($planListId)
            ->select('pm.id', 'pm.plan_list_id')
            ->get();

        if ($planMasters->isEmpty()) {
            return 0;
        }

        $now = now();

        $rows = $planMasters->map(fn($pm) => [
            'plan_master_id' => $pm->id,
            'plan_list_id' => $pm->plan_list_id,
            'is_manual' => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ])->all();

        return DB::table('plan_master_infor_parkaging')->insertOrIgnore($rows);
    }

    /**
     * Thời gian đóng gói dự kiến và công đoạn lô đã đi tới, cho từng lô trên lưới.
     *
     * Công đoạn 1-6 chỉ tồn tại ở lô chính (main_parkaging_id) còn công đoạn đóng gói
     * được xếp lịch riêng cho từng lô chia, nên trạng thái phải gộp stage_plan của cả
     * lô chính lẫn chính lô đó; riêng giờ đóng gói thì lấy đúng dòng của lô đang xét.
     *
     * @param  Collection  $lots  Các lô có sẵn id và main_parkaging_id
     * @return array<int, array{start: ?string, end: ?string, actual_start: ?string, actual_end: ?string, status: string}>
     */
    public static function stageInfoFor(Collection $lots): array
    {
        $ids = $lots->pluck('id')
            ->merge($lots->pluck('main_parkaging_id'))
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (empty($ids)) {
            return [];
        }

        $stages = DB::table('stage_plan')
            ->whereIn('plan_master_id', $ids)
            ->where('active', 1)
            ->where('stage_code', '<>', self::MAINTENANCE_STAGE_CODE)
            ->select('plan_master_id', 'stage_code', 'finished', 'start', 'end', 'actual_start', 'actual_end')
            ->get()
            ->groupBy('plan_master_id');

        $info = [];

        foreach ($lots as $lot) {
            $own = $stages->get($lot->id, collect());
            $main = $lot->main_parkaging_id && $lot->main_parkaging_id != $lot->id
                ? $stages->get($lot->main_parkaging_id, collect())
                : collect();

            $all = $own->concat($main);
            $packaging = $own->firstWhere('stage_code', self::PACKAGING_STAGE_CODE);

            $info[$lot->id] = [
                'start' => $packaging->start ?? null,
                'end' => $packaging->end ?? null,
                'actual_start' => $packaging->actual_start ?? null,
                'actual_end' => $packaging->actual_end ?? null,
                'status' => self::statusFromStages($all),
            ];
        }

        return $info;
    }

    /**
     * Sắp xếp lại danh sách lô để hiển thị trên lưới: ưu tiên lô có thời gian đóng gói
     * dự kiến sớm nhất lên trước, đồng thời gom các lô cùng mã BTP (intermediate_code)
     * nằm cạnh nhau - tránh tình trạng các lô chia của cùng một BTP bị tách rời nhau khi
     * mỗi lô chia lại có giờ đóng gói khác nhau.
     *
     * Thực hiện bằng cách nhóm theo mã BTP, xếp các NHÓM theo thời gian đóng gói sớm
     * nhất trong nhóm, rồi trong từng nhóm xếp tiếp theo thời gian đóng gói của chính
     * lô đó. Lô chưa xếp lịch (start = null) bị đẩy xuống cuối thay vì lên đầu, vì
     * "chưa có lịch" không phải là "sớm nhất".
     *
     * @param  \Illuminate\Support\Collection  $datas  Kết quả truy vấn đã có sẵn 'id' và
     *                                                  'intermediate_code'
     * @param  array<int, array{start: ?string}>  $stageInfo  Kết quả của stageInfoFor()
     */
    public static function sortForDisplay(Collection $datas, array $stageInfo): Collection
    {
        $timestamp = function ($item) use ($stageInfo) {
            $start = $stageInfo[$item->id]['start'] ?? null;

            return $start ? strtotime($start) : PHP_INT_MAX;
        };

        $groups = $datas->groupBy('intermediate_code');
        $groupOrder = $groups->map(fn($items) => $items->min($timestamp))->sort();

        return $groupOrder->keys()
            ->flatMap(fn($code) => $groups->get($code)->sortBy($timestamp)->values())
            ->values();
    }

    /** Nhãn trạng thái từ tập stage_plan của một lô */
    private static function statusFromStages(Collection $stages): string
    {
        $finished = $stages->where('finished', 1);

        if ($finished->isEmpty()) {
            return 'Chưa làm';
        }

        $maxFinished = (int) $finished->max('stage_code');

        // Đã xong công đoạn cuối cùng còn hiệu lực của lô
        if ($maxFinished === (int) $stages->max('stage_code')) {
            return 'Hoàn Tất';
        }

        return self::STAGE_STATUS_LABELS[$maxFinished] ?? 'Chưa làm';
    }
}
