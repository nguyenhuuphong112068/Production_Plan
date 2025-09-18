<?php

namespace App\Http\Controllers;

use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
// use mysqli;

class UploadDataController extends Controller
{
        public function index(){
                   return view('upload.form_load');
        }

        

        public function import(Request $request) {
            $request->validate([
                'excel_file' => 'required|mimes:xlsx,xls'
            ]);

            $path = $request->file('excel_file')->getRealPath();

            // Load file Excel
            $spreadsheet = IOFactory::load($path);
            $rows = $spreadsheet->getActiveSheet()->toArray();

            unset($rows[0]); // Bỏ dòng tiêu đề nếu có
            $check = false;
            foreach ($rows as $row) {
                
                if ($request->table === 'user_management' ) {
                    $check = DB::table('user_management')->insert([
                        'id'=> $row[0],
                        'userName'=> $row[1],
                        'userGroup'=> $row[2],
                        'passWord'=> $row[3],
                        'fullName' => $row[4],
                        'deparment' => $row[5],
                        'groupName' => $row[6],
                        'mail' => $row[7],
                        'isLocked' => $row[8],
                        'isActive' => $row[9],
                        'changePWdate' => $row[10],
                        'hisPW_1' => $row[11],
                        'hisPW_2' => $row[12],
                        'hisPW_3' => $row[13],
                        'prepareBy' => "Nguyễn Hữu Phong",
                        'created_at' => now (),                   
                ]);}
                elseif ($request->table === 'room') {     
                    $check = DB::table('room')->insert([
                        'id'         => $row[0],
                        'order_by'         => $row[1],
                        'code'             => $row[2],
                        'name'             => $row[3],
                        'production_group' => $row[4],
                        'stage'            => $row[5],
                        'stage_code'       => $row[6],
                        'deparment_code'   => $row[7],
                        'prepareBy' => "Auto-generate",
                ]);}
                elseif ($request->table === 'intermediate_category') {     
                    $check = DB::table('intermediate_category')->insert([
                        'id'=> $row[0],
                        'intermediate_code'=> $row[1],
                        'product_name_id'=> $row[2],
                        'batch_size'=> $row[3],
                        'unit_batch_size'=> $row[4],
                        'batch_qty'=> $row[5],
                        'unit_batch_qty'=> $row[6],
                        'dosage_id'=> $row[7],
                        'weight_1'=> $row[8],
                        'weight_2'=> $row[9],
                        'prepering'=> $row[10],
                        'blending'=> $row[11],
                        'forming'=> $row[12],
                        'coating'=> $row[13],
                        'quarantine_total'=> $row[14],
                        'quarantine_weight'=> $row[15],
                        'quarantine_preparing'=> $row[16],
                        'quarantine_blending'=> $row[17],
                        'quarantine_forming'=> $row[18],
                        'quarantine_coating'=> $row[19],
                        'quarantine_time_unit'  => $row[20],
                        'deparment_code'=> $row[21],            
                        'prepared_by' => "Auto-generate",
                ]);}
                elseif ($request->table === 'finished_product_category') {
                        $check = DB::table('finished_product_category')->insert([
                        'id'=> $row[0],
                        'process_code'=> $row[1],                       
                        'finished_product_code'=> $row[2],
                        'intermediate_code'=> $row[3],
                        'product_name_id'=> $row[4],
                        'market_id'=> $row[5],
                        'specification_id'=> $row[6],
                        'batch_qty'=> $row[7],
                        'unit_batch_qty'=> $row[8],
                        'primary_parkaging'=> $row[9],
                        'secondary_parkaging'=> $row[10],
                        'deparment_code'=> $row[11],
                        'active' => 1,  
                        'prepared_by' => "Auto-generate",
                ]);}
                elseif ($request->table === 'plan_master'){
                    
                    $check = DB::table('plan_master')->insert([
                            'plan_list_id'=> $row[0],
                            'product_caterogy_id'=> $row[1],
                            'level'=> $row[2],
                            'batch'=> $row[3],
                            'expected_date'=> $row[4],
                            'is_val'=> $row[5],
                            'after_weigth_date'=> $row[6],
                            'before_weigth_date'=> $row[7],
                            'after_parkaging_date'=> $row[8],
                            'before_parkaging_date'=> $row[9],
                            'material_source'=> $row[10],
                            'only_parkaging'=> $row[11],
                            'percent_parkaging'=> $row[12],
                            'deparment_code'=> $row[13],
                            'note'=> $row[14],
                            'prepared_by' => "Nguyễn Hữu Phong",
                ]);}
                elseif ($request->table === 'quota') {
                    $check = DB::table('quota')->insert([
                        'id'=> $row[10],
                        'process_code'=> $row[11],
                        'intermediate_code'=> $row[0],
                        'finished_product_code'=> $row[1],
                        'room_id'=> $row[2],
                        'p_time' => $row[3],
                        'm_time' => $row[4],
                        'C1_time' => $row[5],
                        'C2_time' => $row[6],
                        'stage_code' => $row[7],
                        'maxofbatch_campaign' => $row[8],
                        'deparment_code' => $row[9],
                        'note' => "NA",
                        'created_at' => now (),             
                        'prepared_by' => "Nguyễn Hữu Phong",
                ]);}
                elseif ($request->table === 'product_name') {
                    $check = DB::table('product_name')->insert([
                        'id'=> $row[0],
                        'name'=> $row[1],
                        'shortName'=> $row[2],
                        'deparment_code'=> $row[3],
                        'active' => true,
                        'productType'=> "NA",             
                        'prepareBy' => "Auto-generate",
                ]);}
                elseif ($request->table === 'source_material') {
                    $check = DB::table('source_material')->insert([
                        'id'=> $row[0],
                        'intermediate_code'=> $row[1],
                        'name'=> $row[2],
                        'active' => true,          
                        'prepared_by' => "Auto-generate",
                ]);}
                elseif ($request->table === 'stages') {
                    $check = DB::table('stages')->insert([
                        'id'=> $row[0],
                        'name'=> $row[1],   
                        'code'=> $row[2],
                        'create_by' => "Nguyễn Hữu Phong",
                ]);}
                elseif ($request->table === 'stage_groups') {
                    $check = DB::table('stage_groups')->insert([
                        'id'=> $row[0],
                        'name'=> $row[1],   
                        'code'=> $row[2],
                        'create_by' => "Nguyễn Hữu Phong",
                ]);}
                 elseif ($request->table === 'production') {
                    $check = DB::table('production')->insert([
                        'id'=> $row[0],
                        'name'=> $row[1],   
                        'code'=> $row[2],
                        'create_by' => "Nguyễn Hữu Phong",
                ]);}
                elseif ($request->table === 'dosage') {
                    $check = DB::table('dosage')->insert([
                        'id'=> $row[0],
                        'name'=> $row[1], 
                        'active' => 1,  
                        'created_by' => "Auto-generate",
                ]);}
                 elseif ($request->table === 'specification') {
                    $check = DB::table('specification')->insert([
                        'id'=> $row[0],
                        'name'=> $row[1], 
                        'created_by' => "Auto-generate",
                ]);}
                 elseif ($request->table === 'unit') {
                    $check = DB::table('unit')->insert([
                        'id'=> $row[0],
                        'code'=> $row[1], 
                        'name'=> $row[2], 
                        'active' => 1,  
                        'created_by' => "Auto-generate",
                ]);} elseif ($request->table === 'market') {
                    $check = DB::table('market')->insert([
                        'id'=> $row[0],
                        'code'=> $row[1], 
                        'name'=> $row[2], 
                        'active' => 1,  
                        'created_by' => "Auto-generate",
                ]);}

                
                
            }
                
            if ($check) {dd ("OK");};
            
            //return back()->with('success', 'Import thành công!');
        }

        public function import_permission () {
            $permissions = [
                [
                    'id'                => 1,
                    'permission_group'  => 1, 
                    'name'              => 'plan_production_store', 
                    'display_name'       => 'Tạo Kế Hoạch Sản Xuất',  
                    'description'        => 'Tạo Mới Kế Hoạch Sản Xuất',
                ],
                [
                    'id'                => 2,
                    'permission_group'  => 1, 
                    'name'              => 'plan_production_update', 
                    'display_name'       => 'Cập nhật Kế Hoạch Sản Xuất',  
                    'description'        => 'Cập nhật kế hoạch Sản Xuất',
                ],
                [
                    'id'                => 3,
                    'permission_group'  => 1, 
                    'name'              => 'plan_production_deActive', 
                    'display_name'       => 'Hủy Kế Hoạch Sản Xuất',  
                    'description'        => 'Hủy kế hoạch sản Xuất',
                ],
        
            ];

            foreach ($permissions as $permission ){
                DB::table('permissions')->insert([
                    'id'                => $permission[0],
                    'permission_group'  => $permission[1], 
                    'name'              => $permission[2], 
                    'display_name'       => $permission[3],  
                    'description'        => $permission[4],
                ]);
            }
            
        }
                
            
}