<?php

namespace App\Services;

/**
 * Recorte de video via ffmpeg. Opcional: se o binario nao estiver instalado,
 * disponivel() devolve false e o resto do sistema desabilita o recorte.
 */
class Ffmpeg
{
    private static ?bool $disponivel = null;

    private static function bin(): string
    {
        $b = trim((string) ($_ENV['FFMPEG_PATH'] ?? ''));
        return $b !== '' ? $b : 'ffmpeg';
    }

    private static function probeBin(): string
    {
        $b = trim((string) ($_ENV['FFPROBE_PATH'] ?? ''));
        return $b !== '' ? $b : 'ffprobe';
    }

    public static function duracaoMaxima(): int
    {
        return max(1, (int) ($_ENV['FFMPEG_MAX_DUR'] ?? 900));
    }

    /** O ffmpeg esta acessivel? (memoizado por requisicao) */
    public static function disponivel(): bool
    {
        if (self::$disponivel !== null) {
            return self::$disponivel;
        }

        if (!function_exists('exec')) {
            return self::$disponivel = false;
        }

        $saida = [];
        $code = 1;
        @exec(escapeshellarg(self::bin()) . ' -version 2>&1', $saida, $code);

        return self::$disponivel = ($code === 0);
    }

    /** Duracao do arquivo em segundos, ou null se nao der para saber. */
    public static function duracao(string $arquivoAbs): ?float
    {
        if (!function_exists('exec') || !is_file($arquivoAbs)) {
            return null;
        }

        $saida = [];
        $code = 1;
        @exec(
            escapeshellarg(self::probeBin())
            . ' -v error -show_entries format=duration -of default=nw=1:nk=1 '
            . escapeshellarg($arquivoAbs) . ' 2>&1',
            $saida,
            $code
        );

        if ($code !== 0 || empty($saida[0]) || !is_numeric(trim($saida[0]))) {
            return null;
        }

        return (float) trim($saida[0]);
    }

    /**
     * Gera <saidaAbs> (.mp4) com o trecho [inicio, inicio+dur] de <entradaAbs>.
     *
     * @throws \RuntimeException  se o ffmpeg falhar ou nao criar a saida
     */
    public static function recortar(string $entradaAbs, string $saidaAbs, float $inicio, float $dur): void
    {
        if (!self::disponivel()) {
            throw new \RuntimeException('ffmpeg nao esta disponivel no servidor.');
        }
        if (!is_file($entradaAbs)) {
            throw new \RuntimeException('Arquivo de video nao encontrado.');
        }

        $inicio = max(0, $inicio);
        $dur = min(max(0.1, $dur), (float) self::duracaoMaxima());

        $cmd = escapeshellarg(self::bin())
            . ' -y -ss ' . escapeshellarg((string) round($inicio, 3))
            . ' -i ' . escapeshellarg($entradaAbs)
            . ' -t ' . escapeshellarg((string) round($dur, 3))
            . ' -c:v libx264 -preset veryfast -crf 23 -c:a aac -movflags +faststart '
            . escapeshellarg($saidaAbs) . ' 2>&1';

        $saida = [];
        $code = 1;
        @exec($cmd, $saida, $code);

        if ($code !== 0 || !is_file($saidaAbs) || filesize($saidaAbs) < 1024) {
            @unlink($saidaAbs);
            throw new \RuntimeException('ffmpeg falhou: ' . implode(' ', array_slice($saida, -3)));
        }
    }
}
