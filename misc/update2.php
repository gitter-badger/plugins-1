<?php
/**
 * update.php
 *
 * This script should be fired by a crontab
 * to update the database according to all
 * the url and checksums.
 */

require 'api/vendor/autoload.php';


use \Illuminate\Database\Capsule\Manager as DB;
use \API\Model\Plugin;
use \API\Model\PluginDescription;

class DatabaseUpdater {
    public function __construct() {
        // Connecting to MySQL
        \API\Core\DB::initCapsule();
    }

    public function verifyAndUpdatePlugins() {
        $plugins = Plugin::get();

        // Going to compare checksums
        // for each of these plugins
        foreach($plugins as $num => $plugin) {
            // Defaults not to update
            $update = false;
            // fetching via http
            $xml = file_get_contents($plugin->xml_url);
            $crc = md5($xml); // compute crc
            if ($plugin->xml_crc != $crc ||
                $plugin->name == NULL) {
                $update = true; // if we got
                // missing name or changing
                // crc, then we're going to
                // update that one
            }
            else continue;

            if ($update) {
                // loading XML OO-style with simplemxl
                $xml = simplexml_load_string($xml);
                $this->updatePlugin($plugin, $xml, $crc);
            }
        }
    }

    public function updatePlugin($plugin, $xml, $new_crc) {
        // Updating basic infos
        $plugin->logo_url = $xml->logo;
        $plugin->name = $xml->name;
        $plugin->key = $xml->key;
        $plugin->homepage_url = $xml->homepage;
        $plugin->download_url = $xml->download;
        $plugin->issues_url = $xml->issues;
        $plugin->readme_url  = $xml->readme;
        $plugin->license = $xml->license;

        // reading descriptions,
        // mapping type=>lang relation to lang=>type
        $descriptions = [];
        foreach ($xml->description->children() as $type => $descs) {
            if (in_array($type, ['short','long'])) {
                foreach($descs->children() as $_lang => $content) {
                    $descriptions[$_lang][$type] = (string)$content;             
                }
            }
        }

        // Delete current descriptions
        //$plugin->descriptions()->delete();
        // Refreshing them
        foreach($descriptions as $lang => $_type) {
            $description = new PluginDescription;
            $description->lang = $lang;
            foreach($_type as $type => $html) {
                $description[$type.'_description'] = $html;
            }
            //$plugin->descriptions()->save($description);
        }

        // Delete current authors
        //$plugin->authors()->delete();

        var_dump('updating ' . $plugin->id);
        $clean_authors = [];
        foreach($xml->authors->children() as $author) {
            $_clean_authors = $this->fixParseAuthors((string)$author);
            foreach ($_clean_authors as $author) {
                $clean_authors[] = $author;
            }
        }

        var_dump($clean_authors);


        // Now going to think about making
        // the datamodel evolve
        // and also handle corruption in some
        // xml files.
        // for the datamodel, i'd need a
        // join table between authors and plugins

        $plugin->xml_crc = $new_crc;
    }

    /*
     * fixParseAuthors()
     *
     * This function is very specific,
     * it aims to provide a fix to current
     * state of things in xml files.
     *
     * Currently, some authors are duplicates,
     * and spelled differently depending on
     * plugins, this functions aims to ensure
     * correct detection of EACH author.
     *
     * This function shouldn't be here and might
     * dissapear someday.
     */
    private $fpa_separators = [',', '/'];
    private $fpa_duplicates = [
        [
            "names" => ['Xavier Caillaud / Infotel',
                        'Xavier CAILLAUD'],
            "ends"  => 'Xavier Caillaud'
        ],
        [
            "names" => ['Nelly LASSON',
                        'Nelly MAHU-LASSON'],
            "ends"  => 'Nelly Mahu-Lasson'
        ],
        [
            "names" => ['David DURIEUX'],
            "ends"  => 'David Durieux'
        ],
        [
            "names" => ['Olivier DURGEAU'],
            "ends"  => 'Olivier Durgeau'
        ],
        [
            "names" => ['Yohan BOUSSION'],
            "ends"  => 'Yohan Boussion'
        ],
        [
            "names" => ['Philippe GODOT'],
            "ends"  => 'Philippe Godot'
        ],
        [
            "names" => ['Cyril ZORMAN'],
            "ends"  => 'Cyril Zorman'
        ],
        [
            "names" => ['Maxime BONILLO'],
            "ends"  => 'Maxime Bonillo'
        ],
        [
            "names" => ['Philippe THOIREY'],
            "ends"  => 'Philippe Thoirey'
        ]
    ];
    public function fixParseAuthors($author_string) {
        $detectedAuthors = [];
        // detecting known duplicates
        foreach($this->fpa_duplicates as $known_duplicate) {
            foreach ($known_duplicate['names'] as $known_name) {
                    if (preg_match('/'.preg_quote($known_name, '/').'/', $author_string)) {
                        $author_string = preg_replace('/'.preg_quote($known_name, '/').'/',
                                                      $known_duplicate['ends'],
                                                      $author_string);
                    }
            }
        }

        // detecting inline multiple authors
        foreach($this->fpa_separators as $separator) {
            $found_authors = explode($separator, $author_string);
            if (sizeof($found_authors) > 1) {
                foreach ($found_authors as $author) {
                    $detectedAuthors[] = trim($author);
                }
                break;
            }
        }

        if (sizeof($detectedAuthors) == 0) {
            return [trim($author_string)];
        } else {
            return $detectedAuthors;
        }
    }
}

$db_updater = new DatabaseUpdater;
$db_updater->verifyAndUpdatePlugins();