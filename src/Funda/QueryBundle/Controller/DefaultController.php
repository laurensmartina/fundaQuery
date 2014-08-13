<?php

namespace Funda\QueryBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{
    public function indexAction()
    {
        return $this->render('FundaQueryBundle:Default:index.html.twig');
    }
}
