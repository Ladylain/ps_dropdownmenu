<!-- Block blockdropdownmenu -->
<nav>
	<ul class="nav">
	{if $page_name != 'index'}
		<li><a href="../"><img class="homeBtn" src="http://www.entypo.com/images/home.svg"></a></li>
	{/if}
		{$MENU}
		{hook h=displayDropDownMenu}
	</ul>

</nav>
<!-- /Block blockdropdownmenu -->