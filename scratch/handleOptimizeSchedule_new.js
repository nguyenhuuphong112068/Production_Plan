  const handleOptimizeSchedule = async () => {
    const confirmed = await Swal.fire({
      title: 'Tối Ưu Hóa Toàn Bộ Lịch',
      html: `
        <div style="text-align:left;font-size:14px">
          <p>Chức năng này sẽ:</p>
          <ul style="margin:8px 0;padding-left:20px">
            <li>🔒 <b>Cố định</b> các công đoạn <b>Đã hoàn thành</b> và <b>Pha Chế</b> (3 & 4)</li>
            <li>⬅️ <b>Dồn lịch lùi</b> các công đoạn trước (Cân, Cấp Phát) sát Pha Chế</li>
            <li>➡️ <b>Dồn lịch tiến</b> các công đoạn sau (Nén, Bao, Đóng gói)</li>
            <li>✨ <b>Tối ưu hóa</b>: Điền vào các khoảng trống trên phòng để lấp đầy lịch, đảm bảo không vi phạm lỗi Nguyên liệu (NL/BB) và không lỗi đen.</li>
          </ul>
          <p style="color:#e74c3c;margin-top:8px">⚠️ Thao tác này sẽ cấu trúc lại toàn bộ lịch chưa hoàn thành.</p>
        </div>
      `,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: '🚀 Chạy Tối Ưu',
      cancelButtonText: 'Hủy',
      confirmButtonColor: '#27ae60'
    });

    if (!confirmed.isConfirmed) return;

    Swal.fire({
      title: 'Đang tối ưu hóa...',
      html: 'Hệ thống đang quét và sắp xếp lại toàn bộ lịch sản xuất...',
      allowOutsideClick: false,
      showConfirmButton: false,
      didOpen: () => Swal.showLoading()
    });

    await new Promise(resolve => setTimeout(resolve, 80));

    const calendarApi = calendarRef.current?.getApi();
    if (!calendarApi) return;
    const allEvents = calendarApi.getEvents();

    const getDurationForEvent = (ev, roomId) => {
        let q = quota.find(item => String(item.room_id) === String(roomId) && Number(item.stage_code) === Number(ev.extendedProps?.stage_code) && (
            (ev.extendedProps?.process_code && String(item.process_code).startsWith(String(ev.extendedProps?.process_code))) ||
            (ev.extendedProps?.intermediate_code && String(item.intermediate_code) === String(ev.extendedProps?.intermediate_code)) ||
            (ev.extendedProps?.finished_product_code && String(item.finished_product_code) === String(ev.extendedProps?.finished_product_code))
        ));
        
        if (q) {
             let pTime = parseFloat(q.p_time) || 0;
             let mTime = parseFloat(q.m_time) || 0;
             return (pTime + mTime) * 3600000;
        }
        let currentDur = new Date(ev.end).getTime() - new Date(ev.start).getTime();
        let start = new Date(ev.start);
        let end = new Date(ev.end);
        for (let off of offRanges) {
            if (off.start < end && off.end > start) {
                let overlapStart = new Date(Math.max(start.getTime(), off.start.getTime()));
                let overlapEnd = new Date(Math.min(end.getTime(), off.end.getTime()));
                if (overlapEnd > overlapStart) {
                    currentDur -= (overlapEnd.getTime() - overlapStart.getTime());
                }
            }
        }
        return currentDur;
    };

    const getAllowedRooms = (ev) => {
        let allowed = new Set();
        const currentRoomId = ev.getResources()[0]?.id || ev.extendedProps?.resourceId || ev.resourceId;
        if (currentRoomId) allowed.add(String(currentRoomId));

        let matches = quota.filter(item => Number(item.stage_code) === Number(ev.extendedProps?.stage_code) && (
            (ev.extendedProps?.process_code && String(item.process_code).startsWith(String(ev.extendedProps?.process_code))) ||
            (ev.extendedProps?.intermediate_code && String(item.intermediate_code) === String(ev.extendedProps?.intermediate_code)) ||
            (ev.extendedProps?.finished_product_code && String(item.finished_product_code) === String(ev.extendedProps?.finished_product_code))
        ));
        for (let m of matches) {
            allowed.add(String(m.room_id));
        }
        return Array.from(allowed);
    };

    const getCompatibleMolds = (ev, roomId) => {
        let catId = ev.extendedProps?.product_caterogy_id;
        if (!catId) return [];
        let room = allEvents.find(e => e.getResources && String(e.getResources()[0]?.id) === String(roomId))?.getResources()[0] || resources.find(r => String(r.id) === String(roomId));
        let rType = room?.extendedProps?.blister_type_code || room?.blister_type_code;
        
        let moldIds = finishedProductMolds.filter(f => String(f.finished_product_category_id) === String(catId)).map(f => String(f.blister_mold_id));
        let compatible = blisterMolds.filter(m => moldIds.includes(String(m.id)));
        if (rType) {
            compatible = compatible.filter(m => m.blister_type_code === rType || (m.blister_type_code && m.blister_type_code.includes(rType)) || !m.blister_type_code);
        }
        return compatible;
    };

    const isMoldAvailable = (moldId, tempStart, tempEnd, evId, currentUpdates, updatedTimes) => {
        let mold = blisterMolds.find(m => String(m.id) === String(moldId));
        if (!mold) return true; 
        let amount = Number(mold.amount) || 0;
        
        let concurrent = 0;
        for (let e of allEvents) {
            if (String(e.id) === String(evId) || String(e.id).endsWith('-cleaning')) continue;
            if (Number(e.extendedProps?.stage_code) === 7) {
                 let eStart, eEnd;
                 if (updatedTimes[e.id]) {
                     eStart = new Date(updatedTimes[e.id].start);
                     eEnd = new Date(updatedTimes[e.id].end);
                 } else {
                     eStart = new Date(e.start);
                     eEnd = new Date(e.end);
                 }
                 let eMoldId = currentUpdates.find(u => String(u.id) === String(e.id))?.blister_mold_id || e.extendedProps?.blister_mold_id;
                 if (String(eMoldId) === String(moldId)) {
                     if (tempStart < eEnd && tempEnd > eStart) {
                         concurrent++;
                     }
                 }
            }
        }
        return concurrent < amount;
    };

    const campaignRoomMap = {}; // key: campaign_code_stage_code -> roomId


    const getMaterialStartDate = (ev) => {
      let dates = [];
      if (ev.extendedProps?.after_weigth_date) dates.push(new Date(ev.extendedProps.after_weigth_date));
      if (ev.extendedProps?.allow_weight_before_date) dates.push(new Date(ev.extendedProps.allow_weight_before_date));
      if (ev.extendedProps?.after_parkaging_date) dates.push(new Date(ev.extendedProps.after_parkaging_date));
      if (dates.length === 0) return null;
      return new Date(Math.max(...dates.map(d => d.getTime())));
    };

    // Tập hợp mã của các stage 3 để xét stage 4
    const stage3AnchorCodes = new Set(
      allEvents
        .filter(e => Number(e.extendedProps?.stage_code) === 3 && !String(e.id).endsWith('-cleaning'))
        .map(a => String(a.extendedProps?.code))
        .filter(Boolean)
    );

    const hasStage3Ancestor = (ev) => {
      let code = ev.extendedProps?.predecessor_code;
      let depth = 0;
      while (code && depth++ < 20) {
        if (stage3AnchorCodes.has(String(code))) return true;
        const predEv = allEvents.find(e => String(e.extendedProps?.code) === String(code) && !String(e.id).endsWith('-cleaning'));
        if (!predEv) break;
        code = predEv.extendedProps?.predecessor_code;
      }
      return false;
    };

    // Xác định anchors: Đã hoàn thành, HOẶC Pha chế (3, 4) chưa hoàn thành
    const anchors = allEvents.filter(e => {
      if (String(e.id).endsWith('-cleaning')) return false;
      if (e.extendedProps?.finished == 1) return true;
      if (Number(e.extendedProps?.stage_code) === 3) return true;
      if (Number(e.extendedProps?.stage_code) === 4 && !hasStage3Ancestor(e)) return true;
      return false;
    });

    const anchorIds = new Set(anchors.map(a => String(a.id)));
    const offRanges = offDays.map(d => {
      const start = new Date(`${d}T06:00:00`);
      const end = new Date(start.getTime() + 24 * 60 * 60 * 1000);
      return { start, end };
    }).sort((a, b) => a.start - b.start);

    let updates = [];
    let updatedTimesById = {};

    // Khóa các sự kiện anchor
    anchors.forEach(a => {
      updatedTimesById[String(a.id)] = { start: new Date(a.start), end: new Date(a.end) };
      const cleaningA = allEvents.find(e => String(e.id) === String(a.id).replace('-main', '-cleaning'));
      if (cleaningA) {
        updatedTimesById[String(cleaningA.id)] = { start: new Date(cleaningA.start), end: new Date(cleaningA.end) };
      }
    });

    const buildPredChain = (startId) => {
      const chain = [];
      const visited = new Set();
      let queue = [startId];
      while (queue.length > 0) {
        const cId = queue.shift();
        if (visited.has(cId) || updatedTimesById[cId]) continue; // Ngừng nếu đã bị khóa
        visited.add(cId);
        const ev = allEvents.find(e => String(e.id) === cId && !String(e.id).endsWith('-cleaning'));
        if (!ev) continue;
        chain.push(ev);
        const pCode = ev.extendedProps.predecessor_code;
        if (pCode) {
          const pEv = allEvents.find(e => String(e.extendedProps.code) === String(pCode) && !String(e.id).endsWith('-cleaning') && !visited.has(String(e.id)));
          if (pEv) queue.push(String(pEv.id));
        }
      }
      return chain.sort((a, b) => (b.extendedProps.stage_code || 0) - (a.extendedProps.stage_code || 0));
    };

    const buildSuccChain = (startId) => {
      const chain = [];
      const visited = new Set();
      let queue = [startId];
      while (queue.length > 0) {
        const cId = queue.shift();
        if (visited.has(cId) || updatedTimesById[cId]) continue; // Ngừng nếu đã bị khóa
        visited.add(cId);
        const ev = allEvents.find(e => String(e.id) === cId && !String(e.id).endsWith('-cleaning'));
        if (!ev) continue;
        chain.push(ev);
        const myCode = ev.extendedProps.code;
        if (myCode) {
          allEvents.forEach(e => {
            const eId = String(e.id);
            if (String(e.extendedProps.predecessor_code) === String(myCode) && !String(e.id).endsWith('-cleaning') && !visited.has(eId)) {
              queue.push(eId);
            }
          });
        }
      }
      return chain.sort((a, b) => (a.extendedProps.stage_code || 0) - (b.extendedProps.stage_code || 0));
    };

    // 1. BACKWARD CASCADE (Kéo các predecessor về sát Pha Chế / Finished)
    for (const anchor of anchors) {
      const predCode = anchor.extendedProps.predecessor_code;
      if (!predCode) continue;

      const predEv = allEvents.find(e => String(e.extendedProps.code) === String(predCode) && !String(e.id).endsWith('-cleaning'));
      if (!predEv) continue;

      const predChain = buildPredChain(String(predEv.id));
      if (predChain.length === 0) continue;

      const chainIds = new Set(predChain.map(e => String(e.id)));

      for (const ev of predChain) {
        await new Promise(r => setTimeout(r, 0)); // Yield thread to avoid freezing UI

        const evId = String(ev.id);
        const cleaningEv = allEvents.find(e => String(e.id) === evId.replace('-main', '-cleaning'));
        const myCode = ev.extendedProps.code;
        
        let latestEnd = null;
        if (myCode) {
          const succEv = allEvents.find(e => String(e.extendedProps.predecessor_code) === String(myCode) && !String(e.id).endsWith('-cleaning'));
          if (succEv) {
            const succId = String(succEv.id);
            latestEnd = updatedTimesById[succId]?.start || new Date(succEv.start);
          }
        }
        if (!latestEnd) latestEnd = new Date(anchor.start);

        const duration = new Date(ev.end).getTime() - new Date(ev.start).getTime();
        const resourceId = ev.getResources()[0]?.id;

        const cleaningDur = cleaningEv ? (new Date(cleaningEv.end).getTime() - new Date(cleaningEv.start).getTime()) : 0;
        const adjustedLatestEnd = new Date(latestEnd.getTime() - cleaningDur);

        const unprocessed = [...chainIds].filter(id => id !== evId && !updatedTimesById[id]);
        const unprocessedCleaning = unprocessed.map(id => id.replace('-main', '-cleaning')).filter(cid => !updatedTimesById[cid]);
        
        // TẤT CẢ các event (chưa bị khóa) không thuộc chuỗi hiện tại đang xét đều phải được né (tránh chồng lấn)
        // Vì vậy ignoreIds sẽ bao gồm các event chưa khóa TRONG CHUỖI NÀY
        // Bất kỳ event nào khác trên lịch chưa khóa mà không nằm trong ignoreIds sẽ ĐƯỢC CÓ COI LÀ CHƯỚNG NGẠI VẬT!
        // Nhưng nếu chúng chưa khóa, chúng có thể bị kéo đi ở bước sau. Nếu coi chúng là chướng ngại vật, ta sẽ bị kẹt.
        // Giải pháp: ignoreIds MỞ RỘNG bao gồm TOÀN BỘ CÁC EVENT CHƯA KHÓA!
        // Như vậy Backward Cascade sẽ coi các event chưa khóa khác như "không tồn tại" và lấp vào chỗ của chúng.
        // Khi đến lượt các event đó, chúng sẽ bị Forward Cascade đẩy đi chỗ khác (vị trí trống tiếp theo).
        const allUnprocessed = allEvents.filter(e => !updatedTimesById[String(e.id)] && String(e.id) !== evId && (!cleaningEv || String(e.id) !== String(cleaningEv.id))).map(e => String(e.id));

        let ignoreIds = allUnprocessed; // Né mọi event đang bị khóa. Ignored mọi event chưa khóa.
        const stageCode = Number(ev.extendedProps?.stage_code);
        if (stageCode === 1 || stageCode === 2) {
            // Cho phép chồng chất đối với công đoạn Cân/Cấp phát để đảm bảo không lỗi
            ignoreIds = allEvents.map(e => String(e.id));
        }

        let newSlot = findPreviousAvailableSlot(resourceId, duration, adjustedLatestEnd, allEvents, offRanges, ignoreIds, updatedTimesById);

        // Ràng buộc Ngày NL: Không được kéo quá sát về quá khứ nếu vượt ngày NL
        const matDate = getMaterialStartDate(ev);
        if (matDate && newSlot.start < matDate) {
           // Nếu bị lố quá ngày nguyên liệu, buộc phải forward cascade từ ngày NL
           // Để đơn giản, ta tìm slot trống gần nhất sau ngày NL nhưng VẪN PHẢI kẹt trước adjustedLatestEnd (không đè lên successor)
           // Tuy nhiên findNextAvailableSlot có thể vượt quá adjustedLatestEnd. 
           // Tạm thời nếu chạm giới hạn NL, chúng ta giới hạn cứng tại matDate.
           newSlot.start = matDate;
           newSlot.end = new Date(matDate.getTime() + duration);
        }

        updatedTimesById[evId] = { start: newSlot.start, end: newSlot.end };
        updates.push({ id: evId, start: newSlot.start, end: newSlot.end, resourceId, clearWarnings: true });

        if (cleaningEv) {
          const cId = String(cleaningEv.id);
          const cleanSlot = findPreviousAvailableSlot(resourceId, cleaningDur, latestEnd, allEvents, offRanges, ignoreIds, updatedTimesById);
          updatedTimesById[cId] = { start: cleanSlot.start, end: cleanSlot.end };
          updates.push({ id: cId, start: cleanSlot.start, end: cleanSlot.end, resourceId, clearWarnings: true });
        }
      }
    }

    // Lọc lại các sản phẩm/chuỗi CHƯA CÓ ANCHOR (Không có Pha Chế, Không có Hoàn Thành)
    // Các chuỗi này sẽ lấy event đầu tiên (stage_code nhỏ nhất) làm gốc và bắt đầu Forward Cascade từ NOW
    const unanchoredFirstEvents = allEvents.filter(e => {
        if (String(e.id).endsWith('-cleaning')) return false;
        if (updatedTimesById[String(e.id)]) return false;
        // Kiểm tra xem nó có predecessor không? Nếu KHÔNG có predecessor thì nó là sự kiện đầu tiên của chuỗi
        const pCode = e.extendedProps?.predecessor_code;
        if (!pCode) return true;
        const hasPred = allEvents.some(p => String(p.extendedProps?.code) === String(pCode));
        return !hasPred; // Nếu không có event predecessor nào trên lịch -> nó là khởi đầu
    });

    // 2. FORWARD CASCADE (Đẩy các successor và chuỗi tự do về phía tương lai vào các khoảng trống)
    // Tạo danh sách gốc phát: anchors + unanchoredFirstEvents
    console.log('forwardRoots size:', anchors.length, unanchoredFirstEvents.length);
    const forwardRoots = [...anchors, ...unanchoredFirstEvents].sort((a, b) => new Date(a.start).getTime() - new Date(b.start).getTime());

    for (const anchor of forwardRoots) {
      // Đối với unanchoredFirstEvents, ta Forward Cascade từ chính nó trước
      let chain = [];
      if (unanchoredFirstEvents.some(e => String(e.id) === String(anchor.id))) {
          chain = [anchor, ...buildSuccChain(String(anchor.id))];
      } else {
          const myCode = anchor.extendedProps.code;
          if (!myCode) continue;
          const succEv = allEvents.find(e => String(e.extendedProps.predecessor_code) === String(myCode) && !String(e.id).endsWith('-cleaning'));
          if (!succEv) continue;
          if (updatedTimesById[String(succEv.id)]) continue;
          chain = buildSuccChain(String(succEv.id));
      }

      if (chain.length === 0) continue;

      for (const ev of chain) {
        await new Promise(r => setTimeout(r, 0)); // Yield thread to avoid freezing UI

        const evId = String(ev.id);
        if (updatedTimesById[evId]) continue;

        const cleaningEv = allEvents.find(e => String(e.id) === evId.replace('-main', '-cleaning'));
        const predCode = ev.extendedProps.predecessor_code;
        let earliestStart = new Date(); // Mặc định không được xếp trong quá khứ
        
        if (predCode) {
          const predEv = allEvents.find(e => String(e.extendedProps.code) === String(predCode) && !String(e.id).endsWith('-cleaning'));
          if (predEv) {
            const predId = String(predEv.id);
            const predEnd = updatedTimesById[predId]?.end || new Date(predEv.end);
            earliestStart = new Date(Math.max(earliestStart.getTime(), predEnd.getTime()));
            const predCleaningId = predId.replace('-main', '-cleaning');
            if (updatedTimesById[predCleaningId]) {
              earliestStart = new Date(Math.max(earliestStart.getTime(), updatedTimesById[predCleaningId].end.getTime()));
            } else {
              const predCleaning = allEvents.find(e => String(e.id) === predCleaningId);
              if (predCleaning) earliestStart = new Date(Math.max(earliestStart.getTime(), new Date(predCleaning.end).getTime()));
            }
          }
        }

        const matDate = getMaterialStartDate(ev);
        if (matDate && matDate > earliestStart) earliestStart = matDate;

        const duration = new Date(ev.end).getTime() - new Date(ev.start).getTime();
        const resourceId = ev.getResources()[0]?.id;

        // Tương tự, coi các event chưa khóa khác như "không tồn tại" để lấp vào chỗ của chúng
        const allUnprocessed = allEvents.filter(e => !updatedTimesById[String(e.id)] && String(e.id) !== evId && (!cleaningEv || String(e.id) !== String(cleaningEv.id))).map(e => String(e.id));
        const ignoreIds = allUnprocessed;

        const newSlot = findNextAvailableSlot(resourceId, duration, earliestStart, allEvents, offRanges, ignoreIds, updatedTimesById);

        updatedTimesById[evId] = { start: newSlot.start, end: newSlot.end };
        updates.push({ id: evId, start: newSlot.start, end: newSlot.end, resourceId, clearWarnings: true });

        if (cleaningEv) {
          const cId = String(cleaningEv.id);
          const cleaningDur = new Date(cleaningEv.end).getTime() - new Date(cleaningEv.start).getTime();
          const cleanSlot = findNextAvailableSlot(resourceId, cleaningDur, newSlot.end, allEvents, offRanges, ignoreIds, updatedTimesById);
          updatedTimesById[cId] = { start: cleanSlot.start, end: cleanSlot.end };
          updates.push({ id: cId, start: cleanSlot.start, end: cleanSlot.end, resourceId, clearWarnings: true });
        }
      }
    }

    if (updates.length > 0) {
      let newPending = [...pendingChanges];
      calendarApi.batchRendering(() => {
        
      // Build a map of all events combining DOM events and new updates for color evaluation
      const eventMap = {};
      calendarApi.getEvents().forEach(e => {
        if (e.extendedProps?.code) {
          eventMap[e.extendedProps.code] = { start: e.start, end: e.end, quarantine_time_limit_hour: e.extendedProps.quarantine_time_limit_hour };
        }
      });
      updates.forEach(u => {
        const ev = calendarApi.getEventById(u.id);
        if (ev && ev.extendedProps?.code) {
          eventMap[ev.extendedProps.code] = { start: u.start, end: u.end, quarantine_time_limit_hour: ev.extendedProps.quarantine_time_limit_hour };
        }
      });

      updates.forEach(u => {
          const ev = calendarApi.getEventById(u.id);
          if (ev) {
            if (u.start && u.end) ev.setDates(u.start, u.end);
            if (u.clearWarnings) {
              const fullPlanInfo = { ...ev.extendedProps, start: u.start, end: u.end };
              const newColors = calculateFrontendColors(fullPlanInfo, eventMap);
              ev.setExtendedProp('warning_text', '');
              if (u.blister_mold_id) ev.setExtendedProp('blister_mold_id', u.blister_mold_id);
              if (u.resourceId && (!ev.getResources || !ev.getResources()[0] || String(ev.getResources()[0].id) !== String(u.resourceId))) {
                  ev.setResources([u.resourceId]);
              }
              ev.setExtendedProp('violation_colors', newColors.violation_colors);
              ev.setProp('backgroundColor', newColors.backgroundColor);
              ev.setProp('textColor', newColors.textColor);
            }
          }
          if (u.start && u.end && ev) {
            const changeObj = {
              id: u.id,
              start: u.start,
              end: u.end,
              blister_mold_id: u.blister_mold_id !== undefined ? u.blister_mold_id : ev.extendedProps?.blister_mold_id,
              resourceId: u.resourceId || (ev.getResources && ev.getResources().length ? ev.getResources()[0]?.id : (ev.extendedProps?.resourceId || ev._def?.resourceIds?.[0] || ev.resourceId)),
              title: ev.title,
              submit: ev.extendedProps?.submit,
              C_end: ev.extendedProps?.C_end || false
            };
            const existIdx = newPending.findIndex(p => String(p.id) === String(u.id));
            if (existIdx >= 0) newPending[existIdx] = { ...newPending[existIdx], ...changeObj };
            else newPending.push(changeObj);
          }
        });
      });
      setPendingChanges(newPending);
      
      // Khôi phục lại trạng thái frontend, không lưu lên server vội
      Swal.fire({
        icon: 'success',
        title: 'Hoàn thành dồn lịch!',
        html: `Đã dời vị trí và tạm thời xóa lỗi cho <b>${newPending.length}</b> sự kiện.<br>Hãy xem lại kết quả trên lịch và nhấn 💾 để LƯU THAY ĐỔI.`,
        timer: 3000
      });
    } else {
      Swal.fire('Thông báo', 'Lịch đã được tối ưu hóa tối đa, không cần điều chỉnh.', 'info');
    }
  }