<?php

namespace App\Http\Controllers\Pages\MaterData;

use App\Http\Controllers\Controller;
use App\Models\RoomLink;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class RoomLinkController extends Controller
{
    public function index()
    {
        $department_code = session('user')['production_code'];

        $datas = RoomLink::select('room_links.*')
            ->join('room as src', 'src.id', '=', 'room_links.source_room_id')
            ->where('src.deparment_code', $department_code)
            ->with(['sourceRoom', 'targetRoom'])
            ->get();

        $sourceRooms = DB::table('room')
            ->where('deparment_code', $department_code)
            ->where('stage_code', 3) // Pha Chế
            ->where('active', 1)
            ->get();

        $targetRooms = DB::table('room')
            ->where('deparment_code', $department_code)
            ->where('stage_code', 4) // Trộn Hoàn Tất
            ->where('active', 1)
            ->get();

        session()->put(['title' => 'DỮ LIỆU GỐC - LIÊN KẾT PHÒNG']);

        return view('pages.materData.room_links.list', [
            'datas' => $datas,
            'sourceRooms' => $sourceRooms,
            'targetRooms' => $targetRooms
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'source_room_id' => 'required|exists:room,id',
            'target_room_id' => 'required|exists:room,id',
        ], [
            'source_room_id.required' => 'Vui lòng chọn phòng nguồn (Pha chế)',
            'target_room_id.required' => 'Vui lòng chọn phòng đích bắt buộc (Trộn hoàn tất)',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator, 'createErrors')->withInput();
        }

        // Check if link already exists
        $exists = RoomLink::where('source_room_id', $request->source_room_id)
            ->where('target_room_id', $request->target_room_id)
            ->exists();

        if ($exists) {
            return redirect()->back()->with('error', 'Liên kết này đã tồn tại!');
        }

        RoomLink::create([
            'source_room_id' => $request->source_room_id,
            'target_room_id' => $request->target_room_id,
            'active' => true,
        ]);

        return redirect()->back()->with('success', 'Đã thêm thành công!');
    }

    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:room_links,id',
            'source_room_id' => 'required|exists:room,id',
            'target_room_id' => 'required|exists:room,id',
        ], [
            'source_room_id.required' => 'Vui lòng chọn phòng nguồn',
            'target_room_id.required' => 'Vui lòng chọn phòng đích',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator, 'updateErrors')->withInput();
        }

        RoomLink::where('id', $request->id)->update([
            'source_room_id' => $request->source_room_id,
            'target_room_id' => $request->target_room_id,
        ]);

        return redirect()->back()->with('success', 'Cập nhật thành công!');
    }

    public function deActive(Request $request)
    {
        $link = RoomLink::find($request->id);
        if ($link) {
            $link->active = !$request->active;
            $link->save();
            return redirect()->back()->with('success', 'Đổi trạng thái thành công!');
        }
        return redirect()->back()->with('error', 'Không tìm thấy liên kết!');
    }
}
