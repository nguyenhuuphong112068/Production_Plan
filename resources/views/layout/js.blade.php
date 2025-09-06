
        <script src="{{asset ('dataTable/plugins/jquery/jquery.min.js')}}"></script>
        <script src="{{asset ('dataTable/plugins/bootstrap/js/bootstrap.bundle.min.js')}}"></script>
        <script src="{{asset ('dataTable/plugins/datatables/jquery.dataTables.min.js')}}"></script>
        <script src="{{asset ('dataTable/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js')}}"></script>
        <script src="{{asset ('dataTable/plugins/datatables-responsive/js/dataTables.responsive.min.js')}}"></script>
        <script src="{{asset ('dataTable/plugins/datatables-responsive/js/responsive.bootstrap4.min.js')}}"></script>
        <script src="{{asset ('dataTable/dist/js/adminlte.min.js')}}"></script>
        <script src="{{asset ('dataTable/dist/js/demo.js')}}"></script>

        <!-- Select2 -->
        <script src="{{asset ('dataTable/plugins/select2/js/select2.full.min.js')}}"></script>
        <!-- Bootstrap4 Duallistbox -->
        <script src="{{asset ('dataTable/plugins/bootstrap4-duallistbox/jquery.bootstrap-duallistbox.min.js')}}"></script>
        <!-- InputMask -->
        <script src="{{asset ('dataTable/plugins/moment/moment.min.js')}}"></script>

        <script src="{{asset ('dataTable/plugins/inputmask/min/jquery.inputmask.bundle.min.js')}}"></script>
        <!-- date-range-picker -->
        <script src="{{asset ('dataTable/plugins/daterangepicker/daterangepicker.js')}}"></script>
        <!-- bootstrap color picker -->
        <script src="{{asset ('dataTable/plugins/bootstrap-colorpicker/js/bootstrap-colorpicker.min.js')}}"></script>
        <!-- Tempusdominus Bootstrap 4 -->
        <script src="{{asset ('dataTable/plugins/tempusdominus-bootstrap-4/js/tempusdominus-bootstrap-4.min.js')}}"></script>
        <!-- Bootstrap Switch -->
        <script src="{{asset ('dataTable/plugins/bootstrap-switch/js/bootstrap-switch.min.js')}}"></script>

        
     
        <script src="{{ asset('dataTable/plugins/chart.js/Chart.min.js') }}"></script>

        <script src="{{asset ('dataTable/dist/js/demo.js')}}"></script>
        <script src="{{asset ('dataTable/dist/js/pages/dashboard3.js')}}"></script>

        <!-- Thêm DataTables CSS -->
        <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">

        <!-- Thêm DataTables JS -->
       <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>  

        
        {{-- <script src="{{ asset('js/vendor/jquery-1.12.4.min.js') }}"></script>
        <script src="{{ asset('js/popper.min.js') }}"></script>
        <script src="{{ asset('js/bootstrap.min.js') }}"></script> --}}

        {{-- Chống double submit --}}
        <script>
                /**
                * Chặn double submit cho form
                * @param {string} formSelector - Selector của form (vd: "#myForm" hoặc ".ajax-form")
                * @param {string} buttonSelector - Selector của nút submit (vd: "#btnSave")
                */
                function preventDoubleSubmit(formSelector, buttonSelector) {
                       
                        const form = document.querySelector(formSelector);
                        const btn = document.querySelector(buttonSelector);

                        if (!form || !btn) return;

                        form.addEventListener("submit", function () {
                        if (btn.disabled) {
                                // đã disable rồi thì ngăn submit thêm lần nữa
                                event.preventDefault();
                                return;
                        }
                        btn.disabled = true;
                        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang lưu...';
                        });
                }

                // Gọi hàm ở bất cứ form nào bạn muốn
                document.addEventListener("DOMContentLoaded", function () {
                        preventDoubleSubmit("form", "#btnSave"); 
                        // có thể gọi nhiều lần cho form khác:
                        // preventDoubleSubmit("#form2", "#btnUpdate");
                });
        </script>