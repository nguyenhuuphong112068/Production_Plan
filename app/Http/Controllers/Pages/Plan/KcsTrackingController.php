<?php

namespace App\Http\Controllers\Pages\Plan;

use App\Http\Controllers\Controller;
use App\Models\PlanMasterKcs;
use App\Models\PlanMasterKcsHistory;
use App\Support\MmsBom;
use App\Support\MmsFgQc;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Theo dõi hồ sơ KCS: mỗi lô sản xuất là một dòng, người dùng nhập các mốc hồ sơ
 * (ngày nhận hồ sơ, người đọc, ngày KCS, COATP, các ngày approval...) và hệ thống
 * chấm "Đáp Ứng / Không Đáp Ứng" theo số ngày hoàn thành, kèm bảng tổng kết tỉ lệ.
 */
class KcsTrackingController extends Controller
{
    /** Các phân xưởng có theo dõi hồ sơ KCS, theo thứ tự hiển thị */
    private const DEPARTMENTS = [
        'PXV1' => 'PX Viên 1',
        'PXV2' => 'PX Viên 2',
        'PXTN' => 'PX Thuốc Nước',
        'PXDN' => 'PX Dùng Ngoài',
        'PXVH' => 'PX Viên H',
    ];

    /**
     * Các mốc đã có sẵn trên plan_master được điền trước vào ô nhập để đỡ gõ lại.
     *
     * Chỉ là gợi ý trên giao diện: dòng theo dõi chỉ được tạo khi người dùng thật sự
     * bấm lưu. Cố ý không tự sinh dòng và tự chấm kết quả từ dữ liệu này, vì
     * plan_master không có "ngày đọc xong hồ sơ" - mốc thường quyết định ngày đủ
     * điều kiện - nên tự chấm sẽ cho tỉ lệ đúng hạn thấp hơn thực tế.
     *
     * Cố ý KHÔNG điền sẵn "Ngày nhận COATP" từ plan_master.actual_CoA_date: cột đó là
     * "Ngày ra phiếu TP" do QC nhập ở trang Phản Hồi Kế Hoạch Tháng, khác với ngày KCS
     * nhận được COATP. "Ngày nhận COATP" có cột dữ liệu riêng
     * (plan_master_KCS.coatp_received_date) và chỉ nhận giá trị người dùng tự nhập ở đây.
     *
     * Cũng không còn điền sẵn "Ngày KCS" từ plan_master.actual_KCS: cột đó nay do MMS
     * quyết định (xem PlanMasterKcs::MMS_LABELS và syncFromMms()), không nhập tay nữa nên
     * không cần gợi ý gõ.
     */
    public const PREFILL_FROM_PLAN_MASTER = [
        'record_received_date' => 'actual_record_date',
    ];

    public function index(Request $request)
    {
        $filters = $this->queryFilters($request);
        $filtered = $this->filteredData($filters);

        session()->put(['title' => 'THEO DÕI HỒ SƠ KCS']);

        return view('pages.plan.kcs_tracking.list', array_merge($filtered, [
            'summary' => $this->buildSummary($filters['department'], $filters['summaryYear']),
            'summaryYear' => $filters['summaryYear'],
            'summaryYears' => $this->summaryYears(),
            'departments' => self::DEPARTMENTS,
            'department' => $filters['department'],
            'fromMonth' => $filters['fromMonth'],
            'toMonth' => $filters['toMonth'],
            'keyword' => $filters['keyword'],
            'kcsMonth' => $filters['kcsMonth'],
            'result' => $filters['result'],
            'canUpdate' => user_has_permission(session('user')['userId'], 'kcs_tracking_update', 'boolean'),
        ]));
    }

    /**
     * Xuất Excel danh sách lô đang được lọc trên tab Theo Dõi Hồ Sơ. Dùng đúng
     * queryFilters()/filteredData() của index() nên file luôn khớp với lưới đang xem,
     * kể cả các cột dẫn xuất (Ngày Đủ Điều Kiện, Số Ngày HT, Kết Quả...).
     */
    public function export(Request $request)
    {
        $filters = $this->queryFilters($request);
        $filtered = $this->filteredData($filters);

        $spreadsheet = $this->buildExportSpreadsheet($filters, $filtered);

        $fileName = 'Theo_Doi_Ho_So_KCS_'
            . ($filters['department'] ?: 'TatCa') . '_'
            . now()->format('d-m-Y_His') . '.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            // Dọn mọi output buffer trước khi ghi: chỉ một ký tự thừa lọt vào
            // đầu luồng là Excel báo file hỏng
            while (ob_get_level()) {
                ob_end_clean();
            }

            (new Xlsx($spreadsheet))->save('php://output');
        }, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * Đọc và chuẩn hoá các tham số lọc trên thanh lọc của trang.
     *
     * Tách riêng khỏi filteredData() vì cả view (hiện lại lựa chọn đang chọn) lẫn tên
     * file Excel đều cần các giá trị "thô" (fromMonth/toMonth gốc, chưa nới theo kcsMonth).
     *
     * @return array{department: string, fromMonth: string, toMonth: string, keyword: string,
     *               kcsMonth: string, result: string, summaryYear: int, planFrom: string, planTo: string}
     */
    private function queryFilters(Request $request): array
    {
        // Mặc định chỉ lấy tháng kế hoạch hiện tại: lưới có tới 15 ô nhập mỗi lô nên
        // mở rộng cả năm sẽ nặng trang. Người dùng tự nới khoảng khi cần xem lại.
        $department = $request->query('department', session('user')['production_code'] ?? '');
        $fromMonth = $request->query('from_month') ?: Carbon::now()->format('Y-m');
        $toMonth = $request->query('to_month') ?: Carbon::now()->format('Y-m');
        $keyword = trim((string) $request->query('keyword', ''));
        // Tháng KCS dạng 'YYYY-MM', lọc theo ngày KCS thật của lô chứ không theo tháng kế hoạch
        $kcsMonth = trim((string) $request->query('kcs_month', ''));
        // Kết quả chấm: chỉ nhận đúng hai giá trị hợp lệ để không lọc nhầm bằng chuỗi tự chế
        $result = $request->query('result', '');
        $result = in_array($result, [PlanMasterKcs::RESULT_MET, PlanMasterKcs::RESULT_NOT_MET], true)
            ? $result
            : '';
        $summaryYear = (int) ($request->query('summary_year') ?: Carbon::now()->year);

        // Tháng KCS và tháng kế hoạch là hai trục thời gian khác nhau: lô kế hoạch tháng 6
        // vẫn có thể được KCS trong tháng 8. Giữ cả hai bộ lọc cùng lúc sẽ cắt mất phần lớn
        // lô của tháng KCS đang xem (VD PXV1 tháng 8/2026: 199 lô còn 33), khiến lưới không
        // bao giờ khớp với tab Tổng Kết Tỉ Lệ. Nên khi đã chọn Tháng KCS thì bỏ qua khoảng
        // tháng kế hoạch, chỉ giữ một cửa sổ đủ rộng để câu truy vấn không quét cả bảng.
        //
        // Đo trên 3.214 lô: độ lệch giữa tháng kế hoạch và tháng KCS nằm trong khoảng
        // -1 đến +6 tháng, nên lùi 12 / tiến 2 là dư an toàn.
        $planFrom = $fromMonth;
        $planTo = $toMonth;

        if ($kcsMonth !== '') {
            $anchor = Carbon::parse($kcsMonth . '-01');
            $planFrom = $anchor->copy()->subMonths(12)->format('Y-m');
            $planTo = $anchor->copy()->addMonths(2)->format('Y-m');
        }

        return compact(
            'department',
            'fromMonth',
            'toMonth',
            'keyword',
            'kcsMonth',
            'result',
            'summaryYear',
            'planFrom',
            'planTo'
        );
    }

    /**
     * Danh sách lô đã áp toàn bộ bộ lọc của thanh lọc, dùng chung cho lưới và xuất Excel
     * để file luôn khớp với dữ liệu đang hiển thị trên trang.
     *
     * @param  array  $filters  kết quả của queryFilters()
     * @return array{datas: \Illuminate\Support\Collection, records: \Illuminate\Support\Collection,
     *               bomVersions: \Illuminate\Support\Collection, kcsDates: array<int, string>,
     *               mmsSuggestions: \Illuminate\Support\Collection, mmsCodeMismatch: \Illuminate\Support\Collection,
     *               mmsAvailable: bool}
     */
    private function filteredData(array $filters): array
    {
        ['department' => $department, 'planFrom' => $planFrom, 'planTo' => $planTo,
            'keyword' => $keyword, 'kcsMonth' => $kcsMonth, 'result' => $result] = $filters;

        $datas = $this->batches($department, $planFrom, $planTo, $keyword);

        $bomVersions = $this->captureBomVersions($datas);
        $mms = $this->mmsSuggestions($datas, $planFrom, $kcsMonth);

        // Đồng bộ trước khi đọc $records để lưới hiển thị đúng dữ liệu vừa cập nhật
        $this->syncFromMms($mms['suggestions']);

        $records = PlanMasterKcs::whereIn('plan_master_id', $datas->pluck('id'))
            ->get()
            ->keyBy('plan_master_id');

        $kcsDates = $this->effectiveKcsDates($datas, $mms['suggestions'], $records);

        if ($kcsMonth !== '') {
            $datas = $datas->filter(
                fn($data) => str_starts_with((string) ($kcsDates[$data->id] ?? ''), $kcsMonth)
            )->values();
        }

        // Lô chưa có dòng theo dõi thì chưa có kết quả nên tự khắc bị loại khỏi cả hai lựa chọn
        if ($result !== '') {
            $datas = $datas->filter(
                fn($data) => $records->get($data->id)?->result === $result
            )->values();
        }

        return [
            'datas' => $datas,
            'records' => $records,
            'bomVersions' => $bomVersions,
            'kcsDates' => $kcsDates,
            'mmsSuggestions' => $mms['suggestions'],
            'mmsCodeMismatch' => $mms['code_mismatch'],
            'mmsAvailable' => $mms['available'],
        ];
    }

    /**
     * Dựng file Excel cho export(): mỗi dòng lấy đúng giá trị hiển thị trên lưới
     * (giá trị đã lưu, hoặc gợi ý điền sẵn nếu lô chưa có dòng theo dõi), gồm cả các
     * cột dẫn xuất (Ngày Đủ Điều Kiện, Số Ngày HT, Kết Quả...).
     */
    private function buildExportSpreadsheet(array $filters, array $filtered): Spreadsheet
    {
        ['datas' => $datas, 'records' => $records, 'bomVersions' => $bomVersions,
            'kcsDates' => $kcsDates, 'mmsSuggestions' => $mmsSuggestions] = $filtered;

        $prefill = self::PREFILL_FROM_PLAN_MASTER;

        $headers = [
            'STT', 'Số Lệnh', 'Số Lô', 'Tên Sản Phẩm', 'Mã BTP', 'Mã TP', 'Thị Trường', 'Quy Cách',
            'Cỡ Lô', 'Lô TĐ',
            'Ngày Nhận Hồ Sơ', 'Người Đọc', 'Ngày Đọc Xong HS', 'Số Phiếu COATP', 'Ngày Nhận COATP',
            'DR/IR', 'Ngày AP DR/IR', 'OOS', 'Ngày AP OOS', 'Ngày AP DR/IR KCQ', 'Ngày AP OPV/PVR',
            'Ngày Chờ KCS Theo Đúng Thứ Tự Lô', 'Ngày KCS', 'Ghi Chú',
            'Ngày Đủ Điều Kiện', 'Số Ngày HT', 'KCS Pending', 'Tháng KCS', 'Mốc Quyết Định', 'Kết Quả',
        ];
        $lastColumn = Coordinate::stringFromColumnIndex(count($headers));

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Theo Doi Ho So KCS');

        $sheet->setCellValue('A1', 'THEO DÕI HỒ SƠ KCS');
        $sheet->mergeCells("A1:{$lastColumn}1");
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->setCellValue('A2', $this->exportFilterSummary($filters));
        $sheet->mergeCells("A2:{$lastColumn}2");

        $headerRow = 4;
        $sheet->fromArray($headers, null, 'A' . $headerRow);
        $sheet->getStyle("A{$headerRow}:{$lastColumn}{$headerRow}")->applyFromArray([
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'D9E1F2'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
        ]);

        // Ô ngày hiển thị dạng chuỗi d/m/Y, không dùng kiểu ngày thật của Excel: các cột
        // này trộn lẫn ngày và ô trống, để dạng chuỗi tránh Excel tự đoán sai định dạng.
        $formatDate = fn($value) => $value ? Carbon::parse($value)->format('d/m/Y') : '';

        $row = $headerRow + 1;

        foreach ($datas as $index => $datum) {
            $record = $records->get($datum->id);
            $bom = $bomVersions->get($datum->id);
            $mms = $mmsSuggestions->get($datum->id);
            $kcsDate = $kcsDates[$datum->id] ?? null;

            // Giống hệt closure $value()/$mmsValue() của dataTable.blade.php: lô đã có dòng
            // theo dõi thì lấy đúng dữ liệu đã lưu, chưa có thì lấy gợi ý điền sẵn từ kế hoạch.
            $value = function (string $field) use ($record, $datum, $prefill) {
                if ($record) {
                    return $record->getRawOriginal($field) ?? '';
                }

                return isset($prefill[$field]) ? ($datum->{$prefill[$field]} ?? '') : '';
            };

            $mmsValue = fn(string $field) => $mms[$field] ?? ($record?->getRawOriginal($field) ?? '');

            $orderNumber = trim((string) $datum->order_number_R1);
            if ($datum->order_number_R2) {
                $orderNumber .= ($orderNumber !== '' ? "\n" : '') . $datum->order_number_R2;
            }

            $btpCode = trim($datum->intermediate_code . ($bom?->btp_version ? ' (v' . $bom->btp_version . ')' : ''));
            $tpCode = trim($datum->finished_product_code . ($bom?->tp_version ? ' (v' . $bom->tp_version . ')' : ''));

            $sheet->fromArray([
                $index + 1,
                $orderNumber,
                $datum->actual_batch ?: $datum->batch,
                $datum->product_name,
                $btpCode,
                $tpCode,
                $datum->market,
                $datum->specification,
                trim(number_format((float) $datum->batch_qty, 0, ',', '.') . ' ' . $datum->unit_batch_qty),
                $datum->is_val ? 'TĐ' : '',

                $formatDate($value('record_received_date')),
                $value('reader'),
                $formatDate($value('record_done_date')),
                $mmsValue('coatp_number'),
                $formatDate($value('coatp_received_date')),
                $value('dr_ir'),
                $formatDate($value('dr_ir_approval_date')),
                $value('oos'),
                $formatDate($value('oos_approval_date')),
                $formatDate($value('dr_ir_kcq_approval_date')),
                $formatDate($value('opv_pvr_approval_date')),
                $formatDate($value('kcs_queue_date')),
                $formatDate($kcsDate),
                $value('note'),

                $formatDate($record?->eligible_date),
                $record?->completion_days,
                $record?->kcs_pending,
                $kcsDate ? (int) substr($kcsDate, 5, 2) : '',
                $record?->bottleneck,
                $record?->result,
            ], null, 'A' . $row);

            $row++;
        }

        $lastRow = max($row - 1, $headerRow);

        $sheet->getStyle("A{$headerRow}:{$lastColumn}{$lastRow}")->applyFromArray([
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN],
            ],
        ]);
        $sheet->getStyle("A{$headerRow}:{$lastColumn}{$lastRow}")
            ->getAlignment()
            ->setVertical(Alignment::VERTICAL_TOP)
            ->setWrapText(true);

        for ($col = 1; $col <= count($headers); $col++) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($col))->setAutoSize(true);
        }

        // Tên sản phẩm dài để auto size sẽ tràn màn hình
        $sheet->getColumnDimension('D')->setAutoSize(false)->setWidth(35);
        $sheet->getColumnDimension('X')->setAutoSize(false)->setWidth(25); // Ghi Chú

        // Ghim tiêu đề + 3 cột định danh đầu (Số Lệnh, Số Lô), giống vùng ghim của lưới
        $sheet->freezePane('D' . ($headerRow + 1));

        return $spreadsheet;
    }

    /** Dòng mô tả bộ lọc đang áp dụng, hiển thị ngay dưới tiêu đề file Excel */
    private function exportFilterSummary(array $filters): string
    {
        $parts = [];

        $parts[] = 'Phân xưởng: ' . ($filters['department']
            ? (self::DEPARTMENTS[$filters['department']] ?? $filters['department'])
            : 'Tất cả');

        if ($filters['kcsMonth'] !== '') {
            $parts[] = 'Tháng KCS: ' . Carbon::parse($filters['kcsMonth'] . '-01')->format('m/Y');
        } else {
            $parts[] = 'Tháng kế hoạch: ' . $filters['fromMonth'] . ' - ' . $filters['toMonth'];
        }

        if ($filters['result'] !== '') {
            $parts[] = 'Kết quả: ' . ($filters['result'] === PlanMasterKcs::RESULT_MET ? 'Đáp Ứng' : 'Trễ Hạn');
        }

        if ($filters['keyword'] !== '') {
            $parts[] = 'Tìm kiếm: ' . $filters['keyword'];
        }

        $parts[] = 'Ngày xuất: ' . now()->format('d/m/Y H:i');

        return implode('   |   ', $parts);
    }

    /**
     * Lưu một dòng theo dõi. Các cột dẫn xuất được tính lại từ toàn bộ dữ liệu
     * sau khi ghép thay đổi, nên sửa lẻ một ô vẫn cho kết quả đúng.
     */
    public function save(Request $request)
    {
        if (!user_has_permission(session('user')['userId'], 'kcs_tracking_update', 'boolean')) {
            return response()->json(['success' => false, 'message' => 'Bạn không có quyền cập nhật theo dõi hồ sơ KCS'], 403);
        }

        $request->validate([
            'plan_master_id' => 'required|integer|exists:plan_master,id',
            'record_received_date' => 'nullable|date',
            'reader' => 'nullable|string|max:100',
            'record_done_date' => 'nullable|date',
            'coatp_received_date' => 'nullable|date',
            'dr_ir' => 'nullable|string|max:100',
            'dr_ir_approval_date' => 'nullable|date',
            'oos' => 'nullable|string|max:100',
            'oos_approval_date' => 'nullable|date',
            'dr_ir_kcq_approval_date' => 'nullable|date',
            'opv_pvr_approval_date' => 'nullable|date',
            'kcs_queue_date' => 'nullable|date',
            'note' => 'nullable|string',
        ]);

        try {
            $changes = DB::transaction(function () use ($request) {
                $record = PlanMasterKcs::firstOrNew(['plan_master_id' => $request->plan_master_id]);
                $isNew = !$record->exists;

                // Chụp giá trị cũ trước khi ghép thay đổi để so ra đúng những ô thật sự đổi
                $before = $this->snapshot($record);

                foreach (PlanMasterKcs::inputFields() as $field) {
                    if ($request->has($field)) {
                        $value = $request->input($field);
                        $record->{$field} = ($value === '' ? null : $value);
                    }
                }

                // Ngày KCS / Số phiếu COATP luôn lấy thẳng từ MMS, kể cả khi tạo dòng mới
                $this->applyMmsFields($record);

                $record->fill(PlanMasterKcs::derive($this->snapshot($record)));
                $record->updated_by = session('user')['userName'] ?? 'System';
                $record->save();

                $this->recordHistory($record, $before, $isNew);

                return $record;
            });

            return response()->json([
                'success' => true,
                'message' => 'Đã lưu',
                'derived' => [
                    'eligible_date' => optional($changes->eligible_date)->format('d/m/Y'),
                    'completion_days' => $changes->completion_days,
                    'bottleneck' => $changes->bottleneck,
                    'kcs_pending' => $changes->kcs_pending,
                    'kcs_month' => $changes->kcs_month,
                    'result' => $changes->result,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()], 500);
        }
    }

    /** Lịch sử chỉnh sửa của một lô, hiển thị trong modal */
    public function history(Request $request)
    {
        $request->validate(['plan_master_id' => 'required|integer']);

        $histories = PlanMasterKcsHistory::where('plan_master_id', $request->plan_master_id)
            ->orderBy('id', 'desc')
            ->get();

        // Vết cũ lưu userName (mã nhân viên); tra ngược về họ tên để cột "Người Sửa" luôn đọc được
        $names = DB::table('user_management')
            ->whereIn('userName', $histories->pluck('changed_by')->filter()->unique())
            ->pluck('fullName', 'userName');

        $html = view('pages.plan.kcs_tracking.history', compact('histories', 'names'))->render();

        return response()->json(['html' => $html]);
    }

    /**
     * Giá trị hiện tại của mọi cột dữ liệu gốc (người dùng nhập + đồng bộ từ MMS),
     * đã chuẩn hoá về chuỗi để so sánh và ghi vết.
     *
     * @return array<string, string|null>
     */
    private function snapshot(PlanMasterKcs $record): array
    {
        $snapshot = [];

        foreach (PlanMasterKcs::trackedFields() as $field) {
            $value = $record->{$field};

            if ($value instanceof Carbon) {
                $value = $value->toDateString();
            }

            $snapshot[$field] = ($value === null || $value === '') ? null : (string) $value;
        }

        return $snapshot;
    }

    /**
     * Ghi vết những ô thật sự thay đổi. Lần lưu đầu tiên của một lô được đánh dấu
     * "create"; các lần sau là "update". Không có ô nào đổi thì không ghi gì.
     *
     * @param  array<string, string|null>  $before
     * @param  string|null  $changedBy  người sửa; để trống thì lấy người đang đăng nhập
     *                                  (đợt đồng bộ tự động truyền vào "MMS")
     */
    private function recordHistory(PlanMasterKcs $record, array $before, bool $isNew, ?string $changedBy = null): void
    {
        $after = $this->snapshot($record);
        $now = Carbon::now();
        $changedBy ??= session('user')['fullName'] ?? (session('user')['userName'] ?? 'System');
        $rows = [];

        foreach ($after as $field => $newValue) {
            $oldValue = $before[$field] ?? null;

            if ($oldValue === $newValue) {
                continue;
            }

            $rows[] = [
                'plan_master_id' => $record->plan_master_id,
                'kcs_id' => $record->id,
                'action' => $isNew ? 'create' : 'update',
                'field' => $field,
                'old_value' => $oldValue,
                'new_value' => $newValue,
                'changed_by' => $changedBy,
                'created_at' => $now,
            ];
        }

        if ($rows) {
            PlanMasterKcsHistory::insert($rows);
        }
    }

    /** Bảng tổng kết tỉ lệ (dùng khi người dùng đổi phân xưởng / năm mà không tải lại trang) */
    public function summary(Request $request)
    {
        $department = $request->query('department', '');
        $year = (int) ($request->query('year') ?: Carbon::now()->year);

        $html = view('pages.plan.kcs_tracking.summary', [
            'summary' => $this->buildSummary($department, $year),
            'summaryYear' => $year,
            'summaryYears' => $this->summaryYears(),
            'departments' => self::DEPARTMENTS,
            'department' => $department,
        ])->render();

        return response()->json(['html' => $html]);
    }

    /**
     * Chốt version BOM (mã BTP / TP) cho những lô đã có số lệnh nhưng chưa được chốt,
     * rồi trả về bản đã chốt theo plan_master_id để lưới hiển thị.
     *
     * Chỉ chốt một lần: lô đã có bản ghi thì giữ nguyên version cũ dù MMS đã nâng
     * revision, vì hồ sơ KCS phải phản ánh version tại thời điểm cấp số lệnh.
     * Lô chưa có số lệnh thì chưa chốt, để version không bị lấy sớm hơn thực tế.
     */
    private function captureBomVersions($datas)
    {
        $existing = DB::table('plan_master_bom_version')
            ->whereIn('plan_master_id', $datas->pluck('id'))
            ->get()
            ->keyBy('plan_master_id');

        $pending = $datas->filter(fn($data) => !empty($data->order_number_R1)
            && !$existing->has($data->id));

        if ($pending->isEmpty()) {
            return $existing;
        }

        // MMS không kết nối được thì bỏ qua lần chốt này, trang vẫn dùng bình thường
        if (!MmsBom::isReachable()) {
            return $existing;
        }

        $now = Carbon::now();
        $inserts = [];

        foreach ($pending as $data) {
            // Tra version tại đúng ngày cấp số lệnh, không lấy version hiện tại: BOM được
            // nâng revision theo thời gian nên lô cũ phải giữ version dùng lúc sản xuất.
            $orderedAt = $data->create_at_order_number ?: $now;

            $inserts[] = [
                'plan_master_id' => $data->id,
                'order_number' => $data->order_number_R1,
                'btp_code' => $data->intermediate_code,
                'btp_version' => MmsBom::revisionAsOf($data->intermediate_code, $orderedAt),
                'tp_code' => $data->finished_product_code,
                'tp_version' => MmsBom::revisionAsOf($data->finished_product_code, $orderedAt),
                'captured_at' => $now,
            ];
        }

        foreach (array_chunk($inserts, 200) as $chunk) {
            // insertOrIgnore: hai người mở trang cùng lúc thì bản chốt trước thắng
            DB::table('plan_master_bom_version')->insertOrIgnore($chunk);
        }

        return DB::table('plan_master_bom_version')
            ->whereIn('plan_master_id', $datas->pluck('id'))
            ->get()
            ->keyBy('plan_master_id');
    }

    /**
     * Ngày KCS đang có hiệu lực của từng lô, dạng [plan_master_id => 'Y-m-d'].
     *
     * Ưu tiên MMS rồi mới đến giá trị đã lưu: lô chưa có dòng theo dõi vẫn thấy được ngày
     * của MMS, nên cột Tháng KCS và bộ lọc theo tháng KCS mới khớp với ô Ngày KCS trên lưới.
     * Nếu chỉ đọc plan_master_KCS.kcs_month thì hơn 2.500 lô có ngày trên MMS nhưng chưa
     * theo dõi sẽ bị bỏ sót, khiến bộ lọc trông như hỏng.
     *
     * @return array<int, string>
     */
    private function effectiveKcsDates($datas, $suggestions, $records): array
    {
        $dates = [];

        foreach ($datas as $data) {
            $date = $suggestions->get($data->id)['kcs_date']
                ?? $records->get($data->id)?->getRawOriginal('kcs_date');

            if ($date) {
                $dates[$data->id] = substr((string) $date, 0, 10);
            }
        }

        return $dates;
    }

    /**
     * Cập nhật Ngày KCS / Số phiếu COATP từ MMS xuống các dòng theo dõi ĐÃ CÓ.
     *
     * Chạy mỗi lần mở trang, chỉ ghi những dòng thật sự lệch nên lần mở sau gần như
     * không đụng vào database. Mọi thay đổi đều được ghi vết với người sửa là "MMS".
     *
     * Cố ý KHÔNG tạo dòng mới cho lô chưa theo dõi: dòng chỉ có Ngày KCS mà thiếu Ngày
     * Đọc Xong Hồ Sơ thì không chấm được kết quả, nhưng vẫn bị bảng Tổng Kết Tỉ Lệ đếm
     * vào tổng - tự sinh hơn 2.500 dòng như vậy sẽ kéo tỉ lệ đúng hạn xuống gần 0%.
     * Lô chưa theo dõi vẫn thấy ngày của MMS trên lưới, và ngày đó được ghi xuống ngay
     * khi người dùng nhập mốc đầu tiên (xem applyMmsFields()).
     *
     * Không có lệnh nào ghi ngược lên MMS.
     *
     * @param  \Illuminate\Support\Collection  $suggestions  dữ liệu MMS theo plan_master_id
     * @return int  số dòng đã cập nhật
     */
    private function syncFromMms($suggestions): int
    {
        if ($suggestions->isEmpty()) {
            return 0;
        }

        $records = PlanMasterKcs::whereIn('plan_master_id', $suggestions->keys())->get();
        $updated = 0;

        foreach ($records as $record) {
            $mms = $suggestions->get($record->plan_master_id);

            if (!$mms) {
                continue;
            }

            $before = $this->snapshot($record);
            $changed = false;

            foreach (PlanMasterKcs::mmsFields() as $field) {
                $value = $mms[$field] ?? null;

                // MMS để trống thì giữ nguyên giá trị cũ, không xoá trắng dữ liệu đang có
                if ($value === null || $value === '' || ($before[$field] ?? null) === $value) {
                    continue;
                }

                $record->{$field} = $value;
                $changed = true;
            }

            if (!$changed) {
                continue;
            }

            $record->fill(PlanMasterKcs::derive($this->snapshot($record)));
            $record->updated_by = 'MMS';
            $record->save();

            $this->recordHistory($record, $before, false, 'MMS');
            $updated++;
        }

        return $updated;
    }

    /**
     * Ghi Ngày KCS / Số phiếu COATP của MMS vào một dòng đang được lưu.
     *
     * Hai cột này không nhận giá trị từ request: ô trên lưới đã khoá, nhưng chặn ở đây
     * mới thật sự đảm bảo dữ liệu luôn bằng MMS kể cả khi request bị sửa.
     *
     * Tra lẻ từng lô (~8ms) thay vì dùng lại bảng tra hàng loạt của trang danh sách, vì
     * lưới tự lưu theo từng ô nên mỗi lần lưu chỉ đụng đúng một lô.
     */
    private function applyMmsFields(PlanMasterKcs $record): void
    {
        $lot = DB::table('plan_master as pm')
            ->leftJoin('finished_product_category as fpc', 'pm.product_caterogy_id', '=', 'fpc.id')
            ->where('pm.id', $record->plan_master_id)
            ->select('pm.batch', 'pm.actual_batch', 'fpc.finished_product_code')
            ->first();

        if (!$lot) {
            return;
        }

        try {
            $approval = MmsFgQc::approvalFor(
                $lot->finished_product_code,
                $lot->actual_batch ?: $lot->batch
            );
        } catch (\Throwable) {
            // MMS trục trặc thì vẫn cho lưu các mốc người dùng nhập, giữ nguyên giá trị cũ
            return;
        }

        if (!$approval) {
            return;
        }

        foreach (PlanMasterKcs::mmsFields() as $field) {
            if (($approval[$field] ?? '') !== '') {
                $record->{$field} = $approval[$field];
            }
        }
    }

    /**
     * Ngày KCS / số phiếu COATP lấy từ MMS cho các lô đang hiển thị, kèm danh sách lô
     * bị lệch mã TP giữa hai hệ thống.
     *
     * Chỉ đọc MMS rồi trả về cho lưới và cho syncFromMms(); không ghi gì ở đây.
     *
     * Lô nào lệch mã TP giữa hai hệ thống thì cố ý bỏ trống thay vì đoán, chỉ cảnh báo ở
     * cột Mã TP để người dùng biết vì sao ô không tự điền.
     *
     * Cố ý KHÔNG coi "số lô có trên MMS dưới mã khác" là lệch mã: số lô đánh theo ngày nên
     * gần như lô nào cũng bị nhiều mã dùng chung, quy tắc đó cho 84/362 lô cảnh báo mà hầu
     * hết là nhiễu. Chỉ báo khi số lệnh khớp mà mã TP khác - lúc đó mới thật sự là hai hệ
     * thống ghi khác nhau về cùng một lô.
     *
     * @return array{suggestions: \Illuminate\Support\Collection, code_mismatch: \Illuminate\Support\Collection, available: bool}
     */
    private function mmsSuggestions($datas, string $fromMonth, string $kcsMonth = ''): array
    {
        if ($datas->isEmpty()) {
            return ['suggestions' => collect(), 'code_mismatch' => collect(), 'available' => true];
        }

        try {
            // Lùi 3 tháng so với tháng kế hoạch đầu tiên: lô có thể được KCS sớm hơn
            // tháng kế hoạch của nó, nhất là lô sản xuất vượt tiến độ.
            $since = ($fromMonth ? Carbon::parse($fromMonth . '-01') : Carbon::now())->subMonths(3);

            // Người dùng lọc một tháng KCS nằm trước khoảng trên thì phải nới cửa sổ ra,
            // nếu không những lô cần tìm lại không có trong bảng tra.
            if ($kcsMonth !== '') {
                $wanted = Carbon::parse($kcsMonth . '-01')->subMonth();
                $since = $wanted->lt($since) ? $wanted : $since;
            }

            $lookup = MmsFgQc::approvalsSince($since);
        } catch (\Throwable) {
            // MMS không kết nối được thì trang vẫn dùng bình thường, chỉ mất phần gợi ý
            return ['suggestions' => collect(), 'code_mismatch' => collect(), 'available' => false];
        }

        $suggestions = [];
        $mismatch = [];

        foreach ($datas as $data) {
            $batch = trim((string) ($data->actual_batch ?: $data->batch));
            $code = trim((string) $data->finished_product_code);

            // Thiếu một trong hai thì không đủ khoá để tra, cũng không phải lệch mã
            if ($batch === '' || $code === '') {
                continue;
            }

            $hit = $lookup['approvals'][$code . '|' . $batch] ?? null;

            if ($hit) {
                $suggestions[$data->id] = $hit;
                continue;
            }

            $order = MmsFgQc::normalizeOrderNumber($data->order_number_R1)
                ?? MmsFgQc::normalizeOrderNumber($data->order_number_R2);

            $onMms = $order ? ($lookup['orders'][$order] ?? null) : null;

            // Cùng số lệnh, cùng số lô, khác mã TP: hai hệ thống đang ghi khác nhau
            if ($onMms && $onMms['batch'] === $batch && $onMms['code'] !== $code) {
                $mismatch[$data->id] = $onMms['code'];
            }
        }

        return [
            'suggestions' => collect($suggestions),
            'code_mismatch' => collect($mismatch),
            'available' => true,
        ];
    }

    /** Danh sách lô sản xuất cần theo dõi hồ sơ KCS trong khoảng tháng kế hoạch */
    private function batches(string $department, string $fromMonth, string $toMonth, string $keyword)
    {
        $query = DB::table('plan_master')
            ->join('plan_list as pl', 'plan_master.plan_list_id', '=', 'pl.id')
            ->leftJoin('finished_product_category', 'plan_master.product_caterogy_id', '=', 'finished_product_category.id')
            ->leftJoin('product_name', 'finished_product_category.product_name_id', '=', 'product_name.id')
            ->leftJoin('market', 'finished_product_category.market_id', '=', 'market.id')
            ->leftJoin('specification', 'finished_product_category.specification_id', '=', 'specification.id')
            ->select(
                'plan_master.id',
                'plan_master.batch',
                'plan_master.actual_batch',
                'plan_master.order_number_R1',
                'plan_master.order_number_R2',
                'plan_master.create_at_order_number',
                'plan_master.expected_date',
                'plan_master.is_val',
                'plan_master.code_val',
                'plan_master.deparment_code',
                'plan_master.actual_record_date',
                'plan_master.actual_CoA_date',
                'plan_master.actual_KCS',
                'finished_product_category.intermediate_code',
                'finished_product_category.finished_product_code',
                'finished_product_category.batch_qty',
                'finished_product_category.unit_batch_qty',
                'product_name.name as product_name',
                'market.name as market',
                'specification.name as specification',
                'pl.month as plan_month',
                'pl.year as plan_year'
            )
            ->where('plan_master.active', 1)
            ->where('plan_master.cancel', 0)
            ->where('pl.type', 1);

        if ($department) {
            $query->where('plan_master.deparment_code', $department);
        }

        if ($fromMonth) {
            $query->whereRaw("STR_TO_DATE(CONCAT('01-', pl.month, '-', pl.year), '%d-%c-%Y') >= ?", [$fromMonth . '-01']);
        }

        if ($toMonth) {
            $query->whereRaw("STR_TO_DATE(CONCAT('01-', pl.month, '-', pl.year), '%d-%c-%Y') <= ?", [$toMonth . '-01']);
        }

        if ($keyword) {
            $query->where(function ($q) use ($keyword) {
                $q->where('finished_product_category.finished_product_code', 'LIKE', '%' . $keyword . '%')
                    ->orWhere('finished_product_category.intermediate_code', 'LIKE', '%' . $keyword . '%')
                    ->orWhere('product_name.name', 'LIKE', '%' . $keyword . '%')
                    ->orWhere('plan_master.batch', 'LIKE', '%' . $keyword . '%')
                    ->orWhere('plan_master.actual_batch', 'LIKE', '%' . $keyword . '%');
            });
        }

        return $query->orderBy('pl.year', 'desc')
            ->orderBy('pl.month', 'desc')
            ->orderBy('plan_master.expected_date', 'asc')
            ->orderBy('plan_master.batch', 'asc')
            ->get();
    }

    /**
     * Tổng kết tỉ lệ lô KCS đúng hạn theo từng tháng và trung bình theo quý.
     *
     * CHỈ đếm lô đã chấm được kết quả. Lô đã có Ngày KCS nhưng chưa nhập đủ mốc để ra Ngày
     * Đủ Điều Kiện thì chưa đánh giá được nên không xuất hiện ở đây - nhờ vậy Đúng Hạn + Trễ
     * luôn bằng Tổng. Trước đây đếm cả lô chưa chấm rồi lấy late = total - on_time, khiến 28
     * lô chưa chấm của tháng 8/2026 bị tính thành trễ hạn và tỉ lệ tụt từ 76% xuống 52%.
     *
     * Dùng đúng bộ lọc nền của lưới (active, cancel, pl.type) để hai tab so sánh được với nhau.
     *
     * Vẫn đếm trên plan_master_KCS chứ không theo ngày KCS của MMS: bảng này đo hồ sơ đã được
     * theo dõi và chấm, nên số lô ít hơn số lô có Ngày KCS trên lưới.
     *
     * @return array{months: array<int, array>, quarters: array<int, array>, total: array}
     */
    private function buildSummary(string $department, int $year): array
    {
        $rows = $this->summaryQuery($department, $year)
            ->groupBy('plan_master_KCS.kcs_month')
            ->select(
                'plan_master_KCS.kcs_month',
                DB::raw('COUNT(*) as total'),
                DB::raw("SUM(CASE WHEN plan_master_KCS.result = '" . PlanMasterKcs::RESULT_MET . "' THEN 1 ELSE 0 END) as on_time")
            )
            ->get()
            ->keyBy('kcs_month');

        $months = [];

        for ($month = 1; $month <= 12; $month++) {
            $total = (int) ($rows[$month]->total ?? 0);
            $onTime = (int) ($rows[$month]->on_time ?? 0);

            $months[$month] = [
                'month' => $month,
                'total' => $total,
                'on_time' => $onTime,
                // Mọi lô ở đây đều đã chấm được nên phần còn lại đúng là lô trễ
                'late' => $total - $onTime,
                'rate' => $total > 0 ? (int) round($onTime / $total * 100) : null,
            ];
        }

        $quarters = [];

        for ($quarter = 1; $quarter <= 4; $quarter++) {
            $inQuarter = array_slice($months, ($quarter - 1) * 3, 3);
            $rates = array_filter(array_column($inQuarter, 'rate'), fn($rate) => $rate !== null);

            $quarters[$quarter] = [
                'quarter' => $quarter,
                'total' => array_sum(array_column($inQuarter, 'total')),
                'on_time' => array_sum(array_column($inQuarter, 'on_time')),
                'late' => array_sum(array_column($inQuarter, 'late')),
                // Trung bình các tỉ lệ tháng, đúng như công thức AVERAGE của file gốc
                'rate' => count($rates) > 0 ? (int) round(array_sum($rates) / count($rates)) : null,
            ];
        }

        $total = array_sum(array_column($months, 'total'));
        $onTime = array_sum(array_column($months, 'on_time'));

        return [
            'months' => $months,
            'quarters' => $quarters,
            'total' => [
                'total' => $total,
                'on_time' => $onTime,
                'late' => $total - $onTime,
                'rate' => $total > 0 ? (int) round($onTime / $total * 100) : null,
            ],
            'late_reasons' => $this->lateReasons($department, $year),
        ];
    }

    /**
     * Nhóm lô đã chấm được kết quả của một năm / phân xưởng.
     * Dùng chung cho bảng tỉ lệ theo tháng và bảng nguyên nhân trễ để hai bảng
     * luôn đếm trên cùng một tập lô.
     */
    private function summaryQuery(string $department, int $year)
    {
        return PlanMasterKcs::query()
            ->join('plan_master', 'plan_master_KCS.plan_master_id', '=', 'plan_master.id')
            ->join('plan_list as pl', 'plan_master.plan_list_id', '=', 'pl.id')
            ->where('plan_master_KCS.kcs_year', $year)
            ->whereNotNull('plan_master_KCS.result')
            ->where('plan_master_KCS.result', '<>', '')
            ->where('plan_master.active', 1)
            ->where('plan_master.cancel', 0)
            ->where('pl.type', 1)
            ->when($department, fn($q) => $q->where('plan_master.deparment_code', $department));
    }

    /**
     * Phân bố nguyên nhân của các lô KCS trễ trong năm, theo cột Mốc Quyết Định.
     *
     * Mốc Quyết Định là mốc hoàn tất muộn nhất - tức khâu giữ hồ sơ lâu nhất trước khi lô
     * đủ điều kiện KCS - nên đây là dữ liệu sẵn có sát nghĩa "nguyên nhân" nhất. Tỉ lệ tính
     * trên tổng số lô TRỄ (không phải trên tổng số lô đã chấm) để trả lời "nguyên nhân này
     * chiếm bao nhiêu phần trong các lô trễ".
     *
     * @return array{total: int, rows: array<int, array{reason: string, total: int, rate: float}>}
     */
    private function lateReasons(string $department, int $year): array
    {
        $rows = $this->summaryQuery($department, $year)
            ->where('plan_master_KCS.result', PlanMasterKcs::RESULT_NOT_MET)
            ->groupBy('plan_master_KCS.bottleneck')
            ->select(
                'plan_master_KCS.bottleneck',
                DB::raw('COUNT(*) as total'),
                DB::raw('ROUND(AVG(plan_master_KCS.completion_days), 1) as avg_days')
            )
            ->orderByDesc('total')
            ->get();

        $late = (int) $rows->sum('total');

        return [
            'total' => $late,
            'rows' => $rows->map(fn($row) => [
                'reason' => $row->bottleneck ?: 'Chưa xác định',
                'total' => (int) $row->total,
                'rate' => $late > 0 ? round($row->total / $late * 100, 1) : 0.0,
                'avg_days' => $row->avg_days === null ? null : (float) $row->avg_days,
            ])->all(),
        ];
    }

    /** Các năm có dữ liệu KCS, luôn kèm năm hiện tại để bảng tổng kết không rỗng */
    private function summaryYears(): array
    {
        $years = PlanMasterKcs::whereNotNull('kcs_year')
            ->distinct()
            ->pluck('kcs_year')
            ->map(fn($year) => (int) $year)
            ->all();

        $years[] = Carbon::now()->year;
        $years = array_unique($years);
        rsort($years);

        return $years;
    }
}
