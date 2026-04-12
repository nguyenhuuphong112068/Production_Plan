<?php

namespace App\Http\Controllers\Pages\MaterData;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Schema;

class OffDaysController extends Controller
{
    public function index()
    {
        $datas = DB::table('off_days')->orderBy('off_date', 'desc')->get();

        $flags = collect();
        if (Schema::hasTable('off_days_flags')) {
            $flags = DB::table('off_days_flags')->orderBy('id', 'asc')->get();
        }

        session()->put(['title' => 'CẬP NHẬT NGÀY NGHỈ']);

        return view('pages.OffDays.list', [
            'datas' => $datas,
            'flags' => $flags
        ]);
    }

    public function store(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'off_date' => 'required|date',
            'reason'   => 'nullable|string|max:255',
        ], [
            'off_date.required' => 'Vui lòng chọn ngày nghỉ.',
            'off_date.date'     => 'Ngày nghỉ không đúng định dạng.',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator, 'offDaysErrors')->withInput();
        }

        if ($request->id) {
            DB::table('off_days')->where('id', $request->id)->update([
                'off_date'   => $request->off_date,
                'reason'     => $request->reason,
                'updated_at' => now(),
            ]);
            $msg = 'Cập nhật thành công!';
        } else {
            DB::table('off_days')->insert([
                'off_date'   => $request->off_date,
                'reason'     => $request->reason,
                'created_at' => now(),
            ]);
            $msg = 'Đã thêm ngày nghỉ thành công!';
        }

        return redirect()->back()->with('success', $msg);
    }

    public function storeAjax(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'off_date' => 'required|date',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()]);
        }

        if ($request->id) {
            DB::table('off_days')->where('id', $request->id)->update([
                'off_date'   => $request->off_date,
                'reason'     => $request->reason,
                'updated_at' => now(),
            ]);
            $offDayId = $request->id;
            $msg = 'Cập nhật thành công!';
        } else {
            $offDayId = DB::table('off_days')->insertGetId([
                'off_date'   => $request->off_date,
                'reason'     => $request->reason,
                'created_at' => now(),
            ]);
            $msg = 'Đã thêm thành công!';
        }

        return response()->json([
            'success' => true,
            'message' => $msg,
            'id' => $offDayId,
            'off_date' => $request->off_date,
            'reason' => $request->reason
        ]);
    }

    public function deleteAjax(Request $request)
    {
        if ($request->id) {
            DB::table('off_days')->where('id', $request->id)->delete();
            return response()->json(['success' => true, 'message' => 'Đã xóa sự kiện thành công!']);
        }
        return response()->json(['success' => false, 'message' => 'Không tìm thấy ID']);
    }

    public function storeFlagAjax(Request $request)
    {
        Log::info($request->all());

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()]);
        }

        $color = $request->color ? $request->color : 'bg-info';

        if ($request->id) {
            DB::table('off_days_flags')->where('id', $request->id)->update([
                'name' => $request->name,
                'color' => $color,
                'updated_at' => now(),
            ]);
            $flagId = $request->id;
        } else {
            $flagId = DB::table('off_days_flags')->insertGetId([
                'name' => $request->name,
                'color' => $color,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return response()->json([
            'success' => true,
            'id' => $flagId,
            'name' => $request->name,
            'color' => $color
        ]);
    }

    public function deleteFlagAjax(Request $request)
    {
        if ($request->id) {
            DB::table('off_days_flags')->where('id', $request->id)->delete();
            return response()->json(['success' => true, 'message' => 'Đã xóa cờ thành công!']);
        }
        return response()->json(['success' => false, 'message' => 'Không tìm thấy ID']);
    }
}
