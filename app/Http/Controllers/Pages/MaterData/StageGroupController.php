<?php

namespace App\Http\Controllers\Pages\MaterData;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class StageGroupController extends Controller
{
    public function index()
    {
        $datas = DB::table('stage_groups')->orderBy('name', 'asc')->get();
        session()->put(['title' => 'DỮ LIỆU GỐC - TỔ QUẢN LÝ']);
        $historyCounts = DB::table('stage_groups_history')->select('stage_group_id', DB::raw('count(*) as total'))->groupBy('stage_group_id')->get()->keyBy('stage_group_id');
        return view('pages.materData.StageGroup.list', ['datas' => $datas, 'historyCounts' => $historyCounts]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required|unique:stage_groups,code',
            'name' => 'required|unique:stage_groups,name',
        ], [
            'name.required' => 'Vui lòng nhập Tên Tổ',
            'name.unique' => 'Tên Tổ đã tồn tại.',
            'code.required' => 'Vui lòng nhập Mã Tổ',
            'code.unique' => 'Mã Tổ đã tồn tại.',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator, 'createErrors')->withInput();
        }

        DB::table('stage_groups')->insert([
            'code' => $request->code,
            'name' => $request->name,
            'create_by' => session('user')['fullName'] ?? 'Admin',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        return redirect()->back()->with('success', 'Đã thêm thành công!');
    }

    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required|unique:stage_groups,code,' . $request->id,
            'name' => 'required|unique:stage_groups,name,' . $request->id,
        ], [
            'name.required' => 'Vui lòng nhập Tên Tổ',
            'name.unique' => 'Tên Tổ đã tồn tại.',
            'code.required' => 'Vui lòng nhập Mã Tổ',
            'code.unique' => 'Mã Tổ đã tồn tại.',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator, 'updateErrors')->withInput();
        }

        $this->logHistory($request->id);
        DB::table('stage_groups')->where('id', $request->id)->update([
            'create_by' => session('user')['fullName'] ?? 'Admin',
            'code' => $request->code,
            'name' => $request->name,
            'updated_at' => now(),
        ]);

        return redirect()->back()->with('success', 'Cập nhật thành công!');
    }

    public function logHistory($id)
    {
        $current = DB::table('stage_groups')->where('id', $id)->first();
        if ($current) {
            $data = (array) $current;
            $data['stage_group_id'] = $data['id'];
            unset($data['id']);
            DB::table('stage_groups_history')->insert($data);
        }
    }

    public function history(Request $request)
    {
        $histories = DB::table('stage_groups_history')
            ->where('stage_group_id', $request->id)
            ->orderBy('id', 'desc')
            ->get();
            
        $current = DB::table('stage_groups')->where('id', $request->id)->first();

        return response()->json([
            'current' => $current,
            'history' => $histories
        ]);
    }

}