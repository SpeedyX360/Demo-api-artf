<?php
namespace App\Services;

use App\Entity\User;
use App\Entity\Organisation;
use App\Entity\Operation;
use Pagerfanta\Pagerfanta;
use Symfony\Component\Uid\Uuid;
use Doctrine\ORM\EntityManagerInterface;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Serializer\Context\Normalizer\ObjectNormalizerContextBuilder;



/*
**
**  Class ToolKit
**  Cette classe contient des fonctions utiles pour travailler avec les données utilisateur et les entités.
**  pour ne pas surcharger les code de l'application et les controllers
**  @author Orphée Lié <lieloumloum@gmail.com>
*/

class Toolkit 
{  
    private EntityManagerInterface $entityManager;
    private SerializerInterface $serializer;
    private JWTEncoderInterface $jwtManager;
    private string $apiServerUrl;

    public function __construct(EntityManagerInterface $entityManager, SerializerInterface $serializer, JWTEncoderInterface $jwtManager, ParameterBagInterface $params)
    {
        $this->entityManager = $entityManager;  
        $this->serializer = $serializer;
        $this->jwtManager = $jwtManager;
        $this->apiServerUrl = $params->get('api_server_url');
    }
    /**
     * @param array $dataSelect
     * @return array
     * 
     * Renvoie un tableau de noms d'entité avec la première lettre en majuscule
     * conçu pour intervenir au sein de la fonction qui se charge de retourner les select
     * 
     * @author Orphée Lié <lieloumloum@gmail.com>
     */
    public function formatArrayEntity(array $dataSelect): array
    {
        return array_map(function ($value) {
            // Mettre la première lettre en majuscule
            $value = ucfirst($value);
            // Retirer le 's' final s'il y en a
            if (str_ends_with($value, 's')) {
                $value = substr($value, 0, -1);
            }
            return $value;
        }, $dataSelect);
    }

    /**
     * @param array $dataSelect
     * @return array
     * 
     * Renvoie un tableau pour peupler les select de l'application avec les ID et les labels ou descriptions de chaque entité
     * @author Orphée Lié <lieloumloum@gmail.com>
     */
    public function formatArrayEntityLabel(array $dataSelect): array
    {
        $allData = [];
        foreach ($dataSelect as $key => $value) {
            $entities = $this->entityManager->getRepository('App\Entity\\'.$value)->findAll();
            $data = json_decode($this->serializer->serialize($entities, 'json', ['groups' => 'data_select']),true);
            $allData[strtolower($value)] = $data;
        }
        return $this->transformArray($allData); 
    }

    /**
     * Transforme un tableau d'entrées en un format où l'ID devient la clé et la première autre valeur est également ajoutée.
     * 
     * Cette méthode prend un tableau d'entrée de la forme :
     * [
     *   "administration" => [
     *     [
     *       "id" => 1,
     *       "nom" => "Administration Centrale",
     *       // D'autres clés possibles...
     *     ]
     *   ]
     * ]
     * et renvoie un tableau transformé sous la forme :
     * [
     *   "administration" => [
     *     [
     *       "id" => "1",
     *       "value" => "Administration Centrale"
     *     ]
     *   ]
     * ]
     * Si la clé `nom` n'existe pas, elle prend la première autre clé trouvée pour la valeur associée.
     * 
     * @param array $input Le tableau d'entrée à transformer.
     * @return array Le tableau transformé.
     * 
     * *@author Orphée Lié <lieloumloum@gmail.com>
     * 
     */
    public function transformArray(array $input): array
    {
        $result = [];
        foreach ($input as $key => $items) {
            if (is_array($items) && isset($items[0]['id'])) {
                foreach ($items as $item) {
                    if (isset($item['id'])) {
                        // Recherche la première clé différente de 'id' et extrait sa valeur
                        $otherKey = array_key_first(array_diff_key($item, ['id' => '']));
                        $value = $otherKey !== null ? $item[$otherKey] : null;
                        // Ajoute le résultat transformé
                        $result[$key][] = [
                            'value' => (string)$item['id'],
                            'label' => $value
                        ];
                    }
                }
            }else{
                $result[$key] = [];
            }
        }
        return $result;
    }


    /**
     * @param Caisse $caisse
     * @return void
     * 
     * creation des operations de caisse pour chaque partenaire sur chaque ligne d'une caisse
     * 
     * @author Orphée Lié <lieloumloum@gmail.com>
     */

    /**
     * création d'une nouvelle operation
     */


    /**
     * get Role User 
     * @param JWTTokenInterface $token
     * @return string
     * 
     * *@author Orphée Lié <lieloumloum@gmail.com>
     * 
     */

    public function getRoleUser(Request $request ): array
    {
        $authorizationHeader = $request->headers->get('Authorization');
        $token = substr($authorizationHeader, 7); 
        $payload = $this->jwtManager->decode($token);
        $user =  $this->entityManager->getRepository(User::class)->findOneBy([
            "telephone" => $payload["username"]
        ]);
        return $user->getRoles();
    }

    public function getApiServerUrl(): string
    {
        return $this->apiServerUrl;
    }

        /**
     * Gère la pagination d'une collection d'entités et renvoie les résultats paginés avec des métadonnées de pagination.
     * Cette méthode prend en compte les paramètres `page` et `limit` dans la requête pour configurer la pagination.
     * 
     * @param Request $request La requête HTTP contenant les paramètres de pagination (`page`, `limit`).
     * @param string $class_name Le nom de la classe de l'entité à paginer.
     * @param string $groupe_attribute Le groupe de sérialisation pour filtrer les attributs lors de la sérialisation des résultats.
     * 
     * *@author  Orphée Lié <lieloumloum@gmail.com>
     * 
     * @return array Les données paginées et les informations de pagination.
     */
    public function getPagitionOption(Request $request, string $class_name, string $groupe_attribute) : array
    {
        $context = (new ObjectNormalizerContextBuilder())
            ->withGroups($groupe_attribute)
            ->toArray();
        // dd($context);
        // $json = $serializer->serialize($product, 'json', $context);

        // Initialiser les paramètres de pagination par défaut
        $query = [];
        
        // Vérifie si les paramètres `page` et `limit` sont présents dans la requête, sinon valeurs par défaut
        if ($request->query->has('page') && $request->query->has('limit')) {
            $query['page'] = $request->query->get('page');
            $query['limit'] = $request->query->get('limit');
        } 

        // Définit le numéro de page et la limite d'éléments par page à partir de la requête
        $page = $request->query->getInt('page', $query['page'] ?? 1);
        $maxPerPage = $request->query->getInt('maxPerPage', $query['limit'] ?? 10);

        // Création du QueryBuilder pour la classe d'entité spécifiée
        $queryBuilder = $this->entityManager->getRepository('App\Entity\\'.$class_name)->createQueryBuilder('u');

        // Configuration de l'adaptateur pour Pagerfanta pour gérer la pagination
        $adapter = new QueryAdapter($queryBuilder);
        $pagerfanta = new Pagerfanta($adapter);
        $pagerfanta->setMaxPerPage($maxPerPage);
        $pagerfanta->setCurrentPage($page);

        // Obtenir les résultats de la page actuelle
        $items = $pagerfanta->getCurrentPageResults();

        // Sérialiser les résultats paginés avec le groupe de sérialisation spécifié
        $data = $this->serializer->serialize($items, 'json', $context);


      

        // Construire la structure de réponse, incluant les données paginées et les informations de pagination
        return [
            'data' => json_decode($data), // Les résultats paginés en format JSON décodé
            'pagination' => [
                'current_page' => $page,                      // Numéro de la page actuelle
                'max_per_page' => $maxPerPage,                // Nombre maximum d'éléments par page
                'total_items' => $pagerfanta->getNbResults(), // Nombre total d'éléments dans la collection
                'total_pages' => $pagerfanta->getNbPages(),   // Nombre total de pages
                'next_page' => $pagerfanta->hasNextPage() 
                    ? $this->getApiServerUrl() . "/api/v1/" . strtolower($class_name) . "/?page=" . ($page + 1) . "&limit=$maxPerPage" 
                    : null, // URL pour la page suivante s'il y en a une
                'previous_page' => $pagerfanta->hasPreviousPage() 
                    ? $this->getApiServerUrl() . "/api/v1/" . strtolower($class_name) . "/?page=" . ($page - 1) . "&limit=$maxPerPage" 
                    : null, // URL pour la page précedente s'il y en a une
                'first_page' => $this->getApiServerUrl() . "/api/v1/" . strtolower($class_name) . "/?page=1&limit=$maxPerPage", // URL pour la première page
                'last_page' => $this->getApiServerUrl() . "/api/v1/" . strtolower($class_name) . "/?page=" . $pagerfanta->getNbPages() . "&limit=$maxPerPage", // URL pour la première page
            ],
            "code" => 200

        ];
    }


    /**
     * Génère un numéro de reçu de caisse unique.
     *
     * @return string Le numéro de reçu unique.
     * 
     * *@author Orphée Lié <lieloumloum@gmail.com>
     * 
     */
    

    /**
     * Vérifie si le numéro de reçu existe déjà dans la base de données.
     *
     * @param string $receiptNumber Le numéro de reçu à vérifier.
     * @return bool True si le reçu existe, False sinon.
     * 
     * *@author Orphée Lié <lieloumloum@gmail.com>
     */

}