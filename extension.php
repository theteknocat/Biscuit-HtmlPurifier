<?php
/**
 * A wrapper for the HTML purifier library.  When this class gets instantiated, it determines which version of the purifier library to include based on the PHP version,
 * instantiates it (storing it in it's own property for easy re-use), and can then call it with wrapper functions that allow a list of allowable HTML tags on any individual
 * purifier call.
 *
 * @package Extensions
 * @subpackage HtmlPurify
 * @author Peter Epp
 * @copyright Copyright (c) 2009 Peter Epp (http://teknocat.org)
 * @license GNU Lesser General Public License (http://www.gnu.org/licenses/lgpl.html)
 * @version 2.0 $Id: extension.php 14624 2012-04-19 18:29:35Z teknocat $
 **/
class HtmlPurify {
	/**
	 * The path to HTML purifier, relative to this file
	 *
	 * @var string
	 */
	private static $_htmlpurifier_path = 'vendor/htmlpurifier-4.4.0/library/HTMLPurifier.auto.php';
	/**
	 * Use HTMLPurifier to purify dirty HTML.
	 *
	 * @param $dirty_html string Text that contains HTML that needs purifying
	 * @param $allowed string Optional - comma-separated list of HTML elements and attributes that are allowed.  Defaults to the most commonly used elements/attributes.
	 * @return string The purified HTML
	 * @author Peter Epp
	 */
	public static function purify_html($dirty_html,$filters = array()) {
		if (self::is_installed()) {
			if (!isset($filters['allowed'])) {
				$filters['allowed'] = "p[class|style],
								strong,
								b,
								i,
								em,
								h1,
								h2,
								h3,
								h4,
								br,
								hr,
								a[href|title|class|style|target],
								ul[class|style],
								ol[class|style],
								li[class|style],
								dl[class|style],
								dt[class|style],
								dd[class|style],
								span[class|style],
								img[alt|src|width|height|border|class|style],
								sup,
								sub";
			}
			if (!isset($filters['css_allowed'])) {
				$filters['css_allowed'] = array();
			}
			$purifier_config = HTMLPurifier_Config::createDefault();
			if (!Crumbs::ensure_directory(SITE_ROOT.'/var/cache/html-purifier-serializer')) {
				Console::log("HTML Purifier cache directory (/var/cache/html-purifier-serializer) does not exist or is not writable. Performance will be reduced.");
			}
			$purifier_config->set('Cache.SerializerPath', SITE_ROOT.'/var/cache/html-purifier-serializer');
			$purifier_config->set('Core.Encoding', 'UTF-8');
			$allowed = preg_replace("/(\t|\r|\n|\s)/","",$filters["allowed"]);
			$purifier_config->set('HTML.Allowed',$allowed);
			$purifier_config->set('HTML.Doctype','HTML 4.01 Transitional');
			$purifier_config->set('Filter.YouTube',true);
			$purifier_config->set('HTML.SafeEmbed',true);
			$purifier_config->set('HTML.SafeObject',true);
			$purifier_config->set('Attr.EnableID',true);
			$purifier_config->set('Attr.IDPrefix','user-');
			$purifier_config->set('Attr.AllowedFrameTargets',array("_blank","_top","_self"));
			if (!empty($filters["css_allowed"]) && PURIFIER_VERSION == "PHP5") {
				$purifier_config->set('CSS.AllowedProperties',$filters["css_allowed"]);		// NOTE: This does not work in the PHP4 version
			}
			$purifier = new HTMLPurifier($purifier_config);
			$purified_html = $purifier->purify($dirty_html);
			unset($purifier_config,$purifier);
			return $purified_html;
		}
		else {
			return "Purified content could not be displayed!  Please contact the system administrator.";
		}
	}
	/**
	 * Use HTMLPurifier to strip all HTML from text. This is useful, for example, to clean out HTML for the plain text body of an email
	 *
	 * @param string $dirty_text Text that may contain HTML to be stripped
	 * @return string The text with all HTML stripped out
	 * @author Peter Epp
	 */
	public static function purify_text($dirty_text) {
		if (self::is_installed()) {
			$purifier_config = HTMLPurifier_Config::createDefault();
			if (!Crumbs::ensure_directory(SITE_ROOT.'/var/cache/html-purifier-serializer')) {
				Console::log("HTML Purifier cache directory (/var/cache/html-purifier-serializer) does not exist or is not writable. Performance will be reduced.");
			}
			$purifier_config->set('Cache.SerializerPath', SITE_ROOT.'/var/cache/html-purifier-serializer');
			$purifier_config->set('Core.Encoding', 'UTF-8');
			$purifier_config->set('HTML.Allowed',"");
			$purifier = new HTMLPurifier($purifier_config);
			$purified_text = $purifier->purify($dirty_text);
			unset($purifier_config,$purifier);
			return $purified_text;
		}
		else {
			return "Purified content could not be displayed!  Please contact the system administrator.";
		}
	}
	/**
	 * Whether or not the HTMLPurifier class exists
	 *
	 * @return void
	 * @author Peter Epp
	 */
	public static function is_installed() {
		require_once(self::$_htmlpurifier_path);
		return (class_exists('HTMLPurifier'));
	}
	/**
	 * Purify an entire array of text data recursively. All elements of the array will be stripped of any HTML.
	 *
	 * @param string $data 
	 * @return void
	 * @author Peter Epp
	 */
	public static function purify_array_text($data) {
		if (is_array($data) && !empty($data)) {
			foreach ($data as $key => $value) {
				if (is_array($value)) {
					$data[$key] = self::purify_array_text($value);
				}
				else {
					$data[$key] = self::purify_text($value);
				}
			}
		}
		return $data;
	}
	/**
	 * Purify an entire array of HTML data recursively. All elements of the array will have any HTML purified.
	 *
	 * @param string $data 
	 * @param array $filters Same as for self::purify_html(). Exclude to use default.
	 * @return void
	 * @author Peter Epp
	 */
	public static function purify_array_html($data,$filters = array()) {
		if (is_array($data) && !empty($data)) {
			foreach ($data as $key => $value) {
				if (is_array($value)) {
					$data[$key] = self::purify_array_html($value,$filters);
				}
				else {
					$data[$key] = self::purify_html($value,$filters);
				}
			}
		}
		return $data;
	}
}
/**
 * Shortcut to the HtmlPurify class, which needs to be named per the framework naming conventions
 *
 * @package Extensions
 * @author Peter Epp
 */
class H extends HtmlPurify {
}
