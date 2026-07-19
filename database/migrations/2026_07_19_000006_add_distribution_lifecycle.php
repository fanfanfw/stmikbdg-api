<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private function connection()
    {
        return DB::connection(config('myconfig.database.first_connection'));
    }

    public function up(): void
    {
        $connection = $this->connection();
        if ($connection->getDriverName() === 'sqlite') {
            $duplicates = $connection->select('SELECT distribution_id, target_role, identifier FROM distribution_recipients WHERE deleted_at IS NULL GROUP BY distribution_id, target_role, identifier HAVING COUNT(*) > 1 LIMIT 1');
            if ($duplicates) {
                throw new RuntimeException('Duplicate live distribution recipients found. Resolve duplicates before migration.');
            }
            $connection->statement('ALTER TABLE distributions ADD COLUMN original_distribution_id integer');
            $connection->statement('ALTER TABLE distributions ADD COLUMN withdrawn_at datetime');
            $connection->statement('ALTER TABLE distributions ADD COLUMN withdrawn_by_user_id integer');
            $connection->statement('ALTER TABLE distributions ADD COLUMN withdrawal_reason text');
            $connection->statement('ALTER TABLE distribution_recipients ADD COLUMN download_count integer NOT NULL DEFAULT 0');
            $connection->statement('ALTER TABLE distribution_recipients ADD COLUMN first_downloaded_at datetime');
            $connection->statement('ALTER TABLE distribution_recipients ADD COLUMN last_downloaded_at datetime');
            $connection->statement('CREATE INDEX IF NOT EXISTS distributions_original_distribution_idx ON distributions (original_distribution_id)');
            $connection->statement('CREATE INDEX IF NOT EXISTS distribution_recipients_distribution_download_count_idx ON distribution_recipients (distribution_id, download_count)');
            $connection->statement('CREATE UNIQUE INDEX IF NOT EXISTS distribution_recipients_unique_live_idx ON distribution_recipients (distribution_id, target_role, identifier)');

            return;
        }

        $this->connection()->unprepared(<<<'SQL'
ALTER TABLE arsip_digital.distributions
    ADD COLUMN IF NOT EXISTS original_distribution_id bigint NULL,
    ADD COLUMN IF NOT EXISTS withdrawn_at timestamp NULL,
    ADD COLUMN IF NOT EXISTS withdrawn_by_user_id bigint NULL,
    ADD COLUMN IF NOT EXISTS withdrawal_reason text NULL;
DO $$ BEGIN
    ALTER TABLE arsip_digital.distributions
        ADD CONSTRAINT distributions_original_distribution_fk
        FOREIGN KEY (original_distribution_id) REFERENCES arsip_digital.distributions(distribution_id);
EXCEPTION WHEN duplicate_object THEN NULL;
END $$;
ALTER TABLE arsip_digital.distribution_recipients
    ADD COLUMN IF NOT EXISTS download_count integer NOT NULL DEFAULT 0,
    ADD COLUMN IF NOT EXISTS first_downloaded_at timestamp NULL,
    ADD COLUMN IF NOT EXISTS last_downloaded_at timestamp NULL;
CREATE INDEX IF NOT EXISTS distributions_original_distribution_idx
    ON arsip_digital.distributions (original_distribution_id);
CREATE INDEX IF NOT EXISTS distribution_recipients_distribution_download_count_idx
    ON arsip_digital.distribution_recipients (distribution_id, download_count);
DO $$ BEGIN
    IF EXISTS (
        SELECT 1
        FROM arsip_digital.distribution_recipients
        WHERE deleted_at IS NULL
        GROUP BY distribution_id, target_role, identifier
        HAVING COUNT(*) > 1
    ) THEN
        RAISE EXCEPTION 'Duplicate live distribution recipients found. Resolve duplicates before migration.';
    END IF;
END $$;
CREATE UNIQUE INDEX IF NOT EXISTS distribution_recipients_unique_live_idx
    ON arsip_digital.distribution_recipients (distribution_id, target_role, identifier)
    WHERE deleted_at IS NULL;
SQL);
    }

    public function down(): void
    {
        $connection = $this->connection();
        if ($connection->getDriverName() === 'sqlite') {
            $connection->statement('DROP INDEX IF EXISTS distribution_recipients_unique_live_idx');
            $connection->statement('DROP INDEX IF EXISTS distribution_recipients_distribution_download_count_idx');
            $connection->statement('DROP INDEX IF EXISTS distributions_original_distribution_idx');
            $connection->statement('ALTER TABLE distribution_recipients DROP COLUMN last_downloaded_at');
            $connection->statement('ALTER TABLE distribution_recipients DROP COLUMN first_downloaded_at');
            $connection->statement('ALTER TABLE distribution_recipients DROP COLUMN download_count');
            $connection->statement('ALTER TABLE distributions DROP COLUMN withdrawal_reason');
            $connection->statement('ALTER TABLE distributions DROP COLUMN withdrawn_by_user_id');
            $connection->statement('ALTER TABLE distributions DROP COLUMN withdrawn_at');
            $connection->statement('ALTER TABLE distributions DROP COLUMN original_distribution_id');

            return;
        }

        $this->connection()->unprepared(<<<'SQL'
DROP INDEX IF EXISTS arsip_digital.distribution_recipients_unique_live_idx;
DROP INDEX IF EXISTS arsip_digital.distribution_recipients_distribution_download_count_idx;
DROP INDEX IF EXISTS arsip_digital.distributions_original_distribution_idx;
ALTER TABLE arsip_digital.distribution_recipients
    DROP COLUMN IF EXISTS last_downloaded_at,
    DROP COLUMN IF EXISTS first_downloaded_at,
    DROP COLUMN IF EXISTS download_count;
ALTER TABLE arsip_digital.distributions
    DROP CONSTRAINT IF EXISTS distributions_original_distribution_fk,
    DROP COLUMN IF EXISTS withdrawal_reason,
    DROP COLUMN IF EXISTS withdrawn_by_user_id,
    DROP COLUMN IF EXISTS withdrawn_at,
    DROP COLUMN IF EXISTS original_distribution_id;
SQL);
    }
};
