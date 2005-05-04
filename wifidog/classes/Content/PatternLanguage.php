<?php

/********************************************************************\
 * This program is free software; you can redistribute it and/or    *
 * modify it under the terms of the GNU General Public License as   *
 * published by the Free Software Foundation; either version 2 of   *
 * the License, or (at your option) any later version.              *
 *                                                                  *
 * This program is distributed in the hope that it will be useful,  *
 * but WITHOUT ANY WARRANTY; without even the implied warranty of   *
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the    *
 * GNU General Public License for more details.                     *
 *                                                                  *
 * You should have received a copy of the GNU General Public License*
 * along with this program; if not, contact:                        *
 *                                                                  *
 * Free Software Foundation           Voice:  +1-617-542-5942       *
 * 59 Temple Place - Suite 330        Fax:    +1-617-542-2652       *
 * Boston, MA  02111-1307,  USA       gnu@gnu.org                   *
 *                                                                  *
 \********************************************************************/
/**@file PatternLanguage.php
 * @author Copyright (C) 2005 Benoit Grégoire <bock@step.polymtl.ca>,
 * Technologies Coeus inc.
 */
require_once BASEPATH.'classes/Content/ContentGroup.php';
require_once BASEPATH.'classes/User.php';

/** Pattern Language is a location-aware fiction project by Kate Armstrong that attaches patterns of narrative to individuals as they move through the city of Montreal. Each person's path is logged in the system and compiled into a document that can be read online. The work is meant to engage with the rhythms of the city: by evolving according to the patterns of an individual, each story forms both a map or trace of movement and a fabric of sound. 

Pattern Language is activated through the login system of the Ile Sans Fils network. Once a user subscribes to Pattern Language , a piece of the story is delivered whenever s/he logs into the ISF wireless network using a WiFi-enabled laptop or mobile device. 
Narrative fragments associated with each hotspot correspond to the point of view of one character, so that repeatedly logging into the system from a single hotspot will produce a narrative from a single point of view, while moving between hotspots will insert new characters and perspectives into the text. 

Users may choose to actively engage Pattern Language  by deliberately travelling between points in the city in order to generate narrative activity, or they may decide to have Pattern Language  running in the background as they go about their regular activities, only stopping to read their document now and then over a period of time. Users have the option of setting the project to expire automatically after one day, one week, or one month, or they may manually terminate their involvement in the project at any point.
 
Once the fragments have been delivered they are compiled in real time into a document that is unique to each participant. Each individual narrative is archived and viewable online. Users may read the documents they have generated or those that have been generated by others. Participation in Pattern Language  is limited to those using the system in Montreal, although narratives generated by participants may be read by anyone visiting the website. 
 */
class PatternLanguage extends ContentGroup
{
    /**
     * Get all pattern language objects
     */
    public static function getAllContent() 
    {
       return parent::getAllContent("PatternLanguage"); 
    }   
     
	function __construct($content_id)
	{
		parent :: __construct($content_id);
        
        // A Pattern language can NEVER be expandable
        $this->setIsExpandable(false);
	}
    
    /** Retreives the user interface of this object.  Anything that overrides this method should call the parent method with it's output at the END of processing.
     * @param $subclass_admin_interface Html content of the interface element of a children
     * @return The HTML fragment for this interface */
    public function getUserUI($subclass_user_interface = null)
    {
        $html = '';
        $html .= "<div class='user_ui_container'>\n";
        $html .= "<div class='user_ui_object_class'>PatternLanguage (".get_class($this)." instance)</div>\n";
        
        // Check if the user has already subscribed to Pattern language
        $current_user = User::getCurrentUser();
        if($current_user == null || $this->isUserSubscribed($current_user) == false)
        {
            // hyperlink to all users narrative
            $html .= "<ul class='pattern_language_menu'>";
            $html .= "<li><a class='pattern_language_big_links' href='/content/PatternLanguage/subscription.php'>"._("Subscribe to Pattern Language")."</a></li>";
            $html .= "<li><a class='pattern_language_big_links' href='/content/PatternLanguage/archives.php'>"._("Read narratives archives")."</a></li>";
            $html .= "</ul>";
            
            // Until subscription is done DO NOT log this !
            $this->setLoggingStatus(false);
            // Tell the content group not to display elements until subscription is done
            $parent_output = parent :: getUserUI($html, true);
        }
        else
        {
            // The user is subscribed to the pattern language show an element !
            // hyperlink to user's narrative
            $html .= "<ul class='pattern_language_menu'>";
            $html .= "<li><a href='/content/PatternLanguage/narrative.php'>"._("Read my narrative")."</a></li>";
            $html .= "<li><a href='/content/PatternLanguage/archives.php'>"._("Read narratives archives")."</a></li>";
            $html .= "<li><a href='/content/PatternLanguage/subscription.php'>"._("Unsubscribe")."</a></li>";
            $html .= "</ul>";
            
            // Display the random pattern
            $parent_output = parent :: getUserUI($html);
        }
        
        $html .= $subclass_user_interface;
        $html .= "</div>\n";
        
        return $parent_output;
    }
    
    /** Is this pattern displayable at a certain Node
     * @param $node Node, optionnal
     * @return true or false */
    public function isDisplayableAt($node)
    {
        // Pattern language will always be displayable 
        return true;
    }

	/** Display the narrative
	 * @param $user The user who's narrative you want to grab
	 * @return the archive page HTML */
	public function displayNarrative(User $user)
	{
        global $db;
        // Debug values user_id = 8a90b1ea56cf27a0c61f9304da73bcd5
        // PL : 3a3ea73dd2e2d03729e62b95d2574fc6
        $sql = "SELECT * FROM (SELECT DISTINCT ON (content_group_element_id) content_group_element_id, first_display_timestamp FROM content_display_log AS cdl JOIN content_group_element AS cge ON (cdl.content_id = cge.content_group_element_id) JOIN content ON (content.content_id = cge.content_group_id) where user_id = '{$user->getId()}' AND cge.content_group_id = '{$this->getId()}' AND content.content_type = 'PatternLanguage') AS patterns ORDER BY first_display_timestamp";
        // OLD QUERY $sql = "SELECT DISTINCT ON (content_group_element_id) content_group_element_id, first_display_timestamp FROM content_display_log AS cdl JOIN content_group_element AS cge ON (cdl.content_id = cge.content_group_element_id) JOIN content ON (content.content_id = cge.content_group_id) where user_id = '{$user->getId()}' AND content.content_type = 'PatternLanguage' ORDER BY content_group_element_id";
        $db->ExecSql($sql, $rows, false);
        $html = "";
        if($rows)
            foreach($rows as $row)
            {
                $cge = Content::getObject($row['content_group_element_id']);
                $cge->setLoggingStatus(false);
                $html .= $cge->getUserUI()."<p>";
            } 
	   return $html;
    }
	
	/** Get the list of all narratives
	 * @return the archive page HTML */
	public function getNarrativeList()
	{
		global $db;
        $sql = "SELECT DISTINCT user_id FROM content_display_log AS cdl JOIN content_group_element AS cge ON (cdl.content_id = cge.content_group_element_id) JOIN content ON (content.content_id = cge.content_group_id) WHERE content_type = 'PatternLanguage'";
        $db->ExecSql($sql , $rows, false);
        $narratives = array();
        if($rows)
            foreach($rows as $row)
                $narratives[] = User::getObject($row['user_id']);
        return $narratives;
	}

} // End class
?>