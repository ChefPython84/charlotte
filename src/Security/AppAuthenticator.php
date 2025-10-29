<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Util\TargetPathTrait;
// Assurez-vous d'avoir ce use
use Symfony\Bundle\SecurityBundle\Security; 

class AppAuthenticator extends AbstractLoginFormAuthenticator
{
    use TargetPathTrait;

    public const LOGIN_ROUTE = 'app_login';
    private UrlGeneratorInterface $urlGenerator;
    private Security $security; // Le service Security est injecté

    // Le constructeur doit accepter Security
    public function __construct(UrlGeneratorInterface $urlGenerator, Security $security) 
    {
        $this->urlGenerator = $urlGenerator;
        $this->security = $security; // Le service est stocké
    }

    public function authenticate(Request $request): Passport
    {
        $email = (string) $request->request->get('email', '');
        $password = (string) $request->request->get('password', '');
        $csrfToken = (string) $request->request->get('_csrf_token', '');

        $request->getSession()->set('_security.last_username', $email);

        return new Passport(
            new UserBadge($email),
            new PasswordCredentials($password),
            [
                new CsrfTokenBadge('authenticate', $csrfToken),
                new RememberMeBadge(),
            ]
        );
    }

    // --- VÉRIFIEZ CETTE MÉTHODE ---
// src/Security/AppAuthenticator.php
    // ... (use statements et constructeur comme avant)

    // src/Security/AppAuthenticator.php
// ... (use statements et constructeur comme avant)

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        // --- LOGIQUE FINALE SIMPLIFIÉE ---

        // 1. Si c'est un ADMIN, on le redirige vers sa page cible OU vers /admin
        if ($this->security->isGranted('ROLE_ADMIN')) {
            if ($targetPath = $this->getTargetPath($request->getSession(), $firewallName)) {
                return new RedirectResponse($targetPath);
            }
            return new RedirectResponse($this->urlGenerator->generate('admin'));
        }

        // 2. POUR TOUS LES AUTRES UTILISATEURS (MAIRIE, CLIENT, GESTIONNAIRE...)
        // ON IGNORE LA PAGE CIBLE et on redirige TOUJOURS vers la page d'accueil.
        return new RedirectResponse($this->urlGenerator->generate('home'));

        // --- FIN DE LA LOGIQUE ---
    }

// ... (getLoginUrl comme avant)

    // ... (getLoginUrl comme avant)
    // --- FIN DE LA VÉRIFICATION ---

    protected function getLoginUrl(Request $request): string
    {
        return $this->urlGenerator->generate(self::LOGIN_ROUTE);
    }
}