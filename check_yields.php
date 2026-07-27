<?php
$schema = Schema::getColumnListing('stage_plan');
echo implode(', ', $schema);
