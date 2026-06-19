<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Nouvelle tâche assignée</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f4f4; margin: 0; padding: 0; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; background-color: #ffffff; border-radius: 8px; }
        .header { background: linear-gradient(135deg, #10B981, #059669); color: white; padding: 30px; text-align: center; border-radius: 8px 8px 0 0; }
        .content { padding: 30px; }
        .task-card { background-color: #f9fafb; border-left: 4px solid #4F46E5; padding: 15px; margin: 15px 0; border-radius: 4px; }
        .button { display: inline-block; background: linear-gradient(135deg, #4F46E5, #7C3AED); color: white; padding: 12px 30px; text-decoration: none; border-radius: 6px; }
        .footer { text-align: center; padding: 20px; color: #888; font-size: 12px; border-top: 1px solid #eee; }
        .badge { display: inline-block; padding: 2px 12px; border-radius: 12px; font-size: 12px; }
    </style>
</head>
<body>
    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <div class="container">
            <div class="header">
                <h1>Nouvelle tâche assignée</h1>
            </div>
            <div class="content">
                <h2>Bonjour {{ $user->name }},</h2>
                <p><strong>{{ $assignedBy }}</strong> vous a assigné une nouvelle tâche :</p>
                
                <div class="task-card">
                    <h3 style="margin: 0 0 10px 0; color: #1f2937;">{{ $task->title }}</h3>
                    @if($task->description)
                        <p style="margin: 0 0 10px 0; color: #6b7280;">{{ $task->description }}</p>
                    @endif
                    <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                        @if($task->priority)
                            <span class="badge" style="background-color: {{ $task->priority === 'high' ? '#EF4444' : ($task->priority === 'medium' ? '#F59E0B' : '#10B981') }}; color: white;">
                                {{ ucfirst($task->priority) }}
                            </span>
                        @endif
                        @if($task->due_date)
                            <span class="badge" style="background-color: #E5E7EB; color: #374151;">
                                 {{ \Carbon\Carbon::parse($task->due_date)->format('d/m/Y') }}
                            </span>
                        @endif
                    </div>
                </div>

                <p style="text-align: center; margin: 30px 0;">
                    <!-- Lien vers le frontend -->
                    <a href="{{ $frontendUrl }}/kanban" class="button">Voir la tâche</a>
                </p>
            </div>
            <div class="footer">
                <p>&copy; {{ date('Y') }} PER ANKH. Tous droits réservés.</p>
            </div>
        </div>
    </div>
</body>
</html>