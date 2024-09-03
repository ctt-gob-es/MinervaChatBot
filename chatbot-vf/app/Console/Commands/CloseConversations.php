<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Conversation;
use App\Models\ConversationLog;
use App\Models\ChatbotSetting;
use App\Models\ChatbotLog;
use App\Models\Chatbot;

use Carbon\Carbon;


class CloseConversations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'closeConversations';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cierra las conversaciones que hayan estado abiertas durante un cierto tiempo.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $conversations = Conversation::getOpenConversations();
        foreach ($conversations as $conversation) {
            $chatbot = ChatbotLog::where('id', $conversation->chatbot_log_id)->first();
            $tiempoInactividadSetting = ChatbotSetting::with('defaultTable')
                ->withTrashed()
                ->where('chatbot_id', $chatbot->chatbot_id)
                ->whereHas('defaultTable', function ($query) {
                    $query->whereIn('name', ['inactividad']);
                })
                ->value('value');
            if ($tiempoInactividadSetting) {
                $tiempoInactividad = intval($tiempoInactividadSetting);
                $fechaUltimoMensaje = $conversation->conversationLogs()->latest('created_at')->value('created_at');
                if ($fechaUltimoMensaje) {
                    $fechaUltimoMensaje = Carbon::createFromFormat('Y-m-d H:i:s', $fechaUltimoMensaje);
                    $diferenciaMinutos = $fechaUltimoMensaje->diffInMinutes(now());
                    if ($diferenciaMinutos > $tiempoInactividad) {
                        $conversation->closeConversationInactivity();
                        $conversation->closeChatbotRasaConversation();
                    }
                }
            }
        }
    }
}
