<?php
/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * Alveole implementation : © Cédric Leclinche <cedric@devoreve.com>
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 * alveole.view.php
 *
 * This is your "view" file.
 *
 * The method "build_page" below is called each time the game interface is displayed to a player, ie:
 * _ when the game starts
 * _ when a player refreshes the game page (F5)
 *
 * "build_page" method allows you to dynamically modify the HTML generated for the game interface. In
 * particular, you can set here the values of variables elements defined in alveole_alveole.tpl (elements
 * like {MY_VARIABLE_ELEMENT}), and insert HTML block elements (also defined in your HTML template file)
 *
 * Note: if the HTML of your game interface is always the same, you don't have to place anything here.
 *
 */

require_once(APP_BASE_PATH . "view/common/game.view.php");

class view_alveole_alveole extends game_view
{
    function getGameName()
    {
        return "alveole";
    }

    function build_page($viewArgs)
    {

        // Get players & players number
        $players = $this->game->loadPlayersBasicInfos();
        $players_nbr = count($players);
        
        $this->tpl['DISPLAY_LAST_MOVE'] = _("Display last moves");
        $this->tpl['DISPLAY_HIDE_GRID'] = _("Display/Hide grid");

        /*********** Place your code below:  ************/

        $this->page->begin_block("alveole_alveole", "box");

        for ($x = 0; $x <= 16; $x += 2) {
            for ($y = 0; $y <= 16; $y++) {
                if ($this->game->isValid($x, $y)) {
                    $this->page->insert_block("box", array(
                        'bx' => $x,
                        'by' => $y,
                        'left' => $x * (46 / 2),
                        'top' => $y * 26.5,
                        'position' => $this->game->getBoxPosition($x, $y)
                    ));
                }
            }
        }


        /*

        // Examples: set the value of some element defined in your tpl file like this: {MY_VARIABLE_ELEMENT}

        // Display a specific number / string
        $this->tpl['MY_VARIABLE_ELEMENT'] = $number_to_display;

        // Display a string to be translated in all languages:
        $this->tpl['MY_VARIABLE_ELEMENT'] = self::_("A string to be translated");

        // Display some HTML content of your own:
        $this->tpl['MY_VARIABLE_ELEMENT'] = self::raw( $some_html_code );

        */

        /*

        // Example: display a specific HTML block for each player in this game.
        // (note: the block is defined in your .tpl file like this:
        //      <!-- BEGIN myblock -->
        //          ... my HTML code ...
        //      <!-- END myblock -->


        $this->page->begin_block( "alveole_alveole", "myblock" );
        foreach( $players as $player )
        {
            $this->page->insert_block( "myblock", array(
                                                    "PLAYER_NAME" => $player['player_name'],
                                                    "SOME_VARIABLE" => $some_value
                                                    ...
                                                     ) );
        }

        */


        /*********** Do not change anything below this line  ************/
    }
}
  

