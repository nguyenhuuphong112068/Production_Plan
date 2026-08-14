{{--
    Cảnh báo nguồn NL ở màn tạo lô / sửa lô.

    Đối chiếu mã NL của công thức với ma trận đã khai ở "Danh Mục > Cảnh Báo Nguồn NL":
    mã NL nào nằm trong ma trận mà thị trường của lô không được phép thì cảnh báo.
    Đây là cảnh báo, không chặn lưu.
--}}
<style>
    .msw-plan-flag {
        display: inline-block;
        margin-top: 3px;
        padding: 1px 7px;
        border-radius: 8px;
        font-size: 11px;
        font-weight: 600;
        white-space: nowrap;
    }

    .msw-plan-flag-ok {
        background: #eaf7ee;
        border: 1px solid #28a745;
        color: #1c7430;
    }

    .msw-plan-flag-warning {
        background: #fdecea;
        border: 1px solid #dc3545;
        color: #a71d2a;
    }

    tr.msw-plan-row-warning>td {
        background-color: #fdecea !important;
    }

    /* Ô chọn bị khoá do sai thị trường */
    input[type="checkbox"].msw-plan-locked {
        cursor: not-allowed;
        opacity: .45;
    }
</style>

<script>
    const MSW_PLAN_CHECK_URL = "{{ route('pages.category.material_source_warning.marketCheck') }}";

    /** Mã NL của 1 dòng công thức - lấy từ name="materials[<mã NL>][active]" nên dùng được cho cả 2 modal */
    function mswPlanMaterialCode($checkbox) {
        const matched = /^materials\[(.+)\]\[active\]$/.exec($checkbox.attr('name') || '');

        return matched ? matched[1] : '';
    }

    /** Nội dung cảnh báo của 1 mã NL không đúng thị trường */
    function mswPlanViolationLine(code, warning) {
        const name = warning.material_name ? ' - ' + warning.material_name : '';
        const markets = warning.markets.length ? warning.markets.join(', ') : 'chưa khai báo thị trường';

        return '<li style="text-align:left"><b>' + code + '</b>' + name +
            '<br><span style="color:#a71d2a">Chỉ được sản xuất cho: ' + markets + '</span>' +
            (warning.note ? '<br><i>' + warning.note + '</i>' : '') +
            '</li>';
    }

    function mswPlanShowAlert(marketLabel, lines) {
        Swal.fire({
            icon: 'warning',
            title: 'Cảnh Báo Nguồn Nguyên Liệu',
            html: 'Lô đang tạo cho thị trường <b>' + marketLabel + '</b>, nhưng có nguyên liệu ' +
                'đã khai báo trong ma trận cảnh báo nguồn NL cho thị trường khác:' +
                '<ul>' + lines.join('') + '</ul>' +
                '<span style="font-size:13px">Các nguyên liệu này đã được <b>bỏ chọn và khoá</b> để không dùng cho lô.</span>',
            confirmButtonText: 'Đã hiểu'
        });
    }

    /**
     * Cảnh báo cấp mã BTP: dòng ma trận khai thị trường nhưng không khai mã NL
     * thì ràng buộc cả sản phẩm, không gắn với nguyên liệu nào để bỏ tick.
     */
    function mswPlanShowGeneralAlert(marketLabel, generalWarnings) {
        const lines = generalWarnings.map(function(warning) {
            const markets = warning.markets.length ? warning.markets.join(', ') : 'chưa khai báo thị trường';

            return '<li style="text-align:left"><span style="color:#a71d2a">Chỉ được sản xuất cho: ' +
                markets + '</span>' + (warning.note ? '<br><i>' + warning.note + '</i>' : '') + '</li>';
        });

        Swal.fire({
            icon: 'warning',
            title: 'Cảnh Báo Nguồn Nguyên Liệu',
            html: 'Lô đang tạo cho thị trường <b>' + marketLabel + '</b>, nhưng mã BTP này ' +
                'đã khai báo trong ma trận cảnh báo nguồn NL chỉ dành cho thị trường khác ' +
                '(áp dụng cho mọi nguồn NL):' +
                '<ul>' + lines.join('') + '</ul>' +
                '<span style="font-size:13px">Vui lòng kiểm tra lại trước khi lưu.</span>',
            confirmButtonText: 'Đã hiểu'
        });
    }

    /**
     * Đối chiếu công thức của 1 modal với ma trận cảnh báo nguồn NL.
     *
     * @param {jQuery} $tbody  tbody của bảng công thức nguyên liệu trong modal đang mở
     * @param {Object} params  {product_caterogy_id, intermediate_code} khi tạo lô hoặc {plan_master_id} khi sửa lô
     */
    function mswPlanCheckMaterials($tbody, params) {
        $tbody.find('.msw-plan-flag').remove();
        $tbody.find('tr').removeClass('msw-plan-row-warning');
        // Mở lại các ô đã khoá của lần kiểm tra trước (đổi mã BTP / mở lại modal)
        $tbody.find('input[type="checkbox"].msw-plan-locked')
            .removeClass('msw-plan-locked')
            .prop('disabled', false);

        $.ajax({
            url: MSW_PLAN_CHECK_URL,
            type: 'GET',
            data: params,
            success: function(res) {
                const warnings = res.warnings || {};
                const generalWarnings = res.general_warnings || [];

                if (Object.keys(warnings).length === 0 && generalWarnings.length === 0) {
                    return;
                }

                const marketLabel = res.market ?
                    res.market.code + ' - ' + res.market.name :
                    'chưa xác định';
                const violations = [];

                $tbody.find('input[type="checkbox"]').each(function() {
                    const $checkbox = $(this);
                    const code = mswPlanMaterialCode($checkbox);
                    const warning = warnings[code];

                    if (!warning) {
                        return;
                    }

                    const $row = $checkbox.closest('tr');
                    // Cột thứ 3 của cả 2 bảng công thức là tên nguyên liệu
                    const $nameCell = $row.find('td').eq(2);

                    if (warning.allowed) {
                        $('<div class="msw-plan-flag msw-plan-flag-ok">')
                            .text('Nguồn NL: ' + marketLabel + ' được phép')
                            .appendTo($nameCell);
                        return;
                    }

                    $('<div class="msw-plan-flag msw-plan-flag-warning">')
                        .text('Nguồn NL: chỉ cho ' + (warning.markets.join(', ') || 'thị trường chưa khai báo') +
                            ' - đã bỏ chọn')
                        .appendTo($nameCell);

                    // Không đúng thị trường -> bỏ tick và khoá luôn ô chọn để không dùng cho lô.
                    // Input hidden active=0 đi kèm mỗi dòng nên ô bị disable vẫn lưu đúng trạng thái không dùng.
                    $checkbox
                        .prop('checked', false)
                        .prop('disabled', true)
                        .addClass('msw-plan-locked')
                        .attr('title', 'Không dùng được cho thị trường ' + marketLabel +
                            ' theo ma trận cảnh báo nguồn NL');

                    $row.addClass('msw-plan-row-warning');
                    violations.push(mswPlanViolationLine(code, warning));
                });

                if (violations.length) {
                    mswPlanShowAlert(marketLabel, violations);
                } else if (generalWarnings.length) {
                    // Không có NL nào bị khoá thì mới hiện cảnh báo cấp mã BTP để tránh 2 popup chồng nhau
                    mswPlanShowGeneralAlert(marketLabel, generalWarnings);
                }
            }
        });
    }
</script>
