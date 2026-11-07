<?php
/*
 * Dựng kế hoạch chụp màn hình cho một tài liệu hướng dẫn từ toc.php (chuyển từ bộ công cụ WMS).
 *
 *   php plan.php <khoá tài liệu> [id1,id2...]  > plan.json
 *
 * Mặc định mỗi mục có 'route' được chụp:
 *   - <id>.png      : màn hình, phần đầu vùng nội dung (.content-wrapper, cao tối đa 640px);
 *   - <id>-form.png : hộp thoại "Thêm..." của màn hình, chỉ khi section có bảng "Các ô nhập" (shared.fields).
 * Khai thêm trong toc.php:
 *   'shots' => false                 không chụp mục này
 *   'shots' => [[...], ...]          thay kế hoạch mặc định (khoá: name, url, js, clip, highlight, maxHeight, width,
 *                                    height, wait, afterJs, user, production)
 *   'extraShots' => [[...], ...]     ảnh từng bước, cộng thêm vào kế hoạch mặc định
 *   'shotProduction' => 'PXV2'       phân xưởng đang chọn khi chụp (mặc định PXV1 - nhiều dữ liệu nhất)
 *   'shotWait' => 6000               chờ thêm (ms) sau khi trang tải, cho màn hình React / tải ngầm lâu
 *   'shotMaxHeight' => 900           chiều cao tối đa ảnh màn hình (mặc định 640px)
 */
require __DIR__.'/../../../../vendor/autoload.php';
$app = require __DIR__.'/../../../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

const SHOT_USER = 'Admin';
const SHOT_PRODUCTION = 'PXV1';

$key = $argv[1] ?? exit("Thiếu khoá tài liệu\n");
$only = isset($argv[2]) ? explode(',', $argv[2]) : null;
$dir = resource_path("views/pages/userGuide/$key/sections");
$plan = [];

foreach (App\Support\UserGuideLinks::groups($key) as $items) {
    foreach ($items as $item) {
        if (empty($item['route']) || ($item['shots'] ?? null) === false || ($only && ! in_array($item['id'], $only, true))) {
            continue;
        }
        $url = route($item['route'], $item['params'] ?? []);
        $base = [
            'url' => parse_url($url, PHP_URL_PATH).(($q = parse_url($url, PHP_URL_QUERY)) ? "?$q" : ''),
            'user' => SHOT_USER,
            'production' => $item['shotProduction'] ?? SHOT_PRODUCTION,
            'width' => 1600,
            'wait' => $item['shotWait'] ?? 2500,
            'tab' => array_values((array) ($item['pane'] ?? [])),
        ];
        $shots = $item['shots'] ?? null;
        if ($shots === null) {
            $shots = [['name' => $item['id'], 'clip' => '.content-wrapper', 'maxHeight' => $item['shotMaxHeight'] ?? 640, 'pad' => 0]];
            if (str_contains((string) @file_get_contents("$dir/{$item['file']}.blade.php"), 'shared.fields')) {
                $shots[] = ['name' => $item['id'].'-form', 'js' => '__ug.clickCreate()', 'clip' => '.modal.show .modal-content',
                    'maxHeight' => 1400, 'pad' => 0];
            }
        }
        $shots = array_merge($shots, $item['extraShots'] ?? []);
        foreach ($shots as $shot) {
            $shot['name'] = $shot['name'] ?? $item['id'];
            $plan[] = $shot + $base;
        }
    }
}

echo json_encode($plan, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
