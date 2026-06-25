<?php

namespace PHPSTORM_META {
    // Подсказка для Doctrine репозиториев
    override(\Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository::findOneBy(0), map([
        '' => '@|null',
    ]));
    
    override(\Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository::find(0), map([
        '' => '@|null',
    ]));

    override(\Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository::findAll(0), map([
        '' => '[]',
    ]));

    override(\Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository::findBy(0), map([
        '' => '[]',
    ]));
    
    override(\Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository::createQueryBuilder(0), map([
        '' => \Doctrine\ORM\QueryBuilder::class,
    ]));
    
    override(\Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository::getEntityManager(0), map([
        '' => \Doctrine\ORM\EntityManagerInterface::class,
    ]));
}