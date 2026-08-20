<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Thông báo đóng gói của một lô sản xuất (plan_master).
 *
 * Dòng được sinh tự động khi gửi kế hoạch tháng, ban đầu chỉ có plan_master_id và
 * plan_list_id; người dùng bổ sung số PO và thông tin lấy mẫu ở trang Thông Báo Đóng Gói.
 */
class PlanMasterInforParkaging extends Model
{
    use HasFactory;

    protected $table = 'plan_master_infor_parkaging';

    /**
     * Các cột người dùng được nhập trên lưới, kèm nhãn tiếng Việt.
     * Dùng chung cho việc lưu và hiển thị nên thêm cột mới chỉ cần sửa ở đây.
     */
    public const INPUT_LABELS = [
        'PO_no' => 'Số PO',
        'Sampling_specifications' => 'Quy Cách Lấy Mẫu',
        'Sampling_times' => 'Số Lần Lấy Mẫu',
        'Sampling_amount' => 'Số Lượng Lấy Mẫu',
        'sampling_uint' => 'Đơn Vị',
        'Reason' => 'Lý Do',
    ];

    /** Tên các cột người dùng được nhập (INPUT_LABELS là nguồn duy nhất) */
    public static function inputFields(): array
    {
        return array_keys(self::INPUT_LABELS);
    }

    protected $fillable = [
        'plan_master_id',
        'plan_list_id',
        'PO_no',
        'Sampling_specifications',
        'Sampling_times',
        'Sampling_amount',
        'sampling_uint',
        'Reason',
        'updated_by',
    ];

    protected $casts = [
        'Sampling_amount' => 'decimal:3',
    ];

    public function planMaster()
    {
        return $this->belongsTo(PlanMaster::class, 'plan_master_id');
    }

    /** Thị trường nội địa - lô bán trong nước không cần thông báo đóng gói */
    public const DOMESTIC_MARKET_CODE = 'VN';

    /**
     * Các lô của một kế hoạch tháng cần có thông báo đóng gói.
     *
     * Quy tắc nghiệp vụ (dùng chung cho cả lúc gửi kế hoạch lẫn lúc hiển thị trang,
     * để hai nơi không bao giờ lệch nhau):
     *   - lô còn hiệu lực: active = 1, cancel = 0
     *   - bỏ lô thị trường VN: hàng nội địa không phát sinh thông báo đóng gói
     *   - chỉ lấy lô CÓ CHIA LÔ: nhóm main_parkaging_id phải có từ 2 lô trở lên.
     *     Nhóm chỉ 1 dòng nghĩa là cả lô đóng gói thành một quy cách duy nhất nên
     *     không cần thông báo. Đếm nhóm cũng chỉ tính lô còn hiệu lực: nếu các lô
     *     con đã bị huỷ hết thì lô còn lại coi như không chia.
     *
     * @return \Illuminate\Database\Query\Builder Query trên plan_master (alias 'pm')
     */
    public static function eligibleQuery(int $planListId)
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
            ->joinSub($splitGroups, 'sg', 'sg.main_parkaging_id', '=', 'pm.main_parkaging_id')
            ->where('pm.plan_list_id', $planListId)
            ->where('pm.active', 1)
            ->where('pm.cancel', 0)
            // Lô chưa gán thị trường vẫn giữ lại: chưa xác định thì chưa thể coi là nội địa
            ->where(function ($q) {
                $q->whereNull('mk.code')
                    ->orWhere('mk.code', '<>', self::DOMESTIC_MARKET_CODE);
            });
    }

    /**
     * Sinh dòng thông báo đóng gói cho toàn bộ lô đủ điều kiện của một kế hoạch tháng.
     *
     * Gọi khi người dùng bấm "Gửi Kế Hoạch Tháng". Dùng insertOrIgnore dựa trên ràng buộc
     * unique(plan_master_id) nên gửi lại kế hoạch không sinh dòng trùng và không đè mất
     * dữ liệu người dùng đã nhập.
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
            'created_at' => $now,
            'updated_at' => $now,
        ])->all();

        return DB::table('plan_master_infor_parkaging')->insertOrIgnore($rows);
    }
}
