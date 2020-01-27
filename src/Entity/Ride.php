<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="App\Repository\RideRepository")
 * @ORM\HasLifecycleCallbacks()
 * @UniqueEntity(fields={"ride_id", "source"}, message="There is already a ride with this ID", ignoreNull=true)
 */

class Ride
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User", inversedBy="rides")
     * @ORM\JoinColumn(nullable=false)
     */
    private $user;

    /**
     * @ORM\Column(type="decimal", precision=10, scale=2)
     */
    private $km;

    /**
     * @ORM\Column(type="decimal", precision=10, scale=2, nullable=true)
     */
    private $average_speed;

    /**
     * @ORM\Column(type="datetime")
     */
    private $date;

    /**
     * @ORM\Column(type="datetime")
     */
    private $date_added;

    /**
     * @ORM\Column(type="string", length=2000, nullable=true)
     */
    private $details;

    /**
     * @ORM\Column(type="string", length=20, nullable=true)
     * @Assert\Regex(
     *     pattern="/^\d+$/",
     *     match=true,
     *     message="Invalid Ride ID"
     * )
     */
    private $ride_id;

    /**
     * @ORM\Column(type="boolean")
     */
    private $club_ride;

    /**
     * @ORM\Column(type="string", length=20, nullable=true)
     */
    private $source;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getKm()
    {
        return $this->km;
    }

    public function setKm($km): self
    {
        $this->km = $km;

        return $this;
    }

    public function getAverageSpeed()
    {
        return $this->average_speed;
    }

    public function setAverageSpeed($average_speed): self
    {
        $this->average_speed = $average_speed;

        return $this;
    }

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(\DateTimeInterface $date): self
    {
        $this->date = $date;

        return $this;
    }

    public function getDateAdded(): ?\DateTimeInterface
    {
        return $this->date_added;
    }

    /**
     * @ORM\PrePersist
     */
    public function setDateAddedValue()
    {
        $this->date_added = new \DateTime();
    }

    public function setDateAdded(\DateTimeInterface $date_added): self
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
    
    public function __toString()
    {
        return (string)$this->getId();
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