<?php

namespace App\Console\Commands;

use App\Http\Controllers\Api\PublicDashboardController;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class WarmDashboardCacheCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'dashboard:warm-cache {--force : Paksa hitung ulang dan timpa cache yang ada}';

    /**
     * The console command description.
     */
    protected $description = 'Menghangatkan (precompute) cache data dashboard publik untuk menghindari lonjakan beban database';

    /**
     * Execute the console command.
     */
    public function handle(PublicDashboardController $controller): int
    {
        $force = $this->option('force');
        $this->info('Memulai pemanasan cache dashboard publik...');

        // 1. Metadata Years
        $this->warmYears($controller, $force);

        // 2. Metadata Regions
        $this->warmRegions($controller, $force);

        // 3. Tab Ringkasan (Nasional - Default)
        $defaultArea = ['tahun' => null, 'provinsi' => null, 'kab_kota' => null];
        $this->warmSection($controller, 'ringkasan', $defaultArea, $force);

        // 4. Tab Profil Pencaker (Nasional - Default)
        $this->warmSection($controller, 'profil', $defaultArea, $force);

        // 5. Tab Kebutuhan Industri (Nasional - Default)
        $this->warmSection($controller, 'industri', $defaultArea, $force);

        $this->info('Semua cache utama dashboard berhasil dihangatkan!');
        return self::SUCCESS;
    }

    private function warmYears(PublicDashboardController $controller, bool $force): void
    {
        $key = 'public_dashboard:years';
        if (!$force && Cache::has($key)) {
            $this->line(" [SKIP] {$key} sudah ada di cache.");
            return;
        }

        $this->info(" [RUN] Menghitung {$key}...");
        $start = microtime(true);
        $data = $controller->buildYears();
        Cache::put($key, $data, 604800); // 7 hari
        $ms = round((microtime(true) - $start) * 1000, 2);
        $this->info(" [OK] {$key} selesai ({$ms} ms).");
    }

    private function warmRegions(PublicDashboardController $controller, bool $force): void
    {
        $key = 'public_dashboard:regions';
        if (!$force && Cache::has($key)) {
            $this->line(" [SKIP] {$key} sudah ada di cache.");
            return;
        }

        $this->info(" [RUN] Menghitung {$key}...");
        $start = microtime(true);
        $data = $controller->buildRegions();
        Cache::put($key, $data, 604800); // 7 hari
        $ms = round((microtime(true) - $start) * 1000, 2);
        $this->info(" [OK] {$key} selesai ({$ms} ms).");
    }

    private function warmSection(PublicDashboardController $controller, string $section, array $area, bool $force): void
    {
        $key = 'public_dashboard:' . $section . ':' . md5(json_encode($area));
        if (!$force && Cache::has($key)) {
            $this->line(" [SKIP] {$key} ({$section}) sudah ada di cache.");
            return;
        }

        $this->info(" [RUN] Menghitung {$key} ({$section})...");
        $start = microtime(true);

        $data = match ($section) {
            'ringkasan' => $controller->buildRingkasan($area),
            'profil' => $controller->buildProfilPencaker($area),
            'industri' => $controller->buildKebutuhanIndustri($area),
            default => null,
        };

        if ($data !== null) {
            Cache::put($key, $data, 86400); // 24 jam
            $ms = round((microtime(true) - $start) * 1000, 2);
            $this->info(" [OK] {$key} selesai ({$ms} ms).");
        }
    }
}
