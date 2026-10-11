<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CleanManualJobSeekerSkillsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'skills:clean-manual {--all : Bersihkan seluruh data pivot skill pencaker lama}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Bersihkan tag skill manual / taksonomi lama dari tabel pencaker_esco_skills';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Memulai pembersihan tag skill manual pencari kerja...');

        $isAll = $this->option('all');
        $deletedCount = 0;

        // 1. Bersihkan dari tabel pencaker_esco_skills
        if (Schema::hasTable('pencaker_esco_skills')) {
            $query = DB::table('pencaker_esco_skills');

            if (!$isAll) {
                $query->where(function ($q) {
                    $q->where('is_manual', true)
                      ->orWhere('source', 'manual');
                });
            }

            $count = $query->count();
            $deleted = $query->delete();
            $deletedCount += $deleted;
            $this->info("Berhasil menghapus {$deleted} baris skill manual dari tabel pencaker_esco_skills (Total sebelum: {$count}).");
        }

        // 2. Bersihkan dari tabel job_seeker_skills jika ada
        if (Schema::hasTable('job_seeker_skills') && $isAll) {
            $deleted = DB::table('job_seeker_skills')->delete();
            $deletedCount += $deleted;
            $this->info("Berhasil membersihkan {$deleted} baris dari tabel job_seeker_skills.");
        }

        $this->info("Selesai! Total {$deletedCount} data skill manual berhasil dibersihkan.");
        return Command::SUCCESS;
    }
}
