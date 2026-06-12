<?php

namespace App\Http\Controllers\Pages\MaterData;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class BlisterMoldController extends Controller
{
    public function index()
    {
        $datas = DB::table('blister_mold')->orderBy('code', 'asc')->get();
        $blister_types = DB::table('blister_type')->where('active', true)->get();
        session()->put(['title' => 'DỮ LIỆU GỐC - KHUÔN MẪU']);
        $historyCounts = DB::table('blister_mold_history')->select('blister_mold_id', DB::raw('count(*) as total'))->groupBy('blister_mold_id')->get()->keyBy('blister_mold_id');
        return view('pages.materData.BlisterMold.list', [
            'datas' => $datas,
            'blister_types' => $blister_types
        , 'historyCounts' => $historyCounts]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required|max:50|unique:blister_mold,code',
            'amount' => 'nullable|integer|min:0',
            'blister_type_code' => 'required|integer',
        ], [
            'blister_type_code.required' => 'Vui lòng chọn Loại Máy Ép Vỉ',
            'code.required' => 'Vui lòng nhập Mã Khuôn Mẫu',
            'code.max' => 'Mã Khuôn Mẫu tối đa 50 ký tự',
            'code.unique' => 'Mã Khuôn Mẫu đã tồn tại.',
            'amount.integer' => 'Số lượng phải là một số nguyên.',
            'amount.min' => 'Số lượng không được nhỏ hơn 0.',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator, 'createErrors')->withInput();
        }

        DB::table('blister_mold')->insert([
            'code' => $request->code,
            'amount' => $request->amount,
            'blister_type_code' => $request->blister_type_code,
            'active' => true,
            'created_by' => session('user')['fullName'] ?? 'Admin',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        return redirect()->back()->with('success', 'Đã thêm thành công!');
    }

    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required|max:50|unique:blister_mold,code,' . $request->id,
            'amount' => 'nullable|integer|min:0',
            'blister_type_code' => 'required|integer',
        ], [
            'blister_type_code.required' => 'Vui lòng chọn Loại Máy Ép Vỉ',
            'code.required' => 'Vui lòng nhập Mã Khuôn Mẫu',
            'code.max' => 'Mã Khuôn Mẫu tối đa 50 ký tự',
            'code.unique' => 'Mã Khuôn Mẫu đã tồn tại.',
            'amount.integer' => 'Số lượng phải là một số nguyên.',
            'amount.min' => 'Số lượng không được nhỏ hơn 0.',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator, 'updateErrors')->withInput();
        }

        $this->logHistory($request->id);
        DB::table('blister_mold')->where('id', $request->id)->update([
            'code' => $request->code,
            'amount' => $request->amount,
            'blister_type_code' => $request->blister_type_code,
            'updated_at' => now(),
        ]);

        return redirect()->back()->with('success', 'Cập nhật thành công!');
    }

    public function deActive(Request $request)
    {
        $id = $request->id;
        $active = $request->active;

        $this->logHistory($id);
        DB::table('blister_mold')->where('id', $id)->update([
            'active' => !$active,
            'updated_at' => now(),
        ]);

        return redirect()->back()->with('success', 'Đã thay đổi trạng thái thành công!');
    }

    public function logHistory($id)
    {
        $current = DB::table('blister_mold')->where('id', $id)->first();
        if ($current) {
            $data = (array) $current;
            $data['blister_mold_id'] = $data['id'];
            unset($data['id']);
            DB::table('blister_mold_history')->insert($data);
        }
    }

    public function history(Request $request)
    {
        $histories = DB::table('blister_mold_history')
            ->where('blister_mold_id', $request->id)
            ->orderBy('id', 'desc')
            ->get();
            
        $current = DB::table('blister_mold')->where('id', $request->id)->first();

        return response()->json([
            'current' => $current,
            'history' => $histories
        ]);
    }

}