<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PublicationTrackingPeriod extends Model
{
    use HasFactory;

    protected $table = 'publication_tracking_period';

    protected $fillable = [
        'year',
        'month',
        'start_date',
        'end_date',
        'deparment_code',
        'status',
        'note',
        'created_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function details()
    {
        return $this->hasMany(PublicationTrackingDetail::class, 'period_id');
    }

    /**
     * Nhãn kỳ theo dõi của chu kỳ bắt đầu ngày $start.
     *
     * Kỳ theo dõi là tháng lên ấn bản, đi sau chu kỳ thu thập dữ liệu 2 tháng:
     * chu kỳ 20/07 - 19/08 là kỳ theo dõi của tháng 09.
     *
     * Cột year / month trong bảng vẫn là tháng bắt đầu chu kỳ (07), chỉ dùng làm
     * khoá tra cứu kỳ, không phải tháng hiển thị.
     */
    public static function labelForCycleStart(Carbon $start): string
    {
        $target = $start->copy()->addMonthsNoOverflow(2);

        return 'Tháng ' . str_pad($target->month, 2, '0', STR_PAD_LEFT) . '/' . $target->year;
    }

    /** Nhãn hiển thị của kỳ, ví dụ chu kỳ 20/07/2026 - 19/08/2026 -> "Tháng 09/2026" */
    public function getLabelAttribute()
    {
        return static::labelForCycleStart($this->start_date);
    }

    /** Khoảng ngày của chu kỳ, ví dụ: "20/08/2026 - 19/09/2026" */
    public function getRangeLabelAttribute()
    {
        return $this->start_date->format('d/m/Y') . ' - ' . $this->end_date->format('d/m/Y');
    }
}
