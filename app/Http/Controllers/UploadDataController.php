<?php

namespace App\Http\Controllers;

use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use mysqli;

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
                        'prepareBy' => "Nguyễn Hữu Phong",
                ]);}
                elseif ($request->table === 'intermediate_category') {     
                    $check = DB::table('intermediate_category')->insert([
                        'intermediate_code'=> $row[0],
                        'name'=> $row[1],
                        'batch_size'=> $row[2],
                        'unit_batch_size'=> $row[3],
                        'batch_qty'=> $row[4],
                        'unit_batch_qty'=> $row[5],
                        'dosage'=> $row[6],
                        'weight_1'=> $row[7],
                        'weight_2'=> $row[8],
                        'prepering'=> $row[9],
                        'blending'=> $row[10],
                        'forming'=> $row[11],
                        'coating'=> $row[12],
                        'quarantine_total'=> 0,
                        'quarantine_weight'=> $row[13],
                        'quarantine_preparing'=> $row[14],
                        'quarantine_blending'=> $row[15],
                        'quarantine_forming'=> $row[16],
                        'quarantine_coating'=> $row[17],
                        'deparment_code'=> $row[18],            
                        'prepared_by' => "Nguyễn Hữu Phong",
                ]);}
                elseif ($request->table === 'finished_product_category') {
                        $check = DB::table('finished_product_category')->insert([
                        'id'=> $row[0],
                        'process_code'=> $row[1],
                        'intermediate_code'=> $row[2],
                        'finished_product_code'=> $row[3],
                        'name'=> $row[4],
                        'market'=> $row[5],
                        'specification'=> $row[6],
                        'batch_qty'=> $row[7],
                        'unit_batch_qty'=> $row[8],
                        'primary_parkaging'=> $row[9],
                        'secondary_parkaging'=> $row[10],
                        'deparment_code'=> $row[11],  
                        'prepared_by' => "Nguyễn Hữu Phong",
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
                        'name'=> $row[0],
                        'shortName'=> $row[1],
                        'deparment_code'=> $row[2],
                        'active' => true,
                        'productType'=> "Thành Phẩm",             
                        'prepareBy' => "Nguyễn Hữu Phong",
                ]);}
                elseif ($request->table === 'source_material') {
                    $check = DB::table('source_material')->insert([
                        'id'=> $row[0],
                        'intermediate_code'=> $row[1],
                        'code'=> $row[2],
                        'name'=> $row[3],
                        'active' => true,          
                        'prepared_by' => "Nguyễn Hữu Phong",
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
            }

            if ($check) {dd ("OK");};
           
            
            //return back()->with('success', 'Import thành công!');
        }
                
            
}