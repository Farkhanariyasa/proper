<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use Illuminate\Support\Facades\DB;

$unmatchedKab = DB::select("SELECT kab_kota, COUNT(*) as c FROM req_pk_pencaker WHERE regency_id IS NULL AND kab_kota IS NOT NULL GROUP BY kab_kota ORDER BY c DESC LIMIT 10");
echo "Top Unmatched Kab/Kota:\n";
print_r($unmatchedKab);

$unmatchedEdu = DB::select("SELECT pendidikan, COUNT(*) as c FROM req_pk_pencaker WHERE education_level_id IS NULL AND pendidikan IS NOT NULL GROUP BY pendidikan ORDER BY c DESC LIMIT 10");
echo "\nTop Unmatched Pendidikan:\n";
print_r($unmatchedEdu);
