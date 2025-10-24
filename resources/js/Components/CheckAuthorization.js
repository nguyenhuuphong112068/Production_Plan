import Swal from 'sweetalert2';

/**
 * Kiểm tra quyền người dùng
 * @param {string} authorization - quyền hiện tại của user (vd: 'Admin1', 'Schedualer', ...)
 * @param {string[]} allowedRoles - danh sách quyền được phép (vd: ['Admin1', 'Schedualer'])
 * @returns {boolean} - true nếu được phép, false nếu không
 */
export function CheckAuthorization(authorization, allowedRoles = [], is_Swal = true) {
  if (!allowedRoles.includes(authorization)) {
    if (is_Swal){
      Swal.fire({
        icon: 'error',
        title: 'Bạn không có quyền thực hiện chức năng này!' + is_function,
        allowOutsideClick: false,
        showConfirmButton: false,
        timer: 1000
      });
    }
    return false;
  }
  return true;
}