<?php
    // use Illuminate\Routing\Route;
use App\Http\Controllers\Pages\MaterData\DosageController;
use App\Http\Controllers\Pages\MaterData\InstrumentController;
use App\Http\Controllers\Pages\MaterData\MarketController;
use App\Http\Controllers\Pages\MaterData\ProductNameController;
use App\Http\Controllers\Pages\MaterData\RoomController;
use App\Http\Controllers\Pages\MaterData\SpecificationController;
use App\Http\Controllers\Pages\MaterData\UnitController;
use App\Http\Controllers\UploadDataController;
use App\Http\Middleware\CheckLogin;
use Illuminate\Support\Facades\Route;



Route::get('/upload', [UploadDataController::class, 'index'])->name('upload.form_load');
Route::post('/importdata', [UploadDataController::class, 'import'])->name('upload.import');

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
        });

        Route::prefix('/room')
        ->name('room.')
        ->controller(RoomController::class)
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
               
        });

        Route::prefix('/Unit')
        ->name('Unit.')
        ->controller(UnitController::class)
        ->group(function(){
                Route::get('','index')->name('list');
                Route::post('store','store')->name('store');
                Route::post('update', 'update')->name('update');
                   
        });

        
        Route::prefix('/Market')
        ->name('Market.')
        ->controller(MarketController::class)
        ->group(function(){
                Route::get('','index')->name('list');
                Route::post('store','store')->name('store');
                Route::post('update', 'update')->name('update');
              
        });

        
        Route::prefix('/Specification')
        ->name('Specification.')
        ->controller(SpecificationController::class)
        ->group(function(){
                Route::get('','index')->name('list');
                Route::post('store','store')->name('store');
                Route::post('update', 'update')->name('update');
               
        });

       

});
   

?>