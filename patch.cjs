const fs = require('fs');

let content = fs.readFileSync('c:\\PMS\\Production_Plan\\resources\\views\\pages\\assignment\\production\\dataTable.blade.php', 'utf8');

const targetFunction = `    function updateSidebarPersonnelTimes() {`;

const newFunction = `
    function updateSidebarPersonnelTimes() {
        // Helper to calculate hours between HH:mm times
        function calculateDurationHours(startStr, endStr, shiftVal = null) {
            if (!startStr || !endStr) return 0;
            const sParts = startStr.split(':');
            const eParts = endStr.split(':');

            let sMin = parseInt(sParts[0], 10) * 60 + parseInt(sParts[1], 10);
            let eMin = parseInt(eParts[0], 10) * 60 + parseInt(eParts[1], 10);

            if (eMin < sMin) {
                eMin += 24 * 60;
            }

            let durationMin = eMin - sMin;

            let isNoLunchBreakShift = false;
            if (shiftVal) {
                if (['1', '2', '3', '6'].includes(shiftVal.toString())) {
                    isNoLunchBreakShift = true;
                }
            }

            if (!isNoLunchBreakShift) {
                const lunchStart = 11 * 60 + 30;
                const lunchEnd = 12 * 60 + 15;
                const overlapStart = Math.max(sMin, lunchStart);
                const overlapEnd = Math.min(eMin, lunchEnd);
                if (overlapStart < overlapEnd) {
                    durationMin -= (overlapEnd - overlapStart);
                }
            }
            return durationMin / 60;
        }

        function timeStrToMins(t) {
            if (!t) return 0;
            const parts = t.split(':');
            return parseInt(parts[0], 10) * 60 + parseInt(parts[1], 10);
        }

        // ================= NEW: ROBUST TIME DISPLAY UPDATE =================
        const dbAssignments = typeof window.dbPersonnelAssignments !== 'undefined' ? window.dbPersonnelAssignments : {};
        let domPersons = new Set();
        $('.room-row .assignment-item:not(.foreign-assignment)').each(function() {
            $(this).find('.personnel-row').each(function() {
                const pid = $(this).find('.person-select').val();
                if (pid) domPersons.add(pid.toString());
            });
        });

        let personCumulativeTimes = {}; // personId -> totalHrs

        domPersons.forEach(personId => {
            let assignments = [];
            $('.room-row .assignment-item:not(.foreign-assignment)').each(function() {
                const $item = $(this);
                const shiftVal = $item.find('.shift-select').val() || '4';
                $item.find('.personnel-row').each(function() {
                    const foundPersonRow = $(this);
                    if (foundPersonRow.find('.person-select').val() == personId) {
                        const start = foundPersonRow.find('.p-start-input').val() || $item.find('.start-time-input').val() || '';
                        const end = foundPersonRow.find('.p-end-input').val() || $item.find('.end-time-input').val() || '';
                        assignments.push({
                            element: foundPersonRow,
                            start: start,
                            end: end,
                            shift: shiftVal,
                            is_local: true
                        });
                    }
                });
            });

            const dbList = dbAssignments[personId] || [];
            dbList.forEach(dbAss => {
                assignments.push({
                    element: null,
                    start: dbAss.start_time,
                    end: dbAss.end_time,
                    shift: dbAss.shift_code,
                    is_local: false
                });
            });

            assignments.sort((a, b) => {
                const startA = a.start ? timeStrToMins(a.start) : 0;
                const startB = b.start ? timeStrToMins(b.start) : 0;
                return startA - startB;
            });

            let cumulativeHours = 0;
            assignments.forEach(a => {
                let duration = calculateDurationHours(a.start, a.end, a.shift);
                if (a.element) {
                    const displayEl = a.element.find('.time-display');
                    let tc = 0;
                    if (cumulativeHours >= 8) {
                        tc = duration;
                    } else if (cumulativeHours + duration > 8) {
                        tc = cumulativeHours + duration - 8;
                    }

                    let html = displayEl.html() || '';
                    html = html.replace(/<span style="color:#dc3545[^>]*>.*?<\\/span>/g, '').trim();

                    if (tc > 0.01) {
                        html += \` <span style="color:#dc3545;font-weight:bold;">TC:\${tc.toFixed(1)}h</span>\`;
                    }
                    displayEl.html(html);
                }
                cumulativeHours += duration;
            });

            personCumulativeTimes[personId] = Math.round(cumulativeHours * 100) / 100;
        });
        // ====================================================================

        if (!currentSidebarData) return;

        $('.draggable-person').each(function() {
            const $el = $(this);
            const code = $el.attr('data-code');
            const personId = employeeCodeToId[code];
            const isLeave = $el.attr('data-shift-key') === 'P';

            // Remove existing badges container
            $el.find('.personnel-time-ranges').remove();

            let totalHours = 0;

            if (personId) {
                totalHours = personCumulativeTimes[personId.toString()] || 0;
                
                const assignments = [];
                // 1. Scan DOM (for sidebar badges)
                $('.room-row .assignment-item:not(.foreign-assignment)').each(function() {
                    const $item = $(this);
                    const assId = $item.attr('data-id');
                    let foundPersonRow = null;
                    $item.find('.personnel-row').each(function() {
                        if ($(this).find('.person-select').val() == personId.toString()) {
                            foundPersonRow = $(this);
                        }
                    });
                    if (foundPersonRow) {
                        const roomRow = $item.closest('.room-row');
                        let roomCode = 'Khác';
                        const customSelect = roomRow.find('.room-select-custom');
                        if (customSelect.length > 0) {
                            const selectedOption = customSelect.find('option:selected');
                            const selectedText = selectedOption.text().trim();
                            if (selectedText && !selectedText.startsWith('--')) {
                                roomCode = selectedText.split('-')[0].trim();
                            } else {
                                roomCode = 'Khác';
                            }
                        } else {
                            roomCode = roomRow.find('.room-name-cell b').text().trim() || 'NA';
                        }
                        const shiftVal = $item.find('.shift-select').val() || '4';
                        const start = foundPersonRow.find('.p-start-input').val() || $item.find(
                            '.start-time-input').val() || '';
                        const end = foundPersonRow.find('.p-end-input').val() || $item.find(
                            '.end-time-input').val() || '';

                        if (start || end) {
                            const isSaved = !roomRow.find('.btn-save-room').hasClass('is-dirty');
                            assignments.push({
                                element: foundPersonRow,
                                assignment_id: assId,
                                room: roomCode,
                                start: start,
                                end: end,
                                is_local: true,
                                shift: shiftVal,
                                is_saved: isSaved
                            });
                        }
                    }
                });

                // 2. Scan DB assignments from other groups/departments
                const dbList = dbAssignments[personId.toString()] || [];
                dbList.forEach(dbAss => {
                    const existsInDom = dbAss.assignment_id && $(
                        \`.assignment-item[data-id="\${dbAss.assignment_id}"]\`).length > 0;
                    if (!existsInDom) {
                        assignments.push({
                            assignment_id: dbAss.assignment_id,
                            room: dbAss.room_code || 'Khác',
                            start: dbAss.start,
                            end: dbAss.end,
                            is_local: false,
                            group_name: dbAss.group_name,
                            is_saved: true
                        });
                    }
                });

                if (assignments.length > 0) {
                    // Sort by start time chronologically
                    assignments.sort((a, b) => {
                        const startA = a.start ? timeStrToMins(a.start) : 0;
                        const startB = b.start ? timeStrToMins(b.start) : 0;
                        return startA - startB;
                    });

                    let hasUnsaved = assignments.some(a => a.is_local && !a.is_saved);
                    let totalBadgeClass = hasUnsaved ? 'badge-danger' : 'badge-success';

                    let badgeHtml = '<div class="personnel-time-ranges mt-1">';
                    badgeHtml +=
                        \`<span class="badge \${totalBadgeClass} text-white mr-1" style="font-size: 0.65rem; padding: 2px 4px; font-weight: bold;"><i class="fas fa-hourglass-half mr-1"></i>Tổng: \${totalHours}h</span>\`;
                    assignments.forEach(a => {
                        if (a.is_local) {
                            let localBadgeClass = a.is_saved ? 'badge-info' : 'badge-danger';
                            badgeHtml +=
                                \`<span class="badge \${localBadgeClass} text-white mr-1" style="font-size: 0.65rem; padding: 2px 4px; font-weight: normal;"><i class="far fa-clock mr-1"></i>\${a.room}: \${a.start}-\${a.end}</span>\`;
                        } else {
                            badgeHtml +=
                                \`<span class="badge text-white mr-1" style="font-size: 0.65rem; padding: 2px 4px; font-weight: normal; background-color: #6c757d;" title="Tổ khác: \${a.group_name}"><i class="fas fa-exchange-alt mr-1"></i>\${a.group_name} (\${a.room}): \${a.start}-\${a.end}</span>\`;
                        }
                    });
                    badgeHtml += '</div>';
                    $el.append(badgeHtml);
                }
            }

            // Apply filter under 8h / over 8h
            let showEl = true;
            if (filterUnder8h || filterOver8h) {
                showEl = false;
                if (filterUnder8h && totalHours < 8 && !isLeave) showEl = true;
                if (filterOver8h && totalHours > 8 && !isLeave) showEl = true;
            }
            if (showEl) {
                $el.show();
            } else {
                $el.hide();
            }
        });

        // Hide empty shift headers or update visible count
        $('.shift-header-item').each(function() {
            const shiftKey = $(this).attr('data-shift-key');
            const visibleCount = $(\`.draggable-person[data-shift-key="\${shiftKey}"]:visible\`).length;
            if (visibleCount === 0) {
                $(this).hide();
            } else {
                $(this).show();
                $(this).find('.shift-count-badge').text(visibleCount);
            }
        });

        validateAllOverlaps();
    }
`;

const oldContentPart = content.substring(content.indexOf(targetFunction));
const nextFunction = `    function validateAllOverlaps() {`;
const endIdx = content.indexOf(nextFunction);

const contentBefore = content.substring(0, content.indexOf(targetFunction));
const contentAfter = content.substring(endIdx);

fs.writeFileSync('c:\\PMS\\Production_Plan\\resources\\views\\pages\\assignment\\production\\dataTable.blade.php', contentBefore + newFunction + '\n' + contentAfter);
