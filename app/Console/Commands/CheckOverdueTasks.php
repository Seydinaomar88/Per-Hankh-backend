<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Task;
use App\Models\Notification;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Mail\TaskOverdueEmail;

class CheckOverdueTasks extends Command
{
    protected $signature = 'tasks:check-overdue';
    protected $description = 'Vérifie les tâches en retard et envoie des notifications';

    public function handle()
    {
        $this->info('Vérification des tâches en retard...');

        // Récupérer les tâches dont la date d'échéance est dépassée et qui ne sont pas terminées
        $overdueTasks = Task::where('due_date', '<', Carbon::now())
            ->where('status', '!=', Task::STATUS_DONE)
            ->whereNotNull('due_date')
            ->get();

        $this->info("{$overdueTasks->count()} tâche(s) en retard trouvée(s)");

        foreach ($overdueTasks as $task) {
            // Vérifier si une notification a déjà été envoyée pour cette tâche aujourd'hui
            $existingNotification = Notification::where('type', 'task_overdue')
                ->where('message', 'LIKE', '%' . $task->title . '%')
                ->whereDate('created_at', Carbon::today())
                ->exists();

            if (!$existingNotification) {
                $this->sendOverdueNotification($task);
                $this->info("Notification envoyée pour la tâche: {$task->title}");
            } else {
                $this->info("Notification déjà envoyée aujourd'hui pour: {$task->title}");
            }
        }

        $this->info('Vérification terminée');
        return 0;
    }

    /**
     * Envoyer les notifications pour une tâche en retard
     */
    private function sendOverdueNotification(Task $task)
    {
        // Créer une notification pour le créateur de la tâche
        $this->createNotification(
            $task->created_by,
            $task,
            'Votre tâche "'.$task->title.'" est en retard !',
            'task_overdue'
        );

        // Créer une notification pour la personne assignée
        if ($task->assigned_to && $task->assigned_to !== $task->created_by) {
            $this->createNotification(
                $task->assigned_to,
                $task,
                'La tâche "'.$task->title.'" qui vous est assignée est en retard !',
                'task_overdue'
            );
        }

        // Envoyer un email à la personne assignée
        if ($task->assigned_to) {
            $assignedUser = User::find($task->assigned_to);
            if ($assignedUser) {
                try {
                    Mail::to($assignedUser->email)->send(new TaskOverdueEmail($assignedUser, $task));
                    Log::info('Email tâche en retard envoyé à: ' . $assignedUser->email);
                } catch (\Exception $e) {
                    Log::error('Erreur envoi email tâche en retard: ' . $e->getMessage());
                }
            }
        }
    }

    /**
     * Créer une notification dans la base de données
     */
    private function createNotification(int $userId, Task $task, string $message, string $type)
    {
        return Notification::create([
            'user_id' => $userId,
            'created_by' => $task->created_by,
            'workspace_id' => $task->workspace_id,
            'type' => $type,
            'message' => $message,
            'data' => json_encode([
                'task_id' => $task->id,
                'task_title' => $task->title,
                'due_date' => $task->due_date,
            ]),
            'is_read' => false,
        ]);
    }
}