<?php
namespace App\Controller;

use App\Entity\User;
use App\Entity\Organisation;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use App\Services\Toolkit;

// class UserController
#[Route('/api/v1/users')]
class UserController extends AbstractController
{
    private UserRepository $usersRepository;
    private EntityManagerInterface $entityManager;
    private SerializerInterface $serializer;
    private Toolkit $toolkit;

    public function __construct(
        UserRepository $usersRepository,
        EntityManagerInterface $entityManager,
        SerializerInterface $serializer,
        Toolkit $toolkit
    ) {
        $this->usersRepository = $usersRepository;
        $this->entityManager = $entityManager;
        $this->serializer = $serializer;
        $this->toolkit = $toolkit;
    }

    // création de data select
    #[Route('/data-select', name: 'app_app_data_select', methods: ['POST'])]
    public function dataSelect(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $dataSelectEntity = $this->toolkit->formatArrayEntity($data['data_select']);
        $allSelectEntity = $this->toolkit->formatArrayEntityLabel($dataSelectEntity);
        return  new JsonResponse(($allSelectEntity), Response::HTTP_OK);        
    }

    // recuperer la liste des utilisateurs
    #[Route('/', name: 'users_list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $response = $this->toolkit->getPagitionOption($request, 'User',  'users_show');
        return new JsonResponse($response, Response::HTTP_OK);
    }

    // recuperer un seule utilisateur
    #[Route('/{id}', name: 'users_show', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        try {
            //code...
            $user = $this->usersRepository->find($id);
            $data = $this->serializer->serialize($user, 'json', ['groups' => 'users_show']);
            return new JsonResponse([ 'data' => json_decode($data), 'code' => 200], Response::HTTP_OK);
        } catch (\Throwable $th) {
            //throw $th;
            return new JsonResponse(['message' => 'Utilisateur introuvable', 'code' => 404], Response::HTTP_NOT_FOUND);
        }
    }

    // creation d'un utilisateur
    #[Route('/', name: 'users_create', methods: ['POST'])]
    public function create(Request $request, ValidatorInterface $validator, UserPasswordHasherInterface $passwordHasher): JsonResponse
    {
        try {
            //code...
            $data = json_decode($request->getContent(), true);
            $user = new User();
            $user->setNom($data['nom'])
                    ->setRoles($data['roles'] ?? "ROLE_USER")
                    ->setTelephone($data['telephone'])
                    ->setCreatedAt(new \DateTimeImmutable())
                    ->setUpdatedAt(new \DateTimeImmutable());
            if ($data['id_organisation'] !== null) {
                $id_organisation = $this->entityManager->getRepository(Organisation::class)->findOneBy(['id' => $data['id_organisation']]);
                $user->setIdOrganisation($id_organisation);
            }
            
            if ($data['password'] !== null) {
                $hashedPassword = $passwordHasher->hashPassword($user, $data['password']);
                $user->setPassword($hashedPassword);
            }
            $this->entityManager->persist($user);
            $this->entityManager->flush();
            return new JsonResponse(['message' => 'Utilisateur crée avec succès', 'code' => 200], Response::HTTP_OK);
        } catch (\Throwable $th) {
            return new JsonResponse(['message' => "un problème est survenu lors de la création de l utilisateur", 'code' => 500], Response::HTTP_INTERNAL_SERVER_ERROR);}
    }

    // mise à jour d'un utilisateur
    #[Route('/{id}', name: 'users_update', methods: ['PUT'])]
    public function update(int $id, Request $request, UserPasswordHasherInterface $passwordHasher): JsonResponse
    {
        try {
                $user = $this->usersRepository->find($id);
                $data = json_decode($request->getContent(), true);
                $user->setNom($data['nom'] ?? $user->getNom())
                        ->setRoles($data['roles'] ?? $user->getRoles())
                        ->setTelephone($data['telephone'] ?? $user->getTelephone())
                        ->setUpdatedAt(new \DateTimeImmutable());
                if ($data['password'] !== null) {
                    $hashedPassword = $passwordHasher->hashPassword($user, $data['password']);
                    $user->setPassword($hashedPassword);
                }
                if ($data['id_organisation'] !== null) {
                    $id_organisation = $this->entityManager->getRepository(Organisation::class)->findOneBy(['id' => $data['id_organisation']]);
                    $user->setIdOrganisation($id_organisation);
                }
                $this->entityManager->flush();
                return new JsonResponse(['message' => 'Utilisateur modifié avec succès', 'code' => 200], Response::HTTP_OK);
            } catch (\Throwable $th) {
                return new JsonResponse(['message' => 'Utilisateur introuvable', 'code' => 404], Response::HTTP_NOT_FOUND);
            }
    }

    // suppression d'un utilisateur
    #[Route('/{id}', name: 'users_delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        try {
            $user = $this->usersRepository->find($id);        
            $this->entityManager->remove($user);
            $this->entityManager->flush();
            return new JsonResponse(['message' => 'Utilisateur supprimé avec succès', 'code' => 200], Response::HTTP_OK);
        } catch (\Throwable $th) {
            return new JsonResponse(['message' => 'Utilisateur introuvable', 'code' => 404], Response::HTTP_NOT_FOUND);
        }
    }
}
