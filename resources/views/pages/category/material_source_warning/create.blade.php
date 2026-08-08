<!-- Modal khai báo mới 1 dòng ma trận cảnh báo nguồn NL -->
<div class="modal fade" id="msw_create_modal" tabindex="-1" role="dialog" aria-labelledby="mswCreateModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">

        <form action="{{ route('pages.category.material_source_warning.store') }}" method="POST">
            @csrf

            <div class="modal-content">
                <div class="modal-header">
                    <a href="{{ route('pages.general.home') }}">
                        <img src="{{ asset('img/iconstella.svg') }}" style="opacity: 0.8; max-width:45px;">
                    </a>

                    <h4 class="modal-title w-100 text-center" id="mswCreateModalLabel" style="color: #CDC717">
                        Khai Báo Ma Trận Cảnh Báo Nguồn NL
                    </h4>

                    <button type="button" class="close" data-dismiss="modal" aria-label="Đóng">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>

                <div class="modal-body">

                    <div class="msw-key-section">
                    <div class="row">
                        {{-- MÃ BTP --}}
                        <div class="col-lg-4 col-md-6">
                            <div class="form-group">
                                <label class="msw-label" for="create_intermediate_code">
                                    <i class="fas fa-cube mr-1"></i> Mã Bán Thành Phẩm
                                    <span class="text-danger">*</span>
                                </label>
                                <select class="form-control msw-select2" id="create_intermediate_code"
                                    name="intermediate_code" data-parent="#msw_create_modal">
                                    <option value=""> --- Chọn Mã BTP --- </option>
                                    @foreach ($intermediates as $intermediate)
                                        <option value="{{ $intermediate->intermediate_code }}"
                                            {{ old('intermediate_code') == $intermediate->intermediate_code ? 'selected' : '' }}>
                                            {{ $intermediate->intermediate_code }} - {{ $intermediate->product_name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('intermediate_code', 'createErrors')
                                    <div class="alert alert-danger mt-1 mb-0 py-1">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        {{-- MÃ NL LẤY TỪ BOM ẤN BẢN MỚI NHẤT TRÊN MMS --}}
                        <div class="col-lg-5 col-md-6">
                            <div class="form-group">
                                <label class="msw-label" for="create_material_code">
                                    <i class="fas fa-vial mr-1"></i> Mã Nguyên Liệu
                                    <span class="text-danger">*</span>
                                    <span class="badge msw-bom-revision d-none"></span>
                                </label>
                                <select class="form-control msw-select2" id="create_material_code" name="material_code"
                                    data-parent="#msw_create_modal" data-selected="{{ old('material_code') }}">
                                    <option value=""> --- Chọn Mã BTP trước --- </option>
                                </select>
                                <input type="hidden" name="material_name" id="create_material_name"
                                    value="{{ old('material_name') }}">
                                <input type="hidden" name="bom_revision" id="create_bom_revision"
                                    value="{{ old('bom_revision') }}">
                                <small class="text-muted msw-bom-message"></small>
                                @error('material_code', 'createErrors')
                                    <div class="alert alert-danger mt-1 mb-0 py-1">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        {{-- THỊ TRƯỜNG --}}
                        <div class="col-lg-3 col-md-6">
                            <div class="form-group">
                                <label class="msw-label" for="create_market_id">
                                    <i class="fas fa-globe-asia mr-1"></i> Thị Trường
                                    <span class="text-danger">*</span>
                                </label>
                                <select class="form-control msw-select2" id="create_market_id" name="market_id"
                                    data-parent="#msw_create_modal">
                                    <option value=""> --- Chọn Thị Trường --- </option>
                                    @foreach ($markets as $market)
                                        <option value="{{ $market->id }}"
                                            {{ old('market_id') == $market->id ? 'selected' : '' }}>
                                            {{ $market->code }} - {{ $market->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('market_id', 'createErrors')
                                    <div class="alert alert-danger mt-1 mb-0 py-1">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                    </div>{{-- /.msw-key-section --}}

                    {{-- MA TRẬN THIẾT BỊ THEO CÔNG ĐOẠN --}}
                    <div class="form-group">
                        <label class="msw-label">
                            <i class="fas fa-industry mr-1"></i> Thiết Bị Được Phép Thực Hiện Theo Công Đoạn
                            <span class="text-muted text-lowercase font-weight-normal font-italic">
                                (chỉ hiện thiết bị đã có định mức của mã BTP)
                            </span>
                        </label>
                        @error('rooms', 'createErrors')
                            <div class="alert alert-danger py-1">{{ $message }}</div>
                        @enderror
                        <div id="create_room_matrix">
                            <div class="alert alert-secondary mb-0">Chọn mã BTP để hiện danh sách thiết bị đã định mức.</div>
                        </div>
                    </div>

                    {{-- GHI CHÚ --}}
                    <div class="form-group">
                        <label class="msw-label" for="create_note">
                            <i class="fas fa-sticky-note mr-1"></i> Ghi Chú
                        </label>
                        <textarea class="form-control" id="create_note" name="note" rows="2">{{ old('note') }}</textarea>
                    </div>

                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save"></i> Lưu
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- Tự động mở modal nếu có lỗi --}}
@if ($errors->createErrors->any())
    <script>
        $(document).ready(function() {
            // Mã NL và ma trận thiết bị nạp bằng ajax nên phải nạp lại rồi mới chọn lại được
            mswLoadIntermediateData(
                'create',
                @json(old('intermediate_code')),
                @json(old('material_code')),
                @json(old('rooms', []))
            );

            $('#msw_create_modal').modal('show');
        });
    </script>
@endif
