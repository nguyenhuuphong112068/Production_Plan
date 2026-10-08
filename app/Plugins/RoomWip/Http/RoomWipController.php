<?php

namespace App\Plugins\RoomWip\Http;

use App\Http\Controllers\Controller;
use App\Services\WipCoverageService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RoomWipController extends Controller
{
    /** Quyền thấy nút bật / tắt chế độ xem tồn BTP trên Gantt */
    public const PERMISSION = 'schedual_wip_rows';

    public function access()
    {
        return response()->json(['allowed' => $this->allowed()]);
    }

    /**
     * Các phần lô đang / sẽ chờ vào công đoạn ĐH / BP / ĐG trong khung đang xem.
     * Mỗi phần là một lô con của công đoạn sau, nằm trong kho từ `entry` (mốc công
     * đoạn nguồn, như trang Tồn kho lý thuyết) tới `exit` (lúc lô con bắt đầu chạy).
     * Phần chưa xếp phòng có room = null: chỉ cộng vào dòng tổng công đoạn.
     * Trình duyệt tự cộng tồn lúc 06:00 từng ngày.
     */
    public function data(Request $request, WipCoverageService $coverage)
    {
        if (! $this->allowed()) {
            return response()->json(['success' => false, 'message' => 'Bạn không có quyền xem tồn BTP trên lịch.'], 403);
        }

        $request->validate([
            'from' => 'required|date',
            'to'   => 'required|date|after:from',
        ]);

        $from = Carbon::parse($request->from);
        $to = Carbon::parse($request->to);
        if ($from->diffInDays($to) > 200) {
            return response()->json(['success' => false, 'message' => 'Khung xem quá dài (tối đa 200 ngày).'], 422);
        }

        $productionCode = session('user')['production_code'];
        $now = Carbon::now();
        $horizon = (int) max(1, min(180, ceil($now->diffInDays($to, false)) + 1));
        $ledgers = $coverage->ledgers($productionCode, $now, $horizon)['ledgers'];

        $fromStr = $from->format('Y-m-d H:i:s');
        $toStr = $to->format('Y-m-d H:i:s');

        $parts = [];
        foreach (WipCoverageService::NEXT_GROUPS as $group) {
            foreach ($ledgers[$group] ?? [] as $lot) {
                if ($lot['entry'] === null || $lot['entry'] >= $toStr) {
                    continue;
                }
                foreach ($lot['splits'] ?? [] as $split) {
                    if ($split['start'] !== null && $split['start'] <= $fromStr) {
                        continue;   // đã rút trước đầu khung
                    }
                    $qty = round($lot['qty_dvl'] * $split['weight'], 2);
                    if ($qty <= 0) {
                        continue;
                    }

                    $parts[] = [
                        'group'   => $group,
                        'room'    => empty($split['room_id']) ? null : (int) $split['room_id'],
                        'qty'     => $qty,
                        'entry'   => $lot['entry'],
                        'exit'    => $split['start'],
                        'src'     => $lot['source_id'] ?? null,
                        'dst'     => $split['id'] ?? null,
                        'batch'   => $lot['batch'],
                        'product' => $lot['product_name'],
                    ];
                }
            }
        }

        // Mã phòng nguồn / phòng tiêu thụ cho bảng chi tiết
        $srcIds = array_values(array_unique(array_filter(array_column($parts, 'src'))));
        $srcRoom = [];
        foreach (array_chunk($srcIds, 1000) as $chunk) {
            DB::table('stage_plan as sp')
                ->leftJoin('room', 'room.id', '=', 'sp.resourceId')
                ->whereIn('sp.id', $chunk)
                ->select('sp.id', 'room.code')
                ->get()
                ->each(function ($row) use (&$srcRoom) {
                    $srcRoom[$row->id] = $row->code;
                });
        }
        $roomCode = DB::table('room')
            ->whereIn('id', array_values(array_unique(array_filter(array_column($parts, 'room')))))
            ->pluck('code', 'id');

        foreach ($parts as &$part) {
            $part['src_room'] = $srcRoom[$part['src']] ?? null;
            $part['dst_room'] = $part['room'] !== null ? ($roomCode[$part['room']] ?? null) : null;
        }
        unset($part);

        return response()->json([
            'success'   => true,
            'from'      => $fromStr,
            'to'        => $toStr,
            'day_start' => WipCoverageService::DAY_START_HOUR,
            'unit'      => 'ĐVL',
            'parts'     => $parts,
        ]);
    }

    private function allowed(): bool
    {
        return user_has_permission(session('user')['userId'], self::PERMISSION, 'boolean');
    }
}
