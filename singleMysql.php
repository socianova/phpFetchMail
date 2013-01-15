<?php

/**
 * Description of mysql
 *
 * Date - $Date: 2013-01-15 13:00:00 +0200 (lun., 15 janvier 2013) $
 */

 /*
 * @copyright  GPL License 2012 - Mehboub Sophien - Badreddine Zeghiche - Rudy Delépine sociaNova (http://www.socianova.com)
 * @license    http://www.gnu.org/licenses/gpl-3.0.fr.html  GPL License
 * @version 1.0
 */

abstract class mysql
{
protected static $link = null;
protected static $server = 'localhost';
protected static $user = 'root';
protected static $password = '';

protected static $instance = null;


public static function getInstance() {
        if (self::$link === null) {
            self::$link = mysql_connect(self::$server, self::$user, self::$password);
            if (self::$link == false) {
                die('Link to database cannot be established.');
            } else {
                return self::$link;
            }
        } else {
            return self::$link;
        }
    }
}

?>
