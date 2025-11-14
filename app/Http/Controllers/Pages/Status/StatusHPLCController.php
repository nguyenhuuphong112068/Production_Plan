<?php

namespace App\Http\Controllers\Pages\Status;
use PhpOffice\PhpSpreadsheet\IOFactory;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;



class StatusHPLCController extends Controller
{
        public function show(Request $request){
                $firstDate = Carbon::parse ($request->firstDate?? Carbon::now());

                $general_notication = DB::table('room_status_notification')
                        ->where ('deparment_code', "QC")
                        ->where ('durability', '>=' , now())
                        ->orderBy('id','desc')->first();

                $general_notication = DB::table('room_status_notification')
                        ->where ('deparment_code', "QC")
                        ->where ('durability', '>=' , Carbon::now())
                        ->orderBy('id','desc')->first();

                // $datas = DB::table('hplc_instrument')
                //         ->leftJoin('hplc_status', function ($join) use ($firstDate) {
                //                 $join->on('hplc_instrument.id', '=', 'hplc_status.ins_id')
                //                 ->whereIn(DB::raw('DATE(hplc_status.start_time)'), [
                //                         $firstDate->toDateString(),
                //                         //$firstDate->copy()->addDays(1)->toDateString(),
                //                 ]);
                //         })
                //         ->select('hplc_instrument.id as ins_id', 'hplc_instrument.code', 'hplc_status.*')
                //         ->orderBy('hplc_status.start_time', 'desc') 
                //         ->orderBy('hplc_status.id', 'desc')
                //         ->get()
                //         ->groupBy(function($item) {
                //                 // nhóm theo ngày
                //                 return $item->start_time
                //                 ? \Carbon\Carbon::parse($item->start_time)->toDateString()
                //                 : 'no_data';
                //         })
                //         ->map(function($itemsByDate) {
                //                 // Với mỗi ngày, nhóm theo ins_id
                //                 return $itemsByDate->groupBy('ins_id')
                //                 ->map(function($itemsByIns) {
                //                         // Lấy 2 bản ghi mới nhất mỗi ins_id trong ngày
                //                         return $itemsByIns->take(3);
                //                 })
                //                 ->flatMap(function($items) {
                //                         return $items; // trả về mảng phẳng các bản ghi
                //                 });
                // });

                $datas = DB::table('hplc_instrument')
                        ->leftJoin('hplc_status','hplc_instrument.id', '=', 'hplc_status.ins_id') 
                        ->where(DB::raw('DATE(hplc_status.start_time)'),$firstDate->toDateString())
                        ->select('hplc_instrument.id as ins_id', 'hplc_instrument.code', 'hplc_status.*')
                        ->orderBy('hplc_status.start_time', 'desc') 
                        ->orderBy('hplc_status.id', 'desc')
                        ->get();
                       
                       


 
                //dd( $datas);
                                                        
        
                session()->put(['title'=> "TRANG THÁI KIỂM NGHIỆM - HPLC - QC1"]);
                
                return view('pages.status_HPLC.dataTableShow',[
                        'datas' =>  $datas,
                        'general_notication' =>  $general_notication,
                        'firstDate' => $firstDate,
                        'general_notication' =>  $general_notication,
                ]);
        }


        public function import(Request $request){
        /*-----------------------------------------
        | 1. Validate password + input
        -----------------------------------------*/
        if (!$request->passWord || $request->passWord !== "1") {
                return back()->withErrors([
                'sheet' => "❌ PassWord Không Chính Xác, Vui Lòng Nhập Lại!."
                ]);
        }

        if ($request->hasFile('excel_file') && !$request->date_upload) {
                return back()->withErrors([
                'sheet' => "❌ Vui Lòng Chọn Ngày cần Upload !"
                ]);
        }

        /*-----------------------------------------
        | 2. Xử lý file Excel
        -----------------------------------------*/
        if ($request->hasFile('excel_file') && $request->filled('date_upload')) {

                $path = $request->file('excel_file')->getRealPath();
                $spreadsheet = IOFactory::load($path);

                $sheetNames = $spreadsheet->getSheetNames();

                if (!in_array($request->date_upload, $sheetNames)) {
                return back()->withErrors([
                        'sheet' => "❌ Sheet '{$request->date_upload}' không tồn tại trong file Excel."
                ]);
                }

                // Chọn sheet
                $spreadsheet->setActiveSheetIndexByName($request->date_upload);
                $rows = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);

                // Map code → ins_id
                $allInsIds = DB::table('hplc_instrument')->pluck('id', 'code');

                // Code update theo yêu cầu: yymmdd_HHMMSS
                $code_update = now()->format('ymd_His');

                /*-----------------------------------------
                | 3. Gom dữ liệu theo dòng
                -----------------------------------------*/
                $excelDataByIns = [];
                $current = null;

                foreach ($rows as $index => $row) {

                // Bỏ header (dòng 1–7)
                if ($index < 7) continue;

                $hasNewRow = !empty($row['A']); // STT có hay không
                $code      = $row['B'] ?? null;

                /*-----------------------------------------
                | Trường hợp: DÒNG MỚI (A có số)
                -----------------------------------------*/
                if ($hasNewRow) {

                        // Nếu có dòng trước đó → lưu lại
                        if ($current && !empty($current['ins_id'])) {
                        $excelDataByIns[$current['ins_id']][] = $current;
                        }

                        // Tìm ins_id theo code
                        $ins_id = $allInsIds[$code] ?? null;

                        // Tạo record mới
                        $current = [
                        'ins_id'      => $ins_id,
                        'code_update' => $code_update,
                        'column'      => $row['C'] ?? null,
                        'analyst'     => $row['D'] ?? null,
                        'sample_name' => $row['E'] ?? null,
                        'batch_no'    => $row['F'] ?? null,
                        'stage'       => $row['G'] ?? null,
                        'test'        => $row['H'] ?? null,
                        'notes'       => $row['I'] ?? null,
                        'remark'      => $row['J'] ?? null,
                        'start_time'  => $this->combineDateTime($row['K'], $row['L']),
                        'end_time'    => $this->combineDateTime($row['M'], $row['N']),
                        'created_at'  => now(),
                        'updated_at'  => now(),
                        ];

                        continue;
                }

                /*-----------------------------------------
                | Trường hợp: DÒNG TIẾP THEO (A = null)
                -----------------------------------------*/

                if ($current) {

                // --- Kiểm tra xem dòng này có "khác dữ liệu" không ---
                $isDifferent =
                        ($row['C'] ?? null) !== $current['column'] ||
                        ($row['D'] ?? null) !== $current['analyst'] ||
                        ($row['I'] ?? null) !== $current['notes'] ||
                        ($row['J'] ?? null) !== $current['remark'];

                // Nếu khác dữ liệu → push current, và tạo dòng mới
                if ($isDifferent) {

                        // Lưu dòng trước
                        if (!empty($current['ins_id'])) {
                        $excelDataByIns[$current['ins_id']][] = $current;
                        }

                        // Tạo record mới từ dòng này
                        $current = [
                        'ins_id'      => $allInsIds[$row['B']] ?? null,
                        'code_update' => $code_update,
                        'column'      => $row['C'] ?? null,
                        'analyst'     => $row['D'] ?? null,
                        'sample_name' => $row['E'] ?? null,
                        'batch_no'    => $row['F'] ?? null,
                        'stage'       => $row['G'] ?? null,
                        'test'        => $row['H'] ?? null,
                        'notes'       => $row['I'] ?? null,
                        'remark'      => $row['J'] ?? null,
                        'start_time'  => $this->combineDateTime($row['K'], $row['L']),
                        'end_time'    => $this->combineDateTime($row['M'], $row['N']),
                        'created_at'  => now(),
                        'updated_at'  => now(),
                        ];

                        continue; // Không gộp — vì đã tách sang dòng mới
                }

                // --- Nếu dữ liệu GIỐNG → gộp vào dòng hiện tại ---
                if (!empty($row['E']))  $current['sample_name'] .= " | " . $row['E'];
                if (!empty($row['F']))  $current['batch_no']    .= " | " . $row['F'];
                if (!empty($row['G']))  $current['stage']       .= " | " . $row['G'];
                if (!empty($row['H']))  $current['test']        .= " | " . $row['H'];
                }
                }

                /*-----------------------------------------
                | 4. Push dòng cuối cùng
                -----------------------------------------*/
                if ($current && !empty($current['ins_id'])) {
                $excelDataByIns[$current['ins_id']][] = $current;
                }

                /*-----------------------------------------
                | 5. Gộp lại tất cả dữ liệu để insert
                -----------------------------------------*/
                $allDataToInsert = [];
                foreach ($excelDataByIns as $ins_id => $datas) {
                $allDataToInsert = array_merge($allDataToInsert, $datas);
                }

                if (!empty($allDataToInsert)) {
                DB::table('hplc_status')->insert($allDataToInsert);
                }
        }

        /*-----------------------------------------
        | 6. Lưu notification nếu có
        -----------------------------------------*/
        if ($request->notification) {
                DB::table('room_status_notification')->insert([
                'notification'     => $request->notification,
                'group_code'       => 0,
                'durability'       => $request->durability ?? now(),
                'deparment_code'   => "QC",
                'created_by'       => null,
                'created_at'       => now(),
                ]);
        }

        /*-----------------------------------------
        | 7. Xong
        -----------------------------------------*/
        return back()->with('success', "✅ Import dữ liệu phân công ngày $request->date_upload thành công!");
        }





        private function combineDateTime($date, $time){
        if (empty($date)) return null;
        try {
                return Carbon::parse(trim($date . ' ' . ($time ?? '00:00:00')));
        } catch (\Exception $e) {
                return null;
        }
        }

}
