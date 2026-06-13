<div class="modal fade" id="createModal" tabindex="-1" role="dialog" aria-labelledby="createModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createModalLabel">Thêm Khuôn Mẫu Mới</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="{{ route('pages.materData.blister_mold.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="form-group">
                        <label for="code">Mã Khuôn Mẫu <span class="text-danger">*</span></label>
                        <input type="text" name="code"
                            class="form-control @error('code', 'createErrors') is-invalid @enderror"
                            value="{{ old('code') }}" required maxlength="50">
                        @error('code', 'createErrors')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="form-group">
                        <label for="amount">Số Lượng</label>
                        <input type="number" name="amount" min="0"
                            class="form-control @error('amount', 'createErrors') is-invalid @enderror"
                            value="{{ old('amount') }}">
                        @error('amount', 'createErrors')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="form-group">
                        <label for="blister_type_code">Loại Máy Ép Vỉ <span class="text-danger">*</span></label>
                        <select name="blister_type_code[]" id="blister_type_code" class="form-control @error('blister_type_code', 'createErrors') is-invalid @enderror" multiple required>
                            @if(isset($blister_types))
                                @foreach ($blister_types as $blister_type)
                                    <option value="{{ $blister_type->code }}" {{ is_array(old('blister_type_code')) && in_array($blister_type->code, old('blister_type_code')) ? 'selected' : '' }}>
                                        {{ $blister_type->name }}
                                    </option>
                                @endforeach
                            @endif
                        </select>
                        @error('blister_type_code', 'createErrors')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-primary">Lưu</button>
                </div>
            </form>
        </div>
    </div>
</div>

@if ($errors->createErrors->any())
    <script>
        $(document).ready(function() {
            $('#createModal').modal('show');
        });
    </script>
@endif
