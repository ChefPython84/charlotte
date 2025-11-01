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
use App\Repository\UserRepository;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;

class AppAuthenticator extends AbstractLoginFormAuthenticator
{
    use TargetPathTrait;

    public const LOGIN_ROUTE = 'app_login';
    private UrlGeneratorInterface $urlGenerator;
    private Security $security;
    private UserRepository $userRepository;

    public function __construct(UrlGeneratorInterface $urlGenerator, Security $security, UserRepository $userRepository)
    {
        $this->urlGenerator = $urlGenerator;
        $this->security = $security;
        $this->userRepository = $userRepository;
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

        // --- DÉBUT DE LA LOGIQUE "BONUS" ---
        
        // 1. Récupérer l'utilisateur d'abord
        $user = $this->userRepository->findOneBy(['email' => $email]);

        if (!$user) {
            // On lance l'exception standard si l'utilisateur n'existe pas
            throw new UserNotFoundException('Email could not be found.');
        }

        // 2. VÉRIFIER SI LE MOT DE PASSE EST NULL
        if ($user->getMotDePasse() === null) {
            // L'utilisateur existe mais n'a jamais défini de mot de passe
            throw new CustomUserMessageAuthenticationException(
                'Votre compte est en attente. Veuillez utiliser le lien envoyé par email pour définir votre mot de passe.'
            );
        }
        // --- FIN DE LA LOGIQUE "BONUS" ---

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