<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class IndexController extends AbstractController
{
	#[Route('/api/test', name: 'api_test')]
	public function test(): Response {
		return $this->json('top');
	}
}
