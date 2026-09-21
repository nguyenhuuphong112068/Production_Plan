<?php

namespace App\Support;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

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

    /**
     * Giờ công theo [personKey][ngày công tác] của MỌI tổ trong bộ phận.
     *
     * Khi bảng tuần đang lọc theo một tổ, $personCells chỉ chứa phân công của tổ đó.
     * Muốn biết một người đã đủ 8h hay chưa được phân công thì phải tính cả phần
     * họ làm ở tổ khác — hàm này gom từ các dòng phân công không lọc tổ.
     *
     * @param iterable $rows mỗi dòng cần: personnel_id, start, end, p_start, p_end, Sheet
     */
    public static function personDayHours(iterable $rows, string $weekStart, string $weekEnd): array
    {
        $result = [];
        foreach ($rows as $r) {
            $workDay = WorkingDay::of($r->start);
            if ($workDay < $weekStart || $workDay > $weekEnd) continue;

            $hours = self::hours($r->p_start ?: $r->start, $r->p_end ?: $r->end, $r->Sheet) ?? 0;
            $key = 'p' . $r->personnel_id;
            $result[$key][$workDay] = ($result[$key][$workDay] ?? 0) + $hours;
        }

        return $result;
    }

    /**
     * Ngưỡng phân loại giờ công trong ngày. Giữ đúng ngưỡng của Dashboard tình hình
     * nhân sự (DashBoardController::getData): dưới 7.9h là thiếu, 7.9-8.1h là đủ 8h,
     * trên 8.1h là quá 8h.
     */
    public const UNDER_HOURS = 7.9;
    public const OVER_HOURS = 8.1;

    /** @return string 'under' | 'full' | 'over' */
    public static function hoursStatus(float $hours): string
    {
        if ($hours < self::UNDER_HOURS) return 'under';
        if ($hours <= self::OVER_HOURS) return 'full';
        return 'over';
    }

    /**
     * Nhân sự thuộc bộ phận (và tổ đang chọn) trong tuần đang xem — cùng tiêu chí với
     * Dashboard tình hình nhân sự: đang hoạt động, chưa nghỉ việc, đã vào làm tính tới
     * cuối tuần, và có phân công active ở bộ phận đó.
     *
     * @param string     $productionCode PXV1... hoặc EN / QA
     * @param array|null $groupIds       giới hạn theo ea.group_id, null = cả bộ phận
     * @return array<string, object> ['p'.id => {id, code, name, on_maternity_leave, on_long_leave, joined_on}]
     */
    public static function population(string $productionCode, ?array $groupIds, string $weekEnd): array
    {
        $query = DB::table('employees as e')
            ->join('employee_assignments as ea', 'e.id', '=', 'ea.employees_id')
            ->where('e.active', 1)
            ->where(function ($q) {
                $q->whereNull('e.resign')->orWhere('e.resign', 0);
            })
            ->whereRaw('DATE(e.created_at) <= ?', [$weekEnd])
            ->where('ea.production_code', $productionCode)
            ->where('ea.active', 1);

        if ($groupIds !== null) {
            $query->whereIn('ea.group_id', $groupIds);
        }

        $result = [];
        $rows = $query
            ->select('e.id', 'e.code', 'e.name', 'e.on_maternity_leave', 'e.on_long_leave', DB::raw('DATE(e.created_at) as joined_on'))
            ->groupBy('e.id', 'e.code', 'e.name', 'e.on_maternity_leave', 'e.on_long_leave', 'joined_on')
            ->get();
        foreach ($rows as $emp) {
            $result['p' . $emp->id] = $emp;
        }

        return $result;
    }

    /**
     * Tính trạng thái từng ngày của từng người ở trục nhân sự, theo đúng quy tắc của
     * Dashboard tình hình nhân sự, và thêm dòng cho người chưa được phân công ở bất kỳ
     * tổ nào trong tuần.
     *
     * Mỗi (người, ngày) ra đúng một trạng thái:
     *   - có giờ công (tính MỌI tổ): 'under' | 'full' | 'over' theo hoursStatus()
     *   - không có giờ công: 'maternity' | 'long_leave' (cờ trong bảng employees), rồi
     *     'leave' (eO2 ghi mã ca P), còn lại là 'unassigned'
     *   - ngày trước ngày vào làm, và ngày nghỉ công ty (off_days): không có trạng thái
     *
     * Chỉ dùng để hiển thị badge — không đụng tới $personCells/$personTotals.
     *
     * @param array      $personRows  kết quả pivotByPersonnel()
     * @param array      $personCells kết quả pivotByPersonnel()
     * @param iterable   $days        danh sách ngày trong tuần, cần ->date
     * @param array|null $rosterIndex kết quả ShiftApiService::shiftIndex(); null nếu eO2 lỗi thì
     *                                không kết luận được "nghỉ phép"/"chưa phân công" nên bỏ qua
     * @param array|null $allDayHours personDayHours() của MỌI tổ; null = bảng không lọc tổ,
     *                                lấy luôn từ $personCells
     * @param array      $population  kết quả population()
     * @return array{0: array, 1: array} [personRows, personDayStatus]
     */
    public static function attachDayStatus(
        array $personRows,
        array $personCells,
        iterable $days,
        ?array $rosterIndex,
        ?array $allDayHours,
        array $population
    ): array {
        if ($allDayHours === null) {
            $allDayHours = [];
            foreach ($personCells as $personKey => $daysOfPerson) {
                foreach ($daysOfPerson as $date => $shifts) {
                    $allDayHours[$personKey][$date] = array_sum(array_column($shifts, 'hours'));
                }
            }
        }

        // Ngày nghỉ của công ty (off_days) không cần badge trạng thái
        $offDates = OffDays::all();
        $dateKeys = [];
        foreach ($days as $day) {
            if (!isset($offDates[$day->date])) {
                $dateKeys[] = $day->date;
            }
        }

        // Thông tin nhân sự (cờ nghỉ dài hạn, ngày vào làm) của các dòng đã có
        $known = $population;
        $unknownIds = [];
        foreach ($personRows as $row) {
            if (!isset($known[$row->row_key])) {
                $unknownIds[] = (int) substr($row->row_key, 1);
            }
        }
        if (!empty($unknownIds)) {
            $rows = DB::table('employees')
                ->whereIn('id', $unknownIds)
                ->select('id', 'code', 'name', 'on_maternity_leave', 'on_long_leave', DB::raw('DATE(created_at) as joined_on'))
                ->get();
            foreach ($rows as $emp) {
                $known['p' . $emp->id] = $emp;
            }
        }

        // Người thuộc bộ phận nhưng chưa có ca nào trong tuần ở bất kỳ tổ nào
        $rowKeys = [];
        foreach ($personRows as $row) {
            $rowKeys[$row->row_key] = true;
        }
        foreach ($population as $personKey => $emp) {
            if (isset($rowKeys[$personKey])) continue;
            if (!empty($emp->on_maternity_leave) || !empty($emp->on_long_leave)) continue;
            if (!empty($allDayHours[$personKey])) continue; // đang làm ở tổ khác

            $personRows[] = (object) [
                'row_key' => $personKey,
                'code' => $emp->code,
                'name' => $emp->name,
                'meta' => null,
                'group_code' => 'UNSCHEDULED',
            ];
        }

        // Nhóm "chưa có lịch" luôn nằm cuối bảng, các nhóm khác giữ thứ tự cũ
        usort($personRows, function ($a, $b) {
            $aLast = $a->group_code === 'UNSCHEDULED' ? 1 : 0;
            $bLast = $b->group_code === 'UNSCHEDULED' ? 1 : 0;
            return [$aLast, $a->group_code, $a->name] <=> [$bLast, $b->group_code, $b->name];
        });

        $personDayStatus = [];
        foreach ($personRows as $row) {
            $personKey = $row->row_key;
            $emp = $known[$personKey] ?? null;
            $rosterDays = $rosterIndex[$row->code]['days'] ?? [];

            foreach ($dateKeys as $date) {
                if ($emp && !empty($emp->joined_on) && $emp->joined_on > $date) continue;

                $hours = $allDayHours[$personKey][$date] ?? 0;
                if ($hours > 0) {
                    $personDayStatus[$personKey][$date] = self::hoursStatus((float) $hours);
                } elseif ($emp && !empty($emp->on_maternity_leave)) {
                    $personDayStatus[$personKey][$date] = 'maternity';
                } elseif ($emp && !empty($emp->on_long_leave)) {
                    $personDayStatus[$personKey][$date] = 'long_leave';
                } elseif ($rosterIndex !== null) {
                    $shift = strtoupper(trim((string) ($rosterDays[$date]['shift'] ?? '')));
                    $personDayStatus[$personKey][$date] = $shift === 'P' ? 'leave' : 'unassigned';
                }
            }
        }

        return [$personRows, $personDayStatus];
    }
}
