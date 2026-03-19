<?php

namespace App\Security;

use App\Repository\UserRepository;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Ldap\Ldap;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\CustomCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;

class DualApiAuthenticator extends AbstractAuthenticator
{
    public function __construct(
        private UserRepository $userRepository,
        private UserPasswordHasherInterface $passwordHasher,
        private JWTTokenManagerInterface $jwtManager,
        private string $ldapHost = '192.168.1.106',
        private int $ldapPort = 389,
        private string $ldapBaseDn = 'ou=people,dc=slam,dc=lab',
    ) {
    }

    public function supports(Request $request): ?bool
    {
        return $request->getPathInfo() === '/api/login' && $request->isMethod('POST');
    }

    public function authenticate(Request $request): Passport
    {
        $data = json_decode($request->getContent(), true) ?? [];
        // Accepte "identifier", "email" ou "uid" comme cle d'identifiant
        $identifier = $data['identifier'] ?? $data['email'] ?? $data['uid'] ?? '';
        $password = $data['password'] ?? '';

        if (empty($identifier) || empty($password)) {
            throw new CustomUserMessageAuthenticationException('Identifiant et mot de passe requis.');
        }

        $isEmail = str_contains($identifier, '@');

        if ($isEmail) {
            return new Passport(
                new UserBadge($identifier, fn (string $email) => $this->userRepository->findOneBy(['email' => $email])),
                new CustomCredentials(
                    fn (string $credentials, $user) => $this->passwordHasher->isPasswordValid($user, $credentials),
                    $password
                )
            );
        }

        return new Passport(
            new UserBadge($identifier, function (string $uid) {
                $user = $this->userRepository->findOneBy(['uid' => $uid]);
                if (!$user) {
                    throw new CustomUserMessageAuthenticationException('Compte non configuré (LDAP).');
                }
                return $user;
            }),
            new CustomCredentials(
                fn (string $credentials, $user) => $this->ldapBind($identifier, $credentials),
                $password
            )
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        $jwt = $this->jwtManager->create($token->getUser());
        return new JsonResponse(['token' => $jwt]);
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return new JsonResponse(
            ['error' => strtr($exception->getMessageKey(), $exception->getMessageData())],
            Response::HTTP_UNAUTHORIZED
        );
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
