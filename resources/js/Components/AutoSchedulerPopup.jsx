import Swal from "sweetalert2";
import withReactContent from "sweetalert2-react-content";
import { Calendar } from "primereact/calendar";
import { Stepper, StepperPanel } from "primereact/stepper";
import { createRoot } from "react-dom/client";
import React, { useState } from "react";
import axios from "axios";

const MySwal = withReactContent(Swal);

let selectedStep = 3; // mặc định là PC

export const handleAutoSchedualer = () => {
  if (!CheckAuthorization(authorization, ["Admin", "Schedualer"])) return;

  const hasEmptyPermission = plan.some((item) => {
    const perm = item.permisson_room;
    const isEmptyArray = Array.isArray(perm) && perm.length === 0;

    return item.stage_code >= 3 && item.stage_code <= 7 && isEmptyArray;
  });

  // ===================== Component React trong Swal =====================
  const SchedulerPopup = () => {
    const [localDates, setLocalDates] = useState([]);
    const [selected, setSelected] = useState(selectedStep);

    const handleDateChange = (e) => {
      const selected = e.value.map((d) => {
        const date = new Date(d);
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, "0");
        const day = String(date.getDate()).padStart(2, "0");
        return `${year}-${month}-${day}`;
      });
      setLocalDates(e.value);
    };

    const handleSelect = (value) => {
      console.log("Selected step:", value);
      setSelected(value);
      selectedStep = value;
    };

    const getClass = (value) =>
      `border-2 border-dashed surface-border border-round surface-ground flex justify-content-center align-items-center h-12rem fs-4 cursor-pointer transition-all ${
        selected === value ? "bg-primary text-white" : ""
      }`;

    return (
      <div className="cfg-wrapper">
        <div className="cfg-card">
          {/* Ngày chạy */}
          <div className="cfg-row">
            <div className="cfg-col">
              <label className="cfg-label" htmlFor="schedule-date">
                Ngày chạy bắt đầu sắp lịch:
              </label>
              <input
                id="schedule-date"
                type="date"
                className="swal2-input cfg-input cfg-input--half"
                name="start_date"
                defaultValue={new Date().toISOString().split("T")[0]}
              />
            </div>
          </div>

          {/* Stepper */}
          <label className="cfg-label mt-3">Sắp lịch theo công đoạn:</label>
          <Stepper style={{ width: "100%" }}>
            <StepperPanel header="PC">
              <div className="flex flex-column h-12rem">
                <div
                  className={getClass(3)}
                  onClick={() => handleSelect(3)}
                >
                  Pha Chế
                </div>
              </div>
            </StepperPanel>

            <StepperPanel header="THT">
              <div className="flex flex-column h-12rem">
                <div
                  className={getClass(4)}
                  onClick={() => handleSelect(4)}
                >
                  Pha Chế ➡ Trộn Hoàn Tất
                </div>
              </div>
            </StepperPanel>

            <StepperPanel header="ĐH">
              <div className="flex flex-column h-12rem">
                <div
                  className={getClass(5)}
                  onClick={() => handleSelect(5)}
                >
                  Pha Chế ➡ Định Hình
                </div>
              </div>
            </StepperPanel>

            <StepperPanel header="BP">
              <div className="flex flex-column h-12rem">
                <div
                  className={getClass(6)}
                  onClick={() => handleSelect(6)}
                >
                  Pha Chế ➡ Bao Phim
                </div>
              </div>
            </StepperPanel>

            <StepperPanel header="ĐG">
              <div className="flex flex-column h-12rem">
                <div
                  className={getClass(7)}
                  onClick={() => handleSelect(7)}
                >
                  Pha Chế ➡ Đóng Gói
                </div>
              </div>
            </StepperPanel>
          </Stepper>

          {/* Calendar */}
          <label className="cfg-label mt-3">Ngày không sắp lịch:</label>
          <div className="card flex justify-content-center">
            <Calendar
              value={localDates}
              onChange={handleDateChange}
              selectionMode="multiple"
              inline
              readOnlyInput
            />
          </div>

          {/* Làm Chủ Nhật */}
          <div className="cfg-row mt-3">
            <label className="cfg-label" htmlFor="work-sunday">
              Làm Chủ Nhật:
            </label>
            <label className="switch">
              <input id="work-sunday" type="checkbox" defaultChecked />
              <span className="slider round"></span>
              <span className="switch-labels">
                <span className="off">No</span>
                <span className="on">Yes</span>
              </span>
            </label>
          </div>

          {hasEmptyPermission && (
            <p
              style={{
                color: "red",
                fontWeight: 600,
                marginTop: 10,
              }}
            >
              ⚠️ Một hoặc nhiều sản phẩm chưa được định mức!
              <br />
              Bạn cần định mức đầy đủ trước khi chạy Auto Scheduler.
            </p>
          )}
        </div>
      </div>
    );
  };

  // ===================== Gọi Swal =====================
  MySwal.fire({
    title: "Cấu Hình Chung Sắp Lịch",
    html: <SchedulerPopup />,
    width: 700,
    customClass: { htmlContainer: "cfg-html-left", title: "my-swal-title" },
    showCancelButton: true,
    confirmButtonText: "Chạy",
    cancelButtonText: "Hủy",
    confirmButtonColor: "#3085d6",
    cancelButtonColor: "#d33",

    preConfirm: () => {
      const formValues = {};
      document.querySelectorAll(".swal2-input").forEach((input) => {
        formValues[input.name] = input.value;
      });

      const workSunday = document.getElementById("work-sunday");
      formValues.work_sunday = workSunday.checked;
      formValues.selectedStep = selectedStep;

      const calendarEl = document.querySelector(".p-calendar");
      if (calendarEl) {
        formValues.selectedDates = formValues.selectedDates || [];
      }

      if (!formValues.start_date) {
        Swal.showValidationMessage("Vui lòng chọn ngày!");
        return false;
      }

      return formValues;
    },
  }).then((result) => {
    if (result.isConfirmed) {
      Swal.fire({
        title: "Đang chạy Auto Scheduler...",
        text: "Vui lòng chờ trong giây lát",
        allowOutsideClick: false,
        didOpen: () => Swal.showLoading(),
      });

      const { activeStart, activeEnd } = calendarRef.current?.getApi().view;

      axios
        .post("/Schedual/scheduleAll", {
          ...result.value,
          startDate: activeStart.toISOString(),
          endDate: activeEnd.toISOString(),
        })
        .then((res) => {
          let data = res.data;
          if (typeof data === "string") {
            data = data.replace(/^<!--.*?-->/, "").trim();
            data = JSON.parse(data);
          }

          Swal.fire({
            icon: "success",
            title: "Hoàn Thành Sắp Lịch",
            timer: 1000,
            showConfirmButton: false,
          });

          setEvents(data.events);
          setSumBatchByStage(data.sumBatchByStage);
          setPlan(data.plan);
        })
        .catch((err) => {
          Swal.fire({
            icon: "error",
            title: "Lỗi",
            timer: 1000,
            showConfirmButton: false,
          });
          console.error(
            "ScheduleAll error:",
            err.response?.data || err.message
          );
        });
    }
  });
};
