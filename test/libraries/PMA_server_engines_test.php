<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * tests for server_engines.lib.php
 *
 * @package PhpMyAdmin-test
 */

/*
 * Include to test.
 */
use PMA\libraries\StorageEngine;
use PMA\libraries\Theme;


require_once 'libraries/php-gettext/gettext.inc';
require_once 'libraries/url_generating.lib.php';
require_once 'libraries/server_engines.lib.php';


require_once 'libraries/database_interface.inc.php';

require_once 'libraries/sanitizing.lib.php';
require_once 'libraries/js_escape.lib.php';


/**
 * PMA_ServerEngines_Test class
 *
 * this class is for testing server_engines.lib.php functions
 *
 * @package PhpMyAdmin-test
 */
class PMA_ServerEngines_Test extends PHPUnit_Framework_TestCase
{
    /**
     * Prepares environment for the test.
     *
     * @return void
     */
    public function setUp()
    {
        //$_REQUEST
        $_REQUEST['log'] = "index1";
        $_REQUEST['pos'] = 3;

        //$GLOBALS
        $GLOBALS['server'] = 0;
        $GLOBALS['cfg']['MaxRows'] = 10;
        $GLOBALS['cfg']['ServerDefault'] = "server";
        $GLOBALS['cfg']['RememberSorting'] = true;
        $GLOBALS['cfg']['SQP'] = array();
        $GLOBALS['cfg']['MaxCharactersInDisplayedSQL'] = 1000;
        $GLOBALS['cfg']['ShowSQL'] = true;
        $GLOBALS['cfg']['TableNavigationLinksMode'] = 'icons';
        $GLOBALS['cfg']['LimitChars'] = 100;
        $GLOBALS['cfg']['DBG']['sql'] = false;

        $GLOBALS['table'] = "table";
        $GLOBALS['pmaThemeImage'] = 'image';

        //$_SESSION
        $_SESSION['PMA_Theme'] = Theme::load('./themes/pmahomme');
        $_SESSION['PMA_Theme'] = new Theme();
    }

    /**
     * Test for PMA_getHtmlForServerEngines for all engines
     *
     * @return void
     */
    public function testPMAGetPluginAndModuleInfo()
    {
        //test PMA_getHtmlForAllServerEngines
        $html = PMA_getHtmlForServerEngines();

        //validate 1: Item header
        $this->assertContains(
            '<th>Storage Engine</th>',
            $html
        );
        $this->assertContains(
            '<th>Description</th>',
            $html
        );

        //validate 2: FEDERATED
        $this->assertContains(
            '<td>Federated MySQL storage engine</td>',
            $html
        );
        $this->assertContains(
            'FEDERATED',
            $html
        );
        $this->assertContains(
            'server_engines.php?engine=FEDERATED',
            $html
        );

        //validate 3: dummy
        $this->assertContains(
            '<td>dummy comment</td>',
            $html
        );
        $this->assertContains(
            'dummy',
            $html
        );
        $this->assertContains(
            'server_engines.php?engine=dummy',
            $html
        );
    }

    /**
     * Test for PMA_getHtmlForServerEngines for specific engines "FEDERATED"
     *
     * @return void
     */
    public function testPMAGetPluginAndModuleInfoSpecific()
    {
        $_REQUEST['engine'] = "FEDERATED";
        //Mock DBI
        $dbi = $this->getMockBuilder('PMA\libraries\DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $GLOBALS['dbi'] = $dbi;

        //test PMA_getHtmlForAllServerEngines for specific engines "FEDERATED"
        $html = PMA_getHtmlForServerEngines();

        //validate 1: Engine header
        $this->assertContains(
            'FEDERATED',
            $html
        );
        $this->assertContains(
            'Federated MySQL storage engine',
            $html
        );
        $this->assertContains(
            'This MySQL server does not support the FEDERATED storage engine.',
            $html
        );
        $enginer_info = 'There is no detailed status information '
            . 'available for this storage engine';
        $this->assertContains(
            $enginer_info,
            $html
        );
    }

    /**
     * Test for PMA_getHtmlForSpecifiedServerEngines
     *
     * @return void
     */
    public function testPMAGetHtmlForSpecifiedServerEngines()
    {
        $_REQUEST['engine'] = "Pbxt";
        $_REQUEST['page'] = "page";

        //Mock DBI
        $dbi = $this->getMockBuilder('PMA\libraries\DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $GLOBALS['dbi'] = $dbi;

        //test PMA_getHtmlForSpecifiedServerEngines
        $html = PMA_getHtmlForSpecifiedServerEngines();
        $engine_plugin = StorageEngine::getEngine($_REQUEST['engine']);

        //validate 1: Engine title
        $this->assertContains(
            htmlspecialchars($engine_plugin->getTitle()),
            $html
        );

        //validate 2: Engine Mysql Help Page
        $this->assertContains(
            PMA\libraries\Util::showMySQLDocu($engine_plugin->getMysqlHelpPage()),
            $html
        );

        //validate 3: Engine Comment
        $this->assertContains(
            htmlspecialchars($engine_plugin->getComment()),
            $html
        );

        //validate 4: Engine Info Pages
        $this->assertContains(
            __('Variables'),
            $html
        );
        $this->assertContains(
            PMA_URL_getCommon(
                array('engine' => $_REQUEST['engine'], 'page' => "Documentation")
            ),
            $html
        );

        //validate 5: other items
        $this->assertContains(
            PMA_URL_getCommon(array('engine' => $_REQUEST['engine'])),
            $html
        );
        $this->assertContains(
            $engine_plugin->getSupportInformationMessage(),
            $html
        );
        $this->assertContains(
            $engine_plugin->getHtmlVariables(),
            $html
        );
    }

    /**
     * Test for StorageEngine::getEngine
     *
     * @param string $expectedClass Class that should be selected
     * @param string $engineName    Engine name
     *
     * @return void
     *
     * @dataProvider providerGetEngine
     */
    public function testGetEngine($expectedClass, $engineName)
    {
        $this->assertInstanceOf(
            $expectedClass, StorageEngine::getEngine($engineName)
        );
    }

    /**
     * Provider for test_getEngine
     *
     * @return array
     */
    public function providerGetEngine()
    {
        return array(
            array('PMA\libraries\StorageEngine', 'unknown engine'),
            array('PMA\libraries\engines\Bdb', 'Bdb'),
            array('PMA\libraries\engines\Berkeleydb', 'Berkeleydb'),
            array('PMA\libraries\engines\Binlog', 'Binlog'),
            array('PMA\libraries\engines\Innobase', 'Innobase'),
            array('PMA\libraries\engines\Innodb', 'Innodb'),
            array('PMA\libraries\engines\Memory', 'Memory'),
            array('PMA\libraries\engines\Merge', 'Merge'),
            array('PMA\libraries\engines\Mrg_Myisam', 'Mrg_Myisam'),
            array('PMA\libraries\engines\Myisam', 'Myisam'),
            array('PMA\libraries\engines\Ndbcluster', 'Ndbcluster'),
            array('PMA\libraries\engines\Pbxt', 'Pbxt'),
            array('PMA\libraries\engines\Performance_Schema', 'Performance_Schema'),
        );
    }
}
