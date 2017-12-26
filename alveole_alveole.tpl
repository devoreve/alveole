{OVERALL_GAME_HEADER}


<div id="board">

    <!-- BEGIN box -->
    <div id="box_{bx}_{by}" class="hex" style="left: {left}px; top: {top}px;">
        <span id="position_{bx}_{by}" class="hidden">{position}</span>
    </div>
    <!-- END box -->
    <div id="tokens">

    </div>
    <div id="possibleMoves">

    </div>
    <div id="last-move" class="hex last-move hidden"></div>
</div>

<div id="toolbar">
   <a id="toggle-last-move" class="bgabutton bgabutton_blue" href="#">{DISPLAY_LAST_MOVE}</a> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
   <a id="toggle-grid" class="bgabutton bgabutton_blue" href="#">{DISPLAY_HIDE_GRID}</a>
</div>


<script type="text/javascript">

    var jstpl_token = '<div class="token token-${color}" id="token_${id}"></div>';
    var jstpl_lastmove = '<div id="last-move" class="hex" style="top: ${t}px; left: ${l}px;"></div>';

</script>

{OVERALL_GAME_FOOTER}
