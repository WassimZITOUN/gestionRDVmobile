<?php

namespace App\Security;

use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Ldap\Ldap;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\CustomCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\SecurityRequestAttributes;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

class DualAuthAuthenticator extends AbstractLoginFormAuthenticator
{
    use TargetPathTrait;

    public const LOGIN_ROUTE = 'app_login';

    public function __construct(
        private UserRepository $userRepository,
        private UserPasswordHasherInterface $passwordHasher,
        private UrlGeneratorInterface $urlGenerator,
        private string $ldapHost = '192.168.1.106',
        private int $ldapPort = 389,
        private string $ldapBaseDn = 'ou=people,dc=slam,dc=lab',
    ) {
    }

    public function authenticate(Request $request): Passport
    {
        $identifier = $request->getPayload()->getString('_username');
        $password = $request->getPayload()->getString('_password');

        $request->getSession()->set(SecurityRequestAttributes::LAST_USERNAME, $identifier);

        // Detection automatique : si l'identifiant contient "@", auth classique (email/bcrypt)
        // sinon, auth LDAP (uid)
        $isEmail = str_contains($identifier, '@');

        if ($isEmail) {
            // --- Auth classique : email + bcrypt ---
            return new Passport(
                new UserBadge($identifier, fn (string $email) => $this->userRepository->findOneBy(['email' => $email])),
                new CustomCredentials(
                    function (string $credentials, $user) {
                        return $this->passwordHasher->isPasswordValid($user, $credentials);
                    },
                    $password
                ),
                [
                    new CsrfTokenBadge('authenticate', $request->getPayload()->getString('_csrf_token')),
                    new RememberMeBadge(),
                ]
            );
        }

        // --- Auth LDAP : uid + bind LDAP ---
        return new Passport(
            new UserBadge($identifier, function (string $uid) {
                $user = $this->userRepository->findOneBy(['uid' => $uid]);
                if (!$user) {
                    throw new CustomUserMessageAuthenticationException('Compte non configuré (LDAP). Contactez un administrateur.');
                }
                return $user;
            }),
            new CustomCredentials(
                function (string $credentials, $user) use ($identifier) {
                    return $this->ldapBind($identifier, $credentials);
                },
                $password
            ),
            [
                new CsrfTokenBadge('authenticate', $request->getPayload()->getString('_csrf_token')),
                new RememberMeBadge(),
            ]
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        if ($targetPath = $this->getTargetPath($request->getSession(), $firewallName)) {
            return new RedirectResponse($targetPath);
        }

        return new RedirectResponse($this->urlGenerator->generate('homepage'));
    }

    protected function getLoginUrl(Request $request): string
    {
        return $this->urlGenerator->generate(self::LOGIN_ROUTE);
    }

    private function ldapBind(string $uid, string $password): bool
    {
        try {
            $ldap = Ldap::create('ext_ldap', [
                'host' => $this->ldapHost,
                'port' => $this->ldapPort,
                'version' => 3,
            ]);
            $dn = sprintf('uid=%s,%s', ldap_escape($uid, '', LDAP_ESCAPE_DN), $this->ldapBaseDn);
            $ldap->bind($dn, $password);
            return true;
        } catch (\Exception) {
            return false;
        }
    }
}
