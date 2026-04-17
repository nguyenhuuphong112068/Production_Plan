<?php

namespace App\Http\Controllers\Pages\Assignment;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PersonnelController extends Controller
{
    public function index($department = null)
    {
        // 1. Lấy mã bộ phận: Ưu tiên URL -> Session
        $departmentCode = $department ?? session('user')['department'];

        // 2. Truy vấn danh sách nhân viên theo bộ phận
        $datas = DB::table('personnel')
            ->where('deparment_code', $departmentCode)
            ->orderBy('code', 'asc')
            ->get();

        session()->put(['title' => 'NHÂN VIÊN - BỘ PHẬN: ' . $departmentCode]);

        // 3. Lấy danh sách bộ phận và tổ để hỗ trợ nhập liệu
        $departments = DB::table('deparments')->where('active', true)->get();
        $groups = DB::table('stage_groups')->get();

        return view('pages.assignment.personnel.list', [
            'datas' => $datas,
            'departments' => $departments,
            'groups' => $groups,
            'currentDepartment' => $departmentCode
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required|unique:personnel,code',
            'name' => 'required',
        ], [
            'code.required' => 'Vui lòng nhập Mã nhân viên',
            'code.unique' => 'Mã nhân viên đã tồn tại.',
            'name.required' => 'Vui lòng nhập Tên nhân viên',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator, 'createErrors')->withInput();
        }

        DB::table('personnel')->insert([
            'code' => $request->code,
            'name' => $request->name,
            'deparment_code' => $request->deparment_code,
            'group_name' => $request->group_name,
            'group_code' => $request->group_code,
            'active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->back()->with('success', 'Đã thêm thành công!');
    }

    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required|unique:personnel,code,' . $request->id,
            'name' => 'required',
        ], [
            'code.required' => 'Vui lòng nhập Mã nhân viên',
            'code.unique' => 'Mã nhân viên đã tồn tại.',
            'name.required' => 'Vui lòng nhập Tên nhân viên',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator, 'updateErrors')->withInput();
        }

        DB::table('personnel')->where('id', $request->id)->update([
            'code' => $request->code,
            'name' => $request->name,
            'deparment_code' => $request->deparment_code,
            'group_name' => $request->group_name,
            'group_code' => $request->group_code,
            'updated_at' => now(),
        ]);

        return redirect()->back()->with('success', 'Cập nhật thành công!');
    }

    public function deActive(string|int $id)
    {
        $current = DB::table('personnel')->where('id', $id)->first();
        DB::table('personnel')->where('id', $id)->update([
            'active' => !$current->active,
            'updated_at' => now(),
        ]);
        return redirect()->back()->with('success', 'Đã thay đổi trạng thái thành công!');
    }
}
