<!-- Modal -->
<div class="modal fade" id="UserPermissionModal" tabindex="-1" role="dialog" aria-labelledby="UserPermissionLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">

        <div class="modal-content">
            <div class="modal-header">
                <a href="{{ route('pages.general.home') }}">
                    <img src="{{ asset('img/iconstella.svg') }}" style="opacity: 0.8 ; max-width:45px;">
                </a>

                <h4 class="modal-title w-100 text-center" id="UserPermissionLabel" style="color: #CDC717">
                    Quyền Riêng - <span id="userPermissionName"></span>
                </h4>

                <button type="button" class="close" data-dismiss="modal" aria-label="Đóng">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div class="modal-body">

                <input type="hidden" id="userPermissionUserId" value="">

                <div class="alert alert-info" style="font-size: 14px">
                    <b>Theo nhóm quyền</b>: giữ nguyên kết quả từ nhóm quyền của user.
                    <b>Cho phép</b> / <b>Từ chối</b>: cấp riêng cho user này, ghi đè nhóm quyền.
                </div>

                <div class="form-group">
                    <input type="text" class="form-control" id="userPermissionSearch"
                        placeholder="Tìm quyền theo tên...">
                </div>

                <div style="max-height: 55vh; overflow-y: auto">
                    <table class="table table-bordered table-striped" style="font-size: 15px">
                        <thead style="position: sticky; top: 0; background-color: white; z-index: 1020">
                            <tr>
                                <th>Quyền</th>
                                <th class="text-center" style="width: 130px">Từ Nhóm Quyền</th>
                                <th class="text-center" style="width: 330px">Cấp Riêng</th>
                                <th class="text-center" style="width: 110px">Kết Quả</th>
                            </tr>
                        </thead>
                        <tbody id="userPermissionBody">
                        </tbody>
                    </table>
                </div>

            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
            </div>
        </div>
    </div>
</div>

<script>
    (function() {
        var listUrl = "{{ url('User/user_permission') }}";
        var saveUrl = "{{ route('pages.User.user_permission.store_or_update') }}";

        function resultBadge(fromRole, state) {
            var allowed = state === 'inherit' ? fromRole : state === 'allow';

            return allowed ?
                '<span class="badge badge-success">Có</span>' :
                '<span class="badge badge-secondary">Không</span>';
        }

        function renderRow(item) {
            var name = 'state-' + item.id;

            var options = [
                ['inherit', 'Theo nhóm quyền'],
                ['allow', 'Cho phép'],
                ['deny', 'Từ chối'],
            ];

            var radios = '';
            options.forEach(function(option) {
                radios +=
                    '<div class="form-check form-check-inline">' +
                    '<input class="form-check-input user-permission-state" type="radio"' +
                    ' name="' + name + '" id="' + name + '-' + option[0] + '"' +
                    ' data-permission="' + item.id + '" value="' + option[0] + '"' +
                    (item.state === option[0] ? ' checked' : '') + '>' +
                    '<label class="form-check-label" for="' + name + '-' + option[0] + '">' + option[1] +
                    '</label>' +
                    '</div>';
            });

            return '<tr data-permission-row="' + item.id + '" data-from-role="' + (item.from_role ? 1 : 0) +
                '" data-state="' + item.state + '">' +
                '<td>' + item.name + '</td>' +
                '<td class="text-center">' + (item.from_role ? 'Có' : 'Không') + '</td>' +
                '<td>' + radios + '</td>' +
                '<td class="text-center user-permission-result">' + resultBadge(item.from_role, item.state) +
                '</td>' +
                '</tr>';
        }

        $(document).on('click', '.btn-user-permission', function() {
            var userId = $(this).data('id');

            $('#userPermissionUserId').val(userId);
            $('#userPermissionName').text($(this).data('fullname'));
            $('#userPermissionSearch').val('');
            $('#userPermissionBody').html('<tr><td colspan="4" class="text-center">Đang tải...</td></tr>');

            $.ajax({
                url: listUrl + '/' + userId,
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    var rows = '';
                    response.datas.forEach(function(item) {
                        rows += renderRow(item);
                    });
                    $('#userPermissionBody').html(rows);
                },
                error: function() {
                    $('#userPermissionBody').html(
                        '<tr><td colspan="4" class="text-center text-danger">Không tải được danh sách quyền</td></tr>'
                    );
                }
            });
        });

        $(document).on('change', '.user-permission-state', function() {
            var input = $(this);
            var row = input.closest('tr');
            var permissionId = input.data('permission');
            var state = input.val();

            $.ajax({
                url: saveUrl,
                type: 'POST',
                dataType: 'json',
                data: {
                    _token: '{{ csrf_token() }}',
                    user_id: $('#userPermissionUserId').val(),
                    permission_id: permissionId,
                    state: state
                },
                success: function() {
                    row.attr('data-state', state);
                    row.find('.user-permission-result')
                        .html(resultBadge(row.attr('data-from-role') == '1', state));
                },
                error: function(xhr) {
                    var message = (xhr.responseJSON && xhr.responseJSON.error) ?
                        xhr.responseJSON.error : 'Không lưu được quyền';

                    Swal.fire({
                        title: 'Lỗi!',
                        text: message,
                        icon: 'error'
                    });

                    // Trả radio về trạng thái đã lưu trước đó
                    row.find('.user-permission-state').prop('checked', false);
                    row.find('.user-permission-state[value="' + row.attr('data-state') + '"]')
                        .prop('checked', true);
                }
            });
        });

        $(document).on('keyup', '#userPermissionSearch', function() {
            var keyword = $(this).val().toLowerCase();

            $('#userPermissionBody tr').each(function() {
                var name = $(this).find('td:first').text().toLowerCase();
                $(this).toggle(name.indexOf(keyword) !== -1);
            });
        });
    })();
</script>
