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
            ->select(
                'cg.*',
                'cgm.last_read_at',
                DB::raw("(SELECT message FROM chat_messages WHERE group_id = cg.id ORDER BY created_at DESC LIMIT 1) as last_message"),
                DB::raw("(SELECT created_at FROM chat_messages WHERE group_id = cg.id ORDER BY created_at DESC LIMIT 1) as last_time"),
                DB::raw("(SELECT sender_id FROM chat_messages WHERE group_id = cg.id ORDER BY created_at DESC LIMIT 1) as last_sender_id"),
                DB::raw("(SELECT COUNT(*) FROM chat_messages 
                          WHERE group_id = cg.id AND sender_id != $userId 
                          AND (cgm.last_read_at IS NULL OR created_at > cgm.last_read_at)) as unread_count"),
                DB::raw("(CASE WHEN cg.type = 0 THEN 
                           (SELECT u.fullName FROM user_management u JOIN chat_group_members m ON u.id = m.user_id 
                            WHERE m.group_id = cg.id AND m.user_id != $userId LIMIT 1) 
                          ELSE cg.name END) as display_name"),
                DB::raw("(CASE WHEN cg.type = 0 THEN 
                           (SELECT u.last_activity FROM user_management u JOIN chat_group_members m ON u.id = m.user_id 
                            WHERE m.group_id = cg.id AND m.user_id != $userId LIMIT 1) 
                          ELSE NULL END) as last_activity")
            )
            ->orderByRaw('COALESCE((SELECT created_at FROM chat_messages WHERE group_id = cg.id ORDER BY created_at DESC LIMIT 1), cg.created_at) DESC')
            ->get();

        $fiveMinsAgo = now()->subMinutes(5);
        foreach ($groups as $group) {
            $group->is_online = ($group->type == 0 && $group->last_activity && $group->last_activity > $fiveMinsAgo);
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

        // Lấy thông tin tin nhắn được reply và Reactions
        foreach ($messages as $msg) {
            // Reply
            if ($msg->reply_to_id) {
                $msg->reply_to_content = DB::table('chat_messages as cm')
                    ->join('user_management as u', 'cm.sender_id', '=', 'u.id')
                    ->where('cm.id', $msg->reply_to_id)
                    ->select('cm.message', 'cm.is_recalled', 'u.fullName as sender_name')
                    ->first();
            }

            // Reactions
            $msg->reactions_summary = $this->getReactionsSummary($msg->id, $userId);
        }

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
            'reply_to_id' => is_numeric($request->reply_to_id) ? $request->reply_to_id : null, 
            'created_at' => now(),
        ]);

        // Xử lý Phản hồi tự động nếu gửi cho AI Agent (ID: 9999)
        $targetUser = DB::table('chat_group_members')
            ->where('group_id', $groupId)
            ->where('user_id', 9999)
            ->exists();

        if ($targetUser && $userId != 9999) {
            $aiResponse = \App\Services\AIService::getResponse($message);
            
            DB::table('chat_messages')->insert([
                'group_id' => $groupId,
                'sender_id' => 9999,
                'message' => $aiResponse,
                'created_at' => now()->addSecond(), // Đảm bảo sau tin nhắn người dùng
            ]);
        }

        // Xử lý Tag @mention trong nhóm chat
        if (preg_match_all('/@(.+?)\[(\d+)\]/', $message, $matches)) {
            $taggedUserIds = $matches[2];
            
            \App\Http\Controllers\General\NotificationController::sendNotification(
                session('user')['fullName'] . " đã nhắc đến bạn trong một tin nhắn chat: " . $message,
                'Nhắc tên',
                $groupId,
                array_unique($taggedUserIds)
            );
        }

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
     * Thu hồi tin nhắn
     */
    public function recallMessage(Request $request)
    {
        $userId = session('user')['userId'];
        $messageId = $request->message_id;

        $message = DB::table('chat_messages')
            ->where('id', $messageId)
            ->where('sender_id', $userId)
            ->first();

        if (!$message) {
            return response()->json(['success' => false, 'message' => 'Không tìm thấy tin nhắn.'], 404);
        }

        // Kiểm tra thời gian (30 phút)
        $createdAt = \Carbon\Carbon::parse($message->created_at);
        if ($createdAt->diffInMinutes(now()) > 30) {
            return response()->json(['success' => false, 'message' => 'Đã quá 30 phút, không thể thu hồi.'], 400);
        }

        DB::table('chat_messages')
            ->where('id', $messageId)
            ->update([
                'is_recalled' => 1,
                'message' => 'Tin nhắn đã được thu hồi',
                'file_path' => null,
                'file_name' => null,
                'file_type' => null
            ]);

        return response()->json(['success' => true]);
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
            ->orderByRaw('CASE WHEN id = 9999 THEN 0 ELSE 1 END') // AI Agent luôn ở đầu
            ->orderBy('fullName', 'asc')
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

    /**
     * Thả hoặc gỡ cảm xúc cho tin nhắn
     */
    public function toggleReaction(Request $request)
    {
        $userId = session('user')['userId'];
        $messageId = $request->message_id;
        $reaction = $request->reaction;

        $existing = DB::table('chat_message_reactions')
            ->where('message_id', $messageId)
            ->where('user_id', $userId)
            ->first();

        if ($existing) {
            if ($existing->reaction === $reaction) {
                // Nếu trùng reaction cũ -> Gỡ bỏ
                DB::table('chat_message_reactions')
                    ->where('id', $existing->id)
                    ->delete();
            } else {
                // Nếu khác reaction cũ -> Cập nhật
                DB::table('chat_message_reactions')
                    ->where('id', $existing->id)
                    ->update([
                        'reaction' => $reaction,
                        'created_at' => now()
                    ]);
            }
        } else {
            // Chưa có -> Thêm mới
            DB::table('chat_message_reactions')->insert([
                'message_id' => $messageId,
                'user_id' => $userId,
                'reaction' => $reaction,
                'created_at' => now()
            ]);
        }

        // Trả về summary mới nhất để Frontend cập nhật ngay
        $summary = $this->getReactionsSummary($messageId, $userId);
        return response()->json([
            'success' => true,
            'reactions_summary' => $summary
        ]);
    }

    /**
     * Helper lấy tổng hợp cảm xúc của một tin nhắn
     */
    private function getReactionsSummary($messageId, $userId)
    {
        $reactions = DB::table('chat_message_reactions as r')
            ->join('user_management as u', 'r.user_id', '=', 'u.id')
            ->where('r.message_id', $messageId)
            ->select('r.reaction', 'u.fullName', 'u.id as user_id')
            ->get();

        $summary = [];
        foreach ($reactions as $r) {
            if (!isset($summary[$r->reaction])) {
                $summary[$r->reaction] = [
                    'count' => 0,
                    'users' => [],
                    'me' => false
                ];
            }
            $summary[$r->reaction]['count']++;
            $summary[$r->reaction]['users'][] = $r->fullName;
            if ($r->user_id == $userId) {
                $summary[$r->reaction]['me'] = true;
            }
        }
        return $summary;
    }
}
