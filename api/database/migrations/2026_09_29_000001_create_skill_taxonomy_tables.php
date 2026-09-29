<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Ekstensi pg_trgm untuk indexing trigram gin
        DB::statement('CREATE EXTENSION IF NOT EXISTS pg_trgm;');

        // 2. Tabel Node Taksonomi
        DB::statement("
            CREATE TABLE IF NOT EXISTS skill_nodes (
                id SERIAL PRIMARY KEY,
                code VARCHAR(50),
                type VARCHAR(20) NOT NULL,
                is_layer1 BOOLEAN DEFAULT FALSE,
                title VARCHAR(500) NOT NULL,
                description TEXT,
                alt_labels TEXT[],
                title_en VARCHAR(500) NOT NULL,
                description_en TEXT,
                alt_labels_en TEXT[],
                source_uri VARCHAR(500) UNIQUE NOT NULL,
                metadata JSONB,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            );
        ");

        // 3. Tabel Relasi Hierarki
        DB::statement("
            CREATE TABLE IF NOT EXISTS skill_hierarchy (
                id SERIAL PRIMARY KEY,
                parent_id INT NOT NULL REFERENCES skill_nodes(id) ON DELETE CASCADE,
                child_id INT NOT NULL REFERENCES skill_nodes(id) ON DELETE CASCADE,
                sort_order INT DEFAULT 0,
                CONSTRAINT uq_hierarchy_ids UNIQUE (parent_id, child_id)
            );
        ");

        // 4. Indexing
        DB::statement('CREATE INDEX IF NOT EXISTS idx_hierarchy_parent_id ON skill_hierarchy(parent_id);');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_hierarchy_child_id ON skill_hierarchy(child_id);');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_skill_nodes_code ON skill_nodes(code);');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_skill_nodes_type ON skill_nodes(type);');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_skill_nodes_is_layer1 ON skill_nodes(is_layer1) WHERE is_layer1 = TRUE;');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_skill_nodes_title_trgm ON skill_nodes USING gin (title gin_trgm_ops);');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_skill_nodes_title_en_trgm ON skill_nodes USING gin (title_en gin_trgm_ops);');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('skill_hierarchy');
        Schema::dropIfExists('skill_nodes');
    }
};
