<?php

namespace App\Controllers;

use App\Models\Apresentacao;
use App\Models\Material;
use App\Models\Modulo;
use App\Services\AI;
use App\Services\AIException;
use App\Services\Ffmpeg;
use App\Services\Logger;
use App\Services\Upload;
use App\Services\UploadException;
use App\Services\Video;

/**
 * Assistente de apresentacoes: slides + resumo para a aula expositiva de um tema.
 * Segue o fluxo dos outros geradores: IA cria rascunho -> professor edita/aprova.
 */
class ApresentacaoController extends AppController
{
    private const SLIDES_PADRAO = 8;

    /** Gera o rascunho da apresentacao com a IA. */
    public function gerar(string $moduloId): void
    {
        $prof = $this->professor();
        $modulo = (new Modulo())->find((int) $moduloId, $prof['id']);

        if (!$modulo) {
            $this->notFound('Tema nao encontrado.');
            return;
        }

        $nSlides = (int) ($_POST['n_slides'] ?? self::SLIDES_PADRAO);
        $foco = trim($_POST['foco'] ?? '');

        $bncc = null;
        if (!empty($modulo['codigos_bncc'])) {
            $bncc = trim(explode(',', $modulo['codigos_bncc'])[0]);
        }

        $existentes = (new Apresentacao())->byModulo((int) $moduloId);
        $jaAbordado = $this->resumirExistentes($existentes);

        try {
            $resultado = (new AI())->gerarApresentacao(
                tema: $modulo['titulo'],
                objetivos: $modulo['objetivos'] ?? null,
                habilidadeBncc: $bncc,
                etapa: $modulo['disciplina_etapa'],
                nSlides: $nSlides,
                foco: $foco !== '' ? $foco : null,
                userId: (int) $prof['id'],
                jaAbordado: $jaAbordado,
            );
        } catch (AIException $e) {
            $this->flash('Nao foi possivel gerar a apresentacao: ' . $e->getMessage());
            $this->redirect('/modulos/' . $moduloId);
            return;
        }

        $numero = count($existentes) + 1;
        $tituloBase = $resultado['titulo'] !== '' ? $resultado['titulo'] : 'Apresentacao: ' . $modulo['titulo'];

        $id = (new Apresentacao())->create([
            'modulo_id' => (int) $moduloId,
            'titulo' => $numero > 1 ? "Apresentacao {$numero}: " . $modulo['titulo'] : $tituloBase,
            'foco' => $foco,
            'slides_json' => json_encode($resultado['slides'], JSON_UNESCAPED_UNICODE),
            'resumo' => $resultado['resumo'],
            'origem' => 'ia',
            'status' => 'rascunho',
        ]);

        Logger::activity('apresentacao.generated', [
            'entity_type' => 'apresentacao',
            'entity_id' => $id,
            'description' => 'IA gerou uma apresentacao de aula',
        ]);

        $this->flash('Apresentacao gerada como rascunho. Revise os slides e o resumo antes de aprovar.');
        $this->redirect('/apresentacoes/' . $id);
    }

    public function show(string $id): void
    {
        $prof = $this->professor();
        $apr = (new Apresentacao())->find((int) $id, $prof['id']);

        if (!$apr) {
            $this->notFound('Apresentacao nao encontrada.');
            return;
        }

        $material = !empty($apr['material_id']) ? (new Material())->porOrigem('apresentacao:' . $id) : null;

        $this->view('apresentacoes.show', [
            'title' => $apr['titulo'],
            'apr' => $apr,
            'slides' => Apresentacao::slides($apr),
            'temTurma' => !empty($apr['turma_id']),
            'material' => $material,
            'ffmpegOk' => Ffmpeg::disponivel(),
        ]);
    }

    /** Modo apresentacao: tela cheia, um slide por vez. */
    public function apresentar(string $id): void
    {
        $prof = $this->professor();
        $apr = (new Apresentacao())->find((int) $id, $prof['id']);

        if (!$apr) {
            $this->notFound('Apresentacao nao encontrada.');
            return;
        }

        $this->viewSemLayout('apresentacoes.apresentar', [
            'apr' => $apr,
            'slides' => Apresentacao::slides($apr),
        ]);
    }

    public function update(string $id): void
    {
        $prof = $this->professor();
        $model = new Apresentacao();
        $apr = $model->find((int) $id, $prof['id']);

        if (!$apr) {
            $this->notFound('Apresentacao nao encontrada.');
            return;
        }

        $titulo = trim($_POST['titulo'] ?? '');
        if ($titulo === '') {
            $this->flash('Informe o titulo da apresentacao.');
            $this->redirect('/apresentacoes/' . $id);
            return;
        }

        try {
            $slides = $this->slidesDoPost((int) $id);
        } catch (UploadException $e) {
            $this->flash($e->getMessage());
            $this->redirect('/apresentacoes/' . $id);
            return;
        }

        // Apaga do disco as midias que sairam (troca de arquivo ou slide removido).
        $this->limparMidiasOrfas(Apresentacao::slides($apr), $slides);

        $resumo = trim($_POST['resumo'] ?? '');

        $model->update((int) $id, [
            'titulo' => $titulo,
            'foco' => trim($_POST['foco'] ?? ''),
            'slides_json' => json_encode($slides, JSON_UNESCAPED_UNICODE),
            'resumo' => $resumo,
            'status' => $apr['status'],
        ]);

        // Mantem o material do aluno em dia se o resumo ja foi publicado.
        if (!empty($apr['material_id']) && !empty($apr['turma_id'])) {
            (new Material())->publicarResumo(
                (int) $apr['turma_id'],
                (int) $prof['id'],
                'apresentacao:' . $id,
                $titulo . ' — resumo',
                $resumo
            );
        }

        $this->flash('Alteracoes salvas.');
        $this->redirect('/apresentacoes/' . $id);
    }

    public function aprovar(string $id): void
    {
        $prof = $this->professor();
        $model = new Apresentacao();
        $apr = $model->find((int) $id, $prof['id']);

        if (!$apr) {
            $this->notFound('Apresentacao nao encontrada.');
            return;
        }

        $model->update((int) $id, [
            'titulo' => $apr['titulo'],
            'foco' => $apr['foco'],
            'slides_json' => $apr['slides_json'],
            'resumo' => $apr['resumo'],
            'status' => 'aprovado',
        ]);

        Logger::activity('apresentacao.approved', [
            'entity_type' => 'apresentacao',
            'entity_id' => (int) $id,
            'description' => 'Apresentacao aprovada pelo professor',
        ]);

        $this->flash('Apresentacao aprovada.');
        $this->redirect('/apresentacoes/' . $id);
    }

    public function excluir(string $id): void
    {
        $prof = $this->professor();
        $model = new Apresentacao();
        $apr = $model->find((int) $id, $prof['id']);

        if (!$apr) {
            $this->notFound('Apresentacao nao encontrada.');
            return;
        }

        (new Material())->removerPorOrigem('apresentacao:' . $id);
        Upload::removerPastaApresentacao((int) $id);
        $model->delete((int) $id);

        $this->flash('Apresentacao excluida.');
        $this->redirect('/modulos/' . (int) $apr['modulo_id']);
    }

    /** Recorta o video enviado de um slide (ffmpeg) e substitui pelo trecho. */
    public function cortarVideo(string $id): void
    {
        $prof = $this->professor();
        $model = new Apresentacao();
        $apr = $model->find((int) $id, $prof['id']);

        if (!$apr) {
            $this->notFound('Apresentacao nao encontrada.');
            return;
        }

        if (!Ffmpeg::disponivel()) {
            $this->flash('Recorte de video precisa do ffmpeg instalado no servidor.');
            $this->redirect('/apresentacoes/' . $id);
            return;
        }

        $alvo = basename(trim($_POST['arquivo'] ?? ''));
        $inicio = (float) str_replace(',', '.', (string) ($_POST['inicio'] ?? '0'));
        $fim = (float) str_replace(',', '.', (string) ($_POST['fim'] ?? '0'));

        $slides = Apresentacao::slides($apr);
        $idx = null;
        foreach ($slides as $i => $s) {
            if (($s['video_arquivo'] ?? '') !== '' && basename($s['video_arquivo']) === $alvo) {
                $idx = $i;
                break;
            }
        }

        if ($idx === null) {
            $this->flash('Video do slide nao encontrado.');
            $this->redirect('/apresentacoes/' . $id);
            return;
        }

        if ($inicio < 0 || $fim <= $inicio) {
            $this->flash('Informe um trecho valido (fim depois do inicio).');
            $this->redirect('/apresentacoes/' . $id);
            return;
        }

        $dur = $fim - $inicio;
        if ($dur > Ffmpeg::duracaoMaxima()) {
            $this->flash('O trecho e longo demais (maximo ' . Ffmpeg::duracaoMaxima() . ' s).');
            $this->redirect('/apresentacoes/' . $id);
            return;
        }

        $entradaRel = $slides[$idx]['video_arquivo'];
        $saidaRel = 'apresentacoes/' . (int) $id . '/' . bin2hex(random_bytes(8)) . '.mp4';

        try {
            Ffmpeg::recortar(
                Upload::caminhoAbsoluto($entradaRel),
                Upload::caminhoAbsoluto($saidaRel),
                $inicio,
                $dur
            );
        } catch (\RuntimeException $e) {
            Logger::exception($e);
            $this->flash('Nao foi possivel recortar o video. O original foi mantido.');
            $this->redirect('/apresentacoes/' . $id);
            return;
        }

        Upload::remover($entradaRel);
        $slides[$idx]['video_arquivo'] = $saidaRel;

        $model->update((int) $id, [
            'titulo' => $apr['titulo'],
            'foco' => $apr['foco'],
            'slides_json' => json_encode($slides, JSON_UNESCAPED_UNICODE),
            'resumo' => $apr['resumo'],
            'status' => $apr['status'],
        ]);

        Logger::activity('apresentacao.video_recortado', [
            'entity_type' => 'apresentacao',
            'entity_id' => (int) $id,
            'description' => "Recortou o video do slide " . ($idx + 1),
        ]);

        $this->flash('Trecho do video recortado e salvo.');
        $this->redirect('/apresentacoes/' . $id);
    }

    /** Stream de uma midia (imagem/video) de um slide. */
    public function midia(string $id, string $arquivo): void
    {
        $prof = $this->professor();
        $apr = (new Apresentacao())->find((int) $id, $prof['id']);

        if (!$apr) {
            $this->notFound('Apresentacao nao encontrada.');
            return;
        }

        $nome = basename($arquivo);
        $abs = Upload::caminhoAbsoluto('apresentacoes/' . (int) $id . '/' . $nome);

        if ($nome === '' || !is_file($abs)) {
            $this->notFound('Arquivo nao encontrado.');
            return;
        }

        $ext = strtolower(pathinfo($abs, PATHINFO_EXTENSION));
        $mimes = [
            'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png',
            'webp' => 'image/webp', 'gif' => 'image/gif',
            'mp4' => 'video/mp4', 'webm' => 'video/webm',
        ];

        header('Content-Type: ' . ($mimes[$ext] ?? 'application/octet-stream'));
        header('Content-Length: ' . filesize($abs));
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: private, max-age=3600');
        readfile($abs);
        exit;
    }

    public function publicarResumo(string $id): void
    {
        $prof = $this->professor();
        $model = new Apresentacao();
        $apr = $model->find((int) $id, $prof['id']);

        if (!$apr) {
            $this->notFound('Apresentacao nao encontrada.');
            return;
        }

        if (empty($apr['turma_id'])) {
            $this->flash('Vincule a materia a uma turma para publicar o resumo.');
            $this->redirect('/apresentacoes/' . $id);
            return;
        }

        if (trim((string) $apr['resumo']) === '') {
            $this->flash('Escreva o resumo antes de publicar.');
            $this->redirect('/apresentacoes/' . $id);
            return;
        }

        $materialId = (new Material())->publicarResumo(
            (int) $apr['turma_id'],
            (int) $prof['id'],
            'apresentacao:' . $id,
            $apr['titulo'] . ' — resumo',
            (string) $apr['resumo']
        );

        $model->definirMaterial((int) $id, $materialId ?: null);

        Logger::activity('apresentacao.resumo_publicado', [
            'entity_type' => 'apresentacao',
            'entity_id' => (int) $id,
            'description' => 'Resumo da apresentacao publicado para a turma',
        ]);

        $this->flash('Resumo publicado para a turma.');
        $this->redirect('/apresentacoes/' . $id);
    }

    public function despublicarResumo(string $id): void
    {
        $prof = $this->professor();
        $model = new Apresentacao();
        $apr = $model->find((int) $id, $prof['id']);

        if (!$apr) {
            $this->notFound('Apresentacao nao encontrada.');
            return;
        }

        (new Material())->removerPorOrigem('apresentacao:' . $id);
        $model->definirMaterial((int) $id, null);

        $this->flash('Resumo removido da area do aluno.');
        $this->redirect('/apresentacoes/' . $id);
    }

    /* ---------------------------------------------------------------- */

    /**
     * Remonta os slides a partir dos campos paralelos do formulario, incluindo
     * as midias (imagem enviada, link de video, video enviado).
     *
     * @throws UploadException  arquivo invalido ou link de video nao reconhecido
     */
    private function slidesDoPost(int $aprId): array
    {
        $titulos = (array) ($_POST['slide_titulo'] ?? []);
        $topicos = (array) ($_POST['slide_topicos'] ?? []);
        $notas = (array) ($_POST['slide_nota'] ?? []);
        $imgAtual = (array) ($_POST['slide_imagem_atual'] ?? []);
        $imgRemover = (array) ($_POST['slide_remover_imagem'] ?? []);
        $vidUrl = (array) ($_POST['slide_video_url'] ?? []);
        $vidAtual = (array) ($_POST['slide_video_arquivo_atual'] ?? []);
        $vidRemover = (array) ($_POST['slide_remover_video'] ?? []);
        $vidMudo = (array) ($_POST['slide_video_mudo'] ?? []);

        $slides = [];
        foreach ($titulos as $i => $tituloBruto) {
            $titulo = trim(strip_tags((string) $tituloBruto));

            $listaTopicos = [];
            foreach (preg_split('/\r?\n/', (string) ($topicos[$i] ?? '')) as $linha) {
                $linha = trim(strip_tags($linha));
                $linha = ltrim($linha, "-*• \t");
                if ($linha !== '') {
                    $listaTopicos[] = mb_substr($linha, 0, 240);
                }
            }

            $nota = mb_substr(trim(strip_tags((string) ($notas[$i] ?? ''))), 0, 600);

            // --- Imagem ---
            $imagem = self::relativaSegura((string) ($imgAtual[$i] ?? ''), $aprId);
            $novaImg = $this->arquivoDoPost('slide_imagem', $i);
            if ($novaImg !== null) {
                if ($imagem !== null) {
                    Upload::remover($imagem);
                }
                $imagem = Upload::salvarMidiaApresentacao($novaImg, $aprId, 'imagem')['path'];
            } elseif (($imgRemover[$i] ?? '0') === '1') {
                $imagem = null;
            }

            // --- Video por link ---
            $videoUrl = trim((string) ($vidUrl[$i] ?? ''));
            if ($videoUrl !== '') {
                if (Video::embed($videoUrl) === null) {
                    throw new UploadException(
                        'Link de video nao reconhecido no slide ' . ($i + 1)
                        . '. Use um link do YouTube ou do Vimeo.'
                    );
                }
                $videoUrl = mb_substr($videoUrl, 0, 400);
            } else {
                $videoUrl = null;
            }

            // --- Video por arquivo ---
            $videoArq = self::relativaSegura((string) ($vidAtual[$i] ?? ''), $aprId);
            $novoVid = $this->arquivoDoPost('slide_video', $i);
            if ($novoVid !== null) {
                if ($videoArq !== null) {
                    Upload::remover($videoArq);
                }
                $videoArq = Upload::salvarMidiaApresentacao($novoVid, $aprId, 'video')['path'];
            } elseif (($vidRemover[$i] ?? '0') === '1') {
                $videoArq = null;
            }

            $temMidia = $imagem !== null || $videoUrl !== null || $videoArq !== null;

            if ($titulo === '' && $listaTopicos === [] && $nota === '' && !$temMidia) {
                continue;
            }

            $slides[] = [
                'titulo' => $titulo !== '' ? mb_substr($titulo, 0, 160) : 'Slide',
                'topicos' => array_slice($listaTopicos, 0, 8),
                'nota' => $nota,
                'imagem' => $imagem,
                'video_url' => $videoUrl,
                'video_arquivo' => $videoArq,
                'video_mudo' => ($vidMudo[$i] ?? '0') === '1',
            ];
        }

        return $slides;
    }

    /** Aceita um path relativo so se for de fato desta apresentacao. */
    private static function relativaSegura(string $path, int $aprId): ?string
    {
        $path = trim($path);
        $prefixo = 'apresentacoes/' . $aprId . '/';

        if ($path === '' || !str_starts_with($path, $prefixo) || str_contains($path, '..')) {
            return null;
        }

        return $path;
    }

    /** Um item de $_FILES['<campo>'] (array) para o indice do slide, ou null. */
    private function arquivoDoPost(string $campo, int $i): ?array
    {
        if (!isset($_FILES[$campo]['name'][$i])) {
            return null;
        }
        $erro = $_FILES[$campo]['error'][$i] ?? UPLOAD_ERR_NO_FILE;
        if ($erro === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        return [
            'name' => $_FILES[$campo]['name'][$i],
            'type' => $_FILES[$campo]['type'][$i] ?? '',
            'tmp_name' => $_FILES[$campo]['tmp_name'][$i] ?? '',
            'error' => $erro,
            'size' => $_FILES[$campo]['size'][$i] ?? 0,
        ];
    }

    /** Apaga do disco as midias que existiam antes e nao estao mais em uso. */
    private function limparMidiasOrfas(array $antigos, array $novos): void
    {
        $paths = static function (array $slides): array {
            $out = [];
            foreach ($slides as $s) {
                foreach (['imagem', 'video_arquivo'] as $chave) {
                    if (!empty($s[$chave])) {
                        $out[] = $s[$chave];
                    }
                }
            }
            return $out;
        };

        $orfaos = array_diff($paths($antigos), $paths($novos));
        foreach ($orfaos as $path) {
            Upload::remover($path);
        }
    }

    /** Titulos dos slides das apresentacoes ja existentes, para o prompt. */
    private function resumirExistentes(array $rows): array
    {
        $resumos = [];
        foreach (array_slice($rows, 0, 5) as $row) {
            $titulos = [];
            foreach (Apresentacao::slides($row) as $slide) {
                if (!empty($slide['titulo'])) {
                    $titulos[] = $slide['titulo'];
                }
            }
            if ($titulos !== []) {
                $resumos[] = mb_substr(
                    ($row['titulo'] ?? 'Apresentacao') . ': ' . implode(' / ', array_slice($titulos, 0, 10)),
                    0,
                    300
                );
            }
        }
        return $resumos;
    }
}
