<?php

/** @noinspection PhpPropertyOnlyWrittenInspection */

namespace App\Entity;

use App\Repository\RideRepository;
use DateTime;
use Doctrine\ORM\Mapping as ORM;
use JetBrains\PhpStorm\Pure;
use Stringable;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: RideRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[UniqueEntity(fields: ['ride_id', 'source'], message: 'There is already a ride with this ID', ignoreNull: true)]
class Ride implements Stringable
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'rides')]
    #[ORM\JoinColumn(nullable: false)]
    private User $user;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private float $km;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: true)]
    private ?float $average_speed = null;

    #[ORM\Column(type: 'datetime')]
    private DateTime $date;

    #[ORM\Column(type: 'datetime')]
    private DateTime $date_added;

    #[ORM\Column(type: 'string', length: 2000, nullable: true)]
    private ?string $details = null;

    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    #[Assert\Regex(pattern: '/^\d+$/', message: 'Invalid Ride ID', match: true)]
    private ?string $ride_id = null;

    #[ORM\Column(type: 'boolean')]
    private bool $club_ride;

    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    private ?string $source = null;

    public function getId(): int
    {
        return $this->id;
    }
    public function getUser(): User
    {
        return $this->user;
    }
    public function setUser(User $user): self
    {
        $this->user = $user;

        return $this;
    }
    public function getKm(): float
    {
        return $this->km;
    }
    public function setKm($km): self
    {
        $this->km = $km;

        return $this;
    }
    public function getAverageSpeed(): ?float
    {
        return $this->average_speed;
    }
    public function setAverageSpeed(?float $average_speed): self
    {
        $this->average_speed = $average_speed;

        return $this;
    }
    public function getDate(): DateTime
    {
        return $this->date;
    }
    public function setDate(DateTime $date): self
    {
        $this->date = $date;

        return $this;
    }
    public function getDateAdded(): DateTime
    {
        return $this->date_added;
    }
    #[ORM\PrePersist]
    public function setDateAddedValue()
    {
        $this->date_added = new DateTime();
    }
    public function setDateAdded(DateTime $date_added): self
    {
        $this->date_added = $date_added;

        return $this;
    }
    public function getDetails(): ?string
    {
        return $this->details;
    }
    public function setDetails(?string $details): self
    {
        $this->details = $details;

        return $this;
    }
    public function getRideId(): ?string
    {
        return $this->ride_id;
    }
    public function setRideId(?string $ride_id): self
    {
        $this->ride_id = $ride_id;

        return $this;
    }
    public function getClubRide(): ?bool
    {
        return $this->club_ride;
    }
    public function setClubRide(bool $club_ride): self
    {
        $this->club_ride = $club_ride;

        return $this;
    }
    #[Pure]
    public function __toString(): string
    {
        return $this->getRideId();
    }
    public function getSource(): ?string
    {
        return $this->source;
    }
    public function setSource(?string $source): self
    {
        $this->source = $source;

        return $this;
    }
}
