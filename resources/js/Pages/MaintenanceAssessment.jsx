import React, { useCallback, useEffect, useMemo, useState } from 'react';
import axios from 'axios';
import moment from 'moment';
import { DataTable } from 'primereact/datatable';
import { Column } from 'primereact/column';
import { InputText } from 'primereact/inputtext';
import { MultiSelect } from 'primereact/multiselect';
import { Dialog } from 'primereact/dialog';
import { Button } from 'primereact/button';

const STAR_LABELS = ['', 'Rất không hài lòng', 'Không hài lòng', 'Bình thường', 'Hài lòng', 'Rất hài lòng'];

const Stars = ({ value }) => (
    <span style={{ color: '#f5b301', whiteSpace: 'nowrap', fontSize: 16 }} title={STAR_LABELS[value] || ''}>
        {'★'.repeat(value)}
        <span style={{ color: '#d9d9d9' }}>{'★'.repeat(5 - value)}</span>
    </span>
);

const MaintenanceAssessment = () => {
    const [month, setMonth] = useState(moment().format('YYYY-MM'));
    const [rows, setRows] = useState([]);
    const [byEmployee, setByEmployee] = useState([]);
    const [summary, setSummary] = useState({ total: 0, average: 0, distribution: {} });
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);
    const [search, setSearch] = useState('');
    const [tab, setTab] = useState('detail');

    // Sửa nhân sự liên quan của một đánh giá đã có
    const [editing, setEditing] = useState(null);
    const [employees, setEmployees] = useState([]);
    const [editCodes, setEditCodes] = useState([]);
    const [editDept, setEditDept] = useState('EN');
    const [savingEmployees, setSavingEmployees] = useState(false);
    const [canUpdateEmployees, setCanUpdateEmployees] = useState(false);

    const load = useCallback((selectedMonth) => {
        setLoading(true);
        setError(null);

        axios.get('/MaintenanceAssessment/monthly', { params: { month: selectedMonth } })
            .then(({ data }) => {
                setRows(data.rows || []);
                setByEmployee(data.by_employee || []);
                setSummary(data.summary || { total: 0, average: 0, distribution: {} });
                setCanUpdateEmployees(!!data.can_update_employees);
            })
            .catch((err) => setError(err.response?.data?.message || 'Không tải được dữ liệu đánh giá.'))
            .finally(() => setLoading(false));
    }, []);

    useEffect(() => { load(month); }, [month, load]);

    const maxCount = useMemo(
        () => Math.max(1, ...Object.values(summary.distribution || {})),
        [summary]
    );

    const openEmployeeEditor = (row) => {
        setEditing(row);
        setEditCodes(row.employees_code || []);
        setEditDept(row.type_name === 'Hiệu chuẩn' ? 'QA' : 'EN');

        if (employees.length === 0) {
            axios.get('/MaintenanceAssessment/employees')
                .then(({ data }) => setEmployees(data.employees || []))
                .catch(() => setError('Không tải được danh sách nhân viên.'));
        }
    };

    // Người đã chọn luôn nằm trong danh sách dù không thuộc bộ phận đang lọc
    const employeeOptions = useMemo(() => employees
        .filter(emp => !editDept || (emp.departments || []).includes(editDept) || editCodes.includes(emp.code))
        .map(emp => ({
            label: `${emp.code} - ${emp.name}${(emp.departments || []).length ? ` [${emp.departments.join(', ')}]` : ''}`,
            value: emp.code,
        })), [employees, editDept, editCodes]);

    const saveEmployees = () => {
        setSavingEmployees(true);

        axios.put('/MaintenanceAssessment/updateEmployees', { id: editing.id, employees_code: editCodes })
            .then(() => {
                setEditing(null);
                load(month);
            })
            .catch((err) => setError(err.response?.data?.message || 'Không cập nhật được nhân sự liên quan.'))
            .finally(() => setSavingEmployees(false));
    };

    const dateBody = (row) => (
        <div style={{ whiteSpace: 'nowrap' }}>
            <div>{moment(row.planned_start).format('DD/MM/YYYY')}</div>
            <small className="text-muted">{moment(row.planned_start).format('HH:mm')} ➝ {moment(row.planned_end).format('HH:mm')}</small>
        </div>
    );

    const equipmentBody = (row) => (
        <div>
            <div><b>{row.inst_id || '-'}</b> {row.inst_name ? `- ${row.inst_name}` : ''}</div>
            {row.Eqp_name && <small className="text-muted">{row.parent_eqp_id} - {row.Eqp_name}</small>}
        </div>
    );

    const employeesBody = (row) => (
        <div className="d-flex align-items-start" style={{ gap: 6 }}>
            <div style={{ display: 'flex', flexWrap: 'wrap', gap: 4, flex: 1 }}>
                {(row.employees || []).length
                    ? row.employees.map((name, i) => (
                        <span key={i} style={{ background: '#eef2ff', border: '1px solid #c7d2fe', borderRadius: 10, padding: '1px 8px', fontSize: 12 }}>{name}</span>
                    ))
                    : <span className="text-muted">-</span>}
            </div>
            {canUpdateEmployees && (
                <Button
                    icon="pi pi-pencil"
                    className="p-button-text p-button-sm p-button-secondary"
                    tooltip="Cập nhật nhân sự liên quan"
                    onClick={() => openEmployeeEditor(row)}
                />
            )}
        </div>
    );

    const distributionBody = (row) => (
        <div className="d-flex align-items-center" style={{ gap: 3 }}>
            {[1, 2, 3, 4, 5].map((star) => {
                const count = row.distribution?.[star] || 0;
                return (
                    <span key={star} title={`${count} lượt ${star} sao`}
                        style={{
                            minWidth: 26, textAlign: 'center', fontSize: 12, borderRadius: 3, padding: '1px 4px',
                            background: count ? '#fff7e0' : '#f6f6f6',
                            border: `1px solid ${count ? '#f5b301' : '#e5e5e5'}`,
                            color: count ? '#8a6100' : '#bbb'
                        }}>
                        {star}★<b style={{ marginLeft: 3 }}>{count}</b>
                    </span>
                );
            })}
        </div>
    );

    const assessorBody = (row) => (
        <div style={{ whiteSpace: 'nowrap' }}>
            <div>{row.updated_by || row.created_by || '-'}</div>
            <small className="text-muted">{moment(row.updated_at || row.created_at).format('HH:mm DD/MM/YYYY')}</small>
        </div>
    );

    return (
        <div className="p-3">
            <div className="d-flex flex-wrap align-items-center justify-content-between mb-3" style={{ gap: 12 }}>
                <h5 className="m-0" style={{ color: '#003A4F', fontWeight: 700 }}>
                    ĐÁNH GIÁ CÔNG TÁC BẢO TRÌ - HIỆU CHUẨN
                </h5>
                <div className="d-flex align-items-center" style={{ gap: 8 }}>
                    <span className="p-input-icon-left">
                        <i className="pi pi-search" />
                        <InputText
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            placeholder="Tìm thiết bị, phòng, nhận xét..."
                            className="p-inputtext-sm"
                            style={{ width: 260 }}
                        />
                    </span>
                    <label className="m-0" style={{ fontWeight: 600 }}>Tháng:</label>
                    <input
                        type="month"
                        className="form-control form-control-sm"
                        style={{ width: 160 }}
                        value={month}
                        onChange={(e) => e.target.value && setMonth(e.target.value)}
                    />
                </div>
            </div>

            <div className="d-flex flex-wrap mb-3" style={{ gap: 16 }}>
                <div className="card p-3" style={{ minWidth: 200 }}>
                    <div className="text-muted" style={{ fontSize: 13 }}>Số lượt đánh giá</div>
                    <div style={{ fontSize: 28, fontWeight: 700, color: '#003A4F' }}>{summary.total}</div>
                </div>

                <div className="card p-3" style={{ minWidth: 220 }}>
                    <div className="text-muted" style={{ fontSize: 13 }}>Điểm trung bình</div>
                    <div style={{ fontSize: 28, fontWeight: 700, color: '#f5b301' }}>
                        {Number(summary.average || 0).toFixed(2)} <span style={{ fontSize: 16, color: '#999' }}>/ 5</span>
                    </div>
                    <Stars value={Math.round(summary.average || 0)} />
                </div>

                <div className="card p-3" style={{ minWidth: 280, flex: 1 }}>
                    <div className="text-muted mb-2" style={{ fontSize: 13 }}>Phân bố số sao</div>
                    {[5, 4, 3, 2, 1].map((star) => {
                        const count = summary.distribution?.[star] || 0;
                        return (
                            <div key={star} className="d-flex align-items-center" style={{ gap: 8, marginBottom: 3 }}>
                                <span style={{ width: 34, color: '#f5b301', fontSize: 13 }}>{star} ★</span>
                                <div style={{ flex: 1, background: '#f1f1f1', borderRadius: 4, height: 10 }}>
                                    <div style={{ width: `${(count / maxCount) * 100}%`, background: '#f5b301', height: '100%', borderRadius: 4 }} />
                                </div>
                                <span style={{ width: 28, textAlign: 'right', fontSize: 13 }}>{count}</span>
                            </div>
                        );
                    })}
                </div>
            </div>

            {error && <div className="alert alert-danger">{error}</div>}

            <ul className="nav nav-tabs mb-2">
                <li className="nav-item">
                    <a href="#" className={`nav-link ${tab === 'detail' ? 'active' : ''}`}
                        onClick={(e) => { e.preventDefault(); setTab('detail'); }}>
                        Chi tiết đánh giá ({rows.length})
                    </a>
                </li>
                <li className="nav-item">
                    <a href="#" className={`nav-link ${tab === 'employee' ? 'active' : ''}`}
                        onClick={(e) => { e.preventDefault(); setTab('employee'); }}>
                        Thống kê theo nhân sự ({byEmployee.length})
                    </a>
                </li>
            </ul>

            {tab === 'detail' ? (
                <DataTable
                    value={rows}
                    loading={loading}
                    globalFilter={search}
                    dataKey="id"
                    scrollable
                    scrollHeight="calc(100vh - 440px)"
                    className="p-datatable-sm p-datatable-gridlines"
                    emptyMessage={`Chưa có đánh giá nào trong tháng ${moment(month, 'YYYY-MM').format('MM/YYYY')}.`}
                    paginator
                    rows={20}
                    rowsPerPageOptions={[10, 20, 50, 100]}
                    currentPageReportTemplate="Đang xem {first} đến {last} trong tổng số {totalRecords} đánh giá"
                    paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
                    sortField="planned_start"
                    sortOrder={1}
                >
                    <Column header="Ngày thực hiện" body={dateBody} sortable sortField="planned_start" style={{ width: 140 }} />
                    <Column header="Phòng / Khu vực" field="room_code" sortable style={{ width: 130 }}
                        body={(row) => <span title={row.room_name}>{row.room_code || '-'}</span>} />
                    <Column header="Thiết bị" body={equipmentBody} field="inst_id" sortable style={{ minWidth: 220 }} />
                    <Column header="Loại" field="type_name" sortable style={{ width: 130 }}
                        body={(row) => (
                            <>
                                {row.type_name || '-'}
                                {row.is_manual && (
                                    <span style={{ marginLeft: 4, fontSize: 11, background: '#fff3cd', border: '1px solid #ffeeba', color: '#856404', borderRadius: 3, padding: '0 4px' }}>
                                        Ngoài KH
                                    </span>
                                )}
                            </>
                        )} />
                    <Column header="Số sao" field="star_rating" sortable style={{ width: 130 }}
                        body={(row) => <Stars value={row.star_rating} />} />
                    <Column header="Nhận xét" field="comment" style={{ minWidth: 240 }}
                        body={(row) => row.comment || <span className="text-muted">-</span>} />
                    <Column header="Nhân viên thực hiện" body={employeesBody} style={{ minWidth: 240 }} />
                    <Column header="Người đánh giá" body={assessorBody} style={{ width: 170 }} />
                </DataTable>
            ) : (
                <DataTable
                    value={byEmployee}
                    loading={loading}
                    globalFilter={search}
                    dataKey="code"
                    scrollable
                    scrollHeight="calc(100vh - 440px)"
                    className="p-datatable-sm p-datatable-gridlines"
                    emptyMessage={`Chưa có nhân sự nào được ghi nhận trong tháng ${moment(month, 'YYYY-MM').format('MM/YYYY')}.`}
                    paginator
                    rows={20}
                    rowsPerPageOptions={[10, 20, 50, 100]}
                    currentPageReportTemplate="Đang xem {first} đến {last} trong tổng số {totalRecords} nhân sự"
                    paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
                    sortField="total"
                    sortOrder={-1}
                >
                    <Column header="Mã NV" field="code" sortable style={{ width: 110 }} />
                    <Column header="Họ tên" field="name" sortable style={{ minWidth: 200 }} />
                    <Column header="Số lượt được đánh giá" field="total" sortable style={{ width: 170 }} />
                    <Column header="Điểm trung bình" field="average" sortable style={{ width: 190 }}
                        body={(row) => (
                            <div className="d-flex align-items-center" style={{ gap: 6 }}>
                                <b style={{ color: '#f5b301' }}>{Number(row.average).toFixed(2)}</b>
                                <Stars value={Math.round(row.average)} />
                            </div>
                        )} />
                    <Column header="Thấp / Cao nhất" style={{ width: 140 }}
                        body={(row) => `${row.min} ★ / ${row.max} ★`} />
                    <Column header="Phân bố số sao" body={distributionBody} style={{ minWidth: 220 }} />
                </DataTable>
            )}

            <Dialog
                header="Cập nhật nhân sự liên quan"
                visible={!!editing}
                style={{ width: 560, maxWidth: '95vw' }}
                onHide={() => setEditing(null)}
                footer={
                    <div>
                        <Button label="Đóng" className="p-button-text p-button-secondary" onClick={() => setEditing(null)} />
                        <Button label={savingEmployees ? 'Đang lưu...' : 'Lưu'} icon="pi pi-check"
                            disabled={savingEmployees} onClick={saveEmployees} />
                    </div>
                }
            >
                {editing && (
                    <>
                        <div style={{ background: '#f0f7ff', borderLeft: '3px solid #3085d6', borderRadius: 4, padding: '8px 10px', fontSize: 13, marginBottom: 14 }}>
                            <div><b>{editing.inst_id}</b> {editing.inst_name ? `- ${editing.inst_name}` : ''}</div>
                            <div className="text-muted">
                                {editing.room_code || '-'} · {editing.type_name} · {moment(editing.planned_start).format('HH:mm DD/MM/YYYY')}
                            </div>
                        </div>

                        <div className="d-flex align-items-center mb-2" style={{ gap: 14, fontSize: 13 }}>
                            {[['EN', 'Bảo trì (EN)'], ['QA', 'Hiệu chuẩn (QA)'], ['', 'Tất cả']].map(([value, label]) => (
                                <label key={value} className="d-flex align-items-center m-0" style={{ gap: 5, cursor: 'pointer', fontWeight: 'normal' }}>
                                    <input type="radio" name="edit-dept" value={value}
                                        checked={editDept === value} onChange={() => setEditDept(value)} />
                                    {label}
                                </label>
                            ))}
                        </div>

                        <MultiSelect
                            value={editCodes}
                            options={employeeOptions}
                            onChange={(e) => setEditCodes(e.value)}
                            filter
                            display="chip"
                            showClear
                            placeholder="Chọn nhân viên thực hiện..."
                            emptyMessage="Đang tải danh sách nhân viên..."
                            emptyFilterMessage="Không tìm thấy nhân viên phù hợp."
                            className="w-100"
                            style={{ width: '100%' }}
                        />
                        <div className="text-muted mt-2" style={{ fontSize: 12 }}>
                            Đã chọn {editCodes.length} nhân viên · danh sách đang lọc {editDept || 'tất cả bộ phận'}
                        </div>
                    </>
                )}
            </Dialog>
        </div>
    );
};

export default MaintenanceAssessment;
