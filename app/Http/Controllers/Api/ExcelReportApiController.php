<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * API cho file Excel bên ngoài (WEBSERVICE + FILTERXML) — không đăng nhập, xác thực bằng ?key= so với
 * .env PM_EXCEL_API_KEY. Trả XML vì FILTERXML của Excel chỉ đọc XML.
 *
 *  GET /api/excel/productions                       → danh sách phân xưởng
 *  GET /api/excel/actual-batches?date=Y-m-d&production=PXV1
 *      → các lô sản xuất thực tế trong ngày báo cáo (06:00 ngày date → 06:00 hôm sau), theo phòng, kèm định mức.
 *
 * Mỗi lô tách 2 dòng: [Sản xuất] actual_start→actual_end (định mức P+M) và [Vệ sinh] actual_*_clearning
 * (định mức C2 nếu VS-II, còn lại C1); dòng nào giao khung ngày mới lấy. Bỏ stage_code 8 (Bảo Trì).
 * Định mức: quota active cùng phòng; CĐ ≤ 6 khớp intermediate_code, CĐ 7 khớp intermediate_code + finished_product_code
 * (như SchedualController::checkQuotaRoom). Định mức = p_time + m_time  (giờ) cho dòng sản xuất.
 */
class ExcelReportApiController extends Controller
{
    private function authorized(Request $request): bool
    {
        $key = (string) config('services.pm_excel.key', '');

        return $key !== '' && hash_equals($key, (string) $request->query('key', ''));
    }

    private function xml(string $root, array $rows, array $attrs = [], int $status = 200)
    {
        $doc = new \DOMDocument('1.0', 'UTF-8');
        $el = $doc->appendChild($doc->createElement($root));
        foreach ($attrs as $k => $v) {
            $el->setAttribute($k, (string) $v);
        }
        foreach ($rows as $row) {
            $r = $el->appendChild($doc->createElement('r'));
            foreach ($row as $k => $v) {
                $r->appendChild($doc->createElement($k))->appendChild($doc->createTextNode((string) $v));
            }
        }

        return response($doc->saveXML(), $status)->header('Content-Type', 'application/xml; charset=UTF-8');
    }

    private function denied()
    {
        return $this->xml('error', [['message' => 'Sai hoặc thiếu API key']], [], 401);
    }

    public function productions(Request $request)
    {
        if (! $this->authorized($request)) {
            return $this->denied();
        }

        $rows = DB::table('production')->orderBy('id')->get(['code', 'name'])
            ->map(fn ($p) => ['code' => $p->code, 'name' => $p->name])->all();

        return $this->xml('productions', $rows);
    }

    public function actualBatches(Request $request)
    {
        if (! $this->authorized($request)) {
            return $this->denied();
        }

        $production = (string) $request->query('production', '');
        if (! DB::table('production')->where('code', $production)->exists()) {
            return $this->xml('error', [['message' => 'Phân xưởng không hợp lệ']], [], 422);
        }

        try {
            // Excel có thể gửi Y-m-d hoặc số serial ngày (TEXT(...) khác locale) — nhận cả 2
            $raw = (string) $request->query('date', '');
            $day = is_numeric($raw)
                ? Carbon::create(1899, 12, 30)->addDays((int) $raw)
                : Carbon::createFromFormat('Y-m-d', $raw);
        } catch (\Throwable $e) {
            return $this->xml('error', [['message' => 'Ngày không hợp lệ (yyyy-mm-dd)']], [], 422);
        }

        $from = $day->copy()->setTime(6, 0, 0);
        $to = $from->copy()->addDay();
        // Việc chưa có giờ kết thúc chỉ coi là "đang chạy" nếu bắt đầu ≤ 2 ngày trước ca (bỏ dữ liệu cũ quên trả phòng)
        $staleBefore = $from->copy()->subDays(2);

        $lots = DB::table('stage_plan as sp')
            ->join('room', 'sp.resourceId', '=', 'room.id')
            ->leftJoin('plan_master as pm', 'sp.plan_master_id', '=', 'pm.id')
            ->leftJoin('finished_product_category as fpc', 'sp.product_caterogy_id', '=', 'fpc.id')
            ->leftJoin('intermediate_category as ic', 'fpc.intermediate_code', '=', 'ic.intermediate_code')
            ->leftJoin('product_name as pn', 'ic.product_name_id', '=', 'pn.id')
            ->leftJoin('quota as q', function ($join) {
                $join->on('q.room_id', '=', 'sp.resourceId')
                    ->on('q.intermediate_code', '=', 'fpc.intermediate_code')
                    ->where('q.active', 1)
                    ->where(function ($w) {
                        $w->where('sp.stage_code', '<=', 6)
                            ->orWhereColumn('q.finished_product_code', 'fpc.finished_product_code');
                    });
            })
            ->where('sp.active', 1)
            ->where('sp.deparment_code', $production)
            ->where('sp.stage_code', '!=', 8)
            // Lô có Sản xuất hoặc Vệ sinh thực tế giao khung ngày
            ->where(function ($w) use ($from, $to, $staleBefore) {
                $w->where(function ($x) use ($from, $to, $staleBefore) {
                    $x->whereNotNull('sp.actual_start')->where('sp.actual_start', '<', $to)
                        ->whereRaw('COALESCE(sp.actual_end, NOW()) > ?', [$from])
                        ->where(fn ($o) => $o->whereNotNull('sp.actual_end')->orWhere('sp.actual_start', '>=', $staleBefore));
                })->orWhere(function ($x) use ($from, $to, $staleBefore) {
                    $x->whereNotNull('sp.actual_start_clearning')->where('sp.actual_start_clearning', '<', $to)
                        ->whereRaw('COALESCE(sp.actual_end_clearning, NOW()) > ?', [$from])
                        ->where(fn ($o) => $o->whereNotNull('sp.actual_end_clearning')->orWhere('sp.actual_start_clearning', '>=', $staleBefore));
                });
            })
            ->select(
                'sp.id',
                'room.code as room_code',
                'room.name as room_name',
                'room.order_by as room_order',
                'pn.name as product_name',
                DB::raw('COALESCE(pm.actual_batch, pm.batch) as batch'),
                'sp.actual_start', 'sp.actual_end',
                'sp.actual_start_clearning', 'sp.actual_end_clearning', 'sp.title_clearning',
                'q.p_time', 'q.m_time', 'q.C1_time', 'q.C2_time'
            )
            ->get()
            ->unique('id'); // phòng có >1 dòng định mức trùng → giữ 1

        $toMinutes = function ($t) {
            if ($t === null || $t === '') {
                return null;
            }
            $p = explode(':', $t);

            return (int) $p[0] * 60 + (int) ($p[1] ?? 0);
        };
        $hhmm = fn ($min) => $min === null ? 'Chưa có định mức' : sprintf('%02d:%02d', intdiv($min, 60), $min % 60);
        $span = function ($s, $e) {
            $s = Carbon::parse($s);
            $e = $e ? Carbon::parse($e) : null;

            return $s->format('d/m H:i') . ' → ' . ($e ? $e->format($e->isSameDay($s) ? 'H:i' : 'd/m H:i') : 'đang chạy');
        };
        $overlaps = fn ($s, $e) => $s && Carbon::parse($s)->lt($to) && ($e ? Carbon::parse($e)->gt($from) : Carbon::parse($s)->gte($staleBefore));

        $items = [];
        foreach ($lots as $l) {
            $room = trim($l->room_code . ' - ' . $l->room_name);
            $name = trim(($l->product_name ?? '') . ' - ' . ($l->batch ?? ''), ' -');

            if ($overlaps($l->actual_start, $l->actual_end)) {
                $p = $toMinutes($l->p_time);
                $m = $toMinutes($l->m_time);
                $items[] = [$l->room_order, $l->room_code, $l->actual_start, [
                    'room' => $room,
                    'batch' => '[Sản xuất] ' . $name . ' (' . $span($l->actual_start, $l->actual_end) . ')',
                    'quota' => $hhmm(($p === null && $m === null) ? null : $p + $m),
                ]];
            }
            if ($overlaps($l->actual_start_clearning, $l->actual_end_clearning)) {
                $isC2 = $l->title_clearning === 'VS-II';
                $label = $l->title_clearning ?: 'VS';
                $items[] = [$l->room_order, $l->room_code, $l->actual_start_clearning, [
                    'room' => $room,
                    'batch' => '[Vệ sinh ' . $label . '] ' . $name . ' (' . $span($l->actual_start_clearning, $l->actual_end_clearning) . ')',
                    'quota' => $hhmm($toMinutes($isC2 ? $l->C2_time : $l->C1_time)),
                ]];
            }
        }
        usort($items, fn ($a, $b) => [$a[0], $a[1], $a[2]] <=> [$b[0], $b[1], $b[2]]);
        $rows = array_column($items, 3);

        return $this->xml('batches', $rows, [
            'from' => $from->format('Y-m-d H:i'),
            'to' => $to->format('Y-m-d H:i'),
            'count' => count($rows),
        ]);
    }
}
