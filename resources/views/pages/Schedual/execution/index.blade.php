@extends('layout.master')

@section('topNAV')
    @include('layout.topNAV')
@endsection

@section('leftNAV')
    @include('layout.leftNAV')
@endsection

@php
    $PES = \App\Services\ProductionExecutionService::class;
@endphp

@section('mainContent')
    @include('pages.Schedual.execution._styles')

    <div class="content-wrapper exec-page"
        style="height: 100vh; overflow-y: auto; overflow-x: hidden; padding: 70px 14px 60px;">

        <div class="exec-toolbar">
            <div class="exec-title">
                <div class="exec-title-icon"><i class="fas fa-industry"></i></div>
                <div>
                    <h4>THỰC THI SẢN XUẤT – {{ $production }}</h4>
                    <div class="small">
                        Khai báo bắt đầu / tạm dừng / kết thúc sản xuất và vệ sinh phòng theo thực tế.
                        Dữ liệu ghi vào xác nhận hoàn thành và Báo cáo ngày.
                    </div>
                </div>
            </div>

            {{-- Công tắc tịnh tuyến lịch lưu theo phân xưởng (schedule_reroute_settings), dùng chung với trang Xác nhận hoàn thành --}}
            @if ($canReroute)
                <div class="exec-reroute {{ $rerouteEnabled ? 'on' : '' }}" id="execReroute">
                    <label class="exec-switch mb-0" for="execRerouteToggle">
                        <input type="checkbox" id="execRerouteToggle" {{ $rerouteEnabled ? 'checked' : '' }}>
                        <span class="exec-slider"></span>
                    </label>
                    <label for="execRerouteToggle" class="mb-0 font-weight-bold" style="cursor: pointer">
                        Điều chỉnh lịch theo thời gian thực <span class="badge badge-warning ml-1">Thử nghiệm</span>
                    </label>
                    <small class="d-block w-100 mt-1" id="execRerouteHint"></small>
                </div>
            @elseif ($rerouteEnabled)
                <div class="exec-reroute on">
                    <i class="fas fa-random mr-1"></i>
                    <b>Đang bật điều chỉnh lịch theo thời gian thực:</b>
                    Kết thúc vệ sinh sau lô sẽ tự dịch các lô liên quan trên lịch lý thuyết.
                </div>
            @endif

            <div class="exec-filters">
                <button type="button" class="exec-pill js-filter-stage active" data-stage="all">Tất cả công đoạn</button>
                @foreach ($stages as $group => $rooms)
                    <button type="button" class="exec-pill js-filter-stage" data-stage="{{ $group }}">
                        {{ $PES::STAGE_GROUPS[$group]['label'] ?? $rooms->first()->stage }}
                    </button>
                @endforeach
            </div>

            <div class="exec-filters mt-2">
                @foreach ($PES::DISPLAY_ORDER as $s)
                    <button type="button" class="exec-filter-state st-{{ $PES::STATE_META[$s][0] }}"
                        data-state="{{ $s }}" title="Lọc phòng {{ $PES::STATE_LABELS[$s] }}">
                        <i class="fas {{ $PES::STATE_META[$s][1] }}"></i> {{ $PES::STATE_LABELS[$s] }}
                        <b>{{ $stateCounts->get($s, 0) }}</b>
                    </button>
                @endforeach
                <input type="search" id="execSearch" class="form-control form-control-sm exec-search"
                    placeholder="Tìm phòng, sản phẩm, số lô...">
                <button type="button" id="execRefresh" class="btn btn-sm btn-outline-secondary"
                    title="Tải lại trạng thái các phòng">
                    <i class="fas fa-sync-alt"></i>
                </button>
                <span class="exec-updated">Cập nhật lúc <span id="execUpdatedAt">{{ now()->format('H:i') }}</span></span>
            </div>
        </div>

        <div id="execBoard">
            @include('pages.Schedual.execution._board')
        </div>
    </div>
@endsection

@section('model')
    <div class="exec-page">
        {{-- ===== Mở phòng: chọn lô ===== --}}
        <div class="modal fade exec-modal" id="execStartModal" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header exec-mh-go">
                        <h5 class="modal-title"><i class="fas fa-door-open"></i> Mở phòng <span class="js-room"></span></h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Đóng"><span
                                aria-hidden="true">&times;</span></button>
                    </div>
                    <div class="modal-body">
                        <div class="d-flex flex-wrap align-items-center mb-2">
                            <input type="search" id="execPlanSearch" class="form-control form-control-sm mb-1"
                                style="max-width: 280px" placeholder="Tìm sản phẩm, số lô, mã...">
                        </div>
                        <div id="execEquipWarn" class="alert alert-warning py-2 small mb-2 d-none"></div>
                        <div id="execPlanHint" class="small text-muted mb-2"></div>
                        <div id="execPlanList" class="exec-plan-list"></div>

                        <div class="exec-now mt-3">
                            <i class="far fa-clock"></i> BĐSX ghi theo giờ hệ thống lúc bấm nút · bây giờ <b
                                class="js-now"></b>
                            <div class="mt-1">
                                <b>Chuẩn bị</b>: mở phòng để chuẩn bị, BĐCM ghi khi bấm <b>Thực thi sản xuất</b> trên card
                                phòng.
                                <b>Thực thi sản xuất</b>: bắt đầu tạo ra sản lượng ngay (BĐCM = BĐSX).
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Hủy</button>
                        <button type="button" class="btn btn-info js-submit js-start-mode" data-mode="prepare">
                            <i class="fas fa-clipboard-check"></i> Chuẩn bị
                        </button>
                        <button type="button" class="btn btn-success js-submit js-start-mode" data-mode="execute">
                            <i class="fas fa-play"></i> Thực thi sản xuất
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- ===== Tạm dừng / Kết thúc SX: nhập sản lượng ===== --}}
        <div class="modal fade exec-modal" id="execYieldModal" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title"><span class="js-title"></span> – <span class="js-room"></span></h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Đóng"><span
                                aria-hidden="true">&times;</span></button>
                    </div>
                    <div class="modal-body">
                        <div class="exec-modal-batch">
                            <div><b class="js-batch"></b></div>
                            <div class="small js-batch-info"></div>
                        </div>

                        {{-- BĐCM = lúc bắt đầu / bắt đầu lại, KT = giờ hệ thống lúc bấm nút --}}
                        <div class="exec-now mb-3">
                            <i class="far fa-clock"></i> Lần này: BĐCM <b class="js-yield-start"></b> → KT <b
                                class="js-now"></b>
                            (giờ hệ thống) · <span class="js-since js-yield-run"></span>
                        </div>

                        {{-- Nhóm lô cân chung: mỗi lô 1 dòng (JS dựng) --}}
                        <div id="execYieldGroup" class="d-none mb-3"></div>

                        <div class="form-row" id="execYieldSingle">
                            <div class="form-group col-6">
                                <label for="execYieldQty">Sản lượng lần này</label>
                                <div class="input-group">
                                    <input type="text" inputmode="decimal" id="execYieldQty" class="form-control"
                                        autocomplete="off">
                                    <div class="input-group-append"><span class="input-group-text js-unit"></span></div>
                                </div>
                                <small class="form-text text-muted">Chỉ sản lượng của lần này (BĐCM → bây giờ), không cộng
                                    dồn.</small>
                            </div>
                            <div class="form-group col-3">
                                <label for="execYieldBoxes">Số thùng lần này</label>
                                <input type="text" inputmode="numeric" id="execYieldBoxes" class="form-control"
                                    autocomplete="off">
                            </div>
                            <div class="form-group col-3 d-none" id="execYieldBatchGroup">
                                <label for="execYieldBatch">Số lô TT</label>
                                <input type="text" id="execYieldBatch" class="form-control" autocomplete="off">
                            </div>
                        </div>

                        {{-- Lô đã có sản lượng > 0: thùng dở của lần trước có thể được cho tiếp sản phẩm vào --}}
                        <div class="form-group d-none" id="execBoxModeGroup">
                            <label>Thùng của lần này</label>
                            <div class="exec-boxmode">
                                <label class="exec-boxopt">
                                    <input type="radio" name="execBoxMode" value="continue">
                                    <span><b><i class="fas fa-box-open"></i> Dùng tiếp thùng đang sử dụng</b>
                                        <small>Cho tiếp sản phẩm vào thùng đã khai báo lần trước (không đếm lại thùng
                                            đó)</small></span>
                                </label>
                                <label class="exec-boxopt">
                                    <input type="radio" name="execBoxMode" value="new">
                                    <span><b><i class="fas fa-box"></i> Thùng mới</b>
                                        <small>Tất cả thùng của lần này là thùng mới</small></span>
                                </label>
                            </div>
                            <small class="form-text js-box-total"></small>
                        </div>

                        <div class="form-group d-none" id="execYieldReasonGroup">
                            <label for="execYieldReason">Lý do tạm dừng</label>
                            <input type="text" id="execYieldReason" class="form-control" list="execPauseReasons"
                                autocomplete="off" placeholder="VD: Hết ca, Nghỉ ăn, Chờ nguyên liệu...">
                            <small class="form-text text-muted">Khoảng tạm dừng được ghi vào Báo cáo ngày khi bắt đầu lại /
                                kết thúc lô.</small>
                        </div>

                        <div class="form-group mb-0">
                            <label for="execYieldNote">Ghi chú</label>
                            <textarea id="execYieldNote" class="form-control" rows="2"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Hủy</button>
                        <button type="button" class="btn btn-primary js-submit"></button>
                    </div>
                </div>
            </div>
        </div>

        {{-- ===== Thao tác xác nhận theo giờ hệ thống (bắt đầu lại, vệ sinh, ...) ===== --}}
        <div class="modal fade exec-modal" id="execActionModal" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title"><span class="js-title"></span> – <span class="js-room"></span></h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Đóng"><span
                                aria-hidden="true">&times;</span></button>
                    </div>
                    <div class="modal-body">
                        <div class="exec-modal-batch js-msg"></div>
                        <div class="exec-now mb-3">
                            <i class="far fa-clock"></i> <span class="js-time-label"></span> ghi theo giờ hệ thống lúc bấm
                            nút · bây giờ <b class="js-now"></b>
                        </div>
                        <div class="form-group d-none" id="execActionLevelGroup">
                            <label for="execActionLevel">Cấp vệ sinh</label>
                            <select id="execActionLevel" class="form-control">
                                @foreach ($PES::CLEANING_LEVELS as $code => $label)
                                    <option value="{{ $code }}">{{ $label }} (hạn sạch
                                        {{ $PES::CLEAN_HOLD_HOURS[$code] >= 48 ? $PES::CLEAN_HOLD_HOURS[$code] / 24 . ' ngày' : $PES::CLEAN_HOLD_HOURS[$code] . ' giờ' }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group mb-0 d-none" id="execActionNoteGroup">
                            <label for="execActionNote" id="execActionNoteLabel">Ghi chú</label>
                            <textarea id="execActionNote" class="form-control" rows="2"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Hủy</button>
                        <button type="button" class="btn btn-primary js-submit"></button>
                    </div>
                </div>
            </div>
        </div>

        {{-- ===== Hoạt động khác (ghi room_status → Báo cáo ngày) ===== --}}
        <div class="modal fade exec-modal" id="execActivityModal" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header exec-mh-navy">
                        <h5 class="modal-title"><i class="fas fa-clipboard-list"></i> Hoạt động khác – <span
                                class="js-room"></span></h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Đóng"><span
                                aria-hidden="true">&times;</span></button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="execActName">Hoạt động</label>
                            <input type="text" id="execActName" class="form-control" list="execActivityList"
                                autocomplete="off" placeholder="VD: Ngưng, Chuẩn bị lô mới, Bảo trì...">
                        </div>
                        <div class="form-group">
                            <label for="execActNote">Ghi chú</label>
                            <input type="text" id="execActNote" class="form-control" autocomplete="off"
                                placeholder="VD: Chờ cốm, Không có nhân sự...">
                        </div>
                        <div class="exec-now">
                            <i class="far fa-clock"></i> Bắt đầu từ bây giờ <b class="js-now"></b> (giờ hệ thống). Hoạt
                            động hiện trên card phòng,
                            bấm <b>Kết thúc</b> trên card khi xong. Được ghi vào Báo cáo ngày của phòng.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Hủy</button>
                        <button type="button" class="btn btn-primary js-submit"><i class="fas fa-save"></i> Lưu hoạt
                            động</button>
                    </div>
                </div>
            </div>
        </div>

        {{-- ===== Nhãn hiệu chuẩn / bảo trì thiết bị của phòng ===== --}}
        <div class="modal fade exec-modal" id="execEquipModal" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-xl" role="document">
                <div class="modal-content">
                    <div class="modal-header exec-mh-navy">
                        <h5 class="modal-title"><i class="fas fa-tags"></i> Nhãn hiệu chuẩn – bảo trì thiết bị – <span
                                class="js-room"></span></h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Đóng"><span
                                aria-hidden="true">&times;</span></button>
                    </div>
                    <div class="modal-body" style="max-height: 75vh; overflow-y: auto">
                        <div class="d-flex flex-wrap align-items-center justify-content-between mb-2">
                            <small class="text-muted">
                                Thiết bị của phòng theo danh mục <b>Bảo trì hiệu chuẩn</b>; tình trạng lấy từ phần mềm hiệu
                                chuẩn
                                · Cập nhật lúc <span class="js-generated"></span>. Bấm vào tình trạng để xem nhãn.
                            </small>
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="execEquipRefresh">
                                <i class="fas fa-sync-alt"></i> Làm mới
                            </button>
                        </div>
                        <div class="alert alert-warning py-2 d-none js-errors"></div>
                        <table class="table table-sm table-hover exec-equip-table mb-0">
                            <thead>
                                <tr>
                                    <th style="width: 120px">Mã thiết bị</th>
                                    <th>Tên thiết bị</th>
                                    <th style="width: 140px" class="text-center">Hiệu chuẩn</th>
                                    <th style="width: 140px" class="text-center">Bảo trì</th>
                                    <th style="width: 140px" class="text-center">Tiện ích</th>
                                </tr>
                            </thead>
                            <tbody id="execEquipBody"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- ===== Lịch sử phòng ===== --}}
        <div class="modal fade exec-modal" id="execHistoryModal" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-xl" role="document">
                <div class="modal-content">
                    <div class="modal-header exec-mh-navy">
                        <h5 class="modal-title"><i class="fas fa-history"></i> Lịch sử – <span class="js-room"></span>
                        </h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Đóng"><span
                                aria-hidden="true">&times;</span></button>
                    </div>
                    <div class="modal-body" style="max-height: 70vh; overflow-y: auto">
                        <table class="table table-sm table-hover exec-history mb-0">
                            <thead>
                                <tr>
                                    <th style="width: 190px">Trạng thái / Hoạt động</th>
                                    <th>Chi tiết</th>
                                    <th style="width: 125px">Bắt đầu</th>
                                    <th style="width: 125px">Kết thúc</th>
                                    <th style="width: 200px">Người thực hiện</th>
                                </tr>
                            </thead>
                            <tbody id="execHistoryBody"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- ===== Kiểm tra vệ sinh: người kiểm tra (khác người vệ sinh) xác thực lại tài khoản ===== --}}
        <div class="modal fade exec-modal" id="execCheckModal" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <form class="modal-content" id="execCheckForm" autocomplete="off">
                    <div class="modal-header exec-mh-check">
                        <h5 class="modal-title"><i class="fas fa-user-check"></i> Kiểm tra vệ sinh – <span
                                class="js-room"></span></h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Đóng"><span
                                aria-hidden="true">&times;</span></button>
                    </div>
                    <div class="modal-body">
                        <div class="exec-modal-batch js-check-info"></div>

                        <div class="exec-check-auth">
                            <div class="exec-check-auth-title"><i class="fas fa-signature"></i> Xác nhận người kiểm tra
                                (không được là người vệ sinh)</div>
                            <div class="form-row">
                                <div class="form-group col-6">
                                    <label for="execCheckUser">Tài khoản</label>
                                    <input type="text" id="execCheckUser" class="form-control" autocomplete="off"
                                        autocapitalize="off" spellcheck="false">
                                </div>
                                <div class="form-group col-6">
                                    <label for="execCheckPass">Mật khẩu</label>
                                    <input type="password" id="execCheckPass" class="form-control"
                                        autocomplete="new-password">
                                </div>
                            </div>
                        </div>

                        <label>Kết quả kiểm tra</label>
                        {{-- 1 nút bật/tắt: mặc định Đạt, bấm để đổi Đạt ⇄ Không đạt --}}
                        <div class="exec-check-result mb-3">
                            <button type="button" class="js-check-toggle active" data-result="pass"></button>
                        </div>

                        <div class="form-group mb-0">
                            <label for="execCheckNote" class="js-check-note-label">Ghi chú</label>
                            <textarea id="execCheckNote" class="form-control" rows="2"></textarea>
                            <small class="form-text text-muted js-check-hint"></small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Hủy</button>
                        <button type="submit" class="btn btn-success js-submit"><i class="fas fa-signature"></i> Xác
                            nhận kiểm tra</button>
                    </div>
                </form>
            </div>
        </div>

        {{-- ===== Nhãn tình trạng phòng (mẫu nhãn phòng eBMR), JS dựng nội dung ===== --}}
        <div class="modal fade rl-modal" id="execRoomLabelModal" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content border-0 bg-transparent" id="execRoomLabelBody"></div>
            </div>
        </div>

        <datalist id="execActivityList">
            @foreach ($activitySuggestions as $suggestion)
                <option value="{{ $suggestion }}">
            @endforeach
        </datalist>
        <datalist id="execPauseReasons">
            <option value="Hết ca">
            <option value="Nghỉ ăn">
            <option value="Chờ nguyên liệu / bán thành phẩm">
            <option value="Chờ kết quả IPC / QC">
            <option value="Máy hư">
            <option value="Không có nhân sự">
        </datalist>
    </div>
@endsection

@section('script')
    <script src="{{ asset('js/sweetalert2.all.min.js') }}"></script>
    <script>
        // jQuery/Bootstrap được layout nạp sau section này → chờ DOMContentLoaded
        document.addEventListener('DOMContentLoaded', function() {
            const R = {
                index: @json(route('pages.Schedual.execution.index')),
                plans: @json(route('pages.Schedual.execution.plans')),
                history: @json(route('pages.Schedual.execution.history')),
                room_label: @json(route('pages.Schedual.execution.room_label')),
                start: @json(route('pages.Schedual.execution.start')),
                execute: @json(route('pages.Schedual.execution.execute')),
                pause: @json(route('pages.Schedual.execution.pause')),
                resume: @json(route('pages.Schedual.execution.resume')),
                finish: @json(route('pages.Schedual.execution.finish')),
                clean_start: @json(route('pages.Schedual.execution.clean_start')),
                clean_end: @json(route('pages.Schedual.execution.clean_end')),
                clean_check: @json(route('pages.Schedual.execution.clean_check')),
                mark_dirty: @json(route('pages.Schedual.execution.mark_dirty')),
                undo: @json(route('pages.Schedual.execution.undo')),
                activity: @json(route('pages.Schedual.execution.activity')),
                activity_end: @json(route('pages.Schedual.execution.activity_end')),
                reroute_switch: @json(route('pages.Schedual.execution.reroute_switch')),
                equipment: @json(route('pages.Schedual.execution.equipment')),
                equipment_label: @json(route('pages.Schedual.execution.equipment_label')),
            };
            const PRODUCTION = @json($production);
            let rerouteOn = @json($rerouteEnabled);
            const STATE_META = @json($PES::STATE_META);
            const STATE_LABELS = @json($PES::STATE_LABELS);
            const STATE_ORDER = @json($PES::DISPLAY_ORDER);
            const S = {
                CLEAN: 1,
                PRODUCING: 2,
                NEED_CLEAN: 3,
                CLEANING: 4,
                EXPIRED: 5,
                PAUSED: 6,
                PREPARING: 7,
                AWAIT_CHECK: 8
            };

            @include('pages.Schedual.execution._live_js')
            const toInput = d =>
                `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`;

            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            let ctx = null; // card phòng đang thao tác
            let busy = false;

            /* ---------- Lưu bộ lọc cho lần mở sau (không bắt buộc) ---------- */
            const store = {
                get(k, d) {
                    try {
                        const v = localStorage.getItem('exec.' + k);
                        return v == null ? d : JSON.parse(v);
                    } catch (e) {
                        return d;
                    }
                },
                set(k, v) {
                    try {
                        localStorage.setItem('exec.' + k, JSON.stringify(v));
                    } catch (e) {}
                },
            };
            const filter = {
                stage: String(store.get('stage', 'all')),
                state: null,
                q: ''
            };
            const collapsed = new Set(store.get('collapsed', []).map(String));

            /* ---------- Hiển thị ---------- */

            function countChip(s, n) {
                return `<span class="exec-count st-${STATE_META[s][0]}" title="${esc(STATE_LABELS[s])}"><i class="fas ${STATE_META[s][1]}"></i> ${n}</span>`;
            }

            function updateCounts() {
                $('.exec-filter-state').each(function() {
                    $(this).find('b').text($(`.exec-room-col[data-state="${$(this).attr('data-state')}"]`)
                        .length);
                });
                $('.exec-stage').each(function() {
                    const $stage = $(this);
                    $stage.find('.exec-stage-counts').html(STATE_ORDER.map(s => {
                        const n = $stage.find(`.exec-room-col[data-state="${s}"]`).length;
                        return n ? countChip(s, n) : '';
                    }).join(''));
                });
            }

            function applyFilters() {
                $('.js-filter-stage').removeClass('active').filter(`[data-stage="${filter.stage}"]`).addClass(
                    'active');
                $('.exec-filter-state').removeClass('active').filter(`[data-state="${filter.state}"]`).addClass(
                    'active');

                $('.exec-stage').each(function() {
                    const $stage = $(this);
                    const stageOk = filter.stage === 'all' || $stage.attr('data-stage') === filter.stage;
                    let visible = 0;
                    $stage.find('.exec-room-col').each(function() {
                        const $c = $(this);
                        const ok = (!filter.state || $c.attr('data-state') === filter.state) &&
                            (!filter.q || ($c.attr('data-search') || '').includes(filter.q));
                        $c.toggleClass('d-none', !ok);
                        if (ok) visible++;
                    });
                    $stage.toggleClass('d-none', !stageOk || (visible === 0 && !!(filter.state || filter
                        .q)));
                    $stage.find('.exec-stage-empty').toggleClass('d-none', visible > 0);
                    $stage.toggleClass('collapsed', collapsed.has($stage.attr('data-stage')));
                });
            }

            function afterRender() {
                updateCounts();
                applyFilters();
                tick();
                clocks();
                paintEquip();
            }

            /* ---------- Nhãn HC-BT-TI thiết bị của phòng ---------- */
            const EQ_TYPES = {
                HC: 'Hiệu chuẩn',
                BT: 'Bảo trì',
                TI: 'Tiện ích'
            };
            const EQ_LEVEL = [{
                    btn: 'btn-success',
                    icon: 'fa-check-circle'
                },
                {
                    btn: 'btn-warning',
                    icon: 'fa-exclamation-triangle'
                },
                {
                    btn: 'btn-danger',
                    icon: 'fa-times-circle'
                },
            ];
            const EQ_LABEL_TITLE = {
                HC: 'NHÃN HIỆU CHUẨN THIẾT BỊ',
                BT: 'NHÃN BẢO TRÌ THIẾT BỊ',
                TI: 'NHÃN BẢO TRÌ TIỆN ÍCH'
            };
            let equipSummary = null; // room_id => {count, summary, issues}
            let equipFailed = false;
            let equipErrors = []; // lỗi kết nối CSDL hiệu chuẩn (cal1/cal2)
            let equipList = []; // thiết bị của phòng đang mở modal

            function loadEquipSummary(refresh) {
                $.get(R.equipment, {
                    refresh: refresh ? 1 : 0
                }).done(res => {
                    equipSummary = res.rooms || {};
                    equipErrors = res.errors || [];
                    equipFailed = false;
                    paintEquip();
                }).fail(() => {
                    equipFailed = true;
                    paintEquip();
                });
            }

            // Trên card: dòng đầu là tình trạng chung, bên dưới mỗi thiết bị lớn 1 chip mã thiết bị (màu theo tình trạng xấu nhất;
            // thiết bị chưa đạt có biểu tượng + loại nhãn bị lỗi). Hiện tối đa EQ_CHIP_MAX chip, thiết bị chưa đạt luôn hiện.
            const EQ_LEVEL_TEXT = ['Đạt', 'Đến hạn', 'Không đạt'];
            const EQ_CHIP_MAX = 12;

            function paintEquip() {
                $('.exec-room-col').each(function() {
                    const $status = $(this).find('.js-equip-status');
                    const $list = $(this).find('.js-equip-list');
                    if (!equipSummary) {
                        if (equipFailed) $status.html('<span class="exec-eq-none">Không tải được</span>');
                        return;
                    }
                    const s = equipSummary[this.id.replace('exec-room-', '')];
                    // Phòng có trong kết quả = đã gán thiết bị trong danh mục; rỗng là do không đọc được CSDL hiệu chuẩn
                    const offline = equipErrors.length ?
                        `<span class="exec-eq-state lv1" title="${escAttr(equipErrors.join('\n'))}"><i class="fas fa-plug"></i> Mất kết nối CSDL hiệu chuẩn</span>` :
                        '';
                    if (!s || !s.count) {
                        $status.html(s && offline ? offline :
                            (s ? '<span class="exec-eq-none">Thiết bị không còn hoạt động bên phần mềm hiệu chuẩn</span>' :
                                '<span class="exec-eq-none">Chưa gán thiết bị</span>'));
                        $list.empty();
                        return;
                    }

                    const n = [0, 1, 2].map(lv => s.equipments.filter(e => e.level === lv).length);
                    const state = n[2] ?
                        `<span class="exec-eq-state lv2"><i class="fas fa-times-circle"></i> ${n[2]} không đạt${n[1] ? `, ${n[1]} đến hạn` : ''}</span>` :
                        (n[1] ?
                            `<span class="exec-eq-state lv1"><i class="fas fa-exclamation-triangle"></i> ${n[1]} đến hạn</span>` :
                            '<span class="exec-eq-state lv0"><i class="fas fa-check-circle"></i> Đạt</span>'
                        );
                    // Mất kết nối 1 phần (ví dụ chỉ khối B2): thiết bị của CSDL đó không hiện, báo kèm biểu tượng
                    const partial = equipErrors.length ?
                        ` <i class="fas fa-plug text-warning" title="${escAttr('Thiếu dữ liệu: ' + equipErrors.join('; '))}"></i>` :
                        '';
                    $status.html(`<span class="text-muted">(${s.count})</span>${state}${partial}`);

                    // Danh sách đã xếp sẵn: chưa đạt → máy móc → tiện ích
                    const shown = s.equipments.filter((e, i) => e.level > 0 || i < EQ_CHIP_MAX);
                    const rest = s.equipments.slice(shown.length);
                    $list.html(shown.map(e => {
                        const bad = Object.keys(e.levels).filter(k => e.levels[k] > 0);
                        const title = `${e.code} – ${e.name}\n` + Object.keys(e.levels).map(k =>
                            `${EQ_TYPES[k]}: ${EQ_LEVEL_TEXT[e.levels[k]]}`).join(' · ');
                        return `<span class="exec-eq-chip lv${e.level}" data-code="${escAttr(e.code)}" title="${escAttr(title)}">` +
                            (e.level ? `<i class="fas ${EQ_LEVEL[e.level].icon}"></i> ` : '') +
                            esc(e.code) +
                            (bad.length ? ` <small>${bad.join(', ')}</small>` : '') + '</span>';
                    }).join('') + (rest.length ?
                        `<span class="exec-eq-chip more" title="${escAttr(rest.map(e => e.code).join(', '))}">+${rest.length}</span>` :
                        ''));
                });
            }

            /* ---------- Nhãn tình trạng phòng (mẫu eBMR: vàng "Cần vệ sinh", xanh "Đã vệ sinh") ---------- */
            function roomLabelHtml(d) {
                const v = (x, cls = 'rl-val') => x ? `<span class="${cls}">${esc(x)}</span>` :
                    '<span class="rl-empty">—</span>';
                const box = (on, text) =>
                    `<span class="rl-check"><span class="rl-box">${on ? '✓' : ''}</span>${text}</span>`;
                const cleaned = d.kind === 'cleaned';
                const levels = `<div class="rl-levels">
                        ${box(d.level === 'VS-I', 'Cấp / Level I')}
                        ${box(d.level === 'VS-II', 'Cấp / Level II')}
                        ${box(d.level === 'VS-LAI', 'Vệ sinh lại / Re-Cleaning')}
                    </div>`;
                const rule = cleaned ?
                    `Đánh dấu "✓" vào nội dung áp dụng / Check "✓" in used content<br>
                     Cấp / Level I: Hiệu lực trong vòng 3 ngày / valid for 3 days<br>
                     Cấp / Level II: Hiệu lực trong vòng 7 ngày / valid for 7 days<br>
                     Vệ sinh lại / Re-cleaning: Hiệu lực trong 24 giờ / valid for 24 hours` :
                    `Đánh dấu "✓" vào nội dung áp dụng / Check "✓" in used content<br>
                     Cấp / Level I: Phải thực hiện vệ sinh trong vòng 24 giờ / should be done within 24 hours<br>
                     Cấp / Level II: Phải thực hiện vệ sinh trong vòng 3 ngày / should be done within 3 days`;

                const body = cleaned ? `
                    ${levels}<div class="rl-rule">${rule}</div>
                    <div class="rl-grid">
                        <div><span class="rl-lbl">Thời gian hoàn tất vệ sinh /<br>Cleaning finished on:</span>Ngày / Date: ${v(d.finished_on)}</div>
                        <div><span class="rl-lbl">Hiệu lực đến /<br>Valid until:</span>Ngày / Date: ${v(d.valid_until, 'rl-val due')}</div>
                    </div>
                    <div class="rl-grid">
                        <div><span class="rl-lbl">Người thực hiện / Done by:</span>${v(d.done_by, 'rl-person')}</div>
                        <div><span class="rl-lbl">Người kiểm tra / Checked by:</span>${v(d.checked_by, 'rl-person checked')}</div>
                    </div>
                 
                    <table class="rl-next w-100">
                        <tr><td>Tên sản phẩm /<small>Product's name:</small></td><td>${v(d.next_product)}</td></tr>
                        <tr><td>Số lô /<small>Batch no.:</small></td><td>${v(d.next_batch)}</td></tr>
                        <tr><td>Người nhận /<small>Received by:</small></td><td>${v(d.received_by, 'rl-person')}</td></tr>
                    </table>` :
                    `
                    ${levels}<div class="rl-rule">${rule}</div>
                    <div class="rl-grid">
                        <div><span class="rl-lbl">Thời gian hoàn tất sản xuất /<br>Process finished on:</span>Ngày / Date: ${v(d.finished_on)}</div>
                        <div><span class="rl-lbl">Vệ sinh trước /<br>To be cleaned before:</span>Ngày / Date: ${v(d.before, 'rl-val due')}
                            ${d.overdue ? '<br><small class="text-danger font-weight-bold"><i class="fas fa-exclamation-triangle"></i> Đã quá hạn</small>' : ''}</div>
                    </div>
                    <div><span class="rl-lbl d-inline">Người thực hiện / Done by:</span> ${v(d.done_by, 'rl-person')}</div>`;

                return `<div class="rl-card ${cleaned ? 'cleaned' : 'to-clean'}">
                    <div class="rl-head">
                        <h5>NHÃN PHÒNG</h5><small>ROOM LABEL</small>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Đóng"><span aria-hidden="true">&times;</span></button>
                    </div>
                    <table class="rl-table">
                        <tr><th>Tên phòng<small>Name</small></th><td>${esc(d.room_name)}</td></tr>
                        <tr><th>Mã số / Code</th><td class="rl-code">${esc(d.room_code)}</td></tr>
                    </table>
                    <div class="rl-band">${cleaned ? 'ĐÃ VỆ SINH / CLEANED' : 'CẦN VỆ SINH / TO BE CLEANED'}</div>
                    ${d.note ? `<div class="rl-note ${d.overdue || d.pending || /quá hạn|hết hiệu lực/.test(d.note) ? 'warn' : ''}">${esc(d.note)}</div>` : ''}
                    <div class="rl-body">${body}</div>
                    <div class="rl-foot"><span>Trạng thái: <b>${esc(d.state_label)}</b></span><span>Cập nhật ${esc(d.generated_at)}</span></div>
                </div>`;
            }

            function openRoomLabel() {
                const $m = $('#execRoomLabelModal');
                $('#execRoomLabelBody').html(
                    '<div class="rl-card cleaned p-4 text-center text-muted"><i class="fas fa-spinner fa-spin"></i> Đang tải nhãn phòng...</div>'
                );
                $m.modal('show');
                $.get(R.room_label, {
                    room_id: ctx.room_id
                }).done(d => {
                    $('#execRoomLabelBody').html(roomLabelHtml(d));
                }).fail(xhr => {
                    $m.modal('hide');
                    Swal.fire('Lỗi', (xhr.responseJSON && xhr.responseJSON.message) ||
                        'Không tải được nhãn phòng', 'error');
                });
            }

            // focusCode: bấm vào 1 mã thiết bị trên card → mở sẵn nhãn xấu nhất của thiết bị đó
            function openEquipment(refresh, focusCode) {
                const $m = $('#execEquipModal');
                $m.find('.js-room').text(ctx.room);
                $m.find('.js-errors').addClass('d-none');
                $('#execEquipBody').html(
                    '<tr><td colspan="5" class="text-center text-muted py-4"><i class="fas fa-spinner fa-spin"></i> Đang tải dữ liệu từ phần mềm hiệu chuẩn...</td></tr>'
                );
                if (!$m.hasClass('show')) $m.modal('show');

                return $.get(R.equipment_label, {
                    room_id: ctx.room_id,
                    refresh: refresh ? 1 : 0
                }).done(res => {
                    $m.find('.js-generated').text(res.generated_at);
                    if (res.errors && res.errors.length) $m.find('.js-errors').removeClass('d-none').text(
                        res.errors.join('; '));
                    // Thiết bị có vấn đề lên đầu
                    const worst = e => Math.max(...Object.keys(EQ_TYPES).map(k => e[k] ? e[k].level : -1));
                    equipList = res.equipments.slice().sort((a, b) => worst(b) - worst(a) || a.code
                        .localeCompare(b.code));
                    if (!equipList.length) {
                        $('#execEquipBody').html(
                            '<tr><td colspan="5" class="text-center text-muted py-4">Phòng chưa được gán thiết bị trong danh mục Bảo trì hiệu chuẩn.</td></tr>'
                        );
                        return;
                    }
                    $('#execEquipBody').html(equipList.map((e, i) => `<tr>
                            <td><b>${esc(e.code)}</b></td>
                            <td>${esc(e.name)}</td>
                            ${Object.keys(EQ_TYPES).map(k => `<td class="text-center">${e[k] ?
                                                    `<button type="button" class="btn ${EQ_LEVEL[e[k].level].btn} js-label" data-index="${i}" data-type="${k}" title="Xem ${EQ_LABEL_TITLE[k].toLowerCase()}">
                                    <i class="fas ${EQ_LEVEL[e[k].level].icon}"></i> ${esc(e[k].text)}</button>` :
                                                    '<span class="text-muted">—</span>'}</td>`).join('')}
                        </tr>
                        <tr class="exec-label-row d-none" data-index="${i}"><td colspan="5"></td></tr>`).join(''));

                    const i = focusCode ? equipList.findIndex(e => e.code === focusCode) : -1;
                    if (i >= 0) {
                        const e = equipList[i];
                        const k = Object.keys(EQ_TYPES).filter(t => e[t]).sort((a, b) => e[b].level - e[a]
                            .level)[0];
                        const $btn = $(`#execEquipBody .js-label[data-index="${i}"][data-type="${k}"]`)
                            .trigger('click');
                        // Chờ modal hiện xong mới cuộn được
                        setTimeout(() => $btn[0].scrollIntoView({
                            block: 'start',
                            behavior: 'smooth'
                        }), 350);
                    }
                }).fail(xhr => {
                    $('#execEquipBody').html(
                        `<tr><td colspan="5" class="text-center text-danger py-4">${esc((xhr.responseJSON || {}).message || 'Không tải được dữ liệu thiết bị')}</td></tr>`
                    );
                });
            }

            // Nhãn 1 thiết bị, bố cục giống nhãn in bên eBMR
            function labelHtml(e, k) {
                const d = e[k];
                const hc = k === 'HC';
                const rowCls = lv => lv === 2 ? 'table-danger text-danger font-weight-bold' : (lv === 1 ?
                    'table-warning font-weight-bold' : '');
                const cols = hc ? ['STT', 'Mã số', 'Tên', 'Ngày hiệu chuẩn', 'Hạn sử dụng'] : ['STT', 'Mã số',
                    'Tên', 'Chu kỳ', 'Ngày bảo trì', 'Hạn bảo trì'
                ];
                const rows = d.items.length ? d.items.map((r, i) => `<tr class="${rowCls(r.level)}">
                        <td>${i + 1}.</td><td>${esc(r.id)}</td><td>${esc(r.name)}</td>${hc ? '' : `<td>${esc(r.cycle)}</td>`}
                        <td>${esc(r.done_on || '-')}</td><td>${esc(r.due || '-')}</td></tr>`).join('') :
                    `<tr><td colspan="${cols.length}" class="text-center text-muted">Không có lịch ${hc ? 'hiệu chuẩn' : 'bảo trì'} đang chờ</td></tr>`;

                return `<div class="exec-label lv${d.level}">
                    <div class="exec-label-head"><span><i class="fas fa-tag"></i> ${EQ_LABEL_TITLE[k]}</span><span>${esc(d.text)}</span></div>
                    <div class="exec-label-info">
                        <div><b>Tên thiết bị</b>: ${esc(e.name)}</div>
                        <div><b>Mã số thiết bị</b>: ${esc(e.code)}</div>
                    </div>
                    <table class="table table-sm table-bordered">
                        <thead><tr>${cols.map(c => `<th>${c}</th>`).join('')}</tr></thead>
                        <tbody>${rows}</tbody>
                    </table>
                </div>`;
            }

            $(document).on('click', '#execEquipBody .js-label', function() {
                const i = $(this).data('index');
                const k = $(this).data('type');
                const $row = $(`#execEquipBody .exec-label-row[data-index="${i}"]`);
                // Bấm lại đúng nhãn đang mở thì đóng
                if (!$row.hasClass('d-none') && $row.data('type') === k) {
                    $row.addClass('d-none');
                    return;
                }
                $row.data('type', k).removeClass('d-none').find('td').html(labelHtml(equipList[i], k));
            });

            $('#execEquipRefresh').on('click', function() {
                // Cache phía máy chủ vừa làm mới → cập nhật luôn tóm tắt trên các card
                openEquipment(true).done(() => loadEquipSummary(false));
            });

            function replaceCard(html) {
                if (!html) return;
                const $new = $($.parseHTML(html.trim())).filter('.exec-room-col');
                $('#' + $new.attr('id')).replaceWith($new);
                afterRender();
            }

            function refreshBoard(force) {
                if (!force && ($('.modal.show').length || busy)) return;
                $.get(R.index, {
                    partial: 1
                }).done(html => {
                    $('#execBoard').html(html);
                    $('#execUpdatedAt').text(toInput(serverNow()).slice(11));
                    afterRender();
                });
            }

            /* ---------- Gửi thao tác ---------- */
            function send(url, data, $modal) {
                if (busy) return;
                busy = true;
                const $btn = $modal ? $modal.find('.js-submit').prop('disabled', true) : $();

                $.ajax({
                        url,
                        type: 'POST',
                        data: Object.assign({
                            room_id: ctx.room_id,
                            token: ctx.token
                        }, data)
                    })
                    .done(res => {
                        replaceCard(res.html);
                        if ($modal) $modal.modal('hide');
                        if (res.reroute) return showReroute(res);
                        // titleText (không phải title) vì thông báo có thể chứa tên hoạt động do người dùng nhập
                        Swal.fire({
                            toast: true,
                            position: 'top-end',
                            icon: 'success',
                            titleText: res.message,
                            timer: 3000,
                            showConfirmButton: false
                        });
                    })
                    .fail(xhr => {
                        const res = xhr.responseJSON || {};
                        // 409: phòng vừa bị người khác đổi trạng thái → thay card mới, đóng modal
                        if (res.html) {
                            replaceCard(res.html);
                            if ($modal) $modal.modal('hide');
                        }
                        let msg = res.message || 'Có lỗi xảy ra, vui lòng thử lại';
                        if (xhr.status === 419) msg = 'Phiên làm việc đã hết hạn, vui lòng tải lại trang (F5).';
                        Swal.fire({
                            icon: xhr.status === 409 ? 'info' : 'warning',
                            title: 'Không thể thực hiện',
                            text: msg
                        });
                    })
                    .always(() => {
                        busy = false;
                        $btn.prop('disabled', false);
                    });
            }

            // Kết quả tịnh tuyến lịch sau khi kết thúc vệ sinh (công tắc của phân xưởng đang bật)
            function showReroute(res) {
                const r = res.reroute;
                if (r.error) {
                    return Swal.fire({
                        icon: 'warning',
                        title: 'Đã lưu vệ sinh',
                        text: res.message +
                            '. Tịnh tuyến lịch bị lỗi (đã ghi log), lịch lý thuyết chưa được dịch.'
                    });
                }
                if (!r.count && !r.cleaning_moved) {
                    return Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'success',
                        titleText: res.message + ' · Lịch không cần dịch',
                        timer: 3000,
                        showConfirmButton: false
                    });
                }
                Swal.fire({
                    icon: 'success',
                    title: 'Đã kết thúc vệ sinh',
                    html: `Lô hoàn thành ${r.delta < 0 ? 'sớm' : 'trễ'} <b>${Math.abs(r.delta)} phút</b> so với lịch lý thuyết.<br>` +
                        (r.cleaning_moved ? 'Đã dời vệ sinh của lô ra sau giờ kết thúc sản xuất.<br>' :
                            '') +
                        `Đã tịnh tuyến <b>${r.count}</b> lô liên quan.<br>` +
                        '<small>Xem chi tiết: chuột phải lên lô trên Lịch Sản Xuất → "Lịch sử tịnh tuyến".</small>',
                    confirmButtonText: 'Đóng',
                });
            }

            /* ---------- Công tắc tịnh tuyến lịch (lưu DB theo phân xưởng) ---------- */
            function renderRerouteHint(on, lastChange) {
                $('#execReroute').toggleClass('on', on);
                $('#execRerouteHint').text((on ?
                        `Đang bật cho ${PRODUCTION}: mỗi lần kết thúc vệ sinh sau lô (trang này) hoặc bấm ✓✓ (trang Xác nhận hoàn thành) sẽ tự dịch các lô liên quan trên lịch lý thuyết, bất kể ai thao tác.` :
                        `Đang tắt cho ${PRODUCTION}: xác nhận hoàn thành không ảnh hưởng đến lịch lý thuyết.`) +
                    (lastChange ? ` (Đổi lần cuối: ${lastChange})` : ''));
            }
            renderRerouteHint($('#execRerouteToggle').is(':checked'), @json($rerouteLastChange));

            $('#execRerouteToggle').on('change', function() {
                const toggle = this;
                const on = toggle.checked;
                toggle.disabled = true;
                $.ajax({
                        url: R.reroute_switch,
                        type: 'POST',
                        data: {
                            enabled: on ? 1 : 0
                        }
                    })
                    .done(res => {
                        rerouteOn = res.enabled;
                        renderRerouteHint(res.enabled, res.last_change);
                        Swal.fire({
                            toast: true,
                            position: 'top-end',
                            icon: 'success',
                            titleText: res.message,
                            timer: 3000,
                            showConfirmButton: false
                        });
                    })
                    .fail(xhr => {
                        toggle.checked = !on;
                        Swal.fire({
                            icon: 'warning',
                            title: 'Không lưu được công tắc',
                            text: (xhr.responseJSON || {}).message || 'Có lỗi xảy ra'
                        });
                    })
                    .always(() => {
                        toggle.disabled = false;
                    });
            });

            /* ---------- Mở phòng ---------- */
            // Cân NL chọn được nhiều lô cùng BTP (selected = danh sách id), công đoạn khác 1 lô
            let plans = [],
                selected = [];

            function openStart() {
                const $m = $('#execStartModal');
                $m.find('.js-room').text(ctx.room);
                $('#execPlanSearch').val('');
                $('#execPlanHint').text('');

                // Nhắc (không chặn) nếu thiết bị của phòng quá hạn hiệu chuẩn / bảo trì
                const eq = equipSummary && equipSummary[ctx.room_id];
                $('#execEquipWarn').toggleClass('d-none', !(eq && eq.issues.length)).html(eq && eq.issues.length ?
                    '<i class="fas fa-exclamation-triangle"></i> Thiết bị chưa đạt: <b>' + eq.issues.map(esc)
                    .join('; ') + '</b>. Kiểm tra trước khi sản xuất.' : '');
                selected = [];
                loadPlans();
                $m.modal('show');
            }

            function loadPlans() {
                $('#execPlanList').html(
                    '<div class="exec-plan-empty"><i class="fas fa-spinner fa-spin"></i> Đang tải danh sách lô...</div>'
                );
                $.get(R.plans, {
                    room_id: ctx.room_id,
                    scope: 'room'
                }).done(list => {
                    plans = list;
                    if (!list.length) {
                        $('#execPlanHint').text('Phòng chưa có lô nào được sắp lịch.');
                    }
                    renderPlans();
                }).fail(xhr => {
                    $('#execPlanList').html(
                        `<div class="exec-plan-empty text-danger">${esc((xhr.responseJSON || {}).message || 'Không tải được danh sách lô')}</div>`
                    );
                });
            }

            function renderPlans() {
                const q = $('#execPlanSearch').val().toLowerCase().trim();
                const rows = plans.filter(p => !q || `${p.product} ${p.batch} ${p.codes} ${p.room_code || ''}`
                    .toLowerCase().includes(q));
                if (!rows.length) {
                    $('#execPlanList').html('<div class="exec-plan-empty">Không có lô phù hợp.</div>');
                    return;
                }
                const scrollTop = $('#execPlanList').scrollTop();
                const multi = ctx.weighing;
                const btp = selectedBtp();
                // Số lô cùng lịch lý thuyết + cùng BTP (được chọn theo nhau)
                const sameCount = key => key ? plans.filter(x => x.plan_key === key).length : 0;
                $('#execPlanHint').html(multi ?
                    (selected.length ?
                        `Đã chọn <b>${selected.length}</b> lô · chỉ chọn thêm được lô cùng mã BTP <b>${esc(btp)}</b>` :
                        'Cân NL: chọn được nhiều lô cùng mã BTP; lô cùng lịch và cùng BTP được chọn theo nhau (bỏ chọn được).'
                    ) :
                    (plans.length ? '' : 'Phòng chưa có lô nào được sắp lịch.'));
                $('#execPlanList').html(rows.map(p => {
                    const on = selected.includes(p.id);
                    const off = multi && btp && p.btp !== btp;
                    const same = multi && sameCount(p.plan_key) > 1 ?
                        `<span class="exec-plan-same" title="Cùng lịch lý thuyết và cùng BTP">${sameCount(p.plan_key)} lô cùng lịch</span>` :
                        '';
                    const where = p.same_room ?
                        '<span class="badge badge-success">Lịch phòng này</span>' :
                        (p.room_code ?
                            `<span class="badge badge-secondary">Lịch phòng ${esc(p.room_code)}</span>` :
                            '<span class="badge badge-light">Chưa gán phòng</span>');
                    const qty = p.partial ?
                        `<span class="badge badge-warning">Đã XN ${num(p.confirmed)}/${num(p.theory)} ${esc(p.unit)}</span>` :
                        `<div class="small text-muted">LT: ${num(p.theory)} ${esc(p.unit)}</div>`;
                    return `<label class="exec-plan ${on ? 'selected' : ''} ${off ? 'disabled' : ''}" ${off ? 'title="Khác mã BTP với lô đã chọn"' : ''}>
                        <input type="${multi ? 'checkbox' : 'radio'}" name="execPlan" value="${p.id}" ${on ? 'checked' : ''} ${off ? 'disabled' : ''}>
                        <div class="exec-plan-main">
                            <div class="exec-plan-name">${esc(p.product)}${p.market ? ' - ' + esc(p.market) : ''}
                                ${p.is_val ? '<i class="fas fa-check-circle text-primary" title="Lô thẩm định"></i>' : ''}${same}</div>
                            <div class="exec-plan-meta">Lô <b>${esc(p.batch)}</b> · ${esc(p.codes)}</div>
                            <div class="exec-plan-meta">Lịch: ${p.start ? esc(p.start) + ' → ' + esc(p.end) : 'chưa sắp lịch'}</div>
                        </div>
                        <div class="exec-plan-side">${where}<br>${qty}</div>
                    </label>`;
                }).join('')).scrollTop(scrollTop); // chọn lô không nhảy danh sách về đầu
            }

            $('#execPlanSearch').on('input', renderPlans);
            // Mã BTP của các lô đang chọn (Cân NL chỉ chọn chung các lô cùng BTP)
            const selectedBtp = () => {
                const first = plans.find(x => x.id === selected[0]);
                return first ? first.btp : null;
            };

            $(document).on('change', 'input[name="execPlan"]', function() {
                const id = +this.value;
                if (!ctx.weighing) {
                    selected = [id];
                } else if (this.checked) {
                    const p = plans.find(x => x.id === id);
                    // Lô đầu tiên: các lô cùng lịch lý thuyết + cùng BTP được chọn theo (bỏ chọn được)
                    const auto = !selected.length && p.plan_key ? plans.filter(x => x.plan_key === p
                        .plan_key).map(x => x.id) : [];
                    selected = [...new Set([...selected, id, ...auto])];
                } else {
                    selected = selected.filter(x => x !== id);
                }
                renderPlans();
            });

            // Mở phòng: Chuẩn bị (BĐCM ghi sau, khi bấm Thực thi sản xuất trên card) hoặc Thực thi sản xuất ngay
            $('#execStartModal .js-start-mode').on('click', function() {
                const mode = $(this).data('mode');
                const chosen = plans.filter(x => selected.includes(x.id));
                if (!chosen.length) return Swal.fire({
                    icon: 'warning',
                    title: 'Chọn lô cần sản xuất'
                });

                const go = () => send(R.start, chosen.length > 1 ? {
                    stage_plan_ids: chosen.map(p => p.id),
                    mode
                } : {
                    stage_plan_id: chosen[0].id,
                    mode
                }, $('#execStartModal'));
                const elsewhere = chosen.filter(p => !p.same_room && p.room_code);
                if (!elsewhere.length) return go();

                Swal.fire({
                    icon: 'question',
                    title: 'Chạy lô ở phòng khác lịch?',
                    html: elsewhere.map(p =>
                            `Lô <b>${esc(p.product)} - ${esc(p.batch)}</b> đang được sắp lịch ở phòng <b>${esc(p.room_code)}</b>.`
                        ).join('<br>') +
                        `<br>Khi ghi nhận sản lượng, phòng thực tế sẽ là <b>${esc(ctx.room)}</b>.`,
                    showCancelButton: true,
                    confirmButtonText: 'Vẫn bắt đầu',
                    cancelButtonText: 'Chọn lại',
                }).then(r => {
                    if (r.isConfirmed) go();
                });
            });

            /* ---------- Tạm dừng / Kết thúc SX ---------- */
            function openYield(act) {
                const $m = $('#execYieldModal');
                const p = ctx.plan || {};
                const pause = act === 'pause';
                // Nhóm lô: bảng từng lô; lô đơn: các ô nhập như cũ
                $('#execYieldSingle').toggleClass('d-none', !!ctx.group);
                $('#execYieldGroup').toggleClass('d-none', !ctx.group);
                $m.find('.modal-dialog').toggleClass('modal-lg', !!ctx.group);
                $m.find('.exec-now').toggleClass('d-none', act === 'finish_paused');
                if (ctx.group) {
                    $('#execBoxModeGroup').addClass('d-none');
                    return openGroupYield(act);
                }
                const remain = Math.max(0, Math.round(((p.theory || 0) - (p.confirmed || 0)) * 100) / 100);

                $m.data('act', act);
                $m.find('.modal-header').attr('class', 'modal-header ' + (pause ? 'exec-mh-pause' :
                    'exec-mh-stop'));
                $m.find('.js-title').html(pause ? '<i class="fas fa-pause"></i> Tạm dừng sản xuất' :
                    '<i class="fas fa-flag-checkered"></i> Kết thúc sản xuất');
                $m.find('.js-room').text(ctx.room);
                $m.find('.js-batch').text(p.label || '');
                $m.find('.js-batch-info').html(
                    `Lý thuyết <b>${num(p.theory)}</b> · Đã xác nhận <b>${num(p.confirmed)}</b> · Còn lại <b>${num(remain)}</b> ${esc(p.unit)}`
                );
                $m.find('.js-unit').text(p.unit || '');
                $m.find('.js-submit').html(pause ? '<i class="fas fa-pause"></i> Tạm dừng & ghi sản lượng' :
                    '<i class="fas fa-flag-checkered"></i> Kết thúc sản xuất');
                $m.find('.js-submit').attr('class', 'btn js-submit ' + (pause ? 'btn-primary' : 'btn-danger'));

                $m.find('.js-yield-start').text(ctx.since ? fmtMs(new Date(ctx.since).getTime()) : '—');
                $m.find('.js-yield-run').attr('data-since', ctx.since || '');
                tick();
                $('#execYieldQty').val(pause ? '' : remain).attr('placeholder', 'Còn lại: ' + num(remain));
                $('#execYieldBoxes').val(1);
                $('#execBoxModeGroup').toggleClass('d-none', !(p.confirmed > 0));
                $('input[name="execBoxMode"]').prop('checked', false);
                boxTotal();
                $('#execYieldBatchGroup').toggleClass('d-none', !p.can_edit_batch);
                $('#execYieldBatch').val(p.batch || '');
                $('#execYieldReasonGroup').toggleClass('d-none', !pause);
                $('#execYieldReason').val('');
                $('#execYieldNote').val('');
                $m.modal('show');
            }

            $('#execYieldQty').on('input', function() {
                this.value = this.value.replace(',', '.').replace(/[^0-9.]/g, '').replace(/(\..*)\./g,
                    '$1');
            });
            $('#execYieldBoxes').on('input', function() {
                this.value = this.value.replace(/[^0-9]/g, '');
                boxTotal();
            });
            $('input[name="execBoxMode"]').on('change', boxTotal);

            // Tổng số thùng của lô sau lần này (cùng công thức với server)
            function boxTotal() {
                const p = (ctx && ctx.plan) || {};
                const $t = $('#execBoxModeGroup .js-box-total');
                if (!(p.confirmed > 0)) return $t.empty();
                const prev = Math.max(1, +p.boxes || 1);
                const n = Math.max(1, parseInt($('#execYieldBoxes').val(), 10) || 1);
                const mode = $('input[name="execBoxMode"]:checked').val();
                $t.html(mode ?
                    `Lô đang có <b>${prev}</b> thùng → tổng sau lần này: <b>${prev + n - (mode === 'continue' ? 1 : 0)}</b> thùng` :
                    `Lô đang có <b>${prev}</b> thùng · <span class="text-danger">chọn thùng mới hoặc dùng tiếp thùng đang sử dụng</span>`
                );
            }

            /* Nhóm lô cân chung: Tạm dừng = ghi sản lượng mọi lô đang chạy; Kết thúc = chọn lô kết thúc (còn lô thì phòng chạy tiếp) */
            function openGroupYield(act) {
                const $m = $('#execYieldModal');
                const pause = act === 'pause';
                const withYield = act !== 'finish_paused';
                const rows = ctx.group.filter(b => b.running);
                const editBatch = withYield && rows.some(b => b.can_edit_batch);
                const unit = rows.length ? rows[0].unit : '';

                $m.data('act', act);
                $m.find('.modal-header').attr('class', 'modal-header ' + (pause ? 'exec-mh-pause' :
                    'exec-mh-stop'));
                $m.find('.js-title').html(pause ? '<i class="fas fa-pause"></i> Tạm dừng sản xuất' :
                    '<i class="fas fa-flag-checkered"></i> Kết thúc lô');
                $m.find('.js-room').text(ctx.room);
                $m.find('.js-batch').text(`${ctx.group.length} lô cân chung · còn ${rows.length} lô đang chạy`);
                $m.find('.js-batch-info').html(pause ? 'Nhập sản lượng lần này của <b>từng lô</b>.' :
                    (withYield ?
                        'Chọn lô kết thúc và nhập sản lượng lần này của lô đó. Lô không chọn tiếp tục chạy; hết lô thì phòng chuyển Cần Vệ Sinh.' :
                        'Sản lượng đã ghi lúc tạm dừng. Chọn lô kết thúc; hết lô thì phòng chuyển Cần Vệ Sinh.')
                );
                $m.find('.js-submit').html(pause ? '<i class="fas fa-pause"></i> Tạm dừng & ghi sản lượng' :
                        '<i class="fas fa-flag-checkered"></i> Kết thúc lô đã chọn')
                    .attr('class', 'btn js-submit ' + (pause ? 'btn-primary' : 'btn-danger'));
                $m.find('.js-yield-start').text(ctx.since ? fmtMs(new Date(ctx.since).getTime()) : '—');
                $m.find('.js-yield-run').attr('data-since', ctx.since || '');
                tick();

                $('#execYieldGroup').html(`<table class="exec-group-yield">
                    <thead><tr>
                        ${pause ? '' : '<th title="Kết thúc lô này"><input type="checkbox" class="js-gy-all" checked></th>'}
                        <th>Lô</th>
                        ${withYield ? `<th>Sản lượng lần này (${esc(unit)})</th><th>Số thùng</th><th>Thùng</th>` : ''}
                        ${editBatch ? '<th>Số lô TT</th>' : ''}
                    </tr></thead>
                    <tbody>${rows.map(b => `<tr data-id="${b.id}">
                                            ${pause ? '' : '<td><input type="checkbox" class="js-gy-on" checked></td>'}
                                            <td><b>${esc(b.batch)}</b><small>LT ${num(b.theory)} · đã XN ${num(b.confirmed)}</small></td>
                                            ${withYield ? `
                            <td><input type="text" inputmode="decimal" class="form-control js-gy-qty" autocomplete="off"
                                placeholder="Còn ${num(Math.max(0, b.theory - b.confirmed))}"
                                value="${pause ? '' : Math.max(0, Math.round((b.theory - b.confirmed) * 100) / 100)}"></td>
                            <td style="width:80px"><input type="text" inputmode="numeric" class="form-control js-gy-boxes" value="1" autocomplete="off"></td>
                            <td>${b.confirmed > 0 ? `<select class="form-control js-gy-mode" title="Lô đang có ${b.boxes} thùng">
                                                        <option value="">— chọn —</option>
                                                        <option value="continue">Dùng tiếp thùng đang dùng</option>
                                                        <option value="new">Thùng mới</option></select>` : '<small>thùng mới</small>'}</td>` : ''}
                                            ${editBatch ? `<td>${b.can_edit_batch ? `<input type="text" class="form-control js-gy-batch" value="${escAttr(b.batch || '')}" style="width:100px">` : ''}</td>` : ''}
                                        </tr>`).join('')}</tbody></table>`);

                $('#execYieldReasonGroup').toggleClass('d-none', !pause);
                $('#execYieldReason').val('');
                $('#execYieldNote').val('');
                $m.modal('show');
            }

            $(document).on('input', '.js-gy-qty', function() {
                this.value = this.value.replace(',', '.').replace(/[^0-9.]/g, '').replace(/(\..*)\./g,
                    '$1');
            });
            $(document).on('input', '.js-gy-boxes', function() {
                this.value = this.value.replace(/[^0-9]/g, '');
            });
            $(document).on('change', '.js-gy-on', function() {
                $(this).closest('tr').toggleClass('off', !this.checked).find('input:not(.js-gy-on), select')
                    .prop('disabled', !this.checked);
            });
            $(document).on('change', '.js-gy-all', function() {
                $('.js-gy-on').prop('checked', this.checked).trigger('change');
            });

            function submitGroupYield($m) {
                const act = $m.data('act');
                const pause = act === 'pause';
                const withYield = act !== 'finish_paused';
                const data = {
                    note: $('#execYieldNote').val(),
                    batches: {},
                    finish_ids: []
                };
                const byId = Object.fromEntries(ctx.group.map(b => [b.id, b]));
                for (const tr of $('#execYieldGroup tbody tr').toArray()) {
                    const $tr = $(tr);
                    const b = byId[$tr.data('id')];
                    if (!pause && !$tr.find('.js-gy-on').prop('checked')) continue;
                    if (!pause) data.finish_ids.push(b.id);
                    if (!withYield) continue;
                    const qty = $tr.find('.js-gy-qty').val().trim();
                    if (qty === '') return Swal.fire({
                        icon: 'warning',
                        title: `Nhập sản lượng lần này của lô ${b.batch}`
                    });
                    const mode = $tr.find('.js-gy-mode').val();
                    if (b.confirmed > 0 && !mode) return Swal.fire({
                        icon: 'warning',
                        title: `Lô ${b.batch}: chọn "Thùng mới" hoặc "Dùng tiếp thùng đang dùng"`
                    });
                    const row = {
                        yields: qty,
                        number_of_boxes: $tr.find('.js-gy-boxes').val() || 1,
                        box_mode: mode || ''
                    };
                    const $batch = $tr.find('.js-gy-batch');
                    if ($batch.length && $batch.val().trim() !== String(b.batch || '')) row.actual_batch = $batch
                        .val().trim();
                    data.batches[b.id] = row;
                }
                if (!pause && !data.finish_ids.length) return Swal.fire({
                    icon: 'warning',
                    title: 'Chọn lô cần kết thúc'
                });
                if (pause) data.reason = $('#execYieldReason').val();

                const over = withYield ? ctx.group.filter(b => data.batches[b.id] && b.theory > 0 && (+data.batches[
                    b.id].yields + b.confirmed) > b.theory) : [];
                const go = () => send(pause ? R.pause : R.finish, data, $m);
                if (!over.length) return go();
                Swal.fire({
                    icon: 'warning',
                    title: 'Kiểm tra lại',
                    html: 'Tổng sản lượng vượt sản lượng lý thuyết (tối đa 105%): lô <b>' + over.map(b =>
                        esc(b.batch)).join(', ') + '</b><br><br><b>Vẫn tiếp tục xác nhận?</b>',
                    showCancelButton: true,
                    confirmButtonText: 'Vẫn tiếp tục',
                    cancelButtonText: 'Kiểm tra lại',
                    confirmButtonColor: '#d33',
                }).then(r => {
                    if (r.isConfirmed) go();
                });
            }

            $('#execYieldModal .js-submit').on('click', function() {
                const $m = $('#execYieldModal');
                if (ctx.group) return submitGroupYield($m);
                const act = $m.data('act');
                const p = ctx.plan || {};
                const qty = $('#execYieldQty').val().trim();
                if (qty === '') return Swal.fire({
                    icon: 'warning',
                    title: 'Nhập sản lượng của lần này'
                });
                const boxMode = $('input[name="execBoxMode"]:checked').val();
                if (p.confirmed > 0 && !boxMode) return Swal.fire({
                    icon: 'warning',
                    title: 'Chọn "Thùng mới" hoặc "Dùng tiếp thùng đang sử dụng"'
                });

                const data = {
                    yields: qty,
                    number_of_boxes: $('#execYieldBoxes').val() || 1,
                    box_mode: boxMode || '',
                    note: $('#execYieldNote').val(),
                };
                if (act === 'pause') data.reason = $('#execYieldReason').val();
                if (p.can_edit_batch && $('#execYieldBatch').val().trim() !== String(p.batch || '')) data
                    .actual_batch = $('#execYieldBatch').val().trim();

                // Cảnh báo bất thường như trang Xác nhận hoàn thành, vẫn cho tiếp tục
                const warnings = [];
                const days = (serverNow() - new Date(ctx.since)) / 86400000;
                if (days >= 30) warnings.push('Khoảng BĐCM - KT đang lớn hơn hoặc bằng 30 ngày.');
                if (p.theory > 0 && (+qty + (p.confirmed || 0)) > p.theory) warnings.push(
                    'Tổng sản lượng vượt sản lượng lý thuyết (tối đa 105%).');

                const go = () => send(act === 'pause' ? R.pause : R.finish, data, $m);
                if (!warnings.length) return go();
                Swal.fire({
                    icon: 'warning',
                    title: 'Kiểm tra lại',
                    html: warnings.join('<br>') + '<br><br><b>Vẫn tiếp tục xác nhận?</b>',
                    showCancelButton: true,
                    confirmButtonText: 'Vẫn tiếp tục',
                    cancelButtonText: 'Kiểm tra lại',
                    confirmButtonColor: '#d33',
                }).then(r => {
                    if (r.isConfirmed) go();
                });
            });

            /* ---------- Thao tác xác nhận theo giờ hệ thống ---------- */
            let actionOpts = null;

            function openAction(o) {
                actionOpts = o;
                const $m = $('#execActionModal');
                $m.find('.modal-header').attr('class', 'modal-header ' + (o.header || 'exec-mh-navy'));
                $m.find('.js-title').html(o.title);
                $m.find('.js-room').text(ctx.room);
                $m.find('.js-msg').html(o.msg || '').toggleClass('d-none', !o.msg);
                $m.find('.js-time-label').text(o.timeLabel || 'Thời gian');
                $('#execActionLevelGroup').toggleClass('d-none', !o.level);
                if (o.level) $('#execActionLevel').val(o.level);
                $('#execActionNoteGroup').toggleClass('d-none', !o.note);
                $('#execActionNoteLabel').text(o.note || '');
                $('#execActionNote').val('');
                $m.find('.js-submit').html(o.submit || 'Xác nhận').attr('class', 'btn js-submit ' + (o.btn ||
                    'btn-primary'));
                $m.modal('show');
            }

            $('#execActionModal .js-submit').on('click', function() {
                const o = actionOpts;
                const data = Object.assign({}, o.extra || {});
                if (o.level) data.level = $('#execActionLevel').val();
                if (o.note) {
                    data.note = $('#execActionNote').val().trim();
                    if (o.noteRequired && !data.note) return Swal.fire({
                        icon: 'warning',
                        title: 'Nhập ' + o.note.toLowerCase()
                    });
                }
                send(o.url, data, $('#execActionModal'));
            });

            /* ---------- Kiểm tra vệ sinh ---------- */
            let checkResult = null;

            function openCheck(planMsg) {
                const $m = $('#execCheckModal');
                checkResult = null;
                $m.find('.js-room').text(ctx.room);
                $m.find('.js-check-info').html((planMsg ? 'Vệ sinh sau lô ' + planMsg : 'Vệ sinh không gắn lô') +
                    `<div class="small mt-1">Kết thúc vệ sinh lúc <b>${esc(ctx.since ? ctx.since.replace('T', ' ') : '—')}</b> · cấp <b>${esc(ctx.level || '—')}</b></div>`
                );
                $('#execCheckUser, #execCheckPass, #execCheckNote').val('');
                setCheckResult('pass');
                $m.modal('show');
            }

            function paintCheck() {
                const fail = checkResult === 'fail';
                $('.js-check-note-label').text(fail ? 'Lý do không đạt (bắt buộc)' : 'Ghi chú');
                $('.js-check-hint').text(checkResult === 'pass' ?
                    'Đạt: phòng chuyển sang Phòng Sạch, hạn sạch tính từ lúc kết thúc vệ sinh.' :
                    (fail ? 'Không đạt: phòng quay lại Đang Vệ Sinh để tiếp tục vệ sinh.' : ''));
                $('#execCheckModal .js-submit').attr('class', 'btn js-submit ' + (fail ? 'btn-danger' :
                    'btn-success'));
            }

            $('#execCheckModal').on('shown.bs.modal', () => $('#execCheckUser').trigger('focus'))
                .on('hidden.bs.modal', () => $('#execCheckPass').val(''));

            $('#execCheckModal .js-check-toggle').on('click', function() {
                setCheckResult(checkResult === 'pass' ? 'fail' : 'pass');
                if (checkResult === 'fail') $('#execCheckNote').trigger('focus');
            });

            function setCheckResult(result) {
                checkResult = result;
                $('#execCheckModal .js-check-toggle').attr('data-result', result).html(result === 'pass' ?
                    '<i class="fas fa-check-circle"></i> Đạt <small>(bấm để đổi sang Không đạt)</small>' :
                    '<i class="fas fa-times-circle"></i> Không đạt <small>(bấm để đổi sang Đạt)</small>');
                paintCheck();
            }

            $('#execCheckForm').on('submit', function(ev) {
                ev.preventDefault();
                const data = {
                    username: $('#execCheckUser').val().trim(),
                    password: $('#execCheckPass').val(),
                    result: checkResult,
                    note: $('#execCheckNote').val().trim(),
                };
                if (!data.username || !data.password) return Swal.fire({
                    icon: 'warning',
                    title: 'Nhập tài khoản và mật khẩu của người kiểm tra'
                });
                if (!data.result) return Swal.fire({
                    icon: 'warning',
                    title: 'Chọn kết quả Đạt hoặc Không đạt'
                });
                if (data.result === 'fail' && !data.note) return Swal.fire({
                    icon: 'warning',
                    title: 'Nhập lý do không đạt'
                });
                send(R.clean_check, data, $('#execCheckModal'));
            });

            /* ---------- Hoạt động khác ---------- */
            function openActivity() {
                const $m = $('#execActivityModal');
                $m.find('.js-room').text(ctx.room);
                $('#execActName, #execActNote').val('');
                $m.modal('show');
            }

            $('#execActivityModal .js-submit').on('click', function() {
                const name = $('#execActName').val().trim();
                if (!name) return Swal.fire({
                    icon: 'warning',
                    title: 'Nhập tên hoạt động'
                });
                send(R.activity, {
                    in_production: name,
                    notification: $('#execActNote').val().trim(),
                }, $('#execActivityModal'));
            });

            /* ---------- Lịch sử ---------- */
            function openHistory() {
                const $m = $('#execHistoryModal');
                $m.find('.js-room').text(ctx.room);
                $('#execHistoryBody').html(
                    '<tr><td colspan="5" class="text-center text-muted"><i class="fas fa-spinner fa-spin"></i> Đang tải...</td></tr>'
                );
                $m.modal('show');
                $.get(R.history, {
                    room_id: ctx.room_id
                }).done(rows => {
                    if (!rows.length) {
                        $('#execHistoryBody').html(
                            '<tr><td colspan="5" class="text-center text-muted">Chưa có lịch sử.</td></tr>'
                        );
                        return;
                    }
                    $('#execHistoryBody').html(rows.map(r => `<tr class="${r.cancelled ? 'exec-cancelled' : ''}">
                        <td>${r.kind === 'state' && STATE_META[r.state] ? countChip(r.state, esc(r.label)) : '<span class="badge badge-warning">Hoạt động</span> ' + esc(r.label)}</td>
                        <td>${esc(r.detail)}${r.cancelled ? `<div class="text-danger small">${esc(r.cancelled)}</div>` : ''}</td>
                        <td>${esc(r.start)}</td>
                        <td>${r.end ? esc(r.end) : '<i class="text-muted">đang diễn ra</i>'}</td>
                        <td class="small">${esc(r.by)}${r.end_by && r.end_by !== r.by ? ' → ' + esc(r.end_by) : ''}</td>
                    </tr>`).join(''));
                });
            }

            /* ---------- Nút trên card phòng ---------- */
            $(document).on('click', '.js-act', function(ev) {
                ctx = JSON.parse($(this).closest('.exec-room-col').attr('data-ctx'));
                // Nhóm lô cân chung: liệt kê mọi số lô (vệ sinh / kiểm tra ghi cho cả nhóm)
                const planMsg = ctx.plan ? `<b>${esc(ctx.plan_label || ctx.plan.label)}</b>` : '';
                const groupClean = ctx.group ?
                    `<div class="small mt-1"><i class="fas fa-layer-group"></i> ${ctx.group.length} lô cân chung: thời gian vệ sinh được ghi cho tất cả các lô.</div>` :
                    '';

                switch ($(this).data('act')) {
                    case 'start':
                        return openStart();
                    case 'execute':
                        return openAction({
                            url: R.execute,
                            header: 'exec-mh-go',
                            btn: 'btn-success',
                            title: '<i class="fas fa-play"></i> Thực thi sản xuất',
                            msg: planMsg +
                                '<div class="small mt-1">Kết thúc chuẩn bị, bắt đầu tạo ra sản lượng: lần khai báo sản lượng đầu tiên tính từ lúc này.</div>',
                            timeLabel: 'Thời gian bắt đầu tạo ra sản lượng (BĐCM)',
                            submit: '<i class="fas fa-play"></i> Thực thi sản xuất',
                        });
                    case 'pause':
                    case 'finish':
                        return openYield($(this).data('act'));
                    case 'resume':
                        return openAction({
                            url: R.resume,
                            header: 'exec-mh-go',
                            btn: 'btn-success',
                            title: '<i class="fas fa-play"></i> Bắt đầu lại sản xuất',
                            msg: planMsg,
                            timeLabel: 'Thời gian bắt đầu lại (BĐCM của lần xác nhận tiếp theo)',
                            submit: '<i class="fas fa-play"></i> Bắt đầu lại',
                        });
                    case 'finish_paused':
                        if (ctx.group) return openYield('finish_paused');
                        return openAction({
                            url: R.finish,
                            header: 'exec-mh-stop',
                            btn: 'btn-danger',
                            title: '<i class="fas fa-flag-checkered"></i> Kết thúc sản xuất',
                            msg: planMsg +
                                '<div class="small mt-1">Lô đang tạm dừng: sản lượng đã được ghi nhận lúc tạm dừng. Phòng sẽ chuyển sang Cần Vệ Sinh.</div>',
                            timeLabel: 'Thời gian kết thúc',
                            submit: '<i class="fas fa-flag-checkered"></i> Kết thúc SX',
                        });
                    case 'clean_start':
                        return openAction({
                            url: R.clean_start,
                            header: 'exec-mh-clean',
                            btn: 'btn-warning',
                            title: '<i class="fas fa-broom"></i> ' + (ctx.display === S.EXPIRED ?
                                'Vệ sinh lại phòng' : 'Bắt đầu vệ sinh'),
                            msg: ctx.display === S.EXPIRED ?
                                'Phòng đã quá hạn sạch, cần vệ sinh lại trước khi sản xuất.' : (
                                    planMsg ? 'Vệ sinh sau lô ' + planMsg + groupClean : ''),
                            timeLabel: 'Thời gian bắt đầu vệ sinh',
                            level: ctx.display === S.EXPIRED ? 'VS-LAI' : (ctx.level || 'VS-II'),
                            submit: '<i class="fas fa-broom"></i> Bắt đầu vệ sinh',
                        });
                    case 'clean_end':
                        return openAction({
                            url: R.clean_end,
                            header: 'exec-mh-cleanend',
                            btn: 'btn-warning',
                            title: '<i class="fas fa-check-double"></i> Kết thúc vệ sinh',
                            msg: (planMsg ? 'Vệ sinh sau lô ' + planMsg + groupClean :
                                    'Vệ sinh không gắn lô') +
                                '<div class="small mt-1"><i class="fas fa-user-check"></i> Phòng chuyển sang <b>Chờ Kiểm Tra</b>. Người khác kiểm tra Đạt thì mới ghi nhận thời gian vệ sinh' +
                                (planMsg ? ' của lô (tương đương ✓✓)' : ' vào Báo cáo ngày') +
                                ' và phòng mới thành Phòng Sạch.</div>' +
                                (planMsg && rerouteOn ?
                                    '<div class="small mt-1 text-danger"><i class="fas fa-random"></i> Điều chỉnh lịch theo thời gian thực đang bật: lịch lý thuyết được dịch khi kiểm tra Đạt.</div>' :
                                    ''),
                            timeLabel: 'Thời gian kết thúc vệ sinh',
                            note: 'Ghi chú',
                            submit: '<i class="fas fa-check-double"></i> Kết thúc vệ sinh',
                        });
                    case 'clean_check':
                        return openCheck(planMsg ? planMsg + groupClean : '');
                    case 'mark_dirty':
                        return openAction({
                            url: R.mark_dirty,
                            header: 'exec-mh-stop',
                            btn: 'btn-danger',
                            title: '<i class="fas fa-ban"></i> Chuyển phòng sang Cần Vệ Sinh',
                            msg: 'Dùng khi phòng không còn đảm bảo sạch (sau bảo trì, sự cố...).',
                            timeLabel: 'Thời gian',
                            note: 'Lý do',
                            noteRequired: true,
                            submit: 'Xác nhận',
                        });
                    case 'activity_end':
                        return openAction({
                            url: R.activity_end,
                            header: 'exec-mh-navy',
                            title: '<i class="fas fa-flag-checkered"></i> Kết thúc hoạt động',
                            msg: '<b>' + esc($(this).data('activity-name')) + '</b>',
                            timeLabel: 'Thời gian kết thúc',
                            extra: {
                                activity_id: $(this).data('activity-id')
                            },
                            submit: 'Kết thúc',
                        });
                    case 'undo':
                        return Swal.fire({
                            icon: 'question',
                            title: 'Hủy thao tác vừa rồi?',
                            html: `Phòng <b>${esc(ctx.room)}</b> sẽ trở về trạng thái trước đó.`,
                            showCancelButton: true,
                            confirmButtonText: 'Hủy thao tác',
                            cancelButtonText: 'Không',
                            confirmButtonColor: '#dc2626',
                        }).then(r => {
                            if (r.isConfirmed) send(R.undo, {}, null);
                        });
                    case 'activity':
                        return openActivity();
                    case 'history':
                        return openHistory();
                    case 'label':
                        return openRoomLabel();
                    case 'equipment':
                        return openEquipment(false, $(ev.target).closest('[data-code]').attr('data-code'));
                }
            });

            /* ---------- Bộ lọc, thu gọn, làm mới ---------- */
            $(document).on('click', '.js-filter-stage', function() {
                filter.stage = String($(this).data('stage'));
                store.set('stage', filter.stage);
                applyFilters();
            });
            $(document).on('click', '.exec-filter-state', function() {
                const s = $(this).attr('data-state');
                filter.state = filter.state === s ? null : s;
                applyFilters();
            });
            $('#execSearch').on('input', function() {
                filter.q = this.value.toLowerCase().trim();
                applyFilters();
            });
            $(document).on('click', '[data-toggle-stage]', function() {
                const key = $(this).closest('.exec-stage').attr('data-stage');
                collapsed.has(key) ? collapsed.delete(key) : collapsed.add(key);
                store.set('collapsed', [...collapsed]);
                applyFilters();
            });
            $('#execRefresh').on('click', () => refreshBoard(true));

            if (!$(`.js-filter-stage[data-stage="${filter.stage}"]`).length) filter.stage = 'all';
            afterRender();
            loadEquipSummary(false);
            setInterval(tick, 30000);
            setInterval(clocks, 1000);
            setInterval(() => refreshBoard(false), 120000);
            setInterval(() => loadEquipSummary(false), 600000);
        });
    </script>
@endsection
