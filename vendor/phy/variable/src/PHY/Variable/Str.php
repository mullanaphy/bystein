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
     * Encapsulated String class.
     *
     * @package PHY\Variable\Str
     * @category PHY\Variable
     * @license http://www.wtfpl.net/
     * @author John Mullanaphy <john@jo.mu>
     */
    class Str extends \PHY\Variable\AVar
    {

        protected $types = [
                'string'
            ],
            $original = '',
            $current = '',
            $type = null,
            $chain = null,
            $chaining = false;

        /**
         * Set the default value. Must be a scalar, or an object with
         * __toString since the value will be typecasted to a string.
         * 
         * @param scalar $value
         * @return \PHY\Variable\Str
         */
        public function set($value)
        {
            return parent::set((string)$value);
        }

        /**
         * Replace $find with $replace.
         *
         * @param string|array $find
         * @param string $replace
         * @return \PHY\Variable\Str
         */
        public function replace($find, $replace)
        {
            $string = $this->get();
            $value = str_replace($find, $replace, $string);
            $this->update($value);
            return $this;
        }

        /**
         * Repeat this string by $multipler.
         *
         * @param int $multiplier
         * @return \PHY\Variable\Str
         */
        public function repeat($multiplier)
        {
            $string = $this->get();
            $value = str_repeat($string, $multiplier);
            $this->update($value);
            return $this;
        }

        /**
         * Pad a string.
         *
         * @param int $pad_length
         * @param string $pad_string
         * @param int $pad_type
         * @return \PHY\Variable\Str
         */
        public function pad($pad_length, $pad_string = ' ', $pad_type = STR_PAD_RIGHT)
        {
            $string = $this->get();
            $value = str_pad($string, $pad_length, $pad_string, $pad_type);
            $this->update($value);
            return $this;
        }

        /**
         * Performs the ROT13 encoding on the str argument and returns the
         * resulting string.
         *
         * The ROT13 encoding simply shifts every letter by 13 places in the
         * alphabet while leaving non-alpha characters untouched. Encoding and
         * decoding are done by the same function, passing an encoded string as
         * argument will return the original version.
         *
         * Obviously this is not for any actual encryption...
         *
         * @return \PHY\Variable\Str
         */
        public function rot13()
        {
            $string = $this->get();
            $value = str_rot13($string);
            $this->update($value);
            return $this;
        }

        /**
         * Randomly shuffles the string.
         *
         * @return \PHY\Variable\Str
         */
        public function shuffle()
        {
            $string = $this->get();
            $value = str_shuffle($string);
            $this->update($value);
            return $this;
        }

        /**
         * Split a string at a given interval.
         *
         * @param int $split_length
         * @return array
         */
        public function split($split_length = 1)
        {
            $string = $this->get();
            return str_split($string, $split_length);
        }

        /**
         * Return all the words in this string. Array keys are the numeric
         * position of the words in the current string.
         *
         * @param string $charlist
         * @return array
         */
        public function words($charlist = '')
        {
            $string = $this->get();
            return str_word_count($string, 2, $charlist);
        }

        /**
         * {@inheritDoc}
         */
        public function toArr()
        {
            return new \PHY\Variable\Arr($this->split());
        }

        /**
         * Get the possessive version of a string.
         *
         * @return \PHY\Variable\Str
         */
        public function possessive()
        {
            $string = $this->get();
            $value = $string."'".(
                's' !== substr($string, -1)
                    ? 's'
                    : null
                );
            $this->update($value);
            return $this;
        }

        /**
         * Return an HTML version of the string.
         *
         * @return string
         */
        public function toHtml()
        {
            $string = preg_replace([
                '#((?:https?|ftp)://\S+[[:alnum:]]/?)#si',
                '#((?<!//)(www\.\S+[[:alnum:]]/?))#si'
                ], [
                '<a href="$1" rel="nofollow" target="_blank">$1</a>',
                '<a href="http://$1" rel="nofollow" target="_blank">$1</a>'
                ], $this->get());
            $string = nl2br($string);
            return $string;
        }

        /**
         * Shorten a string.
         *
         * @param int $length
         * @param bool $truncate
         * @return string
         */
        public function toShorten($length = 32, $truncate = false)
        {
            $string = $this->get();

            if (is_bool($length)) {
                $truncate = $length;
                $length = 32;
            } else {
                $length = (int)$length;
            }

            if (strlen($string) > $length) {
                $length - 4;
                $string = substr($string, 0, $length);
                if (!$truncate) {
                    $string = explode(' ', $string);
                    $size = count($string) - 1;
                    if ($size > -1) {
                        $final = '';
                        for ($i = 0; $i < $size; ++$i) {
                            $final .= $string[$i].' ';
                        }
                        $string = $final;
                    }
                }
                $string = trim($string).'...';
            }

            return $string;
        }

        public function toUrl()
        {
            $string = $this->get();
            if (!$string) {
                return '';
            } else {
                $string = (string)$string;
            }
            return trim(preg_replace('/[-]+/', '-', str_replace(' ', '-', preg_replace('/[^a-z0-9- ]/i', '', html_entity_decode(strtolower(trim((new static($string))->toShorten(64))), ENT_QUOTES)))), '-');
        }

        /**
         * Get a camelCase version of this string.
         *
         * @return string
         */
        public function toCamelCase()
        {
            $string = $this->get();
            return preg_replace('#[_| ]([a-z])#', "strtoupper($1)", $string);
        }

        /**
         * Get an underscored version of this string.
         *
         * @param string $underscore
         * @return string
         */
        public function toUnderscore($underscore = '_')
        {
            $string = $this->get();
            return strtolower(preg_replace('#(.)([A-Z])#', "$1".$underscore."$2", $string));
        }

        /**
         * Generate a random string based on $keys with a length of $count.
         *
         * @param int $count
         * @param string $keys
         * @return string
         */
        public static function random($count = 8, $keys = 'abcdefghijklmnopqrstuvwxyz1234567890ABCDEFGHIJKLMNOPQRSTWXYZ')
        {
            mt_srand((double)microtime() * 1000000);
            $random = [];
            $length = strlen($keys) - 1;
            for ($i = 0; $i < $count; ++$i) {
                $random[] = $keys[mt_rand(0, $length)];
            }
            return new static(implode('', $random));
        }

    }
