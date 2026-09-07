<?php

namespace App\Services;

/**
 * Reconhece links de video (YouTube / Vimeo) e devolve a URL de incorporacao.
 * Usado nos slides da apresentacao.
 */
class Video
{
    /**
     * @param bool $mudo  quando true, o player abre sem som
     * @return array{provedor: string, id: string, src: string}|null
     */
    public static function embed(string $url, bool $mudo = false): ?array
    {
        $url = trim($url);
        if ($url === '') {
            return null;
        }

        // YouTube: youtu.be/ID  |  youtube.com/watch?v=ID  |  youtube.com/embed/ID  |  /shorts/ID
        if (preg_match(
            '#(?:youtube\.com/(?:watch\?(?:.*&)?v=|embed/|shorts/)|youtu\.be/)([A-Za-z0-9_-]{6,20})#i',
            $url,
            $m
        )) {
            $src = 'https://www.youtube.com/embed/' . $m[1];
            if ($mudo) {
                $src .= '?mute=1';
            }
            return ['provedor' => 'youtube', 'id' => $m[1], 'src' => $src];
        }

        // Vimeo: vimeo.com/123456789  |  player.vimeo.com/video/123456789
        if (preg_match('#vimeo\.com/(?:video/)?(\d{6,12})#i', $url, $m)) {
            $src = 'https://player.vimeo.com/video/' . $m[1];
            if ($mudo) {
                $src .= '?muted=1';
            }
            return ['provedor' => 'vimeo', 'id' => $m[1], 'src' => $src];
        }

        return null;
    }
}
