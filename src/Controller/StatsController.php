<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use App\Statistic\StatisticService;

/**
 * App\Controller\StatsController
 *
 * @Route("/stats")
 */
class StatsController extends Controller
{
    /**
     * @Route(name="app.stats.index", path="/")
     */
    public function indexAction(StatisticService $statistics)
    {
        return $this->render('/stats/index.html.twig', [
            'statistics' => $statistics->getPersonStatistics(),
        ]);
    }
}
