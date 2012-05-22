<?php

# Copyright (c)  2012  <tobias.thelen@iais.fraunhofer.de>
#
# Plus1Minus1Plugin
#
# Enables [+1-1] Markup to embed small +1/-1-Votings
#
# Version 0.2.0 (2012/02/07): Voting id will be generated on save
# Version 0.1.0 (2012/02/02): User has to invent unique md5 hash, markup is [+1-1:<unique md5 hash]
#
#
# Permission is hereby granted, free of charge, to any person obtaining a copy
# of this software and associated documentation files (the "Software"), to deal
# in the Software without restriction, including without limitation the rights
# to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
# copies of the Software, and to permit persons to whom the Software is
# furnished to do so, subject to the following conditions:
#
# The above copyright notice and this permission notice shall be included in all
# copies or substantial portions of the Software.
#
# THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
# IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
# FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
# AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
# LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
# OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
# SOFTWARE.

class Plus1Minus1Plugin extends StudipPlugin implements SystemPlugin
{

    function __construct()
    {
        parent::__construct();

        // transformation markup for voting element
        StudipTransformFormat::addStudipMarkup('plus1minus1', '\[(\+1-1|\+1)\]', NULL, 'Plus1Minus1Plugin::transformMarkupPlus1Minus1');

        // markup for voting element
        StudipFormat::addStudipMarkup('plus1minus1', '\[(\+1-1|\+1):([a-f0-9]{32})\]', NULL, 'Plus1Minus1Plugin::markupPlus1Minus1');

        // use template mechanism to inject plugin url into jquery code
        // template is in templates/vote_click.php
        $template_path = $this->getPluginPath() . '/templates';
        $this->template_factory = new Flexi_TemplateFactory($template_path);
        $template = $this->template_factory->open('vote_click');
        PageLayout::addHeadElement('script', array(), $template->render());

        // 
        // <!-- UI Tools: Tabs, Tooltip, Scrollable and Overlay (4.45 Kb) -->
        // PageLayout::addScript("http://cdn.jquerytools.org/1.2.6/tiny/jquery.tools.min.js");


        // add some nice css 
        PageLayout::addStyleSheet($this->getPluginURL().'/css/plus1minus1.css');

    }

    function vote_action() 
    {
       // vote_action is called via ajax: registers vote and sends replacements widget

       // check valid user
       // NOTE: every user can vote on every plus1minus1vote regardless of access rights to embedding object (wiki, forum, ...)
       global $user;
       if (!is_object($user) || $user->id == 'nobody') {
		echo "invalid user.";
                die();
        }

        // check vote and vote_id parameters
	if (!($_REQUEST['vote']=='+1' || $_REQUEST['vote']=='-1') || !preg_match("/[a-z0-9]{32}/",$_REQUEST['vote_id'])) {
		echo "invalid parms.";
		die();
	}

        // check markup
        if ($_REQUEST['markup']=='+1') {
		$markup='+1';
        } else if ($_REQUEST['markup']=='+1-1') {
                $markup='+1-1';
        } else {
                $markup='+1-1';
        }

	$st = DBManager::get()->prepare("INSERT IGNORE INTO plus1minus1_vote_user (vote_id, user_id, vote) VALUES (?,?,?)");
	$st->execute(array($_REQUEST['vote_id'], $user->id, $_REQUEST['vote']));

        echo Plus1Minus1Plugin::getWidget($markup, $_REQUEST['vote_id'], $user->id);
        die();
    }

    static function getWidget($markup, $vote_id, $user_id=NULL) {
        global $user;

        // count + and -
        $st = DBManager::get()->prepare("SELECT COUNT(*) FROM plus1minus1_vote_user WHERE vote_id=? AND vote=?");
        $st->execute(array($vote_id, '+1'));
        $plus = $st->fetchColumn();
        $st->execute(array($vote_id, '-1'));
        $minus = $st->fetchColumn();

        // $st = DBManager::get()->prepare("SELECT user_id,vote FROM plus1minus1_vote_user WHERE vote_id=? LIMIT 10");
        // $st->execute(array($vote_id));
        // $users=array();
        // while ($row = $st->fetch(PDO::FETCH_ASSOC)) {
	// 	$users[] = $row['vote'].' <a href="about.php?username='.get_username($row['user_id']).'">'.htmlReady(get_fullname($row["user_id"]))."</a>";
        // }

        // print_r($users);
        // $users_title = implode("<br/>",$users);

        // nice vote icon in front
        // $out="<img src='".$GLOBALS['ASSETS_URL']."images/icons/16/black/vote.png' style='margin-bottom:-3px; margin-right:5px;' class='plus1minus1listvotes' title='".$users_title."'>";
        $out="<img src='".$GLOBALS['ASSETS_URL']."images/icons/16/black/vote.png' style='margin-bottom:-3px; margin-right:5px;'>";

        if (!is_object($user) || $user->id == 'nobody') {
            // nobody and anonymous can't vote
            if ($markup=='+1-1') {
		    $out .= '+%d &nbsp; -%d';
            } else if ($markup=='+1') {
		    $out .= '+%d';
            }
        } else {
            // users may vote if they have not 
            $st = DBManager::get()->prepare("SELECT vote FROM plus1minus1_vote_user WHERE vote_id=? AND user_id=?");
            $st->execute(array($vote_id, $user->id));
            if ($st->rowCount() > 0) { // already voted
                $vote = $st->fetchColumn();
                if ($markup=='+1-1') {
			$out .= ($vote=='+1' ? '<b>+%d</b> &nbsp; ' : '+%d &nbsp; ');
			$out .= ($vote=='-1' ? '<b>-%d</b>' : '-%d');
                } else if ($markup=='+1') {
			$out .= ($vote=='+1' ? '<b>+%d</b>' : '+%d');
                }
            } else { // not voted yet
                if ($markup=='+1-1') {
                    $out .= '<a href="#" class="plus1minus1vote" data-vote="+1" data-voteid="'.$vote_id.'" data-markup="+1-1">+1</a> (%d)  &nbsp; ';
                    $out .= '<a href="#" class="plus1minus1vote" data-vote="-1" data-voteid="'.$vote_id.'" data-markup="+1-1">-1</a> (%d)';
                } else if ($markup=='+1') {
                    $out .= '<a href="#" class="plus1minus1vote" data-vote="+1" data-voteid="'.$vote_id.'" data-markup="+1">+1</a> (%d)';
                }
            }
        }
	if ($markup=='+1-1') {
		return sprintf($out, $plus, $minus);
	} else if ($markup=='+1') {
		return sprintf($out, $plus);
        }
    }

    static function transformMarkupPlus1Minus1($markup, $matches, $contents)
    {
        return "\[".$matches[1].":".md5(uniqid("Plus1Minus1Plugin::transformMarkupPlus1Minus1"))."\]";
    }

    static function markupPlus1Minus1($markup, $matches, $contents)
    {
        // create a widget for given id (md5 hash - ensured by markup regex)
        return "<span class='plus1minus1widget'>".Plus1Minus1Plugin::getWidget($matches[1], $matches[2])."</span>";
        # return sprintf('<a href="https://develop.studip.de/trac/search?q=%s">%s</a>', htmlReady($matches[0]), htmlReady($matches[0]));
    }
}
