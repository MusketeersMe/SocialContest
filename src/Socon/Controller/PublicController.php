<?php

namespace Socon\Controller;

use Phlyty\App;
use Socon\Controller;

class PublicController extends Controller
{
    public function init(\Phlyty\App $app)
    {
        $this->view->page = $this->config->page;
        $this->view->hashtags = $this->config->hashtags->toArray();
        $this->view->contestInfo = $this->config->contest;

        // build blob container base url
        $this->view->container_url = sprintf(
            'http://%s.blob.core.windows.net/%s',
            $this->config->azure->storage->name,
            $this->config->azure->storage->image_container
        );
    }

    /**
     * @param App $app
     */
    public function indexAction(App $app)
    {
        $this->view->css[] = '/css/front.css';
        $this->view->css[] = '/blueprintverticaltimeline/css/component.css';
        $this->view->js[] = '/js/update.js';

        $this->view->project = $this->config->page->title;

        $this->view->page = new \StdClass;
        $this->view->page->title = $this->config->page->title;

        // assume none
        $this->view->entries = false;
        $this->view->winners = false;

        $winners = $this->getRepo();
        if ($winners->getWinners()) {
            $this->view->winners = $winners;
        }

        $entries = $this->getRepo();
        if ($entries->getLatestEntries()) {
            $this->view->entries = $entries;
        }

        // next winner is top of the hour
        $next_winner = new \DateTime("next hour");
        $this->view->next_winner = $next_winner->format('g:00a');

        // output HTML
        $app->render('index.phtml', $this->view);
    }

    /**
     * @param App $app
     */
    public function updatesAction(App $app)
    {
        $query = $app->request()->getQuery();
        if ($query->count()) {
            $result = [];
            if ($since = filter_var($query->since, FILTER_VALIDATE_INT)) {
                $date = new \DateTime("@" . $since);
                $repo = $this->getRepo();
                $view     = $app->view();

                // do we have a new winner?
                if ($winner = $repo->getWinnerSince($date)) {
                    $result['winner']  = $view->render('partial.winner.phtml',
                        ['entry' => $winner, 'container_url' => $this->view->container_url]
                    );
                    // next winner is top of the hour
                    $next_winner = new \DateTime("next hour");
                    $result['next_winner'] = $next_winner->format('g:00a');
                }

                // do we have new entries
                if ($repo->getApprovedSince($date) && $repo->count()) {
                    $result['entry'] = [];
                    foreach ($repo as $entry) {
                        $result['entry'][]  = $view->render('partial.winner.phtml',
                            ['entry' => $entry, 'container_url' => $this->view->container_url]
                        );
                    }

                    $result['entry'] = array_reverse($result['entry']);
                }

                $result['updated'] = time();

                echo json_encode($result);
            }
        }
    }

    /**
     * @param App $app
     */
    public function nextWinnerAction(App $app)
    {
        $this->view->contestInfo = $this->config->contest;
        $result = [];
        $app->render('partial.next_winner.phtml', $this->view);
        $result['next_winner'] = $app->response()->getContent();
        $app->response()->getHeaders()->addHeaderLine('Content-Type', 'application/json');
        $app->response()->setContent(json_encode($result));
    }
}
