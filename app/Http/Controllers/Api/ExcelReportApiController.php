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
 * Lô thực tế: stage_plan.actual_start có, [actual_start, actual_end hoặc now) giao khung ngày; bỏ stage_code 8 (Bảo Trì).
 * Định mức: quota active cùng phòng; CĐ ≤ 6 khớp intermediate_code, CĐ 7 khớp intermediate_code + finished_product_code
 * (như SchedualController::checkQuotaRoom). Định mức = p_time + m_time (giờ).
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
            ->whereNotNull('sp.actual_start')
            ->where('sp.actual_start', '<', $to)
            ->whereRaw('COALESCE(sp.actual_end, NOW()) > ?', [$from])
            ->orderBy('room.order_by')->orderBy('room.code')->orderBy('sp.actual_start')
            ->select(
                'sp.id',
                'room.code as room_code',
                'room.name as room_name',
                'pn.name as product_name',
                DB::raw('COALESCE(pm.actual_batch, pm.batch) as batch'),
                'q.p_time',
                'q.m_time'
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

        $rows = $lots->map(function ($l) use ($toMinutes) {
            $p = $toMinutes($l->p_time);
            $m = $toMinutes($l->m_time);
            $quota = ($p === null && $m === null) ? '' : sprintf('%02d:%02d', intdiv($p + $m, 60), ($p + $m) % 60);

            return [
                'room' => trim($l->room_code . ' - ' . $l->room_name),
                'batch' => trim(($l->product_name ?? '') . ' - ' . ($l->batch ?? ''), ' -'),
                'quota' => $quota === '' ? 'Chưa có định mức' : $quota,
            ];
        })->values()->all();

        return $this->xml('batches', $rows, [
            'from' => $from->format('Y-m-d H:i'),
            'to' => $to->format('Y-m-d H:i'),
            'count' => count($rows),
        ]);
    }
}
