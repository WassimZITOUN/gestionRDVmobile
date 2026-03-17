<?php

namespace App\Tests;

use App\Entity\Patient;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class RendezVousControllerTest extends WebTestCase
{
    public function testNewRedirectsWhenNotAuthenticated(): void
    {
        $client = static::createClient();
        $client->request('GET', '/rendez/vous/new');

        $this->assertTrue($client->getResponse()->isRedirect());
        $this->assertStringContainsString('/login', $client->getResponse()->headers->get('Location'));
    }

    public function testPatientCanAccessNewAfterLogin(): void
    {
        $client = static::createClient();
        $container = static::getContainer();

        // create a patient user object (no DB persistence needed)
        $patient = new Patient();
        $patient->setEmail('testpatient@example.com');
        $patient->setPrenom('Test');
        $patient->setNom('Patient');
        $patient->setRoles(['ROLE_PATIENT']);

        // use the test client helper to simulate login
        $client->loginUser($patient);

        // access new rendez-vous page
        $client->request('GET', '/rendez/vous/new');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
    }
}
