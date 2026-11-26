<?php

namespace App\Support;

/**
 * Loại nguyên liệu của lịch cân (stage_code 1 Cân NL, 2 Cân NL khác), theo công đoạn nhận = hậu tố nextcessor_code
 * của dòng cân. Cùng nhãn / màu với nhãn trên Gantt (weighKindClass trong resources/js/Plugins/WeighAssist +
 * .fc-weigh-kind-* trong calendar.css) - đổi ở đây thì đổi cả hai chỗ đó.
 */
class WeighKind
{
    public const KINDS = [
        'pc'   => ['label' => 'NL PC',     'icon' => '🧪', 'color' => '#1d4ed8'],
        'tht'  => ['label' => 'NL THT',    'icon' => '🧪', 'color' => '#1d4ed8'],
        'nang' => ['label' => 'Nang rỗng', 'icon' => '💊', 'color' => '#7e22ce'],
        'bp'   => ['label' => 'NL BP',     'icon' => '🖌️', 'color' => '#be185d'],
        'dg'   => ['label' => 'NL ĐG',     'icon' => '📦', 'color' => '#475569'],
    ];

    /** ['key', 'label', 'icon', 'color'] hoặc null nếu không phải lịch cân / không xác định được */
    public static function of($stageCode, ?string $nextcessorCode): ?array
    {
        $stage = (int) $stageCode;
        if (!in_array($stage, [1, 2], true)) {
            return null;
        }
        $next = $nextcessorCode ? (int) substr(strrchr($nextcessorCode, '_') ?: '', 1) : 0;
        $key = $stage === 1
            ? ($next === 4 ? 'tht' : 'pc')
            : ([5 => 'nang', 6 => 'bp', 7 => 'dg'][$next] ?? null);

        return $key ? ['key' => $key] + self::KINDS[$key] : null;
    }
}
