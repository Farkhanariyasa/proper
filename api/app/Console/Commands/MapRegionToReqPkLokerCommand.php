<?php

namespace App\Console\Commands;

use App\Models\Province;
use App\Models\Regency;
use App\Models\ReqPkLoker;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MapRegionToReqPkLokerCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pk:map-region-loker';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Memetakan string reg/region_pembeker di req_pk_loker ke provinsi_id dan regency_id yang valid';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Memulai pemetaan wilayah lowongan kerja...');

        $lokers = ReqPkLoker::whereNull('provinsi_id')->orWhereNull('regency_id')->get();
        $this->info('Ditemukan ' . $lokers->count() . ' lowongan yang belum dipetakan wilayahnya.');

        // Preload provinces and regencies mapping
        $provinces = Province::pluck('id', 'name')->mapWithKeys(function ($id, $name) {
            return [strtoupper(trim($name)) => $id];
        });

        // Some common overrides for BPS data
        $provinceOverrides = [
            'DKI JAKARTA' => 'DAERAH KHUSUS IBUKOTA JAKARTA',
            'BANTEN' => 'BANTEN',
            'JAWA BARAT' => 'JAWA BARAT',
            'JAWA TIMUR' => 'JAWA TIMUR',
            'JAWA TENGAH' => 'JAWA TENGAH',
            'DI YOGYAKARTA' => 'DAERAH ISTIMEWA YOGYAKARTA',
            'BALI' => 'BALI',
            'NUSA TENGGARA BARAT' => 'NUSA TENGGARA BARAT',
            'NUSA TENGGARA TIMUR' => 'NUSA TENGGARA TIMUR',
            'SUMATERA UTARA' => 'SUMATERA UTARA',
            'SUMATERA BARAT' => 'SUMATERA BARAT',
            'SUMATERA SELATAN' => 'SUMATERA SELATAN',
            'RIAU' => 'RIAU',
            'KEPULAUAN RIAU' => 'KEPULAUAN RIAU',
            'LAMPUNG' => 'LAMPUNG',
            'JAMBI' => 'JAMBI',
            'BANGKA BELITUNG' => 'KEPULAUAN BANGKA BELITUNG',
            'BENGKULU' => 'BENGKULU',
            'ACEH' => 'ACEH',
            'KALIMANTAN BARAT' => 'KALIMANTAN BARAT',
            'KALIMANTAN TIMUR' => 'KALIMANTAN TIMUR',
            'KALIMANTAN SELATAN' => 'KALIMANTAN SELATAN',
            'KALIMANTAN TENGAH' => 'KALIMANTAN TENGAH',
            'KALIMANTAN UTARA' => 'KALIMANTAN UTARA',
            'SULAWESI SELATAN' => 'SULAWESI SELATAN',
            'SULAWESI UTARA' => 'SULAWESI UTARA',
            'SULAWESI TENGGARA' => 'SULAWESI TENGGARA',
            'SULAWESI TENGAH' => 'SULAWESI TENGAH',
            'SULAWESI BARAT' => 'SULAWESI BARAT',
            'GORONTALO' => 'GORONTALO',
            'MALUKU' => 'MALUKU',
            'MALUKU UTARA' => 'MALUKU UTARA',
            'PAPUA' => 'PAPUA',
            'PAPUA BARAT' => 'PAPUA BARAT',
            'PAPUA TENGAH' => 'PAPUA TENGAH',
            'PAPUA PEGUNUNGAN' => 'PAPUA PEGUNUNGAN',
            'PAPUA SELATAN' => 'PAPUA SELATAN',
            'PAPUA BARAT DAYA' => 'PAPUA BARAT DAYA',
        ];

        $regencies = Regency::pluck('id', 'name')->mapWithKeys(function ($id, $name) {
            return [strtoupper(trim($name)) => $id];
        });

        $mappedCount = 0;

        $bar = $this->output->createProgressBar($lokers->count());
        $bar->start();

        foreach ($lokers as $loker) {
            $reg = $loker->reg ?? $loker->region_pembeker;
            if (!$reg) {
                $bar->advance();
                continue;
            }

            // e.g. "Jatinegara Kaum, Pulogadung, Kota Adm. Jakarta Timur, DKI Jakarta"
            $parts = array_map('trim', explode(',', $reg));
            $partsCount = count($parts);

            if ($partsCount >= 2) {
                $rawProv = strtoupper($parts[$partsCount - 1]);
                $rawReg = strtoupper($parts[$partsCount - 2]);

                // Map Province
                $provName = $provinceOverrides[$rawProv] ?? $rawProv;
                $provId = $provinces[$provName] ?? null;

                // Fix regency name (Cahyadsn uses 'KAB. XXX' or 'KOTA XXX')
                $regName = $rawReg;
                if (str_starts_with($regName, 'KOTA ADM. ')) {
                    $regName = str_replace('KOTA ADM. ', 'KOTA ADMINISTRASI ', $regName);
                }
                if (str_starts_with($regName, 'KAB. ')) {
                    $regName = str_replace('KAB. ', 'KABUPATEN ', $regName);
                }

                $regId = $regencies[$regName] ?? null;

                if ($provId || $regId) {
                    $loker->provinsi_id = $provId;
                    $loker->regency_id = $regId;
                    $loker->save();
                    $mappedCount++;
                }
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Selesai! Berhasil memetakan wilayah untuk {$mappedCount} lowongan.");
    }
}
