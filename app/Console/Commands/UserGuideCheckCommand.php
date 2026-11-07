<?php

namespace App\Console\Commands;

use App\Http\Controllers\Pages\UserGuide\UserGuideController;
use App\Support\GuidePermissions;
use App\Support\UserGuideLinks;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;

/**
 * KIỂM TRA HƯỚNG DẪN SỬ DỤNG CÒN KHỚP VỚI MÃ NGUỒN
 *
 * Chạy sau mỗi lần sửa chức năng (quy tắc trong .agents/skills/common-rules):
 *
 *   php artisan pms:user-guide-check
 *
 * Lỗi (thoát mã 1):
 *  - tài liệu trong UserGuideController::GUIDES thiếu toc.php;
 *  - mục trong toc.php trỏ tới file section, route, helpRoutes, quyền, file 'sources' không còn tồn tại;
 *  - liên kết #neo trong section trỏ tới mục không có (cùng tài liệu hoặc tài liệu khác);
 *  - trùng id mục trong một tài liệu;
 *  - ảnh (shared/shot hoặc asset('img/userGuide/...')) không còn file.
 * Cảnh báo:
 *  - file trong 'sources' của mục được sửa SAU file section: chức năng đã đổi, phải xem lại hướng dẫn
 *    (sửa section, hoặc nếu nội dung vẫn đúng thì cập nhật updated_at của tài liệu và lưu lại section);
 *  - màn hình có trên leftNAV nhưng nút "?" chưa dẫn tới mục hướng dẫn nào;
 *  - quyền trong bảng permissions chưa khai ở App\Support\GuidePermissions (mục Phân Quyền của hướng dẫn).
 * Lỗi phân quyền: quyền khai ở GuidePermissions::MAP không còn trong bảng permissions.
 */
class UserGuideCheckCommand extends Command
{
    protected $signature = 'pms:user-guide-check {--all : In cả cảnh báo màn hình trên menu chưa có hướng dẫn}';

    protected $description = 'Kiểm tra mục lục, liên kết, route, quyền và độ mới của Hướng Dẫn Sử Dụng so với mã nguồn';

    private array $errors = [];

    private array $warnings = [];

    public function handle(): int
    {
        $guides = array_keys(UserGuideController::GUIDES);
        $permissions = DB::table('permissions')->pluck('name')->flip()->all();

        foreach ($guides as $guide) {
            if (!File::exists($this->dir($guide) . '/toc.php')) {
                $this->errors[] = "$guide: thiếu toc.php";
            }
        }
        if ($this->errors) {
            return $this->finish($guides, 0, []);
        }

        // id các mục + id phần tử trong section, theo từng tài liệu (để kiểm #neo)
        $anchors = [];
        foreach ($guides as $guide) {
            $anchors[$guide] = [];
            foreach (UserGuideLinks::groups($guide) as $items) {
                foreach ($items as $item) {
                    if (isset($anchors[$guide][$item['id']])) {
                        $this->errors[] = "$guide: trùng id mục '{$item['id']}'";
                    }
                    $anchors[$guide][$item['id']] = true;
                }
            }
            foreach (File::glob($this->dir($guide) . '/sections/*.blade.php') as $file) {
                preg_match_all('/\bid="([^"{]+)"/', File::get($file), $m);
                $anchors[$guide] += array_fill_keys($m[1], true);
            }
        }

        $count = 0;
        foreach ($guides as $guide) {
            foreach (UserGuideLinks::groups($guide) as $items) {
                foreach ($items as $item) {
                    $count++;
                    $where = "$guide#{$item['id']}";
                    $section = $this->dir($guide) . "/sections/{$item['file']}.blade.php";

                    if (!File::exists($section)) {
                        $this->errors[] = "$where: thiếu file sections/{$item['file']}.blade.php";
                        continue;
                    }

                    foreach (array_merge((array) ($item['route'] ?? []), $item['helpRoutes'] ?? []) as $route) {
                        if (!Route::has($route)) {
                            $this->errors[] = "$where: route '$route' không còn tồn tại";
                        }
                    }

                    $perm = $item['perm'] ?? null;
                    if ($perm && !str_starts_with($perm, 'role:') && !isset($permissions[$perm])) {
                        $this->errors[] = "$where: quyền '$perm' không có trong bảng permissions";
                    }

                    $source = File::get($section);
                    $this->checkAnchors($where, $guide, $source, $anchors);
                    $this->checkImages($where, $source);
                    $this->checkShots($where, $item, $source);
                    $this->checkSources($where, $item, $section);
                }
            }
        }

        $this->checkPermissionMap($guides, $permissions);

        // Màn hình trên menu trái chưa có mục hướng dẫn: chỉ in khi --all (PMS còn nhiều màn hình chưa viết)
        $uncovered = [];
        if ($this->option('all')) {
            // Bỏ phần comment Blade / HTML: mục menu đang ẩn không cần hướng dẫn
            $nav = preg_replace(['/\{\{--.*?--\}\}/s', '/<!--.*?-->/s'], '', File::get(resource_path('views/layout/leftNAV.blade.php')));
            preg_match_all("/route\('(pages\.[A-Za-z_.]+)'/", $nav, $m);
            $uncovered = array_values(array_filter(
                array_unique($m[1]),
                fn ($route) => !str_starts_with($route, 'pages.userGuide.') && !UserGuideLinks::forRoute($route)
            ));
        }

        return $this->finish($guides, $count, $uncovered);
    }

    /**
     * Bảng phân quyền của hướng dẫn (App\Support\GuidePermissions) phải khớp bảng permissions:
     *  - lỗi: khoá tài liệu không có trong GUIDES, quyền khai trong MAP / UNUSED không còn trong bảng permissions;
     *  - cảnh báo: quyền trong bảng permissions chưa khai ở MAP (tài liệu nào dùng) cũng không nằm trong UNUSED.
     */
    private function checkPermissionMap(array $guides, array $permissions): void
    {
        foreach (array_keys(GuidePermissions::MAP) as $guide) {
            if (!in_array($guide, $guides, true)) {
                $this->errors[] = "GuidePermissions::MAP: khoá '$guide' không có trong UserGuideController::GUIDES";
            }
        }

        $mapped = GuidePermissions::mappedPermissions();
        foreach ($mapped as $perm) {
            if (!isset($permissions[$perm])) {
                $this->errors[] = "GuidePermissions::MAP: quyền '$perm' không có trong bảng permissions";
            }
        }

        $known = array_flip(array_merge($mapped, array_keys(GuidePermissions::UNUSED)));
        foreach (array_keys($permissions) as $perm) {
            if (!isset($known[$perm])) {
                $this->warnings[] = "Quyền '$perm' chưa khai ở GuidePermissions::MAP (chức năng nào dùng) hoặc UNUSED - "
                    . 'mục Phân Quyền của hướng dẫn chưa nhắc tới';
            }
        }
    }

    private function finish(array $guides, int $count, array $uncovered): int
    {
        foreach ($this->errors as $error) {
            $this->error('  ✗ ' . $error);
        }
        foreach ($this->warnings as $warning) {
            $this->warn('  ! ' . $warning);
        }
        foreach ($uncovered as $route) {
            $this->line("  · Màn hình '$route' trên menu chưa có mục hướng dẫn");
        }

        if ($this->errors) {
            $this->error(count($this->errors) . ' lỗi trên ' . count($guides) . " tài liệu / $count mục.");

            return self::FAILURE;
        }

        $this->info('Hướng dẫn khớp với mã nguồn: ' . count($guides) . " tài liệu, $count mục, "
            . count($this->warnings) . ' cảnh báo cần xem lại'
            . ($uncovered ? ', ' . count($uncovered) . ' màn hình chưa có hướng dẫn' : '') . '.');

        return self::SUCCESS;
    }

    /** href="#neo" (cùng tài liệu) và route('pages.userGuide.show', 'khoá') }}#neo (tài liệu khác) */
    private function checkAnchors(string $where, string $guide, string $source, array $anchors): void
    {
        preg_match_all('/href="#([^"{]+)"/', $source, $local);
        foreach ($local[1] as $anchor) {
            if (!isset($anchors[$guide][$anchor])) {
                $this->errors[] = "$where: liên kết #$anchor không trỏ tới mục nào";
            }
        }

        preg_match_all("/route\('pages\.userGuide\.show',\s*'([^']+)'\)\s*\}\}#([\w-]+)/", $source, $cross, PREG_SET_ORDER);
        foreach ($cross as [, $target, $anchor]) {
            if (!isset($anchors[$target][$anchor])) {
                $this->errors[] = "$where: liên kết sang $target#$anchor không trỏ tới mục nào";
            }
        }
    }

    /** Ảnh chèn bằng shared/shot ('src' => '...') hoặc asset('img/userGuide/...') phải có file */
    /**
     * Ảnh chụp màn hình (shared/shot): mục có màn hình ('route') mà chưa chèn ảnh nào thì cảnh báo; controller / view
     * của màn hình được sửa SAU lần chụp ảnh thì cảnh báo chụp lại (.agents/skills/user-guide/scripts: plan.php + shoot.mjs).
     * Mục khai 'shots' => false trong toc.php thì bỏ qua.
     */
    private function checkShots(string $where, array $item, string $source): void
    {
        if (empty($item['route']) || ($item['shots'] ?? null) === false || !Route::has($item['route'])) {
            return;
        }
        preg_match_all("/shared\.shot',\s*\['src'\s*=>\s*'([^']+)'/", $source, $m);
        $images = array_values(array_filter(array_map('public_path', $m[1]), 'file_exists'));
        if (!$images) {
            $this->warnings[] = "$where: màn hình chưa có ảnh chụp minh hoạ";

            return;
        }

        $shotAt = min(array_map('filemtime', $images));
        $changed = array_filter($this->screenSources($item['route']), fn ($file) => filemtime($file) > $shotAt);
        if ($changed) {
            $this->warnings[] = "$where: giao diện đã sửa sau khi chụp ảnh (" . implode(', ', array_map(
                fn ($file) => str_replace([base_path() . DIRECTORY_SEPARATOR, '\\'], ['', '/'], $file),
                array_slice(array_values($changed), 0, 2)
            )) . ') - chụp lại ảnh';
        }
    }

    /** Controller + các view mà controller trả về (view('pages.x.y.list') -> các file trong thư mục pages/x/y). */
    private function screenSources(string $routeName): array
    {
        $route = Route::getRoutes()->getByName($routeName);
        $controller = $route ? ltrim(explode('@', (string) $route->getActionName())[0], '\\') : null;
        if (!$controller || !class_exists($controller)) {
            return [];
        }

        $file = (new \ReflectionClass($controller))->getFileName();
        $files = [$file];
        preg_match_all("/view\(\s*'([\w.]+)'/", File::get($file), $m);
        foreach (array_unique($m[1]) as $view) {
            $dir = resource_path('views/' . str_replace('.', '/', preg_replace('/\.[^.]+$/', '', $view)));
            if (str_starts_with($view, 'pages.') && File::isDirectory($dir)) {
                $files = array_merge($files, File::glob($dir . '/*.blade.php'));
            }
        }

        return array_unique($files);
    }

    private function checkImages(string $where, string $source): void
    {
        preg_match_all("/shared\.shot',\s*\['src'\s*=>\s*'([^']+)'/", $source, $shots);
        preg_match_all("/asset\('(img\/userGuide\/[^']+)'\)/", $source, $assets);
        foreach (array_merge($shots[1], $assets[1]) as $src) {
            if (!File::exists(public_path($src))) {
                $this->errors[] = "$where: thiếu ảnh public/$src";
            }
        }
    }

    /**
     * 'sources' => ['đường dẫn file hoặc thư mục', ...]: file mã nguồn của chức năng mà mục mô tả.
     * File (hoặc file bất kỳ trong thư mục) sửa sau section => cảnh báo xem lại hướng dẫn.
     */
    private function checkSources(string $where, array $item, string $section): void
    {
        $sectionAt = filemtime($section);
        foreach ($item['sources'] ?? [] as $path) {
            $full = base_path($path);
            if (!File::exists($full)) {
                $this->errors[] = "$where: file nguồn '$path' không còn tồn tại";
                continue;
            }

            $files = File::isDirectory($full) ? array_map(fn ($f) => $f->getPathname(), File::allFiles($full)) : [$full];
            $newest = max(array_map('filemtime', $files) ?: [0]);
            if ($newest > $sectionAt + 60) {
                $this->warnings[] = "$where: '$path' sửa lúc " . date('d/m/Y H:i', $newest)
                    . ' sau hướng dẫn (' . date('d/m/Y H:i', $sectionAt) . ') - xem lại mục này';
            }
        }
    }

    private function dir(string $guide): string
    {
        return resource_path("views/pages/userGuide/{$guide}");
    }
}
