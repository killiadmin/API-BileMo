<?php

namespace App\Entity;

use App\Repository\BuyerRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation\Groups;
use Hateoas\Configuration\Annotation as Hateoas;

/**
 * @Hateoas\Relation(
 *      "self",
 *      href = @Hateoas\Route(
 *          "detailBuyer",
 *          parameters = { "id" = "expr(object.getId())" }
 *      ),
 *      exclusion = @Hateoas\Exclusion(groups="buyer")
 * )
 *
 * @Hateoas\Relation(
 *       "delete",
 *       href = @Hateoas\Route(
 *           "deleteBuyer",
 *           parameters = { "id" = "expr(object.getId())" },
 *       ),
 *       exclusion = @Hateoas\Exclusion(groups="buyer", excludeIf = "expr(not is_granted('ROLE_ADMIN'))"),
 *  )
 */
#[ORM\Entity(repositoryClass: BuyerRepository::class)]
class Buyer
{
    #[Groups(["buyer"])]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[Groups(["buyer"])]
    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "The firstname must be entered")]
    #[Assert\Length(
        min: 1,
        max: 255,
        minMessage: "The firstname must be at least {{ limit }} characters",
        maxMessage: "The firstname cannot be more than {{ limit }} characters"
    )]
    private ?string $firstname = null;

    #[Groups(["buyer"])]
    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "The lastname must be entered")]
    #[Assert\Length(
        min: 1,
        max: 255,
        minMessage: "The lastname must be at least {{ limit }} characters",
        maxMessage: "The lastname cannot be more than {{ limit }} characters"
    )]
    private ?string $lastname = null;

    #[Groups(["buyer"])]
    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "The email must be entered")]
    #[Assert\Length(
        min: 1,
        max: 255,
        minMessage: "The email must be at least {{ limit }} characters",
        maxMessage: "The email cannot be more than {{ limit }} characters"
    )]
    private ?string $email = null;

    #[Groups(["buyer"])]
    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "The address must be entered")]
    #[Assert\Length(
        min: 1,
        max: 255,
        minMessage: "The address must be at least {{ limit }} characters",
        maxMessage: "The address cannot be more than {{ limit }} characters"
    )]
    private ?string $address = null;

    #[Groups(["buyer"])]
    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "The phone number must be entered")]
    #[Assert\Length(
        min: 1,
        max: 255,
        minMessage: "The phone number must be at least {{ limit }} characters",
        maxMessage: "The phone number cannot be more than {{ limit }} characters"
    )]
    private ?string $phone = null;

    #[Groups(["buyer"])]
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
