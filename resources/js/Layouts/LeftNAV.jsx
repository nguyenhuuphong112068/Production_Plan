import React, { useState } from 'react';
import { FaKey, FaNewspaper, FaFileImport, FaCalendarAlt, FaHistory, FaUser, FaTh } from 'react-icons/fa';
import './Layout.css';
const LeftNAV = () => {
  const [isVisible, setIsVisible] = useState(false);

  return (
    <>
      {/* Vùng bắt chuột sát cạnh trái */}
      <div
        className="fixed top-0 left-0 h-full w-2 z-40"
        onMouseEnter={() => setIsVisible(true)}
      />

      {/* Sidebar chính */}
      <aside
        className={`fixed top-0 left-0 h-full w-60 bg-white shadow-lg border-r text-sm text-gray-800 z-50 transition-transform duration-300 ${
          isVisible ? 'translate-x-0' : '-translate-x-full'
        }`}
        onMouseLeave={() => setIsVisible(false)}
      >
        <div className="h-16 flex items-center justify-center border-b">
          <a href="/" className="flex items-center">
            <img src="/img/iconstella.svg" alt="Logo" className="w-10 h-auto opacity-80" />
          </a>
        </div>

        <nav className="px-4 py-2 overflow-y-auto">
          <ul className="space-y-2">
            {/* Dữ Liệu Gốc */}
            <li>
              <div className="flex items-center gap-2 text-gray-600 font-semibold mt-4">
                <FaKey />
                <span>Dữ Liệu Gốc</span>
              </div>
              <ul className="ml-6 mt-2 space-y-1">
                <li><a href="/product-name" className="hover:text-blue-500">Tên Sản Phẩm</a></li>
                <li><a href="/testing" className="hover:text-blue-500">Chỉ Tiêu Kiểm</a></li>
                <li><a href="/instrument" className="hover:text-blue-500">Thiết Bị Kiểm Nghiệm</a></li>
                <li><a href="/groups" className="hover:text-blue-500">Tổ Kiểm Nghiệm</a></li>
                <li><a href="/analyst" className="hover:text-blue-500">Kiểm Nghiệm Viên</a></li>
              </ul>
            </li>

            {/* Danh Mục */}
            <li>
              <div className="flex items-center gap-2 text-gray-600 font-semibold mt-4">
                <FaNewspaper />
                <span>Danh Mục</span>
              </div>
              <ul className="ml-6 mt-2">
                <li><a href="/category/product" className="hover:text-blue-500">Sản Phẩm KN</a></li>
              </ul>
            </li>

            {/* Nhận Mẫu */}
            <li>
              <div className="flex items-center gap-2 text-gray-600 font-semibold mt-4">
                <FaFileImport />
                <span>Nhận Mẫu</span>
              </div>
              <ul className="ml-6 mt-2">
                <li><a href="/import" className="hover:text-blue-500">Danh Sách Mẫu Chờ Kiểm</a></li>
              </ul>
            </li>

            {/* Lập Lịch */}
            <li>
              <div className="flex items-center gap-2 text-gray-600 font-semibold mt-4">
                <FaCalendarAlt />
                <span>Lập Lịch</span>
              </div>
              <ul className="ml-6 mt-2 space-y-1">
                <li><a href="/schedual" className="hover:text-blue-500">Lập Lịch KN</a></li>
                <li><a href="/schedual/view" className="hover:text-blue-500">Xem Lịch KN</a></li>
              </ul>
            </li>

            {/* Lịch sử */}
            <li className="mt-4">
              <a href="/history" className="flex items-center gap-2 text-gray-600 hover:text-blue-500">
                <FaHistory /> <span>Lịch Sử Kiểm Nghiệm</span>
              </a>
            </li>

            {/* Quản lý User */}
            <li>
              <a href="/user" className="flex items-center gap-2 text-gray-600 hover:text-blue-500">
                <FaUser /> <span>Quản Lý User</span>
              </a>
            </li>

            {/* Audit Trail */}
            <li>
              <a href="/audit-trail" className="flex items-center gap-2 text-gray-600 hover:text-blue-500">
                <FaTh /> <span>Audit Trail</span>
              </a>
            </li>
          </ul>

    





        </nav>
      </aside>
    </>
  );
};

export default LeftNAV;
