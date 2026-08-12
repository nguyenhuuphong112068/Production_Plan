{{-- Ô chọn "Dược sĩ phụ trách", dùng cho các modal tạo mới / cập nhật danh mục BTP.
     Danh mục TP không khai báo dược sĩ riêng mà dùng theo mã BTP tương ứng.
     $pharmacists lấy từ user_management qua helper pharmacist_options().
     $selected chỉ cần truyền ở form tạo mới (giữ lại old input); form cập nhật do JS gán. --}}
<div class="form-group">
    <label for="pharmacist_id">Dược Sĩ Phụ Trách</label>
    <select class="form-control select-pharmacist" name="pharmacist_id">
        <option value=""> --- Chọn Dược Sĩ Phụ Trách --- </option>
        @foreach ($pharmacists as $pharmacist)
            <option value="{{ $pharmacist->id }}"
                {{ (string) ($selected ?? '') === (string) $pharmacist->id ? 'selected' : '' }}>
                {{ $pharmacist->fullName }}{{ $pharmacist->deparment ? ' - ' . $pharmacist->deparment : '' }}
            </option>
        @endforeach
    </select>
</div>
