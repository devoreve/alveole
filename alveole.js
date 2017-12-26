/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * Alveole implementation : © Cédric Leclinche <cedric@devoreve.com>
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 * alveole.js
 *
 * Alveole user interface script
 * 
 * In this file, you are describing the logic of your user interface, in Javascript language.
 *
 */

define([
    "dojo","dojo/_base/declare",
    "ebg/core/gamegui",
    "ebg/counter"
],
function (dojo, declare) {
    return declare("bgagame.alveole", ebg.core.gamegui, {
        constructor: function(){
            console.log('alveole constructor');
            this.tokenSelected = -1;
            this.lastMove = null;
        },
        /*
         setup:

         This method must set up the game user interface according to current game situation specified
         in parameters.

         The method is called each time the game interface is displayed to a player, ie:
         _ when the game starts
         _ when a player refreshes the game page (F5)

         "gamedatas" argument contains all datas retrieved by your "getAllDatas" PHP method.
         */

        setup: function(gamedatas)
        {
            console.log("Starting game setup");

            for(var idToken in gamedatas.tokens)
            {
                var token = gamedatas.tokens[idToken];
                var color = gamedatas.colors[gamedatas.players[token.player].color];
                this.addTokenOnBoard(color, token);
            }

            dojo.query('.hex').connect('onclick', this, 'onPlayToken');
            dojo.query('#toggle-grid').connect('onclick', this, 'onShowGrid');
            dojo.query('#toggle-last-move').connect('onclick', this, 'onShowLastMove');

            // Setup game notifications to handle (see "setupNotifications" method below)
            this.setupNotifications();

            var left, top;
            for (var idMove in gamedatas.lastmoves) {
                if (gamedatas.lastmoves[idMove].player_no != this.player_id) {
                    this.lastMove = "token_" + gamedatas.lastmoves[idMove].token_id;
                    left = gamedatas.lastmoves[idMove].origin_x * (46 / 2);
                    top = gamedatas.lastmoves[idMove].origin_y * 26.5;
                    this.setLastMovePosition(left, top);
                }
            }

            console.log("Ending game setup");
        },
        ///////////////////////////////////////////////////
        //// Game & client states

        // onEnteringState: this method is called each time we are entering into a new game state.
        //                  You can use this method to perform some user interface changes at this moment.
        //
        onEnteringState: function(stateName, args)
        {
            console.log('Entering state: ' + stateName);

            switch(stateName)
            {
                case 'playerTurn':
                    this.possibleMoves = args.args.possibleMoves;
                    break;
                case 'dummmy':
                    break;
            }
        },
        // onLeavingState: this method is called each time we are leaving a game state.
        //                 You can use this method to perform some user interface changes at this moment.
        //
        onLeavingState: function(stateName)
        {
            console.log('Leaving state: ' + stateName);

            switch(stateName)
            {

                case 'dummmy':
                    break;
            }
        },
        // onUpdateActionButtons: in this method you can manage "action buttons" that are displayed in the
        //                        action status bar (ie: the HTML links in the status bar).
        //
        onUpdateActionButtons: function(stateName, args)
        {
            console.log('onUpdateActionButtons: ' + stateName);

            if(this.isCurrentPlayerActive())
            {
                switch(stateName)
                {
                    case 'playerTurn':
                        this.addActionButton('abtn_offer_draw',_('Propose draw'), 'onOfferDraw');
                        break;
                    case 'offeringDraw':
                        this.addActionButton('abtn_accept_draw',_('Accept draw'), 'onAcceptDraw');
                        this.addActionButton('abtn_deny_draw',_('Deny draw'), 'onDenyDraw');
                        break;
                }
            }
        },
        addTokenOnBoard: function(color, token)
        {
            dojo.place(this.format_block('jstpl_token',
                {
                    color: color,
                    id: token.id
                }), 'tokens');

//                    dojo.addClass('token_' + token.id, 'token-' + token.x + '-' + token.y);
            dojo.style('token_' + token.id, 'left', (token.x * (46 / 2) + 7) + 'px');
            dojo.style('token_' + token.id, 'top', (token.y * 26.5 + 3) + 'px');

            if (token.player == this.player_id) {
                dojo.style('token_' + token.id, 'cursor', 'pointer');
            }

         //   playerHexaColor = this.gamedatas.players[this.player_id].color;
           // playerColor = this.gamedatas.colors[playerHexaColor];

            dojo.connect($('token_' + token.id), 'onclick', this, 'onShowPossibleMoves');
        },
        ///////////////////////////////////////////////////
        //// Utility methods

        /*

         Here, you can defines some utility methods that you can use everywhere in your javascript
         script.

         */
        getToken: function (tokenId) {
            for (var id in this.gamedatas.tokens) {
                if (this.gamedatas.tokens[id].id == tokenId) {
                    return this.gamedatas.tokens[tokenId];
                }
            }
        },
        getPlayer: function () {
            for (var playerId in this.gamedatas.players) {
                if (this.gamedatas.players[playerId].id == this.player_id) {
                    return this.gamedatas.players[playerId];
                }
            }
        },
        setLastMovePosition: function (left, top) {
            dojo.style('last-move', 'left', left + 'px');
            dojo.style('last-move', 'top', top + 'px');
            console.log('test');
        },


        ///////////////////////////////////////////////////
        //// Player's action

        /*

         Here, you are defining methods to handle player's action (ex: results of mouse click on
         game objects).

         Most of the time, these methods:
         _ check the action is possible at this game state.
         _ make a call to the game server

         */

        onShowGrid: function(evt)
        {
            dojo.stopEvent( evt );
            
            var grid = dojo.query('.hex span.hidden');

            if (grid.length == 0) {
                dojo.query('.hex span').addClass('hidden');
            } else {
                grid.removeClass('hidden');
            }
        },
        onShowLastMove: function(evt)
        {
            dojo.stopEvent( evt );

            if (this.lastMove != null) {
                if (dojo.hasClass('last-move', 'hidden')) {
                    dojo.query('#last-move').removeClass('hidden');
                    dojo.addClass(this.lastMove, 'active');
                } else {
                    dojo.addClass('last-move', 'hidden');
                    dojo.query('#' + this.lastMove).removeClass('active');
                }
            }
        },
        onPlayToken: function(evt)
        {
            if (dojo.hasClass(evt.target.id, 'possible-move')) {
                dojo.query('.possible-move').removeClass('possible-move');
                dojo.query('.active').removeClass('active');

                var token = dojo.query('#token_' + this.tokenSelected);
                token.addClass('moving');

                if(this.checkAction("playToken")){
                    this.lastMove = null;
                    var coords = evt.target.id.split('_');
                    var x = parseInt(coords[1], 10);
                    var y = parseInt(coords[2], 10);

                    this.ajaxcall('/alveole/alveole/playToken.html', {
                        lock: true,
                        x: x,
                        y: y,
                        id: this.tokenSelected
                    }, this, function(result){

                    });

                    this.tokenSelected = -1;
                }
            }
        },
        onShowPossibleMoves: function(evt)
        {
            dojo.stopEvent(evt);

            if(!this.possibleMoves)
                return;

            if(this.player_id == this.getActivePlayerId())
            {
                var idToken = evt.target.id;
                var currentColor = dojo.hasClass(idToken, 'token-red') ? 'red' : 'blue';

                playerHexaColor = this.gamedatas.players[this.getActivePlayerId()].color;
                playerColor = this.gamedatas.colors[playerHexaColor];

                if(currentColor == playerColor)
                {
                    dojo.query('.active').removeClass('active');
                    dojo.query('.possible-move').removeClass('possible-move');
                    data = idToken.split('_');
                    id = parseInt(data[1], 10);

                    if(id != this.tokenSelected)
                    {
                        dojo.addClass(evt.target, 'active');
                        this.tokenSelected = id;

                        if(this.possibleMoves[id] != undefined && this.possibleMoves[id].length > 0)
                        {
                            for(var idMove in this.possibleMoves[id])
                            {
                                var pm = this.possibleMoves[id][idMove];
                                dojo.addClass('box_' + pm.x + '_' + pm.y, 'possible-move');
                                dojo.addClass('position_' + pm.x + '_' + pm.y, 'possible-move');
                            }
                        }

                        // dojo.query('.possible-move').connect('onclick', this, 'onPlayToken');
                    }
                    else
                    {
                        this.tokenSelected = -1;
                    }
                }
            }
        },
        onOfferDraw: function(){
            this.confirmationDialog( _('Are you sure you want to offer a draw ?'),
                dojo.hitch( this, function() {
                    this.ajaxcall( '/alveole/alveole/offerDraw.html',{
                        lock:true
                    }, this, function( result ) {} );
                })
            );
        },
        onAcceptDraw: function(){
            this.ajaxcall( '/alveole/alveole/acceptDraw.html',{
                lock:true
            }, this, function( result ) {} );
        },
        onDenyDraw: function(){
            this.ajaxcall( '/alveole/alveole/denyDraw.html',{
                lock:true
            }, this, function( result ) {} );
        },
        ///////////////////////////////////////////////////
        //// Reaction to cometD notifications

        /*
         setupNotifications:

         In this method, you associate each of your game notifications with your local method to handle it.

         Note: game notification names correspond to "notifyAllPlayers" and "notifyPlayer" calls in
         your alveole.game.php file.

         */
        setupNotifications: function()
        {
            console.log('notifications subscriptions setup');

            dojo.subscribe('tokenPlayed', this, "notif_tokenPlayed");
            dojo.subscribe('gameOver', this, "notif_gameOver");
            this.notifqueue.setSynchronous( 'tokenPlayed', 1000 );
        },

        notif_tokenPlayed: function(notif)
        {
            var left = dojo.style("token_" + notif.args.token_id, 'left') - 7;
            var top = dojo.style("token_" + notif.args.token_id, 'top') - 3;

            this.slideToObject("token_" + notif.args.token_id, "box_" + notif.args.token_x + "_" + notif.args.token_y, 1000).play();

            dojo.query('#token_' + notif.args.token_id).removeClass('moving');

            var token = this.getToken(notif.args.token_id);

            if (token.player != this.player_id) {
                dojo.style('last-move', 'left', left + 'px');
                dojo.style('last-move', 'top', top + 'px');

                this.lastMove = "token_" + notif.args.token_id;
            }

            if(notif.args.token_erased != -1)
                this.fadeOutAndDestroy("token_" + notif.args.token_erased);
        },
        notif_gameOver: function(notif)
        {
            this.scoreCtrl[ notif.args['player_id'] ].incValue(1);
        }
    });
});
