<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Aunnait\Rasalicante\RasaComm;
use Illuminate\Support\Facades\Log;
use Aunnait\Rasalicante\RasaBotControl;
use App\Models\Chatbot;

class TrainChatbot implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    protected $chatbotId;
    protected $language;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($chatbotId, $language)
    {
        $this->chatbotId = $chatbotId;
        $this->language = $language;
    }
    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            $rasaComm = new RasaComm();
            $rasaBotControl = new RasaBotControl();

            Chatbot::where('id', $this->chatbotId)->update([
                'st_training' => 'entrenando'
            ]);
            $result = $rasaComm->entrena($this->chatbotId, $this->language);
            Log::info('training ' . $result);

            $resultControl = $rasaBotControl->rebootBot($this->chatbotId);
            Log::info('reboot ' . $resultControl);

            Chatbot::where('id', $this->chatbotId)->update([
                'st_training' => 'entrenado'
            ]);
        } catch (\Exception $e) {
            // Log the error
            Log::error('Error training chatbot: ' . $e->getMessage());
            Chatbot::where('id', $this->chatbotId)->update([
                'st_training' => 'fallo'
            ]);
            // Optionally rethrow the exception to allow Laravel to handle the failure
            throw $e;
        }
    }
}
