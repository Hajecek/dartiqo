<?php

declare(strict_types=1);

namespace App\Controllers\Web;

use App\Core\Request;
use App\Repositories\LiveMatchRepository;

final class LiveController
{
    /** Spojení se po této době ukončí a prohlížeč se sám připojí znovu, ať PHP proces nevisí věčně. */
    private const STREAM_SECONDS = 25;
    private const POLL_MICROSECONDS = 600_000;
    private const HEARTBEAT_SECONDS = 10;

    /**
     * Server-Sent Events: pošle stav zápasu pokaždé, když ho mobil změní.
     *
     * @param array<string, string> $params
     */
    public function stream(Request $request, array $params = []): void
    {
        $user = auth()->user();
        if ($user === null) {
            http_response_code(401);
            exit;
        }
        // Otevřená session by zablokovala ostatní požadavky stejného uživatele
        session_write_close();
        set_time_limit(self::STREAM_SECONDS + 15);
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        header('Content-Type: text/event-stream; charset=utf-8');
        header('Cache-Control: no-store');
        header('X-Accel-Buffering: no');

        $matches = new LiveMatchRepository(db());
        $lastKey = $request->header('Last-Event-ID');
        $deadline = time() + self::STREAM_SECONDS;
        $lastWrite = time();
        echo "retry: 1500\n\n";
        flush();

        while (time() < $deadline && !connection_aborted()) {
            $match = $matches->current((int) $user['id']);
            $key = $match === null ? 'none' : $match['id'] . ':' . $match['seq'] . ':' . $match['status'];
            if ($key !== $lastKey) {
                echo 'id: ' . $key . "\n";
                echo 'data: ' . json_encode(['match' => $match], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) . "\n\n";
                $lastKey = $key;
                $lastWrite = time();
                flush();
            } elseif (time() - $lastWrite >= self::HEARTBEAT_SECONDS) {
                echo ": ping\n\n";
                $lastWrite = time();
                flush();
            }
            usleep(self::POLL_MICROSECONDS);
        }
        exit;
    }
}
