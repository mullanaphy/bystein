<?php

    /**
     * By Stein
     *
     * LICENSE
     *
     * This source file is subject to the Open Software License (OSL 3.0)
     * that is bundled with this package in the file LICENSE.txt.
     * It is also available through the world-wide-web at this URL:
     * http://opensource.org/licenses/osl-3.0.php
     * If you did not receive a copy of the license and are unable to
     * obtain it through the world-wide-web, please send an email
     * to license@phyneapple.com so we can send you a copy immediately.
     *
     */

    namespace PHY\Controller;

    /**
     * About page.
     *
     * @package PHY\Controller\About
     * @category PHY\Stein
     * @copyright Copyright (c) 2014 Chris Stein (http://www.bystein.com/)
     * @license http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
     * @author John Mullanaphy <john@jo.mu>
     */
    class About extends AController
    {

        /**
         * GET /about
         */
        public function index_get()
        {
            $layout = $this->getLayout();
            $head = $layout->block('head');
            $head->setVariable('title', 'About');
            $head->setVariable('description', 'Professor Briggs and crew are here to help you get started!');
        }

    }
