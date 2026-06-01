<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Comando customizado de limpeza do activity_log.
 *
 * Substitui o `artisan activitylog:clean` nativo para permitir políticas
 * de retenção diferentes por log_name, conforme definido em
 * config('activitylog.retention_policies').
 *
 * Recommended schedule (app/Console/Kernel.php):
 *   $schedule->command('app:clean-activity-log')->dailyAt('02:00');
 *
 * Execução manual:
 *   php artisan app:clean-activity-log
 *   php artisan app:clean-activity-log --dry-run   (apenas exibe, não deleta)
 */
class CleanActivityLog extends Command
{
    protected $signature = 'app:clean-activity-log
                            {--dry-run : Exibe o que seria deletado sem executar a limpeza}';

    protected $description = 'Remove registros do activity_log respeitando políticas de retenção por log_name';

    public function handle(): int
    {
        $isDryRun  = $this->option('dry-run');
        $policies  = config('activitylog.retention_policies', []);
        $table     = config('activitylog.table_name', 'activity_log');
        $fallback  = (int) config('activitylog.delete_records_older_than_days', 365);
        $totalDeleted = 0;

        if (empty($policies)) {
            $this->warn('Nenhuma política de retenção encontrada em config(\'activitylog.retention_policies\'). Abortando.');
            return self::FAILURE;
        }

        $this->info($isDryRun ? '🔍 Modo dry-run ativo — nenhum registro será deletado.' : '🧹 Iniciando limpeza do activity_log...');
        $this->newLine();

        // ---------------------------------------------------------------
        // 1. Limpa cada log_name com sua política específica
        // ---------------------------------------------------------------
        foreach ($policies as $logName => $days) {
            $days      = (int) $days;
            $cutoff    = Carbon::now()->subDays($days);
            $query     = DB::table($table)
                           ->where('log_name', $logName)
                           ->where('created_at', '<', $cutoff);

            $count = $query->count();

            if ($isDryRun) {
                $this->line(sprintf(
                    '  [dry-run] %-20s → %d registro(s) com mais de %d dias (antes de %s)',
                    $logName,
                    $count,
                    $days,
                    $cutoff->toDateString()
                ));
                continue;
            }

            if ($count === 0) {
                $this->line(sprintf('  ✔ %-20s → nenhum registro para remover (política: %d dias)', $logName, $days));
                continue;
            }

            $query->delete();
            $totalDeleted += $count;

            $this->line(sprintf(
                '  🗑  %-20s → %d registro(s) removido(s) (política: %d dias)',
                $logName,
                $count,
                $days
            ));
        }

        // ---------------------------------------------------------------
        // 2. Limpa log_names que não estão nas políticas (usa o fallback)
        //    Evita que novos log_names sem política configurada cresçam
        //    indefinidamente.
        // ---------------------------------------------------------------
        $knownLogNames  = array_keys($policies);
        $cutoffFallback = Carbon::now()->subDays($fallback);
        $fallbackQuery  = DB::table($table)
                            ->whereNotIn('log_name', $knownLogNames)
                            ->where('created_at', '<', $cutoffFallback);

        $fallbackCount = $fallbackQuery->count();

        if ($fallbackCount > 0) {
            if ($isDryRun) {
                $this->line(sprintf(
                    '  [dry-run] %-20s → %d registro(s) com mais de %d dias (fallback)',
                    '[outros log_names]',
                    $fallbackCount,
                    $fallback
                ));
            } else {
                $fallbackQuery->delete();
                $totalDeleted += $fallbackCount;

                $this->line(sprintf(
                    '  🗑  %-20s → %d registro(s) removido(s) (fallback: %d dias)',
                    '[outros log_names]',
                    $fallbackCount,
                    $fallback
                ));
            }
        }

        // ---------------------------------------------------------------
        // 3. Resumo
        // ---------------------------------------------------------------
        $this->newLine();

        if ($isDryRun) {
            $this->info('Dry-run concluído. Nenhum dado foi alterado.');
        } else {
            $this->info(sprintf('✅ Limpeza concluída. Total removido: %d registro(s).', $totalDeleted));
        }

        return self::SUCCESS;
    }
}