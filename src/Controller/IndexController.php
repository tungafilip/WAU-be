<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class IndexController extends AbstractController
{
	/**
	 * @Route("/api/test", name="test")
	 */
    public function index(): string
    {
        return 'Test';
    }
}
