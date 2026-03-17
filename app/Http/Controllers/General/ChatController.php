<?php

namespace App\Http\Controllers\General;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ChatController extends Controller
{
    /**
     * Lấy danh sách phòng chat của người dùng hiện tại
     */
    public function getGroups()
    {
        $userId = session('user')['userId'];

        $groups = DB::table('chat_groups as cg')
            ->join('chat_group_members as cgm', 'cg.id', '=', 'cgm.group_id')
            ->where('cgm.user_id', $userId)
            ->leftJoin('chat_messages as last_m', function($join) {
                $join->on('cg.id', '=', 'last_m.group_id')
                     ->whereRaw('last_m.id = (SELECT id FROM chat_messages WHERE group_id = cg.id ORDER BY created_at DESC LIMIT 1)');
            })
            ->select('cg.*', 'cgm.last_read_at', 'last_m.created_at as last_m_time')
            ->orderByRaw('COALESCE(last_m.created_at, cg.created_at) DESC')
            ->get();

        foreach ($groups as $group) {
            // Nếu là chat 1-1 (type = 0), lấy tên người kia làm tên phòng
            if ($group->type == 0) {
                $otherUser = DB::table('chat_group_members as cgm')
                    ->join('user_management as u', 'cgm.user_id', '=', 'u.id')
                    ->where('cgm.group_id', $group->id)
                    ->where('cgm.user_id', '!=', $userId)
                    ->select('u.fullName')
                    ->first();
                $group->display_name = $otherUser ? $otherUser->fullName : 'Unknown';
            } else {
                $group->display_name = $group->name;
            }

            // Lấy tin nhắn cuối cùng
            $lastMsg = DB::table('chat_messages')
                ->where('group_id', $group->id)
                ->orderBy('created_at', 'desc')
                ->first();
            $group->last_message = $lastMsg ? $lastMsg->message : '';
            $group->last_time = $lastMsg ? $lastMsg->created_at : $group->created_at;
            $group->last_sender_id = $lastMsg ? $lastMsg->sender_id : null;

            // Tính số tin nhắn chưa đọc
            $query = DB::table('chat_messages')
                ->where('group_id', $group->id)
                ->where('sender_id', '!=', $userId);
            
            if ($group->last_read_at) {
                $query->where('created_at', '>', $group->last_read_at);
            }
            
            $group->unread_count = $query->count();

            // Tính trạng thái online (nếu là chat 1-1)
            if ($group->type == 0) {
                $otherMember = DB::table('chat_group_members as cgm')
                    ->join('user_management as u', 'cgm.user_id', '=', 'u.id')
                    ->where('cgm.group_id', $group->id)
                    ->where('cgm.user_id', '!=', $userId)
                    ->select('u.last_activity')
                    ->first();
                
                $fiveMinsAgo = now()->subMinutes(5);
                $group->is_online = ($otherMember && $otherMember->last_activity && $otherMember->last_activity > $fiveMinsAgo);
            }
        }

        return response()->json($groups);
    }

    /**
     * Lấy danh sách tin nhắn trong một phòng
     */
    public function getMessages(Request $request, $groupId)
    {
        $userId = session('user')['userId'];
        $beforeId = $request->query('before_id');

        $query = DB::table('chat_messages as cm')
            ->join('user_management as u', 'cm.sender_id', '=', 'u.id')
            ->where('cm.group_id', $groupId)
            ->select('cm.*', 'u.fullName as sender_name');

        if ($beforeId) {
            $query->where('cm.id', '<', $beforeId);
        }

        $messages = $query->orderBy('cm.created_at', 'desc')
            ->limit(20)
            ->get()
            ->reverse()
            ->values();

        // Lấy thời gian đọc cuối cùng của các thành viên khác để xác định trạng thái "Đã xem"
        $othersLastRead = DB::table('chat_group_members')
            ->where('group_id', $groupId)
            ->where('user_id', '!=', $userId)
            ->pluck('last_read_at');

        return response()->json([
            'messages' => $messages,
            'others_last_read' => $othersLastRead
        ]);
    }

    /**
     * Gửi tin nhắn mới
     */
    public function sendMessage(Request $request)
    {
        $userId = session('user')['userId'];
        $groupId = $request->group_id;
        $message = $request->message;
        
        $filePath = null;
        $fileName = null;
        $fileType = null;

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $fileName = $file->getClientOriginalName();
            $fileType = $file->getClientMimeType();
            
            // LOGIC NÉN ẢNH TỰ ĐỘNG
            if (Str::startsWith($fileType, 'image/') && extension_loaded('gd')) {
                $uniqueName = Str::random(40) . '.jpg'; // Luôn lưu dạng jpg để nén tốt nhất
                $tempPath = storage_path('app/temp_' . $uniqueName);
                
                // Tạo ảnh từ file nguồn
                $image = null;
                if ($fileType == 'image/jpeg' || $fileType == 'image/jpg') $image = imagecreatefromjpeg($file->getRealPath());
                elseif ($fileType == 'image/png') $image = imagecreatefrompng($file->getRealPath());
                elseif ($fileType == 'image/gif') $image = imagecreatefromgif($file->getRealPath());
                elseif ($fileType == 'image/webp') $image = imagecreatefromwebp($file->getRealPath());

                if ($image) {
                    // Tự động xoay ảnh nếu có EXIF (nếu cần, bỏ qua cho nhẹ)
                    // Nén và lưu với chất lượng 70%
                    imagejpeg($image, $tempPath, 70);
                    imagedestroy($image);
                    
                    $filePath = 'chat_files/' . $uniqueName;
                    Storage::disk('public')->put($filePath, file_get_contents($tempPath));
                    unlink($tempPath); // Xóa file tạm
                    $fileType = 'image/jpeg';
                    $fileName = Str::slug(pathinfo($fileName, PATHINFO_FILENAME)) . '.jpg';
                } else {
                    $filePath = $file->store('chat_files', 'public');
                }
            } else {
                $filePath = $file->store('chat_files', 'public');
            }
        }

        $messageId = DB::table('chat_messages')->insertGetId([
            'group_id' => $groupId,
            'sender_id' => $userId,
            'message' => $message,
            'file_path' => $filePath,
            'file_name' => $fileName,
            'file_type' => $fileType,
            'created_at' => now(),
        ]);

        // Cập nhật last_read_at cho chính người gửi để tránh hiện thông báo tin nhắn mình vừa gửi
        DB::table('chat_group_members')
            ->where('group_id', $groupId)
            ->where('user_id', $userId)
            ->update(['last_read_at' => now()]);

        return response()->json([
            'success' => true,
            'message_id' => $messageId
        ]);
    }

    /**
     * Tạo hoặc lấy phòng chat 1-1
     */
    public function getOrCreateDirectChat(Request $request)
    {
        $userId = session('user')['userId'];
        $targetUserId = $request->target_user_id;

        // Tìm phòng chat 1-1 đang tồn tại giữa 2 người
        $existingGroup = DB::table('chat_groups as cg')
            ->join('chat_group_members as cgm1', 'cg.id', '=', 'cgm1.group_id')
            ->join('chat_group_members as cgm2', 'cg.id', '=', 'cgm2.group_id')
            ->where('cg.type', 0)
            ->where('cgm1.user_id', $userId)
            ->where('cgm2.user_id', $targetUserId)
            ->select('cg.*')
            ->first();

        if ($existingGroup) {
            return response()->json($existingGroup);
        }

        // Tạo mới nếu chưa có
        $groupId = DB::table('chat_groups')->insertGetId([
            'name' => null,
            'type' => 0,
            'created_by' => $userId,
            'created_at' => now(),
        ]);

        DB::table('chat_group_members')->insert([
            ['group_id' => $groupId, 'user_id' => $userId, 'joined_at' => now()],
            ['group_id' => $groupId, 'user_id' => $targetUserId, 'joined_at' => now()],
        ]);

        return response()->json(['id' => $groupId, 'type' => 0]);
    }

    /**
     * Lấy danh sách thành viên để tạo nhóm
     */
    public function getAllUsers()
    {
        $fiveMinsAgo = now()->subMinutes(5);
        $users = DB::table('user_management')
            ->where('isActive', 1)
            ->where('id', '!=', session('user')['userId'])
            ->select('id', 'fullName', 'userName', 'last_activity', 'deparment')
            ->get();
            
        foreach ($users as $user) {
            $user->is_online = ($user->last_activity && $user->last_activity > $fiveMinsAgo);
        }
        
        return response()->json($users);
    }

    /**
     * Tạo nhóm chat mới
     */
    public function createGroupChat(Request $request)
    {
        $userId = session('user')['userId'];
        $name = $request->name;
        $memberIds = $request->member_ids; // Mảng ID thành viên

        $groupId = DB::table('chat_groups')->insertGetId([
            'name' => $name,
            'type' => 1,
            'created_by' => $userId,
            'created_at' => now(),
        ]);

        $dataInsert = [['group_id' => $groupId, 'user_id' => $userId, 'joined_at' => now()]];
        foreach ($memberIds as $mId) {
            $dataInsert[] = ['group_id' => $groupId, 'user_id' => $mId, 'joined_at' => now()];
        }

        DB::table('chat_group_members')->insert($dataInsert);

        return response()->json(['id' => $groupId, 'success' => true]);
    }

    /**
     * Đánh dấu đã đọc tất cả tin nhắn trong một phòng
     */
    public function markAsRead(Request $request)
    {
        $userId = session('user')['userId'];
        $groupId = $request->group_id;

        DB::table('chat_group_members')
            ->where('group_id', $groupId)
            ->where('user_id', $userId)
            ->update(['last_read_at' => now()]);

        return response()->json(['success' => true]);
    }
}
