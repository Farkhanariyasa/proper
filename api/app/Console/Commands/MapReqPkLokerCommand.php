<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Services\KbjiResolver;

class MapReqPkLokerCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'pk:map-loker {--limit=1000 : Jumlah maksimal data yang akan di-mapping} {--all : Force remap semua data}';

    /**
     * The console command description.
     */
    protected $description = 'Memetakan judul_pekerjaan di tabel req_pk_loker ke kbji_2026_id secara otomatis';

    /**
     * Execute the console command.
     */
    public function handle(KbjiResolver $resolver)
    {
        $limit = $this->option('limit');
        $remapAll = $this->option('all');

        $query = DB::table('req_pk_loker');
        
        if (!$remapAll) {
            $query->where('is_mapped', false)
                  ->orWhereNull('is_mapped');
        }

        $total = $query->count();
        if ($total === 0) {
            $this->info("Semua data loker sudah di-mapping!");
            return;
        }

        $this->info("Memulai proses mapping untuk {$total} data (Limit iterasi: {$limit})...");

        $bar = $this->output->createProgressBar(min($total, $limit));
        $bar->start();

        // Menggunakan cursor() agar lebih hemat memori dibanding get() sekaligus
        $lokers = DB::table('req_pk_loker')
            ->when(!$remapAll, fn($q) => $q->where('is_mapped', false)->orWhereNull('is_mapped'))
            ->orderBy('id')
            ->limit($limit)
            ->get(); // get dengan limit lebih aman

        foreach ($lokers as $loker) {
            $judulPekerjaan = $loker->judul_pekerjaan ?? '';
            
            $kbjiId = null;
            if (!empty($judulPekerjaan)) {
                $kbjiId = $resolver->resolve($judulPekerjaan);
            }

            // Update baris tabel req_pk_loker
            DB::table('req_pk_loker')
                ->where('id', $loker->id)
                ->update([
                    'kbji_2026_id' => $kbjiId,
                    'is_mapped' => true,
                ]);
                
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('Proses mapping selesai!');
    }
}
