<?php

namespace App\EventSubscriber;

use App\Entity\Organisation;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationSuccessEvent;
use Symfony\Component\HttpFoundation\JsonResponse;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\User;
use App\Services\Toolkit;
use Symfony\Component\Serializer\SerializerInterface;

class JWTEventExceptionListenerSubscriber implements EventSubscriberInterface
{
    private $toolkit;
    private $entityManager;
    private SerializerInterface $serializer;

    public function __construct(ToolKit $toolkit, EntityManagerInterface $entityManager, SerializerInterface $serializer)
    {
        $this->toolkit = $toolkit;
        $this->entityManager = $entityManager;
        $this->serializer = $serializer;
    }
/**
 * Méthode appelée lorsque l'authentification est réussie.
 * Elle permet d'enrichir la réponse avec les informations de l'utilisateur authentifié,
 * son administration, et son rôle pour fournir plus de contexte au client après une connexion réussie.
 *
 * *@author Michel,Daryon et Aristote
 * 
 * @param AuthenticationSuccessEvent $event L'événement d'authentification réussie.
 */
public function onSecurityAuthenticationSuccess(AuthenticationSuccessEvent $event): void
{
    // Récupérer l'utilisateur qui vient de se connecter et les données associées à l'événement
    $user = $event->getUser(); // Utilisateur authentifié
    $data = $event->getData();  // Données de réponse à retourner au client après l'authentification
    // Rechercher l'utilisateur complet (entité User) depuis la base de données
    $trueUser = $this->entityManager->getRepository(User::class)->find($user->getId());
    //  dd($user);
    // Récupérer l'entité Administration associée à cet utilisateur
    $organisation = $this->entityManager->getRepository(Organisation::class)->find(
        $trueUser->getIdOrganisation()->getId()
    );


    // Sérialiser l'entité utilisateur avec le groupe 'user' pour n'inclure que les données pertinentes
    $data_user = $this->serializer->serialize($trueUser, 'json', ['groups' => 'users_show']);

    // Sérialiser l'entité Administration avec le groupe 'api_administration_show'
    $organisation = $this->serializer->serialize($organisation, 'json', ['groups' => 'api_organisation_show']);


    // Décoder les données sérialisées en tableaux PHP
    $data_user = json_decode($data_user, true);
    $organisation = json_decode($organisation, true);


    // Enrichir les données de l'événement avec les informations utilisateur, administration et rôle
    $data['user'] = $data_user;
    $data['organisation'] = $organisation;

    // Mettre à jour les données de l'événement avec les nouvelles informations
    $event->setData($data);
}


    public static function getSubscribedEvents(): array
    {
        return [
            'lexik_jwt_authentication.on_authentication_success' => 'onSecurityAuthenticationSuccess',
        ];
    }
}
