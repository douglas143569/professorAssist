<?php

namespace App\Services;

use RuntimeException;

/**
 * Erro de dominio do assistente de IA. O controller deve capturar e mostrar
 * uma mensagem amigavel ao professor, sem derrubar a aplicacao (RNF-08).
 */
class AIException extends RuntimeException
{
}
