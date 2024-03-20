<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Attribute\Route;

class DefaultController extends AbstractController
{
    /**
     * This method returns a RedirectResponse object that redirects to the '/api/doc' URL.
     *
     * @return RedirectResponse A RedirectResponse object that redirects to the '/api/doc' URL.
     *
     * @Route('/', name: 'default')
     */
    #[Route('/', name: 'default')]
    public function index(): RedirectResponse
    {
        return $this->redirect('/api/doc');
    }
}