<?php

namespace Funda\QueryBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class QueryControllerTest extends WebTestCase
{
    public function testAmsterdam()
    {
        /* @var $classUnderTest \Funda\QueryBundle\Controller\QueryController */
        $classUnderTest = $this->getMock('\Funda\QueryBundle\Controller\QueryController', array('fetch', 'getPageCount'));
        $classUnderTest->setFundaAPIKey('somekey');

        $cache = $this->getMock('\Doctrine\Common\Cache\FilesystemCache', array('fetch', 'save'), array('cache'));
        $cache->expects($this->at(0))
            ->method('fetch')
            ->will($this->returnValue(false));

        $classUnderTest->expects($this->once())
            ->method('getPageCount')
            ->will($this->returnValue(3));

        $classUnderTest->expects($this->at(1))
            ->method('fetch')
            ->will($this->returnValue(json_decode(file_get_contents(__DIR__ . '/fundaResponse0.json'))));
        $classUnderTest->expects($this->at(2))
            ->method('fetch')
            ->will($this->returnValue(json_decode(file_get_contents(__DIR__ . '/fundaResponse1.json'))));
        $classUnderTest->expects($this->at(3))
            ->method('fetch')
            ->will($this->returnValue(json_decode(file_get_contents(__DIR__ . '/fundaResponse2.json'))));

        $classUnderTest->setCache($cache);
        $result = $classUnderTest->getDataResult('/amsterdam/tuin/');

        $expected = array(
            array('id' => 24755, 'name' => 'Amsterdam @ Home Makelaars', 'quantity' => 10),
            array('id' => 24079, 'name' => 'Makelaardij Van der Linden Amsterdam', 'quantity' => 8),
            array('id' => 24485, 'name' => 'Geldhof Makelaardij O.G.', 'quantity' => 6),
            array('id' => 24662, 'name' => 'VLIEG Makelaars Amsterdam OG', 'quantity' => 5),
            array('id' => 24739, 'name' => 'SOS Makelaars', 'quantity' => 5),
            array('id' => 24763, 'name' => 'RET Makelaars', 'quantity' => 5),
            array('id' => 24761, 'name' => 'Francis Helmig Makelaardij', 'quantity' => 4),
            array('id' => 24705, 'name' => 'Eefje Voogd Makelaardij', 'quantity' => 4),
            array('id' => 12285, 'name' => 'Makelaarsland', 'quantity' => 4),
            array('id' => 24607, 'name' => 'Kuijs Reinder Kakes Amsterdam', 'quantity' => 3)
        );

        $this->assertSame($expected, $result);
    }
}
