<?php
$schema = Schema::getColumnListing('room_sheet');
echo implode(', ', $schema);
