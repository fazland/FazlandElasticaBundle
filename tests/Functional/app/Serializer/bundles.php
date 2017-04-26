<?php

use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Fazland\ElasticaBundle\FazlandElasticaBundle;
use JMS\SerializerBundle\JMSSerializerBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;

return [
    new FrameworkBundle(),
    new FazlandElasticaBundle(),
    new DoctrineBundle(),
    new JMSSerializerBundle(),
];
