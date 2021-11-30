<?php

/**
  * This plugin can be used to insert a calendar on your page
  *
  * History:
  *   v0.2: initial plugin
  *   v0.21: added doTemplateVar
  *   v0.5: - moved table summary to constructor
  *         - date headers now created according to locale
  *   v0.51: fixed: calender was not limited to blog
  *   v0.6: using options now instead of instance variables to keep options
  *   v0.7: dates with future/draft posts are no longer linked
  *   v0.71: last table cell was missing
  *   v0.72: links to previous/next month added (by Roel)
  *			 Fixed: "Passing locale category name as string is deprecated" by removing two quotes ;-)
  *   v0.73: jhoover fixed the bug that appeared in the monthly archives
  *			 (showing December 1999 everywhere) when using Fancy URL's
  *   v0.75: short_open_tags: <? -> <?php (karma)
  *   v0.76: use sql_table, add supportsFeature, add today highlight, merged w/ XE
  *   v0.77: added option to change prev/next month link label
  *          added option to change day label
  *          added option to start week on Sun (integrated changes from Hop)
  *   v0.78: added straight calendar mode (no links)
  *   v0.78a: merged gRegor's category change
  *   v0.79: fixed typo
  *   v0.79a: disabled prev/next month link if SC mode is enabled
  *   v0.80: fixed today highlight bug
  *   v0.81: included hcgtv's improvement
  *   v0.81a: typo
  *   v0.81b: fixed bug reported by Pieter
  *   v0.82: fixed blogname parameter bug reported by Pieter
  *
  * For today highlight added this in default.css
  *   table.calendar td.today { background-color: green;}
  *
  * Some example of customing the calendar
  *   table.calendar {
  *     font-size: small;
  *     color: black;
  *   }
  *   tr.calendardateheaders {
  *     font-size: small;
  *     color: red;
  *   }
  *   td.days {
  *     text-align: center;
  *   }
  *   td.today {
  *     text-align: center;
  *     color: green;
  *     background-color: whitesmoke;
  *   }
  */

if (!function_exists('sql_table'))
{
        function sql_table($name) {
                return 'nucleus_' . $name;
        }
}

class NP_Calendar extends NucleusPlugin {

   /**
     * Plugin data to be shown on the plugin list
     */
   function getName() {          return 'Calendar Plugin'; }
   function getAuthor()  {       return 'karma / roel / jhoover / admun / hcgtv'; }
   function getURL()  {          return 'http://nucleuscms.org/'; }
   function getVersion() {       return '0.82'; }
   function getDescription() {
	   return 'This plugin can be called from within skins to insert a calender on your site, by using &lt;%Calendar%&gt;.';
   }

   function supportsFeature($feature) {
	  switch($feature) {
		 case 'SqlTablePrefix':
		    return 1;
		 default:
		    return 0;
      }
   }

   /**
     * On plugin install, three options are created
     */
   function install() {
	   // create some options
	   $this->createOption('Locale','Language (locale) to use','text','english');
	   $this->createOption('LinkAll','Create links for all days (even those that do not have posts?)','yesno','no');
	   $this->createOption('JustCal','Display a calendar with no link?  (override create links for all days above)','yesno','no');
	   $this->createOption('Summary','Summary text for the calendar table','text','Monthly calendar with links to each day\'s posts');
	   $this->createOption('prevm','Label for previous month','text','&lt;');
	   $this->createOption('nextm','Label for next month','text','&gt;');
	   $this->createOption('mon','Label for Monday','text','Mon');
	   $this->createOption('tue','Label for Tuesday','text','Tue');
	   $this->createOption('wed','Label for Wednesday','text','Wed');
	   $this->createOption('thu','Label for Thursday','text','Thu');
	   $this->createOption('fri','Label for Friday','text','Fri');
	   $this->createOption('sat','Label for Saturday','text','Sat');
	   $this->createOption('sun','Label for Sunday','text','Sun');
	   $this->createOption('startsun','Should week start in Sunday?','yesno','no');
   }

   /**
       * skinvar parameters:
       *      - blogname (optional)
       */
   function doSkinVar($skinType, $view = 'all', $blogName = '') {
	   global $manager, $blog, $CONF, $archive;

	   /*
	      find out which blog to use:
	      1. try the blog chosen in skinvar parameter
	      2. try to use the currently selected blog
	      3. use the default blog
	    */
	   if ($blogName) {
		   $b =& $manager->getBlog(getBlogIDFromName($blogName));
	   } else if ($blog) {
		   $b =& $blog;
	   } else {
		   $b =& $manager->getBlog($CONF['DefaultBlog']);
	   }

	   /*
	      select which month to show
	      - for archives: use that month
	      - otherwise: use current month
	    */
	   switch($skinType) {
		   case 'archive':
			   sscanf($archive,'%d-%d-%d',$y,$m,$d);
			   $time = mktime(0,0,0,$m,1,$y);
			   break;
		   default:
			   $time = $b->getCorrectTime(time());
	   }

	   /*   Set $category if $view = 'limited'
		This means only items from the specified category
		will be displayed in the calendar.
		Defaults to show all categories in calendar.
	    */
	   $category = ($view == 'limited') ? $blog->getSelectedCategory() : 0;

        $this->_drawCalendar($time, $b, $this->getOption('LinkAll'), $category);
    }

   /**
     * This function draws the actual calendar as a table
     */
   function _drawCalendar($timestamp, &$blog, $linkall, $category) {
	   $blogid = $blog->getID();

	   // set correct locale
	   setlocale(LC_TIME,$this->getOption('Locale'));

	   // get year/month etc
	   $date = getDate($timestamp);

	   $month = $date['mon'];
	   $year = $date['year'];

	   // get previous year-month
	   $last_month = $month - 1;
	   $last_year = $year;
	   if (!checkdate($last_month, 1, $last_year)) {
		   $last_month += 12;
		   $last_year --;
	   }

	   if ($last_month < 10) {
		   $last_month = "0".$last_month;
	   }
	   else {
		   $last_month >= 10;
		   $last_month = $last_month;
	   }

	   // get the next year-month
	   $next_month = $month + 1;
	   $next_year = $year;
	   if (!checkdate($next_month, 1, $next_year)) {
		   $next_year++;
		   $next_month -= 12;
	   }

	   if ($next_month < 10) {
		   $next_month = "0".$next_month;
	   }
	   else {
		   $next_month >= 10;
		   $next_month = $next_month;
	   }

	   $nolink = $this->getOption('JustCal');

	   // find out for which days we have posts
	   if ($linkall == 'no' && $nolink == 'no' ) {
		   $days = array();
		   $timeNow = $blog->getCorrectTime();
		   if ($category != 0) {
			   $res = sql_query('SELECT DAYOFMONTH(itime) as day FROM '.sql_table('item').' WHERE icat='.$category.' and MONTH(itime)='.$month.' and YEAR(itime)='.$year .' and iblog=' . $blogid . ' and idraft=0 and UNIX_TIMESTAMP(itime)<'.$timeNow.' GROUP BY day');
		   } else {
			   $res = sql_query('SELECT DAYOFMONTH(itime) as day FROM '.sql_table('item').' WHERE MONTH(itime)='.$month.' and YEAR(itime)='.$year .' and iblog=' . $blogid . ' and idraft=0 and UNIX_TIMESTAMP(itime)<'.$timeNow.' GROUP BY day');
		   }

		   while ($o = mysql_fetch_object($res)) {
			   $days[$o->day] = 1;
		   }
	   }

	   $prev = $this->getOption('prevm');
	   $next = $this->getOption('nextm');

	   // draw header
	   if ($nolink == "yes") {
	   ?> <!-- kalendar start -->
		   <table class="calendar" summary="<?php echo htmlspecialchars($this->getOption('Summary'))?>">
		   <caption>
		   <?php echo strftime('%B %Y',$timestamp); ?></a>
		   </caption>
		   <tr class="calendardateheaders">
	   <?php
	   } else {
	   ?> <!-- kalendar start -->
		   <table class="calendar" summary="<?php echo htmlspecialchars($this->getOption('Summary'))?>">
		   <caption>
		   <a href="<?php echo createArchiveLink($blogid,$last_year.'-'.$last_month)?>"> <?php echo $prev; ?></a>
		   <a href="<?php echo createArchiveLink($blogid, strftime('%Y-%m',$timestamp))?>"> <?php echo strftime('%B %Y',$timestamp)?></a>
		   <a href="<?php echo createArchiveLink($blogid,$next_year.'-'.$next_month)?>"><?php echo $next?></a>
		   </caption>
		   <tr class="calendardateheaders">
	   <?php
	   }

	   $startsun = $this->getOption('startsun');
	   // output localized weekday-abbreviations as column headers
	   if ($startsun == 'yes') {
		   $daylabel = array(
				   $this->getOption('sun'),
				   $this->getOption('mon'),
				   $this->getOption('tue'),
				   $this->getOption('wed'),
				   $this->getOption('thu'),
				   $this->getOption('fri'),
				   $this->getOption('sat')
				   );
	   }
	   else {
		   $daylabel = array(
				   $this->getOption('mon'),
				   $this->getOption('tue'),
				   $this->getOption('wed'),
				   $this->getOption('thu'),
				   $this->getOption('fri'),
				   $this->getOption('sat'),
				   $this->getOption('sun')
				   );
	   }

	   foreach($daylabel as $weekday) {
		   echo '<th>' . $weekday . '</th>';
	   }
	   ?>
		   </tr>
		   <tr>
	   <?php


	   // draw empty cells for all days before start
	   $firstDay = getDate(mktime(0,0,0,$month,1,$year));

	   if ($startsun == 'yes') {
		   $wday = 1;
		   while ($wday <= $firstDay['wday']) {
			   $wday++;
			   echo '<td>&nbsp;</td>';
		   }
	   }
	   else {
		   if ($firstDay['wday'] == 0)
			   $firstDay['wday'] = 7;

		   $wday = 1;
		   while ($wday < $firstDay['wday']) {
			   $wday++;
			   echo '<td>&nbsp;</td>';
		   }
	   }

	   $mday = 1;
	   $to_day = date("j", $blog->getCorrectTime());
	   $this_month = date("n");
	   while (checkdate($month, $mday, $year)) {
		   if ($mday == $to_day && $this_month == $month)
			   echo '<td class="today">';
		   else
			   echo '<td class="days">';

		   if (($linkall == 'yes' && $nolink== 'no') || $days[$mday])
			   echo '<a href="',createArchiveLink($blogid,$year.'-'.$month.'-'.$mday) ,'">' ,  $mday, '</a></td>';
		   else
			   echo $mday,'</td>';

		   $mday++; $wday++;
		   if (($wday > 7) && (checkdate($month, $mday, $year))) {
			   echo '</tr><tr>';
			   $wday = 1;
		   }
	   }

	   while ($wday++ < 8)
		   echo '<td>&nbsp;</td>';
	   echo '</tr>';

	   // footer
	   echo '</table>';
	   echo "\n<!-- kalendar end -->\n";
   }

}
?>
