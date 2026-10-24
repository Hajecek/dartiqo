<?php

declare(strict_types=1);

namespace App\Controllers\Web;

use App\Core\Request;
use App\Repositories\MatchRepository;

final class LiveController
{
    /** Spojení se po této době ukončí a prohlížeč se sám připojí znovu, ať PHP proces nevisí věčně. */
    private const STREAM_SECONDS = 55;
    private const POLL_MICROSECONDS = 200_000;
    private const HEARTBEAT_SECONDS = 5;

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
        @ini_set('zlib.output_compression', '0');
        @ini_set('output_buffering', '0');
        @ini_set('implicit_flush', '1');
        if (function_exists('apache_setenv')) {
            @apache_setenv('no-gzip', '1');
        }
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        header('Content-Type: text/event-stream; charset=utf-8');
        header('Cache-Control: no-cache, no-store');
        header('Content-Encoding: none');
        header('X-Accel-Buffering: no');

        $matches = new MatchRepository(db());
        $lastKey = $request->header('Last-Event-ID');
        $deadline = time() + self::STREAM_SECONDS;
        $lastWrite = time();
        echo "retry: 700\n";
        // Některé proxy pustí proud až po prvních kilobajtech
        echo ':' . str_repeat(' ', 4096) . "\n\n";
        flush();

        while (time() < $deadline && !connection_aborted()) {
            $match = $matches->currentLive((int) $user['id']);
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

    /**
     * Stejný stav jako stream, jen jednou. Obrazovka zápasu si ho bere, když proud na chvíli spadne.
     *
     * @param array<string, string> $params
     */
    public function state(Request $request, array $params = []): void
    {
        $user = auth()->user();
        if ($user === null) {
            http_response_code(401);
            header('Content-Type: application/json; charset=utf-8');
            echo '{"match":null}';
            exit;
        }
        $match = (new MatchRepository(db()))->currentLive((int) $user['id']);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
        echo json_encode(['match' => $match], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG);
        exit;
    }
}
