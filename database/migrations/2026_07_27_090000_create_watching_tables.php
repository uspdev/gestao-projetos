<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tabela de watches para armazenar os registros de observação de recursos pelos usuários.
        Schema::create('watches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            // Model Polimórfico: watchable_type e watchable_id permitem que um usuário
            // observe diferentes tipos de recursos (por exemplo, tarefas, projetos, etc.).
            $table->string('watchable_type');
            $table->unsignedBigInteger('watchable_id');
            $table->timestamps();
            $table->unique(['user_id', 'watchable_type', 'watchable_id'], 'watches_user_target_unique');
            $table->index(['watchable_type', 'watchable_id'], 'watches_target_index');
        });

        $now = now();
        // Migrar os registros existentes da tabela task_user para a nova tabela watches.
        // Para que os usuários que já estavam observando tarefas continuem a fazê-lo após a migração.
        DB::table('task_user')
            ->orderBy('id')
            ->chunkById(500, function ($assignments) use ($now): void {
                DB::table('watches')->insert(
                    $assignments->map(fn ($assignment) => [
                        'user_id' => $assignment->user_id,
                        'watchable_type' => 'task',
                        'watchable_id' => $assignment->task_id,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ])->all()
                );
            });

        // Armazena eventos que ocorreram em recursos observados pelos usuários, mas que ainda não foram enviados como notificações.
        // Permite que o sistema gerencie o envio de notificações de forma assíncrona.
        Schema::create('pending_watch_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->string('watchable_type');
            $table->unsignedBigInteger('watchable_id');
            $table->string('event_type', 80);
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->text('summary');
            $table->text('details')->nullable();
            $table->text('url')->nullable();
            $table->timestamp('occurred_at');
            $table->timestamp('send_after')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'send_after'], 'pending_watch_user_send_index');
            $table->index(
                ['user_id', 'watchable_type', 'watchable_id'],
                'pending_watch_user_target_index'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pending_watch_notifications');
        Schema::dropIfExists('watches');
    }
};
