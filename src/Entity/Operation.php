<?php
namespace App\Entity;

use App\Repository\OperationRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: OperationRepository::class)]
class Operation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(["api_Operation_show", "Operation_list"])]
    private ?int $id = null;

    #[ORM\Column(type: "float")]
    #[Groups(["api_Operation_show", "Operation_list"])]
    private ?float $montant = null;

    #[ORM\Column(length: 255)]
    #[Groups(["api_Operation_show", "Operation_list"])]
    private ?string $nom_destinataire = null;

    #[ORM\Column(length: 255)]
    #[Groups(["api_Operation_show", "Operation_list"])]
    private ?string $numero_cni_destinataire = null;

    #[ORM\Column(length: 255)]
    #[Groups(["api_Operation_show", "Operation_list"])]
    private ?string $nom_expediteur = null;

    #[ORM\Column(length: 255)]
    #[Groups(["api_Operation_show", "Operation_list"])]
    private ?string $numero_cni_expediteur = null;

    #[ORM\Column(length: 255, type: "string", unique: true)]
    #[Groups(["api_Operation_show", "Operation_list"])]
    private ?string $uuid = null;

    #[ORM\Column(type: "datetime_immutable")]
    #[Groups(["api_Operation_show", "Operation_list"])]
    private ?\DateTimeImmutable $created_at = null;

    #[ORM\Column(type: "datetime_immutable")]
    #[Groups(["api_Operation_show", "Operation_list"])]
    private ?\DateTimeImmutable $updated_at = null;

    #[ORM\ManyToOne(inversedBy: 'id_user')]
        #[Groups(["api_Operation_show", "Operation_list","data_select"])]

    private ?User $user = null;

    #[ORM\ManyToOne(inversedBy: 'operation')]
    #[Groups(["api_Operation_show", "Operation_list","data_select"])]
    private ?Statut $statut = null;
    
    public function __construct() {
        $this->uuid = Uuid::v7()->toString();
        $this->created_at = new \DateTimeImmutable();
        $this->updated_at = new \DateTimeImmutable();
         
     }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMontant(): ?float
    {
        return $this->montant;
    }

    public function setMontant(float $montant): self
    {
        $this->montant = $montant;
        return $this;
    }

    public function getNomDestinataire(): ?string
    {
        return $this->nom_destinataire;
    }

    public function setNomDestinataire(string $nom_destinataire): static
    {
        $this->nom_destinataire = $nom_destinataire;
        return $this;
    }

    public function getNumeroCNIDestinataire(): ?string
    {
        return $this->numero_cni_destinataire;
    }

    public function setNumeroCNIDestinataire(string $numero_cni_destinataire): static
    {
        $this->numero_cni_destinataire = $numero_cni_destinataire;
        return $this;
    }

    public function getNomExpediteur(): ?string
    {
        return $this->nom_expediteur;
    }

    public function setNomExpediteur(string $nom_expediteur): static
    {
        $this->nom_expediteur = $nom_expediteur;
        return $this;
    }

    public function getNumeroCNIExpediteur(): ?string
    {
        return $this->numero_cni_expediteur;
    }

    public function setNumeroCNIExpediteur(string $numero_cni_expediteur): self
    {
        $this->numero_cni_expediteur = $numero_cni_expediteur;
        return $this;
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

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;
        return $this;
    }

    public function getStatut(): ?Statut
    {
        return $this->statut;
    }

    public function setStatut(?Statut $statut): static
    {
        $this->statut = $statut;

        return $this;
    }
}
