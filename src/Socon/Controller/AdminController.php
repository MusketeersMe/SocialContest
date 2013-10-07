<?php

namespace Socon\Controller;

use Phlyty\App;
use Socon\AzureHelper;
use Socon\Controller;
use Socon\Model\EntryAccessorMSSQL;
use Socon\Model\Entry;
use Socon\DB;
use WindowsAzure\Common\ServicesBuilder;
use WindowsAzure\Common\ServiceException;
use Socon\Model\EntryAccessorTable;
use WindowsAzure\ServiceBus\Models\BrokeredMessage;

class AdminController extends Controller
{
    public function init(App $app)
    {
        // check if user is logged in before we proceed
        session_start();
        if (!$this->isAuthenticated()) {
            $app->redirect('/login');
        }

        // prepare common output requirements
        $this->view->page = $this->config->page;
        $this->view->css[] = '/css/admin.css';
        $this->view->js[] = '/js/admin.js';

        // build blob container base url
        $this->view->container_url = sprintf(
            'http://%s.blob.core.windows.net/%s',
            $this->config->azure->storage->name,
            $this->config->azure->storage->image_container
        );
    }

    /**
     * isAuthenticated
     *
     * Checks if user is authenticated
     *
     * @return bool
     */
    protected function isAuthenticated()
    {
        if (isset($_SESSION['auth']) && 1 == $_SESSION['auth']) {
            return true;
        }
        return false;
    }

    /**
     * @param App $app
     */
    public function adminAction(App $app)
    {
        // output HTML
        $app->render('admin/index.phtml', $this->view);
    }

    /**
     * @param App $app
     */
    public function incomingAction(App $app)
    {
        $this->view->js[] = '/js/incoming.js';

        // assume none
        $this->view->entries = false;

        // get entries
        $repo = $this->getRepo();

        if ($repo->getNew()) {
            $this->view->entries = $repo;
        }

        // output HTML
        $app->render('admin/incoming.phtml', $this->view);
    }

    /**
     * @param App $app
     */
    public function approvedAction(App $app)
    {
        // assume none
        $this->view->entries = false;

        // get entries
        $repo = $this->getRepo();

        if ($repo->getApproved()) {
            $this->view->entries = $repo;
        }

         // output HTML
        $app->render('admin/approved.phtml', $this->view);
    }

    /**
     * @param App $app
     */
    public function winnersAction(App $app)
    {
        // assume none
        $this->view->entries = false;

        // get entries
        $repo = $this->getRepo();

        if ($repo->getWinners()) {
            $this->view->entries = $repo;
        }

        $this->view->congratulations_text = $this->config->congratulations_text;

         // output HTML
        $app->render('admin/winners.phtml', $this->view);
    }


    /**
     * @param App $app
     */
    public function deniedAction(App $app)
    {
        // assume none
        $this->view->entries = false;

        // get entries
        $repo = $this->getRepo();

        if ($repo->getDenied()) {
            $this->view->entries = $repo;
        }

         // output HTML
        $app->render('admin/denied.phtml', $this->view);
    }

    /**
     * @param App $app
     */
    public function pickRandomWinnerAction(App $app)
    {
        $post = $app->request()->getPost();
        $result = [];

        if ($post->count()) {
            $type = filter_var($post->get('type'), FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_HIGH);

            // TODO: Separate from time span vs all entries
            if ($type) {
                $repo = $this->getRepo();

                try {
                    $winner = $repo->pickWinnerFromEntries();
                } catch (\Exception $e) {
                    $result['success'] = 0;
                    $result['message'] = "Internal failure trying to pick winner: "
                        . $e->getMessage();
                    $app->response()->getHeaders()->addHeaderLine('Content-Type', 'application/json');
                    $app->response()->setContent(json_encode($result));
                    return;
                }

                if ($winner) {
                    $result['success'] = 1;
                } else {
                    $result['success'] = 0;
                    $result['message'] = "No valid winner found.";
                }
            }
        }
        if (empty($result)) {
            $result['success'] = 0;
            $result['message'] = "Could not pick winner.";
        }

        $app->response()->getHeaders()->addHeaderLine('Content-Type', 'application/json');
        $app->response()->setContent(json_encode($result));
    }

    /**
     * @param App $app
     */
    public function updateStatusAction(App $app)
    {
        $post = $app->request()->getPost();
        if ($post->count()) {

            $id = filter_var($post->get('id'), FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_HIGH);
            $status = filter_var($post->get('status'), FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_HIGH);
            $prev_status = filter_var($post->get('prev_status'), FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_HIGH);

            if ($id && $status && $prev_status) {

                $azure = new AzureHelper($this->config);
                // send a message to the appropriate subscription
                switch ($status) {
                    case Entry::STATUS_APPROVED:
                        $queue = $azure->getToApproveQueueName();
                        break;

                    case Entry::STATUS_DENIED:
                        $queue = $azure->getToDeniedQueueName();
                        break;

                    case Entry::STATUS_NEW:
                        $queue = $azure->getToIncomingQueueName();
                        break;

                    // TODO add winners
                }

                $connectionString = $azure->getServiceBusString();
                $serviceBusRestProxy = ServicesBuilder::getInstance()->createServiceBusService($connectionString);
                try {
                    // Create message.
                    $message = new BrokeredMessage();
                    $message->setBody("Update Entry Status");
                    $message->setProperty('id', $id);
                    $message->setProperty('status', $status);
                    $message->setProperty('prev_status', $prev_status);

                    // Send message.
                    $serviceBusRestProxy->sendQueueMessage($queue, $message);
                    $result['success'] = 1;
                }
                catch(ServiceException $e){
                    // Handle exception based on error codes and messages.
                    // Error codes and messages are here:
                    // http://msdn.microsoft.com/en-us/library/windowsazure/hh780775
                    $code = $e->getCode();
                    $error_message = $e->getMessage();
                    echo $code.": ".$error_message."<br />";
                }

                //$result = $this->changeApprovalStatus($id, $prev_status, $status);

            }
        }
        if (empty($result)) {
            $result['success'] = 0;
            $result['message'] = "Could not save status update.";
        }

        $app->response()->getHeaders()->addHeaderLine('Content-Type', 'application/json');
        $app->response()->setContent(json_encode($result));
    }

    /**
     * @param App $app
     */
    public function latestIncomingAction(App $app)
    {
        $query = $app->request()->getQuery();
        if ($query->count()) {
            $result = [];
            if ($since = filter_var($query->since, FILTER_VALIDATE_INT)) {
                $date = new \DateTime("@" . $since);
                $repo = $this->getRepo();
                $view     = $app->view();

                // do we have new entries
                if ($repo->getIncomingSince($date) && $repo->count()) {
                    $result['entry'] = [];
                    foreach ($repo as $entry) {
                        $result['entry'][]  = $view->render('admin/partial.entry.phtml',
                            ['entry' => $entry, 'container_url' => $this->view->container_url]);
                    }

                    $result['entry'] = array_reverse($result['entry']);
                }

                $result['updated'] = time();

                $app->response()->getHeaders()->addHeaderLine('Content-Type', 'application/json');
                $app->response()->setContent(json_encode($result));
            }
        }
    }

    /**
     * @param $id
     * @param $prev_status
     * @param $status
     * @return array
     */
    protected function changeApprovalStatus($id, $prev_status, $status)
    {
// get entries



        $result = array();
        // set will validate status
        try {
            if (Entry::STATUS_WINNER == $status) {
                $repo = $this->getRepo();
                if ($repo->makeWinner($entry)) {
                    $result['success'] = 1;
                } else {
                    $result['success'] = 0;
                    $result['message'] = "No valid winner found.";
                }
                return $result;
            } else {
                $entry->setStatus($status);
                $entry->save();
                $result['success'] = 1;
            }
        } catch (\Exception $e) {
            $result['success'] = 0;
            $result['message'] = "Could not save status update: " . $e->getMessage();
            return $result;
        }
        return $result;
    }
}