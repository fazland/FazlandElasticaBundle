<?php

use Fazland\ElasticaBundle\FazlandElasticaBundle;
use Knp\Bundle\PaginatorBundle\KnpPaginatorBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\TwigBundle\TwigBundle;

return array(
    new FrameworkBundle(),
    new FazlandElasticaBundle(),
    new KnpPaginatorBundle(),
    new TwigBundle(),
);
