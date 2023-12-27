<?php

namespace App\Controller;

use App\Statistic\StatisticService;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * App\Controller\StatsController
 */
#[Route(path: '/stats')]
class StatsController extends AbstractController
{
    #[Route(name: 'app.stats.index', path: '/')]
    public function index(StatisticService $statistics)
    {
        return $this->render('stats/index.html.twig', [
            'statistics' => $statistics->getPersonStatistics(),
        ]);
    }
}
