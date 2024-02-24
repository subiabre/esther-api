<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Dto\SessionAuthenticationDto;
use App\Entity\Session;
use App\Entity\SessionRequest;
use App\Repository\UserRepository;
use App\Service\AuthenticationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;

class SessionAuthenticationProcessor implements ProcessorInterface
{
    public function __construct(
        private UserRepository $userRepository,
        private UserPasswordHasherInterface $userPasswordHasher,
        private AuthenticationService $authenticationService,
        private EntityManagerInterface $entityManagerInterface,
    ) {
    }

    /**
     * @param SessionAuthenticationDto $data
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): Session
    {
        $user = $this->userRepository->findOneBy(['email' => $data->email]);
        if (!$user) {
            throw new UserNotFoundException();
        }

        if (!$this->userPasswordHasher->isPasswordValid($user, $data->password)) {
            throw new BadCredentialsException();
        }

        $session = new Session;
        $session->setUser($user);
        $session->setToken($this->authenticationService->generateSessionToken($user));
        $session->setRequest(new SessionRequest(
            $context['request']->headers->get('Origin'),
            $context['request']->headers->get('User-Agent'),
        ));

        $this->entityManagerInterface->persist($session);
        $this->entityManagerInterface->flush();

        return $session;
    }
}
