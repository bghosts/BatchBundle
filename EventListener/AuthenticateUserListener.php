<?php

namespace Akeneo\Bundle\BatchBundle\EventListener;

use Akeneo\Bundle\BatchBundle\Command\UserAuthenticator;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Security\Core\SecurityContextInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Security\Core\Authentication\Token\PreAuthenticatedToken;
use Psr\Log\LoggerInterface;

/**
 * EventListener/Firewall
 *
 * @author    Gildas Quemener <gildas@akeneo.com>
 * @copyright 2014 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class AuthenticateUserListener
{
    public function __construct(
        SecurityContextInterface $securityContext,
        LoggerInterface $logger,
        UserProviderInterface $userProvider
    ) {
        $this->securityContext = $securityContext;
        $this->logger = $logger;
        $this->userProvider = $userProvider;
    }

    public function authenticate(ConsoleCommandEvent $event)
    {
        if (!$event->getCommand() instanceof UserAuthenticator) {
            return;
        }

        $command = $event->getCommand();
        $input = $event->getInput();
        $definition = $command->getDefinition();
        $definition->addOption(
            new InputOption(
                'user',
                'u',
                InputOption::VALUE_REQUIRED,
                'The username of the user to log as before running the command'
            )
        );
        $input->bind($definition);

        if (null !== $username = $input->getOption('user')) {
            $user = $this->userProvider->loadUserByUsername($username);
            $token = new PreAuthenticatedToken(
                $user,
                null,
                'batch',
                $user->getRoles()
            );

            // If user has no role, he won't be authenticated
            $token->setAuthenticated(true);
            $this->securityContext->setToken($token);

            $this->logger->debug(sprintf('Authenticated as "%s"', $username));
        }
    }
}