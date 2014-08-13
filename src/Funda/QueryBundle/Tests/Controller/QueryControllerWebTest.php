<?php

namespace Funda\QueryBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class QueryControllerWebTest extends WebTestCase
{
    public function testIndex()
    {
        $client = static::createClient();

        // Fetch index.
        $crawler = $client->request('GET', '/');

        // Check welcome.
        $this->assertCount(1, $crawler->filter('h1:contains("Welcome")'));

        // Check buttons.
        $ul = $crawler->filter('div.header ul');
        $this->assertCount(3, $ul->filter('li'));

        $ul->next();
        $this->assertCount(1, $crawler->filter('div.header ul li.active:contains("Home")'));
    }
}
