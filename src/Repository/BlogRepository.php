<?php

namespace App\Repository;

use App\Entity\Blog;
use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Blog>
 *
 * @method Blog|null find($id, $lockMode = null, $lockVersion = null)
 * @method Blog|null findOneBy(array $criteria, array $orderBy = null)
 * @method Blog[]    findAll()
 * @method Blog[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class BlogRepository extends ServiceEntityRepository
{
    private $manager;

    public function __construct(ManagerRegistry $registry, EntityManagerInterface $manager)
    {
        parent::__construct($registry, Blog::class);
        $this->manager = $manager;
    }

    public function saveBlog($title, $body, $authorId): Blog
    {
        $blog = new Blog();

        $blog
            ->setTitle($title)
            ->setBody($body)
            ->setAuthorId($authorId)
            ->setCreatedAt(new DateTimeImmutable());

        $this->manager->persist($blog);
        $this->manager->flush();

        return $blog;
    }

    public function updateBlog($id, $title, $body): Blog
    {
        $blog = BlogRepository::find($id);

        $blog->setTitle($title)->setBody($body);

        $this->manager->persist($blog);
        $this->manager->flush();

        return $blog;
    }

    public function deleteBlog($id)
    {
        $blog = BlogRepository::find($id);

        $blog->setDeletedAt(new DateTimeImmutable());

        $this->manager->persist($blog);
        $this->manager->flush();

        return true;
    }
}
