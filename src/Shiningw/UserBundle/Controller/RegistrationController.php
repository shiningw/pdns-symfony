<?php

namespace Shiningw\UserBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use FOS\UserBundle\Controller\RegistrationController as BaseRegistrationController;

/**
 * Controller managing the registration.
 *
 * @author Thibault Duplessis <thibault.duplessis@gmail.com>
 * @author Christophe Coevoet <stof@notk.org>
 */
class RegistrationController extends BaseRegistrationController {

    /**
     * @param Request $request
     *
     * @return Response
     */
    public function registerAction(Request $request) {
        if ($this->container->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_REMEMBERED')) {

            // IS_AUTHENTICATED_FULLY also implies IS_AUTHENTICATED_REMEMBERED, but IS_AUTHENTICATED_ANONYMOUSLY doesn't
            return new RedirectResponse($this->container->get('router')->generate('pdns.zonelist', array()));
            // of course you don't have to use the router to generate a route if you want to hard code a route
        }

        return parent::registerAction($request);
    }

}
