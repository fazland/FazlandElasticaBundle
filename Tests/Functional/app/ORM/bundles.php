<?php

use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Fazland\ElasticaBundle\FazlandElasticaBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use JMS\SerializerBundle\JMSSerializerBundle;

return array(
    new FrameworkBundle(),
    new FazlandElasticaBundle(),
    new DoctrineBundle(),
    new JMSSerializerBundle(),
);
