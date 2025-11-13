<?php

namespace App\Controller;

use App\Entity\DossierContrat; 
use App\Entity\Reservation;
use App\Entity\Notification;
use App\Entity\Commentaire; // <-- Ajout pour les commentaires
use App\Form\CommentaireFormType; // <-- Ajout pour les commentaires
use App\Form\DossierClientType; 
use App\Form\DossierMairieType; 
use App\Form\DossierPrestataireType; 
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException; 
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface; 
use Symfony\Component\Workflow\WorkflowInterface;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\String\Slugger\SluggerInterface; 

// Imports pour Gotenberg (PDF)
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Mime\Part\DataPart; 
use Symfony\Component\Mime\Part\Multipart\FormDataPart;

class ContratController extends AbstractController
{
    private WorkflowInterface $reservationContractWorkflow;
    private EntityManagerInterface $em;
    private SluggerInterface $slugger; 
    private UrlGeneratorInterface $urlGenerator; 
    private HttpClientInterface $httpClient;
    private string $gotenbergApiUrl;
    private UserRepository $userRepository;

    public function __construct(
        #[Target('reservation_contract.state_machine')] 
        WorkflowInterface $reservationContractWorkflow,
        EntityManagerInterface $em,
        SluggerInterface $slugger,
        UrlGeneratorInterface $urlGenerator,
        HttpClientInterface $httpClient,
        string $gotenbergApiUrl,
        UserRepository $userRepository
    ) {
        $this->reservationContractWorkflow = $reservationContractWorkflow;
        $this->em = $em;
        $this->slugger = $slugger;
        $this->urlGenerator = $urlGenerator;
        $this->httpClient = $httpClient;
        $this->gotenbergApiUrl = $gotenbergApiUrl;
        $this->userRepository = $userRepository;
    }

    /**
     * Page "Hub" qui affiche l'état actuel du dossier de réservation
     * et gère les formulaires de workflow ET les commentaires.
     */
    #[Route('/espace-contrat/{id}', name: 'contrat_tunnel')]
    public function show(Reservation $reservation, Request $request): Response
    {
        // $this->denyAccessUnlessGranted('view', $reservation); 
        
        // --- LOGIQUE DES COMMENTAIRES (DÉBUT) ---
        $commentaire = new Commentaire();
        $commentaireForm = $this->createForm(CommentaireFormType::class, $commentaire);
        $commentaireForm->handleRequest($request);

        if ($commentaireForm->isSubmitted() && $commentaireForm->isValid()) {
            $user = $this->getUser();
            if ($user) {
                $commentaire->setAuteur($user);
                $commentaire->setReservation($reservation);
                
                $this->em->persist($commentaire);
                $this->em->flush();

                $link = $this->urlGenerator->generate('contrat_tunnel', [
                    'id' => $reservation->getId(),
                    '_fragment' => 'chat-box' // Lien direct vers le chat
                ]);

                // --- NOTIFICATION POUR L'ADMIN/CLIENT ---
                // (Cette logique peut être affinée pour notifier l'autre partie)
                if ($this->isGranted('ROLE_CLIENT')) {
                     $this->addFlash('success', 'Commentaire envoyé à l\'administration.');
                        $admins = $this->userRepository->findUsersByRoles(['ROLE_ADMIN', 'ROLE_GESTIONNAIRE']);
                        foreach ($admins as $admin) {
                            $this->creerNotification(
                                $admin,
                                "Nouveau message de {$user->getPrenom()} sur la résa #{$reservation->getId()}",
                                $link
                            );
                        }
                    } else {
                     $this->addFlash('success', 'Commentaire envoyé au client.');
                     $this->creerNotification(
                        $reservation->getUser(),
                        "Nouveau message de l'administration sur votre résa #{$reservation->getId()}",
                        $link
                    );
                }

                $this->em->flush();

            } else {
                $this->addFlash('danger', 'Vous devez être connecté pour poster un commentaire.');
            }
            
            // On redirige pour éviter la re-soumission (Pattern PRG)
            // On ajoute une #ancre pour que la page se recharge au niveau du chat
            return $this->redirectToRoute('contrat_tunnel', [
                'id' => $reservation->getId(),
                '_fragment' => 'chat-box' // Ancre vers le chat
            ]);
        }
        // --- LOGIQUE DES COMMENTAIRES (FIN) ---


        $transitions = $this->reservationContractWorkflow->getEnabledTransitions($reservation);
        $statutActuel = $reservation->getStatut(); 
        $template = 'contrat_tunnel/show.html.twig'; 
        $form = null; 
        
        // Génère le lien pour les notifications une seule fois
        $link = $this->urlGenerator->generate('contrat_tunnel', ['id' => $reservation->getId()]);

        // S'assure qu'un objet DossierContrat existe
        if (in_array($statutActuel, ['attente_dossier_client', 'attente_validation_loueur', 'attente_validation_mairie', 'attente_validation_prestataire']) && !$reservation->getDossierContrat()) {
            $dossier = new DossierContrat();
            $reservation->setDossierContrat($dossier);
            $this->em->persist($dossier); 
            $this->em->flush(); 
        }

        // ---- Logique pour afficher et traiter le formulaire du CLIENT ----
        if ($statutActuel === 'attente_dossier_client') {
            
            $dossier = $reservation->getDossierContrat(); 
            $form = $this->createForm(DossierClientType::class, $dossier); 
            $form->handleRequest($request); 

            if ($form->isSubmitted() && $form->isValid()) { 
                
                // --- Gestion des Uploads ---
                $planFile = $form->get('planSecuriteFile')->getData();
                if ($planFile) {
                    $newFilename = $this->uploadFile($planFile, 'plans_securite'); 
                    if ($newFilename) $dossier->setPlanSecuritePath($newFilename); 
                }
                $assuranceFile = $form->get('assuranceFile')->getData();
                if ($assuranceFile) {
                    $newFilename = $this->uploadFile($assuranceFile, 'assurances');
                    if ($newFilename) $dossier->setAssurancePath($newFilename);
                }
                // --- Fin Gestion Uploads ---

                $this->em->flush(); // Sauvegarde les fichiers uploadés

                // Applique la transition du workflow
                if ($this->reservationContractWorkflow->can($reservation, 'client_soumet_dossier')) {
                    $this->reservationContractWorkflow->apply($reservation, 'client_soumet_dossier');
                    
                    // --- AJOUT NOTIFICATION ---
                    $this->creerNotification(
                        $reservation->getUser(),
                        "Dossier soumis pour '{$reservation->getSalle()->getNom()}'. En attente de validation.", // ✍️ Message à personnaliser
                        $link 
                    );
                    // --- FIN AJOUT ---

                    $this->em->flush(); 
                    $this->addFlash('success', 'Dossier client soumis avec succès.');
                } else {
                        $this->addFlash('warning', 'Impossible de soumettre le dossier (workflow).');
                }
                return $this->redirectToRoute('contrat_tunnel', ['id' => $reservation->getId()]);
            }
        }
        
        // ---- Logique pour afficher/traiter le formulaire MAIRIE ----
        elseif ($statutActuel === 'attente_validation_mairie') { 
            
            $dossier = $reservation->getDossierContrat();
            $form = $this->createForm(DossierMairieType::class, $dossier);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) { 
                $this->em->flush(); 
                if ($this->reservationContractWorkflow->can($reservation, 'mairie_valide_dossier')) {
                    $this->reservationContractWorkflow->apply($reservation, 'mairie_valide_dossier');
                    // --- AJOUT NOTIFICATION ---
                    $this->creerNotification(
                        $reservation->getUser(),
                        "Avis Mairie reçu pour '{$reservation->getSalle()->getNom()}'.", // ✍️ Message à personnaliser
                        $link 
                    );
                    // --- FIN AJOUT ---
                    $this->em->flush(); 
                    $this->addFlash('success', 'Partie Mairie validée.');
                } else {
                        $this->addFlash('warning', 'Impossible de valider le dossier (workflow).');
                }
                return $this->redirectToRoute('contrat_tunnel', ['id' => $reservation->getId()]);
            }
        }
        
        // ---- Logique pour afficher/traiter le formulaire PRESTATAIRE ----
        elseif ($statutActuel === 'attente_validation_prestataire') { 
            
            $dossier = $reservation->getDossierContrat();
            $form = $this->createForm(DossierPrestataireType::class, $dossier);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $this->em->flush(); 
                if ($this->reservationContractWorkflow->can($reservation, 'prestataire_valide_dossier')) {
                    $this->reservationContractWorkflow->apply($reservation, 'prestataire_valide_dossier');
                    // --- AJOUT NOTIFICATION ---
                    $this->creerNotification(
                        $reservation->getUser(),
                        "Validation technique complétée pour '{$reservation->getSalle()->getNom()}'.", // ✍️ Message à personnaliser
                        $link 
                    );
                    // --- FIN AJOUT ---
                    $this->em->flush(); 
                    $this->addFlash('success', 'Partie Prestataire validée. Le contrat est prêt.');
                } else {
                        $this->addFlash('warning', 'Impossible de valider le dossier (workflow).');
                }
                return $this->redirectToRoute('contrat_tunnel', ['id' => $reservation->getId()]);
            }
        }

        // Rend le template principal en passant les informations nécessaires
        return $this->render($template, [
            'reservation' => $reservation,
            'transitions' => $transitions, 
            'form' => $form ? $form->createView() : null, 
            'commentaireForm' => $commentaireForm->createView(), // <-- Passe le formulaire de chat
            'commentaires' => $reservation->getCommentaires(), // <-- Passe les commentaires existants
        ]);
    }

    /**
     * Action qui exécute une transition simple (sans formulaire) via un bouton POST.
     */
    #[Route('/espace-contrat/{id}/transition/{transitionName}', name: 'contrat_transition', methods: ['POST'])]
    public function transition(Reservation $reservation, string $transitionName, Request $request): Response
    {
        $csrfTokenId = 'transition-'.$reservation->getId().'-'.$transitionName;
        if (!$this->isCsrfTokenValid($csrfTokenId, $request->request->get('_token'))) {
            $this->addFlash('danger', 'Action invalide ou expirée.');
            return $this->redirectToRoute('contrat_tunnel', ['id' => $reservation->getId()]);
        }
        
        try {
            if ($this->reservationContractWorkflow->can($reservation, $transitionName)) {
                
                // 1. Appliquer la transition
                $this->reservationContractWorkflow->apply($reservation, $transitionName);

                // --- AJOUT LOGIQUE DE NOTIFICATION ---
                $userToNotify = $reservation->getUser(); 
                $message = null;
                $link = $this->urlGenerator->generate('contrat_tunnel', ['id' => $reservation->getId()]);

                // ✍️ Personnalisez vos messages ici
                switch ($transitionName) {
                    case 'client_signe_contrat':
                        $message = "Contrat signé ! Votre réservation '{$reservation->getSalle()->getNom()}' est confirmée.";
                        break;
                    case 'loueur_valide': // Remplacez par vos noms de transition
                        $message = "Dossier validé par le loueur pour '{$reservation->getSalle()->getNom()}'.";
                        break;
                    case 'generer_contrat': // Remplacez par vos noms de transition
                         $message = "Le contrat PDF pour '{$reservation->getSalle()->getNom()}' est disponible.";
                        break;
                    case 'annuler':
                        $message = "La réservation '{$reservation->getSalle()->getNom()}' a été annulée.";
                        break;
                }

                if ($message && $userToNotify) {
                    $this->creerNotification($userToNotify, $message, $link); // On passe le lien
                }
                // --- FIN AJOUT ---

                // 2. Sauvegarder la transition ET la notification
                $this->em->flush();
                $this->addFlash('success', "Le dossier est passé à l'état suivant.");

            } else { $this->addFlash('danger', 'La transition demandée n\'est pas possible depuis l\'état actuel.'); }
        } catch (\Exception $e) { $this->addFlash('danger', 'Erreur lors de l\'application du workflow : ' . $e->getMessage()); }
        
        return $this->redirectToRoute('contrat_tunnel', ['id' => $reservation->getId()]);
    }

    /**
     * Génère le contrat en PDF en appelant Gotenberg
     */
    #[Route('/espace-contrat/{id}/pdf', name: 'contrat_pdf')]
    public function generateContratPdf(Reservation $reservation): Response
    {
        // $this->denyAccessUnlessGranted('view', $reservation);
        
        if (!in_array($reservation->getStatut(), ['contrat_genere', 'contrat_signe'])) {
            $this->addFlash('warning', 'Le contrat ne peut pas être généré à ce stade.');
            return $this->redirectToRoute('contrat_tunnel', ['id' => $reservation->getId()]);
        }

        // 1. Rendre le template Twig en HTML
        $html = $this->renderView('contrat_pdf/contrat_template.html.twig', [
            'reservation' => $reservation,
        ]);

        try {
            // 2. Construire le corps 'multipart/form-data'
            $filePart = new DataPart($html, 'index.html', 'text/html');
            $formData = new FormDataPart(['files' => $filePart]);
            $headers = $formData->getPreparedHeaders()->toArray();
            $body = $formData->bodyToIterable();

            // 3. Appeler l'API de Gotenberg
            $response = $this->httpClient->request('POST', $this->gotenbergApiUrl . '/forms/chromium/convert/html', [
                'headers' => $headers, 
                'body' => $body,
            ]);

            // 4. Vérifier si la conversion a réussi
            if (200 !== $response->getStatusCode()) {
                $errorContent = $response->getContent(false); 
                throw new \Exception('Gotenberg a échoué (Code ' . $response->getStatusCode() . '): ' . $errorContent);
            }

            // 5. Récupérer le contenu PDF et le retourner
            $pdfContent = $response->getContent();
            $filename = 'contrat-reservation-' . $reservation->getId() . '.pdf';

            $response = new Response($pdfContent);
            
            // --- MODIFICATION POUR LA LISEUSE ---
            $disposition = $response->headers->makeDisposition(
                ResponseHeaderBag::DISPOSITION_INLINE, // Affiche dans le navigateur
                $filename
            );
            // --- FIN MODIFICATION ---

            $response->headers->set('Content-Disposition', $disposition);
            $response->headers->set('Content-Type', 'application/pdf');

            return $response;

        } catch (\Exception $e) {
            $this->addFlash('danger', 'Erreur lors de la génération du PDF : ' . $e->getMessage());
            return $this->redirectToRoute('contrat_tunnel', ['id' => $reservation->getId()]);
        }
    }

    /**
     * Fonction helper pour gérer l'upload d'un fichier.
     */
    private function uploadFile($file, string $targetDirectory): ?string
    {
        if (!$file) {
            return null; 
        }

        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $this->slugger->slug($originalFilename);
        $newFilename = $safeFilename.'-'.uniqid().'.'.$file->guessExtension();

        try {
            $uploadPath = $this->getParameter('kernel.project_dir').'/public/uploads/'.$targetDirectory;
            if (!file_exists($uploadPath)) { 
                mkdir($uploadPath, 0775, true); 
            } 
            
            $file->move(
                $uploadPath,
                $newFilename
            );
            
            return 'uploads/'.$targetDirectory.'/'.$newFilename; 

        } catch (FileException $e) {
            $this->addFlash('danger', 'Erreur lors de l\'upload du fichier : '.$e->getMessage());
            return null;
        } catch (\Exception $e) {
            $this->addFlash('danger', 'Erreur lors de la configuration du chemin d\'upload. Vérifiez le paramètre kernel.project_dir ou les permissions.');
            return null;
        }
    }

    /**
     * Fonction helper pour créer une notification
     */
    private function creerNotification(?\App\Entity\User $user, string $message, ?string $link = null): void
    {
        if (!$user) {
            return;
        }
        
        $notification = new Notification();
        $notification->setUser($user);
        $notification->setMessage($message);
        $notification->setLink($link); // On sauvegarde le lien
        
        $this->em->persist($notification);
        // Note : le flush() doit être appelé APRÈS cette méthode (dans show() ou transition())
    }
}