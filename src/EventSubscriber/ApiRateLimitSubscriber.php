<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @SuppressWarnings(PHPMD.LongVariable)
 */
final class ApiRateLimitSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly RateLimiterFactory $anonymousApiLimiter,
        private readonly RateLimiterFactory $authenticatedApiLimiter,
        private readonly TokenStorageInterface $tokenStorage
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            // Run after security firewall (priority 8) to ensure JWT authentication is processed
            KernelEvents::REQUEST => ['onKernelRequest', 5],
            KernelEvents::RESPONSE => ['onKernelResponse', -10],
        ];
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.ElseExpression)
     */
    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();

        // Only apply rate limiting to API routes
        if (!str_starts_with($request->getPathInfo(), '/api/')) {
            return;
        }

        // Don't rate limit documentation endpoints
        if (str_starts_with($request->getPathInfo(), '/api/docs') ||
            str_starts_with($request->getPathInfo(), '/api/graphql/graphiql')) {
            return;
        }

        // Determine if user is authenticated via JWT bearer token
        $token = $this->tokenStorage->getToken();
        $user = $token?->getUser();
        $isAuthenticated = $user instanceof UserInterface;


        // Get custom rate limit from user if available
        $userCustomLimit = null;
        if ($isAuthenticated && method_exists($user, 'Limiter')) {
        }
        $userCustomLimit = $user->getLimiter();

        // Use IP address as identifier for anonymous users, user ID for authenticated users
        $identifier = $isAuthenticated
            ? $user->getUserIdentifier()
            : $request->getClientIp() ?? 'unknown';

        // Select appropriate rate limiter
        $limiter = $isAuthenticated
            ? $this->authenticatedApiLimiter->create($identifier)
            : $this->anonymousApiLimiter->create($identifier);

        // Consume a token from the rate limiter
        $limit = $limiter->consume();


        if ($userCustomLimit !== null && $userCustomLimit > 0 && $isAuthenticated) {
            $consumed = $limit->getLimit() - $limit->getRemainingTokens();
            $effectiveLimit = $userCustomLimit;
            $effectiveRemaining = max(0, $userCustomLimit - $consumed);
            $isAccepted = $consumed < $userCustomLimit;
        } else {
            $effectiveLimit = $limit->getLimit();
            $effectiveRemaining = $limit->getRemainingTokens();
            $isAccepted = $limit->isAccepted();
        }

// Store rate limit info in request attributes for the response listener
        $request->attributes->set('_rate_limit', [
            'limit' => $effectiveLimit,
            'remaining' => $effectiveRemaining,
            'reset' => $limit->getRetryAfter()->getTimestamp(),
        ]);


        if (!$isAccepted) {
            $retryAfter = $limit->getRetryAfter();
            $response = new JsonResponse(
                [
                    'error' => 'Too Many Requests',
                    'message' => 'Rate limit exceeded. Please try again later.',
                    'retry_after' => $retryAfter->getTimestamp(),
                ],
                429
            );

            $response->headers->set('Retry-After', (string) $retryAfter->getTimestamp());
            $response->headers->set('X-RateLimit-Limit', (string) $effectiveLimit);
            $response->headers->set('X-RateLimit-Remaining', '0');
            $response->headers->set('X-RateLimit-Reset', (string) $retryAfter->getTimestamp());

            $event->setResponse($response);
        }
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        $request = $event->getRequest();
        $response = $event->getResponse();

        // Only add headers if we have rate limit info
        $rateLimitInfo = $request->attributes->get('_rate_limit');
        if (!$rateLimitInfo) {
            return;
        }

        // Add rate limit headers to the response
        $response->headers->set('X-RateLimit-Limit', (string) $rateLimitInfo['limit']);
        $response->headers->set('X-RateLimit-Remaining', (string) $rateLimitInfo['remaining']);
        $response->headers->set('X-RateLimit-Reset', (string) $rateLimitInfo['reset']);
    }
}
