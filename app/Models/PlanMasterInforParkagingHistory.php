<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Vết thay đổi của một ô trên lưới Thông Báo Đóng Gói.
 */
class PlanMasterInforParkagingHistory extends Model
{
    use HasFactory;

    protected $table = 'plan_master_infor_parkaging_history';

    /** Chỉ có created_at, không có updated_at: đây là bảng ghi vết, không sửa lại */
    public const UPDATED_AT = null;

    protected $fillable = [
        'plan_master_id',
        'infor_parkaging_id',
        'action',
        'field',
        'old_value',
        'new_value',
        'changed_by',
    ];

    /** Nhãn tiếng Việt của hành động */
    public function getActionLabelAttribute(): string
    {
        return [
            'create' => 'Nhập mới',
            'update' => 'Chỉnh sửa',
            'lock' => 'Khoá',
            'unlock' => 'Mở Khoá',
        ][$this->action] ?? $this->action;
    }

    /** Nhãn tiếng Việt của cột bị đổi */
    public function getFieldLabelAttribute(): string
    {
        return PlanMasterInforParkaging::inputLabel($this->field);
    }

    public function getOldValueTextAttribute(): string
    {
        return self::displayValue($this->field, $this->old_value);
    }

    public function getNewValueTextAttribute(): string
    {
        return self::displayValue($this->field, $this->new_value);
    }

    /**
     * Hiển thị giá trị cho người đọc: ngày về d/m/Y, cờ xác nhận về Có/Không,
     * ô trống thì ghi rõ "(trống)" để phân biệt với việc xoá trắng.
     */
    public static function displayValue(string $field, $value): string
    {
        if ($value === null || $value === '') {
            return '(trống)';
        }

        if (str_ends_with($field, '_date')) {
            try {
                return Carbon::parse($value)->format('d/m/Y');
            } catch (\Exception) {
                return (string) $value;
            }
        }

        if ($field === 'sampled_confirmed') {
            return $value && $value !== '0' ? 'Đã lấy mẫu' : 'Chưa lấy mẫu';
        }

        return (string) $value;
    }
}
