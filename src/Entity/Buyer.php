<?php

namespace App\Entity;

use App\Repository\BuyerRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: BuyerRepository::class)]
class Buyer
{
    #[Groups("buyer")]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[Groups("buyer")]
    #[ORM\Column(length: 255)]
    private ?string $firstname = null;

    #[Groups("buyer")]
    #[ORM\Column(length: 255)]
    private ?string $lastname = null;

    #[Groups("buyer")]
    #[ORM\Column(length: 255)]
    private ?string $email = null;

    #[Groups("buyer")]
    #[ORM\Column(length: 255)]
    private ?string $address = null;

    #[Groups("buyer")]
    #[ORM\Column(length: 255)]
    private ?string $phone = null;

    #[ORM\ManyToOne(inversedBy: 'buyers')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $company_associated = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFirstname(): ?string
    {
        return $this->firstname;
    }

    public function setFirstname(string $firstname): static
    {
        $this->firstname = $firstname;

        return $this;
    }

    public function getLastname(): ?string
    {
        return $this->lastname;
    }

    public function setLastname(string $lastname): static
    {
        $this->lastname = $lastname;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(string $address): static
    {
        $this->address = $address;

        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(string $phone): static
    {
        $this->phone = $phone;

        return $this;
    }

    public function getCompanyAssociated(): ?User
    {
        return $this->company_associated;
    }

    public function setCompanyAssociated(?User $company_associated): static
    {
        $this->company_associated = $company_associated;

        return $this;
    }
}
