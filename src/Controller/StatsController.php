<?php

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use AppBundle\Statistic\StatisticService;

/**
 * AppBundle\Controller\StatsController
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
        return $this->render('AppBundle:Stats:index.html.twig', [
            'statistics' => $statistics->getPersonStatistics(),
        ]);
    }
}
