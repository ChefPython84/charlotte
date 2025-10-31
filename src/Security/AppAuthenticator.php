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
use Symfony\Bundle\SecurityBundle\Security; 

class AppAuthenticator extends AbstractLoginFormAuthenticator
{
    use TargetPathTrait;

    public const LOGIN_ROUTE = 'app_login';
    private UrlGeneratorInterface $urlGenerator;
    private Security $security; 

    public function __construct(UrlGeneratorInterface $urlGenerator, Security $security)
    {
        $this->urlGenerator = $urlGenerator;
        $this->security = $security;
    }

    /**
     * Cette méthode doit être implémentée pour satisfaire l'interface AuthenticatorInterface.
     * Bien qu'elle soit habituellement gérée par le parent, si le code a été modifié, on la remet.
     */
    public function authenticate(Request $request): Passport
    {
        $email = (string) $request->request->get('email', '');
        $password = (string) $request->request->get('password', '');
        $csrfToken = (string) $request->request->get('_csrf_token', '');

        // stocker le dernier username dans la session (clé explicite, compatible)
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
    
    // --- LOGIQUE DE REDIRECTION CORRIGÉE ---
    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        $session = $request->getSession();

        // 1. Redirection pour les ADMINISTRATEURS (Priorité Maximale)
        if ($this->security->isGranted('ROLE_ADMIN')) {
            // Supprime la page cible pour éviter les confusions futures
            $session->remove('_security.' . $firewallName . '.target_path');
            return new RedirectResponse($this->urlGenerator->generate('admin'));
        }

        // 2. Redirection pour les GESTIONNAIRES
        if ($this->security->isGranted('ROLE_GESTIONNAIRE')) {
            $session->remove('_security.' . $firewallName . '.target_path');
            return new RedirectResponse($this->urlGenerator->generate('admin')); 
        }

        if ($this->security->isGranted('ROLE_MAIRIE')) {
            $session->remove('_security.' . $firewallName . '.target_path');
            return new RedirectResponse($this->urlGenerator->generate('admin')); 
        }

        // 3. Redirection vers la Page Cible Mémorisée (pour les clients, mairie, etc.)
        if ($targetPath = $this->getTargetPath($session, $firewallName)) {
            return new RedirectResponse($targetPath);
        }

        // 4. Redirection par défaut (CLIENTS et MAIRIE)
        return new RedirectResponse($this->urlGenerator->generate('home'));
    }
    
    // La méthode getLoginUrl() est nécessaire car elle est abstraite dans le parent
    protected function getLoginUrl(Request $request): string
    {
        return $this->urlGenerator->generate(self::LOGIN_ROUTE);
    }
}