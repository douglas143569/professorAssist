<?php

namespace App\Controllers;

use App\Core\Controller;

/** Paginas publicas de Politica de Privacidade e de Cookies (LGPD). */
class PoliticaController extends Controller
{
    public function privacidade(): void
    {
        $this->viewSemLayout('legal.privacidade');
    }

    public function cookies(): void
    {
        $this->viewSemLayout('legal.cookies');
    }
}
