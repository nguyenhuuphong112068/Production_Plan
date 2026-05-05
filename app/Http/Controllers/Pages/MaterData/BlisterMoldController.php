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
        session()->put(['title' => 'DỮ LIỆU GỐC - KHUÔN MẪU']);
        return view('pages.materData.BlisterMold.list', ['datas' => $datas]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required|max:15|unique:blister_mold,code',
        ], [
            'code.required' => 'Vui lòng nhập Mã Khuôn Mẫu',
            'code.max' => 'Mã Khuôn Mẫu tối đa 15 ký tự',
            'code.unique' => 'Mã Khuôn Mẫu đã tồn tại.',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator, 'createErrors')->withInput();
        }

        DB::table('blister_mold')->insert([
            'code' => $request->code,
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
            'code' => 'required|max:15|unique:blister_mold,code,' . $request->id,
        ], [
            'code.required' => 'Vui lòng nhập Mã Khuôn Mẫu',
            'code.max' => 'Mã Khuôn Mẫu tối đa 15 ký tự',
            'code.unique' => 'Mã Khuôn Mẫu đã tồn tại.',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator, 'updateErrors')->withInput();
        }

        DB::table('blister_mold')->where('id', $request->id)->update([
            'code' => $request->code,
            'updated_at' => now(),
        ]);

        return redirect()->back()->with('success', 'Cập nhật thành công!');
    }

    public function deActive(Request $request)
    {
        $id = $request->id;
        $active = $request->active;

        DB::table('blister_mold')->where('id', $id)->update([
            'active' => !$active,
            'updated_at' => now(),
        ]);

        return redirect()->back()->with('success', 'Đã thay đổi trạng thái thành công!');
    }
}
