        let cloneTargetDates = new Set();
        let currentCloneTarget = null;
        let cloneDateFp = null;

        if ($('#clone-date-input').length > 0) {
            cloneDateFp = flatpickr("#clone-date-input", {
                inline: true,
                mode: "multiple",
                minDate: new Date(new Date(reportedDateStr).getTime() + 86400000),
                dateFormat: "Y-m-d",
                onChange: function(selectedDates, dateStr, instance) {
                    cloneTargetDates = new Set(dateStr.split(', ').filter(d => d));
                    renderCloneDates();
                }
            });
        }

        $(document).on('click', '.btn-clone-shift', function() {
            currentCloneTarget = $(this).closest('.assignment-item');
            cloneTargetDates.clear();
            if(cloneDateFp) cloneDateFp.clear();
            renderCloneDates();
            $('#modalCloneCustomTask').modal('show');
        });

        $(document).on('click', '.btn-clone-row', function() {
            currentCloneTarget = $(this).closest('.room-row');
            cloneTargetDates.clear();
            if(cloneDateFp) cloneDateFp.clear();
            renderCloneDates();
            $('#modalCloneCustomTask').modal('show');
        });

        $(document).on('click', '.btn-remove-clone-date', function() {
            const dateToRemove = $(this).data('date');
            cloneTargetDates.delete(dateToRemove);
            if(cloneDateFp) cloneDateFp.setDate(Array.from(cloneTargetDates));
            renderCloneDates();
        });

        function renderCloneDates() {
            const container = $('#clone-dates-container');
            const emptyLabel = $('#clone-dates-empty');

            container.find('.badge').remove();

            if (cloneTargetDates.size === 0) {
                emptyLabel.show();
            } else {
                emptyLabel.hide();
                const sortedDates = Array.from(cloneTargetDates).sort();
                sortedDates.forEach(dateStr => {
                    if (!dateStr) return;
                    const parts = dateStr.split('-');
                    const displayDate = parts[2] + '/' + parts[1] + '/' + parts[0];
                    const badgeHtml = `
                        <span class="badge badge-info p-2 d-flex align-items-center" style="font-size: 14px;">
                            <i class="far fa-calendar-alt mr-2"></i> ${displayDate}
                            <i class="fas fa-times ml-2 text-white cursor-pointer btn-remove-clone-date" data-date="${dateStr}"></i>
                        </span>
                    `;
                    container.append(badgeHtml);
                });
            }
        }

        $('#btn-confirm-clone').on('click', function() {
            if (cloneTargetDates.size === 0) {
                Swal.fire('Lỗi', 'Vui lòng chọn ít nhất 1 ngày để nhân bản.', 'warning');
                return;
            }

            if (!currentCloneTarget) return;

            let assignments = [];
            let roomRow = null;

            if (currentCloneTarget.hasClass('assignment-item')) {
                roomRow = currentCloneTarget.closest('.room-row');

                const p_list = [];
                currentCloneTarget.find('.personnel-row').each(function() {
                    const pid = $(this).find('.person-select').val();
                    if (pid) p_list.push({
                        personnel_id: pid,
                        notification: $(this).find('.person-notif').val()
                    });
                });

                const jobDesc = currentCloneTarget.find('.job-desc').html().trim();
                const shiftName = currentCloneTarget.find('.shift-select option:selected').text();

                if (!jobDesc || jobDesc === '<br>' || jobDesc === 'Nội dung...') {
                    Swal.fire('Thiếu thông tin', `Ca ${shiftName}: Vui lòng nhập nội dung công việc.`, 'warning');
                    return;
                }
                if (p_list.length === 0) return;

                assignments.push({
                    shift: currentCloneTarget.find('.shift-select').val(),
                    start_time: currentCloneTarget.find('.start-time-input').val(),
                    end_time: currentCloneTarget.find('.end-time-input').val(),
                    job_description: jobDesc,
                    personnel_list: p_list
                });
            } else if (currentCloneTarget.hasClass('room-row')) {
                roomRow = currentCloneTarget;
                let isValid = true;
                let validationError = '';

                roomRow.find('.assignment-item:not(.foreign-assignment)').each(function() {
                    const jobDesc = $(this).find('.job-desc').html().trim();
                    const shiftName = $(this).find('.shift-select option:selected').text();

                    let pCount = 0;
                    const p_list = [];
                    $(this).find('.personnel-row').each(function() {
                        const pid = $(this).find('.person-select').val();
                        if (pid) {
                            pCount++;
                            p_list.push({
                                personnel_id: pid,
                                notification: $(this).find('.person-notif').val()
                            });
                        }
                    });

                    if (!jobDesc || jobDesc === '<br>' || jobDesc === 'Nội dung...') {
                        validationError = `Ca ${shiftName}: Vui lòng nhập nội dung công việc.`;
                        isValid = false;
                        return false;
                    }
                    if (pCount === 0) return true;

                    assignments.push({
                        shift: $(this).find('.shift-select').val(),
                        start_time: $(this).find('.start-time-input').val(),
                        end_time: $(this).find('.end-time-input').val(),
                        job_description: jobDesc,
                        personnel_list: p_list
                    });
                });

                if (!isValid) {
                    Swal.fire('Thiếu thông tin', validationError, 'warning');
                    return;
                }
                if (assignments.length === 0) {
                    Swal.fire('Lỗi', 'Không có ca nào để nhân bản.', 'warning');
                    return;
                }
            }

            const roomId = roomRow.attr('data-room-id');
            let roomName = '';
            if(!roomId) {
                roomName = roomRow.find('.room-select-custom').val();
            }

            const payload = {
                _token: "{{ csrf_token() }}",
                room_id: roomId || roomName,
                group_code: "{{ $group_code ?? '' }}",
                stage_groups_code: roomRow.attr('data-group-code'),
                target_dates: Array.from(cloneTargetDates),
                assignments: assignments
            };

            const btn = $(this);
            btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Đang xử lý...');

            $.ajax({
                url: "{{ route('pages.assignment.maintenance.clone_custom_task') }}",
                method: "POST",
                data: payload,
                success: function(res) {
                    btn.prop('disabled', false).html('<i class="fas fa-check mr-1"></i> Xác nhận Clone');
                    if (res.success) {
                        $('#modalCloneCustomTask').modal('hide');
                        Swal.fire('Thành công', res.message, 'success');
                    } else {
                        Swal.fire('Lỗi', res.message || 'Có lỗi xảy ra', 'error');
                    }
                },
                error: function(err) {
                    btn.prop('disabled', false).html('<i class="fas fa-check mr-1"></i> Xác nhận Clone');
                    const msg = err.responseJSON && err.responseJSON.message ? err.responseJSON.message : 'Không thể kết nối đến máy chủ';
                    Swal.fire('Lỗi', msg, 'error');
                }
            });
        });
