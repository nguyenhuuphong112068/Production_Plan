<?php

namespace App\Plugins\WipControl\Services;

/**
 * Mô phỏng "ngưng nguồn theo ngưỡng" cho các phòng của một công đoạn nguồn (chỉ tính,
 * không ghi DB). Dùng chung cho phòng PC/THT (nguồn chờ ĐH), ĐH (nguồn chờ BP với lô
 * bao phim, chờ ĐG với lô không bao phim) và BP (nguồn chờ ĐG).
 *
 * Mỗi lô có "cổng" = nhóm tồn mà nó vào kho khi bắt đầu, nếu nhóm đó có cài Max; lô đi
 * nhóm không cài Max thì không có cổng (lô chạy thay). Mỗi phòng chạy lần lượt các lô
 * của chính nó (giữ phòng, giữ thời lượng chiếm phòng của lịch cũ). Mỗi khi phòng rảnh,
 * chọn lô theo thứ tự:
 *  1. lô không có cổng tới hạn (bắt buộc chạy ngay để kịp công đoạn sau); lô có cổng
 *     không bị ép theo hạn này, tới hạn mà tồn chưa cho phép thì công đoạn sau lùi theo;
 *     nhưng mọi lô tới hạn cứng (ngày NL/BB không được vi phạm, của chính nó hoặc của
 *     công đoạn sau) đều bị ép;
 *  2. lô có cổng nếu tồn nhóm của nó cộng lượng của lô vẫn ≤ Max, hạn gần trước;
 *  3. lô không có cổng đã sẵn sàng, hạn gần trước;
 *  4. không có lô nào thì để phòng trống một bước.
 * Hạn của lô = giờ bắt đầu muộn nhất còn kịp công đoạn sau, không sớm hơn lịch cũ;
 * hạn "bắt buộc" tính ngược trong từng phòng để các lô cùng phòng không giành nhau
 * (lô sau chiếm phòng thì lô trước phải chạy sớm hơn đúng bằng thời gian chiếm).
 * Không bắt đầu trong ngày nghỉ, không đè khối cố định (công đoạn khác, bảo trì, lô
 * đang chạy, lô không được dời).
 */
class WipGate
{
    private const STEP = 1800;

    /**
     * @param array<int, array{id:int, pm:int, room:int, old:int, occ:int, dur:int, gate:?string, ready:int, due:int, qty:float, exit:?int, lag:int, hard?:int}> $lots
     *        gate = nhóm tồn có Max mà lô vào kho (null = lô chạy thay); due = giờ bắt đầu muộn nhất
     *        còn kịp công đoạn sau; exit = giờ công đoạn tiêu thụ bắt đầu; lag = thời gian chạy + chờ trước nó
     * @param array<string, array<int, array{0:int, 1:float}>> $events nhóm => tồn cố định: [giờ, +/- lượng]
     * @param array<int, array<int, array{0:int, 1:int}>> $blocks roomId => [[từ, đến]] khối cố định
     * @param array<int, array{0:int, 1:int}> $offRanges không được bắt đầu trong các khoảng này
     * @param array<string, float> $max nhóm => Max
     * @return array<int, int> id dòng => giờ bắt đầu mới
     */
    public static function plan(array $lots, array $events, array $blocks, array $offRanges, array $max, int $from, int $until): array
    {
        $skipOff = function (int $t) use ($offRanges): int {
            do {
                $moved = false;
                foreach ($offRanges as [$a, $b]) {
                    if ($t >= $a && $t < $b) {
                        $t = $b;
                        $moved = true;
                    }
                }
            } while ($moved);
            return $t;
        };

        // Tồn cố định theo thời gian: gộp phần trước $from thành tồn đầu kỳ, phần sau sắp xếp để cộng dồn khi cần
        $base = [];
        foreach ($max as $g => $_) {
            $base[$g] = 0.0;
            $later = [];
            foreach ($events[$g] ?? [] as $e) {
                if ($e[0] <= $from) {
                    $base[$g] += $e[1];
                } else {
                    $later[] = $e;
                }
            }
            usort($later, fn($a, $b) => $a[0] <=> $b[0]);
            $events[$g] = $later;
        }

        $byRoom = [];
        foreach ($lots as $i => $l) {
            $byRoom[$l['room']][] = $i;
        }

        // Hạn bắt buộc tính ngược trong phòng: lô hạn muộn nhất xếp cuối, lô trước nó phải xong trước khi nó bắt đầu
        $backward = function (string $key) use ($byRoom, $lots): array {
            $out = [];
            foreach ($byRoom as $ids) {
                usort($ids, fn($a, $b) => ($lots[$b][$key] ?? PHP_INT_MAX) <=> ($lots[$a][$key] ?? PHP_INT_MAX));
                $next = PHP_INT_MAX;
                foreach ($ids as $i) {
                    $v = $lots[$i][$key] ?? PHP_INT_MAX;
                    $out[$i] = $next === PHP_INT_MAX ? $v : min($v, $next - $lots[$i]['occ']);
                    $next = $out[$i];
                }
            }
            return $out;
        };
        $must = $backward('due');
        $hardMust = $backward('hard');   // ngày NL/BB không được vi phạm

        $free = array_fill_keys(array_keys($byRoom), $from);
        $placed = [];   // i => start
        $added = [];    // nhóm => tồn của các lô có cổng vừa xếp: [giờ vào, giờ ra, lượng]
        $stockAt = function (string $g, int $t) use (&$events, &$added, $base): float {
            $s = $base[$g];
            foreach ($events[$g] as [$et, $d]) {
                if ($et > $t) {
                    break;
                }
                $s += $d;
            }
            foreach ($added[$g] ?? [] as [$in, $out, $q]) {
                if ($in <= $t && $t < $out) {
                    $s += $q;
                }
            }
            return $s;
        };

        for ($guard = 0; $guard < 500000; $guard++) {
            $room = null;
            foreach ($byRoom as $r => $q) {
                if ($q !== [] && ($room === null || $free[$r] < $free[$room])) {
                    $room = $r;
                }
            }
            if ($room === null) {
                break;
            }

            $t = $skipOff($free[$room]);
            foreach ($blocks[$room] ?? [] as [$a, $b]) {
                if ($t >= $a && $t < $b) {
                    $t = $b;
                }
            }
            if ($t !== $free[$room]) {
                $free[$room] = $t;
                continue;
            }

            $fits = function (int $i) use ($t, $room, $blocks, $lots): bool {
                foreach ($blocks[$room] ?? [] as [$a, $b]) {
                    if ($t < $b && $t + $lots[$i]['occ'] > $a) {
                        return false;
                    }
                }
                return true;
            };
            $late = $t > $until;   // quá cuối khung: xếp nốt, không chặn nữa
            $stock = [];

            $pick = null;
            $best = PHP_INT_MAX;
            foreach ($byRoom[$room] as $i) {
                $l = $lots[$i];
                if ($l['ready'] > $t || ! $fits($i)) {
                    continue;
                }
                $forced = $late || ($must[$i] <= $t + self::STEP && $l['gate'] === null) || $hardMust[$i] <= $t + self::STEP;
                if ($forced && ($pick === null || $must[$i] < $best)) {
                    $pick = $i;
                    $best = $must[$i];
                }
            }
            if ($pick === null) {
                foreach ($byRoom[$room] as $i) {
                    $l = $lots[$i];
                    if ($l['gate'] === null || $l['ready'] > $t || ! $fits($i)) {
                        continue;
                    }
                    $g = $l['gate'];
                    $stock[$g] ??= $stockAt($g, $t);
                    if ($stock[$g] + $l['qty'] <= $max[$g] && ($pick === null || $must[$i] < $best)) {
                        $pick = $i;
                        $best = $must[$i];
                    }
                }
            }
            if ($pick === null) {
                foreach ($byRoom[$room] as $i) {
                    $l = $lots[$i];
                    if ($l['gate'] !== null || $l['ready'] > $t || ! $fits($i)) {
                        continue;
                    }
                    if ($pick === null || $must[$i] < $best) {
                        $pick = $i;
                        $best = $must[$i];
                    }
                }
            }
            if ($pick === null) {
                $free[$room] = $t + self::STEP;
                continue;
            }

            $l = $lots[$pick];
            $placed[$pick] = $t;
            $byRoom[$room] = array_values(array_diff($byRoom[$room], [$pick]));
            $free[$room] = $t + $l['occ'];
            if ($l['gate'] !== null && $l['qty'] > 0) {
                // Ra kho lúc công đoạn tiêu thụ bắt đầu; lô chạy trễ thì công đoạn đó phải lùi theo
                $added[$l['gate']][] = [$t, $l['exit'] === null ? PHP_INT_MAX : max($l['exit'], $t + $l['lag']), $l['qty']];
            }
        }

        if (count($placed) !== count($lots)) {
            throw new \RuntimeException('Mô phỏng ngưng nguồn không xếp hết lô (' . count($placed) . '/' . count($lots) . ')');
        }
        $result = [];
        foreach ($placed as $i => $t) {
            $result[$lots[$i]['id']] = $t;
        }

        return $result;
    }
}
