<?php

namespace App\Services;

use App\Http\Controllers\Pages\Report\DailyReportController;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * File Excel của Báo cáo ngày, trình bày như 2 card trên trang:
 *  - sheet "Sản Lượng": công đoạn → phòng (lý thuyết, thực tế, % đáp ứng), giải trình / lý do, kế hoạch lý thuyết
 *    và cột Chi tiết (như _yield_card.blade.php / _yield_detail.blade.php);
 *  - sheet "Phân Bố Tồn Kho": tồn theo phòng của công đoạn tiếp theo, chia theo tổ (như dataTable.blade.php).
 * Nhận đúng mảng DailyReportController::reportData() mà trang dùng nên số liệu khớp màn hình.
 */
class DailyReportExcelService
{
    private const NUMBER = '#,##0.00';
    private const PERCENT = '#,##0.00%';
    // Ô thời gian lưu dạng số ngày (giây / 86400) nên vẫn cộng / lọc được trong Excel. Không chèn chữ giữa
    // [h] và mm ("[h] giờ mm phút"): khi đó mm bị đọc thành tháng thay vì phút.
    private const DURATION = '[h]:mm';

    // Màu như trên trang (_yield_styles.blade.php)
    private const NAVY = '003A4F';
    private const YELLOW = 'CDC717';
    private const YELLOW_LIGHT = 'E6E38B';
    private const GREEN = 'C9F9C3';
    private const BLUE = 'B3DAF9';
    private const CREAM = 'FFFAF0';
    private const DETAIL = 'D7EAFF';
    private const BORDER = 'CBD5E1';
    private const MUTED = '64748B';

    // Định hình: tách theo dạng bào chế như trên trang
    private const FORMS = ['tablet' => 'Tablet', 'coating' => 'Coating', 'capsule' => 'Capsule'];

    public function build(array $data, Carbon $reportedDate, string $productionCode): Spreadsheet
    {
        $shiftStart = $reportedDate->copy()->setTime(6, 0, 0);
        $shiftEnd = $shiftStart->copy()->addDay();

        $spreadsheet = new Spreadsheet();
        $spreadsheet->getProperties()
            ->setCreator(session('user')['fullName'] ?? 'PMS')
            ->setTitle('Báo cáo ngày ' . $shiftStart->format('d/m/Y'));

        $this->yieldSheet($spreadsheet->getActiveSheet(), $data, $shiftStart, $shiftEnd, $productionCode);
        $this->inventorySheet($spreadsheet->createSheet(), $data['sum_by_next_room'], $shiftStart, $productionCode);

        $spreadsheet->setActiveSheetIndex(0);

        return $spreadsheet;
    }

    private function yieldSheet(Worksheet $sheet, array $data, Carbon $shiftStart, Carbon $shiftEnd, string $productionCode): void
    {
        $sheet->setTitle('Sản Lượng');

        $widths = ['A' => 30, 'B' => 7, 'C' => 16, 'D' => 16, 'E' => 11, 'F' => 50, 'G' => 95, 'H' => 15, 'I' => 18];
        $viewedUntil = now()->gt($shiftStart) && now()->lt($shiftEnd) ? now() : null;

        $headerRow = $this->writeHeading(
            $sheet,
            'BÁO CÁO NGÀY ' . $shiftStart->format('d/m/Y'),
            [
                'Phân xưởng ' . $productionCode
                    . ' · Báo cáo được tính từ 06:00 ngày ' . $shiftStart->format('d/m/Y')
                    . ' đến 06:00 ngày ' . $shiftEnd->format('d/m/Y')
                    . ($viewedUntil ? ' · số liệu tính đến ' . $viewedUntil->format('H:i') : ''),
                $this->exportedLine(),
            ],
            [
                'Công Đoạn / Phòng SX',
                'ĐV',
                'Sản Lượng Lý Thuyết',
                'Sản Lượng Thực Tế',
                'Đáp Ứng',
                'Kế Hoạch (Lý Thuyết)',
                'Chi Tiết',
                'TG Xác Định (giờ:phút)',
                'TG Không Xác Định (giờ:phút)',
            ],
            $widths,
            'B'
        );

        $dayLT = collect($data['yield_theoryl_detial']['yield_day'] ?? []);
        $dayTT = collect($data['yield_actual_detial']['yield_day'] ?? []);
        $actualDetail = collect($data['yield_actual_detial']['actual_detail'] ?? []);
        $explanation = $data['explanation'];

        $row = $headerRow + 1;

        foreach ($data['theory']['yield_room']->groupBy('stage_code') as $stageCode => $rooms) {
            $unit = $stageCode <= 4 ? 'Kg' : 'ĐV';
            $stageLT = $dayLT->where('stage_code', $stageCode)->sum('total_qty');
            $stageTT = $dayTT->where('stage_code', $stageCode)->sum('total_qty');
            $explain = $explanation[$stageCode] ?? null;

            // Dòng tổng công đoạn: cột Kế hoạch / Chi tiết ghi giải trình và lý do
            $this->putRow($sheet, $row, [
                (DailyReportController::STAGE_NAME[$stageCode] ?? $stageCode) . "\n" . $rooms->count() . ' phòng',
                $unit,
                $stageLT,
                $stageTT,
                $this->percent($stageLT, $stageTT),
                'Giải trình: ' . ($explain && !empty($explain->content) ? trim($explain->content) : '—'),
                'Lý do: ' . ($explain && !empty($explain->reason) ? trim($explain->reason) : '—'),
            ]);
            $this->styleStageRow($sheet, $row, true);
            $row++;

            // Dòng phụ như trên trang: THT có thêm quy đổi ĐV, Định hình tách theo dạng bào chế
            $subRows = [];
            if ($stageCode == 4) {
                $subRows['Quy đổi'] = ['ĐV', $dayLT->where('stage_code', 4)->sum('total_qty_unit'), $dayTT->where('stage_code', 4)->sum('total_qty_unit')];
            }
            if ($stageCode == 5) {
                foreach (self::FORMS as $type => $label) {
                    $subRows[$label] = [$unit, $this->sumForm($dayLT, $type), $this->sumForm($dayTT, $type)];
                }
            }
            foreach ($subRows as $label => [$subUnit, $lt, $tt]) {
                $this->putRow($sheet, $row, ['    ' . $label, $subUnit, $lt, $tt]);
                $this->styleStageRow($sheet, $row, false);
                $row++;
            }

            foreach ($rooms as $roomLT) {
                $itemLT = $dayLT->firstWhere('resourceId', $roomLT->resourceId);
                $itemTT = $dayTT->firstWhere('resourceId', $roomLT->resourceId);
                $qtyLT = $itemLT['total_qty'] ?? 0;
                $qtyTT = $itemTT['total_qty'] ?? 0;

                $plans = collect($itemLT['theory_items'] ?? [])
                    ->map(fn($p) => "{$p->start} – {$p->end}  {$p->title}: " . number_format($p->yields, $stageCode <= 4 ? 2 : 0))
                    ->implode("\n");
                $detail = $this->roomDetail($actualDetail->where('resourceId', $roomLT->resourceId), $shiftStart, $shiftEnd);

                $this->putRow($sheet, $row, [
                    trim($roomLT->room_code) . ' - ' . trim($roomLT->room_name),
                    $unit,
                    $qtyLT,
                    $qtyTT,
                    $this->percent($qtyLT, $qtyTT),
                    $plans,
                    $detail['text'],
                    $detail['active'],
                    $detail['dead'],
                ]);
                $this->styleRoomRow($sheet, $row, false);
                $row++;

                if ($stageCode == 4) {
                    $this->putRow($sheet, $row, ['    Quy đổi', 'ĐV', $itemLT['total_qty_unit'] ?? 0, $itemTT['total_qty_unit'] ?? 0]);
                    $this->styleRoomRow($sheet, $row, true);
                    $row++;
                }
            }
        }

        $firstRow = $headerRow + 1;
        $lastRow = max($row - 1, $headerRow);

        $this->styleTable($sheet, $headerRow, $lastRow, 'I');
        $sheet->getStyle("B{$firstRow}:B{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("C{$firstRow}:D{$lastRow}")->getNumberFormat()->setFormatCode(self::NUMBER);
        $sheet->getStyle("E{$firstRow}:E{$lastRow}")->getNumberFormat()->setFormatCode(self::PERCENT);
        $sheet->getStyle("E{$firstRow}:E{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("H{$firstRow}:I{$lastRow}")->getNumberFormat()->setFormatCode(self::DURATION);

        $this->fitRowHeights($sheet, $firstRow, $lastRow, ['A' => $widths['A'], 'F' => $widths['F'], 'G' => $widths['G']]);
    }

    private function inventorySheet(Worksheet $sheet, Collection $items, Carbon $shiftStart, string $productionCode): void
    {
        $sheet->setTitle('Phân Bố Tồn Kho');

        $widths = ['A' => 6, 'B' => 60, 'C' => 22, 'D' => 20, 'E' => 7, 'F' => 16];

        $headerRow = $this->writeHeading(
            $sheet,
            'PHÂN BỐ TỒN KHO NGÀY ' . $shiftStart->format('d/m/Y'),
            ['Phân xưởng ' . $productionCode, $this->exportedLine()],
            ['STT', 'Tên Phòng - Thiết Bị Chính', 'Công Đoạn Tiếp Theo', 'Tồn Thực Tế Công Đoạn Trước', 'ĐV', 'Quy Đổi (ĐV)'],
            $widths,
            'A'
        );

        $row = $headerRow + 1;

        foreach ($items->groupBy('group_code') as $groupCode => $groupItems) {
            $sheet->setCellValueExplicit("A{$row}", 'Tổ ' . (DailyReportController::GROUP_NAME[$groupCode] ?? $groupCode), DataType::TYPE_STRING);
            $sheet->mergeCells("A{$row}:F{$row}");
            $sheet->getStyle("A{$row}:F{$row}")->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => self::NAVY]],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::YELLOW]],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ]);
            $row++;

            foreach ($groupItems->values() as $index => $item) {
                // ĐV theo công đoạn của phòng tiếp theo, như trên trang; vào Định hình thì có thêm quy đổi ĐV
                $this->putRow($sheet, $row, [
                    $index + 1,
                    $item->next_room,
                    $item->stage,
                    (float) $item->sum_yields,
                    $item->stage_code <= 5 ? 'Kg' : 'ĐV',
                    $item->stage_code == 5 ? (float) ($item->sum_yields_unit ?? 0) : null,
                ]);
                $row++;
            }
        }

        if ($row == $headerRow + 1) {
            $sheet->setCellValue("A{$row}", 'Không có dữ liệu');
            $sheet->mergeCells("A{$row}:F{$row}");
            $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $row++;
        }

        $firstRow = $headerRow + 1;
        $lastRow = $row - 1;

        $this->styleTable($sheet, $headerRow, $lastRow, 'F');
        $sheet->getStyle("A{$firstRow}:A{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("E{$firstRow}:E{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("D{$firstRow}:D{$lastRow}")->getNumberFormat()->setFormatCode(self::NUMBER);
        $sheet->getStyle("F{$firstRow}:F{$lastRow}")->getNumberFormat()->setFormatCode(self::NUMBER);
        $sheet->getStyle("D{$firstRow}:D{$lastRow}")->getFont()->setBold(true);

        $this->fitRowHeights($sheet, $firstRow, $lastRow, ['B' => $widths['B'], 'C' => $widths['C']]);
    }

    /**
     * Nội dung ô Chi tiết của 1 phòng, giống _yield_detail.blade.php: các dòng
     * "N. Tên (HH:MM - HH:MM = XhYp) || Sản Lượng: … || Ghi Chú: …" và tổng thời gian xác định (hợp các khoảng,
     * giới hạn trong ca) / không xác định (phần còn lại của ca), làm tròn xuống phút như trên trang.
     *
     * @return array{text: string, active: ?float, dead: ?float} active / dead tính bằng ngày cho định dạng thời gian của Excel
     */
    private function roomDetail(Collection $detail, Carbon $shiftStart, Carbon $shiftEnd): array
    {
        $rows = $detail->map(function ($d) {
            $start = Carbon::parse($d->start);
            $end = Carbon::parse($d->end);
            if ($end->lessThan($start)) {
                $end->addDay(); // qua ngày hôm sau
            }
            return (object) ['d' => $d, 'start' => $start, 'end' => $end];
        })->values();

        if ($rows->isEmpty()) {
            return ['text' => '—', 'active' => null, 'dead' => null];
        }

        // Ghi chú / tên hoạt động nhập tay có thể xuống dòng: trên trang HTML hiện liền 1 dòng, giữ như vậy để
        // không lẫn với các mục đánh số trong ô
        $oneLine = fn($text) => trim(preg_replace('/\s+/u', ' ', (string) $text));

        $lines = $rows->map(function ($r, $i) use ($oneLine) {
            $d = $r->d;
            $minutes = (int) $r->start->diffInMinutes($r->end);

            $line = ($i + 1) . '. ' . ($d->title == null && $d->yields == null ? 'VS' : $oneLine($d->title))
                . ' (' . $r->start->format('H:i') . ' - ' . $r->end->format('H:i')
                . ' = ' . intdiv($minutes, 60) . 'h' . ($minutes % 60) . 'p)';
            if ($d->yields) {
                $line .= ' || Sản Lượng: ' . number_format($d->yields, 2) . ' ' . $d->unit;
                if ((float) $d->yields_batch_qty) {
                    $line .= ' # ' . $d->yields_batch_qty . ' ĐV';
                }
            }
            if ($d->note && $d->note != 'NA') {
                $line .= ' || Ghi Chú: ' . $oneLine($d->note);
            }

            return $line;
        });

        $intervals = $rows->map(fn($r) => [$r->start->max($shiftStart), $r->end->min($shiftEnd)])
            ->filter(fn($i) => $i[1] > $i[0])
            ->sortBy(fn($i) => $i[0]->getTimestamp())
            ->values();
        $activeSeconds = 0;
        $current = null;
        foreach ($intervals as $i) {
            if ($current && $i[0] <= $current[1]) {
                $current[1] = $i[1]->max($current[1]);
                continue;
            }
            if ($current) {
                $activeSeconds += $current[0]->diffInSeconds($current[1]);
            }
            $current = $i;
        }
        if ($current) {
            $activeSeconds += $current[0]->diffInSeconds($current[1]);
        }
        $deadSeconds = max(0, $shiftStart->diffInSeconds($shiftEnd) - $activeSeconds);

        $toDays = fn($seconds) => intdiv((int) $seconds, 60) / 1440;

        return ['text' => $lines->implode("\n"), 'active' => $toDays($activeSeconds), 'dead' => $toDays($deadSeconds)];
    }

    /**
     * % đáp ứng như trang: không có lý thuyết lẫn thực tế thì không đánh giá ("—"),
     * có thực tế mà không có lý thuyết thì tính 100%.
     */
    private function percent(float $lt, float $tt): float|string
    {
        if ($lt == 0 && $tt == 0) {
            return '—';
        }
        if ($lt == 0) {
            return $tt > 0 ? 1 : 0;
        }

        return $tt / $lt;
    }

    private function sumForm(Collection $day, string $type): float
    {
        return $day->where('stage_code', 5)->where('table_type', $type)->sum('total_qty');
    }

    private function exportedLine(): string
    {
        $user = session('user')['fullName'] ?? null;

        return 'Xuất lúc ' . now()->format('H:i d/m/Y') . ($user ? ' bởi ' . $user : '');
    }

    /**
     * Tiêu đề, dòng mô tả, dòng tên cột, độ rộng cột, thiết lập in và vùng ghim. Trả về số dòng của dòng tên cột.
     */
    private function writeHeading(Worksheet $sheet, string $title, array $subtitles, array $headers, array $widths, string $freezeColumn): int
    {
        $lastColumn = array_key_last($widths);

        $sheet->setCellValue('A1', $title);
        $sheet->mergeCells("A1:{$lastColumn}1");
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16)->getColor()->setRGB(self::NAVY);
        $sheet->getRowDimension(1)->setRowHeight(26);

        $row = 2;
        foreach ($subtitles as $text) {
            $sheet->setCellValueExplicit("A{$row}", $text, DataType::TYPE_STRING);
            $sheet->mergeCells("A{$row}:{$lastColumn}{$row}");
            $sheet->getStyle("A{$row}")->getFont()->setItalic(true)->getColor()->setRGB(self::MUTED);
            $row++;
        }
        $sheet->getStyle("A1:A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $headerRow = $row + 1;
        $sheet->fromArray($headers, null, "A{$headerRow}");
        $sheet->getStyle("A{$headerRow}:{$lastColumn}{$headerRow}")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::NAVY]],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
        ]);
        $sheet->getRowDimension($headerRow)->setRowHeight(32);

        foreach ($widths as $column => $width) {
            $sheet->getColumnDimension($column)->setWidth($width);
        }

        // In ngang khổ A4, vừa 1 trang theo chiều rộng, lặp dòng tên cột ở mỗi trang
        $sheet->getPageSetup()
            ->setOrientation(PageSetup::ORIENTATION_LANDSCAPE)
            ->setPaperSize(PageSetup::PAPERSIZE_A4)
            ->setFitToWidth(1)
            ->setFitToHeight(0)
            ->setRowsToRepeatAtTopByStartAndEnd($headerRow, $headerRow);

        $sheet->freezePane($freezeColumn . ($headerRow + 1));

        return $headerRow;
    }

    /**
     * Ghi 1 dòng từ cột A: chuỗi ghi dạng chữ (không để Excel hiểu nhầm thành số / công thức), số giữ nguyên, null bỏ trống.
     */
    private function putRow(Worksheet $sheet, int $row, array $values): void
    {
        foreach (array_values($values) as $index => $value) {
            if ($value === null || $value === '') {
                continue;
            }

            $cell = $sheet->getCell(Coordinate::stringFromColumnIndex($index + 1) . $row);
            is_string($value) ? $cell->setValueExplicit($value, DataType::TYPE_STRING) : $cell->setValue($value);
        }
    }

    private function styleStageRow(Worksheet $sheet, int $row, bool $main): void
    {
        $sheet->getStyle("A{$row}:I{$row}")->applyFromArray([
            'font' => ['bold' => $main, 'italic' => !$main, 'color' => ['rgb' => self::NAVY]],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $main ? self::YELLOW : self::YELLOW_LIGHT]],
        ]);

        if ($main) {
            $sheet->getStyle("A{$row}")->getFont()->setSize(12);
            $sheet->getStyle("D{$row}")->getFont()->getColor()->setRGB('0B3D91');
        }
    }

    private function styleRoomRow(Worksheet $sheet, int $row, bool $sub): void
    {
        foreach (['C' => self::GREEN, 'D' => self::BLUE, 'E' => self::CREAM, 'G' => self::DETAIL] as $column => $color) {
            $sheet->getStyle("{$column}{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($color);
        }

        $sheet->getStyle("A{$row}:I{$row}")->getFont()->setItalic($sub);
        $sheet->getStyle("C{$row}:D{$row}")->getFont()->setBold(!$sub);
        $sheet->getStyle("D{$row}")->getFont()->getColor()->setRGB('1D4ED8');

        if ($sub) {
            $sheet->getStyle("A{$row}:B{$row}")->getFont()->getColor()->setRGB(self::MUTED);
        }
    }

    private function styleTable(Worksheet $sheet, int $headerRow, int $lastRow, string $lastColumn): void
    {
        $sheet->getStyle("A{$headerRow}:{$lastColumn}{$lastRow}")->applyFromArray([
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => self::BORDER]],
            ],
        ]);

        if ($lastRow > $headerRow) {
            $sheet->getStyle('A' . ($headerRow + 1) . ":{$lastColumn}{$lastRow}")
                ->getAlignment()
                ->setVertical(Alignment::VERTICAL_TOP)
                ->setWrapText(true);
        }
    }

    /**
     * Excel không tự giãn chiều cao dòng của file tạo bằng PhpSpreadsheet, nên ước lượng theo số dòng chữ
     * ở các cột có xuống dòng (độ rộng cột ≈ số ký tự mỗi dòng).
     */
    private function fitRowHeights(Worksheet $sheet, int $fromRow, int $toRow, array $wrapWidths): void
    {
        for ($row = $fromRow; $row <= $toRow; $row++) {
            $lines = 1;

            foreach ($wrapWidths as $column => $width) {
                if (!$sheet->cellExists("{$column}{$row}")) {
                    continue;
                }

                $value = $sheet->getCell("{$column}{$row}")->getValue();
                if (!is_string($value) || $value === '') {
                    continue;
                }

                $count = 0;
                foreach (explode("\n", $value) as $segment) {
                    $count += max(1, (int) ceil(mb_strlen($segment) / max(1, $width - 2)));
                }
                $lines = max($lines, $count);
            }

            $sheet->getRowDimension($row)->setRowHeight(max(20, $lines * 15 + 5));
        }
    }
}
