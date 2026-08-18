<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

use Illuminate\Support\Facades\Schedule;

Schedule::command('notify:unscheduled-batches')->dailyAt('08:00');
Schedule::command('notify:validation-sampling')->dailyAt('08:00');

// Chốt tồn bán thành phẩm đúng đầu ngày công 06:00
Schedule::command('wip:snapshot-coverage')->dailyAt('06:00')->withoutOverlapping();

// Đồng bộ danh sách nhân sự từ eO2 PMS.
// Chạy nền vì máy chủ nguồn mất ~9.5s (PXTN) đến ~88s (PXV1) mỗi request —
// không thể để trong luồng đăng nhập. Chạy trước giờ vào ca sáng, và lặp lại
// giữa ngày để bắt các thay đổi nhân sự phát sinh.
Schedule::command('employees:sync-roster')->dailyAt('05:00')->withoutOverlapping();
Schedule::command('employees:sync-roster')->dailyAt('12:30')->withoutOverlapping();

// Nạp sẵn cache lịch trực cho trang Lịch công tác.
//
// Chạy mỗi giờ trong khung giờ làm việc, ngắn hơn `shiftapi.cache_ttl` (2h) nên
// cache luôn được làm mới trước khi hết hạn -> người dùng đọc cache chứ không
// gọi API, vừa nhanh vừa không dội request làm eO2 trả HTTP 429.
//
// CHỈ tháng hiện tại: đo thực tế cho thấy nạp 2 tháng × 7 bộ phận trong một
// lượt liền mạch là vượt hạn mức của eO2 và bị chặn từ giữa chừng. Tháng kế
// tiếp ít người xem nên tách sang một lượt riêng lúc 21:00, ngoài giờ làm việc.
//
// withoutOverlapping: một lượt có nghỉ giãn nhịp nên kéo dài vài phút.
// Ba mốc bám theo nhịp làm việc: trước ca sáng, đầu giờ chiều, và cuối giờ
// chiều. Khoảng cách lớn nhất giữa hai lượt là 8 giờ (16:00 -> 00:00), nên
// `shiftapi.cache_ttl` phải dài hơn con số đó, nếu không cache sẽ nguội giữa
// chừng và người dùng lại phải chờ API.
Schedule::command('shifts:warm-cache')->dailyAt('05:55')->withoutOverlapping();
Schedule::command('shifts:warm-cache')->dailyAt('12:00')->withoutOverlapping();
Schedule::command('shifts:warm-cache')->dailyAt('16:00')->withoutOverlapping();

// Nửa đêm nạp thêm tháng kế tiếp. Lượt này nặng gấp đôi (14 lượt con thay vì 7)
// nên đặt vào lúc không ai dùng và eO2 rảnh nhất.
Schedule::command('shifts:warm-cache --months=2')->dailyAt('00:00')->withoutOverlapping();
