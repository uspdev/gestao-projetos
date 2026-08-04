<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Adiciona os membros diretos dos Projetos vinculados a cada Reunião.
        $watchers = DB::table('meeting_projects')
            ->join('meetings', 'meetings.id', '=', 'meeting_projects.meeting_id')
            ->join('project_user', 'project_user.project_id', '=', 'meeting_projects.project_id')
            ->whereNull('deleted_at')
            ->where('status', '!=', 'COMPLETED')
            ->selectRaw(
                "project_user.user_id, 'meeting' as watchable_type, meeting_projects.meeting_id as watchable_id, ? as created_at, ? as updated_at",
                [now(), now()],
            );

        DB::table('watches')->insertOrIgnoreUsing(
            ['user_id', 'watchable_type', 'watchable_id', 'created_at', 'updated_at'],
            $watchers,
        );
    }

    public function down(): void
    {
        // A preferência pode ter sido alterada pela pessoa após a migração.
        // Não a removemos na reversão para evitar apagar uma escolha legítima.
    }
};
