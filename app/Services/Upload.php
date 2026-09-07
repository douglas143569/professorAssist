<?php

namespace App\Services;

/**
 * Recebe arquivos enviados pelo aluno (entrega de tarefa).
 *
 * Guarda em storage/uploads/, FORA de public/ -- o download so acontece por
 * um controller que confere a posse e faz o stream. Valida extensao e tamanho.
 */
class Upload
{
    /** Extensoes aceitas na entrega de tarefa. */
    public const EXTENSOES = ['pdf', 'doc', 'docx', 'odt', 'jpg', 'jpeg', 'png', 'zip'];

    /** Extensoes aceitas nas midias de slide da apresentacao. */
    public const EXTENSOES_IMAGEM = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
    public const EXTENSOES_VIDEO = ['mp4', 'webm'];

    private static function baseDir(): string
    {
        return dirname(__DIR__, 2) . '/storage/uploads';
    }

    private static function limiteBytes(): int
    {
        $mb = (int) ($_ENV['UPLOAD_MAX_MB'] ?? 10);
        return max(1, $mb) * 1024 * 1024;
    }

    private static function limiteVideoBytes(): int
    {
        $mb = (int) ($_ENV['UPLOAD_VIDEO_MAX_MB'] ?? 40);
        return max(1, $mb) * 1024 * 1024;
    }

    /**
     * Valida e move o arquivo da entrega.
     *
     * @param array $file  item de $_FILES (com 'error','tmp_name','name','size')
     * @return array{path: string, nome: string}  caminho relativo + nome original
     * @throws UploadException
     */
    public static function salvarEntrega(array $file, int $tarefaId, int $alunoId): array
    {
        $erro = $file['error'] ?? UPLOAD_ERR_NO_FILE;

        if ($erro === UPLOAD_ERR_INI_SIZE || $erro === UPLOAD_ERR_FORM_SIZE) {
            throw new UploadException('O arquivo e grande demais.');
        }
        if ($erro !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'] ?? '')) {
            throw new UploadException('Nao foi possivel receber o arquivo. Tente de novo.');
        }
        if (($file['size'] ?? 0) > self::limiteBytes()) {
            $mb = (int) (self::limiteBytes() / 1024 / 1024);
            throw new UploadException("O arquivo passa do limite de {$mb} MB.");
        }

        $ext = strtolower(pathinfo($file['name'] ?? '', PATHINFO_EXTENSION));

        if (!in_array($ext, self::EXTENSOES, true)) {
            throw new UploadException('Tipo de arquivo nao aceito. Envie ' . implode(', ', self::EXTENSOES) . '.');
        }

        $dir = self::baseDir() . '/entregas/' . $tarefaId;

        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new UploadException('Falha ao preparar a pasta de envio.');
        }

        $nomeArquivo = $alunoId . '-' . bin2hex(random_bytes(6)) . '.' . $ext;
        $destinoAbs = $dir . '/' . $nomeArquivo;

        if (!move_uploaded_file($file['tmp_name'], $destinoAbs)) {
            throw new UploadException('Falha ao salvar o arquivo.');
        }

        return [
            'path' => 'entregas/' . $tarefaId . '/' . $nomeArquivo,
            'nome' => mb_substr((string) ($file['name'] ?? 'arquivo'), 0, 255),
        ];
    }

    /**
     * Valida e move uma midia (imagem ou video) de um slide de apresentacao.
     *
     * @param array  $file  item de $_FILES
     * @param string $tipo  'imagem' | 'video'
     * @return array{path: string}  caminho relativo em storage/uploads
     * @throws UploadException
     */
    public static function salvarMidiaApresentacao(array $file, int $aprId, string $tipo): array
    {
        $erro = $file['error'] ?? UPLOAD_ERR_NO_FILE;

        if ($erro === UPLOAD_ERR_INI_SIZE || $erro === UPLOAD_ERR_FORM_SIZE) {
            throw new UploadException('O arquivo e grande demais.');
        }
        if ($erro !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'] ?? '')) {
            throw new UploadException('Nao foi possivel receber o arquivo. Tente de novo.');
        }

        $ehVideo = $tipo === 'video';
        $exts = $ehVideo ? self::EXTENSOES_VIDEO : self::EXTENSOES_IMAGEM;
        $limite = $ehVideo ? self::limiteVideoBytes() : self::limiteBytes();

        if (($file['size'] ?? 0) > $limite) {
            $mb = (int) ($limite / 1024 / 1024);
            throw new UploadException("O arquivo passa do limite de {$mb} MB.");
        }

        $ext = strtolower(pathinfo($file['name'] ?? '', PATHINFO_EXTENSION));

        if (!in_array($ext, $exts, true)) {
            $rotulo = $ehVideo ? 'video' : 'imagem';
            throw new UploadException("Tipo de {$rotulo} nao aceito. Use " . implode(', ', $exts) . '.');
        }

        $dir = self::baseDir() . '/apresentacoes/' . $aprId;

        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new UploadException('Falha ao preparar a pasta de envio.');
        }

        $nomeArquivo = bin2hex(random_bytes(8)) . '.' . $ext;

        if (!move_uploaded_file($file['tmp_name'], $dir . '/' . $nomeArquivo)) {
            throw new UploadException('Falha ao salvar o arquivo.');
        }

        return ['path' => 'apresentacoes/' . $aprId . '/' . $nomeArquivo];
    }

    /** Remove a pasta de midias de uma apresentacao inteira. */
    public static function removerPastaApresentacao(int $aprId): void
    {
        $dir = self::baseDir() . '/apresentacoes/' . $aprId;
        if (!is_dir($dir)) {
            return;
        }
        foreach ((array) glob($dir . '/*') as $arquivo) {
            if (is_file($arquivo)) {
                @unlink($arquivo);
            }
        }
        @rmdir($dir);
    }

    /** Caminho absoluto de um arquivo guardado (para o download). */
    public static function caminhoAbsoluto(string $relativo): string
    {
        $rel = ltrim(str_replace(['..', '\\'], ['', '/'], $relativo), '/');
        return self::baseDir() . '/' . $rel;
    }

    public static function remover(string $relativo): void
    {
        $abs = self::caminhoAbsoluto($relativo);
        if (is_file($abs)) {
            @unlink($abs);
        }
    }
}
