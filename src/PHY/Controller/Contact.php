<?php

    /**
     * Phyneapple!
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

    use PHY\Database\Mysqli\Manager;
    use PHY\Mailer\Mailgun as Mailer;
    use PHY\Model\Config as ConfigModel;
    use PHY\View\Block;
    use PHY\View\Layout;

    /**
     * Contact page.
     *
     * @package PHY\Controller\Contact
     * @category PHY\Phyneapple
     * @copyright Copyright (c) 2013 Phyneapple! (http://www.phyneapple.com/)
     * @license http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
     * @author John Mullanaphy <john@jo.mu>
     */
    class Contact extends AController
    {

        private $email = null;

        /**
         * GET /contact
         */
        public function index_get()
        {
            $layout = $this->getLayout();
            $app = $layout->getController()->getApp();
            /** @var \PHY\Database\IDatabase $database */
            $database = $app->get('database');
            /** @var Manager $manager */
            $manager = $database->getManager();

            $head = $layout->block('head');
            $head->setVariable('title', 'Contact');
            $head->setVariable('description', 'Chris Stein can be contacted at ' . $this->getEmail($manager)->value);
        }

        /**
         * POST /contact
         */
        public function index_post()
        {
            $layout = $this->getLayout();
            $fields = $this->getRequest()->get('contact', []);
            $success = false;
            $error = 'Something seems to have gone astray.';
            try {

                $app = $this->getLayout()->getController()->getApp();
                /** @var \PHY\Database\IDatabase $database */
                $database = $app->get('database');
                /** @var Manager $manager */
                $manager = $database->getManager();

                $success = mail($this->getEmail($manager)->value, 'NEW WEBSITE MESSAGE', 'Name: ' . $fields['Name'] . PHP_EOL . 'Email: ' . $fields['Email'] . PHP_EOL . PHP_EOL . $fields['Message'], 'From: ' . $email->value . "\r\n");
            } catch (\Exception $e) {
                $error = $e->getMessage();
            }
            if ($success) {
                $this->index_get();
                $content = $layout->block('content');
                $content->setChild('contact/message', [
                    'template' => 'generic/message/success.phtml',
                    'message' => 'Thank you for your submission, I should get back to you within 24 hours.'
                ]);
                foreach ($fields as $field => $value) {
                    $content->setVariable($field, $value);
                }
            } else {
                $content = $layout->block('content');
                $content->setChild('contact/message', [
                    'template' => 'generic/message/error.phtml',
                    'message' => $error,
                    ''
                ]);
            }
        }

        /**
         * Lets get the email value.
         *
         * @param Manager $manager
         * @return ConfigModel
         */
        private function getEmail(Manager $manager)
        {
            if ($this->email === null) {
                $this->email = $manager->load(['key' => 'email'], new ConfigModel);
            }
            return $this->email;
        }

    }
