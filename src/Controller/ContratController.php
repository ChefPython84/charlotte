<?php

namespace App\Controller;

use App\Entity\DossierContrat; 
use App\Entity\Reservation;
use App\Form\DossierClientType; 
use App\Form\DossierMairieType; 
use App\Form\DossierPrestataireType; 
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

// AJOUTS POUR GOTENBERG (PDF)
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Mime\Part\DataPart; // Nécessaire pour la correction
use Symfony\Component\Mime\Part\Multipart\FormDataPart; // Nécessaire pour la correction

class ContratController extends AbstractController
{
    private WorkflowInterface $reservationContractWorkflow;
    private EntityManagerInterface $em;
    private SluggerInterface $slugger; 
    private UrlGeneratorInterface $urlGenerator; 
    private HttpClientInterface $httpClient;
    private string $gotenbergApiUrl;

    public function __construct(
        #[Target('reservation_contract.state_machine')] 
        WorkflowInterface $reservationContractWorkflow,
        EntityManagerInterface $em,
        SluggerInterface $slugger,
        UrlGeneratorInterface $urlGenerator,
        HttpClientInterface $httpClient,
        string $gotenbergApiUrl 
    ) {
        $this->reservationContractWorkflow = $reservationContractWorkflow;
        $this->em = $em;
        $this->slugger = $slugger;
        $this->urlGenerator = $urlGenerator;
        $this->httpClient = $httpClient;
        $this->gotenbergApiUrl = $gotenbergApiUrl;
    }

    /**
     * Page "Hub" qui affiche l'état actuel du dossier de réservation
     */
    #[Route('/espace-contrat/{id}', name: 'contrat_tunnel')]
    public function show(Reservation $reservation, Request $request): Response
    {
        // $this->denyAccessUnlessGranted('view', $reservation); 

        $transitions = $this->reservationContractWorkflow->getEnabledTransitions($reservation);
        $statutActuel = $reservation->getStatut(); 
        $template = 'contrat_tunnel/show.html.twig'; 
        $form = null; 

        // S'assure qu'un objet DossierContrat existe si on est dans un état où il est nécessaire
        if (in_array($statutActuel, ['attente_dossier_client', 'attente_validation_loueur', 'attente_validation_mairie', 'attente_validation_prestataire']) && !$reservation->getDossierContrat()) {
            $dossier = new DossierContrat();
            $reservation->setDossierContrat($dossier);
            $this->em->persist($dossier); 
            $this->em->flush(); 
        }

        // ---- Logique pour afficher et traiter le formulaire du CLIENT ----
        if ($statutActuel === 'attente_dossier_client' /* && ($this->getUser() === $reservation->getUser() || $this->isGranted('ROLE_ADMIN')) */ ) {
            
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

                $this->em->flush(); 

                // Applique la transition du workflow
                if ($this->reservationContractWorkflow->can($reservation, 'client_soumet_dossier')) {
                    $this->reservationContractWorkflow->apply($reservation, 'client_soumet_dossier');
                    $this->em->flush(); 
                    $this->addFlash('success', 'Dossier client soumis avec succès. Notification envoyée à l\'administrateur.');
                } else {
                     $this->addFlash('warning', 'Impossible de soumettre le dossier (workflow).');
                }
                return $this->redirectToRoute('contrat_tunnel', ['id' => $reservation->getId()]);
            }
        }
        
        // ---- Logique pour afficher/traiter le formulaire MAIRIE ----
        elseif ($statutActuel === 'attente_validation_mairie' /* && $this->isGranted('ROLE_MAIRIE') */) { 
            
            $dossier = $reservation->getDossierContrat();
            $form = $this->createForm(DossierMairieType::class, $dossier);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) { 
                
                $this->em->flush(); // Sauvegarde le commentaire et la coche

                // Applique la transition du workflow
                if ($this->reservationContractWorkflow->can($reservation, 'mairie_valide_dossier')) {
                    $this->reservationContractWorkflow->apply($reservation, 'mairie_valide_dossier');
                    $this->em->flush(); // Sauvegarde le nouveau statut
                    $this->addFlash('success', 'Partie Mairie validée. Notification envoyée au prestataire.');
                } else {
                     $this->addFlash('warning', 'Impossible de valider le dossier (workflow).');
                }
                return $this->redirectToRoute('contrat_tunnel', ['id' => $reservation->getId()]);
            }
        }
        
        // ---- Logique pour afficher/traiter le formulaire PRESTATAIRE ----
        elseif ($statutActuel === 'attente_validation_prestataire' /* && $this->isGranted('ROLE_PRESTATAIRE') */) { 
            
            $dossier = $reservation->getDossierContrat();
            $form = $this->createForm(DossierPrestataireType::class, $dossier);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                
                $this->em->flush(); // Sauvegarde le commentaire et la coche

                // Applique la transition du workflow
                if ($this->reservationContractWorkflow->can($reservation, 'prestataire_valide_dossier')) {
                    $this->reservationContractWorkflow->apply($reservation, 'prestataire_valide_dossier');
                    $this->em->flush(); // Sauvegarde le nouveau statut ('contrat_genere')
                    $this->addFlash('success', 'Partie Prestataire validée. Le contrat est prêt à être généré/signé.');
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
                $this->reservationContractWorkflow->apply($reservation, $transitionName);
                $this->em->flush();
                $this->addFlash('success', "Le dossier est passé à l'état suivant.");
            } else { $this->addFlash('danger', 'La transition demandée n\'est pas possible depuis l\'état actuel.'); }
        } catch (\Exception $e) { $this->addFlash('danger', 'Erreur lors de l\'application du workflow : ' . $e->getMessage()); }
        return $this->redirectToRoute('contrat_tunnel', ['id' => $reservation->getId()]);
    }

    /**
     * Génère le contrat en PDF en appelant Gotenberg (VERSION CORRIGÉE)
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
            // --- CORRECTION : Construire le corps 'multipart/form-data' manuellement ---
            
            // 1. Créer la partie "fichier" (notre HTML)
            $filePart = new DataPart($html, 'index.html', 'text/html');
            
            // 2. Créer le formulaire de données
            $formData = new FormDataPart(['files' => $filePart]);

            // 3. Récupérer les en-têtes et le corps préparés par FormDataPart
            $headers = $formData->getPreparedHeaders()->toArray();
            $body = $formData->bodyToIterable();

            // 4. Appeler l'API de Gotenberg avec les bons en-têtes et le corps
            $response = $this->httpClient->request('POST', $this->gotenbergApiUrl . '/forms/chromium/convert/html', [
                'headers' => $headers, // Utilise les en-têtes Content-Type générés
                'body' => $body,       // Utilise le corps généré
            ]);
            // --- FIN DE LA CORRECTION ---


            // 5. Vérifier si la conversion a réussi
            if (200 !== $response->getStatusCode()) {
                $errorContent = $response->getContent(false); 
                throw new \Exception('Gotenberg a échoué (Code ' . $response->getStatusCode() . '): ' . $errorContent);
            }

            // 6. Récupérer le contenu PDF et le retourner
            $pdfContent = $response->getContent();
            $filename = 'contrat-reservation-' . $reservation->getId() . '.pdf';

            $response = new Response($pdfContent);
            $disposition = $response->headers->makeDisposition(
                ResponseHeaderBag::DISPOSITION_ATTACHMENT, 
                $filename
            );
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
}