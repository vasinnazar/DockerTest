<?php

namespace App\Entity;

use App\Repository\UserRepository;
use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\VarDumper\Cloner\Data;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass=UserRepository::class)
 * @ORM\Table(name="`user`")
 */
class User
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
//    /**
//     * @ORM\GeneratedValue
//     * @ORM\Column(name="id", type="integer", nullable=false)
//     */
    private $id;

//    /**
//     * @ORM\Email
//     * @ORM\GeneratedValue
//     * @ORM\Column(type="string")
//     * @Assert\NotBlank
//     */
    /**
     * @ORM\Column(name="email", type="string", nullable=false)
     */
//    #[ORM\Email]
//    #[ORM\GeneratedValue]
//    #[ORM\Column(type:"string")]
//    #[Assert\NotBlank]
    private $email;

    /**
     * @ORM\Column(name="name", type="string", nullable=false)
     */
//    #[ORM\Name]
//    #[ORM\Column(type:"string")]
//    #[Assert\NotBlank]
    private $name;

    /**
     * @ORM\Column(name="age", type="integer", nullable=false)
     */
//    #[ORM\Age]
//    #[ORM\Column(type:"integer")]
//    #[Assert\NotBlank]
    private $age;

    /**
     * @ORM\Column(name="sex", type="string", nullable=false)
     */
//    #[ORM\Sex]
//    #[ORM\Column(type:"string")]
//    #[Assert\NotBlank]
    private $sex;

    /**
     * @ORM\Column(name="birthday", type="datetime", nullable=false)
     */
//    #[ORM\Birthday]
//    #[ORM\Column(type:"datetime")]
//    #[Assert\NotBlank]
    private $birthday;

    /**
     * @ORM\Column(name="phone", type="string", nullable=false)
     */
//    #[ORM\Phone]
//    #[ORM\Column(type:"string")]
//    #[Assert\NotBlank]
    private $phone;

//    #[ORM\Birthday]
//    #[ORM\Column(type:"datetime")]
//    #[Assert\NotBlank]
    /**
     * @ORM\Column(name="created_at", type="datetime", nullable=false)
     */
    private $сreatedAt;

//    #[ORM\Birthday]
//    #[ORM\Column(type:"datetime")]
//    #[Assert\NotBlank]
    /**
     * @ORM\Column(name="updated_at", type="datetime", nullable=false)
     */
    private $updatedAt;

    public function getCreatedAt(): ?DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?DateTimeInterface
    {
        return $this->updatedAt;
    }



    public function getEmail()
    {
        return $this->email;
    }

    public function setEmail(?string $email)
    {
        return $this->email = $email;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setName(?string $name)
    {
        return $this->name = $name;
    }

    public function getAge()
    {
        return $this->age;
    }

    public function setAge(?int $age)
    {
        return $this->age = $age;
    }

    public function getSex()
    {
        return $this->sex;
    }

    public function setSex(?string $sex)
    {
        return $this->sex = $sex;
    }

    public function getBirthday()
    {
        return $this->birthday;
    }

    public function setBirthday($birthday)
    {
        return $this->birthday = $birthday;
    }

    public function getPhone()
    {
        return $this->phone;
    }

    public function setPhone(?string $phone)
    {
        return $this->phone = $phone;
    }
}
