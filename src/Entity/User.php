<?php

namespace App\Entity;
use App\Entity\Organisation;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid; 
use App\Repository\UserRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: UserRepository::class)]

class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(["users_show","api_Operation_show","api_organisation_show","data_select"])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(["users_show","api_organisation_show"])]
    private ?string $roles = null;

    #[ORM\Column(length: 255)]

    private ?string $password = null;

    #[ORM\Column(length: 255)]
    #[Groups(["users_show","api_organisation_show"])]
    private ?string $telephone = null;

    #[ORM\Column(length: 255)]
    #[Groups(["users_show","api_Operation_show","api_organisation_show","data_select"])]
    private ?string $nom = null;

    // #[ORM\Column(length: 255)]
    // #[Groups(["user","api_organisation_show"])]
    // private ?string $fonction = null;

    #[ORM\Column(length: 255,type: 'string', unique: true)]
    #[Groups(["users_show","api_organisation_show"])]
    private ?string $uuid = null;

    #[ORM\Column]
    #[Groups(["users_show","api_organisation_show"])]

    private ?\DateTimeImmutable $created_at = null;

    #[ORM\Column]
    #[Groups(["users_show","api_organisation_show"])]
    private ?\DateTimeImmutable $updated_at = null;

    /**
     * @var Collection<int, Operation>
     */
    #[ORM\OneToMany(targetEntity: Operation::class, mappedBy: 'user')]    
private Collection $id_user;

    #[ORM\ManyToOne(inversedBy: 'users')]
    #[Groups(["users_show","api_organisation_show","data_select"])]
    private ?Organisation $id_organisation = null;


    // #[ORM\ManyToOne(inversedBy: 'user')]
    // private ?Partenaire $id_partenaire = null;



    public function __construct()
    {
        $this->uuid = Uuid::v7()->toString();
        $this->id_user = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRoles(): array
    {
        // Convertit la chaîne de caractères en tableau si nécessaire        
        return $this->roles ? explode(',', $this->roles) : ['ROLE_USER'];        
    }

    public function setRoles(string $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    public function getTelephone(): ?string
    {
        return $this->telephone;
    }

    public function setTelephone(string $telephone): static
    {
        $this->telephone = $telephone;

        return $this;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;

        return $this;
    }

    // public function getFonction(): ?string
    // {
    //     return $this->fonction;
    // }

    // public function setFonction(string $fonction): static
    // {
    //     $this->fonction = $fonction;

    //     return $this;
    // }

    public function eraseCredentials(): void
    {
        // Cette méthode est utilisée pour nettoyer les informations sensibles de l'utilisateur, si nécessaire.
        // Par exemple : $this->plainPassword = null;
    }

    public function getUserIdentifier(): string
    {
        // Retournez ici l'identifiant unique de l'utilisateur (par exemple, l'email ou le nom d'utilisateur)
        return $this->telephone; // ou $this->username si votre identifiant est un nom d'utilisateur
    }

    public function getUuid(): ?string
    {
        return $this->uuid;
    }

    public function setUuid(string $uuid): static
    {
        $this->uuid = $uuid;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->created_at;
    }

    public function setCreatedAt(\DateTimeImmutable $created_at): static
    {
        $this->created_at = $created_at;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updated_at;
    }

    public function setUpdatedAt(\DateTimeImmutable $updated_at): static
    {
        $this->updated_at = $updated_at;

        return $this;
    }

    /**
     * @return Collection<int, Operation>
     */
    public function getIdUser(): Collection
    {
        return $this->id_user;
    }

    public function addIdUser(Operation $idUser): static
    {
        if (!$this->id_user->contains($idUser)) {
            $this->id_user->add($idUser);
            $idUser->setUser($this);
        }

        return $this;
    }

    public function removeIdUser(Operation $idUser): static
    {
        if ($this->id_user->removeElement($idUser)) {
            // set the owning side to null (unless already changed)
            if ($idUser->getUser() === $this) {
                $idUser->setUser(null);
            }
        }

        return $this;
    }

    public function getIdOrganisation(): ?Organisation
    {
        return $this->id_organisation;
    }

    public function setIdOrganisation(?Organisation $id_organisation): static
    {
        $this->id_organisation = $id_organisation;

        return $this;
    }


}
