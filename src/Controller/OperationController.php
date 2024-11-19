<?php
namespace App\Controller;

use App\Entity\User;
use App\Entity\Statut;
use App\Entity\Operation;
use App\Services\Toolkit;
use App\Repository\OperationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

// Class OperationController
#[Route('/api/v1/operations')]
class OperationController extends AbstractController
{
    private OperationRepository $OperationRepository;
    private EntityManagerInterface $entityManager;
    private SerializerInterface $serializer;
    private Toolkit $toolkit;

    public function __construct(
        OperationRepository $OperationRepository,
        EntityManagerInterface $entityManager,
        SerializerInterface $serializer,
        Toolkit $toolkit
    ) {
        $this->OperationRepository = $OperationRepository;
        $this->entityManager = $entityManager;
        $this->serializer = $serializer;
        $this->toolkit = $toolkit;
    }

// recuperation de toute les operations
#[Route('/', name: 'operation_list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $response = $this->toolkit->getPagitionOption($request, 'Operation',  'api_Operation_show');
        return new JsonResponse($response, Response::HTTP_OK);
    }
    
// recuperation d'une seule operation
    #[Route('/{id}', name: 'operation_show', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        try {
            //code...
            $operation = $this->OperationRepository->find($id);
            $data = $this->serializer->serialize($operation, 'json', ['groups' => 'api_Operation_show']);
            return new JsonResponse([ 'data' => json_decode($data), 'code' => 200], Response::HTTP_OK);
        } catch (\Throwable $th) {
            //throw $th;
            return new JsonResponse(['message' => 'Operation introuvable', 'code' => 404], Response::HTTP_NOT_FOUND);
        }
    } 
// creation d'une operation
    #[Route('/', name: 'operation_create', methods: ['POST'])]
    public function create(Request $request, ValidatorInterface $validator): JsonResponse
    {

        $data = json_decode($request->getContent(), true);
        $operation = new Operation();
        $operation->setMontant($data['montant'])
                ->setNomDestinataire($data['nom_destinataire'] )
                ->setNumeroCNIDestinataire($data['numero_cni_destinataire'])
                ->setNumeroCNIExpediteur($data['numero_cni_expediteur'])
                ->setNomExpediteur($data['nom_expediteur'])
                ->setCreatedAt(new \DateTimeImmutable())
                ->setUpdatedAt(new \DateTimeImmutable());
            if ($data['id_user'] !== null) {
                $id_user = $this->entityManager->getRepository(User::class)->findOneBy(['id' => $data['id_user']]);
                $operation->setUser($id_user);
            }
            
        // Vérifier si le montant total des opérations pour cet expéditeur dépasse 1 000 000

        $montantTotalDuMois = $this->entityManager->getRepository(Operation::class)
                ->getMontantTotalDuMois($data['numero_cni_expediteur']);
        $montantTotalDuMois += $data['montant'];
            
        $this->entityManager->persist($operation);
        $this->entityManager->flush();

        // Si le montant total dépasse 1 000 000, retourner un message d'avertissement mais considérer l'opération enregistrée
        if ($montantTotalDuMois > 1000000) {

        // if ($data['statut'] !== null) {

        $statut = $this->entityManager->getRepository(Statut::class)->findOneBy(['id' => 1]);
        $operation->setStatut($statut);
        $this->entityManager->flush();
        // }

        return new JsonResponse([
            'message' => 'Le montant total des opérations dépasse la limite autorisée de 1 000 000 dans ce mois.',
            'montant_total' => $montantTotalDuMois,  'code' => 200], Response::HTTP_OK);
        }else {
        // if ($data['statut'] !== null) {

            $statut = $this->entityManager->getRepository(Statut::class)->findOneBy(['id' => 2]);
            $operation->setStatut($statut);
        // }

        $this->entityManager->flush();
    }

    // Si l'opération est enregistrée sans dépasser la limite, répondre normalement
    return new JsonResponse([
        'message' => 'Opération créée avec succès.',  'code' => 200, ], Response::HTTP_OK);
}

    // mise à jour d'une operation
    #[Route('/{id}', name: 'operation_update', methods: ['PUT'])]
    // #[IsGranted("ROLE_ADMIN", message:"Seul un administrateur peut modifier une opération")]
    public function update(int $id, Request $request): JsonResponse
    {
        // dd($this->toolkit->getRoleUser($request)[0]);
    try {
        if ($this->toolkit->getRoleUser($request)[0] !== "ROLE_ADMIN") {
            return new JsonResponse(['message' => 'Vous n\'avez pas les droits pour modifier cette opération', 'code' => 403], Response::HTTP_FORBIDDEN);
            # code...
        }
        $operation = $this->OperationRepository->find($id);
        $data = json_decode($request->getContent(), true);
        $operation->setMontant($data['montant'] ?? $operation->getMontant())
                ->setNomDestinataire($data['nom_destinataire'] ?? $operation->getNomdestinataire())
                ->setNumeroCNIExpediteur($data['numero_cni_expediteur'] ?? $operation->getNumeroCNIExpediteur())
                ->setNumeroCNIDestinataire($data['numero_cni_destinataire'] ?? $operation->getNumeroCNIDestinataire())
                ->setNomExpediteur($data['nom_expediteur'] ?? $operation->getNomExpediteur())
                ->setCreatedAt(new \DateTimeImmutable())
                ->setUpdatedAt(new \DateTimeImmutable());
                    if ($data['id_user'] !== null) {
                        $id_user = $this->entityManager->getRepository(User::class)->findOneBy(['id' => $data['id_user']]);
                        $operation->setUser($id_user);
                    }
                    if ($data['statut'] !== null) {
                        $statut = $this->entityManager->getRepository(Statut::class)->findOneBy(['id' => $data['statut']]);
                        $operation->setStatut($statut);
                    }
            $this->entityManager->persist($operation);
            $this->entityManager->flush();
            return new JsonResponse(['message' => 'Operation modifiée avec succès', 'code' => 200], Response::HTTP_OK);
        } catch (\Throwable $th) {
            return new JsonResponse(['message' => 'Operation introuvable', 'code' => 404], Response::HTTP_NOT_FOUND);
        }
    }

    // suppression d'une operation
    #[Route('/{id}', name: 'operation_delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        try {
            $operation = $this->OperationRepository->find($id);        
            $this->entityManager->remove($operation);
            $this->entityManager->flush();
            return new JsonResponse(['message' => 'Operation supprimée avec succès', 'code' => 200], Response::HTTP_OK);
        } catch (\Throwable $th) {
            return new JsonResponse(['message' => 'Operation introuvable', 'code' => 404], Response::HTTP_NOT_FOUND);
        }
    }

    // Récupérer la somme des montants pour un expéditeur dans le mois
    #[Route("/montants/{numero_cni_expediteur}", name: "operation_montants", methods: ['GET'])]
    public function index(string $numero_cni_expediteur, OperationRepository $operationRepository): Response
    {
        // Récupérer la somme des montants pour ce numéro d'expéditeur dans le mois
        $montantTotalDuMois = $operationRepository->getMontantTotalDuMois($numero_cni_expediteur);
        
        // Limite autorisée
        $limite = 1000000;

        // Vérifier si la somme dépasse la limite
        if ($montantTotalDuMois > $limite) {
            // Retourner un message d'erreur si la somme dépasse la limite
            return $this->json([
                'message' => 'Vous avez dépassé la limite autorisée de 1 000 000 dans le mois.',
                'montant_total' => $montantTotalDuMois
            ], Response::HTTP_BAD_REQUEST);
        }

        // Sinon, retourner la somme des montants avec un message de validation
        return $this->json([
            'montant_total' => $montantTotalDuMois,
            'message' => 'Le montant total des transactions dans le mois est valide.'
        ]);
    }
} 