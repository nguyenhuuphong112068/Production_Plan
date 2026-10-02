<?php
require(__DIR__ . '/generalRoute.php');
require(__DIR__ . '/materDataRoute.php');
require(__DIR__ . '/UserRoute.php');
require(__DIR__ . '/AuditTrialRoute.php');
require(__DIR__ . '/categoryRoute.php');
require(__DIR__ . '/ImportRoute.php');
require(__DIR__ . '/SchedualRoute.php');

require(__DIR__ . '/HistoryRoute.php');
require(__DIR__ . '/statisticsRoute.php');
require(__DIR__ . '/planRoute.php');
require(__DIR__ . '/quotaRoute.php');
require(__DIR__ . '/statusRoute.php');
require(__DIR__ . '/quarantineRoute.php');
require(__DIR__ . '/reportRoute.php');
require(__DIR__ . '/AssignmentRoute.php');
require(__DIR__ . '/MMSRoute.php');


require(__DIR__ . '/exportExcelRoute.php');
require(__DIR__ . '/NotificationRoute.php');
require(__DIR__ . '/ChatRoute.php');

use App\Http\Controllers\UserTablePreferenceController;

Route::post('/user-table-preferences/save', [UserTablePreferenceController::class, 'save']);
Route::get('/user-table-preferences/load', [UserTablePreferenceController::class, 'load']);
