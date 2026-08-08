<!-- Modal cập nhật 1 dòng ma trận cảnh báo nguồn NL -->
<div class="modal fade" id="msw_update_modal" tabindex="-1" role="dialog" aria-labelledby="mswUpdateModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">

        <form action="{{ route('pages.category.material_source_warning.update') }}" method="POST">
            @csrf
            <input type="hidden" name="id" id="update_id" value="{{ old('id') }}">

            <div class="modal-content">
                <div class="modal-header">
                    <a href="{{ route('pages.general.home') }}">
                        <img src="{{ asset('img/iconstella.svg') }}" style="opacity: 0.8; max-width:45px;">
                    </a>

                    <h4 class="modal-title w-100 text-center" id="mswUpdateModalLabel" style="color: #CDC717">
                        Cập Nhật Ma Trận Cảnh Báo Nguồn NL
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
                                <label class="msw-label" for="update_intermediate_code">
                                    <i class="fas fa-cube mr-1"></i> Mã Bán Thành Phẩm
                                    <span class="text-danger">*</span>
                                </label>
                                <select class="form-control msw-select2" id="update_intermediate_code"
                                    name="intermediate_code" data-parent="#msw_update_modal">
                                    <option value=""> --- Chọn Mã BTP --- </option>
                                    @foreach ($intermediates as $intermediate)
                                        <option value="{{ $intermediate->intermediate_code }}">
                                            {{ $intermediate->intermediate_code }} - {{ $intermediate->product_name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('intermediate_code', 'updateErrors')
                                    <div class="alert alert-danger mt-1 mb-0 py-1">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        {{-- MÃ NL LẤY TỪ BOM ẤN BẢN MỚI NHẤT TRÊN MMS --}}
                        <div class="col-lg-5 col-md-6">
                            <div class="form-group">
                                <label class="msw-label" for="update_material_code">
                                    <i class="fas fa-vial mr-1"></i> Mã Nguyên Liệu
                                    <span class="text-danger">*</span>
                                    <span class="badge msw-bom-revision d-none"></span>
                                </label>
                                <select class="form-control msw-select2" id="update_material_code" name="material_code"
                                    data-parent="#msw_update_modal">
                                    <option value=""> --- Chọn Mã BTP trước --- </option>
                                </select>
                                <input type="hidden" name="material_name" id="update_material_name">
                                <input type="hidden" name="bom_revision" id="update_bom_revision">
                                <small class="text-muted msw-bom-message"></small>
                                @error('material_code', 'updateErrors')
                                    <div class="alert alert-danger mt-1 mb-0 py-1">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        {{-- THỊ TRƯỜNG --}}
                        <div class="col-lg-3 col-md-6">
                            <div class="form-group">
                                <label class="msw-label" for="update_market_id">
                                    <i class="fas fa-globe-asia mr-1"></i> Thị Trường
                                    <span class="text-danger">*</span>
                                </label>
                                <select class="form-control msw-select2" id="update_market_id" name="market_id"
                                    data-parent="#msw_update_modal">
                                    <option value=""> --- Chọn Thị Trường --- </option>
                                    @foreach ($markets as $market)
                                        <option value="{{ $market->id }}">
                                            {{ $market->code }} - {{ $market->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('market_id', 'updateErrors')
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
                        @error('rooms', 'updateErrors')
                            <div class="alert alert-danger py-1">{{ $message }}</div>
                        @enderror
                        <div id="update_room_matrix">
                            <div class="alert alert-secondary mb-0">Chọn mã BTP để hiện danh sách thiết bị đã định mức.</div>
                        </div>
                    </div>

                    {{-- GHI CHÚ --}}
                    <div class="form-group">
                        <label class="msw-label" for="update_note">
                            <i class="fas fa-sticky-note mr-1"></i> Ghi Chú
                        </label>
                        <textarea class="form-control" id="update_note" name="note" rows="2"></textarea>
                    </div>

                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-save"></i> Cập Nhật
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- Submit lỗi thì mở lại modal và trả về đúng dữ liệu người dùng vừa nhập --}}
@if ($errors->updateErrors->any())
    <script>
        $(document).ready(function() {
            $('#update_intermediate_code').val(@json(old('intermediate_code')));
            $('#update_market_id').val(@json(old('market_id')));
            $('#update_note').val(@json(old('note')));
            $('#update_material_name').val(@json(old('material_name')));
            $('#update_bom_revision').val(@json(old('bom_revision')));

            // Mã NL và ma trận thiết bị nạp bằng ajax nên chỉ chọn lại được sau khi nạp xong
            mswLoadIntermediateData(
                'update',
                @json(old('intermediate_code')),
                @json(old('material_code')),
                @json(old('rooms', []))
            );

            $('#msw_update_modal').modal('show');
        });
    </script>
@endif
