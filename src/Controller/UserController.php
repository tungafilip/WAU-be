<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

class UserController extends AbstractController
{
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
				'passwordError' => 'Wrong password!'
			]));
		}

		return $response->setContent(json_encode([
			'emailError' => 'There is no user with provided email!'
		]));
	}

	// Change user's data (Settings)
	#[Route('/api/update/user', name: 'api_update_user', methods: 'POST')]
	public function changeUserData(Request $request, UserRepository $userRepository, EntityManagerInterface $em): Response {
		$data = $request->getContent();
		$data = json_decode($data, true);
		$user = $userRepository->findUserById($data['id']);
		$user->setFname($data['fname']);
		$user->setLname($data['lname']);
		$user->setUsername($data['username']);
		$user->setEmail($data['email']);

		// Convert string to date
		$time_input = strtotime($data['birthday']);
		$date = getdate($time_input);
		$user->setBirthday($date);

		$user->setGender($data['gender']);
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
	public function register(Request $request, UserRepository $userRepository, UserPasswordHasherInterface $passwordHasher): Response
	{
		$response = new Response();
		$user = new User();
		$data = $request->getContent();
		$data = json_decode($data, true);
		$emailError = '';
		$usernameError = '';
		$ageError = '';
		$error = false;

		// Email exist checker
		if($userRepository->findUserByEmail($data['email'])) {
			$error = true;
			$emailError = 'The user with this email is already registered!';
		}

		// Username exist checker
		if($userRepository->findUserByUsername($data['username'])) {
			$error = true;
			$usernameError = 'The user with this username is already registered!';
		}

		// Age between checker
		if($data['age'] < 18 || $data['age'] > 70) {
			$error = true;
			$ageError = 'You need to be older than 18 and younger than 70 years.';
		}

		$response->setContent(json_encode([
			'emailError' => $emailError,
			'usernameError' => $usernameError,
			'ageError' => $ageError,
		]));

		if($error == false) {
			$user->setEmail($data['email']);
			$user->setUsername($data['username']);
			$user->setFname($data['fname']);
			$user->setLname($data['lname']);

			// Password Hashing
			$plaintextPassword = $data['password'];
			$hashedPassword = $passwordHasher->hashPassword(
				$user,
				$plaintextPassword
			);
			$user->setPassword($hashedPassword);
			$user->setAge($data['age']);
			$user->setGender($data['gender']);

			$em = $this->getDoctrine()->getManager();
			$em->persist($user);
			$em->flush();

			return $this->json($data);
		}

		return $response;
	}
}
