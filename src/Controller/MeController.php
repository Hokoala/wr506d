<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;

class MeController
{
    #[Route('/api/me', name: 'get_current_user', methods: ['GET'])]
    public function getCurrentUser(UserInterface $user): JsonResponse
    {
        if (!$user instanceof \App\Entity\User) {
            return new JsonResponse(['error' => 'Invalid user type'], 400);
        }

        $userData = [
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'firstname' => $user->getFirstname(),
            'lastname' => $user->getLastname(),
        ];

        return new JsonResponse($userData);
    }
}
