<?php

namespace SAS\IRAD\PersonInfoBundle\Service;

Class NameCase {

    /**
     * @array
     */
    private $name;
    private $first_name;
    private $middle_name;
    private $last_name;

    public function __construct(Array $array) {

        $this->name = $array;

        $this->first_name  = trim($array['first_name']);
        $this->last_name   = trim($array['last_name']);

        if ( !$this->first_name ) {
            throw new Exception('No "first_name" element in passed array.');
        }
        if ( !$this->last_name ) {
            throw new Exception('No "last_name" element in passed array.');
        }

    }


    public function fullName() {
        return trim($this->firstName() . ' ' . $this->middleName()) . ' ' . $this->lastName();
    }


    public function firstName() {
        $first_name = $this->upperCaseFirst($this->first_name);
        $first_name = $this->fixInitials($first_name);

        return $first_name;
    }


    public function middleName() {

        $middle_name = $this->middle_name;

        if ( !$middle_name ) {
            // nothing to do
            return;
        }

        $middle_name = $this->upperCaseFirst($middle_name);
        $middle_name = $this->fixInitials($middle_name);
        $middle_name = $this->fixMcNames($middle_name);

        return $middle_name;
    }

    public function lastName() {
        $last_name = $this->upperCaseFirst($this->last_name);
        $last_name = $this->fixSuffixes($last_name);
        $last_name = $this->fixMcNames($last_name);
        $last_name = $this->fixExceptions($last_name);
        return $last_name;
    }



    private function upperCaseFirst($arg) {
        $words = $this->splitWords($arg);
        foreach ( $words as $index => $word ) {
            $words[$index] = ucwords(strtolower($word));
        }
        return implode('', $words);
    }

    /**
     * Convert initials 'M' to 'M.' This should only apply to first and middle names
     */
    private function fixInitials($arg) {
        $words = $this->splitWords($arg);
        foreach ( $words as $index => $word ) {
            // do we have an intial that isn't already in "standard" form?
            if ( strlen($word) == 1 && preg_match('/^[a-zA-Z]$/', $word) && !preg_match("/^$word\.$/", $arg) ) {
                $words[$index] = $word . '.';
            }
        }
        return implode('', $words);
    }


    /**
     * Fix suffixes on last names
     */
    private function fixSuffixes($arg) {

        $words = $this->splitWords($arg);
        $suffixes = array('II', 'III', 'IV', 'VI', 'VII', 'VIII', 'IX', 'XI', 'XII', 'XIII', 'XIV');


        if ( count($words) == 1 ) {
            // only one word so no suffix
            return $arg;
        }

        foreach ( $words as $index => $word ) {

            // skip the first word
            if ( $index == 0 ) {
                continue;
            }

            // do we match one of our known suffixes?
            foreach ( $suffixes as $suffix ) {
                if ( $suffix == strtoupper($word) ) {
                    $words[$index] = $suffix;
                }
            }
        }
        return implode('', $words);
    }


    /**
     * Fix McNames on last names
     */
    private function fixMcNames($arg) {

        $words    = $this->splitWords($arg);
        $prefixes = array('Mc');

        foreach ( $words as $index => $word ) {

            // do we match one of our known prefixes?
            foreach ( $prefixes as $prefix ) {
                // skip very short names where this doesn't make sense
                if ( strlen($word) - strlen($prefix) < 3 ) {
                    continue;
                }
                // test for prefix match
                if ( preg_match("/^$prefix(.+)$/", $word, $match) ) {
                    $words[$index] = $prefix . ucwords($match[1]);
                }
            }
        }


        // limited cases where we match on prefix plus next letter
        $prefixes = array('MacB', 'MacD', 'MacF', 'MacG', 'MacL', 'MacM', 'MacN', 'MacP', 'MacQ', 'MacV', 'MacW');

        foreach ( $words as $index => $word ) {

            // do we match one of our known prefixes?
            foreach ( $prefixes as $prefix ) {
                // skip very short names where this doesn't make sense
                if ( strlen($word) - strlen($prefix) < 3 ) {
                    continue;
                }
                // test for prefix match
                if ( preg_match("/^$prefix(.+)$/i", $word, $match) ) {
                    $words[$index] = $prefix . $match[1];
                }
            }
        }


        return implode('', $words);
    }



    /**
     * Fix exceptions to capitalization on last names
     */
    private function fixExceptions($arg) {

        $words = $this->splitWords($arg);
        $exceptions = array('del');

        foreach ( $words as $index => $word ) {

            // do we match one of our known exemptions?
            foreach ( $exceptions as $exception ) {
                // test for exception match
                if ( strtolower($word) == $exception ) {
                    $words[$index] = $exception;
                }
            }
        }

        // combined name exceptions
        $name = implode('', $words);

        $exceptions = array('van der', 'van den', 'van de', 'de las', 'de los', 'de la', 'y', "dell'");

        // do we match one of our known exemptions?
        foreach ( $exceptions as $exception ) {
            // test for exception match
            if ( preg_match("/\b($exception)\b/i", $name, $match) ) {
                $name = str_replace($match[1], $exception, $name);
            }
        }


        return $name;
    }


    /**
     * Split a string on all boundaries to get all "words" in a name
     * @param string $arg
     * @return array
     */
    private function splitWords($arg) {
        $words = preg_split("/\b/", $arg);
        return $words;
    }
}