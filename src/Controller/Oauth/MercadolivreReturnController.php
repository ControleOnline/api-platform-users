<?php

namespace ControleOnline\Controller\Oauth;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;


class MercadolivreReturnController
{
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->manager = $entityManager;
    }

    public function __invoke(Request $request): Response
    {
        echo $request->get('code');
        echo $request->get('state');

        $html = '<html><body>';
        $html .= '<h1>Seu Conteúdo HTML</h1>';
        //$html .= '<script>window.close();</script>';
        $html .= '</body></html>';
        return new Response($html, Response::HTTP_OK, ['Content-Type' => 'text/html']);
    }
}
