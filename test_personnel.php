<?php
$controller = new \App\Http\Controllers\Pages\Assignment\ProductionAssignmentController();
$method = new ReflectionMethod($controller, 'index');
// Not easy to test without request

