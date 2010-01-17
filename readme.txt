=== github_activty ===
Contributors: 01001111
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=11216463
Tags: widget, calendar, google, events, social
Requires at least: 2.7
Tested up to: 2.9
Stable tag: trunk

List recent activity on your github account.

== Description ==

The github_activty widget/plugin lists recent activity on your github account.

== Installation ==

1. Upload 'github_activty.php' to the '/wp-content/plugins/' directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Configure the widget in the widget section.

To use as a plugin in your theme, include the following:
`<php	$w = new github_activty($options);
	echo $w->outputEvents(); ?>`

The configuration parameters are:

* Github User ID: the target github user id.
* Title: the title of the section if used as a widget.
* Limit Events: the number of events to limit the output to.  Leave blank or enter a negative number for no limit.
* Date Format: the format of the date/time output (see php's date() function for reference: http://www.php.net/manual/en/function.date.php).
* Wrapper Div ID: the div id for styling (blank for no div).

If using as a theme plugin, the options are an associative array with the following entries (and example values):


`$options = array(
    'user'       => "php",
    'title'      => "github activity",
    'limit'      => 10,
    'dateFormat' => "Y-m-d H:i:s:",
    'divid'      => "github_activity"
);`


== Frequently Asked Questions ==

= What's GitHub? =

From http://github.com :

Git is a fast, efficient, distributed version control system ideal for the collaborative development of software.

GitHub is the easiest (and prettiest) way to participate in that collaboration: fork projects, send pull requests, monitor development, all with ease.

== Screenshots ==

None at the moment.

== Changelog ==

= 1.0.0 =
* First release.

== Upgrade Notice ==

= 1.0.0 =
First release.