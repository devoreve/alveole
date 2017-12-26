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
  * alveole.game.php
  *
  * This is the main file for your game logic.
  *
  * In this PHP file, you are going to defines the rules of the game.
  *
  */


require_once( APP_GAMEMODULE_PATH.'module/table/table.game.php' );


class Alveole extends Table
{
    const COLOR_RED = "ff0000";
    const COLOR_BLUE = "0000ff";
    const COLOR_GREEN = "008000";
    const COLOR_ORANGE = "ffa500";
    const TOKENS_REUNITED = 1;
    const TOKENS_ERASED = 2;

    function __construct()
    {

        // Your global variables labels:
        //  Here, you can assign labels to global variables you are using for this game.
        //  You can use any number of global variables with IDs between 10 and 99.
        //  If your game has options (variants), you also have to associate here a label to
        //  the corresponding ID in gameoptions.inc.php.
        // Note: afterwards, you can get/set the global variables with getGameStateValue/setGameStateInitialValue/setGameStateValue
        parent::__construct();
        self::initGameStateLabels(array(
            "game_over" => 10,
            "number_moves" => 11,
            "progression_active_player" => 12,
            "progression_inactive_player" => 13
        ));
    }

    protected function getGameName()
    {
        // Used for translations and stuff. Please do not modify.
        return "alveole";
    }

    /*
      setupNewGame:

      This method is called only once, when a new game is launched.
      In this method, you must setup the game according to the game rules, so that
      the game is ready to be played.
     */

    protected function setupNewGame($players, $options = array())
    {
        // Set the colors of the players with HTML color code
        // The default below is red/green/blue/orange/brown
        // The number of colors defined here must correspond to the maximum number of players allowed for the gams
        $default_colors = array("ff0000", "0000ff");

        // Create players
        // Note: if you added some extra field on "player" table in the database (dbmodel.sql), you can initialize it there.
        $sql = "INSERT INTO player (player_id, player_color, player_canal, player_name, player_avatar) VALUES ";
        $values = array();
        $tokens = array();
        foreach($players as $player_id => $player)
        {
            $color = array_shift($default_colors);
            $values[] = "('" . $player_id . "','$color','" . $player['player_canal'] . "','" . addslashes($player['player_name']) . "','" . addslashes($player['player_avatar']) . "')";

            if($color === self::COLOR_RED)
            {
                $tokens[] = '(' . $player_id . ', 0, 6)';
                $tokens[] = '(' . $player_id . ', 0, 12)';
                $tokens[] = '(' . $player_id . ', 2, 3)';
                $tokens[] = '(' . $player_id . ', 6, 15)';
                $tokens[] = '(' . $player_id . ', 8, 0)';
                $tokens[] = '(' . $player_id . ', 10, 15)';
                $tokens[] = '(' . $player_id . ', 14, 3)';
                $tokens[] = '(' . $player_id . ', 16, 6)';
                $tokens[] = '(' . $player_id . ', 16, 12)';
            }
            else if($color === self::COLOR_BLUE)
            {
                $tokens[] = '(' . $player_id . ', 0, 4)';
                $tokens[] = '(' . $player_id . ', 0, 10)';
                $tokens[] = '(' . $player_id . ', 2, 13)';
                $tokens[] = '(' . $player_id . ', 6, 1)';
                $tokens[] = '(' . $player_id . ', 8, 16)';
                $tokens[] = '(' . $player_id . ', 10, 1)';
                $tokens[] = '(' . $player_id . ', 14, 13)';
                $tokens[] = '(' . $player_id . ', 16, 4)';
                $tokens[] = '(' . $player_id . ', 16, 10)';
            }
        }
        $sql .= implode($values, ',');
        self::DbQuery($sql);
//        self::reattributeColorsBasedOnPreferences($players, array("ff0000", "008000", "0000ff", "ffa500"));
        self::reloadPlayersBasicInfos();

        $sql = "INSERT INTO token (player_no, token_x, token_y) VALUES ";
        $sql .= implode(', ', $tokens);
        self::DbQuery($sql);

        // Init global values with their initial values
        self::setGameStateInitialValue('game_over', 0);
        self::setGameStateInitialValue('number_moves', 0);
        self::setGameStateInitialValue('progression_active_player', 0);
        self::setGameStateInitialValue('progression_inactive_player', 0);

        // Init game statistics
        self::initStat('table', 'turns_number', 0);
        self::initStat('table', 'tokens_erased', 0);
        self::initStat('table', 'tokens_remained', 0);
        self::initStat('player', 'player_tokens_erased', 0);
        self::initStat('player', 'player_erase_tokens', 0);
        self::initStat('player', 'player_tokens_remained', 0);

        $this->activeNextPlayer();
    }

    /* s
      getAllDatas:

      Gather all informations about current game situation (visible by the current player).

      The method is called each time the game interface is displayed to a player, ie:
      _ when the game starts
      _ when a player refreshes the game page (F5)
     */

    protected function getAllDatas()
    {
        $result = array('players' => array());

        $current_player_id = self::getCurrentPlayerId();    // !! We must only return informations visible by this player !!

        $sql = "SELECT player_id id, player_score score FROM player ";
        $result['players'] = self::getCollectionFromDb($sql);

        $sql = "SELECT token_id id, player_no player, token_x x, token_y y FROM token";
        $result['tokens'] = self::getCollectionFromDb($sql);

        $sql = "SELECT move.*, player_no 
                FROM move
                INNER JOIN token ON token.token_id = move.token_id
                ORDER BY move_id DESC
                LIMIT 0, 2";

        $result['lastmoves'] = self::getCollectionFromDb($sql);

        $result['colors'] = array(self::COLOR_RED => 'red', self::COLOR_BLUE => 'blue');

        return $result;
    }

    /**
     * Compute and return the current game progression.
     * The number returned must be an integer beween 0 (=the game just started) and
     * 100 (= the game is finished or almost finished).

     * This method is called each time we are in a game state with the "updateGameProgression" property set to true
     * @example states.inc.php
     */
    function getGameProgression()
    {
        $progressionMoves = (int) self::getGameStateValue('number_moves');
        $progressionActivePlayer = (int) (self::getGameStateValue('progression_active_player')) / 100;
        $progressionInactivePlayer = (int) (self::getGameStateValue('progression_inactive_player')) / 100;
        $coeff = 100;

        if($progressionMoves < 20)
            return round((50 / 20) * $progressionMoves);
        else
        {
            $progression = $progressionMoves < 50 ? 50 : 70;
        }

        $coeff -= $progression;
        $progressionActivePlayer = round($progressionActivePlayer * $coeff);
        $progressionInactivePlayer = round($progressionInactivePlayer * $coeff);

        return max($progressionActivePlayer, $progressionInactivePlayer) + $progression;
    }

//////////////////////////////////////////////////////////////////////////////
//////////// Utility functions
////////////

    /**
     * Return possible moves of a token
     * @param Array $pos Array which contains token data like id token and id
     * player, x and y coords
     * @param Array $tokens Collection of boardgame tokens
     * @return Array Array of possible moves for the token
     */
    function getMoves(Array $tokens, Array $pos, $player)
    {
        $northToSouth = 0;
        $northwestToSoutheast = 0;
        $southwestToNortheast = 0;
        $blockToNorth = 9;
        $blockToSouth = 9;
        $blockToNortheast = 9;
        $blockToNorthwest = 9;
        $blockToSoutheast = 9;
        $blockToSouthwest = 9;
        $coordsSameColor = array();

        foreach($tokens as $token)
        {
            if($token['x'] == $pos['x'])
            {
                ++$northToSouth;
                if($player !== $token['player'])
                {
                    if($pos['y'] - $token['y'] > 0)
                    {
                        $blockToNorth = $blockToNorth > (($pos['y'] - $token['y']) / 2) ? (($pos['y'] - $token['y']) / 2) : $blockToNorth;
                    }
                    else
                    {
                        $blockToSouth = $blockToSouth > (($token['y'] - $pos['y']) / 2) ? (($token['y'] - $pos['y']) / 2) : $blockToSouth;
                    }
                }
            }

            if($player === $token['player'])
            {
                $coordsSameColor[] = array('x' => $token['x'], 'y' => $token['y']);
            }
            for($i = 1; $i < 9; $i++)
            {
                if($token['x'] == $pos['x'] + $i * 2 && $token['y'] == $pos['y'] + $i)
                {
                    ++$northwestToSoutheast;
                    if($token['player'] !== $player)
                    {
                        if($i < $blockToSoutheast)
                            $blockToSoutheast = $i;
                    }
                }
                if($token['x'] == $pos['x'] - $i * 2 && $token['y'] == $pos['y'] - $i)
                {
                    ++$northwestToSoutheast;
                    if($token['player'] !== $player)
                    {
                        if($i < $blockToNorthwest)
                            $blockToNorthwest = $i;
                    }
                }
                if($token['x'] == $pos['x'] - $i * 2 && $token['y'] == $pos['y'] + $i)
                {
                    ++$southwestToNortheast;
                    if($token['player'] !== $player)
                    {
                        if($i < $blockToSouthwest)
                            $blockToSouthwest = $i;
                    }
                }
                if($token['x'] == $pos['x'] + $i * 2 && $token['y'] == $pos['y'] - $i)
                {
                    ++$southwestToNortheast;
                    if($token['player'] !== $player)
                    {
                        if($i < $blockToNortheast)
                            $blockToNortheast = $i;
                    }
                }
            }
        }

        ++$northwestToSoutheast;
        ++$southwestToNortheast;

        $moves = array();

        if($blockToNorth - $northToSouth >= 0)
        {
            $newCoord = array('x' => $pos['x'], 'y' => ($pos['y'] - 2 * $northToSouth));
            if($newCoord['x'] >= 0 && $newCoord['y'] >= 0 && $newCoord['x'] <= 16 && $newCoord['y'] <= 16)
            {
                if($this->isValid($newCoord['x'], $newCoord['y']) && array_search($newCoord, $coordsSameColor) === false)
                    $moves[] = $newCoord;
            }
        }
        if($blockToSouth - $northToSouth >= 0)
        {
            $newCoord = array('x' => $pos['x'], 'y' => ($pos['y'] + 2 * $northToSouth));
            if($newCoord['x'] >= 0 && $newCoord['y'] >= 0 && $newCoord['x'] <= 16 && $newCoord['y'] <= 16)
            {
                if($this->isValid($newCoord['x'], $newCoord['y']) && array_search($newCoord, $coordsSameColor) === false)
                    $moves[] = $newCoord;
            }
        }
        if($blockToNortheast - $southwestToNortheast >= 0)
        {
            $newCoord = array('x' => ($pos['x'] + 2 * $southwestToNortheast), 'y' => ($pos['y'] - $southwestToNortheast));
            if($newCoord['x'] >= 0 && $newCoord['y'] >= 0 && $newCoord['x'] <= 16 && $newCoord['y'] <= 16)
            {
                if($this->isValid($newCoord['x'], $newCoord['y']) && array_search($newCoord, $coordsSameColor) === false)
                    $moves[] = $newCoord;
            }
        }
        if($blockToSouthwest - $southwestToNortheast >= 0)
        {
            $newCoord = array('x' => ($pos['x'] - 2 * $southwestToNortheast), 'y' => ($pos['y'] + $southwestToNortheast));
            if($newCoord['x'] >= 0 && $newCoord['y'] >= 0 && $newCoord['x'] <= 16 && $newCoord['y'] <= 16)
            {
                if($this->isValid($newCoord['x'], $newCoord['y']) && array_search($newCoord, $coordsSameColor) === false)
                    $moves[] = $newCoord;
            }
        }
        if($blockToNorthwest - $northwestToSoutheast >= 0)
        {
            $newCoord = array('x' => ($pos['x'] - 2 * $northwestToSoutheast), 'y' => ($pos['y'] - $northwestToSoutheast));
            if($newCoord['x'] >= 0 && $newCoord['y'] >= 0 && $newCoord['x'] <= 16 && $newCoord['y'] <= 16)
            {
                if($this->isValid($newCoord['x'], $newCoord['y']) && array_search($newCoord, $coordsSameColor) === false)
                    $moves[] = $newCoord;
            }
        }
        if($blockToSoutheast - $northwestToSoutheast >= 0)
        {
            $newCoord = array('x' => ($pos['x'] + 2 * $northwestToSoutheast), 'y' => ($pos['y'] + $northwestToSoutheast));
            if($newCoord['x'] >= 0 && $newCoord['y'] >= 0 && $newCoord['x'] <= 16 && $newCoord['y'] <= 16)
            {
                if($this->isValid($newCoord['x'], $newCoord['y']) && array_search($newCoord, $coordsSameColor) === false)
                    $moves[] = $newCoord;
            }
        }

        return $moves;
    }

    /**
     * Check if a couple of coordinates are a valid position on the board
     *
     * @param int $x
     * @param int $y
     * @return boolean
     */
    public function isValid($x, $y)
    {
        if($x % 2 != 0)
            return false;

        if(($x % 4 == 0 && $y % 2 != 0) || ($x % 4 != 0 && $y % 2 == 0))
            return false;

        if($x <= 8)
        {
            $limitI = 4 - $x / 2;
            $limitS = 12 + $x / 2;
        }
        else
        {
            $x -= 10;
            $limitI = 1 + $x / 2;
            $limitS = 15 - $x / 2;
        }

        return $y >= $limitI && $y <= $limitS;
    }

    public function getBoxPosition($x, $y)
    {
        $boxPositions = array(
            '0-4' => 'A1',
            '0-6' => 'A2',
            '0-8' => 'A3',
            '0-10' => 'A4',
            '0-12' => 'A5',
            '2-3' => 'B1',
            '2-5' => 'B2',
            '2-7' => 'B3',
            '2-9' => 'B4',
            '2-11' => 'B5',
            '2-13' => 'B6',
            '4-2' => 'C1',
            '4-4' => 'C2',
            '4-6' => 'C3',
            '4-8' => 'C4',
            '4-10' => 'C5',
            '4-12' => 'C6',
            '4-14' => 'C7',
            '6-1' => 'D1',
            '6-3' => 'D2',
            '6-5' => 'D3',
            '6-7' => 'D4',
            '6-9' => 'D5',
            '6-11' => 'D6',
            '6-13' => 'D7',
            '6-15' => 'D8',
            '8-0' => 'E1',
            '8-2' => 'E2',
            '8-4' => 'E3',
            '8-6' => 'E4',
            '8-8' => 'E5',
            '8-10' => 'E6',
            '8-12' => 'E7',
            '8-14' => 'E8',
            '8-16' => 'E9',
            '10-1' => 'F1',
            '10-3' => 'F2',
            '10-5' => 'F3',
            '10-7' => 'F4',
            '10-9' => 'F5',
            '10-11' => 'F6',
            '10-13' => 'F7',
            '10-15' => 'F8',
            '12-2' => 'G1',
            '12-4' => 'G2',
            '12-6' => 'G3',
            '12-8' => 'G4',
            '12-10' => 'G5',
            '12-12' => 'G6',
            '12-14' => 'G7',
            '14-3' => 'H1',
            '14-5' => 'H2',
            '14-7' => 'H3',
            '14-9' => 'H4',
            '14-11' => 'H5',
            '14-13' => 'H6',
            '16-4' => 'I1',
            '16-6' => 'I2',
            '16-8' => 'I3',
            '16-10' => 'I4',
            '16-12' => 'I5'
        );

        return $boxPositions[$x . '-' . $y];
    }

    /**
     * @param $player
     * @param $message
     * @param $data
     */
    public function gameOver($player, $message, $data)
    {
        $sql = "UPDATE player SET player_score = 1 WHERE player_id = $player";
        self::DbQuery($sql);

        self::notifyAllPlayers("gameOver", clienttranslate($message), $data);

        $nMoves = self::getGameStateValue('number_moves');
        $turnsNumber = ceil((float) ($nMoves / 2));
        self::setStat($turnsNumber, "turns_number");

        $this->gamestate->nextState('endGame');
    }

    public function getPlayerMoves($player)
    {
        $tokens = self::getObjectListFromDB("SELECT token_id id, token_x x, token_y y, player_no player FROM token");
        $possibleMoves = array();

        foreach($tokens as $token)
        {
            if($token['player'] === $player)
            {
                $moves = $this->getMoves($tokens, $token, $player);

                if(!empty($moves))
                    $possibleMoves[$token['id']] = $moves;
            }
        }

        return $possibleMoves;
    }

//////////////////////////////////////////////////////////////////////////////
//////////// Player actions
////////////

    /*
      Each time a player is doing some game action, one of the methods below is called.
      (note: each method below must match an input method in kwartz.action.php)
     */

    /**
     * Move a token into another position
     * If there is already a token
     *
     * @param string $x The x coordinate where the token will be moved
     * @param string $y The y coordinate where the token will be moved
     * @param string $id Id of the token moved
     * @throws BgaUserException The move is not valid
     * @var int $tokenErased Contains token id which was erased (-1 if not)
     */
    public function playToken($x, $y, $id)
    {
        self::checkAction("playToken");

        $player = (string) (self::getActivePlayerId());
        $token = self::getObjectFromDB("SELECT token_id id, token_x x, token_y y, player_no player FROM token WHERE token_id=$id");

        $startPosition = $this->getBoxPosition($token['x'], $token['y']);
        $endPosition = $this->getBoxPosition($x, $y);
        //Check if the tokens belongs to the user who sends the request
        if($token['player'] != $player)
            throw new BgaUserException(self::_('This token is not yours !'));

        $tokens = self::getObjectListFromDB("SELECT token_id id, token_x x, token_y y, player_no player FROM token");
        $possibleMoves = $this->getMoves($tokens, $token, $player);

        //Check if the move is really a valid move
        $valid = false;
        foreach($possibleMoves as $pm)
        {
            if($pm['x'] == $x && $pm['y'] == $y)
            {
                $valid = true;
                break;
            }
        }

        if(!$valid)
            throw new BgaUserException(self::_('This move is not authorized'));

        $tokenErased = -1;
        $tokenDeleted = null;
        $tokenModified = null;
        $playerTokens = 0;
        $otherPlayer = null;
        $enemyTokens = 0;

        //Check if a token was erased
        foreach($tokens as $k => $t)
        {
            if($t['x'] == $token['x'] && $t['y'] == $token['y'])
                $tokenModified = $k;

            if($t['x'] == $x && $t['y'] == $y)
            {
                self::DbQuery("DELETE FROM token WHERE token_x=$x AND token_y=$y");
                $tokenErased = $t['id'];
                $tokenDeleted = $k;
            }

            if($t['player'] == $player)
                ++$playerTokens;
            else
            {
                if(empty($otherPlayer))
                    $otherPlayer = $t['player'];

                ++$enemyTokens;
            }
        }

        $movePosition = $startPosition;

        //Update tokens array
        if($tokenDeleted !== null)
        {
            unset($tokens[$tokenDeleted]);
            self::incStat(1, 'tokens_erased');
            self::incStat(1, 'player_erase_tokens', $player);
            self::incStat(1, 'player_tokens_erased', $otherPlayer);

            $movePosition .= 'x';
        }
        else
        {
            $movePosition .= '-';
        }

        $movePosition .= $endPosition;

        self::incGameStateValue('number_moves', 1);
        self::setStat(count($tokens), "tokens_remained");
        self::setStat($playerTokens, 'player_tokens_remained', $player);
        self::setStat(count($tokens) - $playerTokens, 'player_tokens_remained', $otherPlayer);

        $tokens[$tokenModified]['x'] = $x;
        $tokens[$tokenModified]['y'] = $y;

        $tokenId = $token['id'];
        $fromX = $token['x'];
        $fromY = $token['y'];

        self::DbQuery("INSERT INTO move(token_id, origin_x, origin_y, target_x, target_y, notation) VALUES($tokenId, $fromX, $fromY, $x, $y, '$movePosition')");
        self::DbQuery("UPDATE token set token_x=$x, token_y=$y WHERE token_id=$id");

        self::notifyAllPlayers("tokenPlayed", clienttranslate('${player_name} played ${move}'), array(
            'token_x' => $x,
            'token_y' => $y,
            'token_id' => $id,
            'player_name' => self::getActivePlayerName(),
            'token_erased' => $tokenErased,
            'move' => $movePosition
        ));

        $token = array('id' => $id, 'x' => $tokens[$tokenModified]['x'], 'y' => $tokens[$tokenModified]['y'], 'player' => $player);
        $isOverActivePlayer = count($this->isOver($player, $token, $tokens, array($token)));
        $isOverInactivePlayer = 0;

        if($isOverActivePlayer == $playerTokens)
        {
            self::setGameStateValue('game_over', self::TOKENS_REUNITED);
        }
        else if($tokenDeleted !== null)
        {
            $nextPlayer = self::getPlayerAfter($player);
            --$enemyTokens;
            $token = self::getObjectListFromDB("SELECT token_id id, token_x x, token_y y, player_no player FROM token WHERE player_no = $nextPlayer");
            $token = $token[0];
            $isOverInactivePlayer = count($this->isOver($nextPlayer, $token, $tokens, array($token)));

            if($isOverInactivePlayer == $enemyTokens)
            {
                self::setGameStateValue('game_over', self::TOKENS_ERASED);
            }

            $progression = ($isOverInactivePlayer / $enemyTokens) * 100;
            self::setGameStateValue('progression_inactive_player', $progression);
        }
        else
        {
            $progression = self::getGameStateValue('progression_active_player');
            self::setGameStateValue('progression_inactive_player', (int) $progression);
        }

        $progression = ($isOverActivePlayer / $playerTokens) * 100;
        self::setGameStateValue('progression_active_player', $progression);
        $this->gamestate->nextState("tokenPlayed");
    }

    public function offerDraw()
    {
        self::checkAction('offerDraw');
        self::notifyAllPlayers("offerDraw", clienttranslate('${player_name} has proposed a draw'), array('player_name' => self::getActivePlayerName()));
        $this->gamestate->nextState("offerDraw");
    }

    public function endGame()
    {
        self::checkAction("endGame");
        self::notifyAllPlayers("acceptDraw", clienttranslate('${player_name} has accepted'), array('player_name' => self::getActivePlayerName()));
        $this->gamestate->nextState("endGame");
    }

    public function continueGame()
    {
        self::checkAction("continueGame");
        self::notifyAllPlayers("denyDraw", clienttranslate('${player_name} has denied'), array('player_name' => self::getActivePlayerName()));
        $this->gamestate->nextState("continueGame");
    }

    /**
     *
     * @param $player
     * @param $token
     * @param $tokens
     * @param array $union
     * @return array
     */
    private function isOver($player, $token, $tokens, Array $union)
    {
        foreach($tokens as $t)
        {
            if($t['player'] == $player)
            {
                if($t['x'] != $token['x'] || $t['y'] != $token['y'])
                {
                    if($this->nextTo($token, $t) && !in_array($t, $union))
                    {
                        $union[] = $t;
                        $union = $this->isOver($player, $t, $tokens, $union);
                    }
                }
            }
        }

        return $union;
    }

    /**
     * Check if a token is next to another
     *
     * @param array $currentToken
     * @param array $token
     * @return boolean
     */
    private function nextTo($currentToken, $token)
    {
        $dx = pow($currentToken['x'] - $token['x'], 2);
        $dy = pow($currentToken['y'] - $token['y'], 2);

        return ($dx == 4 && $dy == 1) || ($dx == 0 && $dy == 4);
    }

    /**
     *
     * @return array List of tokens
     */
    private function getTokens()
    {
        return self::getObjectListFromDB("SELECT token_id id, token_x x, token_y y, player_no player FROM token");
    }

//////////////////////////////////////////////////////////////////////////////
//////////// Game state arguments
////////////

    /*
      Here, you can create methods defined as "game state arguments" (see "args" property in states.inc.php).
      These methods function is to return some additional information that is specific to the current
      game state.
     */

    function argPlayerTurn()
    {
        $player = (string) (self::getActivePlayerId());

        return array
        (
            'possibleMoves' => $this->getPlayerMoves($player)
        );
    }

//////////////////////////////////////////////////////////////////////////////
//////////// Game state actions
////////////

    /*
      Here, you can create methods defined as "game state actions" (see "action" property in states.inc.php).
      The action method of state X is called everytime the current game state is set to X.
     */

    function stNextPlayer()
    {
        $gameOver = self::getGameStateValue('game_over');
        $player = self::getActivePlayerId();

        if($gameOver == self::TOKENS_REUNITED)
        {
            $this->gameOver($player, '${player_name} has reunited the whole tokens ! Game over !', array(
                'player_name' => self::getActivePlayerName(),
                'player_id' => $player
            ));
        }
        else
        {
            $playerName = self::getActivePlayerName();
            $playerId = $player;
            $player = self::activeNextPlayer();

            if($gameOver == self::TOKENS_ERASED)
            {
                $this->gameOver($player, '${player_name} has erased all the isolated enemys tokens ! Game over !', array(
                    'player_name' => $playerName,
                    'player_id' => $playerId
                ));
            }
            else
            {
                $moves = $this->getPlayerMoves((string) $player);

                if(empty($moves))
                {
                    $this->gameOver($playerId, '${player_name} can\'t play any move ! Game over !', array(
                        'player_name' => self::getActivePlayerName(),
                        'player_id' => $playerId
                    ));
                }
                else
                {
                    self::giveExtraTime($player);
                    $this->gamestate->nextState('nextPlayer');
                }
            }
        }
    }

    public function stOfferDraw()
    {
        self::activeNextPlayer();
        $this->gamestate->nextState("offeringDraw");
    }

    public function stContinueGame()
    {
        $activePlayer = self::activeNextPlayer();
        $this->gamestate->nextState("nextTurn");
    }

//////////////////////////////////////////////////////////////////////////////
//////////// Zombie
////////////

    /*
      zombieTurn:

      This method is called each time it is the turn of a player who has quit the game (= "zombie" player).
      You can do whatever you want in order to make sure the turn of this player ends appropriately
      (ex: pass).
     */

    function zombieTurn($state, $active_player)
    {
        $statename = $state['name'];

        if($state['type'] == "activeplayer")
        {
            switch($statename)
            {
                default:
                    $this->gamestate->nextState("zombiePass");
                    break;
            }

            return;
        }

        if($state['type'] == "multipleactiveplayer")
        {
            // Make sure player is in a non blocking status for role turn
            $sql = "
                UPDATE  player
                SET     player_is_multiactive = 0
                WHERE   player_id = $active_player
            ";
            self::DbQuery($sql);

            $this->gamestate->updateMultiactiveOrNextState('');
            return;
        }

        throw new feException("Zombie mode not supported at this game state: " . $statename);
    }

///////////////////////////////////////////////////////////////////////////////////:
////////// DB upgrade
//////////

    /*
      upgradeTableDb:

      You don't have to care about this until your game has been published on BGA.
      Once your game is on BGA, this method is called everytime the system detects a game running with your old
      Database scheme.
      In this case, if you change your Database scheme, you just have to apply the needed changes in order to
      update the game database and allow the game to continue to run with your new version.

     */

    function upgradeTableDb($from_version)
    {
        // $from_version is the current version of this game database, in numerical form.
        // For example, if the game was running with a release of your game named "140430-1345",
        // $from_version is equal to 1404301345
        // Example:
//        if( $from_version <= 1404301345 )
//        {
//            $sql = "ALTER TABLE xxxxxxx ....";
//            self::DbQuery( $sql );
//        }
//        if( $from_version <= 1405061421 )
//        {
//            $sql = "CREATE TABLE xxxxxxx ....";
//            self::DbQuery( $sql );
//        }
//        // Please add your future database scheme changes here
//
//
    }

    //Compensate the absence of reattributeColorsBasedOnPreferences
    public function __call($method, $args)
    {
        if($method == 'reattributeColorsBasedOnPreferences' && !method_exists($this, 'reattributeColorsBasedOnPreferences'))
        {
            list($players, $colors) = $args;
            foreach($players as $playerId => $player)
            {
                //Set player colors from last color to the first so it gets a chance to mess up with the default first to last color assignment
                self::DbQuery("UPDATE player SET player_color='" . array_pop($colors) . "' WHERE player_id='$playerId'");
            }
        }
    }    
}
