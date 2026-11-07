<?php

namespace App\Http\Controllers\Pages\MaterData;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * Loại máy / nhóm máy dùng để khớp khuôn với phòng.
 *  - stage_code 7: máy ép vỉ (Đóng Gói) – khuôn ép vỉ.
 *  - stage_code 5: máy Định Hình (dập viên / đóng nang) – khuôn Định Hình.
 * Mã nhóm máy (code) là khoá ghi vào room.blister_type_code và blister_mold.blister_type_code;
 * nhiều loại máy cùng công đoạn có thể chung một mã (cùng nhóm), nhưng một mã không
 * được dùng cho cả 2 công đoạn.
 */
class BlisterTypeController extends Controller
{
    public const STAGES = [7 => 'Đóng Gói', 5 => 'Định Hình'];

    public function index()
    {
        $datas = DB::table('blister_type')->orderBy('stage_code', 'desc')->orderBy('code')->orderBy('name')->get();
        session()->put(['title' => 'DỮ LIỆU GỐC - NHÓM MÁY']);
        $historyCounts = DB::table('blister_type_history')->select('blister_type_id', DB::raw('count(*) as total'))->groupBy('blister_type_id')->get()->keyBy('blister_type_id');

        // Phòng đang gán từng mã nhóm máy (mọi phân xưởng)
        $roomsByCode = DB::table('room')
            ->whereNotNull('blister_type_code')
            ->where('active', 1)
            ->orderBy('deparment_code')->orderBy('code')
            ->get(['code', 'deparment_code', 'blister_type_code'])
            ->groupBy('blister_type_code');

        return view('pages.materData.BlisterType.list', [
            'datas' => $datas,
            'historyCounts' => $historyCounts,
            'roomsByCode' => $roomsByCode,
            'stages' => self::STAGES,
        ]);
    }

    protected function validator(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|max:255|unique:blister_type,name' . ($request->id ? ',' . (int) $request->id : ''),
            'code' => 'required|integer|min:1|max:127',
            'stage_code' => 'required|in:5,7',
        ], [
            'name.required' => 'Vui Lòng Nhập Loại Máy',
            'name.unique' => 'Loại Máy đã tồn tại.',
            'code.required' => 'Vui lòng nhập Mã Nhóm Máy.',
            'code.integer' => 'Mã Nhóm Máy phải là số nguyên.',
            'code.min' => 'Mã Nhóm Máy từ 1 đến 127.',
            'code.max' => 'Mã Nhóm Máy từ 1 đến 127.',
            'stage_code.required' => 'Vui lòng chọn Công Đoạn.',
            'stage_code.in' => 'Công Đoạn chỉ được là Đóng Gói hoặc Định Hình.',
        ]);

        $validator->after(function ($v) use ($request) {
            if ($v->errors()->isNotEmpty()) {
                return;
            }
            // Một mã nhóm máy chỉ thuộc một công đoạn: phòng/khuôn lưu mã, nếu dùng chung
            // mã giữa ĐH và ĐG thì khuôn ĐH sẽ khớp nhầm máy ép vỉ và ngược lại.
            $other = DB::table('blister_type')
                ->where('code', (int) $request->code)
                ->where('stage_code', '!=', (int) $request->stage_code)
                ->when($request->id, fn ($q) => $q->where('id', '!=', $request->id))
                ->value('name');
            if ($other) {
                $v->errors()->add('code', "Mã nhóm máy {$request->code} đang dùng cho loại máy \"{$other}\" của công đoạn khác.");
            }

            // Đổi công đoạn / mã khi phòng hoặc khuôn đang dùng mã cũ sẽ làm khuôn khớp sai máy
            if ($request->id) {
                $current = DB::table('blister_type')->where('id', $request->id)->first();
                if ($current && ((int) $current->code !== (int) $request->code || (int) $current->stage_code !== (int) $request->stage_code)) {
                    $sameCodeLeft = DB::table('blister_type')->where('code', $current->code)->where('id', '!=', $current->id)->exists();
                    $roomsUsing = DB::table('room')->where('blister_type_code', $current->code)->pluck('code')->all();
                    if (! $sameCodeLeft && $roomsUsing) {
                        $v->errors()->add('code', 'Không đổi được Mã/Công Đoạn: phòng ' . implode(', ', $roomsUsing)
                            . ' đang gán mã ' . $current->code . '. Đổi loại máy ở Dữ Liệu Gốc › Phòng Sản Xuất trước.');
                    }
                }
            }
        });

        return $validator;
    }

    public function store(Request $request)
    {
        $validator = $this->validator($request);
        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator, 'createErrors')->withInput();
        }

        DB::table('blister_type')->insert([
            'name' => $request->name,
            'code' => (int) $request->code,
            'stage_code' => (int) $request->stage_code,
            'active' => true,
            'created_by' => session('user')['fullName'] ?? 'Admin',
            'created_at' => now(),
        ]);

        return redirect()->back()->with('success', 'Đã thêm thành công!');
    }

    public function update(Request $request)
    {
        $validator = $this->validator($request);
        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator, 'updateErrors')->withInput();
        }

        $this->logHistory($request->id);
        DB::table('blister_type')->where('id', $request->id)->update([
            'name' => $request->name,
            'code' => (int) $request->code,
            'stage_code' => (int) $request->stage_code,
            'created_by' => session('user')['fullName'] ?? 'Admin',
            'updated_at' => now(),
        ]);

        return redirect()->back()->with('success', 'Cập nhật thành công!');
    }

    public function deActive(Request $request)
    {
        $blister_type = DB::table('blister_type')->where('id', $request->id)->first();
        if ($blister_type) {
            $this->logHistory($request->id);
            DB::table('blister_type')->where('id', $request->id)->update([
                'created_by' => session('user')['fullName'] ?? 'Admin',
                'active' => ! $blister_type->active,
                'updated_at' => now(),
            ]);
        }

        return redirect()->back()->with('success', 'Cập nhật trạng thái thành công!');
    }

    public function logHistory($id)
    {
        $current = DB::table('blister_type')->where('id', $id)->first();
        if ($current) {
            $data = (array) $current;
            $data['blister_type_id'] = $data['id'];
            unset($data['id']);
            DB::table('blister_type_history')->insert($data);
        }
    }

    public function history(Request $request)
    {
        $histories = DB::table('blister_type_history')
            ->where('blister_type_id', $request->id)
            ->orderBy('id', 'desc')
            ->get();

        $current = DB::table('blister_type')->where('id', $request->id)->first();

        return response()->json([
            'current' => $current,
            'history' => $histories,
        ]);
    }
}
