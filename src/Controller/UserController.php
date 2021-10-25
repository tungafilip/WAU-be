<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

class UserController extends AbstractController
{
	#[Route('/api/top', name: 'api_top')]
	public function top(): Response {
		return $this->json('top');
	}

	#[Route('/api/login', name: 'api_login', methods: 'POST')]
	public function login(Request $request, UserRepository $userRepository, UserPasswordHasherInterface $passwordHasher): Response
	{
		$response = new Response();

		$data = $request->getContent();
		$data = json_decode($data, true);
		$email = $data['email'];
		if ($userRepository->findUserByEmail($email) !== null) {
			$user = $userRepository->findUserByEmail($email);
			$plaintextPassword = $data['password'];

			// Compare hashed password with plaintext password
			$compare = $passwordHasher->isPasswordValid($user, $plaintextPassword);
			if ($compare) {

				// User Api Key
				$userApiKey = $user->getUserApiKey();
				if (!$userApiKey) {

					// Generate User's new Api-key
					$userId = $user->getId();
					$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
					$charactersLength = strlen($characters);
					$randomString = '';
					for ($i = 0; $i < $charactersLength; $i++) {
						$randomString .= $characters[rand(0, $charactersLength - 1)];
					}
					$apiKey = $randomString . strval($user->getId());
					$userRepository->updateUsersApiKey($apiKey, $userId);
				}

				return $response->setContent(json_encode([
					'userApiKey' => $user->getUserApiKey()
				]));
			}

			return $response->setContent(json_encode([
				'error' => 'Wrong password!'
			]));
		}

		return $response->setContent(json_encode([
			'error' => 'There is no user with provided email!'
		]));
	}

	// Find user by Api key
	#[Route('/api/get/user/by/api', name: 'api_get_user', methods: 'POST')]
	public function findUserByApiKey(Request $request, UserRepository $userRepository): Response {
		$response = new Response();

		$data = $request->getContent();
		$data = json_decode($data, true);
		$apiKey = $data['apiKey'];
		$user = $userRepository->findUserByApiKey($apiKey);

		return $user ?
			$this->json($user) :
			$response->setContent(json_encode([
				'delete' => 'Deleting cookie!'
			]));
	}

	#[Route('/api/register', name: 'api_register', methods: 'POST')]
	public function register(Request $request, UserPasswordHasherInterface $passwordHasher): Response
	{
		$user = new User();
		$user->setEmail($request->get('email'));
		$user->setUsername($request->get('username'));
		$user->setFname($request->get('fname'));
		$user->setLname($request->get('lname'));

		// Password Hashing
		$plaintextPassword = $request->get('password');
		$hashedPassword = $passwordHasher->hashPassword(
			$user,
			$plaintextPassword
		);
		$user->setPassword($hashedPassword);
		$user->setAge($request->get('age'));
		$user->setGender($request->get('gender'));

		$em = $this->getDoctrine()->getManager();
		$em->persist($user);
		$em->flush();

		return $this->json($user);
	}
}
