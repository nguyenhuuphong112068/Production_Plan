import React from 'react';
import { Link } from '@inertiajs/react';


export default function LeftNav() {
  return (
    <aside className="main-sidebar sidebar-light-primary elevation-4" style={{ height: '100vh' }}>
      {/* Brand Logo */}
      <a href="/home" className="brand-link container d-flex justify-content-center align-items-center">
        <img
          src="/img/iconstella.svg"
          alt="AdminLTE Logo"
          style={{ opacity: 0.8, maxWidth: '43px', height: 'auto' }}
        />
      </a>

      <div className="sidebar">
        {/* Sidebar Menu */}
        <nav className="mt-2">
          <ul className="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">

            {/* Menu Dữ Liệu Gốc */}
            <li className="nav-item has-treeview">
              <a href="/materData/Testing" className="nav-link">
                <i className="nav-icon fas fa-tachometer-alt"></i>
                <p>
                  Dữ Liệu Gốc
                  <i className="right fas fa-angle-left"></i>
                </p>
              </a>

              <ul className="nav nav-treeview">
                <li className="nav-item">
                  <a href="/materData" className="block py-1"></a>
                    <i className="far fa-circle nav-icon"></i>
                    <p>Tên Sản Phẩm</p>
                </li>
                <li className="nav-item">
                  
                    <i className="far fa-circle nav-icon"></i>
                    <p>Chỉ Tiêu Kiểm</p>
                 
                </li>
                <li className="nav-item">
                 
                    <i className="far fa-circle nav-icon"></i>
                    <p>Thiết Bị Kiểm Nghiêm</p>
                 
                </li>
                <li className="nav-item">
                 
                    <i className="far fa-circle nav-icon"></i>
                    <p>Tổ Kiểm Nghiêm</p>
                 
                </li>
                <li className="nav-item">
                  
                    <i className="far fa-circle nav-icon"></i>
                    <p>Kiểm Nghiêm Viên</p>
                  
                </li>
              </ul>
            </li>

            {/* Danh Mục */}
            <li className="nav-item has-treeview">
              <a href="#" className="nav-link">
                <i className="nav-icon fas fa-tachometer-alt"></i>
                <p>
                  Danh Mục
                  <i className="right fas fa-angle-left"></i>
                </p>
              </a>
              <ul className="nav nav-treeview">
                <li className="nav-item">
                  
                    <i className="far fa-circle nav-icon"></i>
                    <p>Sản Phẩm KN</p>
                  
                </li>
              </ul>
            </li>

            {/* Nhận Mẫu */}
            <li className="nav-item has-treeview">
              <a href="#" className="nav-link">
                <i className="nav-icon fas fa-tachometer-alt"></i>
                <p>
                  Nhận Mẫu
                  <i className="right fas fa-angle-left"></i>
                </p>
              </a>
              <ul className="nav nav-treeview">
                <li className="nav-item">
                  
                    <i className="far fa-circle nav-icon"></i>
                    <p>Danh Sách Mẫu Chờ Kiểm</p>
                  
                </li>
              </ul>
            </li>

            {/* User */}
            <li className="nav-item">
              
                <i className="nav-icon fas fa-th"></i>
                <p>Quản Lý User</p>
              
            </li>

            {/* Audit Trail */}
            <li className="nav-item">
             
                <i className="nav-icon fas fa-th"></i>
                <p>Audit Trail</p>
              
            </li>

          </ul>
        </nav>
      </div>
    </aside>
  );
}
