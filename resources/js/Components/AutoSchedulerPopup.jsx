import React, { useState, useRef } from "react";
import { Dialog } from "primereact/dialog";
import { Calendar } from "primereact/calendar";
import { Button } from "primereact/button";
import { Calendar } from 'primereact/calendar';
import { Stepper } from 'primereact/stepper';
import { StepperPanel } from 'primereact/stepperpanel';

export default function AutoSchedulerPopup({ visible, onHide, onSubmit }) {
  const [formValues, setFormValues] = useState({
    start_date: new Date().toISOString().split("T")[0],
    wt_bleding: 5,
    wt_forming: 5,
    wt_coating: 5,
    wt_blitering: 10,
    work_sunday: true,
    selectedDates: [],
  });

  const stepperRef = useRef(null);

  const handleCalendarChange = (e) => {
    const selected = e.value.map((d) => {
      const date = new Date(d);
      const y = date.getFullYear();
      const m = String(date.getMonth() + 1).padStart(2, "0");
      const da = String(date.getDate()).padStart(2, "0");
      return `${y}-${m}-${da}`;
    });
    setFormValues({ ...formValues, selectedDates: selected });
  };

  const handleConfirm = () => {
    if (!formValues.start_date) {
      alert("Vui lòng chọn ngày bắt đầu!");
      return;
    }
    onSubmit(formValues);
  };

  return (
    <Dialog
      header="Cấu Hình Chung Sắp Lịch"
      visible={visible}
      style={{ width: "50vw" }}
      modal
      onHide={onHide}
    >
      <div className="p-fluid">
        <div className="p-field">
          <label>Ngày bắt đầu sắp lịch</label>
          <input
            type="date"
            value={formValues.start_date}
            onChange={(e) => setFormValues({ ...formValues, start_date: e.target.value })}
          />
        </div>

        <div className="p-grid">
          <div className="p-col-6">
            <label>Trộn Thẩm Định</label>
            <input
              type="number"
              value={formValues.wt_bleding}
              onChange={(e) => setFormValues({ ...formValues, wt_bleding: e.target.value })}
            />
            <label>Định Hình Thẩm Định</label>
            <input
              type="number"
              value={formValues.wt_forming}
              onChange={(e) => setFormValues({ ...formValues, wt_forming: e.target.value })}
            />
          </div>
          <div className="p-col-6">
            <label>Trộn Thương Mại</label>
            <input
              type="number"
              value={formValues.wt_bleding2}
              onChange={(e) => setFormValues({ ...formValues, wt_bleding2: e.target.value })}
            />
          </div>
        </div>

        <div className="p-field mt-3">
          <label>Làm Chủ Nhật</label>
          <input
            type="checkbox"
            checked={formValues.work_sunday}
            onChange={(e) => setFormValues({ ...formValues, work_sunday: e.target.checked })}
          />
        </div>

        <div className="mt-4">
          <label>Ngày Không Sắp Lịch</label>
          <Calendar
            selectionMode="multiple"
            inline
            value={formValues.selectedDates.map((d) => new Date(d))}
            onChange={handleCalendarChange}
          />
        </div>

        <div className="mt-4">
          <label>Sắp Lịch Theo Công Đoạn</label>
          <Stepper ref={stepperRef}>
            <StepperPanel header="Trộn">
              <div>Trộn</div>
              <Button label="Tiếp" icon="pi pi-arrow-right" onClick={() => stepperRef.current.nextCallback()} />
            </StepperPanel>
            <StepperPanel header="Định Hình">
              <div>Định Hình</div>
              <Button label="Trở Lại" onClick={() => stepperRef.current.prevCallback()} />
              <Button label="Tiếp" onClick={() => stepperRef.current.nextCallback()} />
            </StepperPanel>
            <StepperPanel header="Bao Phim">
              <div>Bao Phim</div>
              <Button label="Trở Lại" onClick={() => stepperRef.current.prevCallback()} />
            </StepperPanel>
          </Stepper>
        </div>

        <div className="flex justify-content-end mt-4">
          <Button label="Hủy" className="p-button-secondary" onClick={onHide} />
          <Button label="Chạy" className="p-button-primary ml-2" onClick={handleConfirm} />
        </div>
      </div>
    </Dialog>
  );
}
