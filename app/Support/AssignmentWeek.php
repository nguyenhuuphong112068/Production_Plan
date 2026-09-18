<?php

namespace App\Support;

use Carbon\Carbon;

/**
 * Tiện ích dùng chung cho các trang Lịch công tác theo tuần
 * (sản xuất và bảo trì/hiệu chuẩn).
 */
class AssignmentWeek
{
    /**
     * Job_description được nhập bằng contenteditable nên nội dung là HTML
     * (<div>, <br>, &nbsp;, thậm chí cả bảng dán từ Excel). Tách thành từng
     * dòng công việc dạng text thuần để hiển thị trên bảng tuần.
     */
    public static function jobLines($html): array
    {
        $text = (string) $html;

        // Ô trong bảng dán từ Excel: ngăn cách bằng khoảng trắng, không xuống dòng
        $text = preg_replace('#<\s*/\s*(td|th)\s*>#i', ' ', $text);

        // Mở hoặc đóng một block đều là xuống dòng (kể cả <div style="...">)
        $text = preg_replace('#<\s*/?\s*(div|p|li|ul|ol|table|tbody|thead|tr|br)\b[^>]*>#i', "\n", $text);

        $text = strip_tags($text);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = str_replace("\xC2\xA0", ' ', $text);

        $lines = preg_split('/\r\n|\r|\n/', $text);

        return array_values(array_filter(array_map(function ($line) {
            return trim(preg_replace('/[ \t]+/', ' ', $line));
        }, $lines), function ($line) {
            return $line !== '';
        }));
    }

    /**
     * Số giờ công giữa hai mốc thời gian.
     * Ca 1, 2, 3, 6 chạy liên tục; các ca còn lại (HC) bị trừ 45 phút nghỉ trưa.
     */
    public static function hours($start, $end, $shift = null): ?float
    {
        if (!$start || !$end) return null;

        $s = Carbon::parse($start);
        $e = Carbon::parse($end);
        if ($e < $s) $e = $e->copy()->addDay();

        $minutes = $s->diffInMinutes($e);

        if (!in_array((string) $shift, ['1', '2', '3', '6'], true)) {
            $lunchStart = $s->copy()->startOfDay()->addMinutes(11 * 60 + 30);
            $lunchEnd = $s->copy()->startOfDay()->addMinutes(12 * 60 + 15);

            $overlapStart = $s->greaterThan($lunchStart) ? $s : $lunchStart;
            $overlapEnd = $e->lessThan($lunchEnd) ? $e : $lunchEnd;
            if ($overlapStart < $overlapEnd) {
                $minutes -= $overlapStart->diffInMinutes($overlapEnd);
            }
        }

        return round(max(0, $minutes) / 60, 2);
    }

    /**
     * Lật bảng tuần từ trục "phòng" sang trục "nhân sự".
     *
     * Dùng lại đúng dữ liệu đã dựng cho bảng theo phòng (không truy vấn thêm):
     * mỗi ca trong ô [phòng][ngày] được tách ra thành từng dòng của người làm ca đó,
     * nên tổng giờ hai chế độ luôn khớp nhau.
     *
     * @param array      $cells   [rowKey][Y-m-d] => danh sách ca (mỗi ca có ->people)
     * @param iterable   $rows    danh sách dòng phòng, cần row_key / code / name / group_code
     * @return array{0: array, 1: array, 2: array} [personRows, personCells, personTotals]
     */
    public static function pivotByPersonnel(array $cells, $rows): array
    {
        // Tra cứu nhãn phòng theo row_key để gắn vào từng ca của nhân sự
        $roomLabels = [];
        $roomGroups = [];
        foreach ($rows as $row) {
            $roomLabels[$row->row_key] = trim(implode(' - ', array_filter([$row->code ?? null, $row->name ?? null])));
            $roomGroups[$row->row_key] = $row->group_code ?? 'OTHER';
        }

        $personRows = [];
        $personCells = [];
        $personTotals = [];

        foreach ($cells as $rowKey => $daysOfRow) {
            foreach ($daysOfRow as $date => $shifts) {
                foreach ($shifts as $shift) {
                    foreach (($shift->people ?? []) as $person) {
                        if (empty($person->id)) continue;

                        $personKey = 'p' . $person->id;
                        if (!isset($personRows[$personKey])) {
                            $personRows[$personKey] = (object) [
                                'row_key' => $personKey,
                                'code' => $person->code ?: '',
                                'name' => $person->name,
                                'meta' => null,
                                'group_code' => $roomGroups[$rowKey] ?? 'OTHER',
                            ];
                        }

                        $personCells[$personKey][$date][] = (object) [
                            'id' => $shift->id ?? null,
                            'shift' => $shift->shift,
                            'shift_name' => $shift->shift_name,
                            'time' => $person->time,
                            'hours' => $person->hours,
                            'room_label' => $roomLabels[$rowKey] ?? '',
                            'jobs' => $shift->jobs ?? [],
                            'note' => $person->note ?? null,
                            'operation_type' => $person->operation_type ?? null,
                            'adjusted' => $person->adjusted ?? false,
                        ];

                        $personTotals[$personKey]['hours'] = ($personTotals[$personKey]['hours'] ?? 0) + $person->hours;
                        $personTotals[$personKey]['shifts'] = ($personTotals[$personKey]['shifts'] ?? 0) + 1;
                    }
                }
            }
        }

        // Sắp các ca trong cùng một ngày theo giờ bắt đầu, và xếp dòng theo tên
        foreach ($personCells as $personKey => $daysOfPerson) {
            foreach ($daysOfPerson as $date => $shifts) {
                usort($shifts, fn($a, $b) => strcmp($a->time, $b->time));
                $personCells[$personKey][$date] = $shifts;
            }
        }

        uasort($personRows, function ($a, $b) {
            return [$a->group_code, $a->name] <=> [$b->group_code, $b->name];
        });

        return [array_values($personRows), $personCells, $personTotals];
    }
}
