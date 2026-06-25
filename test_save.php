<?php
$data = [
    'production_code' => 'PXV1',
    'group_code' => 5,
    'reportedDate' => '2026-06-25',
    'room_id' => 28,
    'assignments' => [
        [
            'shift' => 1,
            'start_time' => '06:00',
            'end_time' => '14:00',
            'job_description' => 'Test',
            'number_of_employes' => 1,
            'num_of_per_level_3' => 0,
            'off_stream' => 0,
            'personnel_list' => [
                ['personnel_id' => 9, 'notification' => '', 'operation_type' => 'thủ công', 'start' => '06:00', 'end' => '14:00']
            ]
        ]
    ]
];
$request = new \Illuminate\Http\Request();
$request->replace($data);
$controller = new \App\Http\Controllers\Pages\Assignment\ProductionAssignmentController();
$response = $controller->store($request);
print_r($response->getContent());

