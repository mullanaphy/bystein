<?php

    /**
     * PHY\Variable
     * https://github.com/mullanaphy/variable
     *
     * LICENSE
     * DO WHAT THE FUCK YOU WANT TO PUBLIC LICENSE
     * http://www.wtfpl.net/
     */

    namespace PHY\Variable;

    /**
     * Encapsulated Int class.
     *
     * @package PHY\Variable\Int
     * @category PHY\Variable\Int
     * @license http://www.wtfpl.net/
     * @author John Mullanaphy <john@jo.mu>
     */
    class Int extends \PHY\Variable\AVar
    {

        const MINUTE = 60;
        const HOUR = 3600;
        const DAY = 86400;
        const MONTH = 2629744;
        const YEAR = 31556926;

        protected $types = [
            'int'
        ];
        protected $original = 0;
        protected $current = 0;

        /**
         * Set the default value and typecase it to an int.
         * 
         * @param numeric $value
         * @return \PHY\Variable\Int
         */
        public function set($value)
        {
            return parent::set((int)$value);
        }

        /**
         * Convert an int as seconds into Minutes.
         *
         * @return string
         */
        public function toMinutes()
        {
            $time = $this->get();
            if (!$time) {
                $time = '00:00';
            } else {
                $time = sprintf('%02d:%02d', (int)(floor($time / 60)), (int)($time % 60));
            }
            return $this;
        }

    }
