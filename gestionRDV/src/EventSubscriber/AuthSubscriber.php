<?php

namespace App\EventSubscriber;

use App\Entity\LoginEvent;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Http\Event\LogoutEvent;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class AuthSubscriber implements EventSubscriberInterface
{
    private EntityManagerInterface $em;
    private TokenStorageInterface $tokenStorage;
    private RequestStack $requestStack;
    private int $idleTimeout;

    public function __construct(EntityManagerInterface $em, TokenStorageInterface $tokenStorage, RequestStack $requestStack, ?int $idleTimeout = null)
    {
        $this->em = $em;
        $this->tokenStorage = $tokenStorage;
        $this->requestStack = $requestStack;
        $this->idleTimeout = $idleTimeout ?? (int) ($_ENV['SESSION_IDLE_TIMEOUT'] ?? 1800);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            InteractiveLoginEvent::class => 'onInteractiveLogin',
            LogoutEvent::class => 'onLogout',
            KernelEvents::REQUEST => 'onKernelRequest',
        ];
    }

    public function onInteractiveLogin(InteractiveLoginEvent $event): void
    {
        $request = $event->getRequest();
        $token = $event->getAuthenticationToken();
        $user = $token->getUser();

        // detect possible automatic logout before this login
        $repo = $this->em->getRepository(LoginEvent::class);
        $last = $repo->findOneBy(['user' => $user], ['createdAt' => 'DESC']);
        if ($last && $last->getType() === LoginEvent::TYPE_LOGIN && $last->getLastActivity()) {
            $diff = time() - $last->getLastActivity()->getTimestamp();
            if ($diff > $this->idleTimeout) {
                $auto = new LoginEvent();
                $auto->setUser($user)->setType(LoginEvent::TYPE_AUTOLOGOUT)->setIp($last->getIp())->setUserAgent($last->getUserAgent());
                $this->em->persist($auto);
                $this->em->flush();
            }
        }

        $evt = new LoginEvent();
        $evt->setUser($user);
        $evt->setType(LoginEvent::TYPE_LOGIN);
        $evt->setIp($request->getClientIp());
        $evt->setUserAgent($request->headers->get('User-Agent'));
        $evt->setLastActivity(new \DateTime());

        $this->em->persist($evt);
        $this->em->flush();

        // store current login event id in session to update activity later
        $session = $request->getSession();
        if ($session) {
            $session->set('login_event_id', $evt->getId());
        }
    }

    public function onLogout(LogoutEvent $event): void
    {
        $request = $event->getRequest();
        $token = $this->tokenStorage->getToken();
        $user = $token ? $token->getUser() : null;

        $evt = new LoginEvent();
        $evt->setUser($user instanceof \App\Entity\User ? $user : null);
        $evt->setType(LoginEvent::TYPE_LOGOUT);
        $evt->setIp($request->getClientIp());
        $evt->setUserAgent($request->headers->get('User-Agent'));
        $this->em->persist($evt);

        // also update linked login event lastActivity if present
        $session = $request->getSession();
        if ($session && $session->has('login_event_id')) {
            $id = $session->get('login_event_id');
            $login = $this->em->getRepository(LoginEvent::class)->find($id);
            if ($login) {
                $login->setLastActivity(new \DateTime());
                $this->em->persist($login);
            }
            $session->remove('login_event_id');
        }

        $this->em->flush();
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();
        $token = $this->tokenStorage->getToken();
        if (!$token) return;
        $user = $token->getUser();
        if (!$user || !is_object($user)) return;

        $session = $request->getSession();
        if (!$session) return;

        // update lastActivity on stored login event
        if ($session->has('login_event_id')) {
            $id = $session->get('login_event_id');
            $login = $this->em->getRepository(LoginEvent::class)->find($id);
            if ($login) {
                $login->setLastActivity(new \DateTime());
                $this->em->persist($login);
                $this->em->flush();
            }
        }
    }
}
