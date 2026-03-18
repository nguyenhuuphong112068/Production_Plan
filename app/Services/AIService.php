<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AIService
{
    /**
     * Xử lý tin nhắn và trả về phản hồi từ AI
     */
    public static function getResponse($message)
    {
        $message = mb_strtolower($message);

        // 1. Phản hồi chào hỏi cơ bản
        if (preg_match('/(chào|hello|hi|bạn là ai)/u', $message)) {
            return "Xin chào! Tôi là **AI Agent Search PMS**. Tôi được tạo ra để giúp bạn tìm kiếm thông tin nhanh trong hệ thống quản lý sản xuất (PMS).\n\nBạn có thể hỏi tôi về:\n- Danh mục bảo trì/hiệu chuẩn\n- Thông tin máy móc\n- Kế hoạch sản xuất";
        }

        // 2. Tra cứu Kế hoạch sản xuất (Sản lượng lý thuyết/thực tế)
        // Ví dụ: Kế hoạch sản xuất tháng 4 của PXVH có sản lượng lý thuyết bao nhiêu?
        if (preg_match('/(kế hoạch|sản lượng) (.+?) của (.+)/u', $message, $matches)) {
            $timeInfo = trim($matches[2]);
            $dept = trim($matches[3]);
            return self::searchProductionPlan($timeInfo, $dept);
        }

        // 3. Tra cứu tri thức hệ thống từ Metadata
        if (preg_match('/(bạn biết gì về|thông tin về|bảng|dữ liệu) (.+)/u', $message, $matches)) {
            $topic = trim($matches[2]);
            // Loại bỏ dấu hỏi chấm và từ "bảng" thừa ở đầu nếu có
            $topic = str_replace(['?', '.', '!'], '', $topic);
            $topic = preg_replace('/^(bảng|dữ liệu|thông tin) /u', '', $topic);
            $topic = trim($topic);
            
            return self::searchMetadata($topic);
        }

        // 4. Phản hồi mặc định
        return "Hiện tại tôi đang được huấn luyện để hiểu sâu hơn về dữ liệu dự án. Bạn thử hỏi tôi bằng từ khóa như: *'Tìm thiết bị [tên máy]'* hoặc *'Sản lượng lý thuyết tháng 4 của PXVH'* nhé.";
    }

    /**
     * Tra cứu Kế hoạch sản xuất
     */
    private static function searchProductionPlan($timeInfo, $dept)
    {
        // Chuẩn hóa tên phân xưởng (ví dụ PXVH)
        $dept = mb_strtoupper($dept);
        
        // Tìm kế hoạch trong plan_list dựa trên tên (tháng) và mã phân xưởng
        $plan = DB::table('plan_list')
            ->where('deparment_code', 'like', "%{$dept}%")
            ->where('name', 'like', "%{$timeInfo}%")
            ->where('active', 1)
            ->first();

        if (!$plan) {
            return "Tôi không tìm thấy kế hoạch nào khớp với '**{$timeInfo}**' của bộ phận '**{$dept}**'.\nBạn hãy thử lại với: *'Kế hoạch tháng 04 của PXVH'*.";
        }

        // Lấy danh sách sản lượng lý thuyết từng lô từ plan_master
        $stats = DB::table('plan_master as pm')
            ->join('finished_product_category as fpc', 'pm.product_caterogy_id', '=', 'fpc.id')
            ->where('pm.plan_list_id', $plan->id)
            ->where('pm.active', 1)
            ->where('pm.cancel', 0)
            ->select(
                DB::raw('COUNT(pm.id) as total_batches'),
                DB::raw('SUM(fpc.batch_qty) as total_qty'),
                'fpc.unit_batch_qty'
            )
            ->groupBy('fpc.unit_batch_qty')
            ->first();

        if (!$stats) {
            return "Kế hoạch '**{$plan->name}**' đã được tìm thấy, nhưng hiện tại chưa có dữ liệu lô sản xuất chi tiết bên trong.";
        }

        $formattedQty = number_format($stats->total_qty, 2);

        return "Dựa trên dữ liệu hệ thống, tôi tìm thấy thông tin sau cho **{$dept}**:\n" .
               "- **Kế hoạch**: {$plan->name}\n" .
               "- **Tổng số lô dự kiến**: {$stats->total_batches} lô\n" .
               "- **Tổng sản lượng lý thuyết**: {$formattedQty} {$stats->unit_batch_qty}\n" .
               "- **Trạng thái**: " . ($plan->send ? 'Đã gởi' : 'Đang dự thảo') . "\n" .
               "- **Người lập**: {$plan->prepared_by}";
    }

    /**
     * Tra cứu Metadata (Tri thức hệ thống)
     */
    private static function searchMetadata($topic)
    {
        $info = DB::table('ai_metadata')
            ->where('table_name', 'like', "%{$topic}%")
            ->orWhere('keywords', 'like', "%{$topic}%")
            ->orWhere('description', 'like', "%{$topic}%")
            ->first();

        if (!$info) {
            return "Rất tiếc, tôi chưa có thông tin chi tiết về chủ đề '**{$topic}**' trong Metadata. Bạn có thể bổ sung dữ liệu vào bảng `ai_metadata` để giúp tôi thông minh hơn nhé!";
        }

        return "Về '**{$topic}**', tôi biết các thông tin sau:\n" .
               "- **Mô tả**: {$info->description}\n" .
               "- **Các cột dữ liệu chính**: {$info->key_columns}\n" .
               "- **Từ khóa liên quan**: {$info->keywords}";
    }

    /**
     * Tìm kiếm thiết bị trong các bảng Inst_Master
     */
    private static function searchInstruments($keyword)
    {
        // (Giữ nguyên logic cũ của searchInstruments bên dưới)
        $results = collect();
        $connections = ['cal1', 'cal2'];
        
        foreach ($connections as $conn) {
            for ($i = 1; $i <= 3; $i++) {
                try {
                    $items = DB::connection($conn)->table("Inst_Master_{$i}")
                        ->where('Inst_id', 'like', "%{$keyword}%")
                        ->orWhere('Inst_Name', 'like', "%{$keyword}%")
                        ->limit(3)
                        ->get();
                    $results = $results->merge($items);
                } catch (\Exception $e) {
                    continue;
                }
            }
        }

        if ($results->isEmpty()) {
            return "Rất tiếc, tôi không tìm thấy thiết bị nào liên quan đến từ khóa '**{$keyword}**'.";
        }

        $response = "Tôi tìm thấy " . $results->count() . " kết quả phù hợp cho bạn:\n";
        foreach ($results->take(5) as $item) {
            $response .= "\n- **{$item->Inst_id}**: {$item->Inst_Name} (Vị trí: {$item->Inst_Installed_Location})";
        }
        
        return $response;
    }
}
