<?php
namespace App\Controller;

use Pagerfanta\Pagerfanta;
use App\Entity\Organisation;
use Symfony\Component\Uid\Uuid;
use Doctrine\ORM\EntityManagerInterface;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use App\Repository\OrganisationRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Services\Toolkit;

#[Route('/api/v1/organisations')]
class OrganisationController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private SerializerInterface $serializer;
    private Toolkit $toolkit;

    public function __construct(EntityManagerInterface $entityManager, SerializerInterface $serializer, Toolkit $toolkit)
    {
        $this->entityManager = $entityManager;
        $this->serializer = $serializer;
        $this->toolkit = $toolkit;
    }

    #[Route('/', name: 'api_Organisation_list', methods: ['GET'])]
    public function list(OrganisationRepository $organisationRepository, Request $request): JsonResponse
    {
        $response = $this->toolkit->getPagitionOption($request, 'Organisation',  'api_organisation_show');
        return new JsonResponse($response, Response::HTTP_OK);
    }


    #[Route('/{id}', name: 'api_organisation_show', methods: ['GET'])]
    public function show($id): JsonResponse
    {
        try {
            //code...
            $organisation = $this->entityManager->getRepository(Organisation::class)->find($id);
            $data = $this->serializer->serialize($organisation, 'json',['groups' => 'api_organisation_show']);
            return new JsonResponse(["data" => json_decode($data), "code" => 200], Response::HTTP_OK);
        } catch (\Throwable $th) {
            //throw $th;
            return new JsonResponse(['message' => 'Erreur lors de la recuperation de l administration', 'code' => 500], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/', name: 'api_Organisation_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            $organisation = new Organisation();
            $organisation->setNom($data['nom'] ?? null)
                ->setSigle($data['sigle'] ?? null)
                ->setCreatedAt(new \DateTimeImmutable())
                ->setUpdatedAt(new \DateTimeImmutable());
            $this->entityManager->persist($organisation);
            $this->entityManager->flush();
            return new JsonResponse(['message' => 'Administration créée avec succès', 'code' => 200], Response::HTTP_CREATED);
        } catch (\Throwable $th) {
            //throw $th;
            return new JsonResponse(['message' => 'Erreur lors de la crée de l administration', 'code' => 500], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/{id}', name: 'api_Organisation_update', methods: ['PUT'])]
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $organisation = $this->entityManager->getRepository(Organisation::class)->find($id);
            $data = json_decode($request->getContent(), true);
            $organisation->setNom($data['nom'] ?? $organisation->getNom())
                ->setSigle($data['sigle'] ?? $organisation->getSigle())
                ->setUpdatedAt(new \DateTimeImmutable());
            $this->entityManager->flush();
            return new JsonResponse(['message' => 'Administration mis à jour avec succès', 'code' => 200], Response::HTTP_OK);
        } catch (\Throwable $th) {
            //throw $th;
            return new JsonResponse(['message' => 'Erreur lors de la mise à jour de l administration', 'code' => 500], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/{id}', name: 'api_Organisation_delete', methods: ['DELETE'])]
    public function delete($id): JsonResponse
    {
        try {
            //code...
            $organisation = $this->entityManager->getRepository(Organisation::class)->find($id);
            $users = $organisation->getUsers();
            foreach ($users as $user) {
                $user->setIdOrganisation(null);
                $this->entityManager->flush();
            }
            $this->entityManager->remove($organisation);
            $this->entityManager->flush();
            return new JsonResponse(['message' => 'Administration supprimée avec succès', 'code' => 200], Response::HTTP_OK);
        } catch (\Throwable $th) {
            //throw $th;
            return new JsonResponse(['message' => 'Erreur lors de la suppression de l administration', 'code' => 500], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
