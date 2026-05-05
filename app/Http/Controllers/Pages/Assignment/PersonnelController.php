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
        $datas = DB::table('employees')
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

    public function sync(Request $request)
    {
        $departmentCode = $request->department ?? session('user')['production_code'];
        
        // Mapping department codes to IDs for the external API
        $depMapping = [
            'PXV1' => 15,
            'PXV2' => 31,
            'PXVH' => 30,
            'PXDN' => 30,
            'EN' => 3,
            'PXN' => 6,
            'PXTN' => 6
        ];
        
        $depId = $depMapping[$departmentCode] ?? null;
        if (!$depId) {
             return redirect()->back()->with('error', "Bộ phận {$departmentCode} không hỗ trợ đồng bộ tự động.");
        }

        $month = now()->month;
        $year = now()->year;
        
        $url = "http://s-webdev:5070/api/shifts/by-department?month={$month}&year={$year}&department={$depId}";
        
        try {
            $data = file_get_contents($url);
            $employees = json_decode($data);
            
            if (empty($employees)) {
                 return redirect()->back()->with('info', 'Không tìm thấy dữ liệu nhân sự trên hệ thống nguồn.');
            }

            $count = 0;
            foreach ($employees as $emp) {
                // Kiểm tra theo mã nhân viên
                $exists = DB::table('employees')->where('code', $emp->employeeId)->exists();
                if (!$exists) {
                    DB::table('employees')->insert([
                        'code' => $emp->employeeId,
                        'name' => $emp->employeeName,
                        'deparment_code' => $departmentCode,
                        'active' => 1,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                    $count++;
                }
            }
            
            return redirect()->back()->with('success', "Đã đồng bộ thành công {$count} nhân sự mới.");
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Lỗi đồng bộ: ' . $e->getMessage());
        }
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required|unique:employees,code',
            'name' => 'required',
        ], [
            'code.required' => 'Vui lòng nhập Mã nhân viên',
            'code.unique' => 'Mã nhân viên đã tồn tại.',
            'name.required' => 'Vui lòng nhập Tên nhân viên',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator, 'createErrors')->withInput();
        }

        DB::table('employees')->insert([
            'code' => $request->code,
            'name' => $request->name,
            'deparment_code' => $request->deparment_code,
            'group_name' => $request->group_name,
            'group_code' => $request->group_code,
            'level' => $request->level,
            'active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->back()->with('success', 'Đã thêm thành công!');
    }

    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required|unique:employees,code,' . $request->id,
            'name' => 'required',
        ], [
            'code.required' => 'Vui lòng nhập Mã nhân viên',
            'code.unique' => 'Mã nhân viên đã tồn tại.',
            'name.required' => 'Vui lòng nhập Tên nhân viên',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator, 'updateErrors')->withInput();
        }

        DB::table('employees')->where('id', $request->id)->update([
            'code' => $request->code,
            'name' => $request->name,
            'deparment_code' => $request->deparment_code,
            'group_name' => $request->group_name,
            'group_code' => $request->group_code,
            'level' => $request->level,
            'updated_at' => now(),
        ]);

        return redirect()->back()->with('success', 'Cập nhật thành công!');
    }

    public function deActive(string|int $id)
    {
        $current = DB::table('employees')->where('id', $id)->first();
        DB::table('employees')->where('id', $id)->update([
            'active' => !$current->active,
            'updated_at' => now(),
        ]);
        return redirect()->back()->with('success', 'Đã thay đổi trạng thái thành công!');
    }
}
