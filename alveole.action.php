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
 * alveole.action.php
 *
 * Alveole main action entry point
 *
 *
 * In this file, you are describing all the methods that can be called from your
 * user interface logic (javascript).
 *
 * If you define a method "myAction" here, then you can call it from your javascript code with:
 * this.ajaxcall( "/alveole/alveole/myAction.html", ...)
 *
 */
class action_alveole extends APP_GameAction
{
    // Constructor: please do not modify
    public function __default()
    {
        if (self::isArg('notifwindow')) {
            $this->view = "common_notifwindow";
            $this->viewArgs['table'] = self::getArg("table", AT_posint, true);
        } else {
            $this->view = "alveole_alveole";
            self::trace("Complete reinitialization of board game");
        }
    }

    public function playToken()
    {
        self::setAjaxMode();

        $x = self::getArg('x', AT_posint, true);
        $y = self::getArg('y', AT_posint, true);
        $id = self::getArg('id', AT_posint, true);

        $this->game->playToken($x, $y, $id);

        self::ajaxResponse();
    }

    public function offerDraw()
    {
        self::setAjaxMode();
        $this->game->offerDraw();
        self::ajaxResponse();
    }

    public function acceptDraw()
    {
        self::setAjaxMode();
        $this->game->endGame();
        self::ajaxResponse();
    }

    public function denyDraw()
    {
        self::setAjaxMode();
        $this->game->continueGame();
        self::ajaxResponse();
    }

    public function endGame()
    {
        self::setAjaxMode();
        $this->game->endGame();
        self::ajaxResponse();
    }

    // TODO: defines your action entry points there


    /*
    
    Example:
  	
    public function myAction()
    {
        self::setAjaxMode();     

        // Retrieve arguments
        // Note: these arguments correspond to what has been sent through the javascript "ajaxcall" method
        $arg1 = self::getArg( "myArgument1", AT_posint, true );
        $arg2 = self::getArg( "myArgument2", AT_posint, true );

        // Then, call the appropriate method in your game logic, like "playCard" or "myAction"
        $this->game->myAction( $arg1, $arg2 );

        self::ajaxResponse( );
    }
    
    */

}
  

