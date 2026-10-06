<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use Illuminate\Support\Facades\DB;

echo "Updating Province...\n";
DB::statement("UPDATE req_pk_pencaker p SET province_id = master.id FROM provinces master WHERE UPPER(TRIM(p.provinsi)) = UPPER(master.name);");

echo "Updating Regency...\n";
DB::statement("UPDATE req_pk_pencaker p SET regency_id = master.id FROM regencies master WHERE UPPER(TRIM(p.kab_kota)) = UPPER(master.name);");

echo "Updating Education Level...\n";
DB::statement("UPDATE req_pk_pencaker p
SET education_level_id = e.id
FROM education_levels e
WHERE 
  (UPPER(TRIM(p.pendidikan)) IN ('S1', 'D4') AND e.name = 'Sarjana / Diploma IV (S1 / D4)') OR
  (UPPER(TRIM(p.pendidikan)) IN ('D1', 'D2', 'D3') AND e.name = 'Diploma I - III (D1 - D3)') OR
  (UPPER(TRIM(p.pendidikan)) IN ('SMA', 'SMK', 'SMA ATAU SEDERAJAT', 'STM') AND e.name = 'SMA / SMK / Sederajat') OR
  (UPPER(TRIM(p.pendidikan)) IN ('S2') AND e.name = 'Magister (S2)') OR
  (UPPER(TRIM(p.pendidikan)) IN ('S3') AND e.name = 'Doktoral (S3)') OR
  (UPPER(TRIM(p.pendidikan)) IN ('SMP ATAU SEDERAJAT', 'SMP') AND e.name = 'SMP / Sederajat') OR
  (UPPER(TRIM(p.pendidikan)) IN ('SD ATAU SEDERAJAT', 'SD') AND e.name = 'SD / Sederajat');");

echo "Bulk update completed.\n";
