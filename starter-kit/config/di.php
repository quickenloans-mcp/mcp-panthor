<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use ExampleApplication\HelloWorldController;
use QL\Panthor\Twig\LazyTwig;

return function (ContainerConfigurator $container) {
    $s = $container->services();

    $s
        ('hello.page', HelloWorldController::class)
            ->arg('$template', ref('hello.twig'))

        ('hello.twig', LazyTwig::class)
            ->arg('$template', 'hello.twig')
            ->autowire()
    ;
};
