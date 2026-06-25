<?php
$request = new \Illuminate\Http\Request();
$request->replace(['date' => '2026-06-25', 'production_code' => 'PXV1', 'type' => 'day']);
$controller = new \App\Http\Controllers\Pages\Assignment\DashBoardController();
$response = $controller->getData($request);
$data = json_decode($response->getContent(), true);
foreach($data['details'] as $detail) {
    if($detail['code'] == '19021') print_r($detail);
}

