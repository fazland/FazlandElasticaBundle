<?php

use Fazland\ElasticaBundle\FazlandElasticaBundle;
use Knp\Bundle\PaginatorBundle\KnpPaginatorBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;

return [
    new FrameworkBundle(),
    new FazlandElasticaBundle(),
    new KnpPaginatorBundle(),
];
