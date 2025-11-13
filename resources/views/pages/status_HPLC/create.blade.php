<!-- Modal -->
<div class="modal fade" id="Modal" tabindex="-1" role="dialog" aria-labelledby="ModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">

        <form action="{{ route('pages.status_HPLC.import') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <a href="{{ route('pages.general.home') }}">
                        <img src="{{ asset('img/iconstella.svg') }}" style="opacity: 0.8 ; max-width:45px;">
                    </a>

                    <h4 class="modal-title w-100 text-center" id="pModalLabel" style="color: #CDC717">
                        {{ 'Cập Nhật Trạng Thái Phòng Sản Xuất' }}
                    </h4>

                    <button type="button" class="close" data-dismiss="modal" aria-label="Đóng">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>

                <div class="modal-body">

                    <div class="form-group">
                        <label>Chọn file Excel:</label>
                        <input type="file" name="excel_file" id="excel_file" accept=".xlsx,.xls">
                    </div>

                    @php 
                        $date = [
                            1 => \Carbon\Carbon::now()->subDays(3)->format('d-m-Y'),
                            2 => \Carbon\Carbon::now()->subDays(2)->format('d-m-Y'),
                            3 => \Carbon\Carbon::now()->subDays(1)->format('d-m-Y'),
                            4 => \Carbon\Carbon::now()->format('d-m-Y'),
                            5 => \Carbon\Carbon::now()->addDays(1)->format('d-m-Y'),
                            6 => \Carbon\Carbon::now()->addDays(2)->format('d-m-Y'),
                            7 => \Carbon\Carbon::now()->addDays(3)->format('d-m-Y'),
                            8 => \Carbon\Carbon::now()->addDays(4)->format('d-m-Y'),
                            9 => \Carbon\Carbon::now()->addDays(5)->format('d-m-Y'),
                        ];
                    @endphp

                    <div class="form-group">
                        <label>Ngày UpLoad:</label>
                        <select class="select" data-placeholder="Select a State" id ="room_id"
                                style="width: 100%;" name="date_upload" >
                            <option value="" >-- Chọn Ngày Cập Nhật --</option>
                            @foreach ($date as $item)
                                <option value="{{ $item }}" >
                                    {{ $item }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="row mt-3">
                        <div class="col-md-12">
                            <label>Thông báo</label>
                            <textarea class="form-control" name="notification" rows="2"></textarea>
                        </div>
                        @error('notification', 'createErrors')
                            <div class="alert alert-danger mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <label>Thời Hạn Thông báo</label>
                            <div class="input-group">
                                <input type="datetime-local" class="form-control" name = "durability" value="{{ old('durability', \Carbon\Carbon::now()->format('Y-m-d\TH:i')) }}" >
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3 mt-3 password-wrapper">
                        <label for="password" class="form-label">Password</label>
                        <input type="password"  name="passWord" class="form-control" required>
                    </div>

                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
                    <button type="submit" class="btn btn-primary">Lưu</button>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- Hiển thị lỗi từ validation hoặc session --}}
@if ($errors->any() || session('error'))
    <script>
        $(document).ready(function() {
            $('#Modal').modal('show'); // Tự động mở lại modal
        });
    </script>

    <div class="alert alert-danger mt-3">
        @if (session('error'))
            {{ session('error') }}
        @else
            {{ $errors->first() }}
        @endif
    </div>
@endif


<script>
    document.getElementById('excel_file').addEventListener('change', function() {
        const file = this.files[0];
        const fileNameElement = document.getElementById('fileName');

    if (file) {
        if (file.name !== "Upload_DashBoard_QC.xlsx") {
            alert("⚠️ Vui lòng chọn đúng file: Upload_DashBoard_QC.xlsx");
            this.value = ''; // ❌ Xóa file đã chọn
            fileNameElement.textContent = ''; // Xóa hiển thị tên
            return;
        }

        // ✅ Nếu đúng tên file, hiển thị tên ra giao diện
        fileNameElement.textContent = "Tên file đã chọn: " + file.name;
    } else {
        fileNameElement.textContent = '';
    }

    });
</script>

<script>
    // 🔁 Chuyển form login <-> đổi mật khẩu
  

    // 👁‍🗨 Toggle hiển thị mật khẩu
    function togglePassword(inputId, icon) {
        const input = document.getElementById(inputId);
        if (input.type === "password") {
            input.type = "text";
            icon.classList.remove("bi-eye-slash");
            icon.classList.add("bi-eye");
        } else {
            input.type = "password";
            icon.classList.remove("bi-eye");
            icon.classList.add("bi-eye-slash");
        }
    }


</script>
