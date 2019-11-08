<?php

/*
 * This file is part of the FOSUserBundle package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Shiningw\UserBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use FOS\UserBundle\Controller\SecurityController as BaseSecurityController;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;


class SecurityController extends BaseSecurityController {

    private $tokenManager;

    public function __construct(CsrfTokenManagerInterface $tokenManager = null) {
        
        parent::__construct($tokenManager);
    }

    /**
     * @param Request $request
     *
     * @return Response
     * 
     */
    public function loginAction(Request $request) {
        if ($this->container->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_REMEMBERED')) {

            // IS_AUTHENTICATED_FULLY also implies IS_AUTHENTICATED_REMEMBERED, but IS_AUTHENTICATED_ANONYMOUSLY doesn't

            return new RedirectResponse($this->container->get('router')->generate('pdns.zonelist', array()));
            // of course you don't have to use the router to generate a route if you want to hard code a route
        }

        return parent::loginAction($request);
    }

}
