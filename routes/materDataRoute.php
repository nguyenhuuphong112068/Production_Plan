<?php
    // use Illuminate\Routing\Route;
use App\Http\Controllers\Pages\MaterData\DosageController;
use App\Http\Controllers\Pages\MaterData\InstrumentController;
use App\Http\Controllers\Pages\MaterData\MarketController;
use App\Http\Controllers\Pages\MaterData\ProductNameController;
use App\Http\Controllers\Pages\MaterData\RoomController;
use App\Http\Controllers\Pages\MaterData\SourceMaterialController;
use App\Http\Controllers\Pages\MaterData\SpecificationController;
use App\Http\Controllers\Pages\MaterData\UnitController;
use App\Http\Controllers\Pages\MaterData\OffDaysController;
use App\Http\Controllers\Pages\MaterData\StageGroupController;
use App\Http\Controllers\Pages\MaterData\DepartmentController;
use App\Http\Controllers\Pages\MaterData\BlisterMoldController;
use App\Http\Controllers\Pages\MaterData\BlisterTypeController;
use App\Http\Controllers\UploadDataController;
use App\Http\Middleware\CheckLogin;
use Illuminate\Support\Facades\Route;



Route::get('/upload', [UploadDataController::class, 'index'])->name('upload.form_load');
Route::POST('/import', [UploadDataController::class, 'import'])->name('upload.import');
Route::POST('/import_permission', [UploadDataController::class, 'import_permission'])->name('upload.import_permission');


Route::prefix('/materData')
->name('pages.materData.')
->middleware(CheckLogin::class)
->group(function(){

        Route::prefix('/productName')
        ->name('productName.')
        ->controller(ProductNameController::class)
        ->group(function(){
                Route::get('','index')->name('list');
                Route::post('store','store')->name('store');
                Route::post('update', 'update')->name('update');
                Route::post('deActive','deActive')->name('deActive'); 
            Route::get('history', 'history')->name('history');
 
        });

        Route::prefix('/room')
        ->name('room.')
        ->controller(RoomController::class)
        ->group(function(){
                Route::get('','index')->name('list');
                Route::post('store','store')->name('store');
                Route::post('update', 'update')->name('update');
                Route::post('deActive','deActive')->name('deActive');          
            Route::get('history', 'history')->name('history');
          
        });

        Route::prefix('/room_links')
        ->name('room_links.')
        ->controller(\App\Http\Controllers\Pages\MaterData\RoomLinkController::class)
        ->group(function(){
            Route::get('','index')->name('list');
            Route::post('store','store')->name('store');
            Route::post('update', 'update')->name('update');
            Route::post('deActive','deActive')->name('deActive');
        });


        Route::prefix('/Dosage')
        ->name('Dosage.')
        ->controller(DosageController::class)
        ->group(function(){
                Route::get('','index')->name('list');
                Route::post('store','store')->name('store');
                Route::post('update', 'update')->name('update');
               
            Route::get('history', 'history')->name('history');

               
        });

        Route::prefix('/Unit')
        ->name('Unit.')
        ->controller(UnitController::class)
        ->group(function(){
                Route::get('','index')->name('list');
                Route::post('store','store')->name('store');
                Route::post('update', 'update')->name('update');
                   
            Route::get('history', 'history')->name('history');

                   
        });

        
        Route::prefix('/Market')
        ->name('Market.')
        ->controller(MarketController::class)
        ->group(function(){
                Route::get('','index')->name('list');
                Route::post('store','store')->name('store');
                Route::post('update', 'update')->name('update');
              
            Route::get('history', 'history')->name('history');

              
        });

        
        Route::prefix('/Specification')
        ->name('Specification.')
        ->controller(SpecificationController::class)
        ->group(function(){
                Route::get('','index')->name('list');
                Route::post('store','store')->name('store');
                Route::post('update', 'update')->name('update');
               
            Route::get('history', 'history')->name('history');

               
        });


        Route::prefix('/source_material')
        ->name('source_material.')
        ->controller(SourceMaterialController::class)
        ->group(function(){
                Route::get('','index')->name('list');
                Route::post('store','store')->name('store');
                Route::post('update', 'update')->name('update');
                Route::post('deActive','deActive')->name('deActive');          
            Route::get('history', 'history')->name('history');
          
        });

        Route::prefix('/offdays')
        ->name('offdays.')
        ->controller(OffDaysController::class)
        ->group(function(){
                Route::get('','index')->name('list');
                Route::post('store','store')->name('store');
                Route::post('store_ajax','storeAjax')->name('store_ajax');
                Route::post('delete_ajax','deleteAjax')->name('delete_ajax');
                Route::post('flags/store_ajax','storeFlagAjax')->name('flags_store_ajax');
                Route::post('flags/delete_ajax','deleteFlagAjax')->name('flags_delete_ajax');
            Route::get('history', 'history')->name('history');

        });

        Route::prefix('/stageGroup')
        ->name('stageGroup.')
        ->controller(StageGroupController::class)
        ->group(function(){
                Route::get('','index')->name('list');
                Route::post('store','store')->name('store');
                Route::post('update', 'update')->name('update');
            Route::get('history', 'history')->name('history');

        });

        Route::prefix('/department')
        ->name('department.')
        ->controller(DepartmentController::class)
        ->group(function(){
                Route::get('','index')->name('list');
                Route::post('store','store')->name('store');
                Route::post('update', 'update')->name('update');
                Route::post('deActive','deActive')->name('deActive');
            Route::get('history', 'history')->name('history');

        });

        Route::prefix('/blister_mold')
        ->name('blister_mold.')
        ->controller(BlisterMoldController::class)
        ->group(function(){
                Route::get('','index')->name('list');
                Route::post('store','store')->name('store');
                Route::post('update', 'update')->name('update');
                Route::post('deActive','deActive')->name('deActive');
            Route::get('history', 'history')->name('history');

        });

        Route::prefix('/blister_type')
        ->name('blister_type.')
        ->controller(BlisterTypeController::class)
        ->group(function(){
                Route::get('','index')->name('list');
                Route::post('store','store')->name('store');
                Route::post('update', 'update')->name('update');
                Route::post('deActive','deActive')->name('deActive');
            Route::get('history', 'history')->name('history');

        });


});
   

?>