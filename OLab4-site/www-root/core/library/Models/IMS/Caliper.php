<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 *
 * Entrada is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Entrada is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Entrada.  If not, see <http://www.gnu.org/licenses/>.
 *
 * A model for handling communities.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Frederic Turmel <ft11@queensu.ca>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 */
require_once "Caliper/entities/reading/EPubVolume.php";
require_once "Caliper/entities/reading/EPubChapter.php";
require_once "Caliper/entities/reading/EPubPart.php";
require_once "Caliper/entities/reading/Frame.php";
require_once "Caliper/entities/reading/Reading.php";
require_once "Caliper/entities/assessment/Assessment.php";
require_once "Caliper/entities/agent/Person.php";
require_once "Caliper/entities/agent/SoftwareApplication.php";
require_once "Caliper/entities/session/Session.php";
require_once "Caliper/events/SessionEvent.php";
require_once "Caliper/events/ReadingEvent.php";
require_once "Caliper/events/AssignableEvent.php";
require_once "Caliper/events/AssessmentEvent.php";
require_once "Caliper/actions/Action.php";
require_once "Caliper/entities/EntityType.php";
require_once "Classes/mspr/Observership.class.php";

class Models_IMS_Caliper {
    protected $stat,
        $timestamp,
        $module,
        $action,
        $action_field,
        $action_value,
        $proxy_id,
        $number,
        $firstname,
        $lastname,
        $email,
        $entities;


    /**
     * Return an array of Caliper entities created during the Caliper events creation.
     * To be used with the describe function
     *
     * @return array
     */
    public function getEntity() {
        return $this->entities;
    }

    /**
     * Parse an Entrada statistic line and set the class variables value accordingly
     *
     * @param $stat
     * @return bool
     */
    public function setStat($stat) {
        if( ! is_array($stat) || ! isset($stat["timestamp"])
            || ! isset($stat["module"])
            || ! isset($stat["action"])
            || ! isset($stat["action_field"])
            || ! isset($stat["proxy_id"])
            || ! isset($stat["firstname"])
            || ! isset($stat["lastname"]) ) {
            return false;
        }

        $date = new DateTime();
        $date->setTimestamp($stat["timestamp"]);

        $this->stat         = $stat;
        $this->timestamp    = $date;
        $this->module       = $stat["module"];
        $this->action       = $stat["action"];
        $this->action_field = $stat["action_field"];
        $this->action_value = $stat["action_value"];
        $this->proxy_id     = $stat["proxy_id"];
        $this->number       = $stat["number"];
        $this->firstname    = $stat["firstname"];
        $this->lastname     = $stat["lastname"];
        $this->email        = $stat["email"];

        $this->entities     = array();

        return true;
    }

    public function __construct($stat = NULL) {
        if (isset($stat) ) {
            $this->setStat($stat);
        }
    }

    /**
     * Create and return a Caliper Person object to use as actor.
     *
     * @return Person
     */
    private function getPerson() {
        $personObj = new Person($this->proxy_id);
        $personObj->setDateCreated($this->timestamp)
            ->setDateModified($this->timestamp)
            ->setName($this->firstname . " " . $this->lastname);

        $this->entities[] = $personObj;

        return $personObj;
    }

    /**
     * Prepare and return a Caliper software application object
     *
     * @return SoftwareApplication
     */
    private function getApplication() {
        $appObj = new SoftwareApplication('https://example.com/viewer');
        $appObj->setName(APPLICATION_NAME . " v" . APPLICATION_VERSION)
            ->setDateCreated($this->timestamp)
            ->setDateModified($this->timestamp);

        return $appObj;
    }


    /**
     * This function parse the statistics line passed to the class and get the appropriate
     * Caliper Event object
     *
     * @return Event
     */
    public function getEvent() {
        $eventObj = "";

        if ($this->module == "index") {
            /**
             * Login
             */
            $eventObj = $this->getSessionEvent();
        } else if (substr($this->module, 0, strlen("community:")) == "community:") {
            /**
             * Communities related events
             */
            $eventObj = $this->getCommunityEvent();
        } else if ($this->module == "rss") {
            /**
             * RSS feed view
             */
            $eventObj = $this->getRssEvent();
        } else if ($this->module == "podcasts") {
            /**
             * Podcasts
             */
            $eventObj = $this->getPodcastEvent();
        } else if ($this->module == "events" && $this->action_field != "aquiz_id") {
            /**
             * Events
             */
            $eventObj = $this->getEventsEvent();
        } else if ($this->module == "events" && $this->action_field == "aquiz_id") {
            /**
             * Quiz, somehow logged as events...
             */
            $eventObj = $this->getQuizEvent();
        } else if ($this->module == "evaluation" || $this->module == "evaluations") {
            /**
             * Evaluations
             */
            $eventObj = $this->getEvaluationEvent();
        } else if ($this->module == "community_events") {
            /**
             * Community Event
             */
            $eventObj = $this->getCommunityEventsEvent();
        } else if ($this->module == "community_polling") {
            /**
             * Community Polling
             */
            $eventObj = $this->getCommunityPollingEvent();
        } else if ($this->module == "courses") {
            /**
             * Courses
             */
            $eventObj = $this->getCoursesEvent();
        } else if ($this->module == "encounter_tracking") {
            /**
             * Encounter tracking view
             */
            $eventObj = $this->getEncounterEvent();
        } else if ($this->module == "calendar.api") {
            /**
             * ICS Calendar
             */
            $eventObj = $this->getCalendarEvent();
        } else if (substr($this->module, 0, strlen("assignment:")) == "assignment:") {
            /**
             * Assignments
             */
            $eventObj = $this->getAssignmentEvent();
        } else if ($this->module == "gradebook") {
            /**
             * Gradebook
             */
            $eventObj = $this->getGradeBookEvent();
        } else if ($this->module == "notices") {
            /**
             * Notices
             */
            $eventObj = $this->getNoticesEvent();
        } else if ($this->module == "eportfolio") {
            /**
             * ePortfolio
             */
            $eventObj = $this->getEPortfolioEvent();
        } else if ($this->module == "observerships") {
            /**
             * Observerships
             */
            $eventObj = $this->getObservershipsEvent();
        }

        return $eventObj;
    }


    /**
     * Function that prepare and return a Caliper session event based on the
     * statistic entry
     *
     * @return SessionEvent
     */
    private function getSessionEvent() {
        $person = $this->getPerson();
        $app    = $this->getApplication();

        $generatedObj = new Session("session-" . $this->proxy_id . "-" . $this->stat["timestamp"]);
        $generatedObj->setName("session-" . $this->proxy_id . "-" . $this->stat["timestamp"])
            ->setDateCreated($this->timestamp)
            ->setDateModified($this->timestamp)
            ->setActor($person)
            ->setStartedAtTime($this->timestamp);

        $targetObj = new Frame(ENTRADA_URL."/index.php");
        $targetObj->setName('Index')
            ->setDateCreated($this->timestamp)
            ->setDateModified($this->timestamp)
            ->setVersion(APPLICATION_VERSION);

        $action = ($this->action == "logout") ? new Action(ACTION::LOGGED_OUT) : new Action(Action::LOGGED_IN);

        $sessionEvent = new SessionEvent();
        $sessionEvent->setAction($action)
            ->setActor($person)
            ->setObject($app)
            ->setTarget($targetObj)
            ->setGenerated($generatedObj);

        return $sessionEvent;
    }

    /**
     * Method that Prepare and return a Caliper reading event for the communities
     *
     * @return bool|ReadingEvent
     */
    private function getCommunityEvent() {
        $module = explode(":", $this->module);

        /**
         * Check the arguments count
         */
        if (count($module) < 3) {
            application_log("error", "Unexpected format of module in statistic entry: ".$this->module);
            return false;
        }

        /**
         * Check if 2nd argument is a valid ID
         */
        $module[1] = (int) $module[1];
        if (!$module[1]) {
            application_log("error", "Invalid community ID in statistic entry: ".$this->module);
            return false;
        }

        if (!($community = Models_Community::fetchRowByID($module[1]))) {
            application_log("error", "Failed to load community fo ID : ".$module[1]);
            return false;
        }

        $ePubVolume = new EPubVolume(ENTRADA_URL . $community->getUrl());
        $ePubVolume->setName($community->getTitle())
            ->setDescription($community->getDescription())
            ->setDateCreated($this->timestamp)
            ->setDateModified($this->timestamp);

        $this->entities[] = $ePubVolume;

        switch ($module[2]) {
            case "annoucements":
                $ePubPart = new EPubPart(ENTRADA_URL . $community->getUrl() . ":announcements");
                $ePubPart->setName("Announcements")
                    ->setDateCreated($this->timestamp)
                    ->setDateModified($this->timestamp)
                    ->setIsPartOf($ePubVolume);

                $this->entities[] = $ePubPart;

                $announcement = Models_Community_Announcements::fetchRowByID($this->action_value);
                $ePubChapter = new EPubChapter($announcement->getID());
                $ePubChapter->setName($announcement->getAnnouncementTitle())
                    ->setDateCreated($this->timestamp)
                    ->setDateModified($this->timestamp)
                    ->setIsPartOf($ePubPart);

                $this->entities[] = $ePubChapter;
                break;

            case "discussions":
                $ePubPart = new EPubPart(ENTRADA_URL . $community->getUrl() . ":disscussions");
                $ePubPart->setName("Discussions")
                    ->setDateCreated($this->timestamp)
                    ->setDateModified($this->timestamp)
                    ->setIsPartOf($ePubPart);

                $this->entities[] = $ePubPart;

                switch ($this->action_field) {
                    case "cdfile_id":
                        $file = Models_Community_Discussion_File::fetchRowByID($this->action_value);
                        $discussion = Models_Community_Discussion::fetchRowByID($file->getCdiscussionID());

                        $ePubChapter = new EPubChapter($discussion->getCDiscussionID());
                        $ePubChapter->setName($discussion->getForumTitle())
                            ->setDateCreated($this->timestamp)
                            ->setDateModified($this->timestamp)
                            ->setIsPartOf($ePubVolume);

                        $this->entities[] = $ePubChapter;

                        $ePubFrame = new Frame("file-" . $file->getID());
                        $ePubFrame->setName($file->getFileTitle())
                            ->setDateCreated($this->timestamp)
                            ->setDateModified($this->timestamp)
                            ->setIsPartOf($ePubChapter);

                        $this->entities[] = $ePubFrame;
                        break;

                    case "cdiscussion_id":
                        $discussion = Models_Community_Discussion::fetchRowByID($this->action_value);

                        $ePubChapter = new EPubChapter($discussion->getCDiscussionID());
                        $ePubChapter->setName($discussion->getForumTitle())
                            ->setDateCreated($this->timestamp)
                            ->setDateModified($this->timestamp)
                            ->setIsPartOf($ePubVolume);

                        $this->entities[] = $ePubChapter;
                        break;

                    case "cdtopic_id":
                        $topic = Models_Community_Discussion_Topics::fetchRowByID($this->action_value);
                        $discussion = Models_Community_Discussion::fetchRowByID($topic->getCdiscussionID());

                        $ePubChapter = new EPubChapter($discussion->getCDiscussionID());
                        $ePubChapter->setName($discussion->getForumTitle())
                            ->setDateCreated($this->timestamp)
                            ->setDateModified($this->timestamp)
                            ->setIsPartOf($ePubVolume);

                        $this->entities[] = $ePubChapter;

                        $ePubFrame = new Frame("topic-" . $topic->getID());
                        $ePubFrame->setName($topic->getTopicTitle())
                            ->setDateCreated($this->timestamp)
                            ->setDateModified($this->timestamp)
                            ->setIsPartOf($ePubChapter);

                        $this->entities[] = $ePubFrame;
                        break;
                }
                break;

            case "events":
                $event = Models_Community_Events::fetchRowByID($this->action_value);
                $ePubPart = new EPubPart(ENTRADA_URL . $community->getUrl() . ":events");
                $ePubPart->setName("Community Events")
                    ->setDateCreated($this->timestamp)
                    ->setDateModified($this->timestamp)
                    ->setIsPartOf($ePubVolume);

                $this->entities[] = $ePubPart;

                if ($event) {
                    $ePubChapter = new EPubChapter($this->action_value);
                    $ePubChapter->setName($event->getEventTitle())
                        ->setDateCreated($this->timestamp)
                        ->setDateModified($this->timestamp)
                        ->setIsPartOf($ePubVolume);

                    $this->entities[] = $ePubChapter;
                }


                break;

            case "galleries":
                $ePubPart = new EPubPart(ENTRADA_URL . $community->getUrl() . ":galleries");
                $ePubPart->setName("Community Galleries")
                    ->setDateCreated($this->timestamp)
                    ->setDateModified($this->timestamp)
                    ->setIsPartOf($ePubVolume);

                $this->entities[] = $ePubPart;

                switch ($this->action_field) {
                    case "cgallery_id":
                        $gallery = Models_Community_Galleries::fetchRowByID($this->action_value);

                        $ePubChapter = new EPubChapter($this->action_value);
                        $ePubChapter->setName($gallery->getGalleryTitle())
                            ->setDateCreated($this->timestamp)
                            ->setDateModified($this->timestamp)
                            ->setIsPartOf($ePubVolume);

                        $this->entities[] = $ePubChapter;
                        break;

                    case "cgcomment_id":
                        $comment = Models_Community_Gallery_Comments::fetchRowByID($this->action_value);
                        $gallery = Models_Community_Galleries::fetchRowByID($comment->getCgalleryID());

                        $ePubChapter = new EPubChapter($gallery->getID());
                        $ePubChapter->setName($gallery->getGalleryTitle())
                            ->setDateCreated($this->timestamp)
                            ->setDateModified($this->timestamp)
                            ->setIsPartOf($ePubVolume);

                        $this->entities[] = $ePubChapter;

                        $ePubFrame = new Frame("comment-" . $comment->getId());
                        $ePubFrame->setName($comment->getCommentTitle())
                            ->setDateCreated($this->timestamp)
                            ->setDateModified($this->timestamp)
                            ->setIsPartOf($ePubChapter);

                        $this->entities[] = $ePubFrame;
                        break;

                    case "cgphoto_id":
                        $photo = Models_Community_Gallery_Photos::fetchRowByID($this->action_value);
                        $gallery = Models_Community_Galleries::fetchRowByID($photo->getCgalleryID());

                        $ePubChapter = new EPubChapter($gallery->getID());
                        $ePubChapter->setName($gallery->getGalleryTitle())
                            ->setDateCreated($this->timestamp)
                            ->setDateModified($this->timestamp)
                            ->setIsPartOf($ePubVolume);

                        $this->entities[] = $ePubChapter;

                        $ePubFrame = new Frame("photo-" . $photo->getId());
                        $ePubFrame->setName($photo->getPhotoTitle())
                            ->setDateCreated($this->timestamp)
                            ->setDateModified($this->timestamp)
                            ->setIsPartOf($ePubChapter);

                        $this->entities[] = $ePubFrame;
                        break;
                }

            case "shares":
                $ePubPart = new EPubPart(ENTRADA_URL . $community->getUrl() . ":shares");
                $ePubPart->setName("Community Shares")
                    ->setDateCreated($this->timestamp)
                    ->setDateModified($this->timestamp)
                    ->setIsPartOf($ePubVolume);

                $this->entities[] = $ePubPart;

                switch ($this->action_field) {
                    case "cscomment_id":
                        $comment = Models_Community_Share_Comments::fetchRowByID($this->action_value);
                        $share = Models_Community_Share::fetchRowByCShareID($comment->getCshareID());

                        $ePubChapter = new EPubChapter("share-" . $share->getCShareID());
                        $ePubChapter->setName($share->getFolderTitle())
                            ->setDateCreated($this->timestamp)
                            ->setDateModified($this->timestamp)
                            ->setIsPartOf($ePubVolume);

                        $this->entities[] = $ePubChapter;

                        $ePubFrame = new Frame("comment-" . $comment->getId());
                        $ePubFrame->setName($comment->getCommentTitle())
                            ->setDateCreated($this->timestamp)
                            ->setDateModified($this->timestamp)
                            ->setIsPartOf($ePubChapter);

                        $this->entities[] = $ePubFrame;
                        break;

                    case "csfile_id":
                        $file = Models_Community_Share_File::fetchRowByID($this->action_value);
                        $share = Models_Community_Share::fetchRowByCShareID($file->getCShareID());

                        $ePubChapter = new EPubChapter("share-" . $share->getId());
                        $ePubChapter->setName($share->getFolderTitle())
                            ->setDateCreated($this->timestamp)
                            ->setDateModified($this->timestamp)
                            ->setIsPartOf($ePubVolume);

                        $this->entities[] = $ePubChapter;

                        $ePubFrame = new Frame("file-" . $file->getId());
                        $ePubFrame->setName($file->getFileTitle())
                            ->setDateCreated($this->timestamp)
                            ->setDateModified($this->timestamp)
                            ->setIsPartOf($ePubChapter);

                        $this->entities[] = $ePubFrame;
                        break;

                    case "csfversion_id":
                        $file = Models_Community_Share_File_Version::fetchRowByID($this->action_value);
                        $share = Models_Community_Share::fetchRowByCShareID($file->getShareID());

                        $ePubChapter = new EPubChapter("share-" . $share->getId());
                        $ePubChapter->setName($share->getFolderTitle())
                            ->setDateCreated($this->timestamp)
                            ->setDateModified($this->timestamp)
                            ->setIsPartOf($ePubVolume);

                        $this->entities[] = $ePubChapter;

                        $ePubFrame = new Frame("file-version-" . $file->getId());
                        $ePubFrame->setName($file->getFileVersion())
                            ->setDateCreated($this->timestamp)
                            ->setDateModified($this->timestamp)
                            ->setIsPartOf($ePubChapter);

                        $this->entities[] = $ePubFrame;
                        break;

                    case "cshare_id":
                        $share = Models_Community_Share::fetchRowByCShareID($this->action_value);

                        $ePubChapter = new EPubChapter("share-" . $share->getCShareID());
                        $ePubChapter->setName($share->getFolderTitle())
                            ->setDateCreated($this->timestamp)
                            ->setDateModified($this->timestamp)
                            ->setIsPartOf($ePubVolume);

                        $this->entities[] = $ePubChapter;
                        break;

                    case "cshtml_id":
                        $html = Models_Community_Share_Html::fetchRowByID($this->action_value);
                        $share = Models_Community_Share::fetchRowByCShareID($html->getCShareID());

                        $ePubChapter = new EPubChapter("share-" . $share->getId());
                        $ePubChapter->setName($share->getFolderTitle())
                            ->setDateCreated($this->timestamp)
                            ->setDateModified($this->timestamp)
                            ->setIsPartOf($ePubVolume);

                        $this->entities[] = $ePubChapter;

                        $ePubFrame = new Frame("html-" . $html->getId());
                        $ePubFrame->setName($html->getHtmlTitle())
                            ->setDateCreated($this->timestamp)
                            ->setDateModified($this->timestamp)
                            ->setIsPartOf($ePubChapter);

                        $this->entities[] = $ePubFrame;
                        break;

                    case "cslink_id":
                        $link = Models_Community_Share_Link::fetchRowByID($this->action_value);
                        $share = Models_Community_Share::fetchRowByCShareID($link->getCShareID());

                        $ePubChapter = new EPubChapter("share-" . $share->getId());
                        $ePubChapter->setName($share->getFolderTitle())
                            ->setDateCreated($this->timestamp)
                            ->setDateModified($this->timestamp)
                            ->setIsPartOf($ePubVolume);

                        $this->entities[] = $ePubChapter;

                        $ePubFrame = new Frame("link-" . $link->getId());
                        $ePubFrame->setName($link->getLinkTitle())
                            ->setDateCreated($this->timestamp)
                            ->setDateModified($this->timestamp)
                            ->setIsPartOf($ePubChapter);

                        $this->entities[] = $ePubFrame;
                        break;
                }
                break;
        }

        if ( !($person = $this->getPerson()) ) {
            return false;
        }

        if ( !($app = $this->getApplication()) ) {
            return false;
        }

        $generatedObj = new Session("session-" . $this->proxy_id . "-" . $this->stat["timestamp"]);
        $generatedObj->setName("session-" . $this->proxy_id . "-" . $this->stat["timestamp"])
            ->setDateCreated($this->timestamp)
            ->setDateModified($this->timestamp)
            ->setActor($person)
            ->setStartedAtTime($this->timestamp);

        $targetObj = new Frame($ePubVolume->getId());
        $targetObj->setName($ePubVolume->getName())
            ->setDateCreated($this->timestamp)
            ->setDateModified($this->timestamp)
            ->setVersion(APPLICATION_VERSION);

        if (strstr($this->action, "view")) {
            $readingEvent = new ReadingEvent();
            $readingEvent->setAction(new Action(Action::VIEWED))
                ->setActor($person)
                ->setObject($app)
                ->setTarget($targetObj)
                ->setGenerated($generatedObj);

            return $readingEvent;
        }

        return false;
    }

    /**
     * Prepare and return a Caliper reading event for Rss feed views
     *
     * @return ReadingEvent
     */
    private function getRssEvent() {
        $person = $this->getPerson();
        $app    = $this->getApplication();

        $ePubVolume = new EPubVolume(ENTRADA_URL . '/rss/');
        $ePubVolume->setName(APPLICATION_NAME . " RSS notices for " . $this->firstname . ' ' . $this->lastname)
            ->setDescription("Announcements, Schedule Changes, Updates and more")
            ->setDateCreated($this->timestamp)
            ->setDateModified($this->timestamp);

        $this->entities[] = $ePubVolume;

        $generatedObj = new Session("session-" . $this->proxy_id . "-" . $this->stat["timestamp"]);
        $generatedObj->setName("session-" . $this->proxy_id . "-" . $this->stat["timestamp"])
            ->setDateCreated($this->timestamp)
            ->setDateModified($this->timestamp)
            ->setActor($person)
            ->setStartedAtTime($this->timestamp);

        $targetObj = new Frame($ePubVolume->getId());
        $targetObj->setName($ePubVolume->getName())
            ->setDateCreated($this->timestamp)
            ->setDateModified($this->timestamp)
            ->setVersion(APPLICATION_VERSION);

        $readingEvent = new ReadingEvent();
        $readingEvent->setAction(new Action(Action::VIEWED))
            ->setActor($person)
            ->setObject($app)
            ->setTarget($targetObj)
            ->setGenerated($generatedObj);

        return $readingEvent;
    }

    /**
     * Prepare and return Caliper event for podcasts action
     *
     * @return EPubVolume|MediaObject
     */
    private function getPodcastEvent() {
        $person = $this->getPerson();
        $app    = $this->getApplication();

        $generatedObj = new Session("session-" . $this->proxy_id . "-" . $this->stat["timestamp"]);
        $generatedObj->setName("session-" . $this->proxy_id . "-" . $this->stat["timestamp"])
            ->setDateCreated($this->timestamp)
            ->setDateModified($this->timestamp)
            ->setActor($person)
            ->setStartedAtTime($this->timestamp);
        
        if ($this->action_field == "file_download") {
            $file = Models_Event_Resource_File::fetchRowByID($this->action_value);
            
            $eMedia = new  AudioObject($file->getID());
            $eMedia->setName($file->getFileTitle())
                ->setDateCreated($this->timestamp)
                ->setDateModified($this->timestamp);

            $this->entities[] = $eMedia;

            $targetObj = new Frame($eMedia->getId());
            $targetObj->setName($eMedia->getName())
                ->setDateCreated($this->timestamp)
                ->setDateModified($this->timestamp)
                ->setVersion(APPLICATION_VERSION);

            $mediaEvent = new MediaEvent($eMedia->getId());
            $mediaEvent->setAction(new Action(Action::STARTED))
                ->setActor($person)
                ->setObject($app)
                ->setTarget($targetObj)
                ->setGenerated($generatedObj);

            return $mediaEvent;
        } else {
            $ePubVolume = new EPubVolume(ENTRADA_URL."/podcasts");
            $ePubVolume->setName($this->firstname . " " . $this->lastname . "'s School of Medicine Podcasts")
                ->setDescription("Learning event podcasts from the School of Medicine at Queen's University")
                ->setDateCreated($this->timestamp)
                ->setDateModified($this->timestamp);

            $this->entities[] = $ePubVolume;

            $targetObj = new Frame($ePubVolume->getId());
            $targetObj->setName($ePubVolume->getName())
                ->setDateCreated($this->timestamp)
                ->setDateModified($this->timestamp)
                ->setVersion(APPLICATION_VERSION);
        }
    }

    /**
     * Generate and return a caliper reading event for events related actions
     *
     * @return ReadingEvent
     */
    private function getEventsEvent() {
        $person = $this->getPerson();
        $app    = $this->getApplication();

        if ($this->action =="view") {
            $event = Models_Event::get($this->action_value);

            $ePubVolume = new EPubVolume(ENTRADA_URL . "/events/rid=" . $event->getID());
            $ePubVolume->setName($event->getEventTitle())
                ->setDescription($event->getEventDescription())
                ->setDateCreated($this->timestamp)
                ->setDateModified($this->timestamp);

            $this->entities[] = $ePubVolume;
        } else if ($this->action == "file_download") {
            $file = Models_Event_Resource_File::fetchRowByID($this->action_value);
            $event = Models_Event::get($file->getEventID());

            $ePubVolume = new EPubVolume(ENTRADA_URL . "/events/rid=" . $event->getID());
            $ePubVolume->setName($event->getEventTitle())
                ->setDescription($event->getEventDescription())
                ->setDateCreated($this->timestamp)
                ->setDateModified($this->timestamp);

            $this->entities[] = $ePubVolume;

            $ePubFrame = new Frame("file-" . $file->getID());
            $ePubFrame->setName($file->getFileTitle())
                ->setDateCreated($this->timestamp)
                ->setDateModified($this->timestamp)
                ->setIsPartOf($ePubVolume);

            $this->entities[] = $ePubFrame;
        } else if ($this->action == "link_access") {
            $link = Models_Event_Resource_Link::fetchRowByID($this->action_value);
            $event = Models_Event::get($link->getEventID());

            $ePubVolume = new EPubVolume(ENTRADA_URL . "/events/rid=" . $event->getID());
            $ePubVolume->setName($event->getEventTitle())
                ->setDescription($event->getEventDescription())
                ->setDateCreated($this->timestamp)
                ->setDateModified($this->timestamp);

            $this->entities[] = $ePubVolume;

            $ePubFrame = new Frame("link-" . $link->getID());
            $ePubFrame->setName($link->getFileTitle())
                ->setDateCreated($link->timestamp)
                ->setDateModified($link->timestamp)
                ->setIsPartOf($ePubVolume);

            $this->entities[] = $ePubFrame;
        }

        $generatedObj = new Session("session-" . $this->proxy_id . "-" . $this->stat["timestamp"]);
        $generatedObj->setName("session-" . $this->proxy_id . "-" . $this->stat["timestamp"])
            ->setDateCreated($this->timestamp)
            ->setDateModified($this->timestamp)
            ->setActor($person)
            ->setStartedAtTime($this->timestamp);

        $targetObj = new Frame($ePubVolume->getId());
        $targetObj->setName($ePubVolume->getName())
            ->setDateCreated($this->timestamp)
            ->setDateModified($this->timestamp)
            ->setVersion(APPLICATION_VERSION);

        $readingEvent = new ReadingEvent();
        $readingEvent->setAction(new Action(Action::VIEWED))
            ->setActor($person)
            ->setObject($app)
            ->setTarget($targetObj)
            ->setGenerated($generatedObj);

        return $readingEvent;
    }

    /**
     * Create and return a caliper assessment event for Quiz related action
     * @return AssessmentEvent
     */
    private function getQuizEvent() {
        $person = $this->getPerson();

        $progress = Models_Quiz_Progress::fetchRowByAQuizID($this->action_value);
        $quiz = Models_Quiz::fetchRowByID($progress->getQuizID());

        $eAssessment = new Assessment($quiz->getQuizID());
        $eAssessment->setName($quiz->getQuizTitle())
            ->setDescription($quiz->getQuizDescription())
            ->setDateModified($this->timestamp)
            ->setVersion(APPLICATION_VERSION);

        if ($this->action == "quiz_view") {
            $action = ACTION::VIEWED;
        } else {
            $action = ACTION::COMPLETED;
        }

        $assessmentEvent = new AssessmentEvent();
        $assessmentEvent->setAction(new Action($action))
            ->setActor($person)
            ->setObject($eAssessment);

        return $assessmentEvent;
    }

    /**
     * Create and return a caliper assessment event for assessment related actions
     *
     * @return AssessmentEvent
     */
    private function getEvaluationEvent() {
        global $db;
        $person = $this->getPerson();

        $query = "SELECT *  
                  FROM `evaluations`  
                  WHERE evaluation_id=" . $db->qstr($this->action_value);
        $eval = $db->getRow($query);

        $eAssessment = new Assessment($eval["evaluation_id"]);
        $eAssessment->setName($eval["evaluation_title"])
            ->setDescription($eval["evaluation_description"])
            ->setDateModified($this->timestamp)
            ->setVersion(APPLICATION_VERSION);

        if ($this->action == "evaluation_view") {
            $action = ACTION::VIEWED;
        } else {
            $action = ACTION::COMPLETED;
        }

        $assessmentEvent = new AssessmentEvent();
        $assessmentEvent->setAction(new Action($action))
            ->setActor($person)
            ->setObject($eAssessment);

        return $assessmentEvent;
    }

    /**
     * Create and return caliper reading event for community events view
     *
     * @return ReadingEvent
     */
    private function getCommunityEventsEvent() {
        $person = $this->getPerson();
        $app    = $this->getApplication();

        $event = Models_Community_Events::fetchRowByID($this->action_value);
        $community = Models_Community::fetchRowByID($event->getCommunityID());

        $ePubVolume = new EPubVolume(ENTRADA_URL . $community->getUrl());
        $ePubVolume->setName($community->getTitle())
            ->setDescription($community->getDescription())
            ->setDateCreated($this->timestamp)
            ->setDateModified($this->timestamp);

        $this->entities[] = $ePubVolume;

        $ePubChapter = new EPubChapter($event->getID());
        $ePubChapter->setName($event->getEventTitle())
            ->setDescription($event->getEventDescription())
            ->setDateCreated($this->timestamp)
            ->setDateModified($this->timestamp)
            ->setIsPartOf($ePubVolume);

        $this->entities[] = $ePubChapter;

        $generatedObj = new Session("session-" . $this->proxy_id . "-" . $this->stat["timestamp"]);
        $generatedObj->setName("session-" . $this->proxy_id . "-" . $this->stat["timestamp"])
            ->setDateCreated($this->timestamp)
            ->setDateModified($this->timestamp)
            ->setActor($person)
            ->setStartedAtTime($this->timestamp);

        $targetObj = new Frame($ePubVolume->getId());
        $targetObj->setName($ePubVolume->getName())
                ->setDateCreated($this->timestamp)
                ->setDateModified($this->timestamp)
                ->setVersion(APPLICATION_VERSION);

        $readingEvent = new ReadingEvent();
        $readingEvent->setAction(new Action(Action::VIEWED))
            ->setActor($person)
            ->setObject($app)
            ->setTarget($targetObj)
            ->setGenerated($generatedObj);

        return $readingEvent;
    }

    /**
     * Create and return a caliper assessment event for community polling
     *
     * @return AssessmentEvent
     */
    private function getCommunityPollingEvent() {
        $person = $this->getPerson();

        $poll = Models_Community_Polls::fetchRowByID($this->action_value);

        $eAssessment = new Assessment($poll->getID());
        $eAssessment->setName($poll->getPollTitle())
            ->setDescription($poll->getPollDescription())
            ->setDateCreated($this->timestamp)
            ->setDateModified($this->timestamp);

        $this->entities[] = $eAssessment;

        if ($this->action == "poll_view") {
            $action = ACTION::VIEWED;
        } else {
            $action = ACTION::COMPLETED;
        }

        $assessmentEvent = new AssessmentEvent();
        $assessmentEvent->setAction(new Action($action))
            ->setActor($person)
            ->setObject($eAssessment);

        return $assessmentEvent;
    }

    /**
     * Create and return a caliper reading event for Courses
     *
     * @return ReadingEvent
     */
    private function getCoursesEvent() {
        $person = $this->getPerson();
        $app    = $this->getApplication();

        if ($this->action == "file_download") {
            $file = Models_Course_Files::fetchRowByID($this->action_value);
            $course = Models_Course::get($file->getCourseID());

            $ePubVolume = new EPubVolume(ENTRADA_URL . "/courses/id=" . $course->getID());
            $ePubVolume->setName($course->getCourseName())
                ->setDescription($course->getCourseDescription())
                ->setDateCreated($this->timestamp)
                ->setDateModified($this->timestamp);

            $this->entities[] = $ePubVolume;

            $ePubFrame = new Frame("file-" . $file->getID());
            $ePubFrame->setName($file->getFileTitle())
                ->setDescription($file->getFileNotes())
                ->setDateCreated($this->timestamp)
                ->setDateModified($this->timestamp)
                ->setIsPartOf($ePubVolume);

            $this->entities[] = $ePubFrame;
        } else if ($this->action == "link_access") {
            $link = Models_Course_Links::fetchRowByID($this->action_value);
            $course = Models_Course::get($link->getCourseID());

            $ePubVolume = new EPubVolume(ENTRADA_URL . "/courses/id=" . $course->getID());
            $ePubVolume->setName($course->getCourseName())
                ->setDescription($course->getCourseDescription())
                ->setDateCreated($this->timestamp)
                ->setDateModified($this->timestamp);

            $this->entities[] = $ePubVolume;

            $ePubFrame = new Frame("link-" . $link->getID());
            $ePubFrame->setName($link->getFileTitle())
                ->setDateCreated($link->timestamp)
                ->setDateModified($link->timestamp)
                ->setIsPartOf($ePubVolume);

            $this->entities[] = $ePubFrame;
        } else if ($this->action == "view") {
            $course = Models_Course::get($this->action_value);

            $ePubVolume = new EPubVolume(ENTRADA_URL . "/courses/id=" . $course->getID());
            $ePubVolume->setName($course->getCourseName())
                ->setDescription($course->getCourseDescription())
                ->setDateCreated($this->timestamp)
                ->setDateModified($this->timestamp);

            $this->entities[] = $ePubVolume;
        }

        $generatedObj = new Session("session-" . $this->proxy_id . "-" . $this->stat["timestamp"]);
        $generatedObj->setName("session-" . $this->proxy_id . "-" . $this->stat["timestamp"])
            ->setDateCreated($this->timestamp)
            ->setDateModified($this->timestamp)
            ->setActor($person)
            ->setStartedAtTime($this->timestamp);

        $targetObj = new Frame($ePubVolume->getId());
        $targetObj->setName($ePubVolume->getName())
            ->setDateCreated($this->timestamp)
            ->setDateModified($this->timestamp)
            ->setVersion(APPLICATION_VERSION);

        $readingEvent = new ReadingEvent();
        $readingEvent->setAction(new Action(Action::VIEWED))
            ->setActor($person)
            ->setObject($app)
            ->setTarget($targetObj)
            ->setGenerated($generatedObj);

        return $readingEvent;
    }

    /**
     * Create and return a caliper reading event for Logbook
     *
     * @return ReadingEvent
     */
    private function getEncounterEvent() {
        $person = $this->getPerson();
        $app    = $this->getApplication();

        $ePubVolume = new EPubVolume(ENTRADA_URL . "/logbook");
        $ePubVolume->setName("Log Book")
            ->setDateCreated($this->timestamp)
            ->setDateModified($this->timestamp);

        $this->entities[] = $ePubVolume;

        $generatedObj = new Session("session-" . $this->proxy_id . "-" . $this->stat["timestamp"]);
        $generatedObj->setName("session-" . $this->proxy_id . "-" . $this->stat["timestamp"])
            ->setDateCreated($this->timestamp)
            ->setDateModified($this->timestamp)
            ->setActor($person)
            ->setStartedAtTime($this->timestamp);

        $targetObj = new Frame($ePubVolume->getId());
        $targetObj->setName($ePubVolume->getName())
            ->setDateCreated($this->timestamp)
            ->setDateModified($this->timestamp)
            ->setVersion(APPLICATION_VERSION);

        $readingEvent = new ReadingEvent();
        $readingEvent->setAction(new Action(Action::VIEWED))
            ->setActor($person)
            ->setObject($app)
            ->setTarget($targetObj)
            ->setGenerated($generatedObj);

        return $readingEvent;
    }

    /**
     * Create and return a caliper reading event for iCal download
     *
     * @return ReadingEvent
     */
    private function getCalendarEvent() {
        $person = $this->getPerson();
        $app    = $this->getApplication();

        $ePubVolume = new EPubVolume(ENTRADA_URL . "/api/calendar.api.php");
        $ePubVolume->setName("ICS Calendar Download")
            ->setDateCreated($this->timestamp)
            ->setDateModified($this->timestamp);

        $this->entities[] = $ePubVolume;

        $generatedObj = new Session("session-" . $this->proxy_id . "-" . $this->stat["timestamp"]);
        $generatedObj->setName("session-" . $this->proxy_id . "-" . $this->stat["timestamp"])
            ->setDateCreated($this->timestamp)
            ->setDateModified($this->timestamp)
            ->setActor($person)
            ->setStartedAtTime($this->timestamp);

        $targetObj = new Frame($ePubVolume->getId());
        $targetObj->setName($ePubVolume->getName())
            ->setDateCreated($this->timestamp)
            ->setDateModified($this->timestamp)
            ->setVersion(APPLICATION_VERSION);

        $readingEvent = new ReadingEvent();
        $readingEvent->setAction(new Action(Action::VIEWED))
            ->setActor($person)
            ->setObject($app)
            ->setTarget($targetObj)
            ->setGenerated($generatedObj);

        return $readingEvent;
    }

    /**
     * Create and return a caliper annotation event for assignment events
     *
     * @return AnnotationEvent|bool
     */
    private function getAssignmentEvent() {
        $person = $this->getPerson();
        $app    = $this->getApplication();

        $module = explode(":", $this->module);
        if ( ! ((int) $module[1]) ) {
            return false;
        }

        $assignment = Models_Assignment::fetchRowByID((int) $module[1]);

        $eAssignment = new AssignableDigitalResource($assignment->getID());
        $eAssignment->setName($assignment->getAssignmentTitle())
            ->setDescription($assignment->getAssignmentDescription())
            ->setDateCreated($this->timestamp)
            ->setDateModified($this->timestamp);

        $this->entities[] = $eAssignment;

        switch ($this->action) {
            case "file_add":
                $file = Models_Assignment_File::fetchRowByID($this->action_value);
                $eAnnotation = new SharedAnnotation($this->action_value);
                $eAnnotation->setName($file->getFileTitle())
                    ->setDescription($file->getFileDescription())
                    ->setDateCreated($this->timestamp)
                    ->setDateModified($this->timestamp);

                $this->entities[] = $eAnnotation;

                $event = new AnnotationEvent();
                $event->setAction(new Action(ACTION::ATTACHED))
                    ->setActor($person)
                    ->setObject($eAssignment)
                    ->setGenerated($eAnnotation)
                    ->setEdApp($app);

                break;

            case "comment_add":
            case "comment_edit":
            case "comment_delete":
                $comment = Models_Assignment_Comments::fetchRowByID($this->action_value);
                $eAnnotation = new SharedAnnotation($this->action_value);
                $eAnnotation->setName($comment->getCommentTitle())
                    ->setDescription($comment->getCommentDescription())
                    ->setDateCreated($this->timestamp)
                    ->setDateModified($this->timestamp);

                $this->entities[] = $eAnnotation;

                $event = new AnnotationEvent();
                $event->setAction(new Action(ACTION::COMMENTED))
                    ->setActor($person)
                    ->setObject($eAssignment)
                    ->setGenerated($eAnnotation)
                    ->setEdApp($app);

                break;

            case "file_zip_download":
                /**
                 * Could not find an appropriate Object/Event for file_zip_download
                 */
                $event = false;
                break;

            default:
                $event = false;
        }

        return $event;
    }

    /**
     * Create and return a caliper event for Gradebook events
     *
     * @return AssessmentEvent
     */
    private function getGradeBookEvent() {
        $person = $this->getPerson();
        $app    = $this->getApplication();
        
        
        $assignment = Models_Gradebook_Assessment::fetchRowByID($this->action_value);

        $eAssignment = new AssignableDigitalResource($assignment->getAssessmentID());
        $eAssignment->setName($assignment->getName())
            ->setDescription($assignment->getDescription())
            ->setDateCreated($this->timestamp)
            ->setDateModified($this->timestamp);

        $this->entities[] = $eAssignment;

        $event = new AssessmentEvent();
        $event->setAction(new Action(ACTION::VIEWED))
            ->setActor($person)
            ->setObject($eAssignment)
            ->setEdApp($app);

        return $event;
    }

    /**
     * Create and return a caliper reading event for Notices events
     *
     * @return bool|ReadingEvent
     */
    private function getNoticesEvent() {
        $person = $this->getPerson();
        $app    = $this->getApplication();

        $ePubVolume = new EPubVolume(ENTRADA_URL . "/notice");
        $ePubVolume->setName("Notices")
            ->setDateCreated($this->timestamp)
            ->setDateModified($this->timestamp);

        $this->entities[] = $ePubVolume;

        $notice = Models_Notice::fetchNotice($this->action_value);
        if( ! $notice ) {
            return false;
        }

        $eFrame = new Frame($this->action_value);
        $eFrame->setName("Notice id " . $notice["notice_id"])
            ->setDescription($notice["notice_summary"])
            ->setDateCreated($this->timestamp)
            ->setDateModified($this->timestamp)
            ->setIsPartOf($ePubVolume);

        $this->entities[] = $eFrame;

        $generatedObj = new Session("session-" . $this->proxy_id . "-" . $this->stat["timestamp"]);
        $generatedObj->setName("session-" . $this->proxy_id . "-" . $this->stat["timestamp"])
            ->setDateCreated($this->timestamp)
            ->setDateModified($this->timestamp)
            ->setActor($person)
            ->setStartedAtTime($this->timestamp);

        $readingEvent = new ReadingEvent();
        $readingEvent->setAction(new Action(Action::VIEWED))
            ->setActor($person)
            ->setObject($app)
            ->setTarget($ePubVolume)
            ->setGenerated($generatedObj);

        return $readingEvent;

    }

    /**
     * Create and return a caliper reading event for ePortfolio events
     *
     * @return bool|ReadingEvent
     */
    private function getEPortfolioEvent() {
        $person = $this->getPerson();
        $app    = $this->getApplication();

        switch ($this->action) {
            case "download_file":
                $pentry_id = (int) $this->action_value;
                if (! $pentry_id) {
                    return false;
                }
                
                $pentry = Models_Eportfolio_Entry::fetchRow($pentry_id);
                $pfartifact = $pentry->getPfartifact();
                $pfolder = $pfartifact->getFolder();
                $portfolio = $pfolder->getPortfolio();

                $ePubVolume = new EPubVolume($portfolio->getID());
                $ePubVolume->setName($portfolio->getPortfolioName())
                    ->setDateCreated($this->timestamp)
                    ->setDateModified($this->timestamp);

                $this->entities[] = $ePubVolume;

                $ePubPart = new EPubPart($pfolder->getID());
                $ePubPart->setName($pfolder->getTitle())
                    ->setDescription($pfolder->getDescription())
                    ->setDateCreated($this->timestamp)
                    ->setDateModified($this->timestamp)
                    ->setIsPartOf($ePubVolume);

                $ePubChapter = new EPubChapter($pfartifact->getID());
                $ePubChapter->setName($pfartifact->getTitle())
                    ->setDescription($pfartifact->getDescription())
                    ->setDateCreated($this->timestamp)
                    ->setDateModified($this->timestamp)
                    ->setIsPartOf($ePubPart);

                $pentry_data = unserialize($pentry->getEdata());

                $eFrame = new Frame($pentry_id);
                $eFrame->setName($pentry_data["filename"])
                    ->setDescription($pentry_data["descrption"])
                    ->setDateCreated($this->timestamp)
                    ->setDateModified($this->timestamp)
                    ->setIsPartOf($ePubChapter);

                $readingEvent = new ReadingEvent();
                $readingEvent->setAction(new Action(Action::VIEWED))
                    ->setActor($person)
                    ->setObject($app)
                    ->setTarget($eFrame)
                    ->setGenerated($ePubVolume);

                return $readingEvent;

                break;
        }
    }

    private function getObservershipsEvent() {
        $person = $this->getPerson();
        $app    = $this->getApplication();

        switch ($this->action) {
            case "review":
                $observership_id = (int) $this->action_value;
                if (! $observership_id) {
                    return false;
                }

                $observership = Observership::get($observership_id);

                $eAssignment = new AssignableDigitalResource("orbservership_" . $observership_id);
                $eAssignment->setName($observership->getTitle())
                    ->setDescription($observership->getObservershipDetails())
                    ->setDateCreated($this->timestamp)
                    ->setDateModified($this->timestamp);

                $this->entities[] = $eAssignment;
                
                $event = new AssignableEvent();
                $event->setAction(new Action(ACTION::REVIEWED))
                    ->setActor($person)
                    ->setEdApp($app)
                    ->setObject($eAssignment);
                
                return $event;
                break;
            
            case "reflection_review":
            case "reflection_edit":
                $observership_id = (int) $this->action_value;
                if (! $observership_id) {
                    return false;
                }

                $observership = Observership::get($observership_id);
                $observership_reflection = ObservershipReflection::get($observership->getReflection());

                $eAssignment = new AssignableDigitalResource("orbservership_" . $observership_id);
                $eAssignment->setName($observership->getTitle())
                    ->setDescription($observership->getObservershipDetails())
                    ->setDateCreated($this->timestamp)
                    ->setDateModified($this->timestamp);

                $this->entities[] = $eAssignment;

                $eAttempt = new Attempt($observership_reflection->getID());
                $eAttempt->setName($observership->getTitle() . " reflections")
                    ->setDateCreated($this->timestamp)
                    ->setDateModified($this->timestamp)
                    ->setAssignable($eAssignment)
                    ->setActor($person);

                $action = ($this->action == "reflection_review") ? Action::REVIEWED : Action::STARTED;

                $event = new AssignableEvent();
                $event->setAction(new Action($action))
                    ->setActor($person)
                    ->setGenerated($eAttempt)
                    ->setEdApp($app)
                    ->setObject($eAttempt);

                return $event;
                break;
        }
    }

    private function getMSPREvent() {
        
    }
}
