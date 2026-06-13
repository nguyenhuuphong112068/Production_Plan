<?php

namespace App\Http\Controllers\Pages\MaterData;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class DepartmentController extends Controller
{
    public function index()
    {
        $datas = DB::table('deparments')->orderBy('name', 'asc')->get();
        session()->put(['title' => 'DỮ LIỆU GỐC - PHÒNG BAN']);
        $historyCounts = DB::table('deparments_history')->select('deparment_id', DB::raw('count(*) as total'))->groupBy('deparment_id')->get()->keyBy('deparment_id');
        return view('pages.materData.Department.list', ['datas' => $datas, 'historyCounts' => $historyCounts]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'shortName' => 'required|unique:deparments,shortName',
            'name' => 'required|unique:deparments,name',
        ], [
            'name.required' => 'Vui lòng nhập Tên Phòng Ban',
            'name.unique' => 'Tên Phòng Ban đã tồn tại.',
            'shortName.required' => 'Vui lòng nhập Tên Viết Tắt',
            'shortName.unique' => 'Tên Viết Tắt đã tồn tại.',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator, 'createErrors')->withInput();
        }

        DB::table('deparments')->insert([
            'shortName' => $request->shortName,
            'name' => $request->name,
            'active' => true,
            'prepareBy' => session('user')['fullName'] ?? 'Admin',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        return redirect()->back()->with('success', 'Đã thêm thành công!');
    }

    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'shortName' => 'required|unique:deparments,shortName,' . $request->id,
            'name' => 'required|unique:deparments,name,' . $request->id,
        ], [
            'name.required' => 'Vui lòng nhập Tên Phòng Ban',
            'name.unique' => 'Tên Phòng Ban đã tồn tại.',
            'shortName.required' => 'Vui lòng nhập Tên Viết Tắt',
            'shortName.unique' => 'Tên Viết Tắt đã tồn tại.',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator, 'updateErrors')->withInput();
        }

        $this->logHistory($request->id);
        DB::table('deparments')->where('id', $request->id)->update([
            'prepareBy' => session('user')['fullName'] ?? 'Admin',
            'shortName' => $request->shortName,
            'name' => $request->name,
            'updated_at' => now(),
        ]);

        return redirect()->back()->with('success', 'Cập nhật thành công!');
    }

    public function deActive(Request $request)
    {
        $id = $request->id;
        $active = $request->active;

        $this->logHistory($id);
        DB::table('deparments')->where('id', $id)->update([
            'prepareBy' => session('user')['fullName'] ?? 'Admin',
            'active' => !$active,
            'updated_at' => now(),
        ]);

        return redirect()->back()->with('success', 'Đã thay đổi trạng thái thành công!');
    }

    public function logHistory($id)
    {
        $current = DB::table('deparments')->where('id', $id)->first();
        if ($current) {
            $data = (array) $current;
            $data['deparment_id'] = $data['id'];
            unset($data['id']);
            DB::table('deparments_history')->insert($data);
        }
    }

    public function history(Request $request)
    {
        $histories = DB::table('deparments_history')
            ->where('deparment_id', $request->id)
            ->orderBy('id', 'desc')
            ->get();
            
        $current = DB::table('deparments')->where('id', $request->id)->first();

        return response()->json([
            'current' => $current,
            'history' => $histories
        ]);
    }

}