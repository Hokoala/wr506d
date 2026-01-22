<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Comment;
use Symfony\Bundle\SecurityBundle\Security;

final class CommentUserProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly ProcessorInterface $processor,
        private readonly Security $security
    ) {
    }

    public function process($data, Operation $operation, array $uriVariables = [], array $context = [])
    {
        if ($data instanceof Comment && $data->getUser() === null) {
            $user = $this->security->getUser();
            if ($user) {
                $data->setUser($user);
            }
        }

        return $this->processor->process($data, $operation, $uriVariables, $context);
    }
}
