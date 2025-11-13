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

                $datas = DB::table('hplc_instrument')
                        ->leftJoin('hplc_status', function ($join) use ($firstDate) {
                                $join->on('hplc_instrument.id', '=', 'hplc_status.ins_id')
                                ->whereIn(DB::raw('DATE(hplc_status.start_time)'), [
                                        $firstDate->toDateString(),
                                        $firstDate->copy()->addDays(1)->toDateString(),
                                ]);
                        })
                        ->select('hplc_instrument.id as ins_id', 'hplc_instrument.code', 'hplc_status.*')
                        ->orderBy('hplc_status.start_time', 'desc') // sắp xếp theo thời gian mới nhất
                        ->orderBy('hplc_status.id', 'desc')
                        ->get()
                        ->groupBy(function($item) {
                                // nhóm theo ngày
                                return $item->start_time
                                ? \Carbon\Carbon::parse($item->start_time)->toDateString()
                                : 'no_data';
                        })
                        ->map(function($itemsByDate) {
                                // Với mỗi ngày, nhóm theo ins_id
                                return $itemsByDate->groupBy('ins_id')
                                ->map(function($itemsByIns) {
                                        // Lấy 2 bản ghi mới nhất mỗi ins_id trong ngày
                                        return $itemsByIns->take(2);
                                })
                                ->flatMap(function($items) {
                                        return $items; // trả về mảng phẳng các bản ghi
                                });
                });


                $datas = $datas->map(function ($items) {
                        return $items->sortBy('id'); 
                });
                //dd($firstDate, $datas);
                                                        
        
                session()->put(['title'=> "TRANG THÁI KIỂM NGHIỆM - HPLC - QC1"]);
                
                return view('pages.status_HPLC.dataTableShow',[
                        'datas' =>  $datas,
                        'general_notication' =>  $general_notication,
                        'firstDate' => $firstDate,
                        'general_notication' =>  $general_notication,
                ]);
        }



        public function import(Request $request){
             // dd ($request->all());
                if (!$request->passWord || $request->passWord != "Stell@QC" ) {
                         return back()->withErrors([
                                'sheet' => "❌ PassWord Không Chính Xác, Vui Lòng Nhập Lại!."
                        ]);
                }

                if ($request->hasFile('excel_file') && !$request->date_upload) {
                         return back()->withErrors([
                                'sheet' => "❌ Vui Lòng Chọn Ngày cần Upload !."
                        ]);
                }

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

                        // Lấy tất cả ins_id từ bảng hplc_instrument
                        $allInsIds = DB::table('hplc_instrument')->pluck('id', 'code');

                        // Gom dữ liệu Excel theo ins_id
                        $excelDataByIns = [];
                        foreach ($rows as $index => $row) {
                        if ($index < 7) continue; // bỏ dòng tiêu đề
                        if (empty($row['D'])) continue;

                        $code = $row['B'] ?? null;
                        if (!$code || !isset($allInsIds[$code])) continue;

                        $ins_id = $allInsIds[$code];
                        $excelDataByIns[$ins_id][] = [
                                'ins_id'      => $ins_id,
                                'column'      => $row['C'] ?? null,
                                'analyst'     => $row['D'] ?? null,
                                'sample_name' => $row['E'] ?? null,
                                'batch_no'    => $row['F'] ?? null,
                                'stage'       => $row['G'] ?? null,
                                'test'        => $row['H'] ?? null,
                                'notes'       => $row['I'] ?? null,
                                'remark'      => $row['J'] ?? null,
                                'start_time'  => $this->combineDateTime($row['K'] ?? null, $row['L'] ?? null),
                                'end_time'    => $this->combineDateTime($row['M'] ?? null, $row['N'] ?? null),
                                'created_at'  => now(),
                                'updated_at'  => now(),
                        ];
                        }

                        // Chèn dữ liệu Excel đầu tiên
                        $allDataToInsert = [];
                        foreach ($excelDataByIns as $ins_id => $datas) {
                        $allDataToInsert = array_merge($allDataToInsert, $datas);
                        }

                        if (!empty($allDataToInsert)) {
                        DB::table('hplc_status')->insert($allDataToInsert);
                        }

                        // Đảm bảo mỗi ins_id có 2 dòng
                        foreach ($allInsIds as $ins_id) {
                        $dataForIns = $excelDataByIns[$ins_id] ?? [];

                        // Nếu có ít hơn 2 dòng, thêm null để đủ 2
                        while (count($dataForIns) < 2) {
                                $dataForIns[] = [
                                'ins_id'      => $ins_id,
                                'column'      => null,
                                'analyst'     => null,
                                'sample_name' => null,
                                'batch_no'    => null,
                                'stage'       => null,
                                'test'        => null,
                                'notes'       => null,
                                'remark'      => null,
                                'start_time'  => Carbon::parse($request->date_upload)->format('Y-m-d'),
                                'end_time'    => null,
                                'created_at'  => now(),
                                'updated_at'  => now(),
                                ];
                        }

                        // Nếu đã có bản ghi từ Excel, chỉ chèn thêm các bản ghi null còn thiếu
                        $nullRows = array_slice($dataForIns, count($excelDataByIns[$ins_id] ?? []));
                        if (!empty($nullRows)) {
                                DB::table('hplc_status')->insert($nullRows);
                        }
                        }

                }

                if ($request->notification) {

                        DB::table('room_status_notification')->insert([
                        'notification' => $request->notification,
                        'group_code' => 0,
                        'durability' => $request->durability??now(),
                        'deparment_code' => "QC",
                        'created_by' => null,
                        'created_at' => now(),
                        ]);

                }

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
