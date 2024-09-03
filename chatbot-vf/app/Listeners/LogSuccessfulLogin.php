<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Login;
use App\Models\AccessHistory;

class LogSuccessfulLogin
{

    /**
     * Handle the event.
     */
    public function handle(Login $event): void
    {
        $user = $event->user;
        $session_data['id'] = $user->id;
        $session_data['name'] =$user->name;
        $session_data['rol'] = $user->getRoleNames()->first();
        $ip = request()->ip();
        $userAgent = request()->header('User-Agent');

        $browser = 'Unknown Browser';
        $browserVersion = 'Unknown Version';

        if (preg_match('/(Firefox|Chrome|Safari|Edge)\/([\d\.]+)/', $userAgent, $matches)) {
            $browser = $matches[1];
            $browserVersion = $matches[2];
        }

        // Identifica la plataforma desde la cadena User-Agent y extrae la versión
        $platform = 'Unknown Platform';

        if (preg_match('/\(([^)]+)\)/', $userAgent, $platformMatches)) {
            $platformInfo = $platformMatches[1];
            if (strpos($platformInfo, 'Windows') !== false) {
                $platform = 'Windows';
                preg_match('/Windows NT ([\d\.]+)/', $platformInfo, $versionMatches);
            } elseif (strpos($platformInfo, 'Macintosh') !== false) {
                $platform = 'Macintosh';
            } elseif (strpos($platformInfo, 'Linux') !== false) {
                $platform = 'Linux';
            }
        }

        AccessHistory::create([
            'user_id' => $user->id,
            'session_data' => json_encode($session_data),
            'ip' => $ip,
            'browser' => $browser . ' ' . $browserVersion,
            'platform' => $platform
        ]);

    }
}
