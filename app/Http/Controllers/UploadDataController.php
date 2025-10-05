<?php

namespace App\Http\Controllers;

use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
// use mysqli;

class UploadDataController extends Controller
{
    public function index()
    {
        return view('upload.form_load');
    }

    public function import(Request $request)
    {
        $request->validate([
            'excel_file' => 'required|mimes:xlsx,xls',
            'table' => 'required'
        ]);

        $path = $request->file('excel_file')->getRealPath();
        $spreadsheet = IOFactory::load($path);
        $rows = $spreadsheet->getActiveSheet()->toArray();
        unset($rows[0]); // Bỏ dòng tiêu đề
        //dd ($rows);

        // ⚙️ Cấu hình mapping bảng <-> cột
        $tableMappings = [
            'user_management' => [
                'columns' => [
                    'id','userName','userGroup','passWord','fullName','deparment','groupName',
                    'mail','isLocked','isActive','changePWdate','hisPW_1','hisPW_2','hisPW_3'
                ],
                'extra' => [
                    'prepareBy' => 'Nguyễn Hữu Phong',
                    'created_at' => now(),
                ],
            ],

            'room' => [
                'columns' => [
                    'id','order_by','code','name','production_group','stage','stage_code','deparment_code'
                ],
                'extra' => ['prepareBy' => 'Auto-generate'],
            ],

            'intermediate_category' => [
                'columns' => [
                    'id','intermediate_code','product_name_id','batch_size','unit_batch_size',
                    'batch_qty','unit_batch_qty','dosage_id','weight_1','weight_2','prepering',
                    'blending','forming','coating','quarantine_total','quarantine_weight',
                    'quarantine_preparing','quarantine_blending','quarantine_forming','quarantine_coating',
                    'quarantine_time_unit','deparment_code'
                ],
                'extra' => ['prepared_by' => 'Auto-generate'],
            ],

            'finished_product_category' => [
                'columns' => [
                    'id',
                    'process_code',
                    'intermediate_code',
                    'finished_product_code',
                    'product_name_id',
                    'market_id',
                    'specification_id',
                    'batch_qty',
                    'unit_batch_qty',
                    'primary_parkaging',     // DG thứ nhất
                    'secondary_parkaging',   // DG thứ hai
                    'deparment_code'         // bophan
                ],
                'extra' => [
                    'active' => 1,
                    'prepared_by' => 'Auto-generate',
                ],
            ],

            'plan_master' => [
                'columns' => [
                    'plan_list_id','product_caterogy_id','level','batch','expected_date',
                    'is_val','after_weigth_date','before_weigth_date','after_parkaging_date',
                    'before_parkaging_date','material_source','only_parkaging','percent_parkaging',
                    'deparment_code','note'
                ],
                'extra' => ['prepared_by' => 'Nguyễn Hữu Phong'],
            ],


            'product_name' => [
                'columns' => ['id','name','shortName','deparment_code'],
                'extra' => [
                    'active' => true,
                    'productType' => 'NA',
                    'prepareBy' => 'Auto-generate',
                ],
            ],

            'quota' => [
                'columns' => [
                    'id',
                    'process_code',
                    'intermediate_code',
                    'finished_product_code',
                    'room_id',
                    'p_time',
                    'm_time',
                    'C1_time',
                    'C2_time',
                    'stage_code',
                    'maxofbatch_campaign',
                    'note',
                    'deparment_code'
                ],
                'extra' => [
                    'active' => true,
                    'tank' => 0,
                    'keep_dry' => 0,
                    'prepared_by' => 'Auto-generate',
                    'created_at' => now(),
                ],
            ],


            'source_material' => [
                'columns' => ['id','intermediate_code','name'],
                'extra' => ['active' => true, 'prepared_by' => 'Auto-generate'],
            ],

            'stages' => [
                'columns' => ['id','name','code'],
                'extra' => ['create_by' => 'Nguyễn Hữu Phong'],
            ],

            'stage_groups' => [
                'columns' => ['id','name','code'],
                'extra' => ['create_by' => 'Nguyễn Hữu Phong'],
            ],

            'production' => [
                'columns' => ['id','name','code'],
                'extra' => ['create_by' => 'Nguyễn Hữu Phong'],
            ],

            'dosage' => [
                'columns' => ['id','name'],
                'extra' => ['active' => 1, 'created_by' => 'Auto-generate'],
            ],

            'specification' => [
                'columns' => ['id','name'],
                'extra' => ['created_by' => 'Auto-generate'],
            ],

            'unit' => [
                'columns' => ['id','code','name'],
                'extra' => ['active' => 1, 'created_by' => 'Auto-generate'],
            ],

            'market' => [
                'columns' => ['id','code','name'],
                'extra' => ['active' => 1, 'created_by' => 'Auto-generate'],
            ],

            'roles' => [
                'columns' => ['id','name','display_name','description'],
                'extra' => [],
            ],
        ];

        $table = $request->table;

        if (!isset($tableMappings[$table])) {
            return back()->with('error', 'File không hợp lệ hoặc lỗi khi import.');
        }

        $mapping = $tableMappings[$table];
        $inserted = 0;

        foreach ($rows as $row) {
            $data = [];

            foreach ($mapping['columns'] as $i => $colName) {
                $data[$colName] = $row[$i] ?? null;
            }

            $data = array_merge($data, $mapping['extra']);
                DB::table($table)->insert($data);
            $inserted++;
        }

        return back()->with('success', "Đã import $inserted dòng vào bảng [$table] thành công!");
    }
}
