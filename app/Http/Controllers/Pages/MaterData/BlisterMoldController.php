<?php

namespace App\Http\Controllers\Pages\MaterData;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;

class BlisterMoldController extends Controller
{
    /** Khuôn mẫu tách theo phân xưởng đang chọn */
    protected function department()
    {
        return session('user')['production_code'];
    }

    public function index()
    {
        $datas = DB::table('blister_mold')->where('deparment_code', $this->department())->orderBy('code', 'asc')->get();
        $blister_types = DB::table('blister_type')->where('active', true)->get();
        session()->put(['title' => 'DỮ LIỆU GỐC - KHUÔN MẪU']);
        $historyCounts = DB::table('blister_mold_history')->select('blister_mold_id', DB::raw('count(*) as total'))->groupBy('blister_mold_id')->get()->keyBy('blister_mold_id');
        return view('pages.materData.BlisterMold.list', [
            'datas' => $datas,
            'blister_types' => $blister_types,
            'historyCounts' => $historyCounts
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'code' => ['required', 'max:50', Rule::unique('blister_mold', 'code')->where('deparment_code', $this->department())],
            'amount' => 'nullable|integer|min:0',
            'stage_code' => 'required|in:5,7',
            'blister_type_code' => 'required|array',
            'blister_type_code.*' => 'integer',
        ], [
            'stage_code.required' => 'Vui lòng chọn Công Đoạn',
            'stage_code.in' => 'Công Đoạn chỉ được là Đóng Gói hoặc Định Hình',
            'blister_type_code.required' => 'Vui lòng chọn Loại Máy lắp được khuôn',
            'code.required' => 'Vui lòng nhập Mã Khuôn Mẫu',
            'code.max' => 'Mã Khuôn Mẫu tối đa 50 ký tự',
            'code.unique' => 'Mã Khuôn Mẫu đã tồn tại trong phân xưởng.',
            'amount.integer' => 'Số lượng phải là một số nguyên.',
            'amount.min' => 'Số lượng không được nhỏ hơn 0.',
        ]);

        $this->checkMachineTypes($validator, $request);
        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator, 'createErrors')->withInput();
        }

        DB::table('blister_mold')->insert([
            'code' => $request->code,
            'deparment_code' => $this->department(),
            'amount' => $request->amount,
            'stage_code' => (int) $request->stage_code,
            // Loại máy lắp được khuôn (cùng công đoạn với khuôn), dạng JSON ["1","3"]
            'blister_type_code' => json_encode(array_values(array_map('strval', $request->blister_type_code))),
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
            'code' => ['required', 'max:50', Rule::unique('blister_mold', 'code')->where('deparment_code', $this->department())->ignore($request->id)],
            'amount' => 'nullable|integer|min:0',
            'stage_code' => 'required|in:5,7',
            'blister_type_code' => 'required|array',
            'blister_type_code.*' => 'integer',
        ], [
            'stage_code.required' => 'Vui lòng chọn Công Đoạn',
            'stage_code.in' => 'Công Đoạn chỉ được là Đóng Gói hoặc Định Hình',
            'blister_type_code.required' => 'Vui lòng chọn Loại Máy lắp được khuôn',
            'code.required' => 'Vui lòng nhập Mã Khuôn Mẫu',
            'code.max' => 'Mã Khuôn Mẫu tối đa 50 ký tự',
            'code.unique' => 'Mã Khuôn Mẫu đã tồn tại trong phân xưởng.',
            'amount.integer' => 'Số lượng phải là một số nguyên.',
            'amount.min' => 'Số lượng không được nhỏ hơn 0.',
        ]);

        $this->checkMachineTypes($validator, $request);
        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator, 'updateErrors')->withInput();
        }

        if (! $this->ownMold($request->id)) {
            return redirect()->back()->with('error', 'Khuôn không thuộc phân xưởng đang chọn.');
        }

        $this->logHistory($request->id);
        DB::table('blister_mold')->where('id', $request->id)->update([
            'code' => $request->code,
            'amount' => $request->amount,
            'stage_code' => (int) $request->stage_code,
            // Loại máy lắp được khuôn (cùng công đoạn với khuôn), dạng JSON ["1","3"]
            'blister_type_code' => json_encode(array_values(array_map('strval', $request->blister_type_code))),
            'updated_at' => now(),
        ]);

        return redirect()->back()->with('success', 'Cập nhật thành công!');
    }

    public function deActive(Request $request)
    {
        $id = $request->id;
        $active = $request->active;

        if (! $this->ownMold($id)) {
            return redirect()->back()->with('error', 'Khuôn không thuộc phân xưởng đang chọn.');
        }

        $this->logHistory($id);
        DB::table('blister_mold')->where('id', $id)->update([
            'active' => !$active,
            'updated_at' => now(),
        ]);

        return redirect()->back()->with('success', 'Đã thay đổi trạng thái thành công!');
    }

    /** Loại máy chọn cho khuôn phải cùng công đoạn với khuôn (ĐH ↔ máy Định Hình, ĐG ↔ máy ép vỉ). */
    protected function checkMachineTypes($validator, Request $request)
    {
        $validator->after(function ($v) use ($request) {
            if ($v->errors()->isNotEmpty()) {
                return;
            }
            $codes = array_unique(array_map('intval', (array) $request->blister_type_code));
            $valid = DB::table('blister_type')
                ->whereIn('code', $codes ?: [0])
                ->where('stage_code', (int) $request->stage_code)
                ->distinct()
                ->count('code');
            if ($valid !== count($codes)) {
                $v->errors()->add('blister_type_code', 'Loại máy phải cùng công đoạn với khuôn (khai ở Dữ Liệu Gốc › Nhóm Máy).');
            }
        });
    }

    protected function ownMold($id)
    {
        return DB::table('blister_mold')->where('id', $id)->where('deparment_code', $this->department())->exists();
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

        $blister_types = DB::table('blister_type')->pluck('name', 'code')->toArray();
        
        $translate = function($codeStr) use ($blister_types) {
            $codes = json_decode($codeStr, true);
            if (!is_array($codes)) {
                $codes = [$codeStr];
            }
            $names = [];
            foreach($codes as $c) {
                if(isset($blister_types[$c])) $names[] = $blister_types[$c];
            }
            return implode(', ', $names) ?: $codeStr;
        };

        if ($current) {
            $current->blister_type_code = $translate($current->blister_type_code);
        }
        foreach($histories as $history) {
            $history->blister_type_code = $translate($history->blister_type_code);
        }

        return response()->json([
            'current' => $current,
            'history' => $histories
        ]);
    }
}
