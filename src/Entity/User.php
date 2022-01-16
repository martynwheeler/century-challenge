<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: \App\Repository\UserRepository::class)]
#[UniqueEntity(fields: ['username'], message: 'There is already an account with this username')]
#[UniqueEntity(fields: ['email'], message: 'There is already an account with this email')]
class User implements UserInterface, PasswordAuthenticatedUserInterface, \Stringable
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\Column(type: 'string', length: 180, unique: true)]
    #[Assert\Regex(pattern: "/^[a-z0-9]+$/i", message: "Username must contain only letter or numbers.")]
    private string $username;

    #[ORM\Column(type: 'json')]
    private array $roles = [];

    #[ORM\Column(type: 'string')]
    private string $password;

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\Email(message: "The email '{{ value }}' is not a valid email.")]
    private string $email;

    #[ORM\Column(type: 'string', length: 40)]
    private $surname;

    #[ORM\Column(type: 'string', length: 40)]
    private $forename;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $passwordRequestToken = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTime $requestTokenExpiry = null;

    #[ORM\OneToMany(targetEntity: \App\Entity\Ride::class, mappedBy: 'user', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Object $rides;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $komootID = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $komootRefreshToken = null;

    #[ORM\Column(type: 'bigint', nullable: true)]
    private ?string $komootTokenExpiry = null;

    #[ORM\Column(type: 'string', length: 25, nullable: true)]
    #[Assert\Regex(pattern: '/^\d+$/', match: true, message: 'Invalid Strava ID')]
    private ?string $stravaID = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $stravaRefreshToken = null;

    #[ORM\Column(type: 'bigint', nullable: true)]
    private ?string $stravaTokenExpiry = null;

    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    private ?string $preferredProvider = null;

    public function __construct()
    {
        $this->rides = new ArrayCollection();
    }
    
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->username;
    }

    /**
     * @deprecated since Symfony 5.3
     */
    public function getUsername(): string
    {
        return (string) $this->username;
    }

    public function setUsername(string $username): self
    {
        $this->username = $username;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function getPassword(): string
    {
        return (string) $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function getSalt(): ?string
    {
        // not needed when using the "bcrypt" algorithm in security.yaml
        return null;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials()
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    /**
     * getters for user fields
     */
    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getSurname(): ?string
    {
        return $this->surname;
    }

    public function setSurname(string $surname): self
    {
        $this->surname = $surname;

        return $this;
    }

    public function getForename(): ?string
    {
        return $this->forename;
    }

    public function setForename(string $forename): self
    {
        $this->forename = $forename;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->getForeName() . ' ' . $this->getSurname();
    }

    /**
     * Added functionality to set private name
     */
    public function getPrivateName()
    {
        return ucwords($this->getForeName().' '.substr($this->getSurname(), 0, 1).'.');
    }

    /**
     * fields for password reset
     */
    public function getPasswordRequestToken(): ?string
    {
        return $this->passwordRequestToken;
    }

    public function setPasswordRequestToken(?string $passwordRequestToken): self
    {
        $this->passwordRequestToken = $passwordRequestToken;

        return $this;
    }

    public function getRequestTokenExpiry(): ?\DateTimeInterface
    {
        return $this->requestTokenExpiry;
    }

    public function setRequestTokenExpiry(?\DateTimeInterface $requestTokenExpiry): self
    {
        $this->requestTokenExpiry = $requestTokenExpiry;

        return $this;
    }

    /**
     * access ride data
     */
    public function getRides(): Collection
    {
        return $this->rides;
    }

    public function addRide(Ride $ride): self
    {
        if (!$this->rides->contains($ride)) {
            $this->rides[] = $ride;
            $ride->setUser($this);
        }

        return $this;
    }

    public function removeRide(Ride $ride): self
    {
        if ($this->rides->contains($ride)) {
            $this->rides->removeElement($ride);
            // set the owning side to null (unless already changed)
            if ($ride->getUser() === $this) {
                $ride->setUser(null);
            }
        }
        return $this;
    }


    /**
     * Get and set komoot credentials
     */
    public function getKomootID(): ?string
    {
        return $this->komootID;
    }

    public function setKomootID(?string $komootID): self
    {
        $this->komootID = $komootID;

        return $this;
    }

    public function getKomootRefreshToken(): ?string
    {
        return $this->komootRefreshToken;
    }

    public function setKomootRefreshToken(?string $komootRefreshToken): self
    {
        $this->komootRefreshToken = $komootRefreshToken;

        return $this;
    }

    public function getKomootTokenExpiry(): ?string
    {
        return $this->komootTokenExpiry;
    }

    public function setKomootTokenExpiry(?string $komootTokenExpiry): self
    {
        $this->komootTokenExpiry = $komootTokenExpiry;

        return $this;
    }

    /**
     * Get and set strava credentials
     */
    public function getStravaID(): ?string
    {
        return $this->stravaID;
    }

    public function setStravaID(?string $stravaID): self
    {
        $this->stravaID = $stravaID;

        return $this;
    }

    public function getStravaRefreshToken(): ?string
    {
        return $this->stravaRefreshToken;
    }

    public function setStravaRefreshToken(?string $stravaRefreshToken): self
    {
        $this->stravaRefreshToken = $stravaRefreshToken;

        return $this;
    }

    public function getStravaTokenExpiry(): ?string
    {
        return $this->stravaTokenExpiry;
    }

    public function setStravaTokenExpiry(?string $stravaTokenExpiry): self
    {
        $this->stravaTokenExpiry = $stravaTokenExpiry;

        return $this;
    }

    public function getPreferredProvider(): ?string
    {
        return $this->preferredProvider;
    }

    public function setPreferredProvider(?string $preferredProvider): self
    {
        $this->preferredProvider = $preferredProvider;

        return $this;
    }

    public function __toString(): string
    {
        return (string)$this->getName();
    }
}